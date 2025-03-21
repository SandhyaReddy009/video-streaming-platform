<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Re-fetch user data to ensure it reflects the latest status (e.g., after payment)
$query = "SELECT username, profile_picture, premium FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$user = $result->fetch_assoc();
$stmt->close();

// Update session with latest premium status (optional, for consistency across pages)
$_SESSION['premium'] = $user['premium'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streaming Service Homepage</title>
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
            scrollbar-width: thin;
            scrollbar-color: #2A3A5A #1A2A44;
        }

        body::-webkit-scrollbar,
        .sidebar::-webkit-scrollbar,
        .main-content::-webkit-scrollbar,
        .featured-section::-webkit-scrollbar {
            width: 8px;
        }

        body::-webkit-scrollbar-track,
        .sidebar::-webkit-scrollbar-track,
        .main-content::-webkit-scrollbar-track,
        .featured-section::-webkit-scrollbar-track {
            background: #1A2A44;
        }

        body::-webkit-scrollbar-thumb,
        .sidebar::-webkit-scrollbar-thumb,
        .main-content::-webkit-scrollbar-thumb,
        .featured-section::-webkit-scrollbar-thumb {
            background: #2A3A5A;
            border-radius: 10px;
        }

        body::-webkit-scrollbar-thumb:hover,
        .sidebar::-webkit-scrollbar-thumb:hover,
        .main-content::-webkit-scrollbar-thumb:hover,
        .featured-section::-webkit-scrollbar-thumb:hover {
            background: #3A4A6A;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .header {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #be4883;
        }

        .user-info .default-picture {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #2A3A5A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #fff;
            border: 2px solid #be4883;
        }

        .user-info span {
            font-size: 16px;
            font-weight: 600;
            color: #be4883;
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

        .profile-icon {
            position: relative;
        }

        .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: #2A3A5A;
            border-radius: 0 0 8px 8px;
            padding: 10px 0;
            z-index: 101;
        }

        .sidebar:hover .profile-icon .dropdown {
            display: block;
        }

        .dropdown-item {
            padding: 10px 20px;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: #be4883;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            margin-left: 70px;
            overflow-y: auto;
        }

        .welcome-heading {
            font-size: 36px;
            margin-bottom: 20px;
            color: #be4883;
            /* Font color for the h1 */
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            text-align: center;
        }

        .search-box {
            display: none;
            margin-bottom: 20px;
        }

        .search-box.active {
            display: block;
        }

        .search-box input {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #2A3A5A;
            color: #fff;
            font-size: 16px;
        }

        .search-box input::placeholder {
            color: #9FA8DA;
        }

        .section {
            margin-bottom: 20px;
        }

        .section h2 {
            font-size: 22px;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #be4883;
        }

        .featured-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            border-radius: 10px;
            padding-bottom: 10px;
        }

        .movie-card {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .movie-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .movie-card:hover {
            transform: scale(1.05);
        }

        .ratings {
            position: absolute;
            bottom: 10px;
            left: 10px;
            display: flex;
            gap: 10px;
            font-size: 16px;
            color: #fff;
            background: rgba(26, 42, 68, 0.7);
            padding: 5px 10px;
            border-radius: 5px;
        }

        .movie-popup {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 42, 68, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-sizing: border-box;
            z-index: 10;
        }

        .movie-card:hover .movie-popup {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .movie-popup h2 {
            font-size: 24px;
            margin-bottom: 10px;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
        }

        .movie-popup .watch-btn {
            padding: 10px 20px;
            background-color: #be4883;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .movie-popup .watch-btn:hover {
            background-color: #d65c96;
        }

        .movie-popup .watch-later-btn {
            padding: 10px 20px;
            background-color: #2A3A5A;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        .movie-popup .watch-later-btn:hover {
            background-color: #3A4A6A;
        }

        .movie-popup p {
            font-size: 14px;
            color: #9FA8DA;
            text-align: center;
            font-family: 'Roboto', sans-serif;
            font-weight: 400;
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
            font-size: 24px;
            cursor: pointer;
            color: #fff;
        }

        .movie-card.hidden {
            display: none;
        }

        .not-found {
            text-align: center;
            font-size: 18px;
            color: #9FA8DA;
            padding: 20px;
        }

        /* Premium Subscription Styles */
        .premium-lock {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 5;
        }

        .premium-lock p {
            font-size: 18px;
            color: #fff;
            background: #be4883;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .premium-lock.hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <?php if ($user && !empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="User Profile">
                <?php else: ?>
                    <div class="default-picture"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </div>

        <div class="sidebar">
            <div class="logo">
                <img src="images/logo.png" alt="Logo" />
            </div>
            <div class="nav-icons">
                <div class="icon home-icon">🏠 <span>Home</span></div>
                <div class="icon search-icon">🔍 <span>Search</span></div>
                <div class="icon movie-icon">🎬 <span>Movies</span></div>
                <div class="icon sports-icon">🏃‍➡️ <span>Sports</span></div>
                <div class="icon education-icon">📚 <span>Education</span></div>
                <div class="icon songs-icon">🎵 <span>Songs</span></div>
                <div class="icon watch-later-icon">⏰ <span>Watch Later</span></div>
                <div class="icon profile-icon">
                    👤 <span>My Space</span>
                    <div class="dropdown">
                        <div class="dropdown-item logout">Logout</div>
                        <?php if (!$user['premium']): ?>
                            <div class="dropdown-item upgrade">Upgrade to Premium</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="search-box" id="searchBox">
                <input type="text" id="searchInput" placeholder="Search for movies, shows, and more..." />
            </div>
            <div>
                <h1 class="welcome-heading">Welcome to Video Streaming Platform</h1>
            </div>
            <!-- Latest Movies -->
            <div class="section">
                <h2>Latest Movies</h2>
                <div class="featured-section">
                    <?php $is_premium = $user['premium']; ?>
                    <div class="movie-card" data-free="true">
                        <img src="images/dragon.jpg" alt="Dragon Movie" />
                        <div class="ratings">
                            <span class="like">👍 85%</span>
                            <span class="dislike">👎 15%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Dragon</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • U/A 13+ • 2h 41m • Telugu</p>
                            <p>After a devastating breakup, troubled student Ragavan abandons his studies and enters the
                                dangerous world of financial fraud.</p>
                        </div>
                    </div>
                    <div class="movie-card">
                        <img src="images/movie2.jpg" alt="Sankranthiki Vasthunnam" />
                        <div class="ratings">
                            <span class="like">👍 90%</span>
                            <span class="dislike">👎 10%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Sankranthiki Vasthunnam</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • U/A 16+ • 2h 15m • Telugu</p>
                            <p>When a renowned tech entrepreneur is kidnapped upon returning to India, a police officer
                                recruits her ex-boyfriend, a former cop, to assist in the rescue mission.</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="movie-card">
                        <img src="images/movie3.jpg" alt="Emoji" />
                        <div class="ratings">
                            <span class="like">👍 78%</span>
                            <span class="dislike">👎 22%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Emoji</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2023 • U/A 13+ • 1h 58m • Telugu</p>
                            <p>Aadhav and Deeksha, a vibrant young couple, are about to be separated as passion pulls
                                them apart. Can love win them back together?</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Latest Sports -->
            <div class="section">
                <h2>Latest Sports</h2>
                <div class="featured-section">
                    <div class="movie-card" data-free="true">
                        <img src="images/cricket.jpg" alt="Cricket" />
                        <div class="ratings">
                            <span class="like">👍 92%</span>
                            <span class="dislike">👎 8%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Cricket 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 45m • English</p>
                            <p>Highlights from an intense cricket match showcasing top players and thrilling moments.
                            </p>
                        </div>
                    </div>
                    <div class="movie-card">
                        <img src="images/archery.jpeg" alt="Archery" />
                        <div class="ratings">
                            <span class="like">👍 88%</span>
                            <span class="dislike">👎 12%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Archery 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 1h • English</p>
                            <p>Watch skilled archers compete in a high-stakes tournament with perfect shots.</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="movie-card">
                        <img src="images/skating.jpg" alt="Skating" />
                        <div class="ratings">
                            <span class="like">👍 95%</span>
                            <span class="dislike">👎 5%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Skating 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 1h 15m • English</p>
                            <p>Experience the grace and speed of top skaters in this 2025 championship recap.</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Latest Education -->
            <div class="section">
                <h2>Latest Education</h2>
                <div class="featured-section">
                    <div class="movie-card" data-free="true">
                        <img src="images/math.jpeg" alt="Math Basics" />
                        <div class="ratings">
                            <span class="like">👍 90%</span>
                            <span class="dislike">👎 10%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Math Basics 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 30m • English</p>
                            <p>Learn the fundamentals of mathematics with engaging examples and clear explanations.</p>
                        </div>
                    </div>
                    <div class="movie-card">
                        <img src="images/science.jpeg" alt="Science Experiments" />
                        <div class="ratings">
                            <span class="like">👍 87%</span>
                            <span class="dislike">👎 13%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Science Experiments 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 45m • English</p>
                            <p>Explore exciting science experiments that bring concepts to life.</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="movie-card">
                        <img src="images/history.jpeg" alt="World History" />
                        <div class="ratings">
                            <span class="like">👍 93%</span>
                            <span class="dislike">👎 7%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>World History 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 1h • English</p>
                            <p>Dive into key events that shaped the world in this comprehensive history lesson.</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Latest Songs -->
            <div class="section">
                <h2>Latest Songs</h2>
                <div class="featured-section">
                    <div class="movie-card" data-free="true">
                        <img src="images/pop.jpg" alt="Pop Hit" />
                        <div class="ratings">
                            <span class="like">👍 89%</span>
                            <span class="dislike">👎 11%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Pop Hit 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 4m • English</p>
                            <p>A catchy pop song topping the charts this year.</p>
                        </div>
                    </div>
                    <div class="movie-card">
                        <img src="images/rock.jpeg" alt="Rock Anthem" />
                        <div class="ratings">
                            <span class="like">👍 91%</span>
                            <span class="dislike">👎 9%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Rock Anthem 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 5m • English</p>
                            <p>An electrifying rock anthem that gets the crowd moving.</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="movie-card">
                        <img src="images/jazz.jpeg" alt="Jazz Vibes" />
                        <div class="ratings">
                            <span class="like">👍 85%</span>
                            <span class="dislike">👎 15%</span>
                        </div>
                        <div class="movie-popup">
                            <h2>Jazz Vibes 2025</h2>
                            <button class="watch-btn">▶ Watch Now</button>
                            <button class="watch-later-btn">⏰ Watch Later</button>
                            <p>2025 • All Ages • 6m • English</p>
                            <p>Smooth jazz vibes to relax and unwind.</p>
                        </div>
                        <?php if (!$is_premium): ?>
                            <div class="premium-lock">
                                <p>Premium Required</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="not-found" id="notFound" style="display: none;">Not Found</div>
        </div>

        <div class="movie-video" id="movieVideo">
            <span class="close-btn" id="closeBtn">✖</span>
            <video id="videoPlayer" controls>
                <source src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>

    <script>
        <?php if (isset($_SESSION['success_message'])): ?>
            alert("<?php echo htmlspecialchars($_SESSION['success_message']); ?>");
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        const isPremium = <?php echo json_encode($user['premium']); ?>;

        document.querySelectorAll('.icon').forEach(icon => {
            icon.addEventListener('click', (e) => {
                if (icon.classList.contains('search-icon')) {
                    const searchBox = document.getElementById('searchBox');
                    searchBox.classList.toggle('active');
                } else if (icon.classList.contains('watch-later-icon')) {
                    window.location.href = 'watchlater.php';
                } else if (icon.classList.contains('movie-icon')) {
                    window.location.href = 'movies.php';
                } else if (icon.classList.contains('sports-icon')) {
                    window.location.href = 'sports.php';
                } else if (icon.classList.contains('education-icon')) {
                    window.location.href = 'education.php';
                } else if (icon.classList.contains('songs-icon')) {
                    window.location.href = 'songs.php';
                } else if (icon.classList.contains('home-icon')) {
                    window.location.href = 'index.php';
                } else if (icon.classList.contains('profile-icon')) {
                    window.location.href = 'profile_edit.php';
                }
            });
        });

        document.querySelector('.logout').addEventListener('click', () => {
            window.location.href = 'logout.php';
        });

        <?php if (!$user['premium']): ?>
            document.querySelector('.upgrade').addEventListener('click', () => {
                window.location.href = 'payment.php';
            });
        <?php endif; ?>

        const watchButtons = document.querySelectorAll('.watch-btn');
        const videoPlayer = document.getElementById('videoPlayer');
        const movieVideo = document.getElementById('movieVideo');
        const closeBtn = document.getElementById('closeBtn');

        watchButtons.forEach(button => {
            button.addEventListener('click', () => {
                const movieCard = button.closest('.movie-card');
                const isFree = movieCard.hasAttribute('data-free');
                if (!isPremium && !isFree) {
                    alert('Please upgrade to Premium to watch this content.');
                    return;
                }

                const movieTitle = movieCard.querySelector('h2').textContent;
                let videoSrc;
                if (movieTitle === 'Dragon') videoSrc = 'videos/dragon.mp4';
                else if (movieTitle === 'Sankranthiki Vasthunnam') videoSrc = 'videos/movie2.mp4';
                else if (movieTitle === 'Emoji') videoSrc = 'videos/movie3.mp4';
                else if (movieTitle === 'Cricket 2025') videoSrc = 'videos/cricket.mp4';
                else if (movieTitle === 'Archery 2025') videoSrc = 'videos/archery.mp4';
                else if (movieTitle === 'Skating 2025') videoSrc = 'videos/skating.mp4';
                else if (movieTitle === 'Math Basics 2025') videoSrc = 'videos/math.mp4';
                else if (movieTitle === 'Science Experiments 2025') videoSrc = 'videos/science.mp4';
                else if (movieTitle === 'World History 2025') videoSrc = 'videos/history.mp4';
                else if (movieTitle === 'Pop Hit 2025') videoSrc = 'videos/pop.mp4';
                else if (movieTitle === 'Rock Anthem 2025') videoSrc = 'videos/rock.mp4';
                else if (movieTitle === 'Jazz Vibes 2025') videoSrc = 'videos/jazz.mp4';

                videoPlayer.src = videoSrc;
                movieVideo.classList.add('active');
                if (videoPlayer.requestFullscreen) videoPlayer.requestFullscreen();
                else if (videoPlayer.mozRequestFullScreen) videoPlayer.mozRequestFullScreen();
                else if (videoPlayer.webkitRequestFullscreen) videoPlayer.webkitRequestFullscreen();
                videoPlayer.play();
            });
        });

        closeBtn.addEventListener('click', () => {
            movieVideo.classList.remove('active');
            videoPlayer.pause();
            videoPlayer.src = '';
            if (document.exitFullscreen) document.exitFullscreen();
            else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
        });

        document.querySelectorAll('.watch-later-btn').forEach(button => {
            button.addEventListener('click', () => {
                const movieCard = button.closest('.movie-card');
                const movieTitle = movieCard.querySelector('h2').textContent;
                const movieImage = movieCard.querySelector('img').src;

                let videoSrc;
                if (movieTitle === 'Dragon') videoSrc = 'videos/dragon.mp4';
                else if (movieTitle === 'Sankranthiki Vasthunnam') videoSrc = 'videos/movie2.mp4';
                else if (movieTitle === 'Emoji') videoSrc = 'videos/movie3.mp4';
                else if (movieTitle === 'Cricket 2025') videoSrc = 'videos/cricket.mp4';
                else if (movieTitle === 'Archery 2025') videoSrc = 'videos/archery.mp4';
                else if (movieTitle === 'Skating 2025') videoSrc = 'videos/skating.mp4';
                else if (movieTitle === 'Math Basics 2025') videoSrc = 'videos/math.mp4';
                else if (movieTitle === 'Science Experiments 2025') videoSrc = 'videos/science.mp4';
                else if (movieTitle === 'World History 2025') videoSrc = 'videos/history.mp4';
                else if (movieTitle === 'Pop Hit 2025') videoSrc = 'videos/pop.mp4';
                else if (movieTitle === 'Rock Anthem 2025') videoSrc = 'videos/rock.mp4';
                else if (movieTitle === 'Jazz Vibes 2025') videoSrc = 'videos/jazz.mp4';

                const formData = new FormData();
                formData.append('title', movieTitle);
                formData.append('image', movieImage);
                formData.append('video', videoSrc);

                fetch('add_to_watch_later.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('Added to Watch Later!');
                            window.location.href = 'watchlater.php';
                        } else {
                            alert(data.message || 'Error adding to Watch Later.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error adding to Watch Later.');
                    });
            });
        });

        const searchInput = document.getElementById('searchInput');
        const sections = document.querySelectorAll('.section');
        const movieCards = document.querySelectorAll('.movie-card');
        const notFound = document.getElementById('notFound');

        searchInput.addEventListener('input', () => {
            const searchText = searchInput.value.trim().toLowerCase();
            let foundMatch = false;

            sections.forEach(section => {
                const cards = section.querySelectorAll('.movie-card');
                let sectionHasVisibleCards = false;

                cards.forEach(card => {
                    const title = card.querySelector('.movie-popup h2').textContent.toLowerCase();
                    if (searchText === '' || title.includes(searchText)) {
                        card.classList.remove('hidden');
                        sectionHasVisibleCards = true;
                        foundMatch = true;
                    } else {
                        card.classList.add('hidden');
                    }
                });

                section.style.display = sectionHasVisibleCards ? 'block' : 'none';
            });

            notFound.style.display = foundMatch ? 'none' : 'block';
        });
    </script>
</body>

</html>

<?php
mysqli_close($conn);
?>