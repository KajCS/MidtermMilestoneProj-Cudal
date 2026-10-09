// Front-end behavior for Kusina ng Barangay (plain JavaScript, no libraries).

document.addEventListener('DOMContentLoaded', () => {
    setupIngredientFields();
    setupPasswordMatch();
    setupFavoriteButtons();
    setupConfirmForms();
});

// "Add ingredient" creates a new input box; ✕ removes one.
function setupIngredientFields() {
    const list = document.getElementById('ingredient-list');
    const addButton = document.getElementById('add-ingredient');
    if (!list || !addButton) return;

    const MAX_INGREDIENTS = 30;

    addButton.addEventListener('click', () => {
        if (list.children.length >= MAX_INGREDIENTS) {
            alert(`A recipe can have up to ${MAX_INGREDIENTS} ingredients.`);
            return;
        }
        const newRow = list.firstElementChild.cloneNode(true); // copy an existing row
        const input = newRow.querySelector('input');
        input.value = '';
        list.appendChild(newRow);
        input.focus();
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-ingredient');
        if (!removeButton) return;

        if (list.children.length === 1) {
            // Keep at least one box; just clear it
            list.querySelector('input').value = '';
            return;
        }
        removeButton.closest('.ingredient-row').remove();
    });
}

// Register page: show "Passwords do not match" before the form is sent.
function setupPasswordMatch() {
    const form = document.querySelector('form[data-register]');
    if (!form) return;

    const password = form.querySelector('input[name="password"]');
    const confirm = form.querySelector('input[name="confirm_password"]');

    const check = () => {
        confirm.setCustomValidity(confirm.value === password.value ? '' : 'Passwords do not match.');
    };
    password.addEventListener('input', check);
    confirm.addEventListener('input', check);
}

// ♥ Save / Saved without reloading the page (fetch + JSON).
function setupFavoriteButtons() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.fav-btn');
        if (!button) return;

        const willSave = button.getAttribute('aria-pressed') !== 'true';
        const body = new FormData();
        body.append('recipe_id', button.dataset.recipeId);
        body.append('action', willSave ? 'add' : 'remove');

        button.disabled = true;
        try {
            const response = await fetch('favorite-toggle.php', { method: 'POST', body });

            if (response.status === 401) {
                window.location.href = 'login.php'; // session expired
                return;
            }

            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'Request failed');
            }

            setFavoriteState(button, data.favorited);

            // Favorites page: remove the card once it is un-saved
            if (!data.favorited && button.dataset.removeCard) {
                button.closest('.recipe-card').remove();
                if (!document.querySelector('.recipe-card')) {
                    document.getElementById('favorites-empty').hidden = false;
                }
            }
        } catch (error) {
            alert('Could not update your favorites. Please try again.');
        } finally {
            button.disabled = false;
        }
    });
}

function setFavoriteState(button, saved) {
    button.setAttribute('aria-pressed', saved ? 'true' : 'false');
    button.querySelector('.fav-label').textContent = saved ? 'Saved' : 'Save';
}

// Any form with data-confirm="..." asks before submitting (used for Delete).
function setupConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}
