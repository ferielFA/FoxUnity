<?php
/**
 * Script de test pour vérifier le diagramme des catégories
 * Accédez à: http://localhost/foxunity/view/back/test_chart.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/reclamationcontroller.php';

$reclamationController = new ReclamationController();
$categoryStats = $reclamationController->getStatsByCategory();

// Trier par nombre décroissant
arsort($categoryStats);

$categoryLabels = [];
$categoryData = [];
$categoryColors = [
    'Account Issues' => '#2196F3',
    'Payment & Billing' => '#4CAF50',
    'Technical Support' => '#FF9800',
    'Shop & Orders' => '#9C27B0',
    'Trading Issues' => '#FF5722',
    'Events & Tournaments' => '#E91E63',
    'Charity & Donations' => '#00BCD4',
    'Feedback & Suggestions' => '#8BC34A',
    'Other' => '#9E9E9E'
];

$categoryColorsArray = [];

foreach ($categoryStats as $category => $count) {
    $categoryLabels[] = $category;
    $categoryData[] = $count;
    $categoryColorsArray[] = $categoryColors[$category] ?? '#9E9E9E';
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: Arial;
            padding: 20px;
        }
        .info {
            background: rgba(20, 20, 20, 0.95);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .chart-container {
            background: rgba(20, 20, 20, 0.95);
            padding: 30px;
            border-radius: 15px;
            border: 2px solid rgba(255, 122, 0, 0.3);
        }
        pre {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Test du Diagramme des Catégories</h1>
    
    <div class="info">
        <h2>Données récupérées:</h2>
        <pre><?php print_r($categoryStats); ?></pre>
    </div>
    
    <div class="info">
        <h2>Labels:</h2>
        <pre><?php print_r($categoryLabels); ?></pre>
    </div>
    
    <div class="info">
        <h2>Données:</h2>
        <pre><?php print_r($categoryData); ?></pre>
    </div>
    
    <div class="info">
        <h2>Couleurs (dans l'ordre):</h2>
        <pre><?php print_r($categoryColorsArray); ?></pre>
    </div>
    
    <div class="chart-container">
        <h2>Diagramme:</h2>
        <div style="position: relative; height: 400px;">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <script>
        console.log('Chart.js chargé:', typeof Chart !== 'undefined');
        console.log('Labels:', <?php echo json_encode($categoryLabels); ?>);
        console.log('Data:', <?php echo json_encode($categoryData); ?>);
        console.log('Colors:', <?php echo json_encode($categoryColorsArray); ?>);
        
        if (typeof Chart !== 'undefined') {
            const ctx = document.getElementById('categoryChart');
            if (ctx) {
                const chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($categoryLabels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($categoryData); ?>,
                            backgroundColor: <?php echo json_encode($categoryColorsArray); ?>,
                            borderColor: '#0a0a0a',
                            borderWidth: 4,
                            hoverBorderWidth: 6,
                            hoverOffset: 12
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right',
                                labels: {
                                    color: '#fff',
                                    padding: 15,
                                    font: {
                                        size: 14
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(20, 20, 20, 0.98)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255, 122, 0, 0.8)',
                                borderWidth: 2,
                                padding: 15
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true,
                            duration: 2000
                        }
                    }
                });
                console.log('Graphique créé avec succès');
            } else {
                console.error('Canvas non trouvé');
            }
        } else {
            console.error('Chart.js non chargé');
        }
    </script>
</body>
</html>








