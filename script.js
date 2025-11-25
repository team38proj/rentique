function showDetail(id) {
    
    if (id === 'product1') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product2') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product3') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product4') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product5') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product6') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product7') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    if (id === 'product8') {
        document.getElementById('productCategory').textContent = 'example'
        document.getElementById('productName').textContent = 'example'
        document.getElementById('productPrice').textContent = '£example'
        document.getElementById('productCondition').textContent = 'example'
        document.getElementById('productSize').textContent = 'example'
        document.getElementById('productMaterial').textContent = 'example'
    }
    
    document.getElementById('productGrid').style.display = 'none'
    document.querySelector('.intro').style.display = 'none'
    document.getElementById('productView').classList.remove('hiddenProducts')
}

function goBack() {
    document.getElementById('productGrid').style.display = 'grid'
    document.querySelector('.intro').style.display = 'block'
    document.getElementById('productView').classList.add('hiddenProducts')
    
    return false
}

let resetButton = document.getElementById('resetBtn')
resetButton.addEventListener('click', function() {
    document.getElementById('searchFilter').value = ''
    document.getElementById('categoryFilter').value = 'all'
})


/*
>>>> Registration page
*/
<?php

/*
Saja (backend) - connect to database
*/
include('cs2team38_db.php');
session_start();
$errors = [];

/*
Saja (backend) - CSRF protection
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // if csrf token is missing or invalid then stop request
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF validation failed");
    }

    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($username)) {
        $errors[] = "Username field is required";
    } 
    //elseif (strlen($username) < 3 || strlen($username) > 50) {
        //$errors[] = "Username must be more than 3 and 50 characters";
    //}

    if (empty($email)) {
        $errors[] = "Email field is required";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } 
    //elseif (strlen($password) < 8) {
        //$errors[] = "Password must be at least 8 characters";
    //}
    
    /*
    Saja (backend) - check for existing user
    */
    $stmt = $pdo->query("SELECT * FROM users WHERE username = '$username' OR email = '$email'");
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        // username,email and input are identical
        if ($existing_user['username'] === $username) {
            $errors[] = "Username already exists";
        }

        if ($existing_user['email'] === $email) {
            $errors[] = "Email already exists";
        }
    }

    /*
    Saja (backend) - insert into database
    */
    //if it is done successfully with no errors then register user
    if (empty($errors)) {
	// hashing passwords for maximum security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
	//insert using prepared statement with placeholders
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $result = $stmt->execute([$username, $email, $hashed_password]);

        if ($result) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
	    //go to homepage
            header('Location: index.php');
            exit;
        } 
        else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$csrf_token = generate_csrf_token();
?>



/*
>>>> Login page
*/
<?php

/*
Saja (backend) - connect to database
*/
include('cs2team38_db.php');
session_start();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF validation failed");
    }

    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['uid'];
            $_SESSION['username'] = $user['username'];
            session_regenerate_id(true);
            header('Location: index.php');
            exit;
        } 
        else {
            $errors[] = "Invalid username or password";
        }
    }
}
$csrf_token = generate_csrf_token();
?>
