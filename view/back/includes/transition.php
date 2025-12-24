<!-- Fox Walking Transition Screen -->
<div class="transition-screen" id="transitionScreen" style="background: #0a0a12 !important;">
    <div class="fox-walking-container">
        <div class="walking-fox"></div>
        <div class="fox-loading-text">Nine Tailed Fox</div>
    </div>
</div>

<script>
    // Global transition handler
    window.addEventListener('load', () => {
        const transitionScreen = document.getElementById('transitionScreen');
        if (transitionScreen) {
            setTimeout(() => {
                transitionScreen.classList.add('hidden');
            }, 1200);
        }
    });
</script>
