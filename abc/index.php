<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitBuddy - Your Personal Fitness Companion</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
</head>
<body>
    <div class="landing-page">
        <header>
            <nav>
                <div class="logo" data-aos="fade-right" data-aos-duration="800">
                    <h1>FitBuddy</h1>
                    <li>Help</li>
                </div>
                <div class="login-button">
                    <a href="#" class="btn-login">Login</a>
                </div>
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </header>

        <section class="hero">
            <div class="hero-content" data-aos="fade-right" data-aos-duration="1000">
                <h1>Track. Improve. Achieve.</h1>
                <p>Your all-in-one solution for tracking nutrition, weight, and fitness progress.</p>
                <div class="hero-cta">
                    <a href="#chat-section" class="cta-button" id="chatbot-trigger">try the chatbot</a>
                </div>
            </div>
            <section id="chat-section" class="chat-section">
                <!-- Chat container -->
                <div class="chat-container-preview" data-aos="fade-left" data-aos-duration="900" id="chat-container">
                    <div class="chat-header">
                        <div class="chat-title">Nutrition Assistant</div>
                        <button class="chat-toggle" id="chatToggle">−</button>
                    </div>
                    <div class="chat-preview" id="chatMessages">
                        <!-- Demo messages -->
                        <div class="message bot">
                            <p>I can help you with nutrition information. What would you like to know?</p>
                        </div>
                        <div class="message user">
                            <p>How many calories in a banana?</p>
                        </div>
                        <div class="message bot">
                            <p>A medium banana (118g) contains approximately 105 calories, along with 27g of carbs, 3g of fiber, and 1.3g of protein.</p>
                        </div>
                    </div>
                    <div class="chat-input-area" id="chat-input-container" style="display: none;">
                        <textarea id="userInput" placeholder="Type your question here..." class="chat-input"></textarea>
                        <button id="sendButton" class="send-button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </section>
        </section>

        <section id="features" class="features">
            <h2 data-aos="fade-up">Everything You Need to Reach Your Goals</h2>
            <div class="feature-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Nutrition Tracking</h3>
                    <p>Log your meals and track calories, macros, and micronutrients with our extensive food database.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon">
                        <i class="fas fa-weight"></i>
                    </div>
                    <h3>Weight Monitoring</h3>
                    <p>Track your weight changes over time with visual graphs and trend analysis.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>Workout Logging</h3>
                    <p>Record gym sessions, steps, cycling, and other activities to track your fitness progress.</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Weekly Statistics</h3>
                    <p>Get insights on your progress with detailed weekly and monthly statistics.</p>
                </div>
            </div>
        </section>



    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="img/logo.png" alt="FitBuddy Logo">
                <h3>FitBuddy</h3>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Product</h4>
                    <a href="#">Features</a>
                    <a href="#">Pricing</a>
                    <a href="#">FAQ</a>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <a href="#">About</a>
                    <a href="#">Blog</a>
                    <a href="#">Careers</a>
                </div>
                <div class="footer-column">
                    <h4>Legal</h4>
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Security</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2023 FitBuddy. All rights reserved.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
            
            // Toggle mobile menu
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('.nav-links');
            
            menuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });

            // Chatbot functionality
            const chatbotTrigger = document.getElementById('chatbot-trigger');
            const chatInputContainer = document.getElementById('chat-input-container');
            const chatInput = document.getElementById('userInput');
            const sendMessageButton = document.getElementById('sendButton');
            const chatMessages = document.getElementById('chatMessages');
            let chatActive = false;

            // Make chat interactive when clicking the button
            chatbotTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!chatActive) {
                    // Show input area
                    chatInputContainer.style.display = 'flex';
                    chatInput.focus();
                    chatActive = true;

                    // Scroll to chat section
                    document.getElementById('chat-section').scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });

            // Function to send message
            function sendMessage() {
                const userMessage = chatInput.value.trim();
                if (userMessage !== '') {
                    // Add user message
                    const userMessageDiv = document.createElement('div');
                    userMessageDiv.className = 'message user';
                    userMessageDiv.innerHTML = `<p>${userMessage}</p>`;
                    chatMessages.appendChild(userMessageDiv);
                    chatInput.value = '';
                    
                    // Auto-scroll to bottom of chat
                    chatMessages.scrollTop = chatMessages.scrollHeight;

                    // Simulate bot response after a short delay
                    setTimeout(function() {
                        let botResponse = "I'm sorry, I don't have specific information about that.";
                        
                        // Simple response logic
                        if (userMessage.toLowerCase().includes('calorie') || userMessage.toLowerCase().includes('calories')) {
                            botResponse = "A general recommendation is 2000-2500 calories per day for adults, but this varies based on activity level, age, and goals.";
                        } else if (userMessage.toLowerCase().includes('protein')) {
                            botResponse = "Most adults should consume 0.8g of protein per kg of body weight daily. Athletes may need 1.2-2.0g per kg.";
                        } else if (userMessage.toLowerCase().includes('banana')) {
                            botResponse = "A medium banana (118g) contains approximately 105 calories, along with 27g of carbs, 3g of fiber, and 1.3g of protein.";
                        } else if (userMessage.toLowerCase().includes('hello') || userMessage.toLowerCase().includes('hi')) {
                            botResponse = "Hello! How can I help you with nutrition information today?";
                        } else if (userMessage.toLowerCase().includes('weight loss')) {
                            botResponse = "Weight loss typically requires a caloric deficit. A safe rate of loss is 0.5-1kg per week, usually achieved through a 500-1000 calorie daily deficit.";
                        } else if (userMessage.toLowerCase().includes('carbs') || userMessage.toLowerCase().includes('carbohydrates')) {
                            botResponse = "Carbohydrates should make up about 45-65% of your daily calories. Focus on complex carbs like whole grains, beans, fruits and vegetables.";
                        } else if (userMessage.toLowerCase().includes('fat')) {
                            botResponse = "Healthy fats should comprise 20-35% of your daily calories. Focus on unsaturated fats from sources like olive oil, avocados, nuts, and fatty fish.";
                        }
                        
                        const botMessageDiv = document.createElement('div');
                        botMessageDiv.className = 'message bot';
                        botMessageDiv.innerHTML = `<p>${botResponse}</p>`;
                        chatMessages.appendChild(botMessageDiv);
                        
                        // Auto-scroll to bottom of chat
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }, 800);
                }
            }

            sendMessageButton.addEventListener('click', sendMessage);
            
            // Allow Enter key to send messages
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
    <script src="js/fitbuddy.js"></script>
</body>
</html>
