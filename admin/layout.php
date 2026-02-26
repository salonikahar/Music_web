<!-- admin/layout.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - PulseWave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-theme.css">
    <link rel="stylesheet" href="../assets/css/notification.css">
    <script src="../assets/js/notification.js"></script>
    
    <style>
        body {
            
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 240px;
            padding: 24px;
            min-height: 100vh;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?= $content ?>
    </div>

</body>

</html>


