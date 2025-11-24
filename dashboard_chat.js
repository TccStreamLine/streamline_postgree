document.addEventListener('DOMContentLoaded', () => {
    const chatLauncher = document.getElementById('chat-launcher');
    const chatContainer = document.getElementById('chat-container');
    const closeChatBtn = document.getElementById('close-chat');
    const chatForm = document.getElementById('ai-chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');
    const typingIndicator = document.getElementById('typing-indicator');
    const submitButton = chatForm.querySelector('button[type="submit"]'); // Seleciona o botão

    chatLauncher.addEventListener('click', () => {
        chatContainer.classList.toggle('hidden');
    });

    closeChatBtn.addEventListener('click', () => {
        chatContainer.classList.add('hidden');
    });

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userMessage = chatInput.value.trim();
        if (!userMessage) return;

        submitButton.disabled = true; // Desabilita o botão aqui
        addMessageToChat(userMessage, 'user');
        chatInput.value = '';
        typingIndicator.classList.remove('hidden');

        try {
            const response = await fetch('chat_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ pergunta: userMessage })
            });

            if (!response.ok) {
                 const errorText = await response.text(); // Tenta ler a resposta de erro
                 console.error('Raw error response:', errorText); 
                 throw new Error(`Erro ${response.status}: ${errorText || 'Erro na comunicação com o servidor.'}`);
            }

            const data = await response.json();
            
            if (data.resposta) {
                 addMessageToChat(data.resposta, 'ai');
            } else {
                 throw new Error('Resposta da IA inválida ou vazia.');
            }

        } catch (error) {
            console.error('Erro no fetch:', error);
             // Tenta extrair a mensagem de erro específica, se disponível
             const errorMessage = error.message.includes('{') ? error.message : 'Desculpe, ocorreu um erro ao processar sua pergunta. Verifique o console para detalhes.';
            addMessageToChat(errorMessage, 'ai');
        } finally {
            typingIndicator.classList.add('hidden');
            submitButton.disabled = false; // Reabilita o botão aqui, no finally
        }
    });

    function addMessageToChat(text, sender) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message', sender);
        messageElement.textContent = text;
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});