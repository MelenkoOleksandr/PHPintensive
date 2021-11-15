<?php
include_once "config.php";

function debug($var, $stop = false) {
    echo "<pre>";
    print_r($var);
    echo "</pre>";
    if ($stop) {die;}
}

function get_url($page = '') {
    return HOST . "/$page";
}

function get_page_title($title = '') {
    if (!empty($title)) {
        return SITE_NAME . " - $title";
    } else {
        return SITE_NAME;
    }
}


function db() {
    try {
        return new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS,
        [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    } catch (PDOException $e) {
        //if cannot connect db or error in query
        die($e->getMessage());
    }
}

function db_query($sql, $exec = false) {
    if (empty($sql)) return false;

    if ($exec) return db()->exec($sql);

    return db()->query($sql);
}

function get_posts($user_id = 0, $sort = false) {
    $sorting = 'DESC';
    if ($sort) {
        $sorting = 'ASC';
    }
    if ($user_id > 0)  return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id WHERE posts.user_id = $user_id ORDER BY `posts`.`date` $sorting")->fetchAll();
    
    return db_query("SELECT posts.*, users.name, users.login, users.avatar FROM `posts` JOIN `users` ON users.id = posts.user_id ORDER BY `posts`.`date` $sorting")->fetchAll();
}

function get_user_info($login) {
    return db_query("SELECT * FROM `users` WHERE `login` = '$login';")->fetch();
}

function redirect($link = HOST) {
    header("Location: $link");
    die;
}

function add_user($login, $pass) {
    $login = trim($login);
    $name = ucfirst($login);
    $password = password_hash($pass,PASSWORD_DEFAULT);
    return db_query("INSERT INTO `users` (`id`, `login`, `pass`, `name`) VALUES (NULL, '$login', '$password', '$name');", true);
}

function register_user($auth_data) {
    if (empty($auth_data) || !isset($auth_data['login']) || empty($auth_data['login'])) return false;
   
    $user = get_user_info($auth_data['login']);
    if (!empty($user)) {
        $_SESSION['error'] = 'Пользователь ' . $auth_data['login'] . ' уже существует';
        //redirect 
        redirect(get_url('register.php'));
    }

    if ($auth_data['pass'] !== $auth_data['pass2']) {
        $_SESSION['error'] = 'Пароли не совпадают';
        redirect(get_url('register.php'));
    }

    if (strlen($auth_data['pass']) < 4) {
        $_SESSION['error'] = 'Слишком короткий пароль';
        redirect(get_url('register.php'));
    }

    if (strlen($auth_data['login']) < 4) {
        $_SESSION['error'] = 'Слишком короткий логин';
        redirect(get_url('register.php'));
    }



    if (add_user($auth_data['login'], $auth_data['pass'])) {
        redirect(get_url());
    }
}

function login($auth_data) {
    
    if (empty($auth_data) || !isset($auth_data['login']) || empty($auth_data['login'])) return false;
   
    $user = get_user_info($auth_data['login']);

    if (empty($user)) {
        $_SESSION['error'] = 'Пользователь не найден';
        redirect(get_url());
    }

    if (password_verify($auth_data['pass'], $user['pass'])) {
        $_SESSION['user'] = $user;
        $_SESSION['error'] = '';
        redirect(get_url('user_posts.php?id=' .  $_SESSION['user']['id']));
        
    } else {
        $_SESSION['error'] = 'Пароли неверный';
        redirect(get_url());
        
    }
}

function get_error_message() {
    $error = '';
    if (isset( $_SESSION['error']) && !empty( $_SESSION['error'])) {
        $error = $_SESSION['error'];
        $_SESSION['error'] = '';
    }
    return $error;
}

function logged_in() {
    return isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
}

function add_post($text, $image) {
    $text = trim($text);
    if (mb_strlen($text) > 255) {
        $text = mb_substr($text, 0, 250) . ' ...';
    }

    $user_id = $_SESSION['user']['id'];
    $sql = "INSERT INTO `posts` (`id`, `user_id`, `text`, `image`) VALUES (NULL, $user_id, '$text', '$image');";
    return db_query($sql, true);
}

function delete_post($id) {
    //DZ
    if ($id > 0) {
        $user_id = $_SESSION['user']['id'];
        return db_query("DELETE FROM `posts` WHERE `posts`.`id` = $id AND `posts`.`user_id` = $user_id", true);
    }
}