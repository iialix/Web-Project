/**
 * Client-Side Validation Module
 * Validates all form inputs before sending AJAX requests
 */

const Validation = {
    /**
     * Validate signup form data
     * @param {Object} data - { userName, email, password, birthDate }
     * @returns {Object} errors - field errors
     */
    validateSignup(data) {
        const errors = {};
        
        if (!data.userName || data.userName.trim().length < 3) {
            errors['signup-username'] = 'Username must be at least 3 characters';
        }
        
        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors['signup-email'] = 'Please enter a valid email address';
        }
        
        if (!data.password || data.password.length < 6) {
            errors['signup-password'] = 'Password must be at least 6 characters';
        }
        
        if (!data.birthDate) {
            errors['signup-birthdate'] = 'Birth date is required';
        } else {
            // Parse as local date (YYYY-MM-DD) to avoid timezone off-by-one issues
            const [year, month, day] = data.birthDate.split('-').map(Number);
            const birthDate = new Date(year, month - 1, day);
            const today = new Date();
            const isInvalidDate =
                !year || !month || !day ||
                isNaN(birthDate.getTime()) ||
                birthDate.getFullYear() !== year ||
                birthDate.getMonth() !== month - 1 ||
                birthDate.getDate() !== day;

            if (isInvalidDate || birthDate > today) {
                errors['signup-birthdate'] = 'Please enter a valid birth date';
            } else {
                // Compute age accounting for whether this year's birthday has passed
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                if (age < 13) {
                    errors['signup-birthdate'] = 'You must be at least 13 years old';
                }
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
            errors['login-email'] = 'Please enter a valid email address';
        }
        
        if (!data.password) {
            errors['login-password'] = 'Password is required';
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
     * @returns {boolean} - true if no errors, false if errors exist
     */
    showErrors(errors) {
        // Clear all previous errors
        document.querySelectorAll('.error-msg').forEach(el => {
            el.textContent = '';
            el.classList.remove('active');
        });
        
        // Show new errors with shake animation
        Object.keys(errors).forEach(field => {
            const errorEl = document.getElementById(`${field}-error`);
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