// js/game.js - Con mejor manejo de errores
class GameEngine {
    constructor() {
        this.gameState = {};
        this.isRunning = false;
    }

    async init() {
        await this.loadGameState();
        this.setupEventListeners();
        if (this.isLoggedIn()) {
            this.startGameLoop();
        }
    }

    isLoggedIn() {
        return document.getElementById('money') !== null;
    }

    async loadGameState() {
        if (!this.isLoggedIn()) return;
        
        try {
            const response = await fetch('php/game.php?action=load');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            
            if (data.error) {
                this.showNotification(data.error, 'error');
            } else {
                this.gameState = data;
                this.updateUI();
            }
        } catch (error) {
            console.error('Error loading game:', error);
            this.showNotification('Error al cargar el juego: ' + error.message, 'error');
        }
    }

    setupEventListeners() {
        // Configurar formularios de autenticación
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleAuth(e, 'login'));
        }
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleAuth(e, 'register'));
        }

        // Configurar navegación
        document.querySelectorAll('[data-tab]').forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                switchTab(tabName);
            });
        });
    }

    async handleAuth(e, action) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const messageDiv = document.getElementById(action + 'Message');
        
        // Mostrar loading
        messageDiv.innerHTML = '<div class="alert alert-info">Procesando...</div>';
        
        try {
            const response = await fetch('php/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    username: formData.get('username'),
                    email: formData.get('email'),
                    password: formData.get('password')
                })
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Respuesta del servidor no válida');
            }
            
            if (result.success) {
                messageDiv.innerHTML = '<div class="alert alert-success">' + result.message + '</div>';
                // Recargar después de un breve delay
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            } else {
                messageDiv.innerHTML = '<div class="alert alert-danger">' + result.message + '</div>';
            }
            
        } catch (error) {
            console.error(action + ' error:', error);
            messageDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
        }
    }

    startGameLoop() {
        this.isRunning = true;
        this.gameLoop();
    }

    async gameLoop() {
        if (!this.isRunning || !this.isLoggedIn()) return;

        // Simular ganancias cada segundo
        if (this.gameState.workers) {
            const earnings = this.calculateEarnings(1000);
            this.gameState.money += earnings;
            this.updateUI();
        }

        // Guardar cada 30 segundos
        if (Date.now() % 30000 < 1000) {
            await this.saveGame();
        }

        setTimeout(() => this.gameLoop(), 1000);
    }

    calculateEarnings(time) {
        let totalEarnings = 0;
        if (this.gameState.workers) {
            this.gameState.workers.forEach(worker => {
                const production = worker.speed_skill * 0.1;
                const efficiency = 1 + (worker.efficiency_skill * 0.05);
                totalEarnings += production * efficiency * (time / 1000);
            });
        }
        return totalEarnings;
    }

    updateUI() {
        if (!this.isLoggedIn()) return;

        // Actualizar monedas
        if (this.gameState.money !== undefined) {
            document.getElementById('money').textContent = this.gameState.money.toFixed(2);
        }
        if (this.gameState.gems !== undefined) {
            document.getElementById('gems').textContent = this.gameState.gems;
        }
        if (this.gameState.prestige_points !== undefined) {
            document.getElementById('prestige').textContent = this.gameState.prestige_points;
        }
        if (this.gameState.level !== undefined) {
            document.getElementById('playerLevel').textContent = this.gameState.level;
        }
    }

    async saveGame() {
        try {
            const response = await fetch('php/game.php?action=save', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(this.gameState)
            });
            
            const result = await response.json();
            if (!result.success) {
                console.error('Error saving game:', result.message);
            }
        } catch (error) {
            console.error('Error saving game:', error);
        }
    }

    async trainWorker(workerId) {
        try {
            const response = await fetch('php/workers.php?action=train', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({worker_id: workerId})
            });
            
            const result = await response.json();
            if (result.success) {
                await this.loadGameState();
                this.showNotification(result.message || '¡Trabajador entrenado!');
            } else {
                this.showNotification(result.message || 'Error al entrenar', 'error');
            }
        } catch (error) {
            console.error('Error training worker:', error);
            this.showNotification('Error de conexión', 'error');
        }
    }

    async hireWorker(workerType) {
        try {
            const response = await fetch('php/game.php?action=hire_worker', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({worker_type: workerType})
            });
            
            const result = await response.json();
            if (result.success) {
                await this.loadGameState();
                this.showNotification(result.message || '¡Trabajador contratado!');
            } else {
                this.showNotification(result.message || 'Error al contratar', 'error');
            }
        } catch (error) {
            console.error('Error hiring worker:', error);
            this.showNotification('Error de conexión', 'error');
        }
    }

    showNotification(message, type = 'success') {
        const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 3000);
    }
}

// Funciones globales
function switchTab(tabName) {
    document.querySelectorAll('.game-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.getElementById(`${tabName}-tab`).classList.add('active');
    
    document.querySelectorAll('.game-nav .nav-link').forEach(button => {
        button.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}

function logout() {
    if (confirm('¿Estás seguro de que quieres salir?')) {
        fetch('php/auth.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'logout'})
        }).then(() => {
            window.location.reload();
        });
    }
}

function hireWorker(workerType) {
    if (window.game) {
        window.game.hireWorker(workerType);
    }
}

function showSettings() {
    alert('Configuración en desarrollo...');
}

// Inicializar juego cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.game = new GameEngine();
    window.game.init();
    
    // Agregar consola de depuración
    window.debugGame = function() {
        console.log('Game State:', window.game.gameState);
        console.log('Session:', {
            user_id: <?php echo $_SESSION['user_id'] ?? 'null'; ?>,
            username: '<?php echo $_SESSION['username'] ?? 'null'; ?>'
        });
    };
});