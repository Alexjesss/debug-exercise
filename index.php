<?php
declare(strict_types=1);
session_start();
$sports = ['Football', 'Tennis', 'Ping pong', 'Volley ball', 'Rugby', 'Horse riding', 'Swimming', 'Judo', 'Karate'];
function openConnection(): PDO
{
    // No bugs in this function, just use the right credentials.
    $dbhost = "localhost:3306";
    $dbuser = "root";
    $dbpass = "";
    $db = "debug";

    $driverOptions = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO('mysql:host=' . $dbhost . ';dbname=' . $db, $dbuser, $dbpass, $driverOptions);
}

$pdo = openConnection();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $_SESSION['firstname'] = $_POST['firstname'];
    $_SESSION['lastname'] = $_POST['lastname'];
    $_SESSION['id'] = $_POST['id'];
    $_SESSION['sports'] = $_POST['sports'];
    $_SESSION['delete'] = $_POST['delete'];
    header('Location:index.php');
        exit;
}


if(!empty($_SESSION['firstname']) && !empty($_SESSION['lastname'])) {
    //@todo possible bug below?
    if(empty($_SESSION['id'])) {
        $handle = $pdo->prepare('INSERT INTO user (firstname, lastname, year) VALUES (:firstname, :lastname, :year)');
        $message = 'Your record has been added';
    } else {
        //@todo why does this not work?
        $handle = $pdo->prepare('UPDATE user set firstname = :firstname, lastname = :lastname, year = :year WHERE user.id = :id');
        $handle->bindValue(':id', $_SESSION['id']);
        $message = 'Your record has been updated';
    }

    $handle->bindValue(':firstname', $_SESSION['firstname']);
    $handle->bindValue(':lastname', $_SESSION['lastname']);
    $handle->bindValue(':year', date('Y'));
    $handle->execute();

    if(!empty($_SESSION['id'])) {
        $handle = $pdo->prepare('DELETE FROM sport WHERE user_id = :id');
        $handle->bindValue(':id', $_SESSION['id']);
        $handle->execute();
        $userId = $_SESSION['id'];
    } else {
        $userId = $pdo->lastInsertId();
    }

    foreach($_SESSION['sports'] AS $sport) {
        $handle = $pdo->prepare('INSERT INTO sport (user_id, sport) VALUES (:userId, :sport)');
        $handle->bindValue(':userId', $userId);
        $handle->bindValue(':sport', $sport);
        $handle->execute();
    }
}
elseif(isset($_SESSION['delete'])) {
    $handle = $pdo->prepare('DELETE FROM user where user.id = :id ');
    $handle->bindValue(':id', $_SESSION['id']);
    $handle->execute();
    $message = 'Your record has been deleted';
}


$handle = $pdo->prepare('SELECT user.id, concat_ws(" ",firstname, lastname) AS name, group_concat(sport) AS sport FROM user LEFT JOIN sport ON user.id = sport.user_id where year = :year group by user.id');
$handle->bindValue(':year', date('Y'));
$handle->execute();
$users = $handle->fetchAll();

$saveLabel = 'Save record';
if(!empty($_GET['id'])) {
    $saveLabel = 'Update record';
    $handle = $pdo->prepare('SELECT id, firstname, lastname FROM user where id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    $selectedUser = $handle->fetch();

    //This segment checks all the current sports for an existing user when you update him.
    $selectedUser['sports'] = [];
    $handle = $pdo->prepare('SELECT sport FROM sport where user_id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    foreach($handle->fetchAll() AS $sport) {
        $selectedUser['sports'][] = implode($sport);
    }
}

if(empty($selectedUser['id'])) {
    $selectedUser = [
        'id' => '',
        'firstname' => '',
        'lastname' => '',
        'sports' => []
    ];
}
session_unset();

require 'view.php';
// All bugs where written with Love for the learning Process. No actual bugs where harmed or eaten during the creation of this code.