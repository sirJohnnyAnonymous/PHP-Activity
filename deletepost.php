<?php if (isset($_SESSION['username'])) { ?>
    <h1>Hello there!! <?php echo $_SESSION['username']; ?>. Are you sure you want to delete this post?</h1>
    <?php include 'navbar.php'; ?>
<?php } else { echo "<h1>No user logged in</h1>";}?>

<?php $getPostByID = getPostByID($pdo, $_GET['user_post_id']); ?>

<div class="postContainer" style="border-style: solid; width: 50%; height: 300px; margin-top: 20px;">
    <h2><?php echo $getPostByID['title']; ?></h2>
    <p><?php echo $getPostByID['date_added']; ?></p>
    <p><?php echo $getPostByID['body']; ?></p>

    <form action="core/handleForms.php?user_post_id=<?php echo $_GET['user_post_id']; ?>" method="POST">
        <input type="submit" name="deletePostBtn" style="float: right; margin-right: 10px;" value="Delete">
    </form>

</div>
