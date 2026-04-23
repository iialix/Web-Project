<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <div class="logo">Movie Tracker</div>
        <nav class="main-nav">
            <a href="#" data-view="home" class="active">Home</a>
            <a href="#" data-view="movies">Movies</a>
            <a href="#" data-view="add">Add Movie</a>
            <a href="#" data-view="profile">Profile</a>
            <a href="#" data-view="auth" id="authLink">Login</a>
        </nav>
        <button class="menu-toggle" aria-label="Toggle menu">&#9776;</button>
    </header>
    <main class="main-content">