document.addEventListener('DOMContentLoaded', function() {
    const chatToggle = document.getElementById('chatToggle');
    const chatContainer = document.getElementById('chatContainer');
    const chatMessages = document.getElementById('chatMessages');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');

    // Toggle chat open/closed
    chatToggle.addEventListener('click', function() {
        chatContainer.classList.toggle('collapsed');
        chatToggle.textContent = chatContainer.classList.contains('collapsed') ? '+' : '−';
    });

    // Send message when Send button is clicked
    sendButton.addEventListener('click', sendMessage);

    // Send message when Enter is pressed (without Shift)
    userInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize input as user types
    userInput.addEventListener('input', function() {
        this.style.height = 'auto';
        const newHeight = Math.min(this.scrollHeight, 120);
        this.style.height = newHeight + 'px';
    });

    // Function to send user message and get response
    function sendMessage() {
        const message = userInput.value.trim();
        if (!message) return;

        // Add user message to chat
        addMessage(message, 'user');
        userInput.value = '';
        userInput.style.height = 'auto';

        // Show thinking indicator
        const thinkingId = addMessage('<div class="thinking-indicator"><span></span><span></span><span></span></div>', 'bot');

        // Enhance the user's message to better encourage markdown response
        let enhancedMessage = message;
        
        // If it's not already a specialized query, add a stronger formatting hint
        if (!message.toLowerCase().includes('maintenance') && 
            !message.toLowerCase().includes('list')) {
            enhancedMessage += "\n\nPlease use markdown in your response: ### for headers, *** for important text, ** for bold, * for italics, and - for bullet points.";
        } else if (message.toLowerCase().includes('list')) {
            // For lists, specifically ask for a structured format with calories
            enhancedMessage += "\n\nPlease format each food item clearly with its calories (e.g., 'Food name: 123 calories') and calculate the total calories at the end.";
        }

        // Send to API
        fetch('chatbotapi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: enhancedMessage })
        })
        .then(response => response.json())
        .then(data => {
            // Remove thinking message
            document.getElementById(thinkingId).remove();
            
            if (data.error) {
                addMessage('Error: ' + data.error, 'bot');
            } else {
                // Process the response
                addMessage(data.response, 'bot');
            }
        })
        .catch(error => {
            // Remove thinking message
            document.getElementById(thinkingId).remove();
            addMessage('Error connecting to the server. Please try again.', 'bot');
            console.error('Error:', error);
        });
    }

    // Function to add message to chat
    function addMessage(text, sender) {
        const messageId = 'msg-' + Date.now();
        const messageDiv = document.createElement('div');
        messageDiv.id = messageId;
        messageDiv.className = `message ${sender}`;
        messageDiv.innerHTML = text;
        chatMessages.appendChild(messageDiv);
        
        // Ensure smooth scrolling to the latest message
        setTimeout(() => {
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 100);
        
        return messageId;
    }

    // Function to format response for nutrition lists
    function formatResponse(response, userMessage) {
        // If the response is already formatted HTML (from our PHP backend)
        if (response.includes('<div class="nutrition-') || 
            response.includes('<div class="maintenance-')) {
            return response;
        }
        
        // Check if the message contains nutrition information
        if (userMessage.toLowerCase().includes('calories') || 
            userMessage.toLowerCase().includes('nutrition')) {
            
            // Basic formatting for non-list nutrition info
            let formattedResponse = `<div class="nutrition-info-card">
                <div class="nutrition-description">${response}</div>
            </div>`;
            
            return formattedResponse;
        }
        
        // If it's a list but not formatted by backend
        if (userMessage.toLowerCase().includes('list') || 
            (userMessage.includes('\n') && userMessage.split('\n').length > 1)) {
            
            let formattedResponse = `<div class="nutrition-card">
                <div class="nutrition-description">${response}</div>
            </div>`;
            
            return formattedResponse;
        }
        
        // Default response - just return as is
        return response;
    }
    
    // Optional: Add a helper function to highlight nutritional values in text
    function highlightNutritionalValues(text) {
        // Highlight calorie values
        text = text.replace(/(\d+)(\s*)(calories|kcal|cal)/gi, 
            '<span class="highlight-value">$1</span>$2$3');
        
        // Highlight nutrient values (protein, carbs, fat, etc.)
        text = text.replace(/(\d+\.?\d*)(\s*)(g)(\s*)(protein|carbs|carbohydrates|fat)/gi, 
            '<span class="highlight-value">$1</span>$2$3$4$5');
        
        return text;
    }

    // Add styles for thinking indicator
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
    </style>
    `);
});
