/**
 * Movie Tracker - Main JavaScript Module
 * Handles SPA navigation, AJAX calls, and UI interactions
 */

// ========================================
// State Management
// ========================================
let currentUser = JSON.parse(localStorage.getItem('movieTrackerUser')) || null;
let currentMovieId = null;

// ========================================
// SPA View Navigation
// ========================================

/**
 * Switch between SPA views without page reload
 * @param {string} viewName - The view ID to show (e.g., 'home', 'movies', 'auth')
 */
function showView(viewName) {
    // Guard: profile requires authentication
    if (viewName === 'profile' && !currentUser) {
        showToast('Please login to view your profile', 'error');
        viewName = 'auth';
    }

    // Hide all views
    document.querySelectorAll('.view').forEach(view => {
        view.classList.remove('active');
    });

    // Show target view
    const targetView = document.getElementById(`view-${viewName}`);
    if (targetView) {
        targetView.classList.add('active');
    }

    // Update nav active state
    document.querySelectorAll('.main-nav a').forEach(link => {
        link.classList.toggle('active', link.dataset.view === viewName);
    });

    // Close mobile menu
    document.querySelector('.main-nav').classList.remove('active');

    // Load data based on view
    if (viewName === 'movies' || viewName === 'home') {
        loadMovies(viewName === 'home' ? 'featured-movies' : 'movies-container');
    } else if (viewName === 'profile' && currentUser) {
        loadUserProfile();
        loadUserRatings(currentUser.id);
    } else if (viewName === 'auth') {
        Validation.clearErrors();
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Navigation click handlers
document.querySelectorAll('.main-nav a').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const viewName = link.dataset.view;

        // Handle logout
        if (viewName === 'auth' && currentUser) {
            logout();
            return;
        }

        showView(viewName);
    });
});

// Mobile menu toggle
document.querySelector('.menu-toggle').addEventListener('click', () => {
    document.querySelector('.main-nav').classList.toggle('active');
});

// ========================================
// AJAX Helper Functions
// ========================================

/**
 * Make a GET request
 * @param {string} url - The URL to fetch
 * @returns {Promise<Object>} - JSON response
 */
async function get(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('GET Error:', error);
        showToast('Network error. Please try again.', 'error');
        throw error;
    }
}

/**
 * Make a POST request with JSON data
 * @param {string} url - The URL to post to
 * @param {Object} data - The data to send
 * @returns {Promise<Object>} - JSON response
 */
async function post(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('POST Error:', error);
        showToast('Network error. Please try again.', 'error');
        throw error;
    }
}

/**
 * Make a POST request with FormData (for file uploads)
 * @param {string} url - The URL to post to
 * @param {FormData} formData - The FormData object
 * @returns {Promise<Object>} - JSON response
 */
async function postFormData(url, formData) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    } catch (error) {
        console.error('POST FormData Error:', error);
        showToast('Network error. Please try again.', 'error');
        throw error;
    }
}

// ========================================
// Movie Operations
// ========================================

/**
 * Load all movies from the database
 * @param {string} containerId - The container element ID
 */
async function loadMovies(containerId = 'movies-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '<div class="spinner"></div>';

    try {
        const result = await get('DB_Ops.php?action=getAllMovies');

        if (result.success && result.data && result.data.length > 0) {
            container.innerHTML = result.data.map((movie, index) => `
                <div class="movie-card" style="animation-delay: ${index * 0.1}s" onclick="showMovieDetail(${movie.id})">
                    <div class="poster-wrapper">
                        <img src="data:image/jpeg;base64,${movie.poster}" alt="${escapeHtml(movie.name)}" loading="lazy">
                        <div class="overlay">
                            <h3>${escapeHtml(movie.name)}</h3>
                            <p class="categories">${escapeHtml(movie.categories)}</p>
                            <div class="card-actions">
                                <button class="btn-primary" onclick="event.stopPropagation(); showMovieDetail(${movie.id})">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="empty-state">No movies found. Add the first one!</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load movies. Please try again.</p>';
    }
}

/**
 * Show movie detail view
 * @param {number} movieId - The movie ID
 */
async function showMovieDetail(movieId) {
    currentMovieId = movieId;

    const container = document.getElementById('movie-detail-content');
    container.innerHTML = '<div class="spinner"></div>';

    // Clear stale content from any previously viewed movie
    const ratingsContainer = document.getElementById('movie-ratings');
    if (ratingsContainer) ratingsContainer.innerHTML = '';
    const ratingFormEl = document.getElementById('add-rating-form');
    if (ratingFormEl) ratingFormEl.style.display = 'none';

    showView('movie-detail');

    try {
        // Get all movies and find the one we need
        const result = await get('DB_Ops.php?action=getAllMovies');

        if (result.success && result.data) {
            const movie = result.data.find(m => m.id == movieId);

            if (movie) {
                container.innerHTML = `
                    <div class="movie-detail">
                        <div class="movie-detail-poster">
                            <img src="data:image/jpeg;base64,${movie.poster}" alt="${escapeHtml(movie.name)}">
                        </div>
                        <div class="movie-detail-info">
                            <h2>${escapeHtml(movie.name)}</h2>
                            <p class="categories">${escapeHtml(movie.categories)}</p>
                            <p class="description">${escapeHtml(movie.description)}</p>
                            <div class="detail-actions">
                                <button class="btn-primary" onclick="showAddRatingForm()">Rate Movie</button>
                                <button class="btn-secondary" onclick="showView('movies')">Back to List</button>
                            </div>
                        </div>
                    </div>
                `;

                // Load ratings for this movie
                loadMovieRatings(movieId);

                // Show rating form if user is logged in
                const ratingForm = document.getElementById('add-rating-form');
                if (ratingForm) {
                    ratingForm.style.display = currentUser ? 'block' : 'none';
                }
            } else {
                container.innerHTML = '<p class="empty-state">Movie not found.</p>';
            }
        } else {
            container.innerHTML = '<p class="empty-state">Failed to load movie details.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load movie details.</p>';
    }
}

/**
 * Load ratings for a specific movie
 * @param {number} movieId - The movie ID
 */
async function loadMovieRatings(movieId) {
    const container = document.getElementById('movie-ratings');
    if (!container) return;

    container.innerHTML = '<div class="spinner"></div>';

    try {
        const result = await get(`DB_Ops.php?action=getRatingsByMovie&movieId=${movieId}`);

        if (result.success && result.data && result.data.length > 0) {
            const averageRating = result.averageRating || 0;
            container.innerHTML = `
                <div class="average-rating">
                    <strong>Average Rating: ${averageRating}/10</strong> 
                    (${result.totalRatings} reviews)
                </div>
                ${result.data.map(rating => `
                    <div class="rating-item">
                        <div class="rating-header">
                            <span class="username">${escapeHtml(rating.UserName)}</span>
                            <span class="rating-value">${rating.Rating}/10</span>
                        </div>
                        ${rating.Description ? `<p>${escapeHtml(rating.Description)}</p>` : ''}
                    </div>
                `).join('')}
            `;
        } else {
            container.innerHTML = '<p class="empty-state">No ratings yet. Be the first to rate!</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="empty-state">Failed to load ratings.</p>';
    }
}

// Add movie form handler
document.getElementById('add-movie-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const errors = Validation.validateMovie(formData);
    
    if (!Validation.showErrors(errors)) return;
    
    try {
        const result = await postFormData('DB_Ops.php?action=insertMovie', formData);
        
        if (result.success) {
            showToast('Movie added successfully!', 'success');
            e.target.reset();
            Validation.clearErrors();
            showView('movies');
        } else {
            showToast(result.message || 'Failed to add movie', 'error');
        }
    } catch (error) {
        showToast('Error adding movie', 'error');
    }
});

// ========================================
// Authentication
// ========================================

// Login form handler
document.getElementById('login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        email: document.getElementById('login-email').value.trim(),
        password: document.getElementById('login-password').value
    };
    
    const errors = Validation.validateLogin(data);
    if (!Validation.showErrors(errors)) return;
    
    try {
        const result = await post('DB_Ops.php?action=login', data);
        
        if (result.success) {
            currentUser = result.user;
            localStorage.setItem('movieTrackerUser', JSON.stringify(currentUser));
            showToast('Login successful!', 'success');
            updateAuthUI();
            showView('home');
        } else {
            showToast(result.error || 'Login failed. Please check your credentials.', 'error');
        }
    } catch (error) {
        showToast('Login error. Please try again.', 'error');
    }
});

// Signup form handler
document.getElementById('signup-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        userName: document.getElementById('signup-username').value.trim(),
        email: document.getElementById('signup-email').value.trim(),
        password: document.getElementById('signup-password').value,
        birthDate: document.getElementById('signup-birthdate').value
    };
    
    const errors = Validation.validateSignup(data);
    if (!Validation.showErrors(errors)) return;
    
    try {
        const result = await post('DB_Ops.php?action=signup', data);
        
        if (result.success) {
            showToast('Account created! Please login.', 'success');
            showLoginForm();
            Validation.clearErrors();
        } else {
            showToast(result.error || 'Signup failed.', 'error');
        }
    } catch (error) {
        showToast('Signup error. Please try again.', 'error');
    }
});

// Toggle between login and signup forms
document.getElementById('show-signup')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('login-form-container').style.display = 'none';
    document.getElementById('signup-form-container').style.display = 'block';
    Validation.clearErrors();
});

document.getElementById('show-login')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('signup-form-container').style.display = 'none';
    document.getElementById('login-form-container').style.display = 'block';
    Validation.clearErrors();
});

function showLoginForm() {
    document.getElementById('signup-form-container').style.display = 'none';
    document.getElementById('login-form-container').style.display = 'block';
}

/**
 * Update UI based on authentication state
 */
function updateAuthUI() {
    const authLink = document.getElementById('authLink');
    if (currentUser) {
        authLink.textContent = 'Logout';
        // Keep data-view as 'auth' so the nav click handler can
        // detect the logout case via `viewName === 'auth' && currentUser`
        authLink.dataset.view = 'auth';
    } else {
        authLink.textContent = 'Login';
        authLink.dataset.view = 'auth';
    }
}

/**
 * Logout the current user
 */
function logout() {
    currentUser = null;
    localStorage.removeItem('movieTrackerUser');
    showToast('Logged out successfully', 'success');
    updateAuthUI();
    showView('home');
}

// ========================================
// Rating Operations
// ========================================

function showAddRatingForm() {
    if (!currentUser) {
        showToast('Please login to rate movies', 'error');
        showView('auth');
        return;
    }
    document.getElementById('add-rating-form').scrollIntoView({ behavior: 'smooth' });
}

// Rating form handler
document.getElementById('rating-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (!currentUser) {
        showToast('Please login to rate', 'error');
        return;
    }
    
    const data = {
        movieId: currentMovieId,
        userId: currentUser.id,
        rating: parseInt(document.getElementById('rating-value').value),
        description: document.getElementById('rating-description').value.trim()
    };
    
    const errors = Validation.validateRating(data);
    if (!Validation.showErrors(errors)) return;
    
    try {
        const result = await post('DB_Ops.php?action=addRating', data);
        
        if (result.success) {
            showToast('Rating added!', 'success');
            e.target.reset();
            Validation.clearErrors();
            loadMovieRatings(currentMovieId);
        } else {
            showToast(result.error || 'Failed to add rating', 'error');
        }
    } catch (error) {
        showToast('Error adding rating', 'error');
    }
});

/**
 * Load user profile data
 */
function loadUserProfile() {
    if (!currentUser) return;
    
    document.getElementById('profile-name').textContent = currentUser.userName;
    document.getElementById('profile-email').textContent = currentUser.email;
}

/**
 * Load ratings made by the current user
 * @param {number} userId - The user ID
 */
async function loadUserRatings(userId) {
    const container = document.getElementById('user-ratings');
    if (!container) return;
    
    container.innerHTML = '<div class="spinner"></div>';
    
    try {
        const result = await get(`DB_Ops.php?action=getRatingsByUser&userId=${userId}`);
        
        if (result.success && result.data && result.data.length > 0) {
            document.getElementById('total-ratings').textContent = result.totalRatings;
            
            container.innerHTML = result.data.map(rating => `
                <div class="rating-item">
                    <div class="rating-header">
                        <span class="username">${escapeHtml(rating.movieName)}</span>
                        <span class="rating-value">${rating.Rating}/10</span>
                    </div>
                    ${rating.Description ? `<p>${escapeHtml(rating.Description)}</p>` : ''}
                </div>
            `).join('');
        } else {
            document.getElementById('total-ratings').textContent = '0';
            container.innerHTML = '<p class="empty-state">No ratings yet. Start rating movies!</p>';
        }
    } catch (error) {
        document.getElementById('total-ratings').textContent = '0';
        container.innerHTML = '<p class="empty-state">Failed to load your ratings.</p>';
    }
}

// ========================================
// Search Movies
// ========================================

function searchMovies() {
    const query = document.getElementById('movie-search').value.trim();
    if (!query) {
        loadMovies('movies-container');
        return;
    }
    
    // Filter loaded movies (client-side search)
    const container = document.getElementById('movies-container');
    const cards = container.querySelectorAll('.movie-card');
    let hasResults = false;
    
    cards.forEach(card => {
        const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
        const categories = card.querySelector('.categories')?.textContent.toLowerCase() || '';
        
        if (title.includes(query.toLowerCase()) || categories.includes(query.toLowerCase())) {
            card.style.display = '';
            hasResults = true;
        } else {
            card.style.display = 'none';
        }
    });
    
    const existingNoResults = document.getElementById('no-results');
    if (!hasResults) {
        if (!existingNoResults) {
            const noResults = document.createElement('p');
            noResults.className = 'empty-state';
            noResults.id = 'no-results';
            noResults.textContent = 'No movies match your search.';
            container.appendChild(noResults);
        }
    } else if (existingNoResults) {
        existingNoResults.remove();
    }
}

// Search on Enter key
document.getElementById('movie-search')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') searchMovies();
});

// ========================================
// Utility Functions
// ========================================

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - The type: 'success', 'error', or 'info'
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Escape HTML to prevent XSS attacks
 * @param {string} text - The text to escape
 * @returns {string} - Escaped HTML string
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// Initialization
// ========================================

// Restore user session on page load
if (currentUser) {
    updateAuthUI();
}

// Show home view by default
showView('home');