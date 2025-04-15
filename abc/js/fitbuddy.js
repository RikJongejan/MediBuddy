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
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }

    // Chatbot functionality
    const chatbotTrigger = document.getElementById('chatbot-trigger');
    const chatInputContainer = document.getElementById('chat-input-container');
    const chatMessages = document.getElementById('chatMessages');
    const chatToggle = document.getElementById('chatToggle');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');
    const chatContainer = document.getElementById('chat-container');
    
    // Store the original demo chat content to restore later
    const originalChatContent = chatMessages ? chatMessages.innerHTML : '';
    let chatActive = false;
    
    // Nebius API key - in production this should be secured
    const NEBIUS_API_KEY = 'eyJhbGciOiJIUzI1NiIsImtpZCI6IlV6SXJWd1h0dnprLVRvdzlLZWstc0M1akptWXBvX1VaVkxUZlpnMDRlOFUiLCJ0eXAiOiJKV1QifQ.eyJzdWIiOiJnb29nbGUtb2F1dGgyfDEwMzY5Mjg3OTQ5NDY4MjU1MDMzMCIsInNjb3BlIjoib3BlbmlkIG9mZmxpbmVfYWNjZXNzIiwiaXNzIjoiYXBpX2tleV9pc3N1ZXIiLCJhdWQiOlsiaHR0cHM6Ly9uZWJpdXMtaW5mZXJlbmNlLmV1LmF1dGgwLmNvbS9hcGkvdjIvIl0sImV4cCI6MTkwMjM5NTQxOCwidXVpZCI6IjEyNzM1NGJjLTlhNTctNDM1MC04ZGYzLWExMGI1MzhlNTEwNSIsIm5hbWUiOiJmaXRidWRkeSIsImV4cGlyZXNfYXQiOiIyMDMwLTA0LTE0VDExOjEwOjE4KzAwMDAifQ.mMdmwfTZxZk7YouGvqH4trt_41BF0QowYBZT6plAOBQ';
    
    // Toggle chat open/closed
    if (chatToggle && chatContainer) {
        chatToggle.addEventListener('click', function() {
            // Only allow toggling if chat is active (not in demo mode)
            if (chatActive) {
                chatActive = false;
                chatMessages.innerHTML = originalChatContent;
                chatInputContainer.style.display = 'none';
            }
            // Otherwise do nothing if in demo mode
        });
    }
    
    // Start chat when clicking "try the chatbot"
    if (chatbotTrigger) {
        chatbotTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            
            chatActive = true;
            
            // Clear all messages
            if (chatMessages) {
                chatMessages.innerHTML = '';
            }
            
            // Show chat input
            if (chatInputContainer) {
                chatInputContainer.style.display = 'flex';
            }
            
            // Add initial bot message
            if (chatMessages) {
                const welcomeDiv = document.createElement('div');
                welcomeDiv.className = 'message bot';
                welcomeDiv.innerHTML = '<p>Hello! I\'m your nutrition assistant. How can I help you today?</p>';
                chatMessages.appendChild(welcomeDiv);
            }
            
            // Focus on input
            if (userInput) {
                userInput.focus();
            }
            
            // Scroll to chat section
            document.getElementById('chat-section').scrollIntoView({
                behavior: 'smooth'
            });
        });
    }
    
    // Send message function
    function sendMessage() {
        if (!userInput) return;
        
        const message = userInput.value.trim();
        if (message === '') return;
        
        // Add user message to chat
        addMessage(message, 'user');
        userInput.value = '';
        
        // Show thinking indicator
        const thinkingId = addMessage('<div class="thinking-indicator"><span></span><span></span><span></span></div>', 'bot');
        
        // Send to Nebius API directly
        callNebiusAPI(message)
            .then(response => {
                // Remove thinking message
                const thinkingElement = document.getElementById(thinkingId);
                if (thinkingElement) thinkingElement.remove();
                
                // Add bot response
                addMessage(response, 'bot');
            })
            .catch(error => {
                // Remove thinking message
                const thinkingElement = document.getElementById(thinkingId);
                if (thinkingElement) thinkingElement.remove();
                
                // Add error message
                addMessage('Error connecting to the AI. Please try again.', 'bot');
                console.error('API Error:', error);
            });
    }
    
    // Function to call Nebius API
    async function callNebiusAPI(userMessage) {
        const url = 'https://api.studio.nebius.com/v1/chat/completions';
        
        const requestData = {
            model: "meta-llama/Meta-Llama-3.1-70B-Instruct-fast",
            max_tokens: 512,
            temperature: 0.6,
            top_p: 0.9,
            extra_body: {
                top_k: 50
            },
            messages: [
                {
                    role: "system",
                    content: "You are a nutrition and fitness assistant that provides direct, concise answers about healthy eating, calorie counts, and exercise recommendations. Keep your responses brief and to the point. Focus on providing specific facts and numbers rather than general advice when possible. Use simple markdown formatting to highlight important information: **bold** for key points, *italic* for emphasis, and ***bold italic*** for very important information. Use - or * for bullet points when listing items."
                },
                {
                    role: "user",
                    content: userMessage
                }
            ]
        };
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${NEBIUS_API_KEY}`
                },
                body: JSON.stringify(requestData)
            });
            
            if (!response.ok) {
                throw new Error(`API returned status ${response.status}`);
            }
            
            const data = await response.json();
            return data.choices[0].message.content;
        } catch (error) {
            console.error('Error calling Nebius API:', error);
            throw error;
        }
    }
    
    // Parse markdown-like formatting in text
    function parseMarkdown(text) {
        if (!text) return '';
        
        // Replace bold italic: ***text*** or ___text___
        text = text.replace(/(\*\*\*|___)([^*_]+)(\*\*\*|___)/g, '<strong><em>$2</em></strong>');
        
        // Replace bold: **text** or __text__
        text = text.replace(/(\*\*|__)([^*_]+)(\*\*|__)/g, '<strong>$2</strong>');
        
        // Replace italic: *text* or _text_
        text = text.replace(/(\*|_)([^*_]+)(\*|_)/g, '<em>$2</em>');
        
        // Replace headers: # Header 1, ## Header 2, etc.
        text = text.replace(/^# (.+)$/gm, '<h3>$1</h3>');
        text = text.replace(/^## (.+)$/gm, '<h4>$1</h4>');
        text = text.replace(/^### (.+)$/gm, '<h5>$1</h5>');
        
        // Replace lists: - item or * item
        text = text.replace(/^(\s*)[-*] (.+)$/gm, '$1<li>$2</li>');
        text = text.replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>');
        
        // Replace line breaks
        text = text.replace(/\n/g, '<br>');
        
        return text;
    }
    
    // Add message to chat
    function addMessage(text, sender) {
        if (!chatMessages) return;
        
        const messageId = 'msg-' + Date.now();
        const messageDiv = document.createElement('div');
        messageDiv.id = messageId;
        messageDiv.className = `message ${sender}`;
        
        // Apply markdown parsing for bot messages only
        if (sender === 'bot' && !text.startsWith('<div')) {
            text = parseMarkdown(text);
            messageDiv.innerHTML = `<p>${text}</p>`;
        } else if (text.startsWith('<div')) {
            messageDiv.innerHTML = text;
        } else {
            messageDiv.innerHTML = `<p>${text}</p>`;
        }
        
        chatMessages.appendChild(messageDiv);
        
        // Scroll to the new message
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        return messageId;
    }
    
    // Event listeners for sending messages
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
    
    if (userInput) {
        userInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // Add styles for thinking indicator and markdown formatting
    document.head.insertAdjacentHTML('beforeend', `
    <style>
    .thinking-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 30px;
    }
    .thinking-indicator span {
        display: inline-block;
        width: 8px;
        height: 8px;
        margin: 0 4px;
        background-color: #4CAF50;
        border-radius: 50%;
        opacity: 0.6;
        animation: thinking 1.4s infinite ease-in-out both;
    }
    .thinking-indicator span:nth-child(1) {
        animation-delay: -0.32s;
    }
    .thinking-indicator span:nth-child(2) {
        animation-delay: -0.16s;
    }
    @keyframes thinking {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
    
    /* Markdown styling */
    .message.bot p strong {
        font-weight: 600;
        color: #111;
    }
    
    .message.bot p em {
        font-style: italic;
        color: #333;
    }
    
    .message.bot p strong em, 
    .message.bot p em strong {
        font-weight: 600;
        font-style: italic;
        color: #000;
    }
    
    .message.bot h3, 
    .message.bot h4, 
    .message.bot h5 {
        margin: 8px 0 4px 0;
        font-weight: 600;
    }
    
    .message.bot h3 {
        font-size: 1.2em;
    }
    
    .message.bot h4 {
        font-size: 1.1em;
    }
    
    .message.bot h5 {
        font-size: 1em;
    }
    
    .message.bot ul {
        margin: 4px 0;
        padding-left: 20px;
    }
    
    .message.bot li {
        margin-bottom: 2px;
    }
    </style>
    `);
});
