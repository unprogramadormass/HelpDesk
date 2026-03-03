// assets/js/tickets/my-tickets.view.js

let currentMyPage = 1;
let myDebounceTimer;

document.addEventListener('DOMContentLoaded', () => {
    // Cargar mis tickets al iniciar
    loadMyTickets();

    // Event Listeners
    document.getElementById('my-filter-search').addEventListener('input', () => {
        clearTimeout(myDebounceTimer);
        myDebounceTimer = setTimeout(() => {
            currentMyPage = 1;
            loadMyTickets();
        }, 300);
    });

    ['my-filter-status', 'my-filter-priority', 'my-filter-date-start', 'my-filter-date-end'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            currentMyPage = 1; 
            loadMyTickets();
        });
    });
});

async function loadMyTickets() {
    const search = document.getElementById('my-filter-search').value;
    const status = document.getElementById('my-filter-status').value;
    const priority = document.getElementById('my-filter-priority').value;
    const dateStart = document.getElementById('my-filter-date-start').value;
    const dateEnd = document.getElementById('my-filter-date-end').value;

    const url = `/HelpDesk/db/admin/tickets/get-my-tickets.php?page=${currentMyPage}&search=${search}&estado=${status}&prioridad=${priority}&date_start=${dateStart}&date_end=${dateEnd}`;

    try {
        const response = await fetch(url);
        const res = await response.json();

        if (res.success) {
            renderMyTicketsTable(res.data);
            renderMyPagination(res.meta);
        } else {
            console.error("Error backend:", res.message);
        }
    } catch (error) {
        console.error("Error conexión:", error);
    }
}

function renderMyTicketsTable(tickets) {
    const tbody = document.getElementById('my-tickets-table-body');
    if (!tbody) return;

    if (tickets.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-slate-400">No tienes tickets asignados ni creados.</td></tr>`;
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

        // Badge "Soy Agente" o "Soy Creador"
        let roleBadge = '';
        if (t.mi_rol === 'Agente') roleBadge = '<span class="ml-2 text-[9px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded border border-purple-200">AGENTE</span>';
        if (t.mi_rol === 'Creador') roleBadge = '<span class="ml-2 text-[9px] bg-sky-100 text-sky-700 px-1.5 py-0.5 rounded border border-sky-200">CREADOR</span>';

        const agenteHtml = t.agente_nombre 
            ? `<div class="flex items-center gap-2">
                 <img src="/HelpDesk/assets/img/avatars/${t.agente_avatar || 'default.png'}" class="w-6 h-6 rounded-full" onerror="this.src='https://api.dicebear.com/7.x/avataaars/svg?seed=${t.agente_nombre}'">
                 <span class="truncate">${t.agente_nombre}</span>
               </div>`
            : `<div class="flex items-center gap-2 opacity-50"><div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px]">?</div><span class="italic">Sin Asignar</span></div>`;

        html += `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition relative group border-b border-slate-50 dark:border-slate-700">
                <td class="p-4 font-mono text-blue-600 dark:text-blue-400 text-xs font-bold">${t.folio}</td>
                <td class="p-4">
                    <span class="font-medium block text-slate-700 dark:text-slate-200 flex items-center">
                        ${t.titulo}
                    </span>
                    <span class="text-xs text-slate-400 truncate max-w-[200px] block">${t.descripcion ? t.descripcion.substring(0, 40) + '...' : ''}</span>
                </td>
                <td class="p-4">
                    <div class="flex items-center gap-2">
                        <img src="${t.creador_avatar_url}" class="w-6 h-6 rounded-full border border-slate-200"> 
                        <span class="truncate text-xs font-medium">${t.creador_nombre}</span>
                    </div>
                </td>
                <td class="p-4 text-xs">${agenteHtml}</td>
                <td class="p-4">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border ${prioClass}">${t.prioridad}</span>
                </td>
                <td class="p-4">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border ${statusClass}">${t.estado}</span>
                </td>
                <td class="p-4 text-slate-500 text-xs whitespace-nowrap">${t.fecha_formateada}</td>
                
                <td class="p-4 text-center relative">
                    <a href="../../../pages/public/tickets/ticket-detail.php?id=${t.id}" class="text-slate-400 hover:text-blue-600 transition"><i class="fas fa-eye"></i></a>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function renderMyPagination(meta) {
    const info = document.getElementById('my-pagination-info');
    const controls = document.getElementById('my-pagination-controls');
    
    info.innerHTML = `Mostrando <span class="font-bold text-slate-700 dark:text-white">${meta.start_index}</span> a <span class="font-bold text-slate-700 dark:text-white">${meta.end_index}</span> de <span class="font-bold text-slate-700 dark:text-white">${meta.total_records}</span> tickets`;

    let html = '';
    const prevDisabled = meta.current_page === 1 ? 'disabled opacity-50 cursor-not-allowed' : '';
    html += `<button onclick="changeMyPage(${meta.current_page - 1})" ${prevDisabled} class="px-3 py-1 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400"><i class="fas fa-chevron-left"></i></button>`;

    for (let i = 1; i <= meta.total_pages; i++) {
        if (i === 1 || i === meta.total_pages || (i >= meta.current_page - 1 && i <= meta.current_page + 1)) {
            const activeClass = i === meta.current_page 
                ? 'bg-blue-50 border-blue-200 text-blue-600 dark:bg-slate-700 dark:text-white' 
                : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700';
            
            html += `<button onclick="changeMyPage(${i})" class="px-3 py-1 text-xs font-medium border rounded-lg transition ${activeClass}">${i}</button>`;
        } else if (i === meta.current_page - 2 || i === meta.current_page + 2) {
            html += `<span class="px-1 text-slate-400">...</span>`;
        }
    }

    const nextDisabled = meta.current_page === meta.total_pages ? 'disabled opacity-50 cursor-not-allowed' : '';
    html += `<button onclick="changeMyPage(${meta.current_page + 1})" ${nextDisabled} class="px-3 py-1 text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 dark:bg-slate-800 dark:border-slate-700"><i class="fas fa-chevron-right"></i></button>`;

    controls.innerHTML = html;
}

function changeMyPage(page) {
    if (page < 1) return;
    currentMyPage = page;
    loadMyTickets();
}

function resetMyTicketFilters() {
    document.getElementById('my-filter-search').value = '';
    document.getElementById('my-filter-status').value = 'all';
    document.getElementById('my-filter-priority').value = 'all';
    document.getElementById('my-filter-date-start').value = '';
    document.getElementById('my-filter-date-end').value = '';
    currentMyPage = 1;
    loadMyTickets();
}