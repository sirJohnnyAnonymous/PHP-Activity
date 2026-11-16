<?php if (isset($_SESSION['username'])) { ?>
    <h1>Hello there!! <?php echo $_SESSION['username']; ?></h1>
    <?php include 'navbar.php'; ?>
<?php } else { echo "<h1>No user logged in</h1>";}?>

<h3>Users List</h3>
<ul>
    <?php $getAllUsers = getAllUsers($pdo); ?>
    <?php foreach ($getAllUsers as $row) { ?>
        <li>
            <a href="viewuser.php?user_id=<?php echo $row['user_id']; ?>"><?php echo $row['username']; ?></a>
        </li>
    <?php } ?>
</ul>
