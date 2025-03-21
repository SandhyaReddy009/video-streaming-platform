<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $conn->real_escape_string($_POST['email']);
    $subscription_type = $_POST['subscription_type'];

    // Handle file upload for profile picture
    $profile_picture = 'uploads/default.jpg'; // Default image
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $profile_picture = $upload_dir . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    }

    if ($subscription_type === 'normal') {
        // Insert normal user directly
        $sql = "INSERT INTO users (username, password, profile_picture, email, premium) VALUES ('$username', '$password', '$profile_picture', '$email', 0)";
        if ($conn->query($sql) === TRUE) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif ($subscription_type === 'premium') {
        // Temporarily store user data in session and redirect to payment
        $_SESSION['pending_user'] = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'profile_picture' => $profile_picture
        ];
        header("Location: payment.php");
        exit();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #1A2A44;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background: #2A3A5A;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        h2 {
            margin-bottom: 20px;
            color: #be4883;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background: #1A2A44;
            color: #fff;
        }

        select {
            appearance: none;
            cursor: pointer;
        }

        .btn {
            padding: 10px 20px;
            background-color: #be4883;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #d65c96;
        }

        .error {
            color: #ff6b6b;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Register</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="file" name="profile_picture" accept="image/*">
            <select name="subscription_type" required>
                <option value="" disabled selected>Select Subscription</option>
                <option value="normal">Normal (Free)</option>
                <option value="premium">Premium</option>
            </select>
            <button type="submit" class="btn">Register</button>
        </form>
        <?php if (isset($error)) {
            echo "<p class='error'>$error</p>";
        } ?>
        <p>Already have an account? <a href="login.php" style="color: #be4883;">Login</a></p>
    </div>
</body>

</html>