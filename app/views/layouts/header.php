<?php
// Header simplificado para views específicas
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container">
        <a class="navbar-brand" href="/">Carteira Manager</a>
        <div class="navbar-nav ms-auto">
            <?php if (Auth::isLoggedIn()): ?>
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a href="/logout" class="btn btn-sm btn-outline-secondary">Sair</a>
            <?php endif; ?>
        </div>
    </div>
</nav>