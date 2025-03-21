<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if coming from registration or profile edit
if (!isset($_SESSION['pending_user']) && !isset($_SESSION['pending_profile'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';

    // Validate payment method
    if (!in_array($payment_method, ['debit_card', 'credit_card', 'upi'])) {
        $error = "Please select a valid payment method.";
    } else {
        if (isset($_SESSION['pending_user'])) {
            // Handle new user registration with premium
            $user_data = $_SESSION['pending_user'];
            $username = $conn->real_escape_string($user_data['username']);
            $password = $user_data['password']; // Already hashed
            $email = $conn->real_escape_string($user_data['email']);
            $profile_picture = $user_data['profile_picture'];

            $sql = "INSERT INTO users (username, password, profile_picture, email, premium, subscription_expiry) 
                    VALUES (?, ?, ?, ?, 1, DATE_ADD(NOW(), INTERVAL 30 DAY))";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("ssss", $username, $password, $profile_picture, $email);
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    unset($_SESSION['pending_user']);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user'] = $username;
                    $_SESSION['premium'] = 1;
                    $_SESSION['success_message'] = "Payment successful via $payment_method! Welcome to Premium.";
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Insert failed: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif (isset($_SESSION['pending_profile'])) {
            // Handle existing user upgrading to premium
            $user_data = $_SESSION['pending_profile'];
            $username = $conn->real_escape_string($user_data['username']);
            $email = $conn->real_escape_string($user_data['email']);
            $profile_picture = $user_data['profile_picture'];
            $user_id = $user_data['user_id'];

            $sql = "UPDATE users SET username = ?, email = ?, profile_picture = ?, premium = 1, 
                    subscription_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("sssi", $username, $email, $profile_picture, $user_id);
                if ($stmt->execute()) {
                    // Check if any rows were affected
                    if ($stmt->affected_rows > 0) {
                        unset($_SESSION['pending_profile']);
                        $_SESSION['user'] = $username;
                        $_SESSION['premium'] = 1;
                        $_SESSION['success_message'] = "Payment successful via $payment_method! You are now a Premium user.";
                        header("Location: index.php");
                        exit();
                    } else {
                        $error = "No rows updated. User ID: $user_id might not exist or data unchanged.";
                    }
                } else {
                    $error = "Update failed: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
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

        .payment-container {
            background: #2A3A5A;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            text-align: center;
            width: 100%;
            max-width: 500px;
        }

        h2 {
            margin-bottom: 20px;
            color: #be4883;
        }

        p {
            margin-bottom: 20px;
            color: #9FA8DA;
        }

        .payment-options {
            margin-bottom: 20px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #1A2A44;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .payment-option:hover {
            background: #3A4A6A;
        }

        .payment-option input[type="radio"] {
            margin-right: 10px;
            transform: scale(1.5);
        }

        .payment-option label {
            flex: 1;
            text-align: left;
            font-size: 16px;
        }

        .payment-option img {
            width: 40px;
            height: 40px;
            object-fit: contain;
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
            width: 100%;
        }

        .btn:hover {
            background-color: #d65c96;
        }

        .btn:disabled {
            background-color: #9FA8DA;
            cursor: not-allowed;
        }

        .error {
            color: #ff6b6b;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <h2>Premium Subscription</h2>
        <p>Unlock all content for just $9.99/month!</p>
        <form method="POST" id="paymentForm">
            <div class="payment-options">
                <div class="payment-option">
                    <input type="radio" id="debit_card" name="payment_method" value="debit_card" required>
                    <label for="debit_card">Debit Card</label>
                    <img src="https://cdn-icons-png.flaticon.com/512/217/217433.png" alt="Debit Card Icon">
                </div>
                <div class="payment-option">
                    <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                    <label for="credit_card">Credit Card</label>
                    <img src="https://cdn-icons-png.flaticon.com/512/217/217420.png" alt="Credit Card Icon">
                </div>
                <div class="payment-option">
                    <input type="radio" id="upi" name="payment_method" value="upi">
                    <label for="upi">UPI</label>
                    <img src="https://cdn-icons-png.flaticon.com/512/5968/5968515.png" alt="UPI Icon">
                </div>
            </div>
            <button type="submit" class="btn" id="payBtn" disabled>Pay Now</button>
        </form>
        <?php if (isset($error)) {
            echo "<p class='error'>$error</p>";
        } ?>
    </div>

    <script>
        const paymentForm = document.getElementById('paymentForm');
        const payBtn = document.getElementById('payBtn');
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');

        paymentMethods.forEach(method => {
            method.addEventListener('change', () => {
                payBtn.disabled = !Array.from(paymentMethods).some(m => m.checked);
            });
        });

        paymentForm.addEventListener('submit', (e) => {
            if (!payBtn.disabled) {
                payBtn.textContent = 'Processing...';
                payBtn.disabled = true;
            }
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>