<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch all users with their subscription details
$sql_users = "SELECT id, username, email, profile_picture, premium, subscription_expiry FROM users";
$result_users = $conn->query($sql_users);

// Fetch watch later entries grouped by user
$sql_watch_later = "
    SELECT u.id AS user_id, u.username, w.title, w.image, w.video, w.added_at 
    FROM watch_later w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY u.id, w.added_at DESC";
$result_watch_later = $conn->query($sql_watch_later);

// Group watch later entries by user
$watch_later_by_user = [];
while ($watch = $result_watch_later->fetch_assoc()) {
    $watch_later_by_user[$watch['user_id']][] = [
        'title' => $watch['title'],
        'image' => $watch['image'],
        'video' => $watch['video'],
        'added_at' => $watch['added_at']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1A2A44, #2A3A5A);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            padding: 40px;
            min-height: 100vh;
            margin: 0;
        }

        .admin-container {
            max-width: 1300px;
            margin: 0 auto;
            background: rgba(42, 58, 90, 0.9);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #be4883;
            text-align: center;
            font-size: 36px;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .toggle-btn {
            padding: 12px 25px;
            background: #be4883;
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(190, 72, 131, 0.3);
        }

        .toggle-btn:hover {
            background: #d65c96;
            transform: translateY(-2px);
        }

        .toggle-btn.active {
            background: #d65c96;
            box-shadow: 0 6px 15px rgba(190, 72, 131, 0.5);
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        h2 {
            color: #be4883;
            margin: 25px 0 15px;
            text-align: center;
            font-size: 24px;
            text-transform: uppercase;
        }

        .data-container {
            background: #1A2A44;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .user-block {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-block:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .user-header {
            display: flex;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .data-row {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            transition: background 0.3s ease;
        }

        .data-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .data-item {
            flex: 1;
            padding: 10px;
            font-size: 16px;
        }

        .movie-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .oval-image {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #be4883;
        }

        .profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #be4883;
            margin-right: 15px;
        }

        .default-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #2A3A5A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            border: 2px solid #be4883;
            margin-right: 15px;
        }

        .video-link {
            color: #be4883;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .video-link:hover {
            color: #d65c96;
        }

        .logout-btn {
            display: block;
            padding: 12px 30px;
            background: #be4883;
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin: 30px auto 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(190, 72, 131, 0.3);
        }

        .logout-btn:hover {
            background: #d65c96;
            transform: translateY(-2px);
        }

        .empty-message {
            text-align: center;
            font-size: 18px;
            color: #9FA8DA;
            padding: 20px;
        }

        .video-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .video-popup.active {
            display: flex;
        }

        .video-popup video {
            max-width: 80%;
            max-height: 80%;
            border-radius: 10px;
        }

        .close-video {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: #be4883;
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .close-video:hover {
            background: #d65c96;
        }

        .subscription-status {
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 15px;
            margin-left: 10px;
        }

        .normal {
            background: #9FA8DA;
            color: #1A2A44;
        }

        .premium {
            background: #be4883;
            color: #1A2A44;
        }

        .expired {
            background: #ff6b6b;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <h1>Admin Dashboard</h1>

        <div class="button-container">
            <button class="toggle-btn active" data-section="users">Users</button>
            <button class="toggle-btn" data-section="watch-later">Watch Later</button>
        </div>

        <!-- User Information Section -->
        <div class="section active" id="users-section">
            <h2>Users</h2>
            <div class="data-container">
                <?php if ($result_users->num_rows > 0): ?>
                    <?php while ($user = $result_users->fetch_assoc()): ?>
                        <div class="user-block">
                            <div class="user-header">
                                <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                    <img class="profile-pic" src="<?php echo htmlspecialchars($user['profile_picture']); ?>"
                                        alt="Profile Picture">
                                <?php else: ?>
                                    <div class="default-pic"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
                                <?php endif; ?>
                                <div class="data-item">
                                    <?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['id']; ?>)
                                    <span
                                        class="subscription-status <?php echo $user['premium'] ? ($user['subscription_expiry'] && strtotime($user['subscription_expiry']) < time() ? 'expired' : 'premium') : 'normal'; ?>">
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
                                <div class="data-item"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php $result_users->data_seek(0); ?>
                <?php else: ?>
                    <div class="empty-message">No users found.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Watch Later Information Section -->
        <div class="section" id="watch-later-section">
            <h2>Watch Later</h2>
            <div class="data-container">
                <?php if (!empty($watch_later_by_user)): ?>
                    <?php foreach ($watch_later_by_user as $user_id => $entries): ?>
                        <?php
                        $user_query = "SELECT username FROM users WHERE id = ?";
                        $stmt = $conn->prepare($user_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $user_result = $stmt->get_result();
                        $user = $user_result->fetch_assoc();
                        $stmt->close();
                        ?>
                        <div class="user-block">
                            <div class="user-header">
                                <div class="data-item"><?php echo htmlspecialchars($user['username']); ?> (ID:
                                    <?php echo $user_id; ?>)</div>
                            </div>
                            <?php foreach ($entries as $watch): ?>
                                <div class="data-row">
                                    <div class="data-item movie-title">
                                        <img class="oval-image" src="<?php echo htmlspecialchars($watch['image']); ?>"
                                            alt="<?php echo htmlspecialchars($watch['title']); ?>">
                                        <?php echo htmlspecialchars($watch['title']); ?>
                                    </div>
                                    <div class="data-item">
                                        <a class="video-link" data-video="<?php echo htmlspecialchars($watch['video']); ?>">
                                            <?php echo htmlspecialchars(basename($watch['video'])); ?>
                                        </a>
                                    </div>
                                    <div class="data-item"><?php echo date('Y-m-d H:i:s', strtotime($watch['added_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">No Watch Later entries found.</div>
                <?php endif; ?>
            </div>
        </div>

        <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>

    <div class="video-popup" id="videoPopup">
        <button class="close-video" id="closeVideo">✖</button>
        <video id="videoPlayer" controls>
            <source src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <script>
        const toggleButtons = document.querySelectorAll('.toggle-btn');
        const sections = document.querySelectorAll('.section');
        const videoLinks = document.querySelectorAll('.video-link');
        const videoPopup = document.getElementById('videoPopup');
        const videoPlayer = document.getElementById('videoPlayer');
        const closeVideo = document.getElementById('closeVideo');

        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                toggleButtons.forEach(btn => btn.classList.remove('active'));
                sections.forEach(section => section.classList.remove('active'));
                button.classList.add('active');
                const sectionId = button.getAttribute('data-section') + '-section';
                document.getElementById(sectionId).classList.add('active');
            });
        });

        videoLinks.forEach(link => {
            link.addEventListener('click', () => {
                const videoSrc = link.getAttribute('data-video');
                videoPlayer.querySelector('source').src = videoSrc;
                videoPlayer.load();
                videoPopup.classList.add('active');
                videoPlayer.play();
            });
        });

        closeVideo.addEventListener('click', () => {
            videoPopup.classList.remove('active');
            videoPlayer.pause();
            videoPlayer.querySelector('source').src = '';
        });

        videoPopup.addEventListener('click', (e) => {
            if (e.target === videoPopup) {
                videoPopup.classList.remove('active');
                videoPlayer.pause();
                videoPlayer.querySelector('source').src = '';
            }
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>