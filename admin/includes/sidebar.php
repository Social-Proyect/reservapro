<aside class="sidebar">
    <div class="sidebar-header">
        <h2>📋 ReservaPro</h2>
        <p class="text-muted">Panel Admin</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            <span>Dashboard</span>
        </a>
        
        <a href="calendario.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'calendario.php' ? 'active' : '' ?>">
            <span class="nav-icon">📅</span>
            <span>Calendario</span>
        </a>
        
        <a href="clientes.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span>
            <span>Clientes</span>
        </a>
        
        <a href="empleados.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'empleados.php' ? 'active' : '' ?>">
            <span class="nav-icon">👤</span>
            <span>Empleados</span>
        </a>
        
        <a href="servicios.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'servicios.php' ? 'active' : '' ?>">
            <span class="nav-icon">✂️</span>
            <span>Servicios</span>
        </a>
        
        <a href="reportes.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : '' ?>">
            <span class="nav-icon">📈</span>
            <span>Reportes</span>
        </a>
        
        <a href="editar-empresa.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'editar-empresa.php' ? 'active' : '' ?>">
            <span class="nav-icon">⚙️</span>
            <span>Editar Empresa</span>
        </a>
    </nav>
</aside>
