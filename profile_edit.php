<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$sql = "SELECT username, email, profile_picture, premium, subscription_expiry FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $profile_picture = $user['profile_picture']; // Keep existing picture by default
    $upgrade_to_premium = isset($_POST['upgrade_to_premium']) && $_POST['upgrade_to_premium'] == '1';

    // Handle file upload for profile picture
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $profile_picture = $upload_dir . basename($_FILES['profile_picture']['name']);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    }

    if ($upgrade_to_premium && !$user['premium']) {
        // Store pending profile changes in session and redirect to payment page
        $_SESSION['pending_profile'] = [
            'username' => $username,
            'email' => $email,
            'profile_picture' => $profile_picture,
            'user_id' => $user_id // Add user_id to session
        ];
        error_log("Pending profile set: " . print_r($_SESSION['pending_profile'], true)); // Debug log
        header("Location: payment.php");
        exit();
    } else {
        // Update user data without changing premium status
        $sql = "UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("sssi", $username, $email, $profile_picture, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Profile updated successfully!";
                $_SESSION['user'] = $username; // Update session username
                header("Location: index.php");
                exit();
            } else {
                $error = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
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
            min-height: 100vh;
        }

        .form-container {
            background: #2A3A5A;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 500px;
        }

        h2 {
            margin-bottom: 20px;
            color: #be4883;
            text-align: center;
        }

        .profile-preview {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-preview img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #be4883;
        }

        .default-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #2A3A5A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            border: 2px solid #be4883;
            margin: 0 auto;
        }

        .subscription-status {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .subscription-status span {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
        }

        .normal {
            background: #9FA8DA;
            color: #1A2A44;
        }

        .premium {
            background: #FFD700;
            color: #1A2A44;
        }

        .expired {
            background: #ff6b6b;
            color: #fff;
        }

        input[type="text"],
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background: #1A2A44;
            color: #fff;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.5);
            cursor: pointer;
        }

        label {
            font-size: 16px;
            color: #fff;
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
            display: block;
            margin: 20px auto 0;
        }

        .btn:hover {
            background-color: #d65c96;
        }

        .error {
            color: #ff6b6b;
            margin-top: 10px;
            text-align: center;
        }

        a {
            color: #be4883;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 10px;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Edit Profile</h2>
        <div class="profile-preview">
            <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
            <?php else: ?>
                <div class="default-picture"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
            <?php endif; ?>
        </div>
        <div class="subscription-status">
            <span
                class="<?php echo $user['premium'] ? ($user['subscription_expiry'] && strtotime($user['subscription_expiry']) < time() ? 'expired' : 'premium') : 'normal'; ?>">
                <?php
                if ($user['premium']) {
                    if ($user['subscription_expiry'] && strtotime($user['subscription_expiry']) < time()) {
                        echo "Premium (Expired)";
                    } else {
                        echo "Premium" . ($user['subscription_expiry'] ? " (Expires: " . date('Y-m-d', strtotime($user['subscription_expiry'])) . ")" : "");
                    }
                } else {
                    echo "Normal";
                }
                ?>
            </span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                placeholder="Username" required>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Email"
                required>
            <input type="file" name="profile_picture" accept="image/*">
            <?php if (!$user['premium']): ?>
                <div class="checkbox-container">
                    <input type="checkbox" name="upgrade_to_premium" id="upgrade_to_premium" value="1">
                    <label for="upgrade_to_premium">Upgrade to Premium</label>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn">Save Changes</button>
        </form>
        <?php if (isset($error)) {
            echo "<p class='error'>$error</p>";
        } ?>
        <a href="index.php">Back to Home</a>
    </div>
</body>

</html>
<?php $conn->close(); ?>