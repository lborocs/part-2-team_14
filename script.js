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

//Prevent forms from submitting (front-end demo)
const signInForm = document.getElementById('signInForm');
const signUpForm = document.getElementById('signUpForm');
const loginEmail = document.getElementById('login-email');
const loginPassword = document.getElementById('login-password');
const loginError = document.getElementById('login-error');

signInForm.addEventListener('submit', (e) => {
    e.preventDefault(); // Prevent default form submission
    loginError.classList.remove('visible'); // Hide error on new attempt

    // Hard-coded login check
    const email = loginEmail.value;
    const password = loginPassword.value;

    let userEmail = null;
    let redirectUrl = null;

    if (email === 'user@make-it-all.co.uk' && password === 'Password123!') {
        // Team Member
        userEmail = 'user@make-it-all.co.uk';
        redirectUrl = 'user/home/home.html';

    } else if (email === 'specialist@make-it-all.co.uk' && password === 'Password123!') {
        // Technical Specialist
        userEmail = 'specialist@make-it-all.co.uk';
        redirectUrl = 'user/home/home.html'; // All users land on home first

    } else if (email === 'manager@make-it-all.co.uk' && password === 'Password123!') {
        // Manager
        userEmail = 'manager@make-it-all.co.uk';
        redirectUrl = 'user/home/home.html';

    } else if (email === 'leader@make-it-all.co.uk' && password === 'Password123!') {
        // Team Leaders
        userEmail = 'leader@make-it-all.co.uk';
        redirectUrl = 'user/home/home.html';
    } else {
        // FAILURE: Show login error
        loginError.classList.add('visible');
    }

    if (userEmail && redirectUrl) {
    //SUCCESS: Store user in session storage as backup
    sessionStorage.setItem('currentUserEmail', userEmail);

    //Redirect to the dashboard WITH the user query parameter
    alert('Login Successful! Redirecting...');
    window.location.href = `${redirectUrl}?user=${encodeURIComponent(userEmail)}`;
}
});

signUpForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const email = document.getElementById('signup-email').value.trim();
    const password = document.getElementById('signup-password').value.trim();

    // --- Check email format ---
    const emailPattern = /^[a-zA-Z0-9._%+-]+@make-it-all\.co.uk$/;
    if (!emailPattern.test(email)) {
        alert("Email must end with '@make-it-all.co.uk'");
        return;
    }

    // --- Check if email already exists (prevent duplicates of hardcoded ones) ---
    const existingEmails = [
        'user@make-it-all.co.uk',
        'specialist@make-it-all.co.uk',
        'manager@make-it-all.co.uk',
        'leader@make-it-all.co.uk'
    ];

    if (existingEmails.includes(email.toLowerCase())) {
        alert("This email is already registered. Please use another one.");
        return;
    }

    // --- Password validation ---
    // At least 8 chars, at least 1 letter, 1 special char, and at least 3 digits
    const passwordPattern = /^(?=.*[A-Za-z])(?=.*[^A-Za-z0-9])(?=(?:.*\d){3,}).{8,}$/;

    if (!passwordPattern.test(password)) {
        alert("Password must be at least 8 characters long, include at least 1 letter, 1 special character, and at least 3 numbers.");
        return;
    }

    // Passed all checks
    alert(`Account created successfully for ${email}`);
    // In real system, you’d now save to backend or storage
});
