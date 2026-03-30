<?php
// includes/header.php
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | CharityHub' : 'CharityHub — Online Donation Management' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>css/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>index.php">
        <span class="brand-icon">❤</span>
        <span>CharityHub</span>
    </a>
    <ul class="nav-links">
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>index.php" <?= $current=='index.php'?'class="active"':'' ?>>Dashboard</a></li>
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>pages/donors.php" <?= $current=='donors.php'?'class="active"':'' ?>>Donors</a></li>
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>pages/charities.php" <?= $current=='charities.php'?'class="active"':'' ?>>Charities</a></li>
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>pages/campaigns.php" <?= $current=='campaigns.php'?'class="active"':'' ?>>Campaigns</a></li>
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>pages/donations.php" <?= $current=='donations.php'?'class="active"':'' ?>>Donations</a></li>
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>pages/payments.php" <?= $current=='payments.php'?'class="active"':'' ?>>Payments</a></li>
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>pages/report.php" class="nav-report <?= $current=='report.php'?'active':'' ?>">📊 Report</a></li>
        <li><a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>donor_login.php" style="color:var(--gold-light)" <?= $current=='donor_login.php'?'class="active"':'' ?>>❤ Donor Login</a></li>
    </ul>
</nav>
