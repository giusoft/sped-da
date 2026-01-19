<?php
$bottomValue = "10rem";

if (!$_SESSION['key_user']) {
    $bottomValue = "2rem";
}
?>

<!DOCTYPE html>

<style>

    #chat-toggle {
		position: fixed;
		bottom: <?= $bottomValue ?>;
		right: 2rem;
		background: #007bff;
		color: white;
		border: none;
		border-radius: 50%;
		width: 66px;
		height: 66px;
		font-size: 24px;
		cursor: pointer;
		box-shadow: 0 4px 6px rgba(0,0,0,0.2);
		z-index: 9999999999;
    }

    #chat-container {
		position: fixed;
		bottom: 1rem;
		right: 1rem;
		width: 460px;
		max-width: 90%;
		height: 640px;
		max-height: 64%;
		background: white;
		border: 1px solid #ccc;
		border-radius: 10px;
		box-shadow: 0 4px 10px rgba(0,0,0,0.2);
		display: none;
		flex-direction: column;
		overflow: hidden;
		z-index: 9999999999;
    }

    .chat-header {
		background: #007bff;
		color: white;
		padding: 12.7px;
		font-size: 19px;
		display: flex;
		align-items: center;
        gap: 5px;
    }

    .chat-header span {
        margin-right: auto; /* Isso empurra todo o resto para a direita */
    }

    .chat-header button {
		background: none;
		border: none;
		color: black;
		font-size: 28px;
		cursor: pointer;
		border-radius: 8px;
		padding: 0 10px;
    }

    #chat-body {
		flex: 1;
		padding: 10px;
		background: #f9f9f9;
		overflow-y: auto;
		display: flex;
		flex-direction: column;
    }

    .msg {
		max-width: 75%;
		margin-bottom: 10px;
		padding: 8px 12px;
		border-radius: 15px;
		word-wrap: break-word;
    }

    .user {
		align-self: flex-end;
		background: #007bff;
		color: white;
		border-bottom-right-radius: 0;
    }

    .bot {
		align-self: flex-start;
		background: #e4e4e4;
		color: black;
		border-bottom-left-radius: 0;
    }

    .chat-footer {
		display: flex;
		border-top: 1px solid #ccc;
		padding: 8px;
    }

    #chat-input {
		flex: 1;
		padding: 8px;
		border: 1px solid #ccc;
		border-radius: 5px;
		outline: none;
    }

    #send-btn {
        color: white;
        border: none;
        margin-left: 5px;
        padding: 8px 12px;
        border-radius: 5px;
        transition: background 0.2s;
    }

    #send-btn.enabled {
        background-color: #007bff !important;
        cursor: pointer;
    }

    #send-btn.disabled {
        background-color: #75afee !important;
        cursor: not-allowed;
    }

    #menu-toggle {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
    }

    #menu-dropdown {
        display: none;
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 10000;
        width: 250px;
    }

    #menu-dropdown button {
        display: block;
        width: 100%;
        padding: 10px 15px;
        text-align: center;
        color: black;
        font-size: 16px;
        background-color: white;
    }

    #menu-dropdown button:hover {
        background-color: #f0f0f0;
    }

    #menu-container {
        position: relative;
    }

    @media print {
    #chat-toggle,
    #chat-container {
        display: none !important;
    }
}

</style>

<button id="chat-toggle">
	<i class="fas fa-robot"></i>
</button>

<div id="chat-container">
    <div class="chat-header">
        <span>ChatBot - gWMS</span>
        <div id="menu-container">
            <button id="menu-toggle">&#9776;</button>
            <div id="menu-dropdown" style="display: none; position: absolute; right: 0; top: 36px; background: white; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                <button id="query-mode-menu-btn">Modo de Consultas</button>
            </div>
        </div>

        <button id="close-chat">&times;</button>
    </div>

	<div id="chat-body">
	</div>
	<div class="chat-footer">
		<input type="text" id="chat-input" placeholder="Digite uma mensagem...">
		<button id="send-btn" class="enabled">Enviar</button>
	</div>
</div>

<script>
    const chatToggle = document.getElementById('chat-toggle');
    const chatContainer = document.getElementById('chat-container');
    const closeChat = document.getElementById('close-chat');
    const chatBody = document.getElementById('chat-body');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-btn');
    const menuToggle = document.getElementById('menu-toggle');
    const menuDropdown = document.getElementById('menu-dropdown');
    const queryModeMenuBtn = document.getElementById('query-mode-menu-btn');
    let isQueryModeActive = false;

    menuToggle.addEventListener('click', () => {
        menuDropdown.style.display = menuDropdown.style.display === 'block' ? 'none' : 'block';
    });

    queryModeMenuBtn.addEventListener('click', () => {
        isQueryModeActive = !isQueryModeActive;
        if (isQueryModeActive) {
            queryModeMenuBtn.textContent = 'Modo de Consultas Ativo';
            queryModeMenuBtn.style.backgroundColor = 'rgb(8, 208, 54)';
            queryModeMenuBtn.style.color = 'white';
            addMessage("Modo de consultas ativado.", 'bot');
        } else {
            queryModeMenuBtn.textContent = 'Modo de Consultas';
            queryModeMenuBtn.style.backgroundColor = 'transparent';
            queryModeMenuBtn.style.color = 'black';
            addMessage("Modo de consultas desativado.", 'bot');
        }
        menuDropdown.style.display = 'none';
    });

    function mdToHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/^\s*[-+*]\s?(.*)$/gm, '- $1')
            .replace(/^\s*(\d+)\.\s?(.*)$/gm, '$1. $2')
            .replace(/\[(.*?)\]\((https?:\/\/[^\s)]+)\)/g,
                     '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>')
            .replace(/\*\*\s*(.*?)\s*\*\*/g, '<b>$1</b>')
            .replace(/__\s*(.*?)\s*__/g, '<u>$1</u>')
            .replace(/(^|[^*])\*(?!\s)([^*\n]+?)\*(?!\*)/gm, '$1<i>$2</i>')
            .replace(/\n/g, '<br>');
    }

    chatToggle.addEventListener('click', () => {
        chatContainer.style.display = 'flex';
        chatToggle.style.display = 'none';
        chatInput.focus();
    });

    closeChat.addEventListener('click', () => {
        chatContainer.style.display = 'none';
        chatToggle.style.display = 'block';
    });

    function addMessage(text, sender) {
        const msg = document.createElement('div');
        msg.classList.add('msg', sender);
        msg.innerHTML = mdToHtml(text);
        chatBody.appendChild(msg);
        chatBody.scrollTop = chatBody.scrollHeight;
        saveMessages();
    }

    function saveMessages() {
        const messages = [];
        chatBody.querySelectorAll('.msg').forEach(msg => {
            messages.push({
                text: msg.innerHTML,
                sender: msg.classList.contains('user') ? 'user' : 'bot'
            });
        });
        localStorage.setItem('chatMessages', JSON.stringify(messages));
    }

    function loadMessages() {
        const messages = JSON.parse(localStorage.getItem('chatMessages') || '[]');

        if (messages.length === 0) {
            addMessage("Bem-vindo ao Chatbot da GiuSoft! Sou seu assistente virtual.", 'bot');
            addMessage("Posso tirar dúvidas sobre o sistema WMS. Digite sua pergunta abaixo e responderei em instantes!", 'bot');
        } else {
            messages.forEach(m => {
                const el = document.createElement('div');
                el.className = `msg ${m.sender}`;
                el.innerHTML = m.text;
                chatBody.appendChild(el);
            });
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }

    let isWaiting = false;

    function updateSendButton() {
        chatInput.disabled = isWaiting;

        sendBtn.classList.remove('enabled','disabled');

        if (isWaiting) {
            sendBtn.classList.add('disabled');
            sendBtn.disabled = true;
		        chatInput.disabled = false;
        } else {
            sendBtn.classList.add('enabled');
            sendBtn.disabled = false;
            chatInput.disabled = false;
        }
    }

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message || isWaiting) return;

        isWaiting = true;
        updateSendButton();

        addMessage(message, 'user');
        chatInput.value = '';

        const thinkingMsg = document.createElement('div');
        thinkingMsg.classList.add('msg', 'bot');
        thinkingMsg.textContent = 'Pensando...';
        chatBody.appendChild(thinkingMsg);
        chatBody.scrollTop = chatBody.scrollHeight;

        try {
            let apiUrl;

            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                apiUrl = 'http://127.0.0.1:5000/chat';
            } else {
                apiUrl = 'https://alexa.giusoft.com.br:8443/chat';
            }

            const requestBody = {
                message: message,
                queryMode: isQueryModeActive
            };

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });
            const data = await response.json();

            thinkingMsg.innerHTML = mdToHtml(data.response);
            saveMessages();
        } catch (error) {
            thinkingMsg.textContent = 'Erro ao se conectar ao servidor.';
        } finally {
            isWaiting = false;
            updateSendButton();
            chatInput.focus();
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    updateSendButton();

    window.addEventListener('load', loadMessages);
</script>
