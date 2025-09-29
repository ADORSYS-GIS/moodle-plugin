// Chat widget functionality - Logic only
class OpenAIChatWidget {
    constructor() {
        this.isOpen = false;
        this.init();
        this.showWelcomeMessage();
    }

    init() {
        this.createWidget();
        this.attachEventListeners();
    }

    showWelcomeMessage() {
        setTimeout(() => {
            if (!this.isOpen) {
                this.showWelcomePopup();
            }
        }, 2000);
    }

    showWelcomePopup() {
        const popup = document.createElement('div');
        popup.id = 'openai-welcome-popup';
        popup.innerHTML = `
            <div class="openai-welcome-content">
                <div class="openai-welcome-header">
                    <span class="openai-welcome-icon">🤖</span>
                    <h4>Hey there! 👋</h4>
                </div>
                <p>I'm your AI assistant! I can help you with:</p>
                <ul>
                    <li>💬 Answering questions</li>
                    <li>📄 Summarizing content</li>
                    <li>🔍 Analyzing educational materials</li>
                </ul>
                <div class="openai-welcome-buttons">
                    <button onclick="chatWidget.openModalFromWelcome()" class="openai-welcome-btn-primary">Let's Chat!</button>
                    <button onclick="chatWidget.closeWelcomePopup()" class="openai-welcome-btn-secondary">Maybe Later</button>
                </div>
            </div>
        `;
        document.body.appendChild(popup);
        
        setTimeout(() => {
            this.closeWelcomePopup();
        }, 10000);
    }

    closeWelcomePopup() {
        const popup = document.getElementById('openai-welcome-popup');
        if (popup) {
            popup.remove();
        }
    }

    openModalFromWelcome() {
        this.closeWelcomePopup();
        this.openModal();
    }

    createWidget() {
        const button = document.createElement('div');
        button.id = 'openai-chat-button';
        button.innerHTML = '🤖';
        button.title = 'Chat with AI Assistant';
        document.body.appendChild(button);

        const modal = document.createElement('div');
        modal.id = 'openai-chat-modal';
        modal.innerHTML = this.getModalHTML();
        document.body.appendChild(modal);
    }



    attachEventListeners() {
        document.getElementById('openai-chat-button').addEventListener('click', () => {
            this.openModal();
        });

        document.getElementById('openai-chat-modal').addEventListener('click', (e) => {
            if (e.target.id === 'openai-chat-modal') {
                this.closeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.target.id === 'openai-message' && e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
    }

    openModal() {
        document.getElementById('openai-chat-modal').style.display = 'block';
        // Prevent background scrolling while modal is open
        try { document.body.style.overflow = 'hidden'; } catch(e) {}
        document.getElementById('openai-message').focus();
        this.isOpen = true;
    }
    
    closeModal() {
        document.getElementById('openai-chat-modal').style.display = 'none';
        // Restore background scrolling
        try { document.body.style.overflow = ''; } catch(e) {}
        this.isOpen = false;
    }

    addMessage(content, type = 'ai') {
        const messagesContainer = document.getElementById('openai-chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `openai-message ${type}`;
        messageDiv.innerHTML = content.replace(/\n/g, '<br>');
        messagesContainer.appendChild(messageDiv);
        
        // Force scroll after adding message
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 5);
    }

    removeLastMessage() {
        const messages = document.getElementById('openai-chat-messages');
        if (messages.lastChild && messages.lastChild.querySelector('.openai-typing')) {
            messages.removeChild(messages.lastChild);
        }
    }

    async sendMessage() {
        const messageInput = document.getElementById('openai-message');
        const actionSelect = document.getElementById('openai-action');
        const sendBtn = document.getElementById('openai-send-btn');
        
        const message = messageInput.value.trim();
        if (!message) return;

        const action = actionSelect.value;

        this.addMessage(message, 'user');
        
        messageInput.value = '';
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';

        this.addMessage('<span class="openai-typing">AI is thinking...</span>', 'ai');

        try {
            // Use same-origin credentials so the browser sends the Moodle session cookie.
            // Include sesskey in the JSON body as a fallback for non-cookie scenarios.
            const apiUrl = M.cfg.wwwroot + '/local/openai_assistant/api.php';
            const payload = {
                action: action,
                message: message,
                sesskey: M.cfg.sesskey
            };
            console.debug('[OpenAI Widget] POST', apiUrl, payload);

            const response = await fetch(apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });

            console.debug('[OpenAI Widget] response.status:', response.status, 'ok:', response.ok);

            // Read raw response text for robust handling and logging
            const rawText = await response.text();
            console.debug('[OpenAI Widget] raw response text (truncated 2000 chars):', rawText.slice(0, 2000));

            // Remove typing indicator
            this.removeLastMessage();

            if (!response.ok) {
                // Show server returned status and raw text for debugging
                this.addMessage(`❌ Error: Server returned status ${response.status}`, 'error');
                console.error('[OpenAI Widget] Non-2xx response:', response.status, rawText);
                return;
            }

            // Try parse JSON; if it fails and the response looks like plain assistant text, show it as AI reply
            let data = null;
            try {
                data = JSON.parse(rawText);
            } catch (parseErr) {
                const trimmed = rawText.trim();
                if (trimmed.length > 0 && !trimmed.startsWith('<') && !trimmed.startsWith('{')) {
                    // Treat plain text response as AI reply
                    this.addMessage(trimmed, 'ai');
                    return;
                }
                console.error('[OpenAI Widget] JSON.parse failed:', parseErr, 'raw:', rawText);
                this.addMessage('❌ Error: Invalid JSON response from server', 'error');
                return;
            }

            console.debug('[OpenAI Widget] parsed response:', data);

            if (data && data.success) {
                this.addMessage(data.data, 'ai');
            } else {
                const errMsg = data && data.error ? data.error : 'Unknown error';
                this.addMessage(`❌ Error: ${errMsg}`, 'error');
            }

        } catch (error) {
            console.error('[OpenAI Widget] fetch exception:', error);
            // Remove typing indicator
            this.removeLastMessage();
            this.addMessage(`❌ Network Error: ${error.message}`, 'error');
        } finally {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send';
            messageInput.focus();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        window.chatWidget = new OpenAIChatWidget();
    } catch (error) {
        console.error('Failed to initialize OpenAI Widget:', error);
    }
});

