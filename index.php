<?php

session_start();
require_once 'auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$host = 'localhost'; 
$dbname = 'computers'; 
$user = 'erik'; 
$pass = 'erik';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Handle systems search
$search_results = null;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $search_sql = 'SELECT entry_id, cpu, gpu, ram FROM systems WHERE cpu LIKE :search';
    $search_stmt = $pdo->prepare($search_sql);
    $search_stmt->execute(['search' => $search_term]);
    $search_results = $search_stmt->fetchAll();
}

$sql = 'SELECT entry_id, cpu, gpu, ram FROM systems';
$stmt = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Erik's Prebuild Desktop PCs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="hero">
        <h1>Erik's Prebuild Desktop PCs</h1>
        <p>"Here you'll find what I think are the best desktop pcs for each purpose."</p>
    </div>

    <div class="search-bar">
        <h3>Search by CPU:</h3>
        <form action="" method="GET">
            <input type="text" id="search" name="search"/>
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (isset($_GET['search'])): ?>
        <h3 style="margin: 40px">Search Results:</h3>
        <div class="search-results">
            <?php if ($search_results && count($search_results) > 0): ?>
                <?php foreach ($search_results as $row): ?>
                    <div class="result-box">
                        <p><strong>Entry ID:</strong> <?php echo htmlspecialchars($row['entry_id']); ?></p>
                        <p><strong>CPU:</strong> <?php echo htmlspecialchars($row['cpu']); ?></p>
                        <p><strong>GPU:</strong> <?php echo htmlspecialchars($row['gpu']); ?></p>
                        <p><strong>RAM:</strong> <?php echo htmlspecialchars($row['ram']); ?></p>
                        <form action="index.php" method="post">
                            <input type="hidden" name="delete_id" value="<?php echo $row['entry_id']; ?>">
                            <input type="submit" value="Delete">
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No systems found matching your search.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>
</html>