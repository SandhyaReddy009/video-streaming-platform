<?php
header('Content-Type: application/json');

// Mock content (replace with database logic later)
$movies = [
    'dragon' => ['title' => 'Dragon', 'free' => true],
    'sankranthiki vasthunnam' => ['title' => 'Sankranthiki Vasthunnam', 'free' => false],
    'emoji' => ['title' => 'Emoji', 'free' => false],
    'cricket 2025' => ['title' => 'Cricket 2025', 'free' => true]
];

// Get Dialogflow request
$input = json_decode(file_get_contents('php://input'), true);
$intent = $input['queryResult']['intent']['displayName'];
$params = $input['queryResult']['parameters'];

$response = [];
switch ($intent) {
    case 'SearchVideo':
        $query = strtolower($params['videoQuery']);
        if (isset($movies[$query])) {
            $movie = $movies[$query];
            $response['fulfillmentText'] = $movie['free'] ?
                "You can watch '{$movie['title']}'—it’s free!" :
                "'{$movie['title']}' requires a premium subscription.";
        } else {
            $response['fulfillmentText'] = "Sorry, I couldn’t find '$query'.";
        }
        break;
    case 'CheckPremium':
        // Mock premium status (replace with session/db check)
        $response['fulfillmentText'] = "I can’t check your premium status yet—upgrade logic TBD!";
        break;
    case 'RecommendVideo':
        $response['fulfillmentText'] = "Try 'Dragon'—it’s free and has an 85% rating!";
        break;
    case 'Navigate':
        $destination = strtolower($params['destination']);
        $pages = ['movies' => 'movies.php', 'sports' => 'sports.php', 'watch later' => 'watchlater.php'];
        if (isset($pages[$destination])) {
            $response['fulfillmentText'] = "Go to {$pages[$destination]} for '$destination' content!";
        } else {
            $response['fulfillmentText'] = "I don’t know how to navigate to '$destination'.";
        }
        break;
    default:
        $response['fulfillmentText'] = "I’m here to help! Try searching for a movie.";
}

echo json_encode($response);
?>