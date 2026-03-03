// assets/js/admin/config/config.sessions.js

// Variable global para guardar el estado anterior y evitar parpadeos en la UI
let previousSessionsState = '';

// 1. RENDERIZAR SESIONES ACTIVAS
function renderSessions(sessions) {
    const container = document.getElementById('sesiones-activas');
    if (!container) return;

    if (!sessions || sessions.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-400 italic">No hay sesiones activas en este momento.</p>';
        return;
    }

    let html = '';
    sessions.forEach(s => {
        let icon = 'fa-desktop';
        if(s.dispositivo.toLowerCase().includes('mobile') || s.dispositivo.toLowerCase().includes('android') || s.dispositivo.toLowerCase().includes('iphone')) icon = 'fa-mobile-alt';
        if(s.dispositivo.toLowerCase().includes('mac')) icon = 'fa-laptop';

        const bgClass = s.es_actual 
            ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' 
            : 'bg-white border-slate-200 dark:bg-slate-800/50 dark:border-slate-700';

        const iconColor = s.es_actual ? 'text-blue-600' : 'text-slate-500';
        
        const badge = s.es_actual 
            ? `<span class="ml-2 bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold border border-green-200 dark:bg-green-900/40 dark:text-green-400 dark:border-green-800">ACTUAL</span>` 
            : '';

        const btnClose = !s.es_actual 
            ? `<button onclick="closeSession(${s.id})" class="text-xs font-bold text-red-500 border border-red-200 hover:bg-red-50 px-3 py-1.5 rounded-lg transition dark:border-red-900/40 dark:hover:bg-red-900/20">
                 Cerrar Sesión
               </button>` 
            : '';

        html += `
            <div id="session-card-${s.id}" class="flex items-center justify-between p-4 rounded-xl border ${bgClass} transition-all duration-300 animate-fade-in">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-white dark:bg-slate-700 rounded-full flex items-center justify-center ${iconColor} shadow-sm border border-slate-100 dark:border-slate-600">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-white flex items-center">
                            ${s.dispositivo} ${badge}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            <i class="fas fa-map-marker-alt text-[10px] mr-1 opacity-70"></i>${s.ubicacion} 
                            <span class="mx-1">•</span> 
                            ${s.fecha}
                        </p>
                    </div>
                </div>
                <div>${btnClose}</div>
            </div>`;
    });
    container.innerHTML = html;
}

// 2. RENDERIZAR HISTORIAL (INACTIVAS)
function renderHistory(history) {
    const container = document.getElementById('sesiones-historial');
    if (!container) return;

    if (!history || history.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-400 italic">No hay historial reciente.</p>';
        return;
    }

    let html = '';
    history.forEach(h => {
        let icon = 'fa-desktop';
        if(h.dispositivo.toLowerCase().includes('mobile') || h.dispositivo.toLowerCase().includes('android') || h.dispositivo.toLowerCase().includes('iphone')) icon = 'fa-mobile-alt';

        html += `
            <div class="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50 dark:bg-slate-800/20 dark:border-slate-700 opacity-80 hover:opacity-100 transition-opacity animate-fade-in">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center text-slate-500">
                        <i class="fas ${icon} text-xs"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300">${h.dispositivo}</p>
                        <p class="text-[10px] text-slate-400">Último acceso: ${h.fecha}</p>
                    </div>
                </div>
                <div class="text-[10px] text-slate-400 font-medium px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded">
                    ${h.ubicacion}
                </div>
            </div>`;
    });
    container.innerHTML = html;
}

// 3. FUNCIÓN PARA CERRAR SESIÓN (Con SweetAlert2 y Animación inmediata)
async function closeSession(id) {
    const result = await Swal.fire({
        title: '¿Cerrar esta sesión remota?',
        text: "Se desconectará el dispositivo de tu cuenta de inmediato.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-sign-out-alt mr-1"></i> Sí, cerrar sesión',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'dark:bg-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 rounded-xl'
        }
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('/HelpDesk/db/configuracion/close-session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const res = await response.json();

        if (res.success) {
            // 1. Mostrar Toast de éxito
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success', title: 'Sesión cerrada exitosamente',
                showConfirmButton: false, timer: 3000, timerProgressBar: true,
                customClass: { popup: 'dark:bg-slate-800 dark:text-white border border-slate-200 dark:border-slate-700' }
            });

            // 2. Efecto visual: Desaparecer la tarjeta activa de inmediato
            const card = document.getElementById(`session-card-${id}`);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    // Quitamos la tarjeta del HTML
                    card.remove();
                    
                    // Verificamos si la lista quedó vacía
                    const containerActivas = document.getElementById('sesiones-activas');
                    if(containerActivas && containerActivas.children.length === 0) {
                        containerActivas.innerHTML = '<p class="text-sm text-slate-400 italic">No hay sesiones activas en este momento.</p>';
                    }

                    // 3. Forzamos la actualización desde el servidor para que aparezca en el historial
                    // Le damos medio segundo para asegurar que la BD procesó el UPDATE
                    setTimeout(() => {
                        fetchAndUpdateSessions(true);
                    }, 500);

                }, 300); // Duración de la animación CSS
            } else {
                // Si no encuentra la tarjeta, actualiza todo de golpe
                fetchAndUpdateSessions(true);
            }

        } else {
            Swal.fire({
                title: 'Error', text: res.message || 'No se pudo cerrar la sesión.', icon: 'error',
                customClass: { popup: 'dark:bg-slate-800 dark:text-white border border-slate-200 dark:border-slate-700' }
            });
        }
    } catch (error) { 
        console.error(error); 
    }
}

// 4. LÓGICA DE ACTUALIZACIÓN EN TIEMPO REAL (SILENCIOSA)
async function fetchAndUpdateSessions(forceUpdate = false) {
    try {
        // Asegúrate de que esta ruta coincida con el nombre real de tu archivo PHP
        const response = await fetch('/HelpDesk/db/configuracion/get-config-data.php');
        if (!response.ok) return;
        
        const data = await response.json();
        
        if (data.success) {
            // Extraemos solo lo que nos importa para comparar (sesiones y historial)
            // Ignoramos el 'profile' para que si el usuario cambia su nombre no parpadeen las sesiones innecesariamente
            const currentState = JSON.stringify({
                sessions: data.sessions,
                history: data.history
            });

            // Si los datos cambiaron desde la última vez (o forzamos actualización al cerrar sesión)
            if (currentState !== previousSessionsState || forceUpdate) {
                
                // Usamos los keys exactos que devuelve tu PHP (sessions y history)
                renderSessions(data.sessions);
                renderHistory(data.history);
                
                previousSessionsState = currentState;
            }
        }

    } catch (error) {
        console.error("Error al actualizar sesiones en segundo plano:", error);
    }
}

// 5. INICIAR EL "TIEMPO REAL"
function startRealTimeUpdates() {
    // Primera carga inmediata
    fetchAndUpdateSessions();
    
    // Consultar el servidor cada 10 segundos
    setInterval(() => {
        fetchAndUpdateSessions();
    }, 10000);
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    startRealTimeUpdates();
});