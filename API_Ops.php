<?php
header('Content-Type: application/json');

// ==========================================
// OMDb API Configuration
// ==========================================
define('OMDB_API_KEY', 'c6446c3');
define('OMDB_BASE_URL', 'http://www.omdbapi.com/');

// ==========================================
// Handle Actions
// ==========================================
$action = $_POST['action'] ?? '';

switch($action) {
    case 'search_movie':
        searchMovie();
        break;

    case 'get_movie_details':
        getMovieDetails();
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

// ==========================================
// Search Movies
// ==========================================
function searchMovie() {
    $query = trim($_POST['query'] ?? '');

    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Enter a movie name']);
        return;
    }

    $url = OMDB_BASE_URL . "?apikey=" . OMDB_API_KEY . "&s=" . urlencode($query);

    $response = makeRequest($url);

    if (!$response['success']) {
        echo json_encode(['success' => false, 'error' => $response['error']]);
        return;
    }

    $data = json_decode($response['data'], true);

    if ($data['Response'] === 'False') {
        echo json_encode(['success' => false, 'error' => $data['Error']]);
        return;
    }

    $movies = [];

    foreach ($data['Search'] as $movie) {
        $movies[] = [
            'id' => $movie['imdbID'],
            'title' => $movie['Title'],
            'release_date' => $movie['Year'],
            'poster_path' => $movie['Poster'] !== 'N/A'
                ? $movie['Poster']
                : 'https://via.placeholder.com/500x750?text=No+Poster',
            'vote_average' => 'N/A' // OMDb doesn't provide rating here
        ];
    }

    echo json_encode([
        'success' => true,
        'movies' => $movies
    ]);
}

// ==========================================
// Movie Details
// ==========================================
function getMovieDetails() {
    $movie_id = $_POST['movie_id'] ?? '';

    if (empty($movie_id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid movie ID']);
        return;
    }

    $url = OMDB_BASE_URL . "?apikey=" . OMDB_API_KEY . "&i=" . $movie_id;

    $response = makeRequest($url);

    if (!$response['success']) {
        echo json_encode(['success' => false, 'error' => $response['error']]);
        return;
    }

    $movie = json_decode($response['data'], true);

    if ($movie['Response'] === 'False') {
        echo json_encode(['success' => false, 'error' => $movie['Error']]);
        return;
    }

    $movieDetails = [
        'id' => $movie['imdbID'],
        'title' => $movie['Title'],
        'release_date' => $movie['Year'],
        'runtime' => $movie['Runtime'],
        'overview' => $movie['Plot'],
        'genres' => explode(', ', $movie['Genre']),
        'poster_path' => $movie['Poster'] !== 'N/A'
            ? $movie['Poster']
            : 'https://via.placeholder.com/500x750?text=No+Poster',
        'vote_average' => $movie['imdbRating'],
        'vote_count' => $movie['imdbVotes']
    ];

    echo json_encode([
        'success' => true,
        'movie' => $movieDetails
    ]);
}

// ==========================================
// CURL Helper
// ==========================================
function makeRequest($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            'success' => false,
            'error' => curl_error($ch)
        ];
    }

    curl_close($ch);

    return [
        'success' => true,
        'data' => $response
    ];
}
?>
