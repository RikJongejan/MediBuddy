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
    const scrollFactor = 0.3; // How much the button moves with scroll (0-1)
    
    // Set initial position
    chatToggleBtn.style.position = 'absolute'; // Change to absolute to move with scroll
    chatToggleBtn.style.top = initialButtonTop + 'px';
    
    // Handle scroll reactivity - make button move with scroll
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Calculate new position based on scroll
        currentButtonTop = initialButtonTop + (scrollTop * scrollFactor);
        
        // Apply bounds to keep button on screen
        const minTop = 100; // Minimum distance from top
        const maxTop = window.innerHeight - 100; // Maximum position
        
        currentButtonTop = Math.max(minTop, Math.min(currentButtonTop, maxTop));
        
        // Update button position
        chatToggleBtn.style.top = currentButtonTop + 'px';
        
        // If chat is open, also update its position
        if (chatWidget.classList.contains('active')) {
            chatWidget.style.position = 'absolute';
            chatWidget.style.top = (currentButtonTop - 430) + 'px'; // Position above button
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
        
        // Set chat position based on button position
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
