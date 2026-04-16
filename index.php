<?php
session_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajeria Estilo Instagram</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <button id="themeToggle" class="theme-toggle" type="button" aria-label="Cambiar tema">Modo oscuro</button>

    <main class="container">
        <section id="authSection" class="card auth-card hidden">
            <h1 class="brand">Mensajes<span>Gram</span></h1>
            <p class="subtitle">Conecta con tus contactos en tiempo real</p>

            <div class="tabs">
                <button class="tab-btn active" data-tab="login">Iniciar sesion</button>
                <button class="tab-btn" data-tab="register">Crear cuenta</button>
            </div>

            <form id="loginForm" class="auth-form">
                <label>Usuario o correo</label>
                <input type="text" name="identity" required>
                <label>Contrasena</label>
                <input type="password" name="password" required>
                <button type="submit" class="btn-primary">Entrar</button>
            </form>

            <form id="registerForm" class="auth-form hidden">
                <label>Nombre completo</label>
                <input type="text" name="fullname" required>
                <label>Usuario</label>
                <input type="text" name="username" required>
                <label>Correo</label>
                <input type="email" name="email" required>
                <label>Contrasena</label>
                <input type="password" name="password" required>
                <button type="submit" class="btn-primary">Crear cuenta</button>
            </form>

            <p id="authMessage" class="message"></p>
        </section>

        <section id="chatSection" class="card chat-card hidden">
            <aside class="sidebar">
                <div class="sidebar-head">
                    <div>
                        <p class="small">Conectado como</p>
                        <h2 id="currentUser">@usuario</h2>
                    </div>
                    <button id="logoutBtn" class="btn-ghost">Salir</button>
                </div>

                <h3>Usuarios</h3>
                <div id="usersList" class="users-list"></div>
            </aside>

            <section class="chat-panel">
                <header class="chat-header">
                    <h3 id="chatTitle">Selecciona una cuenta</h3>
                </header>

                <div id="messagesBox" class="messages-box"></div>

                <form id="sendForm" class="send-form">
                    <input id="messageInput" type="text" placeholder="Escribe un mensaje..." autocomplete="off" required>
                    <button type="submit" class="btn-primary">Enviar</button>
                </form>
                <p id="chatMessage" class="message"></p>
            </section>
        </section>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
