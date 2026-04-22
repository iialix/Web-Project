<?php

$host = "localhost";
$dbname = "assignment";
$username = "root";
$password = "";
$port = 3307;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


class DB_Ops {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // =========================
    // GET ALL MOVIES
    // =========================
    public function getAllMovies() {
        try {
            $sql  = "SELECT id, name, categories, description,
                     TO_BASE64(poster) AS poster
                     FROM movies";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($movies)) {
                return ["success" => true, "data" => [], "message" => "No movies found."];
            }

            return ["success" => true, "data" => $movies];

        } catch (PDOException $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    // =========================
    // SIGNUP
    // =========================
    public function signup($userName, $email, $password, $birthDate) {

        // ---------- Validation ----------
        if (empty($userName) || empty($email) || empty($password) || empty($birthDate)) {
            return ["success" => false, "error" => "All fields are required."];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["success" => false, "error" => "Invalid email format."];
        }

        if (strlen($password) < 6) {
            return ["success" => false, "error" => "Password must be at least 6 characters."];
        }

        try {
            // Check if email already exists
            $checkSql  = "SELECT id FROM users WHERE Email = :email";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                return ["success" => false, "error" => "Email is already registered."];
            }

            // Check if username already exists
            $checkUserSql  = "SELECT id FROM users WHERE UserName = :userName";
            $checkUserStmt = $this->pdo->prepare($checkUserSql);
            $checkUserStmt->bindParam(':userName', $userName, PDO::PARAM_STR);
            $checkUserStmt->execute();

            if ($checkUserStmt->fetch()) {
                return ["success" => false, "error" => "Username is already taken."];
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert user
            $sql  = "INSERT INTO users (UserName, Email, Password, BirthDate)
                     VALUES (:userName, :email, :password, :birthDate)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':userName',  $userName,       PDO::PARAM_STR);
            $stmt->bindParam(':email',     $email,          PDO::PARAM_STR);
            $stmt->bindParam(':password',  $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':birthDate', $birthDate,      PDO::PARAM_STR);

            $stmt->execute();

            return [
                "success" => true,
                "message" => "Account created successfully.",
                "userId"  => $this->pdo->lastInsertId()
            ];

        } catch (PDOException $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    // =========================
    // LOGIN
    // =========================
    public function login($email, $password) {

    if (empty($email) || empty($password)) {
        return ["success" => false, "error" => "Email and password are required."];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ["success" => false, "error" => "Invalid email format."];
    }

    try {
        $sql  = "SELECT Id, UserName, Email, Password, BirthDate
                 FROM users
                 WHERE Email = :email";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            "success" => true,
            "message" => "Login successful.",
            "user"    => [
                "id"        => $user['Id'],
                "userName"  => $user['UserName'],
                "email"     => $user['Email'],
                "birthDate" => $user['BirthDate']
            ]
        ];

    } catch (PDOException $e) {
        return ["success" => false, "error" => $e->getMessage()];
    }
}

    // =========================
    // CREATE: Insert Movie
    // =========================
    public function insertMovie($name, $categories, $description, $posterFile) {

        if (empty($name) || empty($categories) || empty($description)) {
            return "All fields are required.";
        }

        if (!isset($posterFile) || $posterFile['error'] !== UPLOAD_ERR_OK) {
            return "Valid image is required.";
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($posterFile['type'], $allowedTypes)) {
            return "Only JPG, PNG, WEBP images are allowed.";
        }

        $imageData = file_get_contents($posterFile['tmp_name']);

        $sql = "INSERT INTO movies (name, categories, description, poster)
                VALUES (:name, :categories, :description, :poster)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':name',        $name,        PDO::PARAM_STR);
        $stmt->bindParam(':categories',  $categories,  PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':poster',      $imageData,   PDO::PARAM_LOB);

        return $stmt->execute()
            ? "Movie inserted successfully."
            : "Failed to insert movie.";
    }

    // =========================
    // UPDATE: Update Movie
    // =========================
    public function updateMovie($id, $name, $categories, $description, $posterFile = null) {

        if (empty($id) || empty($name) || empty($categories) || empty($description)) {
            return "All fields except poster are required.";
        }

        if ($posterFile && $posterFile['error'] === UPLOAD_ERR_OK) {

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($posterFile['type'], $allowedTypes)) {
                return "Invalid image type.";
            }

            $imageData = file_get_contents($posterFile['tmp_name']);

            $sql = "UPDATE movies 
                    SET name = :name,
                        categories = :categories,
                        description = :description,
                        poster = :poster
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':poster', $imageData, PDO::PARAM_LOB);

        } else {

            $sql = "UPDATE movies 
                    SET name = :name,
                        categories = :categories,
                        description = :description
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
        }

        $stmt->bindParam(':id',          $id,          PDO::PARAM_INT);
        $stmt->bindParam(':name',        $name,        PDO::PARAM_STR);
        $stmt->bindParam(':categories',  $categories,  PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);

        return $stmt->execute()
            ? "Movie updated successfully."
            : "Failed to update movie.";
    }

    // =========================
    // DELETE: Delete Movie
    // =========================
    public function deleteMovie($id) {

        if (empty($id)) {
            return "Invalid ID.";
        }

        $sql  = "DELETE FROM movies WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute()
            ? "Movie deleted successfully."
            : "Failed to delete movie.";
    }
    // =========================
    // ADD RATING
    // =========================
    public function addRating($movieId, $userId, $rating, $description) {

        if (empty($movieId) || empty($userId) || empty($rating)) {
            return ["success" => false, "error" => "MovieID, UserID and Rating are required."];
        }

        if (!is_numeric($rating) || $rating < 1 || $rating > 10) {
            return ["success" => false, "error" => "Rating must be a number between 1 and 10."];
        }

        try {
            // Check if user already rated this movie
            $checkSql  = "SELECT Id FROM Ratings 
                        WHERE MovieID = :movieId AND UserID = :userId";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $checkStmt->bindParam(':userId',  $userId,  PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                return ["success" => false, "error" => "You have already rated this movie."];
            }

            $sql  = "INSERT INTO Ratings (MovieID, UserID, Rating, Description)
                    VALUES (:movieId, :userId, :rating, :description)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':movieId',     $movieId,     PDO::PARAM_INT);
            $stmt->bindParam(':userId',      $userId,      PDO::PARAM_INT);
            $stmt->bindParam(':rating',      $rating,      PDO::PARAM_INT);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);

            $stmt->execute();

            return [
                "success"  => true,
                "message"  => "Rating added successfully.",
                "ratingId" => $this->pdo->lastInsertId()
            ];

        } catch (PDOException $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    // =========================
    // UPDATE RATING
    // =========================
    public function updateRating($id, $rating, $description) {

        if (empty($id) || empty($rating)) {
            return ["success" => false, "error" => "Rating ID and Rating value are required."];
        }

        if (!is_numeric($rating) || $rating < 1 || $rating > 10) {
            return ["success" => false, "error" => "Rating must be a number between 1 and 10."];
        }

        try {
            // Check rating exists
            $checkSql  = "SELECT Id FROM Ratings WHERE Id = :id";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();

            if (!$checkStmt->fetch()) {
                return ["success" => false, "error" => "Rating not found."];
            }

            $sql  = "UPDATE Ratings 
                    SET Rating = :rating, Description = :description
                    WHERE Id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id',          $id,          PDO::PARAM_INT);
            $stmt->bindParam(':rating',      $rating,      PDO::PARAM_INT);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);

            $stmt->execute();

            return ["success" => true, "message" => "Rating updated successfully."];

        } catch (PDOException $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    // =========================
    // DELETE RATING
    // =========================
    public function deleteRating($id) {

        if (empty($id)) {
            return ["success" => false, "error" => "Rating ID is required."];
        }

        try {
            // Check rating exists
            $checkSql  = "SELECT Id FROM Ratings WHERE Id = :id";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();

            if (!$checkStmt->fetch()) {
                return ["success" => false, "error" => "Rating not found."];
            }

            $sql  = "DELETE FROM Ratings WHERE Id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return ["success" => true, "message" => "Rating deleted successfully."];

        } catch (PDOException $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    // =========================
    // GET ALL RATINGS FOR A MOVIE
    // =========================
    public function getRatingsByMovie($movieId) {

        if (empty($movieId)) {
            return ["success" => false, "error" => "Movie ID is required."];
        }

        try {
            $sql  = "SELECT r.Id, r.MovieID, r.UserID, u.UserName,
                            r.Rating, r.Description
                    FROM Ratings r
                    JOIN users u ON r.UserID = u.Id
                    WHERE r.MovieID = :movieId
                    ORDER BY r.Id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':movieId', $movieId, PDO::PARAM_INT);
            $stmt->execute();

            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($ratings)) {
                return ["success" => true, "data" => [], "message" => "No ratings found for this movie."];
            }

            // Calculate average rating
            $average = round(
                array_sum(array_column($ratings, 'Rating')) / count($ratings),
                1
            );

            return [
                "success"        => true,
                "movieId"        => $movieId,
                "totalRatings"   => count($ratings),
                "averageRating"  => $average,
                "data"           => $ratings
            ];

        } catch (PDOException $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    // =========================
    // GET ALL RATINGS BY A USER
    // =========================
    public function getRatingsByUser($userId) {

        if (empty($userId)) {
            return ["success" => false, "error" => "User ID is required."];
        }

        try {
            $sql  = "SELECT r.Id, r.MovieID, m.name AS movieName,
                            r.UserID, r.Rating, r.Description
                    FROM Ratings r
                    JOIN movies m ON r.MovieID = m.id
                    WHERE r.UserID = :userId
                    ORDER BY r.Id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($ratings)) {
                return ["success" => true, "data" => [], "message" => "No ratings found for this user."];
            }

            return [
                "success"      => true,
                "userId"       => $userId,
                "totalRatings" => count($ratings),
                "data"         => $ratings
            ];

        } catch (PDOException $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }
}

// ─────────────────────────────────────────
// Handle HTTP Requests
// ─────────────────────────────────────────
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$db     = new DB_Ops($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET /DB_Ops.php?action=getAllMovies
if ($method === 'GET' && $action === 'getAllMovies') {
    $result = $db->getAllMovies();
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);

// POST /DB_Ops.php?action=signup
} elseif ($method === 'POST' && $action === 'signup') {
    $body = json_decode(file_get_contents("php://input"), true);

    $result = $db->signup(
        $body['userName']  ?? '',
        $body['email']     ?? '',
        $body['password']  ?? '',
        $body['birthDate'] ?? ''
    );

    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);

// POST /DB_Ops.php?action=login
} elseif ($method === 'POST' && $action === 'login') {
    $body = json_decode(file_get_contents("php://input"), true);

    $result = $db->login(
        $body['email']    ?? '',
        $body['password'] ?? ''
    );

    http_response_code($result['success'] ? 200 : 401);
    echo json_encode($result);

// POST /DB_Ops.php?action=addRating
} elseif ($method === 'POST' && $action === 'addRating') {
    $body   = json_decode(file_get_contents("php://input"), true);
    $result = $db->addRating(
        $body['movieId']     ?? '',
        $body['userId']      ?? '',
        $body['rating']      ?? '',
        $body['description'] ?? ''
    );
    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);

// POST /DB_Ops.php?action=updateRating
} elseif ($method === 'POST' && $action === 'updateRating') {
    $body   = json_decode(file_get_contents("php://input"), true);
    $result = $db->updateRating(
        $body['id']          ?? '',
        $body['rating']      ?? '',
        $body['description'] ?? ''
    );
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

// POST /DB_Ops.php?action=deleteRating
} elseif ($method === 'POST' && $action === 'deleteRating') {
    $body   = json_decode(file_get_contents("php://input"), true);
    $result = $db->deleteRating($body['id'] ?? '');
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

// GET /DB_Ops.php?action=getRatingsByMovie&movieId=1
} elseif ($method === 'GET' && $action === 'getRatingsByMovie') {
    $result = $db->getRatingsByMovie($_GET['movieId'] ?? '');
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

// GET /DB_Ops.php?action=getRatingsByUser&userId=1
} elseif ($method === 'GET' && $action === 'getRatingsByUser') {
    $result = $db->getRatingsByUser($_GET['userId'] ?? '');
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

// POST /DB_Ops.php?action=insertMovie  (multipart/form-data)
} elseif ($method === 'POST' && $action === 'insertMovie') {
    $result  = $db->insertMovie(
        $_POST['name']        ?? '',
        $_POST['categories']  ?? '',
        $_POST['description'] ?? '',
        $_FILES['poster']     ?? null
    );
    $success = str_contains($result, 'successfully');
    http_response_code($success ? 201 : 400);
    echo json_encode(["success" => $success, "message" => $result]);

// POST /DB_Ops.php?action=updateMovie  (multipart/form-data)
} elseif ($method === 'POST' && $action === 'updateMovie') {
    $result  = $db->updateMovie(
        $_POST['id']          ?? '',
        $_POST['name']        ?? '',
        $_POST['categories']  ?? '',
        $_POST['description'] ?? '',
        $_FILES['poster']     ?? null
    );
    $success = str_contains($result, 'successfully');
    http_response_code($success ? 200 : 400);
    echo json_encode(["success" => $success, "message" => $result]);

// POST /DB_Ops.php?action=deleteMovie
} elseif ($method === 'POST' && $action === 'deleteMovie') {
    $body    = json_decode(file_get_contents("php://input"), true);
    $result  = $db->deleteMovie($body['id'] ?? '');
    $success = str_contains($result, 'successfully');
    http_response_code($success ? 200 : 400);
    echo json_encode(["success" => $success, "message" => $result]);

} else {
    http_response_code(404);
    echo json_encode(["error" => "Unknown action."]);
}