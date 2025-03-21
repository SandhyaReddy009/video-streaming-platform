<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php'; // Your database connection file

$user_id = $_SESSION['user_id'];

// Fetch watch later items for the user
$sql = "SELECT id, title, image, video FROM watch_later WHERE user_id = ? ORDER BY added_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$watch_later_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Later</title>
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

        body::-webkit-scrollbar,
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        body::-webkit-scrollbar-track,
        .sidebar::-webkit-scrollbar-track {
            background: #1A2A44;
        }

        body::-webkit-scrollbar-thumb,
        .sidebar::-webkit-scrollbar-thumb {
            background: #2A3A5A;
            border-radius: 10px;
        }

        body::-webkit-scrollbar-thumb:hover,
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #3A4A6A;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 70px;
            background: linear-gradient(135deg, #1A2A44, #2A3A5A);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            transition: width 0.4s ease-in-out;
            position: fixed;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(26, 42, 68, 0.5);
        }

        .sidebar:hover {
            width: 220px;
        }

        .sidebar:hover .icon span {
            opacity: 1;
        }

        .logo {
            margin-bottom: 40px;
            display: flex;
            justify-content: center;
        }

        .logo img {
            width: 100px;
            transition: transform 0.3s ease;
        }

        .sidebar:hover .logo img {
            transform: scale(1.1);
        }

        .nav-icons {
            display: flex;
            flex-direction: column;
            gap: 25px;
            width: 100%;
            padding: 10px 0;
        }

        .icon {
            font-size: 24px;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 12px 20px;
            transition: all 0.3s ease;
            position: relative;
            border-radius: 8px;
        }

        .icon:hover {
            background-color: #be4883;
            color: #fff;
            transform: translateX(5px);
        }

        .icon span {
            margin-left: 15px;
            opacity: 0;
            transition: opacity 0.3s ease;
            white-space: nowrap;
            font-size: 16px;
            position: absolute;
            left: 60px;
            background: transparent;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .content-wrapper {
            flex: 1;
            margin-left: 70px;
            padding: 20px;
            overflow-y: auto;
        }

        .section {
            margin-bottom: 40px;
        }

        h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 24px;
            color: #be4883;
        }

        .watch-later-section {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .movie-card {
            position: relative;
            width: 300px;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            border-radius: 10px;
            overflow: hidden;
        }

        .movie-card:hover {
            transform: scale(1.05);
        }

        .movie-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .movie-popup {
            background-color: #2A3A5A;
            padding: 15px;
            border-radius: 0 0 10px 10px;
            flex: 1;
        }

        .movie-popup h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            margin-bottom: 10px;
            color: #be4883;
        }

        .movie-popup button {
            padding: 10px;
            background-color: #be4883;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
            transition: background-color 0.2s ease;
        }

        .movie-popup button:hover {
            background-color: #d65c96;
        }

        .movie-popup .delete-btn {
            background-color: #ff4444;
        }

        .movie-popup .delete-btn:hover {
            background-color: #ff6666;
        }

        .movie-video {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #1A2A44;
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .movie-video.active {
            display: flex;
        }

        .movie-video video {
            width: 80%;
            height: 80%;
            object-fit: contain;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background-color: #be4883;
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .close-btn:hover {
            background-color: #d65c96;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <img src="images/logo.png" alt="Logo" />
            </div>
            <div class="nav-icons">
                <div class="icon home-icon">🏠 <span>Home</span></div>
                <div class="icon movie-icon">🎬 <span>Movies</span></div>
                <div class="icon sports-icon">🏃‍➡️ <span>Sports</span></div>
                <div class="icon education-icon">📚 <span>Education</span></div>
                <div class="icon songs-icon">🎵 <span>Songs</span></div>
                <div class="icon watch-later-icon">⏰ <span>Watch Later</span></div>
                <div class="icon profile-icon">👤 <span>My Space</span></div>
                <div class="icon logout-icon">🚪 <span>Logout</span></div>
            </div>
        </div>
        <div class="content-wrapper">
            <div class="section">
                <h2>Your Watch Later List</h2>
                <div class="watch-later-section">
                    <?php if (empty($watch_later_items)): ?>
                        <p style="color: #9FA8DA;">Your Watch Later list is empty.</p>
                    <?php else: ?>
                        <?php foreach ($watch_later_items as $item): ?>
                            <div class="movie-card" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['title']); ?>" />
                                <div class="movie-popup">
                                    <h2><?php echo htmlspecialchars($item['title']); ?></h2>
                                    <button class="watch-btn">▶ Watch Now</button>
                                    <button class="delete-btn">🗑️ Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="movie-video" id="movieVideo">
        <span class="close-btn" id="closeBtn">✖</span>
        <video id="videoPlayer" controls>
            <source src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <script>
        document.querySelectorAll('.icon').forEach(icon => {
            icon.addEventListener('click', () => {
                if (icon.classList.contains('home-icon')) window.location.href = 'index.php';
                else if (icon.classList.contains('movie-icon')) window.location.href = 'movies.php';
                else if (icon.classList.contains('sports-icon')) window.location.href = 'sports.php';
                else if (icon.classList.contains('education-icon')) window.location.href = 'education.php';
                else if (icon.classList.contains('songs-icon')) window.location.href = 'songs.php';
                else if (icon.classList.contains('watch-later-icon')) window.location.href = 'watchlater.php';
                else if (icon.classList.contains('profile-icon')) window.location.href = 'profile_edit.php';
                else if (icon.classList.contains('logout-icon')) window.location.href = 'logout.php';
            });
        });

        const watchButtons = document.querySelectorAll('.watch-btn');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const videoPlayer = document.getElementById('videoPlayer');
        const movieVideo = document.getElementById('movieVideo');
        const closeBtn = document.getElementById('closeBtn');

        watchButtons.forEach(button => {
            button.addEventListener('click', () => {
                const movieCard = button.closest('.movie-card');
                const movieTitle = movieCard.querySelector('h2').textContent;
                let videoSrc;

                // Map titles to video sources
                if (movieTitle === 'Pop Hit 2025') videoSrc = 'videos/pop.mp4';
                else if (movieTitle === 'Rock Anthem 2025') videoSrc = 'videos/rock.mp4';
                else if (movieTitle === 'Jazz Vibes 2025') videoSrc = 'videos/jazz.mp4';
                else if (movieTitle === 'Math Basics 2025') videoSrc = 'videos/math.mp4';
                else if (movieTitle === 'Science Experiments 2025') videoSrc = 'videos/science.mp4';
                else if (movieTitle === 'World History 2025') videoSrc = 'videos/history.mp4';
                else if (movieTitle === 'Cricket 2025') videoSrc = 'videos/cricket.mp4';
                else if (movieTitle === 'Archery 2025') videoSrc = 'videos/archery.mp4';
                else if (movieTitle === 'Skating 2025') videoSrc = 'videos/skating.mp4';
                else if (movieTitle === 'Dragon') videoSrc = 'videos/dragon.mp4';
                else if (movieTitle === 'Sankranthiki Vasthunnam') videoSrc = 'videos/movie2.mp4';
                else if (movieTitle === 'Emoji') videoSrc = 'videos/movie3.mp4';
                else if (movieTitle === 'Trailer 1 2025') videoSrc = 'videos/trailer1.mp4';
                else if (movieTitle === 'Trailer 2 2025') videoSrc = 'videos/trailer2.mp4';
                else if (movieTitle === 'Trailer 3 2025') videoSrc = 'videos/trailer3.mp4';
                else {
                    console.error('No video source found for title:', movieTitle);
                    videoSrc = '';
                }

                videoPlayer.src = videoSrc;
                movieVideo.classList.add('active');
                videoPlayer.requestFullscreen?.() || videoPlayer.mozRequestFullScreen?.() || videoPlayer.webkitRequestFullscreen?.();
                videoPlayer.play();
            });
        });

        closeBtn.addEventListener('click', () => {
            movieVideo.classList.remove('active');
            videoPlayer.pause();
            videoPlayer.src = '';
            document.exitFullscreen?.() || document.mozCancelFullScreen?.() || document.webkitExitFullscreen?.();
        });

        deleteButtons.forEach(button => {
            button.addEventListener('click', () => {
                const movieCard = button.closest('.movie-card');
                const watchLaterId = movieCard.dataset.id;

                fetch('delete_watch_later.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: watchLaterId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            movieCard.remove();
                            alert('Removed from Watch Later!');
                            if (!document.querySelector('.movie-card')) {
                                document.querySelector('.watch-later-section').innerHTML = '<p style="color: #9FA8DA;">Your Watch Later list is empty.</p>';
                            }
                        } else {
                            alert(data.message || 'Error removing from Watch Later');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error removing from Watch Later');
                    });
            });
        });
    </script>
</body>

</html>