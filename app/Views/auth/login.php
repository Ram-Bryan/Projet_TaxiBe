<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TaxiBe Madagascar</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary-color: #8D6E63;
            --primary-dark: #5D4037;
            --bg-color: #F5F5F6;
            --surface-color: #FFFFFF;
            --text-main: #3E2723;
            --text-light: #795548;
            --error-color: #D32F2F;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-main);
        }

        .login-container {
            background: var(--surface-color);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(93, 64, 55, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .icon-container {
            background-color: rgba(141, 110, 99, 0.1);
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 1rem;
            color: var(--primary-color);
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-main);
        }

        .login-header p {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            width: 20px;
            height: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #D7CCC8;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
        }

        .alert {
            background-color: rgba(211, 47, 47, 0.1);
            color: var(--error-color);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <div class="icon-container">
                <i data-lucide="bus" style="width: 32px; height: 32px;"></i>
            </div>
            <h1>TaxiBe Madagascar</h1>
            <p>Connectez-vous pour accéder à votre espace</p>
        </div>

        <?php if(session()->getFlashdata('msg')):?>
            <div class="alert">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
                <?= session()->getFlashdata('msg') ?>
            </div>
        <?php endif;?>

        <form action="/login" method="post">
            <div class="form-group">
                <label for="email">Adresse Email</label>
                <div class="input-with-icon">
                    <input type="email" name="email" id="email" placeholder="admin@taxibe.mg" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-with-icon">
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Se connecter</span>
                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
            </button>

        
        </form>

        <div>
            <p>Admin: admin@taxibe.mg / admin123</p>
            <p>Simple: user@taxibe.mg / user123</p>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
