/**
 * Client-Side Validation Module
 * Validates all form inputs before sending AJAX requests
 */

const Validation = {
    /**
     * Validate signup form data
     * @param {Object} data - { userName, email, password, birthDate }
     * @returns {Object} errors - field errors
     *
     * FIX (error key names): Error keys were renamed from camelCase (userName,
     * birthDate) to lowercase (username, birthdate) so they match the element
     * IDs in index.php (signup-username-error, signup-birthdate-error) when
     * combined with the 'signup-' prefix passed to showErrors.
     *
     * FIX (age calculation): Age was previously calculated using year difference
     * only (today.getFullYear() - birthDate.getFullYear()), which over-counted
     * by 1 for anyone whose birthday has not yet occurred this calendar year.
     * Now we subtract 1 if the current month/day is still before the birthday.
     */
    validateSignup(data) {
        const errors = {};
        
        if (!data.userName || data.userName.trim().length < 3) {
            errors.username = 'Username must be at least 3 characters';
        }
        
        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.email = 'Please enter a valid email address';
        }
        
        if (!data.password || data.password.length < 6) {
            errors.password = 'Password must be at least 6 characters';
        }
        
        if (!data.birthDate) {
            errors.birthdate = 'Birth date is required';
        } else {
            const birthDate = new Date(data.birthDate);
            const today = new Date();
            const yearDiff = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            const dayDiff = today.getDate() - birthDate.getDate();
            // Subtract 1 if the birthday has not happened yet this year
            const age = (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) ? yearDiff - 1 : yearDiff;
            if (birthDate > today || age < 13) {
                errors.birthdate = 'You must be at least 13 years old';
            }
        }
        
        return errors;
    },
    
    /**
     * Validate login form data
     * @param {Object} data - { email, password }
     * @returns {Object} errors - field errors
     */
    validateLogin(data) {
        const errors = {};
        
        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.email = 'Please enter a valid email address';
        }
        
        if (!data.password) {
            errors.password = 'Password is required';
        }
        
        return errors;
    },
    
    /**
     * Validate movie form data
     * @param {FormData} formData - FormData object with movie fields
     * @returns {Object} errors - field errors
     */
    validateMovie(formData) {
        const errors = {};
        
        const name = formData.get('name');
        if (!name || name.trim().length === 0) {
            errors.name = 'Movie name is required';
        } else if (name.trim().length < 2) {
            errors.name = 'Movie name must be at least 2 characters';
        }
        
        const categories = formData.get('categories');
        if (!categories || categories.trim().length === 0) {
            errors.categories = 'At least one category is required';
        }
        
        const description = formData.get('description');
        if (!description || description.trim().length === 0) {
            errors.description = 'Description is required';
        } else if (description.trim().length < 10) {
            errors.description = 'Description must be at least 10 characters';
        }
        
        const poster = formData.get('poster');
        if (!poster || poster.size === 0) {
            errors.poster = 'Poster image is required';
        } else {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(poster.type)) {
                errors.poster = 'Only JPG, PNG, or WEBP images are allowed';
            }
            // Max 5MB
            if (poster.size > 5 * 1024 * 1024) {
                errors.poster = 'Image must be less than 5MB';
            }
        }
        
        return errors;
    },
    
    /**
     * Validate rating form data
     * @param {Object} data - { rating, description }
     * @returns {Object} errors - field errors
     */
    validateRating(data) {
        const errors = {};
        
        const rating = parseInt(data.rating);
        if (isNaN(rating) || rating < 1 || rating > 10) {
            errors.rating = 'Rating must be a number between 1 and 10';
        }
        
        return errors;
    },
    
    /**
     * Display validation errors in the form
     * @param {Object} errors - Object with field names as keys and error messages as values
     * @param {string} [prefix=''] - Optional prefix prepended to each field name when
     *   building the element ID, e.g. 'login-' targets 'login-email-error'.
     *   Pass 'login-' for the login form and 'signup-' for the signup form.
     *   Leave empty (default) for the movie and rating forms.
     * @returns {boolean} - true if no errors, false if errors exist
     *
     * FIX: Added the optional prefix parameter. Previously the function always
     * looked up bare field names like 'email-error', which only matched the
     * movie/rating form spans. The login and signup forms prefix their error
     * span IDs with the form name (e.g. 'login-email-error'), so without the
     * prefix those spans were never found and errors were never shown.
     */
    showErrors(errors, prefix = '') {
        // Clear all previous errors
        document.querySelectorAll('.error-msg').forEach(el => {
            el.textContent = '';
            el.classList.remove('active');
        });
        
        // Show new errors with shake animation
        Object.keys(errors).forEach(field => {
            const errorEl = document.getElementById(`${prefix}${field}-error`);
            if (errorEl) {
                errorEl.textContent = errors[field];
                errorEl.classList.add('active');
            }
        });
        
        // Return true if no errors
        return Object.keys(errors).length === 0;
    },
    
    /**
     * Clear all validation errors
     */
    clearErrors() {
        document.querySelectorAll('.error-msg').forEach(el => {
            el.textContent = '';
            el.classList.remove('active');
        });
    }
};