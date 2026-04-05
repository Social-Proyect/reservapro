<header class="topbar">
    <div class="topbar-left">
        <button class="btn-menu" onclick="toggleSidebar()">☰</button>
        <h1 class="page-title-mobile">ReservaPro</h1>
    </div>
    
    <div class="topbar-right">
        <div class="topbar-item">
            <span class="icon">📅</span>
            <span><?= date('d/m/Y') ?></span>
        </div>
        
        <div class="topbar-item">
            <span class="icon">🕐</span>
            <span id="current-time"></span>
        </div>
        
        <div class="user-menu">
            <button class="user-menu-btn" onclick="toggleUserMenu()">
                <span class="icon">👤</span>
                <span><?= $_SESSION['admin_nombre'] ?? 'Admin' ?></span>
            </button>
            <div class="user-menu-dropdown" id="user-menu">
                <a href="../index.php" target="_blank">Ver Sitio Público</a>
                <a href="mi-perfil.php">Mi Perfil</a>
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</header>

<script>
// Actualizar reloj
function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('current-time').textContent = `${hours}:${minutes}`;
}
updateTime();
setInterval(updateTime, 60000);

// Toggle menú de usuario
function toggleUserMenu() {
    document.getElementById('user-menu').classList.toggle('show');
}

// Cerrar menú al hacer clic fuera
window.onclick = function(event) {
    if (!event.target.matches('.user-menu-btn') && !event.target.closest('.user-menu-btn')) {
        const dropdown = document.getElementById('user-menu');
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
}
</script>
