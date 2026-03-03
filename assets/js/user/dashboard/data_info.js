// --- FUNCIÓN PARA CARGAR ESTADÍSTICAS DEL DASHBOARD ---
function loadDashboardStats() {
    // IMPORTANTE: Asegúrate de poner la ruta correcta a tu nuevo archivo PHP
    fetch('/HelpDesk/db/user/dashboard/get_dashboard_stats.php') 
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                // Seleccionamos los elementos HTML y les inyectamos el número
                document.getElementById('totalTickets').innerText = res.data.total;
                document.getElementById('pendingTickets').innerText = res.data.pendientes;
                document.getElementById('closedTickets').innerText = res.data.finalizados;
                document.getElementById('cancelledTickets').innerText = res.data.cancelados;
            } else {
                console.error('Error del servidor:', res.error);
            }
        })
        .catch(error => console.error('Error al solicitar stats:', error));
}

// Ejecutar la función apenas cargue la página
document.addEventListener("DOMContentLoaded", () => {
    loadDashboardStats();
});