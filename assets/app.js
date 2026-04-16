const authSection = document.getElementById('authSection');
const chatSection = document.getElementById('chatSection');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const authMessage = document.getElementById('authMessage');
const usersList = document.getElementById('usersList');
const currentUserText = document.getElementById('currentUser');
const chatTitle = document.getElementById('chatTitle');
const messagesBox = document.getElementById('messagesBox');
const sendForm = document.getElementById('sendForm');
const messageInput = document.getElementById('messageInput');
const chatMessage = document.getElementById('chatMessage');
const logoutBtn = document.getElementById('logoutBtn');
const themeToggle = document.getElementById('themeToggle');

let currentUser = null;
let users = [];
let selectedUserId = null;
let pollTimer = null;

const tabs = document.querySelectorAll('.tab-btn');

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    themeToggle.textContent = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
}

function initTheme() {
    const stored = localStorage.getItem('theme');
    const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = stored || (systemDark ? 'dark' : 'light');
    applyTheme(theme);
}

themeToggle.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', next);
    applyTheme(next);
});

tabs.forEach((btn) => {
    btn.addEventListener('click', () => {
        tabs.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');

        const tab = btn.dataset.tab;
        loginForm.classList.toggle('hidden', tab !== 'login');
        registerForm.classList.toggle('hidden', tab !== 'register');
        authMessage.textContent = '';
    });
});

function showAuth(message = '') {
    authSection.classList.remove('hidden');
    chatSection.classList.add('hidden');
    authMessage.textContent = message;

    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function showChat() {
    authSection.classList.add('hidden');
    chatSection.classList.remove('hidden');
}

async function request(url, options = {}) {
    const response = await fetch(url, options);
    const raw = await response.text();
    let data = null;

    try {
        data = raw ? JSON.parse(raw) : {};
    } catch (err) {
        throw new Error('La respuesta del servidor no es JSON valido. Revisa errores de PHP/BD.');
    }

    if (!response.ok) {
        throw new Error(data.message || 'Error en la solicitud');
    }
    return data;
}

function renderUsers() {
    usersList.innerHTML = '';

    if (users.length === 0) {
        usersList.innerHTML = '<p class="message">Aun no hay otras cuentas registradas.</p>';
        return;
    }

    users.forEach((user) => {
        const item = document.createElement('div');
        item.className = 'user-item';
        if (selectedUserId === user.id) item.classList.add('active');

        item.innerHTML = `
            <p class="username">@${user.username}</p>
            <p class="fullname">${user.fullname}</p>
        `;

        item.addEventListener('click', () => {
            selectedUserId = user.id;
            chatTitle.textContent = `Conversacion con @${user.username}`;
            chatMessage.textContent = '';
            renderUsers();
            loadMessages();
        });

        usersList.appendChild(item);
    });
}

function renderMessages(messages) {
    messagesBox.innerHTML = '';

    if (messages.length === 0) {
        messagesBox.innerHTML = '<p class="message">No hay mensajes todavia. Inicia la conversacion.</p>';
        return;
    }

    messages.forEach((msg) => {
        const bubble = document.createElement('div');
        bubble.className = `msg ${msg.sender_id === currentUser.id ? 'mine' : 'other'}`;
        bubble.textContent = msg.content;
        messagesBox.appendChild(bubble);
    });

    messagesBox.scrollTop = messagesBox.scrollHeight;
}

async function loadSession() {
    try {
        const data = await request('api/session.php');
        if (!data.authenticated) {
            showAuth();
            return;
        }

        currentUser = data.user;
        users = data.users;
        currentUserText.textContent = `@${currentUser.username}`;
        renderUsers();
        showChat();

        if (!selectedUserId && users.length > 0) {
            selectedUserId = users[0].id;
            chatTitle.textContent = `Conversacion con @${users[0].username}`;
            renderUsers();
        }

        await loadMessages();
        if (!pollTimer) {
            pollTimer = setInterval(loadMessages, 3000);
        }
    } catch (err) {
        showAuth(err.message);
    }
}

async function loadMessages() {
    if (!selectedUserId) {
        messagesBox.innerHTML = '<p class="message">Selecciona un usuario para empezar.</p>';
        return;
    }

    try {
        const data = await request(`api/messages.php?user_id=${selectedUserId}`);
        renderMessages(data.messages);
    } catch (err) {
        chatMessage.textContent = err.message;
    }
}

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    authMessage.textContent = '';

    const formData = new FormData(loginForm);

    try {
        await request('api/login.php', {
            method: 'POST',
            body: formData
        });
        loginForm.reset();
        await loadSession();
    } catch (err) {
        authMessage.textContent = err.message;
    }
});

registerForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    authMessage.textContent = '';

    const formData = new FormData(registerForm);

    try {
        await request('api/register.php', {
            method: 'POST',
            body: formData
        });
        registerForm.reset();
        await loadSession();
    } catch (err) {
        authMessage.textContent = err.message;
    }
});

sendForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    chatMessage.textContent = '';

    if (!selectedUserId) {
        chatMessage.textContent = 'Debes elegir una cuenta existente.';
        return;
    }

    const content = messageInput.value.trim();
    if (!content) return;

    const formData = new FormData();
    formData.append('user_id', selectedUserId);
    formData.append('content', content);

    try {
        await request('api/messages.php', {
            method: 'POST',
            body: formData
        });
        messageInput.value = '';
        await loadMessages();
    } catch (err) {
        chatMessage.textContent = err.message;
    }
});

logoutBtn.addEventListener('click', async () => {
    try {
        await request('api/logout.php', { method: 'POST' });
    } catch (err) {
        // no-op
    }

    selectedUserId = null;
    users = [];
    currentUser = null;
    chatTitle.textContent = 'Selecciona una cuenta';
    messagesBox.innerHTML = '';
    showAuth('Sesion cerrada correctamente.');
});

loadSession();
initTheme();
