//code for animation toggling
let signup = document.querySelector(".signup");
let login = document.querySelector(".login");
let slider = document.querySelector(".slider");
let formSection = document.querySelector(".form-section");

signup.addEventListener("click", () => {
    slider.classList.add("moveslider");
    formSection.classList.add("form-section-move");
});

login.addEventListener("click", () => {
    slider.classList.remove("moveslider");
    formSection.classList.remove("form-section-move");
});

//Password validation
const passwordInput = document.getElementById('signup-password');
const passwordError = document.getElementById('password-error');

passwordInput.addEventListener('keyup', () => {
    if (passwordInput.value.length < 8) {
        passwordError.classList.add('visible');
    } else {
        passwordError.classList.remove('visible');
    }
});

//LOGIN FORM - Connected to Database
const signInForm = document.getElementById('signInForm');
const loginEmail = document.getElementById('login-email');
const loginPassword = document.getElementById('login-password');
const loginError = document.getElementById('login-error');

signInForm.addEventListener('submit', (e) => {
    e.preventDefault();
    loginError.classList.remove('visible');
    
    const formData = new FormData();
    formData.append('email', loginEmail.value);
    formData.append('password', loginPassword.value);
    
    fetch('actions/login_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Login Successful! Redirecting...');
            window.location.href = data.redirect;
        } else {
            loginError.textContent = data.message;
            loginError.classList.add('visible');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        loginError.textContent = 'An error occurred. Please try again.';
        loginError.classList.add('visible');
    });
});

//SIGNUP FORM - Connected to Database
const signUpForm = document.getElementById('signUpForm');

signUpForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const email = document.getElementById('signup-email').value.trim();
    const password = document.getElementById('signup-password').value.trim();
    const confirmPassword = document.getElementById('confirm-password').value.trim();
    
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    formData.append('confirm_password', confirmPassword);
    
    fetch('actions/signup_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Switch to login form
            slider.classList.remove("moveslider");
            formSection.classList.remove("form-section-move");
            signUpForm.reset();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});