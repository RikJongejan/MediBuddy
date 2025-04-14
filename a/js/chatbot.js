document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const userInput = document.getElementById('userMessage');
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatWidget = document.getElementById('chatWidget');
    const chatCloseBtn = document.getElementById('chatCloseBtn');
    
    // Starting position and configuration
    const initialButtonTop = 600; // Initial top position in pixels
    let currentButtonTop = initialButtonTop;
    
    // Set initial position - use fixed positioning in the DOM
    chatToggleBtn.style.position = 'absolute';
    chatToggleBtn.style.top = initialButtonTop + 'px';
    
    // Handle scroll reactivity - make button move exactly with scroll
    window.addEventListener('scroll', function() {
        const scrollTop = window.scrollY;
        
        // Move button with scroll at exact 1:1 ratio
        const newPosition = initialButtonTop + scrollTop;
        
        // Update button position to follow the scroll exactly
        chatToggleBtn.style.top = newPosition + 'px';
        
        // Store current position
        currentButtonTop = newPosition;
        
        // If chat is open, also update its position to stay with the button
        if (chatWidget.classList.contains('active')) {
            chatWidget.style.position = 'absolute';
            chatWidget.style.top = (newPosition - 430) + 'px';
        }
    });
    
    // Toggle chat widget visibility
    chatToggleBtn.addEventListener('click', function() {
        openChat();
    });
    
    // Close chat when clicking the close button
    chatCloseBtn.addEventListener('click', function() {
        closeChat();
    });
    
    function openChat() {
        chatWidget.classList.add('active');
        chatToggleBtn.classList.add('hidden');
        
        // Set chat position based on exact button position
        chatWidget.style.position = 'absolute';
        chatWidget.style.top = (currentButtonTop - 430) + 'px';
        
        setTimeout(() => {
            userInput.focus();
        }, 300);
    }
    
    function closeChat() {
        chatWidget.classList.remove('active');
        setTimeout(() => {
            chatToggleBtn.classList.remove('hidden');
        }, 300); // Delay to match animation timing
    }
    
    async function sendMessage(userText) {
        appendMessage('user', userText);
        
        // Add a typing indicator for AI response
        const typingMessageId = "typing-" + Date.now();
        appendMessage('assistant', '<span id="'+typingMessageId+'">AI is thinking...</span>');
        
        try {
            // Using our secure proxy endpoint instead of direct API call
            const response = await fetch('api/chatbot_proxy.php', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    message: userText
                })
            });
            
            const data = await response.json();
            
            // Remove typing indicator
            const typingIndicator = document.getElementById(typingMessageId);
            if (typingIndicator) {
                typingIndicator.parentElement.removeChild(typingIndicator);
            }
            
            // Display response or error message
            if (data.error) {
                appendMessage('assistant', "Sorry, I encountered an error: " + data.error);
            } else {
                // Simulate a brief delay for a smooth transition
                setTimeout(() => {
                    appendMessage('assistant', data.response);
                }, 500);
            }
        } catch (error) {
            const typingIndicator = document.getElementById(typingMessageId);
            if (typingIndicator) {
                typingIndicator.parentElement.removeChild(typingIndicator);
            }
            appendMessage('assistant', "Error: Unable to connect to the assistant. Please try again later.");
        }
    }
    
    function appendMessage(role, text) {
        const div = document.createElement('div');
        div.classList.add('message', role);
        div.innerHTML = text.replace(/\n/g, '<br>');
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const text = userInput.value.trim();
            if (text.length === 0) return;
            sendMessage(text);
            userInput.value = '';
        });
    }
    
    // Ensure chat is scrolled to bottom on load
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Close chat when clicking outside (optional, can be removed if not wanted)
    document.addEventListener('click', function(e) {
        if (chatWidget.classList.contains('active') && 
            !chatWidget.contains(e.target) && 
            !chatToggleBtn.contains(e.target)) {
            closeChat();
        }
    });
});
