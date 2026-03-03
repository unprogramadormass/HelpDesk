document.addEventListener("DOMContentLoaded", function() {
    
    // Configuración
    const NOTIF_URL = '/HelpDesk/db/header/notificaciones.php';
    const MARK_READ_URL = '/HelpDesk/db/header/marcar_leida_noti.php';
    const MARK_ALL_READ_URL = '/HelpDesk/db/header/marcar_todas_leidas_noti.php';
    const POLL_INTERVAL = 1000;

    // Referencias al DOM
    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notifList');
    const dropdown = document.getElementById('notif-dropdown');
    const markAllReadBtn = document.getElementById('markAllReadBtn');

    // Variables de estado
    let currentNotifications = [];
    let lastUnreadCount = 0;

    // Función principal para obtener notificaciones
    function fetchNotifications() {
        fetch(NOTIF_URL)
            .then(response => {
                if (!response.ok) throw new Error("Error en red");
                return response.json();
            })
            .then(data => {
                currentNotifications = data;
                //console.log("Datos recibidos:", data); // Para debug
                updateBadge(data);
                renderList(data);
            })
            .catch(error => {
                console.error("Error obteniendo notificaciones:", error);
                if (list && list.innerHTML.includes("Cargando")) {
                    list.innerHTML = '<li class="p-4 text-center text-xs text-slate-500">Error al cargar</li>';
                }
            });
    }

    // Función para actualizar el badge - MANEJA NULL
    function updateBadge(notifications) {
        if (!badge) return;

        // Contar notificaciones no leídas (leida es NULL o vacío)
        const unreadCount = notifications.filter(n => 
            n.leida === null || 
            n.leida === '' || 
            n.leida === undefined || 
            n.leida == 0  // Por si acaso
        ).length;
        
        //console.log("Conteo no leídas:", unreadCount, "de", notifications.length); // Debug

        if (unreadCount > 0) {
            // Establecer el texto del badge
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
            
            // Ajustar el ancho mínimo basado en el número de dígitos
            if (unreadCount > 9) {
                badge.style.minWidth = '22px';
                badge.style.height = '22px';
            } else {
                badge.style.minWidth = '18px';
                badge.style.height = '18px';
            }
            
            // Mostrar el badge con animación
            badge.classList.remove('opacity-0', 'scale-0');
            badge.classList.add('opacity-100', 'scale-100', 'animate-pulse');
            
            // Detectar si hay nuevas notificaciones
            if (unreadCount > lastUnreadCount && lastUnreadCount > 0) {
                badge.classList.add('animate-bounce');
                setTimeout(() => {
                    badge.classList.remove('animate-bounce');
                }, 1000);
            }
        } else {
            // Ocultar el badge con animación
            badge.classList.remove('opacity-100', 'scale-100', 'animate-pulse', 'animate-bounce');
            badge.classList.add('opacity-0', 'scale-0');
            badge.textContent = '';
        }
        
        // Guardar el conteo actual
        lastUnreadCount = unreadCount;
    }

    // Función para renderizar la lista - MANEJA NULL
    function renderList(notifications) {
        if (!list) return;

        if (notifications.length === 0) {
            list.innerHTML = '<li class="p-4 text-center text-xs text-slate-500">No tienes notificaciones.</li>';
            return;
        }

        let htmlContent = '';

        notifications.forEach(notif => {
            // Determinar si está leída (true si leida = 1, false si es null/0/vacío)
            const isRead = !(notif.leida === null || notif.leida === '' || notif.leida === undefined || notif.leida == 0);
            
            //console.log("Notificación:", notif.id, "leida:", notif.leida, "isRead:", isRead); // Debug
            
            const itemClass = isRead ? 'opacity-60' : 'bg-blue-50/50 dark:bg-blue-900/10';
            
            let iconClass = 'fa-info-circle';
            let iconColorClass = isRead ? 
                'text-slate-400 bg-slate-100 dark:bg-slate-700' : 
                'text-blue-600 bg-blue-100 dark:bg-blue-800';

            const descLower = notif.descripcion.toLowerCase();
            if (descLower.includes('crítico') || descLower.includes('error')) {
                iconClass = 'fa-exclamation-triangle';
                iconColorClass = isRead ? 
                    'text-red-400 bg-red-100/50' : 
                    'text-red-600 bg-red-100 dark:bg-red-800';
            } else if (descLower.includes('éxito') || descLower.includes('completado') || descLower.includes('finalizado')) {
                iconClass = 'fa-check-circle';
                iconColorClass = isRead ? 
                    'text-green-400 bg-green-100/50' : 
                    'text-green-600 bg-green-100 dark:bg-green-800';
            }

            // ¡NUEVO! Agregamos data-ticket-id="${notif.ticket_id}" al elemento <li>
            htmlContent += `
                <li data-notif-id="${notif.id}" data-ticket-id="${notif.ticket_id}" data-read="${isRead}" 
                    class="p-3 border-b border-slate-100 dark:border-slate-700 cursor-pointer flex gap-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 ${itemClass}">
                    <div class="${iconColorClass} w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="fas ${iconClass} text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <p class="text-xs font-medium text-slate-700 dark:text-slate-200">
                                Ticket #${notif.ticket_id || '?'}
                            </p>
                            ${!isRead ? '<span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>' : ''}
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                            ${notif.descripcion}
                        </p>
                        <span class="text-[10px] text-slate-400 mt-1 block">
                            ${formatDate(notif.fecha_creacion)}
                        </span>
                    </div>
                </li>
            `;
        });

        if (list.innerHTML !== htmlContent) {
            list.innerHTML = htmlContent;
            
            // Agregar event listeners
            document.querySelectorAll('#notifList li[data-notif-id]').forEach(item => {
                item.addEventListener('click', function() {
                    const notifId = this.getAttribute('data-notif-id');
                    const ticketId = this.getAttribute('data-ticket-id'); // ¡NUEVO! Obtenemos el ID del ticket
                    const isRead = this.getAttribute('data-read') === 'true';
                    
                    if (!isRead) {
                        // ¡NUEVO! Le pasamos el ticketId a la función para que redirija
                        markAsRead(notifId, this, ticketId);
                    } else {
                        // ¡NUEVO! Si ya está leída, redirigimos de inmediato
                        window.location.href = `/HelpDesk/pages/public/tickets/ticket-detail.php?id=${ticketId}`;
                    }
                });
            });
        }
    }

    // Función para marcar una notificación como leída
    // ¡NUEVO! Añadimos el parámetro ticketId
    function markAsRead(notifId, element, ticketId) {
        fetch(MARK_READ_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: notifId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Marcar como leída visualmente
                element.setAttribute('data-read', 'true');
                element.classList.remove('bg-blue-50/50', 'dark:bg-blue-900/10');
                element.classList.add('opacity-60');
                
                // Quitar el punto azul
                const dot = element.querySelector('.bg-blue-500');
                if (dot) dot.remove();
                
                // Actualizar el conteo localmente
                const notifIndex = currentNotifications.findIndex(n => n.id == notifId);
                if (notifIndex !== -1) {
                    currentNotifications[notifIndex].leida = 1;
                }
                
                // Recalcular y actualizar badge
                const unreadCount = currentNotifications.filter(n => 
                    n.leida === null || n.leida === '' || n.leida === undefined || n.leida == 0
                ).length;
                
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                } else {
                    // Ocultar badge si no hay más no leídas
                    badge.classList.remove('opacity-100', 'scale-100', 'animate-pulse');
                    badge.classList.add('opacity-0', 'scale-0');
                    badge.textContent = '';
                }

                // ¡NUEVO! Redirigir a la página de detalles del ticket
                window.location.href = `/HelpDesk/pages/public/tickets/ticket-detail.php?id=${ticketId}`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error al marcar como leída', true);
        });
    }

    // Función para marcar TODAS como leídas
    function markAllAsRead() {
        const unreadNotifications = currentNotifications.filter(n => 
            n.leida === null || n.leida === '' || n.leida === undefined || n.leida == 0
        );
        
        if (unreadNotifications.length === 0) {
            showMessage('No hay notificaciones sin leer', true);
            return;
        }

        const unreadIds = unreadNotifications.map(n => n.id);

        fetch(MARK_ALL_READ_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: unreadIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar todas las notificaciones visualmente
                document.querySelectorAll('#notifList li[data-read="false"]').forEach(item => {
                    item.setAttribute('data-read', 'true');
                    item.classList.remove('bg-blue-50/50', 'dark:bg-blue-900/10');
                    item.classList.add('opacity-60');
                    
                    const dot = item.querySelector('.bg-blue-500');
                    if (dot) dot.remove();
                });
                
                // Ocultar badge completamente
                badge.classList.remove('opacity-100', 'scale-100', 'animate-pulse');
                badge.classList.add('opacity-0', 'scale-0');
                badge.textContent = '';
                
                // Actualizar datos localmente
                currentNotifications = currentNotifications.map(n => ({
                    ...n,
                    leida: 1
                }));
                
                showMessage('Todas las notificaciones marcadas como leídas');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error al marcar como leídas', true);
        });
    }

    // Función para mostrar mensajes
    function showMessage(text, isError = false) {
        const message = document.createElement('div');
        message.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-sm font-medium transition-all duration-300 transform ${
            isError ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' : 
                     'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200'
        }`;
        message.textContent = text;
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => message.remove(), 300);
        }, 2000);
    }

    // Función para formatear fecha
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Ahora mismo';
        if (minutes < 60) return `Hace ${minutes} min`;
        if (hours < 24) return `Hace ${hours} h`;
        if (days === 1) return 'Ayer';
        if (days < 7) return `Hace ${days} días`;
        return date.toLocaleDateString('es-ES');
    }

    // Función para manejar clics fuera del dropdown
    document.addEventListener('click', function(event) {
        if (dropdown && !dropdown.contains(event.target) && 
            !event.target.closest('button[onclick="toggleNotifications()"]')) {
            dropdown.classList.add('hidden');
        }
    });

    // Función global para toggle
    window.toggleNotifications = function() {
        if (dropdown) {
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                fetchNotifications();
            }
        }
    };

    // Inicialización
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllAsRead);
    }
    
    // Carga inicial
    fetchNotifications();
    
    // Actualización periódica
    setInterval(fetchNotifications, POLL_INTERVAL);
});