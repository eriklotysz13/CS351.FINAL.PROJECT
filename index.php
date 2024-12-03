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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_system'])) {
        if (isset($_POST['cpu']) && isset($_POST['gpu']) && isset($_POST['ram'])) {
            // Insert new entry
            $cpu = htmlspecialchars($_POST['cpu']);
            $gpu = htmlspecialchars($_POST['gpu']);
            $ram = htmlspecialchars($_POST['ram']);
            
            $insert_sql = 'INSERT INTO systems (cpu, gpu, ram) VALUES (:cpu, :gpu, :ram)';
            $stmt_insert = $pdo->prepare($insert_sql);
            $stmt_insert->execute(['cpu' => $cpu, 'gpu' => $gpu, 'ram' => $ram]);
        } elseif (isset($_POST['delete_id'])) {
            // Delete an entry
            $delete_id = (int) $_POST['delete_id'];
            
            $delete_sql = 'DELETE FROM systems WHERE entry_id = :entry_id';
            $stmt_delete = $pdo->prepare($delete_sql);
            $stmt_delete->execute(['entry_id' => $delete_id]);
        }
    }

    // Handle update action
    if (isset($_POST['update_id'])) { 
        $update_id = (int)$_POST['update_id'];
        $cpu = htmlspecialchars($_POST['cpu']);
        $gpu = htmlspecialchars($_POST['gpu']);
        $ram = htmlspecialchars($_POST['ram']);
        
        $update_sql = 'UPDATE systems SET cpu = :cpu, gpu = :gpu, ram = :ram WHERE entry_id = :entry_id';
        $stmt_update = $pdo->prepare($update_sql);
        $stmt_update->execute([
            'cpu' => $cpu,
            'gpu' => $gpu,
            'ram' => $ram,
            'entry_id' => $update_id,
        ]);
    }
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
    <!-- Hero section -->
    <div class="hero">
        <h1 style="font-size: 60px">Erik's Prebuild Desktop PCs</h1>
        <p style="font-size: 25px">"Here you'll find what I think are the best desktop PC combos."</p>
        <a href="mailto:erikdesktops@contact.com" class="hero-button">Contact Me</a>
    </div>

    <!-- Search section and output -->
    <div class="search-bar">
        <h3 style="font-size: 28px">Search by CPU:</h3>
        <form method="GET">
            <input type="text" id="search" name="search"/>
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (isset($_GET['search'])): ?>
        <h3 style="margin: 40px; font-size: 28px">Search Results:</h3>
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

    <!-- Main table with systems -->
    <div class="main-table-container">
        <h2 style="margin: 20px">All Systems:</h2>
        <table class="main-table" style="margin: 20px">
            <thead>
                <tr>
                    <th>ENTRY_ID</th>
                    <th>CPU</th>
                    <th>GPU</th>
                    <th>RAM</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['entry_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['cpu']); ?></td>
                    <td><?php echo htmlspecialchars($row['gpu']); ?></td>
                    <td><?php echo htmlspecialchars($row['ram']); ?></td>
                    <td>
                        <button type="button" onclick="document.getElementById('update-form-<?php echo $row['entry_id']; ?>').style.display = 'block';">Update</button>
                        <div id="update-form-<?php echo $row['entry_id']; ?>" style="display:none;">
                            <form action="index.php" method="post">
                                <input type="hidden" name="update_id" value="<?php echo $row['entry_id']; ?>">
                                <label for="cpu-<?php echo $row['entry_id']; ?>">CPU:</label>
                                <input type="text" id="cpu-<?php echo $row['entry_id']; ?>" name="cpu" value="<?php echo htmlspecialchars($row['cpu']); ?>" required>
                                <br>
                                <label for="gpu-<?php echo $row['entry_id']; ?>">GPU:</label>
                                <input type="text" id="gpu-<?php echo $row['entry_id']; ?>" name="gpu" value="<?php echo htmlspecialchars($row['gpu']); ?>" required>
                                <br>
                                <label for="ram-<?php echo $row['entry_id']; ?>">RAM:</label>
                                <input type="text" id="ram-<?php echo $row['entry_id']; ?>" name="ram" value="<?php echo htmlspecialchars($row['ram']); ?>" required>
                                <br>
                                <input type="submit" value="Update System">
                            </form>
                        </div>
                        <form action="index.php" method="post" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $row['entry_id']; ?>">
                            <input type="submit" value="Delete">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add a system form -->
    <div class="main-table-container" style="width: 30%;">
        <h2>Add your own System!</h2>
        <form action="index.php" method="post">
            <label for="cpu">CPU:</label>
            <input type="text" id="cpu" name="cpu" required>
            <br><br>
            <label for="gpu">GPU:</label>
            <input type="text" id="gpu" name="gpu" required>
            <br><br>
            <label for="ram">RAM:</label>
            <input type="text" id="ram" name="ram" required>
            <br><br>
            <input type="hidden" name="add_system" value="1">
            <input type="submit" value="Add System">
        </form>
    </div>

</body>
</html>