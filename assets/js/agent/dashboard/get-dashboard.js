// Variables globales para las gráficas y estado
let mainChartInstance = null;
let pieChartInstance = null;
let allTicketsCache = []; 
let autoRefreshInterval = null;
let lastFirstTicketId = null; // Para detectar si hay tickets nuevos sin refrescar todo

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    loadDashboardData();
    setupSearchListener();
    
    // Iniciar actualización automática cada 10 segundos
    startAutoRefresh(10000); // Cambiado a 10s según tu comentario
});

/**
 * 1. CONTROL DE ACTUALIZACIÓN AUTOMÁTICA
 */
function startAutoRefresh(ms) {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(() => {
        const searchInput = document.getElementById('searchTicketInput');
        const isSearching = searchInput && searchInput.value.trim() !== "";
        loadDashboardData(isSearching);
    }, ms);
}

/**
 * 2. CARGA DE DATOS (Lógica de no-refresco visual)
 */
async function loadDashboardData(isSearching = false) {
    try {
        const response = await fetch('/HelpDesk/db/agent/dashboard/get-dashboard-data.php');
        const data = await response.json();

        if (data.success) {
            updateTextIfChanged('totalTickets', data.kpis.total);
            updateTextIfChanged('pendingTickets', data.kpis.pending);
            updateTextIfChanged('avgTime', data.kpis.avgTime);
            updateTextIfChanged('satisfaction', data.kpis.satisfaction + "%");
            updateTextIfChanged('satisfactionText', data.kpis.resolved + " tickets cerrados");

            updateMainChart(data.charts.main);
            updatePieChart(data.charts.pie);

            renderRecentTableSmooth(data.recent);

            allTicketsCache = data.history; 
            if (!isSearching) {
                renderHistoryTable(allTicketsCache); 
            }
        }
    } catch (error) {
        console.error("Error cargando dashboard:", error);
    }
}

function updateTextIfChanged(id, newValue) {
    const el = document.getElementById(id);
    if (el && el.innerText !== String(newValue)) {
        el.innerText = newValue;
    }
}

/**
 * 3. RENDERIZADO FLUIDO DE TABLA RECIENTES
 */
function renderRecentTableSmooth(tickets) {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-slate-400">No hay tickets recientes.</td></tr>';
        return;
    }

    if (lastFirstTicketId !== tickets[0].id) {
        let html = '';
        tickets.forEach((t, index) => {
            const isNew = (lastFirstTicketId !== null && index === 0);
            const rowClass = isNew ? 'animate-fade-in bg-blue-50/30 dark:bg-blue-900/10' : '';

            // --- Lógica de Botones de Acción (Igual que en tickets.view.js) ---
            let actionButtons = '';
            // Validamos que CURRENT_USER_ID esté definido en tu index.php del dashboard
            const myId = typeof CURRENT_USER_ID !== 'undefined' ? Number(CURRENT_USER_ID) : 0;
            const asignadoA = t.agente_id ? Number(t.agente_id) : null;

            if (!asignadoA) {
                actionButtons = `
                    <button onclick="takeTicket(${t.id})" class="flex items-center justify-center gap-1 text-emerald-600 hover:text-emerald-800 transition p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-[10px] font-bold" title="Tomar este ticket">
                        <i class="fas fa-hand-paper"></i> Tomar
                    </button>
                `;
            } else if (asignadoA === myId) {
                actionButtons = `
                    <a href="../../../pages/public/tickets/ticket-detail.php?id=${t.id}" class="flex items-center justify-center gap-1 text-blue-600 hover:text-blue-800 transition p-1.5 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30 text-[10px] font-bold" title="Ver detalles">
                        <i class="fas fa-eye"></i> Ver
                    </a>
                `;
            } else {
                actionButtons = `
                    <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded text-[10px] font-medium dark:bg-slate-700 dark:text-slate-400 border border-slate-200 dark:border-slate-600 flex items-center justify-center gap-1">
                        <i class="fas fa-lock"></i> Asignado
                    </span>
                `;
            }

            html += `
                <tr class="border-b border-slate-50 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors ${rowClass}">
                    <td class="py-4 font-mono text-blue-600 dark:text-blue-400 text-xs font-bold">${t.folio}</td>
                    <td class="py-4 font-medium text-slate-700 dark:text-slate-200 text-sm">${t.titulo}</td>
                    <td class="py-4">
                        <div class="flex items-center gap-2">
                            <img src="${t.creador_avatar_url}" class="w-6 h-6 rounded-full border border-slate-200"> 
                            <span class="text-xs">${t.creador_nombre}</span>
                        </div>
                    </td>
                    <td class="py-4 text-xs">${getAgenteHtml(t)}</td>
                    <td class="py-4"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border ${getPrioColor(t.prioridad)}">${t.prioridad}</span></td>
                    <td class="py-4"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border ${getStatusColor(t.estado)}">${t.estado}</span></td>
                    <td class="py-4 text-center">
                        ${actionButtons}
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
        lastFirstTicketId = tickets[0].id;
    }
}

/**
 * 4. BUSCADOR Y TABLA HISTORIAL
 */
function setupSearchListener() {
    const searchInput = document.getElementById('searchTicketInput');
    if(searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allTicketsCache.filter(t => 
                t.folio.toLowerCase().includes(term) || 
                t.titulo.toLowerCase().includes(term) ||
                t.estado.toLowerCase().includes(term)
            );
            renderHistoryTable(filtered);
        });
    }
}

function renderHistoryTable(tickets) {
    const tbody = document.getElementById('historialtt');
    if (!tbody) return;

    if (!tickets || tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-6 text-center text-slate-400 italic">No se encontraron tickets.</td></tr>';
        return;
    }

    let html = '';
    tickets.forEach(t => {
        html += `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-b border-slate-100 dark:border-slate-700">
                <td class="p-3 font-mono text-blue-600 dark:text-blue-400 font-bold text-xs">${t.folio}</td>
                <td class="p-3 font-medium text-slate-700 dark:text-slate-200 text-sm">${t.titulo}</td>
                <td class="p-3">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border ${getStatusColor(t.estado)}">${t.estado}</span>
                </td>
                <td class="p-3 text-right">
                    <a href="../../../pages/public/tickets/ticket-detail.php?id=${t.id}" class="text-blue-500 hover:text-blue-700 transition p-2 hover:bg-blue-50 dark:hover:bg-slate-700 rounded-full"><i class="fas fa-eye"></i></a>
                </td>
            </tr>`;
    });
    tbody.innerHTML = html;
}

/**
 * 5. CONFIGURACIÓN DE GRÁFICAS (CHART.JS)
 */
function initCharts() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#cbd5e1' : '#64748b';
    const gridColor = isDark ? '#334155' : '#f1f5f9';
    
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = textColor;

    const ctxMain = document.getElementById('mainChart').getContext('2d');
    mainChartInstance = new Chart(ctxMain, {
        type: 'line',
        data: { labels: [], datasets: [
            { label: 'Nuevos', data: [], borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.1)', tension: 0.4, fill: true },
            { label: 'Resueltos', data: [], borderColor: '#10b981', backgroundColor: 'transparent', tension: 0.4, borderDash: [5, 5] }
        ]},
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { grid: { color: gridColor }, beginAtZero: true }, x: { grid: { display: false } } } }
    });

    const ctxPie = document.getElementById('pieChart').getContext('2d');
    pieChartInstance = new Chart(ctxPie, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: ['#3b82f6', '#6366f1', '#10b981', '#f59e0b'], borderWidth: 0 }]},
        options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } } }
    });
}

function updateMainChart(chartData) {
    mainChartInstance.data.labels = chartData.labels;
    mainChartInstance.data.datasets[0].data = chartData.new;
    mainChartInstance.data.datasets[1].data = chartData.resolved;
    mainChartInstance.update();
}

function updatePieChart(pieData) {
    if(pieData.data.length === 0) {
        pieChartInstance.data.labels = ['Sin Datos'];
        pieChartInstance.data.datasets[0].data = [1];
        pieChartInstance.data.datasets[0].backgroundColor = ['#cbd5e1'];
    } else {
        pieChartInstance.data.labels = pieData.labels;
        pieChartInstance.data.datasets[0].data = pieData.data;
        pieChartInstance.data.datasets[0].backgroundColor = ['#3b82f6', '#6366f1', '#10b981', '#f59e0b']; 
    }
    pieChartInstance.update();
}

/**
 * 6. FUNCIONES AUXILIARES DE ESTILO
 */
function getPrioColor(p) {
    if(p === 'Alta' || p === 'Crítica') return 'bg-red-100 text-red-600 border-red-200 dark:bg-red-900/40 dark:text-red-400 dark:border-red-800';
    if(p === 'Media') return 'bg-orange-100 text-orange-600 border-orange-200 dark:bg-orange-900/40 dark:text-orange-400 dark:border-orange-800';
    if(p === 'Baja') return 'bg-green-100 text-green-600 border-green-200 dark:bg-green-900/40 dark:text-green-400 dark:border-green-800';
    return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
}

function getStatusColor(e) {
    switch (e) {
        case 'Abierto': return 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:text-blue-400 dark:border-blue-800';
        case 'Asignado': return 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-400 dark:border-indigo-800';
        case 'En Proceso': return 'bg-cyan-100 text-cyan-700 border-cyan-200 dark:bg-cyan-900/40 dark:text-cyan-400 dark:border-cyan-800';
        case 'Espera': return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-400 dark:border-amber-800';
        case 'Resuelto': return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400 dark:border-emerald-800';
        case 'Cerrado': return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700';
        case 'Cancelado': return 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-400 dark:border-rose-800';
        default: return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
    }
}

function getAgenteHtml(t) {
    return t.agente_nombre 
        ? `<div class="flex items-center gap-2"><img src="/HelpDesk/assets/img/avatars/${t.agente_avatar || 'default.png'}" class="w-6 h-6 rounded-full" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=${t.agente_nombre}'"> ${t.agente_nombre}</div>`
        : '<span class="text-slate-400 italic">Sin Asignar</span>';
}

function updateChartColors(theme) {
    const c = theme === 'dark' ? '#cbd5e1' : '#64748b';
    const g = theme === 'dark' ? '#334155' : '#f1f5f9';
    Chart.defaults.color = c;
    if(mainChartInstance) {
        mainChartInstance.options.scales.y.grid.color = g;
        mainChartInstance.update();
    }
    if(pieChartInstance) pieChartInstance.update();
}

/**
 * 7. FUNCIÓN PARA TOMAR EL TICKET (Reutilizando backend)
 */
async function takeTicket(ticketId) {
    const result = await Swal.fire({
        title: '¿Tomar este ticket?',
        text: "Serás asignado como el responsable para resolverlo.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Sí, tomarlo',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('ticket_id', ticketId);

        // Llamamos al MISMO backend que usamos en la otra vista
        const response = await fetch('/HelpDesk/db/agent/tickets/take-ticket.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();

        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Ticket tuyo!',
                text: 'Redirigiendo a los detalles...',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = `../../../pages/public/tickets/ticket-detail.php?id=${ticketId}`;
            });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Hubo un problema de conexión', 'error');
    }
}