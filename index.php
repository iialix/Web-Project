<?php include 'header.php'; ?>

<!-- View: Home -->
<section id="view-home" class="view active">
    <div class="hero">
        <h1>Welcome to Movie Tracker</h1>
        <p>Discover, rate, and track your favorite movies</p>
        <button class="btn-primary" onclick="showView('movies')">Browse Movies</button>
    </div>
    <div class="featured-section">
        <h2>Featured Movies</h2>
        <div id="featured-movies" class="movies-grid">
            <div class="spinner"></div>
        </div>
    </div>
</section>

<!-- View: Movies List -->
<section id="view-movies" class="view">
    <div class="section-header">
        <h2>All Movies</h2>
        <div class="search-bar">
            <input type="text" id="movie-search" placeholder="Search movies...">
            <button class="btn-primary" onclick="searchMovies()">Search</button>
        </div>
    </div>
    <div id="movies-container" class="movies-grid">
        <div class="spinner"></div>
    </div>
</section>

<!-- View: Movie Details -->
<section id="view-movie-detail" class="view">
    <button class="btn-secondary back-btn" onclick="showView('movies')">&larr; Back to Movies</button>
    <div id="movie-detail-content"></div>
    <div class="ratings-section">
        <h3>Ratings & Reviews</h3>
        <div id="movie-ratings"></div>
        <div id="add-rating-form" class="rating-form-container" style="display:none;">
            <h4>Add Your Rating</h4>
            <form id="rating-form">
                <div class="form-group">
                    <label>Rating (1-10)</label>
                    <input type="number" id="rating-value" min="1" max="10" required>
                    <span class="error-msg" id="rating-error"></span>
                </div>
                <div class="form-group">
                    <label>Review (Optional)</label>
                    <textarea id="rating-description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn-primary">Submit Rating</button>
            </form>
        </div>
    </div>
</section>

<!-- View: Add Movie -->
<section id="view-add" class="view">
    <div class="form-container">
        <h2>Add New Movie</h2>
        <form id="add-movie-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="movie-name">Movie Name *</label>
                <input type="text" id="movie-name" name="name" placeholder="Enter movie title">
                <span class="error-msg" id="name-error"></span>
            </div>
            <div class="form-group">
                <label for="movie-categories">Categories *</label>
                <input type="text" id="movie-categories" name="categories" placeholder="e.g., Action, Drama, Sci-Fi">
                <span class="error-msg" id="categories-error"></span>
            </div>
            <div class="form-group">
                <label for="movie-description">Description *</label>
                <textarea id="movie-description" name="description" rows="5" placeholder="Write a brief description..."></textarea>
                <span class="error-msg" id="description-error"></span>
            </div>
            <div class="form-group">
                <label for="movie-poster">Poster Image *</label>
                <input type="file" id="movie-poster" name="poster" accept="image/jpeg,image/png,image/webp">
                <small class="hint">Accepted formats: JPG, PNG, WEBP</small>
                <span class="error-msg" id="poster-error"></span>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Add Movie</button>
                <button type="button" class="btn-secondary" onclick="showView('movies')">Cancel</button>
            </div>
        </form>
    </div>
</section>

<!-- View: Authentication (Login/Signup) -->
<section id="view-auth" class="view">
    <div class="auth-container">
        <!-- Login Form -->
        <div id="login-form-container" class="auth-form">
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Login to your account</p>
            <form id="login-form">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" placeholder="your@email.com">
                    <span class="error-msg" id="login-email-error"></span>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" placeholder="Enter password">
                    <span class="error-msg" id="login-password-error"></span>
                </div>
                <button type="submit" class="btn-primary btn-full">Login</button>
                <p class="auth-switch">Don't have an account? <a href="#" id="show-signup">Sign up</a></p>
            </form>
        </div>
        
        <!-- Signup Form -->
        <div id="signup-form-container" class="auth-form" style="display:none;">
            <h2>Create Account</h2>
            <p class="auth-subtitle">Join Movie Tracker today</p>
            <form id="signup-form">
                <div class="form-group">
                    <label for="signup-username">Username</label>
                    <input type="text" id="signup-username" name="userName" placeholder="Choose a username">
                    <span class="error-msg" id="signup-username-error"></span>
                </div>
                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="email" placeholder="your@email.com">
                    <span class="error-msg" id="signup-email-error"></span>
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="password" placeholder="Min. 6 characters">
                    <span class="error-msg" id="signup-password-error"></span>
                </div>
                <div class="form-group">
                    <label for="signup-birthdate">Birth Date</label>
                    <input type="date" id="signup-birthdate" name="birthDate">
                    <span class="error-msg" id="signup-birthdate-error"></span>
                </div>
                <button type="submit" class="btn-primary btn-full">Sign Up</button>
                <p class="auth-switch">Already have an account? <a href="#" id="show-login">Login</a></p>
            </form>
        </div>
    </div>
</section>

<!-- View: Profile -->
<section id="view-profile" class="view">
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">&#128100;</div>
            <div class="profile-info">
                <h2 id="profile-name">User</h2>
                <p id="profile-email">user@email.com</p>
            </div>
        </div>
        <div class="profile-stats">
            <div class="stat-card">
                <span class="stat-number" id="total-ratings">0</span>
                <span class="stat-label">Ratings</span>
            </div>
        </div>
        <h3>My Ratings</h3>
        <div id="user-ratings" class="ratings-list">
            <p class="empty-state">No ratings yet. Start rating movies!</p>
        </div>
    </div>
</section>

<!-- Toast Notification Container -->
<div id="toast-container"></div>

<?php include 'footer.php'; ?>