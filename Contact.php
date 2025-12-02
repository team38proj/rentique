<?php
session_start();
require_once 'connectdb.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentique Contact Us</title>
    <link rel="stylesheet" href="rentique.css">
    <link rel="icon" type="image/png" href="rentique_logo.png">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="rentique_logo.png" alt="Rentique logo">
                <span>rentique.</span>
            </div>
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="productsPage.php">Shop</a></li>
                <li><a href="AboutUs.php">About</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="login.html" class="btn login">Login</a></li>
                <li><a href="signup.html" class="btn signup">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="intro">
            <h1>Get in Touch</h1>
            <p class="subtitle">We would like to hear from you!</p>
            <p class="subtitle">If you have any inquiries please contact us here.</p>

            <form class="contactForm" id="contactForm">
                <div class="nameRow">
                    <div>
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" placeholder="First Name">
                    </div>
                    <div>
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" placeholder="Last Name">
                    </div>
                </div>

                <div>
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>

                <div>
                    <label for="message">Message</label>
                    <textarea id="message" name="message" placeholder="Type your message here." rows="5" required></textarea>
                </div>

                <button type="submit" id="sendBtn">Send</button>
                <p id="contactFeedback" class="subtitle"></p>
            </form>
        </section>
    </main>

    <footer>
        <p>© 2025 Rentique. All rights reserved.</p>
    </footer>

    <script>
        const contactForm = document.getElementById('contactForm');
        const feedback = document.getElementById('contactFeedback');

        contactForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            feedback.textContent = '';

            const data = {
                firstName: document.getElementById('firstName').value.trim(),
                lastName: document.getElementById('lastName').value.trim(),
                email: document.getElementById('email').value.trim(),
                message: document.getElementById('message').value.trim(),
            };

            if (!data.email || !data.message) {
                feedback.textContent = 'Email and message are required.';
                return;
            }

            try {
                const response = await fetch('contactpage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();
                feedback.textContent = result.message || 'Unable to send message.';
                feedback.style.color = result.success ? 'green' : 'red';
            } catch (error) {
                feedback.textContent = 'Something went wrong. Please try again later.';
                feedback.style.color = 'red';
            }
        });
    </script>
</body>
</html>
