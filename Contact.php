<?php
session_start();
require_once 'connectdb.php';

// Check login state
$userData = null;

if (isset($_SESSION['uid'])) {
    try {
        $stmt = $db->prepare("SELECT uid, email, billing_fullname, role FROM users WHERE uid = ?");
        $stmt->execute([$_SESSION['uid']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Homepage user fetch error: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Contact Us</title>

    <link rel="stylesheet" href="css/rentique.css">
    <link rel="icon" type="image/png" href="images/rentique_logo.png">
    <script src="js/theme.js" defer></script>


</head>
<body>

<header>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="images/rentique_logo.png" alt="Rentique Logo">
            </a>
            <span>rentique.</span>
        </div>

        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="productsPage.php">Shop</a></li>
            <li><a href="AboutUs.php">About</a></li>
            <li><a href="Contact.php">Contact</a></li>
            <li>
                <a href="BasketPage.php" class="cart-icon">Basket</a>
            </li>
            <button id="themeToggle">Theme</button>
           
            <?php if (isset($userData['role']) && $userData['role'] === 'customer'): ?>
                <li><a href="seller_dashboard.php">Sell</a></li>
                <li><a href="user_dashboard.php"><?= htmlspecialchars($userData['billing_fullname'] ?? "Account") ?></a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

            <?php elseif (isset($userData['role']) && $userData['role'] === 'admin'): ?>
            <!-- Admin logged in -->
                <li><a href="admin_dashboard.php">Admin</a></li>
                <li><a href="index.php?logout=1" class="btn login">Logout</a></li>

            <?php else: ?>
                <li><a href="login.php" class="btn login">Login</a></li>
                <li><a href="signup.php" class="btn signup">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
    <section class="intro">
        <h1>Get in Touch</h1>
        <p class="subtitle">We would like to hear from you</p>
        <p class="subtitle">If you have any inquiries please contact us here</p>

        <div class="contactForm">
            <div class="nameRow">
                <div>
                    <label for="firstName">First Name *</label>
                    <input type="text" id="firstName" placeholder="First Name">
                    <p id="firstNameError" class="error-msg" style="display: none; color: red; font-size: 14px; margin-top: 5px;"></p>
                </div>

                <div>
                    <label for="lastName">Last Name *</label>
                    <input type="text" id="lastName" placeholder="Last Name">
                    <p id="lastNameError" class="error-msg" style="display: none; color: red; font-size: 14px; margin-top: 5px;"></p>
                </div>
            </div>

            <div>
                <label for="email">Email *</label>
                <input type="email" id="email" placeholder="Email">
                <p id="emailError" class="error-msg" style="display: none; color: red; font-size: 14px; margin-top: 5px;"></p>
            </div>

            <div>
                <label for="message">Message *</label>
                <textarea id="message" placeholder="Type your message here." rows="5"></textarea>
                <p id="messageError" class="error-msg" style="display: none; color: red; font-size: 14px; margin-top: 5px;"></p>
            </div>

            <button type="button" id="sendBtn">Send</button>

            <p id="responseMsg" style="text-align:center; margin-top:10px;"></p>
        </div>
    </section>
</main>

<footer>
    <p>© 2025 Rentique. All rights reserved.</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const firstNameField = document.getElementById("firstName");
    const lastNameField = document.getElementById("lastName");
    const emailField = document.getElementById("email");
    const messageField = document.getElementById("message");
    const sendBtn = document.getElementById("sendBtn");
    const responseMsg = document.getElementById("responseMsg");
    
    // Error message elements
    const firstNameError = document.getElementById("firstNameError");
    const lastNameError = document.getElementById("lastNameError");
    const emailError = document.getElementById("emailError");
    const messageError = document.getElementById("messageError");

    // Validation patterns
    const namePattern = /^[A-Za-z\s'-]+$/;
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    // Name validation function
    function validateName(field, errorElement) {
        const value = field.value.trim();
        if (value === "") {
            errorElement.textContent = "This field is required.";
            errorElement.style.display = "block";
            return false;
        } else if (!namePattern.test(value)) {
            errorElement.textContent = "Only letters, spaces, hyphens, and apostrophes are allowed.";
            errorElement.style.display = "block";
            return false;
        } else if (value.length < 2) {
            errorElement.textContent = "Name must be at least 2 characters long.";
            errorElement.style.display = "block";
            return false;
        } else {
            errorElement.style.display = "none";
            return true;
        }
    }

    // Email validation function
    function validateEmail() {
        const emailValue = emailField.value.trim();
        if (emailValue === "") {
            emailError.textContent = "Email is required.";
            emailError.style.display = "block";
            return false;
        } else if (!emailPattern.test(emailValue)) {
            emailError.textContent = "Please enter a valid email address.";
            emailError.style.display = "block";
            return false;
        } else {
            emailError.style.display = "none";
            return true;
        }
    }

    // Message validation function
    function validateMessage() {
        const messageValue = messageField.value.trim();
        if (messageValue === "") {
            messageError.textContent = "Message is required.";
            messageError.style.display = "block";
            return false;
        } else if (messageValue.length < 10) {
            messageError.textContent = "Message must be at least 10 characters long.";
            messageError.style.display = "block";
            return false;
        } else if (messageValue.length > 1000) {
            messageError.textContent = "Message cannot exceed 1000 characters.";
            messageError.style.display = "block";
            return false;
        } else {
            messageError.style.display = "none";
            return true;
        }
    }

    // Real-time validation listeners
    firstNameField.addEventListener("blur", () => validateName(firstNameField, firstNameError));
    firstNameField.addEventListener("input", () => {
        if (firstNameError.style.display !== "none") {
            validateName(firstNameField, firstNameError);
        }
    });

    lastNameField.addEventListener("blur", () => validateName(lastNameField, lastNameError));
    lastNameField.addEventListener("input", () => {
        if (lastNameError.style.display !== "none") {
            validateName(lastNameField, lastNameError);
        }
    });

    emailField.addEventListener("blur", validateEmail);
    emailField.addEventListener("input", () => {
        if (emailError.style.display !== "none") {
            validateEmail();
        }
    });

    messageField.addEventListener("blur", validateMessage);
    messageField.addEventListener("input", () => {
        if (messageError.style.display !== "none") {
            validateMessage();
        }
    });

    // Form submission handler
    sendBtn.addEventListener("click", function() {
        // Validate all fields
        const isFirstNameValid = validateName(firstNameField, firstNameError);
        const isLastNameValid = validateName(lastNameField, lastNameError);
        const isEmailValid = validateEmail();
        const isMessageValid = validateMessage();

        // If any validation fails, don't submit
        if (!isFirstNameValid || !isLastNameValid || !isEmailValid || !isMessageValid) {
            responseMsg.textContent = "Please fix the errors above before sending.";
            responseMsg.style.color = "red";
            return;
        }

        // Get form data
        const firstName = firstNameField.value.trim();
        const lastName = lastNameField.value.trim();
        const email = emailField.value.trim();
        const message = messageField.value.trim();

        // Clear previous response
        responseMsg.textContent = "Sending...";
        responseMsg.style.color = "blue";

        // Send request
        fetch("contactpage.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ 
                firstName, 
                lastName, 
                email, 
                message 
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                responseMsg.textContent = data.message;
                responseMsg.style.color = "green";
                
                // Clear form on successful submission
                firstNameField.value = "";
                lastNameField.value = "";
                emailField.value = "";
                messageField.value = "";
                
                // Clear error messages
                firstNameError.style.display = "none";
                lastNameError.style.display = "none";
                emailError.style.display = "none";
                messageError.style.display = "none";
            } else {
                responseMsg.textContent = data.message || "Failed to send message.";
                responseMsg.style.color = "red";
            }
        })
        .catch(error => {
            console.error('Error:', error);
            responseMsg.textContent = "Error sending message. Please try again.";
            responseMsg.style.color = "red";
        });
    });

    // Optional: Allow form submission on Enter key in the message field
    messageField.addEventListener("keydown", function(event) {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            sendBtn.click();
        }
    });
});
</script>

</body>
</html>