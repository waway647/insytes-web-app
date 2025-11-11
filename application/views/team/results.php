<!DOCTYPE html>
<html>
<head>
    <title>Player Performance</title>
</head>
<body>
    <h1>Player Performance Evaluation</h1>
    <?php if (isset($results['predictions'][0])): ?>
        <p><strong>Predicted Score:</strong> <?= number_format($results['predictions'][0], 2) ?> (<?= $results['categories'][0] ?>)</p>
        <h3>Player Stats:</h3>
        <table border="1">
            <?php foreach ($player_data as $key => $value): ?>
                <tr><td><?= htmlspecialchars($key) ?></td><td><?= htmlspecialchars($value) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No results available.</p>
    <?php endif; ?>
</body>
</html>