const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const overlay = document.getElementById('gameOverlay');
const message = document.getElementById('gameMessage');
const startBtn = document.getElementById('startGameBtn');

// Game constants
const GRID_SIZE = 20;
const CANVAS_WIDTH = 800;
const CANVAS_HEIGHT = 600;
const MAX_SNEAK_METER = 100;
const TIMER_TOTAL = 60; // 1 minute in seconds

// Audio Objects
const bgMusic = new Audio('../gamemusic1.mp3');
bgMusic.loop = true;
const alertMusic = new Audio('../alerted.mp3');
alertMusic.loop = true;

// Game state
let gameRunning = false;
let hasItem = false;
let player = { x: 40, y: 40, radius: 10, speed: 3 };
let item = { x: CANVAS_WIDTH - 50, y: CANVAS_HEIGHT - 50, radius: 12, collected: false };
let sneakMeter = MAX_SNEAK_METER;
let timeLeft = TIMER_TOTAL;
let lastTimestamp = 0;
let speedBoosted = false;

let guards = [];
let walls = [];
let keys = {};

// Listeners
window.addEventListener('keydown', e => keys[e.code] = true);
window.addEventListener('keyup', e => keys[e.code] = false);

startBtn.addEventListener('click', function () {
    if (startBtn.textContent === "Close Game") {
        closeGame();
    } else {
        startGame();
    }
});

function generateLevel() {
    walls = [];
    guards = [];

    // Generate Random Walls (10-15 blocks to keep it playable)
    const wallCount = 10 + Math.floor(Math.random() * 6);
    let attempts = 0;

    while (walls.length < wallCount && attempts < 100) {
        attempts++;
        let w = 60 + Math.floor(Math.random() * 80);
        let h = 60 + Math.floor(Math.random() * 80);
        let x = Math.random() * (CANVAS_WIDTH - w);
        let y = Math.random() * (CANVAS_HEIGHT - h);

        // Safety Zones (No walls here)
        // 1. Start area
        if (x < 150 && y < 150) continue;
        // 2. Item area
        if (x + w > CANVAS_WIDTH - 150 && y + h > CANVAS_HEIGHT - 150) continue;

        // PREVENT OVERLAP: Check against existing walls
        // Add a padding of 40px between walls to ensure player (20px wide) can fit
        const padding = 45;
        let overlaps = walls.some(other => {
            return (x < other.x + other.w + padding &&
                x + w + padding > other.x &&
                y < other.y + other.h + padding &&
                y + h + padding > other.y);
        });

        if (!overlaps) {
            walls.push({ x, y, w, h });
        }
    }

    // Generate Guards (Increased count by 3: was 4-5, now 7-8)
    const guardCount = 7 + Math.floor(Math.random() * 2);
    for (let i = 0; i < guardCount; i++) {
        let gx, gy, path, speed;
        let validGuard = false;
        let gAttempts = 0;

        while (!validGuard && gAttempts < 50) {
            gAttempts++;
            gx = 100 + Math.random() * (CANVAS_WIDTH - 200);
            gy = 100 + Math.random() * (CANVAS_HEIGHT - 200);

            // 1. Check if start position is inside wall
            let insideWall = walls.some(w => gx > w.x - 20 && gx < w.x + w.w + 20 && gy > w.y - 20 && gy < w.y + w.h + 20);
            if (insideWall) continue;

            // 2. Try to generate a valid path
            let patrolX = (Math.random() > 0.5);
            let patrolDist = 80 + Math.random() * 120;
            let endX = gx, endY = gy;

            if (patrolX) endX = Math.min(CANVAS_WIDTH - 40, gx + patrolDist);
            else endY = Math.min(CANVAS_HEIGHT - 40, gy + patrolDist);

            // 3. Check if path segments cross any walls
            let pathBlocked = walls.some(w => lineRectIntersection(gx, gy, endX, endY, w));

            if (!pathBlocked) {
                path = [{ x: gx, y: gy }, { x: endX, y: endY }];
                speed = 0.7 + Math.random() * 0.8;
                validGuard = true;
            }
        }

        if (validGuard) {
            guards.push({
                x: gx, y: gy,
                radius: 12,
                path: path,
                targetIdx: 1,
                speed: speed,
                baseSpeed: speed,
                angle: 0
            });
        }
    }
}

function startGame() {
    if (gameRunning) return;

    generateLevel();

    gameRunning = true;
    hasItem = false;
    player.x = 40;
    player.y = 40;
    item.collected = false;
    item.x = CANVAS_WIDTH - 50;
    item.y = CANVAS_HEIGHT - 50;
    sneakMeter = MAX_SNEAK_METER;
    timeLeft = TIMER_TOTAL;
    speedBoosted = false;
    lastTimestamp = performance.now();

    // Start Audio
    stopAudio();
    bgMusic.currentTime = 0;
    bgMusic.play().catch(e => console.log("Audio play blocked by browser."));

    overlay.style.display = 'none';
    requestAnimationFrame(gameLoop);
}

function stopAudio() {
    bgMusic.pause();
    alertMusic.pause();
    alertMusic.currentTime = 0;
}

async function openGame() {
    document.getElementById('gameModal').style.display = 'flex';
    message.textContent = "Checking for available missions...";
    message.style.color = "#ff7a00";
    overlay.style.display = 'flex';
    startBtn.style.display = 'none';

    try {
        const response = await fetch('api/check_coupons.php');
        const data = await response.json();
        if (data.success && data.count > 0) {
            message.textContent = "Random Mission Generated. Avoid detection!";
            startBtn.style.display = 'inline-block';
            startBtn.textContent = "Start Mission";
        } else {
            message.textContent = data.error || "You used all the available coupons";
            message.style.color = "#ff4757";
            startBtn.style.display = 'inline-block';
            startBtn.textContent = "Close Game";
        }
    } catch (e) {
        message.textContent = "Error checking mission availability.";
        startBtn.style.display = 'inline-block';
        startBtn.textContent = "Close Game";
    }

    gameRunning = false;
    generateLevel(); // Generate for visual background
    draw();
}

function closeGame() {
    document.getElementById('gameModal').style.display = 'none';
    gameRunning = false;
    stopAudio();
    ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
}

function gameLoop(timestamp) {
    if (!gameRunning) return;
    const deltaTime = (timestamp - lastTimestamp) / 1000;
    lastTimestamp = timestamp;
    update(deltaTime);
    draw();
    if (gameRunning) requestAnimationFrame(gameLoop);
}

function update(dt) {
    if (!dt) dt = 0.016;

    if (timeLeft > 0) {
        timeLeft -= dt;
        if (timeLeft <= 0) {
            timeLeft = 0;
            if (!speedBoosted) {
                speedBoosted = true;
                guards.forEach(g => g.speed *= 2);
                bgMusic.pause();
                alertMusic.play().catch(e => { });
            }
        }
    }

    let nextX = player.x;
    let nextY = player.y;
    if (keys['ArrowUp'] || keys['KeyW']) nextY -= player.speed;
    if (keys['ArrowDown'] || keys['KeyS']) nextY += player.speed;
    if (keys['ArrowLeft'] || keys['KeyA']) nextX -= player.speed;
    if (keys['ArrowRight'] || keys['KeyD']) nextX += player.speed;

    if (nextX < player.radius) nextX = player.radius;
    if (nextX > CANVAS_WIDTH - player.radius) nextX = CANVAS_WIDTH - player.radius;
    if (nextY < player.radius) nextY = player.radius;
    if (nextY > CANVAS_HEIGHT - player.radius) nextY = CANVAS_HEIGHT - player.radius;

    let canMoveX = true;
    let canMoveY = true;
    for (let wall of walls) {
        if (circleRectCollision(nextX, player.y, player.radius, wall)) canMoveX = false;
        if (circleRectCollision(player.x, nextY, player.radius, wall)) canMoveY = false;
    }
    if (canMoveX) player.x = nextX;
    if (canMoveY) player.y = nextY;

    if (!item.collected) {
        let dist = Math.hypot(player.x - item.x, player.y - item.y);
        if (dist < player.radius + item.radius) {
            item.collected = true;
            hasItem = true;
        }
    } else {
        if (player.x < 70 && player.y < 70) winGame();
    }

    let isDetected = false;
    for (let guard of guards) {
        let target = guard.path[guard.targetIdx];
        let dx = target.x - guard.x;
        let dy = target.y - guard.y;
        let dist = Math.hypot(dx, dy);
        if (dist < 2) {
            guard.targetIdx = (guard.targetIdx + 1) % guard.path.length;
        } else {
            guard.x += (dx / dist) * guard.speed;
            guard.y += (dy / dist) * guard.speed;
            guard.angle = Math.atan2(dy, dx);
        }
        if (checkDetection(guard, player)) isDetected = true;
    }

    if (isDetected) {
        sneakMeter -= dt * 60;
        if (sneakMeter <= 0) {
            sneakMeter = 0;
            gameOver("Stealth compromised! Detected!");
        }
    } else {
        sneakMeter = Math.min(MAX_SNEAK_METER, sneakMeter + dt * 15);
    }
}

function circleRectCollision(cx, cy, r, rect) {
    let closestX = Math.max(rect.x, Math.min(cx, rect.x + rect.w));
    let closestY = Math.max(rect.y, Math.min(cy, rect.y + rect.h));
    let dist = Math.hypot(cx - closestX, cy - closestY);
    return dist < r;
}

function checkDetection(guard, player) {
    let dist = Math.hypot(player.x - guard.x, player.y - guard.y);
    if (dist > 120) return false;
    for (let wall of walls) {
        if (lineRectIntersection(guard.x, guard.y, player.x, player.y, wall)) return false;
    }
    if (dist < 35) return true;
    let angleToPlayer = Math.atan2(player.y - guard.y, player.x - guard.x);
    let diff = Math.abs(angleToPlayer - guard.angle);
    if (diff > Math.PI) diff = 2 * Math.PI - diff;
    return diff < Math.PI / 6;
}

function lineRectIntersection(x1, y1, x2, y2, rect) {
    const rx = rect.x, ry = rect.y, rw = rect.w, rh = rect.h;
    return lineLineIntersection(x1, y1, x2, y2, rx, ry, rx + rw, ry) ||
        lineLineIntersection(x1, y1, x2, y2, rx + rw, ry, rx + rw, ry + rh) ||
        lineLineIntersection(x1, y1, x2, y2, rx + rw, ry + rh, rx, ry + rh) ||
        lineLineIntersection(x1, y1, x2, y2, rx, ry + rh, rx, ry);
}

function lineLineIntersection(x1, y1, x2, y2, x3, y3, x4, y4) {
    const den = (y4 - y3) * (x2 - x1) - (x4 - x3) * (y2 - y1);
    if (den === 0) return false;
    let ua = ((x4 - x3) * (y1 - y3) - (y4 - y3) * (x1 - x3)) / den;
    let ub = ((x2 - x1) * (y1 - y3) - (y2 - y1) * (x1 - x3)) / den;
    return (ua >= 0 && ua <= 1) && (ub >= 0 && ub <= 1);
}

function draw() {
    ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
    ctx.fillStyle = 'rgba(46, 213, 115, 0.2)';
    ctx.fillRect(0, 0, 80, 80);
    ctx.strokeStyle = '#2ed573';
    ctx.strokeRect(5, 5, 70, 70);

    ctx.fillStyle = '#333';
    for (let wall of walls) {
        ctx.fillRect(wall.x, wall.y, wall.w, wall.h);
        ctx.strokeStyle = '#444';
        ctx.strokeRect(wall.x, wall.y, wall.w, wall.h);
    }

    if (!item.collected) {
        ctx.fillStyle = '#FFD700';
        ctx.beginPath();
        ctx.arc(item.x, item.y, item.radius, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 10; ctx.shadowColor = '#FFD700';
        ctx.stroke(); ctx.shadowBlur = 0;
    }

    for (let guard of guards) {
        ctx.fillStyle = 'rgba(255, 68, 68, 0.1)';
        ctx.beginPath();
        ctx.moveTo(guard.x, guard.y);
        ctx.arc(guard.x, guard.y, 120, guard.angle - Math.PI / 6, guard.angle + Math.PI / 6);
        ctx.fill();
        ctx.fillStyle = '#ff4444';
        ctx.beginPath();
        ctx.arc(guard.x, guard.y, guard.radius, 0, Math.PI * 2);
        ctx.fill();
    }

    ctx.fillStyle = '#ff7a00';
    ctx.beginPath();
    ctx.arc(player.x, player.y, player.radius, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = '#fff'; ctx.lineWidth = 2; ctx.stroke();

    drawUI();

    if (hasItem) {
        ctx.fillStyle = '#FFD700';
        ctx.beginPath();
        ctx.arc(player.x, player.y, 4, 0, Math.PI * 2);
        ctx.fill();
    }

    if (hasItem && !gameRunning && message.textContent.includes("Complete")) {
    } else if (hasItem) {
        ctx.fillStyle = "#fff"; ctx.font = "14px Poppins";
        ctx.fillText("Got the Key! Return to Start!", 10, CANVAS_HEIGHT - 60);
    }
}

function drawUI() {
    const mins = Math.floor(timeLeft / 60);
    const secs = Math.floor(timeLeft % 60);
    const timerStr = `${mins}:${secs.toString().padStart(2, '0')}`;
    ctx.font = "bold 20px Orbitron";
    ctx.fillStyle = timeLeft < 30 ? "#ff4757" : "#ff7a00";
    ctx.textAlign = "right";
    ctx.fillText(timerStr, CANVAS_WIDTH - 20, 30);
    if (speedBoosted) {
        ctx.font = "12px Poppins"; ctx.fillStyle = "#ff4757";
        ctx.fillText("GUARDS ENRAGED!", CANVAS_WIDTH - 20, 50);
    }

    const barWidth = 150, barHeight = 10, x = 20, y = CANVAS_HEIGHT - 30;
    ctx.fillStyle = "rgba(0,0,0,0.5)";
    ctx.fillRect(x, y, barWidth, barHeight);
    const fillWidth = (sneakMeter / MAX_SNEAK_METER) * barWidth;
    let barColor = "#2ed573";
    if (sneakMeter < 60) barColor = "#ffa502";
    if (sneakMeter < 30) barColor = "#ff4757";
    ctx.fillStyle = barColor;
    ctx.fillRect(x, y, fillWidth, barHeight);
    ctx.font = "12px Poppins"; ctx.fillStyle = "#fff"; ctx.textAlign = "left";
    ctx.fillText("STEALTH", x, y - 5);
}

function gameOver(msg) {
    gameRunning = false;
    stopAudio();
    message.textContent = msg;
    message.style.color = "#ff4444";
    startBtn.textContent = "Retry Mission";
    overlay.style.display = 'flex';
}

async function winGame() {
    gameRunning = false;
    stopAudio();
    message.textContent = "Objective Complete!";
    message.style.color = "#2ed573";
    try {
        const response = await fetch('api/get_random_coupon.php');
        const data = await response.json();
        if (data.success) {
            message.innerHTML = `Success! Your Coupon:<br><span id="wonCouponCode" style="color: #fff; font-size: 32px; cursor: pointer;" onclick="navigator.clipboard.writeText(this.innerText)">${data.code}</span><br><small style="color: #aaa">Code applied. Click to copy.</small>`;
            document.getElementById('couponCode').value = data.code;
            startBtn.textContent = "Close Game";
        } else {
            message.innerHTML = `<span style="color: #ff4757; font-size: 18px;">${data.error}</span>`;
            startBtn.textContent = "Close Game";
        }
    } catch (e) {
        message.textContent = "Error claiming reward.";
        startBtn.textContent = "Close";
    }
    overlay.style.display = 'flex';
}
