// assets/js/tickets/tickets.view.js

let currentTicketPage = 1;
let debounceTimer;
let globalAgentsList = []; // Cache para agentes

document.addEventListener('DOMContentLoaded', () => {
    loadTickets();
    loadAgentsForModal(); // Precargar agentes

    // Filtros
    document.getElementById('filter-search').addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { currentTicketPage = 1; loadTickets(); }, 300);
    });

    ['filter-status', 'filter-priority', 'filter-date-start', 'filter-date-end'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            currentTicketPage = 1; 
            loadTickets();
        });
    });

    // Cerrar menú global al hacer clic fuera
    document.addEventListener('click', (e) => {
        const menu = document.getElementById('globalActionMenu');
        if (!e.target.closest('.action-btn') && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
});

// --- CARGA DE DATOS ---
async function loadTickets() {
    const search = document.getElementById('filter-search').value;
    const status = document.getElementById('filter-status').value;
    const priority = document.getElementById('filter-priority').value;
    const dateStart = document.getElementById('filter-date-start').value;
    const dateEnd = document.getElementById('filter-date-end').value;

    const url = `/HelpDesk/db/admin/tickets/get-tickets.php?page=${currentTicketPage}&search=${search}&estado=${status}&prioridad=${priority}&date_start=${dateStart}&date_end=${dateEnd}`;

    try {
        const response = await fetch(url);
        const res = await response.json();
        if (res.success) {
            renderTicketsTable(res.data);
            renderPagination(res.meta);
        } else { console.error(res.message); }
    } catch (error) { console.error(error); }
}

// --- RENDER TABLA ---
function renderTicketsTable(tickets) {
    const tbody = document.getElementById('tickets-table-body');
    if (!tbody) return;

    if (tickets.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-slate-400">No se encontraron tickets.</td></tr>`;
        return;
    }

    let html = '';
    tickets.forEach(t => {
        // --- Colores Prioridad ---
        let prioClass = 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
        if (t.prioridad === 'Alta' || t.prioridad === 'Crítica') prioClass = 'bg-red-100 text-red-600 border-red-200 dark:bg-red-900/40 dark:text-red-400 dark:border-red-800';
        if (t.prioridad === 'Media') prioClass = 'bg-orange-100 text-orange-600 border-orange-200 dark:bg-orange-900/40 dark:text-orange-400 dark:border-orange-800';
        if (t.prioridad === 'Baja') prioClass = 'bg-green-100 text-green-600 border-green-200 dark:bg-green-900/40 dark:text-green-400 dark:border-green-800';

        // --- Colores Estado ---
        let statusClass = 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
        
        switch (t.estado) {
            case 'Abierto':
                statusClass = 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:text-blue-400 dark:border-blue-800';
                break;
            case 'Asignado':
                statusClass = 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-400 dark:border-indigo-800';
                break;
            case 'En Proceso':
                statusClass = 'bg-cyan-100 text-cyan-700 border-cyan-200 dark:bg-cyan-900/40 dark:text-cyan-400 dark:border-cyan-800';
                break;
            case 'Espera':
                statusClass = 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-400 dark:border-amber-800';
                break;
            case 'Resuelto':
                statusClass = 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400 dark:border-emerald-800';
                break;
            case 'Cerrado':
                statusClass = 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700';
                break;
            case 'Cancelado':
                statusClass = 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-400 dark:border-rose-800';
                break;
        }

        const agenteHtml = t.agente_nombre 
            ? `<div class="flex items-center gap-2"><img src="/HelpDesk/assets/img/avatars/${t.agente_avatar || 'default.png'}" class="w-6 h-6 rounded-full"><span class="truncate">${t.agente_nombre}</span></div>`
            : `<div class="flex items-center gap-2 opacity-50"><div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px]">?</div><span class="italic">Sin Asignar</span></div>`;

        html += `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition border-b border-slate-50 dark:border-slate-700">
                <td class="p-4 font-mono text-blue-600 dark:text-blue-400 text-xs font-bold">${t.folio}</td>
                <td class="p-4">
                    <span class="font-medium block text-slate-700 dark:text-slate-200">${t.titulo}</span>
                    <span class="text-xs text-slate-400 truncate max-w-[200px] block">${t.descripcion ? t.descripcion.substring(0, 40) + '...' : ''}</span>
                </td>
                <td class="p-4">
                    <div class="flex items-center gap-2">
                        <img src="${t.creador_avatar_url}" class="w-6 h-6 rounded-full border border-slate-200"> 
                        <span class="truncate text-xs font-medium">${t.creador_nombre}</span>
                    </div>
                </td>
                <td class="p-4 text-xs">${agenteHtml}</td>
                <td class="p-4"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border ${prioClass}">${t.prioridad}</span></td>
                <td class="p-4">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border ${statusClass}">${t.estado}</span>
                </td>
                <td class="p-4 text-slate-500 text-xs whitespace-nowrap">${t.fecha_formateada}</td>
                
                <td class="p-4 text-center">
                    <button onclick="showGlobalMenu(this, ${t.id}, '${t.folio}', ${t.agente_id || 'null'})" class="action-btn text-slate-400 hover:text-blue-600 transition p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// --- MENÚ FLOTANTE GLOBAL (Soluciona el corte) ---
function showGlobalMenu(btn, ticketId, folio, currentAgentId) {
    const menu = document.getElementById('globalActionMenu');
    const rect = btn.getBoundingClientRect(); // Obtiene posición exacta del botón
    
    // Posicionar menú cerca del botón
    menu.style.top = `${rect.bottom + window.scrollY + 5}px`;
    menu.style.left = `${rect.left + window.scrollX - 100}px`; // -100 para alinearlo a la izq
    
    // Llenar contenido
    menu.innerHTML = `
        <a href="../../public/tickets/ticket-detail.php?id=${ticketId}" class="w-full px-4 py-3 text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2 transition">
            <i class="fas fa-eye text-blue-500"></i> Ver Detalles
        </a>
        <button onclick="openAssignModal(${ticketId}, '${folio}', ${currentAgentId})" class="w-full px-4 py-3 text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2 transition border-t border-slate-100 dark:border-slate-700">
            <i class="fas fa-user-edit text-orange-500"></i> Asignar / Reasignar
        </button>
    `;

    // Mostrar menú
    menu.classList.remove('hidden');
}

// --- LÓGICA DEL MODAL DE ASIGNACIÓN ---

// 1. Cargar lista de agentes (Solo una vez)
async function loadAgentsForModal() {
    try {
        const response = await fetch('/HelpDesk/db/admin/usuarios/get-agentes-list.php'); 
        const res = await response.json();
        if (res.success) {
            globalAgentsList = res.data;
        }
    } catch (error) { console.error("Error cargando agentes:", error); }
}

// 2. Abrir Modal
function openAssignModal(ticketId, folio, currentAgentId) {
    document.getElementById('globalActionMenu').classList.add('hidden'); // Cerrar menú
    const modal = document.getElementById('assignModal');
    const select = document.getElementById('assign-agent-select');
    
    // Setear datos
    document.getElementById('assign-ticket-id').value = ticketId;
    document.getElementById('assignModalTicketInfo').innerText = `Asignando Ticket: ${folio}`;
    
    // Llenar select
    let options = '<option value="">-- Seleccionar Agente --</option>';
    globalAgentsList.forEach(agent => {
        const selected = agent.id == currentAgentId ? 'selected' : '';
        options += `<option value="${agent.id}" ${selected}>${agent.nombre}</option>`;
    });
    select.innerHTML = options;

    // MOSTRAR CON ANIMACIÓN
    modal.classList.add('active'); 
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.querySelector('div').classList.remove('scale-95');
    modal.querySelector('div').classList.add('scale-100');
}

// 3. Cerrar Modal
function closeAssignModal() {
    const modal = document.getElementById('assignModal');
    
    // OCULTAR CON ANIMACIÓN
    modal.classList.remove('active');
    modal.classList.add('opacity-0', 'pointer-events-none');
    modal.querySelector('div').classList.add('scale-95');
    modal.querySelector('div').classList.remove('scale-100');
}

// 4. Guardar Asignación
async function submitAssignment() {
    const ticketId = document.getElementById('assign-ticket-id').value;
    const agentId = document.getElementById('assign-agent-select').value;

    if (!agentId) {
        Swal.fire('Error', 'Por favor selecciona un agente', 'warning');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'assign_agent');
        formData.append('ticket_id', ticketId);
        formData.append('agente_id', agentId);

        const res = await fetch('/HelpDesk/db/admin/tickets/assign-ticket-action.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Asignado',
                text: 'El agente ha sido asignado correctamente.',
                timer: 1500,
                showConfirmButton: false
            });
            closeAssignModal();
            loadTickets(); // Recargar tabla
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Error de conexión', 'error');
    }
}

// --- PAGINACIÓN ---
function renderPagination(meta) {
    const info = document.getElementById('pagination-info-T');
    const controls = document.getElementById('pagination-controls-T');
    info.innerHTML = `Mostrando <span class="font-bold text-slate-700 dark:text-white">${meta.start_index}</span> a <span class="font-bold text-slate-700 dark:text-white">${meta.end_index}</span> de <span class="font-bold text-slate-700 dark:text-white">${meta.total_records}</span> tickets`;
    
    let html = '';
    const prevDisabled = meta.current_page === 1 ? 'disabled opacity-50 cursor-not-allowed' : '';
    html += `<button onclick="changePage(${meta.current_page - 1})" ${prevDisabled} class="px-3 py-1 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"><i class="fas fa-chevron-left"></i></button>`;
    
    for (let i = 1; i <= meta.total_pages; i++) {
        if (i === 1 || i === meta.total_pages || (i >= meta.current_page - 1 && i <= meta.current_page + 1)) {
            const activeClass = i === meta.current_page ? 'bg-blue-50 border-blue-200 text-blue-600 dark:bg-slate-700 dark:text-white' : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700';
            html += `<button onclick="changePage(${i})" class="px-3 py-1 text-xs font-medium border rounded-lg transition ${activeClass}">${i}</button>`;
        }
    }
    
    const nextDisabled = meta.current_page === meta.total_pages ? 'disabled opacity-50 cursor-not-allowed' : '';
    html += `<button onclick="changePage(${meta.current_page + 1})" ${nextDisabled} class="px-3 py-1 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700"><i class="fas fa-chevron-right"></i></button>`;
    
    controls.innerHTML = html;
}

function changePage(page) {
    if (page < 1) return;
    currentTicketPage = page;
    loadTickets();
}