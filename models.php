<?php
function insertGameDev($pdo, $firstname, $lastname, $dateOfBirth, $specialization, $userId)
{
    $sql = 'INSERT INTO game_dev (firstname, lastname, date_of_birth, specialization, added_by, updated_by)
            VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$firstname, $lastname, $dateOfBirth, $specialization, $userId, $userId]);
}

function escapeLikeSearchTerm($searchTerm)
{
    return strtr($searchTerm, ['!' => '!!', '%' => '!%', '_' => '!_']);
}

function getAllGameDevs($pdo, $searchName = '')
{
    $sql = "SELECT game_dev.*,
                   creator.username AS added_by,
                   updater.username AS updated_by
            FROM game_dev
            LEFT JOIN user_passwords AS creator ON game_dev.added_by = creator.user_id
            LEFT JOIN user_passwords AS updater ON game_dev.updated_by = updater.user_id";

    if ($searchName !== '') {
        $sql .= " WHERE CONCAT_WS(' ', game_dev.firstname, game_dev.lastname) LIKE ? ESCAPE '!'";
        $stmt = $pdo->prepare($sql . ' ORDER BY game_dev.game_dev_id');
        $escapedSearchName = escapeLikeSearchTerm($searchName);
        $stmt->execute(['%' . $escapedSearchName . '%']);
    } else {
        $stmt = $pdo->query($sql . ' ORDER BY game_dev.game_dev_id');
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGameDevByID($pdo, $gameDevId)
{
    $sql = "SELECT game_dev.*,
                   creator.username AS added_by,
                   updater.username AS updated_by
            FROM game_dev
            LEFT JOIN user_passwords AS creator ON game_dev.added_by = creator.user_id
            LEFT JOIN user_passwords AS updater ON game_dev.updated_by = updater.user_id
            WHERE game_dev.game_dev_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$gameDevId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateGameDev($pdo, $firstname, $lastname, $dateOfBirth, $specialization, $gameDevId, $userId)
{
    $sql = 'UPDATE game_dev
            SET firstname = ?, lastname = ?, date_of_birth = ?, specialization = ?, updated_by = ?
            WHERE game_dev_id = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$firstname, $lastname, $dateOfBirth, $specialization, $userId, $gameDevId]);
}

function deleteGameDev($pdo, $gameDevId)
{
    $stmt = $pdo->prepare('DELETE FROM game_dev WHERE game_dev_id = ?');
    return $stmt->execute([$gameDevId]);
}

function getProjectsByGameDev($pdo, $gameDevId, $searchName = '')
{
    $sql = "SELECT game_project.game_project_id AS project_id,
                   game_project.project_name,
                   game_project.technologies_used,
                   game_project.date_added,
                   game_project.last_updated,
                   creator.username AS added_by,
                   updater.username AS updated_by,
                   CONCAT(game_dev.firstname, ' ', game_dev.lastname) AS project_owner
            FROM game_project
            JOIN game_dev ON game_project.game_dev_id = game_dev.game_dev_id
            LEFT JOIN user_passwords AS creator ON game_project.added_by = creator.user_id
            LEFT JOIN user_passwords AS updater ON game_project.updated_by = updater.user_id
            WHERE game_project.game_dev_id = ?";
    $params = [$gameDevId];

    if ($searchName !== '') {
        $sql .= " AND game_project.project_name LIKE ? ESCAPE '!'";
        $escapedSearchName = escapeLikeSearchTerm($searchName);
        $params[] = '%' . $escapedSearchName . '%';
    }

    $stmt = $pdo->prepare($sql . ' ORDER BY game_project.date_added DESC');
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertProject($pdo, $projectName, $technologiesUsed, $gameDevId, $userId)
{
    $sql = 'INSERT INTO game_project
                (game_project_id, project_name, technologies_used, game_dev_id, added_by, updated_by, last_updated)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        bin2hex(random_bytes(16)),
        $projectName,
        $technologiesUsed,
        $gameDevId,
        $userId,
        $userId
    ]);
}

function getProjectByID($pdo, $projectId)
{
    $sql = "SELECT game_project.game_project_id AS project_id,
                   game_project.project_name,
                   game_project.technologies_used,
                   game_project.date_added,
                   game_project.last_updated,
                   creator.username AS added_by,
                   updater.username AS updated_by,
                   CONCAT(game_dev.firstname, ' ', game_dev.lastname) AS project_owner
            FROM game_project
            JOIN game_dev ON game_project.game_dev_id = game_dev.game_dev_id
            LEFT JOIN user_passwords AS creator ON game_project.added_by = creator.user_id
            LEFT JOIN user_passwords AS updater ON game_project.updated_by = updater.user_id
            WHERE game_project.game_project_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$projectId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateProject($pdo, $projectName, $technologiesUsed, $projectId, $userId)
{
    $sql = 'UPDATE game_project
            SET project_name = ?, technologies_used = ?, updated_by = ?, last_updated = CURRENT_TIMESTAMP
            WHERE game_project_id = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$projectName, $technologiesUsed, $userId, $projectId]);
}

function deleteProject($pdo, $projectId)
{
    $stmt = $pdo->prepare('DELETE FROM game_project WHERE game_project_id = ?');
    return $stmt->execute([$projectId]);
}

function getUserByID($pdo, $userId)
{
    $stmt = $pdo->prepare('SELECT user_id, username, date_added FROM user_passwords WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertNewUser($pdo, $username, $passwordHash)
{
    $check = $pdo->prepare('SELECT user_id FROM user_passwords WHERE username = ?');
    $check->execute([$username]);
    if ($check->fetch()) {
        $_SESSION['message'] = 'That username is already registered.';
        return false;
    }

    $stmt = $pdo->prepare('INSERT INTO user_passwords (username, password) VALUES (?, ?)');
    $stmt->execute([$username, $passwordHash]);
    return true;
}

function loginUser($pdo, $username, $password)
{
    $stmt = $pdo->prepare('SELECT user_id, username, password FROM user_passwords WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['message'] = 'Username or password is invalid.';
        return false;
    }

    $storedPassword = (string) $user['password'];
    $passwordMatches = password_verify($password, $storedPassword);
    $legacyPassword = preg_match('/^[a-f0-9]{40}$/i', $storedPassword) === 1;
    if (!$passwordMatches && $legacyPassword && hash_equals(strtolower($storedPassword), sha1($password))) {
        $passwordMatches = true;
        $upgrade = $pdo->prepare('UPDATE user_passwords SET password = ? WHERE user_id = ?');
        $upgrade->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
    }

    if (!$passwordMatches) {
        $_SESSION['message'] = 'Username or password is invalid.';
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    return true;
}

function insertNewPost($pdo, $title, $body, $user_id) {

    $sql = "INSERT INTO user_posts (title, body, user_id) VALUES (?,?,?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt) {
        return $stmt->execute([$title,$body,$user_id]);
    }
}

function editAPost($pdo, $title, $body, $user_post_id) {

    $sql = "UPDATE user_posts SET title = ?, body = ? WHERE user_post_id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt) {
        return $stmt->execute([$title,$body,$user_post_id]);
    }
}

function deleteAPost($pdo, $user_post_id) {

    $sql = "DELETE FROM user_posts WHERE user_post_id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt) {
        return $stmt->execute([$user_post_id]);
    }
}

function getAllPosts($pdo) {

    $sql = "SELECT 
                user_posts.user_post_id AS user_post_id,
                user_posts.user_id AS user_id,
                CONCAT(user_accounts.first_name, ' ',
                    user_accounts.last_name) AS userFullName,
                user_posts.title AS title,
                user_posts.body AS body,
                user_posts.date_added AS date_added
            FROM user_posts
            JOIN user_accounts ON
                user_posts.user_id = user_accounts.user_id
            ";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute()) {
        return $stmt->fetchAll();
    }
}

function getPostByID($pdo, $user_post_id) {

    $sql = "SELECT 
                user_posts.user_post_id AS user_post_id,
                user_posts.user_id AS user_id,
                CONCAT(user_accounts.first_name, ' ',
                    user_accounts.last_name) AS userFullName,
                user_posts.title AS title,
                user_posts.body AS body,
                user_posts.date_added AS date_added
            FROM user_posts
            JOIN user_accounts ON
                user_posts.user_id = user_accounts.user_id
            WHERE user_post_id = ?
            ";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$user_post_id])) {
        return $stmt->fetch();
    }
}

function getAllPostsByUser($pdo, $user_id) {

    $sql = "SELECT 
                user_posts.user_post_id AS user_post_id,
                user_posts.user_id AS user_id,
                CONCAT(user_accounts.first_name, ' ',
                    user_accounts.last_name) AS userFullName,
                user_posts.title AS title,
                user_posts.body AS body,
                user_posts.date_added AS date_added
            FROM user_posts
            JOIN user_accounts ON
                user_posts.user_id = user_accounts.user_id
            WHERE user_posts.user_id = ?";

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$user_id])) {
        return $stmt->fetchAll();
    }
}
function getAllUsers($pdo) {
    $sql = "SELECT * FROM user_accounts";
    $stmt = $pdo->prepare($sql);
    $executeQuery = $stmt->execute();

    if ($executeQuery) {
        return $stmt->fetchAll();
    }
}

