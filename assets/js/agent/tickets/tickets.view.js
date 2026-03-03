// assets/js/tickets/tickets.view.js

let currentTicketPage = 1;
let debounceTimer;

document.addEventListener('DOMContentLoaded', () => {
    loadTickets();

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
});

// --- CARGA DE DATOS ---
async function loadTickets() {
    const search = document.getElementById('filter-search').value;
    const status = document.getElementById('filter-status').value;
    const priority = document.getElementById('filter-priority').value;
    const dateStart = document.getElementById('filter-date-start').value;
    const dateEnd = document.getElementById('filter-date-end').value;

    const url = `/HelpDesk/db/agent/tickets/get-tickets.php?page=${currentTicketPage}&search=${search}&estado=${status}&prioridad=${priority}&date_start=${dateStart}&date_end=${dateEnd}`;

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
        tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-slate-400">No se encontraron tickets.</td></tr>`;
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
            case 'Abierto': statusClass = 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:text-blue-400 dark:border-blue-800'; break;
            case 'Asignado': statusClass = 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-400 dark:border-indigo-800'; break;
            case 'En Proceso': statusClass = 'bg-cyan-100 text-cyan-700 border-cyan-200 dark:bg-cyan-900/40 dark:text-cyan-400 dark:border-cyan-800'; break;
            case 'Espera': statusClass = 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-400 dark:border-amber-800'; break;
            case 'Resuelto': statusClass = 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400 dark:border-emerald-800'; break;
            case 'Cerrado': statusClass = 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'; break;
            case 'Cancelado': statusClass = 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-400 dark:border-rose-800'; break;
        }

        const agenteHtml = t.agente_nombre 
            ? `<div class="flex items-center gap-2"><img src="/HelpDesk/assets/img/avatars/${t.agente_avatar || 'default.png'}" class="w-6 h-6 rounded-full"><span class="truncate">${t.agente_nombre}</span></div>`
            : `<div class="flex items-center gap-2 opacity-50"><div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px]">?</div><span class="italic">Sin Asignar</span></div>`;

        // --- LÓGICA DE BOTONES DE ACCIÓN ---
        let actionButtons = '';
        
        // Convertimos a número para asegurar una comparación perfecta
        const myId = Number(CURRENT_USER_ID);
        const asignadoA = t.agente_id ? Number(t.agente_id) : null;

        if (!asignadoA) {
            // 1. No tiene agente asignado -> Mostrar botón para TOMAR el ticket (Mano)
            actionButtons = `
                <button onclick="takeTicket(${t.id})" class="flex items-center gap-2 text-emerald-600 hover:text-emerald-800 transition p-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-xs font-bold" title="Tomar este ticket">
                    <i class="fas fa-hand-paper text-lg"></i> Tomar
                </button>
            `;
        } else if (asignadoA === myId) {
            // 2. Está asignado al usuario actual -> Mostrar botón para VER (Ojo)
            actionButtons = `
                <a href="../../public/tickets/ticket-detail.php?id=${t.id}" class="flex items-center gap-2 text-blue-600 hover:text-blue-800 transition p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 text-xs font-bold" title="Ver detalles">
                    <i class="fas fa-eye text-lg"></i> Ver
                </a>
            `;
        } else {
            // 3. Está asignado, pero a OTRO usuario -> Mostrar etiqueta
            actionButtons = `
                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-xs font-medium dark:bg-slate-700 dark:text-slate-400 border border-slate-200 dark:border-slate-600">
                    <i class="fas fa-lock mr-1"></i> Ya asignado
                </span>
            `;
        }

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
                    <div class="flex justify-center items-center">
                        ${actionButtons}
                    </div>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// --- FUNCIÓN PARA TOMAR EL TICKET ---
async function takeTicket(ticketId) {
    const result = await Swal.fire({
        title: '¿Tomar este ticket?',
        text: "Serás asignado como el responsable para resolverlo.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981', // Verde emerald
        cancelButtonColor: '#94a3b8', // Gris slate
        confirmButtonText: 'Sí, tomarlo',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('ticket_id', ticketId);

        const response = await fetch('/HelpDesk/db/agent/tickets/take-ticket.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();

        if (res.success) {
            // 1. Recargamos la tabla silenciosamente "por debajo"
            // Esto asegura que si el usuario presiona "Atrás" en el navegador, 
            // la tabla ya tendrá el nuevo estado (con el Ojo en lugar de la Mano).
            loadTickets();

            // 2. Mostramos la alerta de éxito
            Swal.fire({
                icon: 'success',
                title: '¡Ticket tuyo!',
                text: 'Redirigiendo a los detalles...',
                timer: 1000, // Reduje el tiempo un poco para que sea más rápido
                showConfirmButton: false
            }).then(() => {
                // 3. Y finalmente, redirigimos a la vista de detalles
                window.location.href = `../../public/tickets/ticket-detail.php?id=${ticketId}`;
            });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Hubo un problema de conexión', 'error');
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