// script.js

// === Page & Navigation Logic ===
// This part handles the tab switching on the main index.php page
const navLinks = document.querySelectorAll('.nav-link');
const pages = document.querySelectorAll('[id^="page-"]');

function showPage(pageId) {
    pages.forEach(page => {
        page.classList.add('hidden');
    });

    const targetPage = document.getElementById(`page-${pageId}`);
    if (targetPage) {
        targetPage.classList.remove('hidden');
    }

    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.id === `nav-${pageId}`) {
            link.classList.add('active');
        }
    });

    // Special case for 'Browse Cases' and 'Top Lawyers' buttons on the home page
    if (pageId === 'cases' || pageId === 'lawyers') {
        const matchingNavLink = document.getElementById(`nav-${pageId}`);
        if (matchingNavLink) {
            matchingNavLink.classList.add('active');
        }
    }
}

// Ensure the correct page is shown on initial load
window.addEventListener('DOMContentLoaded', () => {
    // Check URL hash for direct links (e.g., index.php#page-cases)
    const urlHash = window.location.hash.replace('#page-', '');
    if (urlHash) {
        showPage(urlHash);
    } else {
        showPage('home'); // Default to home page
    }
});


// === Chatbot Logic ===
const chatbotToggler = document.getElementById('chatbot-toggler');
const chatbotPopup = document.getElementById('chatbot-popup');
const chatbotCloseBtn = document.getElementById('chatbot-close-btn');
const chatBody = document.getElementById('chat-body');
const chatInput = document.getElementById('chat-input');
const chatSendBtn = document.getElementById('chat-send-btn');
const loaderCases = document.getElementById('loader-cases');

// Toggle Chatbot Popup
chatbotToggler.addEventListener('click', () => {
    chatbotPopup.classList.toggle('hidden');
    chatbotPopup.classList.toggle('scale-0');
    chatbotPopup.classList.toggle('scale-100');
    if (!chatbotPopup.classList.contains('hidden')) {
        chatInput.focus();
    }
});

chatbotCloseBtn.addEventListener('click', () => {
    chatbotPopup.classList.add('hidden');
    chatbotPopup.classList.remove('scale-100');
    chatbotPopup.classList.add('scale-0');
});

// Function to add a message to the chat body
function addMessage(text, isUser) {
    const messageContainer = document.createElement('div');
    messageContainer.className = `flex items-start ${isUser ? 'justify-end' : ''}`;
    
    // Add avatar for bot messages
    if (!isUser) {
        const botAvatar = document.createElement('img');
        botAvatar.src = 'vakillogo.png';
        botAvatar.alt = 'Bot Avatar';
        botAvatar.className = 'w-8 h-8 rounded-full flex-shrink-0';
        messageContainer.appendChild(botAvatar);
    }

    const messageBubble = document.createElement('div');
    messageBubble.className = `p-3 rounded-xl max-w-[80%] ${isUser ? 'bg-blue-500 text-white rounded-br-none ml-3' : 'bg-gray-200 text-gray-800 rounded-tl-none mr-3'}`;
    messageBubble.textContent = text;
    messageContainer.appendChild(messageBubble);

    chatBody.appendChild(messageContainer);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Handle sending messages
async function handleChatSend() {
    const userMessage = chatInput.value.trim();
    if (userMessage === '') return;

    // Add user message to chat UI
    addMessage(userMessage, true);
    chatInput.value = '';

    // Show a "typing" indicator or loader
    const loadingMessage = document.createElement('div');
    loadingMessage.className = 'flex items-start';
    loadingMessage.innerHTML = `
        <img src="vakillogo.png" alt="Bot Avatar" class="w-8 h-8 rounded-full flex-shrink-0" />
        <div class="ml-3 bg-gray-200 p-3 rounded-xl rounded-tl-none max-w-[80%]">
            <div class="dot-flashing"></div>
        </div>
    `;
    chatBody.appendChild(loadingMessage);
    chatBody.scrollTop = chatBody.scrollHeight;

    try {
        const response = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: userMessage })
        });

        const data = await response.json();
        
        // Remove loading indicator
        chatBody.removeChild(loadingMessage);

        if (data.success) {
            addMessage(data.response, false);
        } else {
            addMessage("Sorry, I encountered an error. Please try again later.", false);
            console.error("Chatbot API Error:", data.error);
        }

    } catch (error) {
        // Remove loading indicator on error
        chatBody.removeChild(loadingMessage);
        addMessage("Sorry, I'm having trouble connecting right now. Please try again.", false);
        console.error("Fetch Error:", error);
    }
}

chatSendBtn.addEventListener('click', handleChatSend);
chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        handleChatSend();
    }
});

// === AI Case Suggestion Logic (for 'Browse Cases' page) ===
const suggestSpecialtyBtn = document.getElementById('suggest-specialty-btn');
const summarizeCaseBtn = document.getElementById('summarize-case-btn');
const caseDescriptionInput = document.getElementById('case-description');
const geminiOutputContainer = document.getElementById('gemini-output-container');
const summaryOutput = document.getElementById('summary-output');
const geminiError = document.getElementById('gemini-error');
const copySummaryBtn = document.getElementById('copy-summary-btn');

async function sendCaseRequest(requestType) {
    const description = caseDescriptionInput.value.trim();
    if (description === '') {
        alert("Please enter a case description.");
        return;
    }

    loaderCases.classList.remove('hidden');
    geminiOutputContainer.classList.add('hidden');
    geminiError.classList.add('hidden');

    try {
        const response = await fetch('gemini_api.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify({ 
                description: description, 
                request_type: requestType 
            })
        });

        const data = await response.json();
        loaderCases.classList.add('hidden');

        if (data.success) {
            summaryOutput.textContent = data.response;
            geminiOutputContainer.classList.remove('hidden');
        } else {
            geminiError.textContent = "An error occurred: " + data.error;
            geminiError.classList.remove('hidden');
        }

    } catch (error) {
        loaderCases.classList.add('hidden');
        geminiError.textContent = "An error occurred while communicating with the AI. Please try again.";
        geminiError.classList.remove('hidden');
        console.error("Fetch Error:", error);
    }
}

suggestSpecialtyBtn.addEventListener('click', () => sendCaseRequest('suggest_specialty'));
summarizeCaseBtn.addEventListener('click', () => sendCaseRequest('summarize_case'));

copySummaryBtn.addEventListener('click', () => {
    const textToCopy = summaryOutput.textContent;
    navigator.clipboard.writeText(textToCopy).then(() => {
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy text: ', err);
    });
});