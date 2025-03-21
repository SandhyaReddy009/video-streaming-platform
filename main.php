<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Poppins:wght@400;600&family=Roboto:wght@400;700&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #1A2A44;
            color: #fff;
            padding: 20px;
            scrollbar-width: thin;
            scrollbar-color: #2A3A5A #1A2A44;
        }

        body::-webkit-scrollbar {
            width: 8px;
        }

        body::-webkit-scrollbar-track {
            background: #1A2A44;
        }

        body::-webkit-scrollbar-thumb {
            background: #2A3A5A;
            border-radius: 10px;
        }

        body::-webkit-scrollbar-thumb:hover {
            background: #3A4A6A;
        }

        .container {
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .logo {
            margin-bottom: 40px;
            display: flex;
            justify-content: center;
        }

        .logo img {
            width: 150px;
            border: 5px solid #ff69b4;
            /* Pink border */
            border-radius: 50%;
            /* Makes it circular */
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.1);
        }

        .button-container {
            display: flex;
            gap: 20px;
        }

        .btn {
            padding: 15px 30px;
            background-color: #be4883;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #d65c96;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="images/logo.png" alt="Logo" />
        </div>
        <div class="button-container">
            <button class="btn" onclick="window.location.href='adminlogin.php'">Admin</button>
            <button class="btn" onclick="window.location.href='register.php'">User</button>
        </div>
    </div>
</body>

</html>