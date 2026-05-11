const authSection = document.getElementById('authSection');
const chatSection = document.getElementById('chatSection');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const authMessage = document.getElementById('authMessage');
const usersList = document.getElementById('usersList');
const currentUserText = document.getElementById('currentUser');
const chatTitle = document.getElementById('chatTitle');
const peerAvatar = document.getElementById('peerAvatar');
const messagesBox = document.getElementById('messagesBox');
const sendForm = document.getElementById('sendForm');
const messageInput = document.getElementById('messageInput');
const chatMessage = document.getElementById('chatMessage');
const logoutBtn = document.getElementById('logoutBtn');
const themeToggle = document.getElementById('themeToggle');
const currentAvatar = document.getElementById('currentAvatar');
const currentAvatarCard = document.getElementById('currentAvatarCard');
const profilePhotoInput = document.getElementById('profilePhotoInput');
const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
const emojiBtn = document.getElementById('emojiBtn');
const emojiPanel = document.getElementById('emojiPanel');

function ensureAvatarPreviewSlot() {
    if (!currentAvatarCard) return;
    if (!currentAvatarCard.innerHTML || !currentAvatarCard.innerHTML.trim()) {
        currentAvatarCard.innerHTML = '<span class="avatar-placeholder"></span>';
    }
}

// Estado global de la UI
let currentUser = null;
let users = [];
let selectedUserId = null;
let lastMessageId = 0;

const tabs = document.querySelectorAll('.tab-btn');
const emojiSet = [
    '\uD83D\uDE00', '\uD83D\uDE02', '\uD83D\uDE0D', '\uD83D\uDE0E',
    '\uD83D\uDE09', '\uD83D\uDE0A', '\uD83D\uDE2D', '\uD83D\uDE21',
    '\uD83E\uDD14', '\uD83D\uDE4C', '\uD83D\uDD25', '\u2764\uFE0F',
    '\uD83D\uDC4D', '\uD83D\uDC40', '\uD83C\uDF89', '\uD83D\uDE4F'
];

function insertEmojiAtCursor(emoji) {
    const start = typeof messageInput.selectionStart === 'number' ? messageInput.selectionStart : messageInput.value.length;
    const end = typeof messageInput.selectionEnd === 'number' ? messageInput.selectionEnd : messageInput.value.length;
    const before = messageInput.value.slice(0, start);
    const after = messageInput.value.slice(end);
    messageInput.value = `${before}${emoji}${after}`;
    const pos = start + emoji.length;
    messageInput.focus();
    messageInput.setSelectionRange(pos, pos);
}

function toggleEmojiPanel(forceOpen = null) {
    if (!emojiPanel) return;
    const shouldOpen = forceOpen === null ? emojiPanel.classList.contains('hidden') : forceOpen;
    emojiPanel.classList.toggle('hidden', !shouldOpen);
    if (emojiBtn) {
        emojiBtn.setAttribute('aria-expanded', String(shouldOpen));
    }
}

// Fallback global para onclick en HTML (evita fallos por listeners perdidos/caché)
window.__toggleEmoji = () => {
    if (!emojiPanel) return;
    if (!emojiPanel.children.length) {
        initEmojiPicker();
    }
    toggleEmojiPanel();
    messageInput.focus();
};

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    themeToggle.textContent = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
}

// Carga tema guardado o preferencia del sistema
function initTheme() {
    const stored = localStorage.getItem('theme');
    const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = stored || (systemDark ? 'dark' : 'light');
    applyTheme(theme);
}

if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', next);
        applyTheme(next);
    });
}

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

if (profilePhotoInput) {
    profilePhotoInput.addEventListener('change', async () => {
        chatMessage.textContent = '';
        const file = profilePhotoInput.files[0];
        if (!file) return;

        // Vista previa inmediata antes de subir
        const previewUrl = URL.createObjectURL(file);
        currentAvatar.innerHTML = `<img class="avatar-core" src="${previewUrl}" alt="Vista previa">`;
        if (currentAvatarCard) {
            currentAvatarCard.innerHTML = `<img src="${previewUrl}" alt="Vista previa">`;
        }
        chatMessage.textContent = 'Subiendo foto...';

        const formData = new FormData();
        formData.append('photo', file);

        try {
            const data = await request('api/profile_photo.php', {
                method: 'POST',
                body: formData
            });

            // Actualiza en memoria para reflejar la foto al instante sin esperar recarga completa.
            if (currentUser && data.photo_url) {
                currentUser.photo_url = data.photo_url;
                renderCurrentAvatar();
                renderUsers();
            }

            await loadSession();
            chatMessage.textContent = 'Foto de perfil actualizada.';
        } catch (err) {
            chatMessage.textContent = err.message;
        } finally {
            profilePhotoInput.value = '';
            setTimeout(() => URL.revokeObjectURL(previewUrl), 2000);
        }
    });
}

if (uploadPhotoBtn && profilePhotoInput) {
    uploadPhotoBtn.addEventListener('click', () => profilePhotoInput.click());
}

function showAuth(message = '') {
    authSection.classList.remove('hidden');
    chatSection.classList.add('hidden');
    authMessage.textContent = message;
}

function showChat() {
    authSection.classList.add('hidden');
    chatSection.classList.remove('hidden');
    ensureAvatarPreviewSlot();
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

// Render lateral de usuarios disponibles para conversar
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

        const avatar = renderAvatar(user);
        item.innerHTML = `
            ${avatar}
            <div>
            <p class="username">@${user.username}</p>
            <p class="fullname">${user.fullname}</p>
            </div>
        `;

        item.addEventListener('click', () => {
            selectedUserId = user.id;
            lastMessageId = 0;
            chatTitle.textContent = `Conversacion con @${user.username}`;
            renderPeerAvatar(user);
            chatMessage.textContent = '';
            renderUsers();
            loadMessages();
        });

        usersList.appendChild(item);
    });
}

function renderAvatar(user) {
    if (user.photo_url) {
        return `<div class="avatar"><img class="avatar-core" src="${user.photo_url}" alt="Foto de @${user.username}"></div>`;
    }

    return '<div class="avatar"><span class="avatar-core placeholder"></span></div>';
}

function renderCurrentAvatar() {
    if (!currentUser) return;
    const photoUrl = currentUser.photo_url ? `${currentUser.photo_url}${currentUser.photo_url.includes('?') ? '&' : '?'}t=${Date.now()}` : null;
    if (photoUrl) {
        currentAvatar.innerHTML = `<img class="avatar-core" src="${photoUrl}" alt="Foto de @${currentUser.username}">`;
        if (currentAvatarCard) {
            currentAvatarCard.innerHTML = `<img src="${photoUrl}" alt="Foto de perfil de @${currentUser.username}">`;
        }
        return;
    }

    currentAvatar.innerHTML = '<span class="avatar-core placeholder"></span>';
    if (currentAvatarCard) {
        currentAvatarCard.innerHTML = '<span class="avatar-placeholder"></span>';
    }
}

function renderPeerAvatar(user) {
    if (!user) {
        peerAvatar.innerHTML = '<span class="avatar-core placeholder"></span>';
        return;
    }

    if (user.photo_url) {
        peerAvatar.innerHTML = `<img class="avatar-core" src="${user.photo_url}" alt="Foto de @${user.username}">`;
        return;
    }

    peerAvatar.innerHTML = '<span class="avatar-core placeholder"></span>';
}

// Inserta emojis en la posicion del cursor dentro del input
function initEmojiPicker() {
    emojiPanel.innerHTML = '';
    emojiSet.forEach((emoji) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'emoji-item';
        btn.textContent = emoji;
        btn.setAttribute('data-emoji', emoji);
        emojiPanel.appendChild(btn);
    });
}

// Dibuja mensajes de la conversacion activa
function renderMessages(messages, append = false) {
    if (!append) {
        messagesBox.innerHTML = '';
    }

    if (!append && messages.length === 0) {
        messagesBox.innerHTML = '<p class="message">No hay mensajes todavia. Inicia la conversacion.</p>';
        return;
    }

    messages.forEach((msg) => {
        const editButton = msg.can_edit
            ? `<button class="edit-msg-btn" data-message-id="${msg.id}" data-current-content="${encodeURIComponent(msg.content)}">Editar</button>`
            : '';
        const item = document.createElement('div');
        item.className = `msg-wrap ${msg.sender_id === currentUser.id ? 'mine' : 'other'}`;
        item.innerHTML = `
            <div class="msg ${msg.sender_id === currentUser.id ? 'mine' : 'other'}">${msg.content}</div>
            ${editButton}
        `;
        messagesBox.appendChild(item);
    });

    messagesBox.querySelectorAll('.edit-msg-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const currentContent = decodeURIComponent(btn.dataset.currentContent || '');
            const nextContent = window.prompt('Editar mensaje (solo 10 segundos):', currentContent);
            if (nextContent === null) return;
            const cleaned = nextContent.trim();
            if (!cleaned) return;

            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('message_id', btn.dataset.messageId);
            formData.append('content', cleaned);

            try {
                await request('api/messages.php', { method: 'POST', body: formData });
                lastMessageId = 0;
                await loadMessages();
            } catch (err) {
                chatMessage.textContent = err.message;
            }
        });
    });

    if (!append || messages.length > 0) {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }
}

// Consulta sesion actual para decidir entre login o chat
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
        renderCurrentAvatar();
        renderUsers();
        showChat();

        if (!selectedUserId && users.length > 0) {
            selectedUserId = users[0].id;
            lastMessageId = 0;
            chatTitle.textContent = `Conversacion con @${users[0].username}`;
            renderPeerAvatar(users[0]);
            renderUsers();
        }

        if (selectedUserId) {
            const selectedUser = users.find((u) => u.id === selectedUserId);
            if (selectedUser) {
                chatTitle.textContent = `Conversacion con @${selectedUser.username}`;
                renderPeerAvatar(selectedUser);
            }
        }

        if (!users.length) {
            renderPeerAvatar(null);
        }

        await loadMessages();
    } catch (err) {
        showAuth(err.message);
    }
}

// Carga mensajes entre usuario actual y destinatario seleccionado
async function loadMessages() {
    if (!selectedUserId) {
        messagesBox.innerHTML = '<p class="message">Selecciona un usuario para empezar.</p>';
        return;
    }

    try {
        const url = lastMessageId > 0 ? `api/messages.php?user_id=${selectedUserId}&last_id=${lastMessageId}` : `api/messages.php?user_id=${selectedUserId}`;
        const data = await request(url);
        renderMessages(data.messages, lastMessageId > 0);
        if (data.messages.length > 0) {
            lastMessageId = Math.max(...data.messages.map(m => m.id));
        }
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

if (emojiBtn && emojiPanel) {
    emojiBtn.addEventListener('click', (event) => {
        event.preventDefault();
        if (!emojiPanel.children.length) {
            initEmojiPicker();
        }
        toggleEmojiPanel();
        messageInput.focus();
    });

    emojiPanel.addEventListener('click', (event) => {
        const target = event.target.closest('.emoji-item');
        if (!target) return;
        const emoji = target.getAttribute('data-emoji') || target.textContent || '';
        if (!emoji) return;
        insertEmojiAtCursor(emoji);
        toggleEmojiPanel(false);
    });

    document.addEventListener('click', (event) => {
        if (emojiPanel.classList.contains('hidden')) return;
        const clickedInside = emojiPanel.contains(event.target) || emojiBtn.contains(event.target);
        if (!clickedInside) {
            toggleEmojiPanel(false);
        }
    });
}

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
initEmojiPicker();
ensureAvatarPreviewSlot();
