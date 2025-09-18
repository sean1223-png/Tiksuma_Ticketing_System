function toggleForms() {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const formTitle = document.getElementById('formTitle');

        if (loginForm.style.display === "none") {
            loginForm.style.display = "block";
            registerForm.style.display = "none";
            formTitle.textContent = "LOG IN";
        } else {
            loginForm.style.display = "none";
            registerForm.style.display = "block";
            formTitle.textContent = "CREATE ACCOUNT";
        }
    }
