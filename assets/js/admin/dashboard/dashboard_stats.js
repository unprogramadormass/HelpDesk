document.addEventListener("DOMContentLoaded", function() {
    
    const STATS_URL = '/HelpDesk/db/admin/dashboard/dashboard_stats.php';
    const UPDATE_INTERVAL = 30000; // 30 segundos

    // Elementos con IDs
    const totalTickets = document.getElementById('totalTickets');
    const totalChange = document.getElementById('totalChange');
    const pendingTickets = document.getElementById('pendingTickets');
    const avgTime = document.getElementById('avgTime');
    const timeChange = document.getElementById('timeChange');
    const satisfaction = document.getElementById('satisfaction');
    const satisfactionText = document.getElementById('satisfactionText');

    function fetchDashboardStats() {
        fetch(STATS_URL)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboard(data.data);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                // Valores por defecto
                if (totalTickets) totalTickets.textContent = "0";
                if (pendingTickets) pendingTickets.textContent = "0";
                if (avgTime) avgTime.textContent = "0 min";
                if (satisfaction) satisfaction.textContent = "0%";
            });
    }

    function updateDashboard(stats) {
        // Tickets Totales
        if (totalTickets) {
            totalTickets.textContent = new Intl.NumberFormat('es-MX').format(stats.total_tickets);
        }
        
        // Porcentaje cambio
        if (totalChange) {
            const percent = stats.percentage_change;
            const isPositive = percent >= 0;
            const arrow = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
            const color = isPositive ? 'text-green-500' : 'text-red-500';
            
            totalChange.className = `${color} text-sm mt-2`;
            totalChange.innerHTML = `<i class="fas ${arrow}"></i> ${Math.abs(percent)}% vs mes anterior`;
        }
        
        // Pendientes
        if (pendingTickets) {
            pendingTickets.textContent = new Intl.NumberFormat('es-MX').format(stats.pending_tickets);
        }
        
        // Tiempo promedio
        if (avgTime) {
            avgTime.textContent = stats.current_avg_time;
        }
        
        // Cambio tiempo
        if (timeChange && stats.time_difference) {
            const isFaster = stats.time_is_faster;
            const arrow = isFaster ? 'fa-arrow-down' : 'fa-arrow-up';
            const color = isFaster ? 'text-green-500' : 'text-red-500';
            
            timeChange.className = `${color} text-sm mt-2`;
            timeChange.innerHTML = `<i class="fas ${arrow}"></i> ${stats.time_difference}`;
        }
        
        // Satisfacción
        if (satisfaction) {
            satisfaction.textContent = `${stats.satisfaction_percentage}%`;
            
            // Color según porcentaje
            if (stats.satisfaction_percentage >= 80) {
                satisfaction.className = "text-2xl md:text-3xl font-bold text-green-600 mt-1";
            } else if (stats.satisfaction_percentage >= 60) {
                satisfaction.className = "text-2xl md:text-3xl font-bold text-yellow-600 mt-1";
            } else {
                satisfaction.className = "text-2xl md:text-3xl font-bold text-red-600 mt-1";
            }
        }
        
        // Texto satisfacción
        if (satisfactionText) {
            satisfactionText.textContent = `${stats.closed_tickets} tickets cerrados este mes`;
        }
    }

    // Inicializar
    fetchDashboardStats();
    setInterval(fetchDashboardStats, UPDATE_INTERVAL);
});