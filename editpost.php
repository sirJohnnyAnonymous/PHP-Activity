<body>
    <?php if (isset($_SESSION['message'])) { ?>
        <h1 style="color: red;"><?php echo $_SESSION['message']; ?></h1>
    <?php } unset($_SESSION['message']); ?>


    <?php if (isset($_SESSION['username'])) { ?>
        <h1>Hello there!! <?php echo $_SESSION['username']; ?></h1>
        <?php include 'navbar.php'; ?>
    <?php } else { echo "<h1>No user logged in</h1>";}?>

    <?php $getPostByID = getPostByID($pdo, $_GET['user_post_id']); ?>

    <form action="core/handleForms.php?user_post_id=<?php echo $_GET['user_post_id']; ?>" method="POST">
        <p>
            <label for="username">Title</label>
            <input type="text" name="title" value="<?php echo $getPostByID['title']; ?>">
        </p>
        <p><label for="username">Body</label></p>
        <p><textarea name="body" rows="10" cols="50"><?php echo $getPostByID['body']; ?></textarea></p>
        <p><input type="submit" name="editPostBtn"></p>
    </form>
