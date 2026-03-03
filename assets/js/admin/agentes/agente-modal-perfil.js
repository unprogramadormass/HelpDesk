// Variable global para la gráfica (para destruirla antes de crear una nueva)
let agentChartInstance = null;

async function openAgentProfile(id) {
    const modal = document.getElementById('agentModal');
    
    // Mostrar modal con efecto de carga (opcional: limpiar campos antes)
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100', 'pointer-events-auto');

    try {
        const response = await fetch(`/HelpDesk/db/admin/agentes/get-agente-perfil.php?id=${id}`);
        const res = await response.json();

        if (res.success) {
            const ag = res.data;

            // 1. Header (Avatar, Nombre, Rol)
            const avatarUrl = ag.avatar 
                ? `/HelpDesk/assets/img/avatars/${ag.avatar}` 
                : `https://api.dicebear.com/7.x/avataaars/svg?seed=${ag.username}`;
            document.getElementById('modal-agent-img').src = avatarUrl;
            document.getElementById('modal-agent-name').innerText = `${ag.firstname} ${ag.firstapellido}`;
            
            // Subtítulo inteligente
            let subTitle = ag.puesto || 'Sin Puesto';
            if (ag.rol) subTitle += ` • ${ag.rol}`;
            document.getElementById('modal-agent-role').innerText = subTitle;

            // Badges Estado e ID
            const statusContainer = document.getElementById('modal-agent-status-container');
            // Limpiamos y recreamos los badges
            let statusBadge = ag.connected == 1 
                ? `<span class="px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs rounded-md font-bold">● En Línea</span>`
                : `<span class="px-2 py-1 bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 text-xs rounded-md">● Desconectado</span>`;
            
            statusContainer.innerHTML = `
                ${statusBadge}
                <span class="px-2 py-1 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300 text-xs rounded-md">ID: ${ag.folio}</span>
                <span class="px-2 py-1 bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-300 text-xs rounded-md"><i class="fas fa-map-marker-alt"></i> ${ag.sucursal || 'Sin Sucursal'}</span>
            `;

            // 2. Datos de Contacto
            document.getElementById('modal-contact-email').innerText = ag.email;
            document.getElementById('modal-contact-ext').innerText = ag.extension ? `Ext. ${ag.extension}` : 'Sin Ext.';
            document.getElementById('modal-contact-sucursal').innerText = ag.sucursal || 'N/A';

            // 3. Actividad Reciente (Lista dinámica)
            const activityContainer = document.getElementById('modal-activity-list');
            let activityHtml = '';
            
            ag.actividad.forEach(act => {
                if (act.tipo === 'resuelto') {
                    // Item Resuelto (Verde con estrellas)
                    activityHtml += `
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 dark:bg-green-900/20 flex items-center justify-center"><i class="fas fa-check"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">${act.titulo}</p>
                                    <p class="text-xs text-slate-500">${act.ticket} • ${act.tiempo}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex text-yellow-400 text-xs mb-1">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <span class="text-xs text-slate-400">Excelente</span>
                            </div>
                        </div>`;
                } else {
                    // Item Abierto (Azul con reloj)
                    activityHtml += `
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/20 flex items-center justify-center"><i class="fas fa-clock"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">${act.titulo}</p>
                                    <p class="text-xs text-slate-500">${act.ticket} • ${act.tiempo}</p>
                                </div>
                            </div>
                            <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 px-2 py-1 rounded font-medium">Activo</span>
                        </div>`;
                }
            });
            activityContainer.innerHTML = activityHtml;

            // 4. Renderizar Gráfica
            renderAgentChart(ag.kpis.semana);

        }
    } catch (error) {
        console.error("Error cargando perfil:", error);
    }
}

function closeAgentModal() {
    const modal = document.getElementById('agentModal');
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
}

// Función auxiliar para la gráfica
function renderAgentChart(dataPoints) {
    const ctx = document.getElementById('agentPerformanceChart').getContext('2d');
    
    // Destruir gráfica anterior si existe para evitar superposiciones
    if (agentChartInstance) {
        agentChartInstance.destroy();
    }

    agentChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Tickets Resueltos',
                data: dataPoints,
                borderColor: '#3b82f6', // blue-500
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(200, 200, 200, 0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
}