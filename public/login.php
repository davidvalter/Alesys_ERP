    <?php
    //public/login.php

    session_start();
    $erro = $_SESSION['login_erro'] ?? '';
    unset($_SESSION['login_erro']); // Limpa a mensagem após exibir
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Alecrim Restaurante</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            body {
                background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                z-index: -1;
            }

            .login-container {
                position: relative;
                display: flex;
                width: 800px;
                height: 500px;
                background-color: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            }

            /* Estilos para a mensagem de erro */
            .error-message-container {
                position: absolute;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                z-index: 10;
                animation: slideIn 0.5s ease-out;
            }

            @keyframes slideIn {
                from { top: -50px; opacity: 0; }
                to { top: 20px; opacity: 1; }
            }

            .alert {
                padding: 15px 20px;
                border-radius: 8px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }

            .alert-danger {
                color: #721c24;
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
            }

            .close-btn {
                cursor: pointer;
                font-weight: bold;
                font-size: 1.2em;
                margin-left: 15px;
                color: inherit;
            }

            .login-form {
                flex: 1;
                padding: 50px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .login-image {
                flex: 1;
                background-image: url('https://images.unsplash.com/photo-1606787366850-de6330128bfc?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
                background-size: cover;
                background-position: center;
            }

            .logo {
                text-align: center;
                margin-bottom: 30px;
            }

            .logo h1 {
                color: #556B2F;
                font-size: 28px;
                font-weight: 700;
                letter-spacing: 1px;
            }

            .logo p {
                color: #8F9779;
                font-size: 14px;
                margin-top: 5px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #556B2F;
                font-size: 14px;
                font-weight: 500;
            }

            .form-group input {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #D2B48C;
                border-radius: 5px;
                font-size: 14px;
                transition: all 0.3s;
                background-color: #FAF8F5;
            }

            .form-group input:focus {
                border-color: #8F9779;
                outline: none;
                box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.1);
            }

            .login-button {
                width: 100%;
                padding: 12px;
                background-color: #556B2F;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                margin-top: 10px;
            }

            .login-button:hover {
                background-color: #6B8E23;
                transform: translateY(-2px);
            }

            .forgot-password {
                text-align: center;
                margin-top: 20px;
            }

            .forgot-password a {
                color: #8F9779;
                text-decoration: none;
                font-size: 13px;
                transition: color 0.3s;
            }

            .forgot-password a:hover {
                color: #556B2F;
                text-decoration: underline;
            }

            @media (max-width: 768px) {
                .login-container {
                    flex-direction: column;
                    width: 90%;
                    height: auto;
                }

                .login-image {
                    height: 200px;
                }

                .login-form {
                    padding: 30px;
                }
                
                .error-message-container {
                    width: 85%;
                }
            }
        </style>
    </head>

    <body>
        <div class="login-container">
            <!-- Mensagem de erro no topo (fora do formulário) -->
            <?php if (!empty($erro)): ?>
                <div class="error-message-container">
                    <div class="alert alert-danger" id="error-message">
                        <span><?= htmlspecialchars($erro) ?></span>
                        <span class="close-btn">&times;</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="login-form">
                <div class="logo">
                    <h1>Alecrim Restaurante</h1>
                    <p>Sistema administrativo</p>
                </div>
                <form action="conf/methodLogin.php" method="post">
                    <div class="form-group">
                        <label for="username">Login</label>
                        <input type="text" id="Username" name="Username" placeholder="Digite seu usuário" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Senha</label>
                        <input type="Password" id="Password" name="Password" placeholder="Digite sua senha" required>
                    </div>
                    <button type="submit" class="login-button">Entrar</button>
                    <div class="forgot-password">
                        <a href="#">Esqueceu sua senha?</a>
                    </div>
                </form>
            </div>
            <div class="login-image"></div>
        </div>

        <script>
            // Fecha a mensagem ao clicar no X
            document.addEventListener('DOMContentLoaded', function () {
                const errorMessage = document.getElementById('error-message');
                if (errorMessage) {
                    const closeBtn = errorMessage.querySelector('.close-btn');
                    
                    closeBtn.addEventListener('click', function () {
                        errorMessage.style.opacity = '0';
                        setTimeout(() => {
                            errorMessage.style.display = 'none';
                        }, 300);
                    });

                    // Fecha automaticamente após 5 segundos
                    setTimeout(function () {
                        errorMessage.style.opacity = '0';
                        setTimeout(() => {
                            errorMessage.style.display = 'none';
                        }, 300);
                    }, 5000);
                }
            });
        </script>
    </body>
    </html>