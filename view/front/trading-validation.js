/**
 * Trading Form Validation
 * Handles all input validation for trading forms
 */

// Validation configuration
const VALIDATION_RULES = {
    skinName: {
        minLength: 3,
        maxLength: 100,
        pattern: /^[a-zA-Z0-9\s\-_]+$/,
        message: {
            minLength: 'Skin name must be at least 3 characters long',
            maxLength: 'Skin name cannot exceed 100 characters',
            pattern: 'Skin name can only contain letters, numbers, spaces, hyphens, and underscores',
            empty: 'Skin name is required'
        }
    },
    skinPrice: {
        min: 0.01,
        max: 999999.99,
        message: {
            min: 'Price must be at least $0.01',
            max: 'Price cannot exceed $999,999.99',
            invalid: 'Please enter a valid price',
            empty: 'Price is required'
        }
    },
    skinDescription: {
        maxLength: 100,
        message: {
            maxLength: 'Description cannot exceed 100 characters',
            empty: 'Description is optional'
        }
    },
    skinImage: {
        maxSize: 5 * 1024 * 1024, // 5MB
        allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        message: {
            size: 'Image size must be less than 5MB',
            type: 'Please upload a valid image file (JPEG, PNG, GIF, or WebP)',
            empty: 'Skin image is required'
        }
    },
    discussionMessage: {
        minLength: 1,
        maxLength: 500,
        message: {
            minLength: 'Message cannot be empty',
            maxLength: 'Message cannot exceed 500 characters',
            empty: 'Please enter a message'
        }
    }
};

/**
 * Show error message for an input field
 */
function showError(input, message) {
    // Remove existing error
    removeError(input);

    // Add error class to input
    input.classList.add('input-error');

    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;

    // Insert error message after input
    input.parentNode.insertBefore(errorDiv, input.nextSibling);

    // Add shake animation
    input.style.animation = 'shake 0.3s';
    setTimeout(() => {
        input.style.animation = '';
    }, 300);
}

/**
 * Remove error message for an input field
 */
function removeError(input) {
    input.classList.remove('input-error');
    const errorMessage = input.parentNode.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

/**
 * Show success state for an input field
 */
function showSuccess(input) {
    removeError(input);
    input.classList.add('input-success');
}

/**
 * Validate skin name
 */
function validateSkinName(nameInput) {
    const value = nameInput.value.trim();

    if (!value) {
        showError(nameInput, VALIDATION_RULES.skinName.message.empty);
        return false;
    }

    if (value.length < VALIDATION_RULES.skinName.minLength) {
        showError(nameInput, VALIDATION_RULES.skinName.message.minLength);
        return false;
    }

    if (value.length > VALIDATION_RULES.skinName.maxLength) {
        showError(nameInput, VALIDATION_RULES.skinName.message.maxLength);
        return false;
    }

    if (!VALIDATION_RULES.skinName.pattern.test(value)) {
        showError(nameInput, VALIDATION_RULES.skinName.message.pattern);
        return false;
    }

    showSuccess(nameInput);
    return true;
}

/**
 * Validate skin price
 */
function validateSkinPrice(priceInput) {
    const value = parseFloat(priceInput.value);

    if (isNaN(value) || priceInput.value.trim() === '') {
        showError(priceInput, VALIDATION_RULES.skinPrice.message.empty);
        return false;
    }

    if (value < VALIDATION_RULES.skinPrice.min) {
        showError(priceInput, VALIDATION_RULES.skinPrice.message.min);
        return false;
    }

    if (value > VALIDATION_RULES.skinPrice.max) {
        showError(priceInput, VALIDATION_RULES.skinPrice.message.max);
        return false;
    }

    showSuccess(priceInput);
    return true;
}


function validateSkinDescription(descInput) {
    const value = descInput.value.trim();


    if (value && value.length > VALIDATION_RULES.skinDescription.maxLength) {
        showError(descInput, VALIDATION_RULES.skinDescription.message.maxLength);
        return false;
    }

    if (value) {
        showSuccess(descInput);
    } else {
        removeError(descInput);
    }

    return true;
}


function validateSkinImage(imageInput) {
    if (!imageInput.files || imageInput.files.length === 0) {
        showError(imageInput, VALIDATION_RULES.skinImage.message.empty);
        return false;
    }

    const file = imageInput.files[0];


    if (!VALIDATION_RULES.skinImage.allowedTypes.includes(file.type)) {
        showError(imageInput, VALIDATION_RULES.skinImage.message.type);
        return false;
    }


    if (file.size > VALIDATION_RULES.skinImage.maxSize) {
        showError(imageInput, VALIDATION_RULES.skinImage.message.size);
        return false;
    }

    showSuccess(imageInput);
    return true;
}


function validateDiscussionMessage(messageInput) {
    const value = messageInput.value.trim();

    if (!value) {
        showError(messageInput, VALIDATION_RULES.discussionMessage.message.empty);
        return false;
    }

    if (value.length < VALIDATION_RULES.discussionMessage.minLength) {
        showError(messageInput, VALIDATION_RULES.discussionMessage.message.minLength);
        return false;
    }

    if (value.length > VALIDATION_RULES.discussionMessage.maxLength) {
        showError(messageInput, VALIDATION_RULES.discussionMessage.message.maxLength);
        return false;
    }

    showSuccess(messageInput);
    return true;
}


function validateAddTradeForm(form) {
    let isValid = true;

    const skinName = form.querySelector('#skinName');
    const skinPrice = form.querySelector('#skinPrice');
    const skinDescription = form.querySelector('#skinDescription');
    const skinImage = form.querySelector('#skinImage');


    if (!validateSkinName(skinName)) isValid = false;
    if (!validateSkinPrice(skinPrice)) isValid = false;
    if (!validateSkinDescription(skinDescription)) isValid = false;
    if (!validateSkinImage(skinImage)) isValid = false;

    return isValid;
}

function validateEditTradeForm(form) {
    let isValid = true;

    const skinName = form.querySelector('#editSkinName');
    const skinPrice = form.querySelector('#editSkinPrice');
    const skinDescription = form.querySelector('#editSkinDescription');

    // Validate all fields
    if (!validateSkinName(skinName)) isValid = false;
    if (!validateSkinPrice(skinPrice)) isValid = false;
    if (!validateSkinDescription(skinDescription)) isValid = false;

    return isValid;
}

/**
 * Clear all validation errors from a form
 */
function clearFormErrors(form) {
    if (!form) return;

    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        removeError(input);
        input.classList.remove('input-success');
    });
}

/**
 * Initialize validation for all forms
 */
function initializeValidation() {
    // Add CSS for error states if not already present
    if (!document.getElementById('validation-styles')) {
        const style = document.createElement('style');
        style.id = 'validation-styles';
        style.textContent = `
            .input-error {
                border-color: #ffffff!important;
                background-color: rgba(255, 71, 87, 0.1) !important;
            }
            .input-success {
                border-color: #2ed573 !important;
            }
            .error-message {
                color: #ffffff !important;
                font-size: 12px;
                margin-top: 4px;
                margin-bottom: 8px;
                display: block;
                animation: fadeIn 0.3s;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    }

    // Add Trade Form validation
    const addTradeForm = document.getElementById('tradeForm');
    if (addTradeForm) {
        const skinName = addTradeForm.querySelector('#skinName');
        const skinPrice = addTradeForm.querySelector('#skinPrice');
        const skinDescription = addTradeForm.querySelector('#skinDescription');
        const skinImage = addTradeForm.querySelector('#skinImage');

        // Real-time validation on input
        if (skinName) {
            skinName.addEventListener('blur', () => validateSkinName(skinName));
            skinName.addEventListener('input', () => {
                if (skinName.classList.contains('input-error')) {
                    validateSkinName(skinName);
                }
            });
        }

        if (skinPrice) {
            skinPrice.addEventListener('blur', () => validateSkinPrice(skinPrice));
            skinPrice.addEventListener('input', () => {
                if (skinPrice.classList.contains('input-error')) {
                    validateSkinPrice(skinPrice);
                }
            });
        }

        if (skinDescription) {
            skinDescription.addEventListener('blur', () => validateSkinDescription(skinDescription));
            skinDescription.addEventListener('input', () => {
                if (skinDescription.classList.contains('input-error')) {
                    validateSkinDescription(skinDescription);
                }
            });
        }

        if (skinImage) {
            skinImage.addEventListener('change', () => validateSkinImage(skinImage));
        }

        // Form submission validation
        addTradeForm.addEventListener('submit', function (e) {
            if (!validateAddTradeForm(addTradeForm)) {
                e.preventDefault();
                e.stopPropagation();

                // Scroll to first error
                const firstError = addTradeForm.querySelector('.input-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }

                return false;
            }
        });
    }

    // Edit Trade Form validation
    const editTradeForm = document.getElementById('editForm');
    if (editTradeForm) {
        const editSkinName = editTradeForm.querySelector('#editSkinName');
        const editSkinPrice = editTradeForm.querySelector('#editSkinPrice');
        const editSkinDescription = editTradeForm.querySelector('#editSkinDescription');

        // Real-time validation on input
        if (editSkinName) {
            editSkinName.addEventListener('blur', () => validateSkinName(editSkinName));
            editSkinName.addEventListener('input', () => {
                if (editSkinName.classList.contains('input-error')) {
                    validateSkinName(editSkinName);
                }
            });
        }

        if (editSkinPrice) {
            editSkinPrice.addEventListener('blur', () => validateSkinPrice(editSkinPrice));
            editSkinPrice.addEventListener('input', () => {
                if (editSkinPrice.classList.contains('input-error')) {
                    validateSkinPrice(editSkinPrice);
                }
            });
        }

        if (editSkinDescription) {
            editSkinDescription.addEventListener('blur', () => validateSkinDescription(editSkinDescription));
            editSkinDescription.addEventListener('input', () => {
                if (editSkinDescription.classList.contains('input-error')) {
                    validateSkinDescription(editSkinDescription);
                }
            });
        }

        // Form submission validation
        editTradeForm.addEventListener('submit', function (e) {
            if (!validateEditTradeForm(editTradeForm)) {
                e.preventDefault();
                e.stopPropagation();

                // Scroll to first error
                const firstError = editTradeForm.querySelector('.input-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }

                return false;
            }
        });
    }

    // Discussion message validation
    const discussionInput = document.getElementById('discussionInput');
    if (discussionInput) {
        discussionInput.addEventListener('blur', () => validateDiscussionMessage(discussionInput));
        discussionInput.addEventListener('input', () => {
            if (discussionInput.classList.contains('input-error')) {
                validateDiscussionMessage(discussionInput);
            }
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeValidation);
} else {
    initializeValidation();
}

