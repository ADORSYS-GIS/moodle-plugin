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

    getModalHTML() {
        return `
            <div class="openai-modal-content">
                <div class="openai-modal-header">
                    <h3>🤖 AI Assistant</h3>
                    <button class="openai-close-btn" onclick="chatWidget.closeModal()">&times;</button>
                </div>
                <div class="openai-modal-body">
                    <div id="openai-chat-messages" class="openai-chat-messages">
                        <div class="openai-message ai openai-welcome-message">
                            <div class="openai-welcome-header-small">
                                <span>🤖</span> <strong>AI Assistant</strong>
                            </div>
                            <p>Hello! I'm your AI assistant. I'm here to help you with:</p>
                            <ul>
                                <li>💬 <strong>Chat:</strong> Ask me questions about any topic</li>
                                <li>📄 <strong>Summarize:</strong> I can summarize long texts for you</li>
                                <li>🔍 <strong>Analyze:</strong> Get insights about educational content</li>
                            </ul>
                            <p><em>How can I help you today?</em></p>
                        </div>
                    </div>
                    <div class="openai-chat-input-container">
                        <select id="openai-action" class="openai-select">
                            <option value="chat">💬 Chat</option>
                            <option value="summarize">📄 Summarize</option>
                            <option value="analyze">🔍 Analyze</option>
                        </select>
                        <textarea id="openai-message" placeholder="Type your message..." rows="3"></textarea>
                        <button id="openai-send-btn" onclick="chatWidget.sendMessage()">Send</button>
                    </div>
                </div>
            </div>
        `;
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
        document.getElementById('openai-message').focus();
        this.isOpen = true;
    }

    closeModal() {
        document.getElementById('openai-chat-modal').style.display = 'none';
        this.isOpen = false;
    }

    addMessage(content, type = 'ai') {
        const messagesContainer = document.getElementById('openai-chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `openai-message ${type}`;

        if (type === 'ai') {
            // Render AI response as sanitized HTML using the lightweight renderer.
            try {
                messageDiv.innerHTML = this.renderAIResponse(content);
            } catch (e) {
                console.error('[OpenAI Widget] renderAIResponse failed:', e);
                messageDiv.innerHTML = String(content).replace(/\n/g, '<br>');
            }
        } else {
            // User messages: treat as plain text with newlines -> <br>
            messageDiv.innerHTML = String(content).replace(/\n/g, '<br>');
        }

        messagesContainer.appendChild(messageDiv);
        
        // Force scroll after adding message
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 10);
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
            const apiUrl = M.cfg.wwwroot + '/local/openai_assistant/api.php';
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    message: message,
                    sesskey: M.cfg.sesskey
                })
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response. Check API endpoint.');
            }

            const data = await response.json();
            
            this.removeLastMessage();

            if (data.success) {
                this.addMessage(data.data, 'ai');
            } else {
                this.addMessage(`❌ Error: ${data.error}`, 'error');
            }

        } catch (error) {
            this.removeLastMessage();
            console.error('Widget API Error:', error);
            this.addMessage(`❌ Error: ${error.message}`, 'error');
        } finally {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send';
            messageInput.focus();
        }
    }
}

(function() {
    function initOpenAIChatWidget() {
        if (window.chatWidget) {
            return;
        }
        try {
            window.chatWidget = new OpenAIChatWidget();
        } catch (error) {
            console.error('Failed to initialize OpenAI Widget:', error);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOpenAIChatWidget);
    } else {
        initOpenAIChatWidget();
    }
})();
