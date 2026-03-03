// Ruta a tu nuevo archivo PHP
const API_TICKETS = '/HelpDesk/db/user/tickets/get_my_tickets.php';

// Variables globales para guardar los datos y controlar la paginación
let allTickets = [];
let filteredTickets = [];
let currentPage = 1;
const ticketsPerPage = 6; // Cuántos tickets mostrar por página en la vista principal

// Función para obtener el color de la Prioridad
function getPriorityBadge(prioridad) {
    switch (prioridad) {
        case 'Baja': return '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-500">Baja</span>';
        case 'Media': return '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-500">Media</span>';
        case 'Alta': return '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-500">Alta</span>';
        case 'Crítica': return '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-500">Crítica</span>';
        default: return `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">${prioridad}</span>`;
    }
}

// Función para obtener el color del Estado
function getStatusIndicator(estado) {
    let colorClass = 'bg-slate-400';
    if(estado === 'Abierto') colorClass = 'bg-red-500';
    if(estado === 'Asignado') colorClass = 'bg-blue-500';
    if(estado === 'En Proceso') colorClass = 'bg-indigo-500';
    if(estado === 'Espera') colorClass = 'bg-yellow-500';
    if(estado === 'Resuelto') colorClass = 'bg-teal-500';
    if(estado === 'Cerrado') colorClass = 'bg-green-500';
    if(estado === 'Cancelado') colorClass = 'bg-orange-500';

    return `<span class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                <span class="w-1.5 h-1.5 rounded-full ${colorClass}"></span> ${estado}
            </span>`;
}

// 1. TRAER LOS DATOS DEL SERVIDOR (Y aplicar filtros si los hay)
function fetchTicketsData() {
    fetch(API_TICKETS)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                allTickets = res.data;
                applyFilters(); // Una vez que tenemos los datos, aplicamos los filtros y dibujamos
            }
        });
}

// 2. APLICAR LOS FILTROS
function applyFilters() {
    const searchTerm = document.getElementById('my-filter-search')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('my-filter-status')?.value || 'all';
    const priorityFilter = document.getElementById('my-filter-priority')?.value || 'all';
    const startDate = document.getElementById('my-filter-date-start')?.value || '';
    const endDate = document.getElementById('my-filter-date-end')?.value || '';

    filteredTickets = allTickets.filter(ticket => {
        // 1. Filtro de búsqueda (Busca en Folio, Título o Agente)
        const matchSearch = ticket.folio.toLowerCase().includes(searchTerm) || 
                            ticket.titulo.toLowerCase().includes(searchTerm) ||
                            ticket.agente.toLowerCase().includes(searchTerm);
        
        // 2. Filtro de Estado
        // Nota: En tu select los values están en minúsculas ('abierto', 'en proceso'),
        // pero en tu BD están con mayúsculas ('Abierto', 'En Proceso').
        const matchStatus = statusFilter === 'all' || ticket.estado.toLowerCase() === statusFilter.toLowerCase();
        
        // 3. Filtro de Prioridad
        const matchPriority = priorityFilter === 'all' || ticket.prioridad.toLowerCase() === priorityFilter.toLowerCase();
        
        // 4. Filtros de Fecha (Requieren convertir el formato dd/mm/yyyy a Objeto Date)
        let matchDate = true;
        if (startDate || endDate) {
            const parts = ticket.fecha.split('/'); // Asumiendo formato DD/MM/YYYY
            const ticketDate = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`); // YYYY-MM-DD
            
            if (startDate) {
                matchDate = matchDate && (ticketDate >= new Date(startDate));
            }
            if (endDate) {
                const end = new Date(endDate);
                end.setHours(23, 59, 59); // Cubrir todo el día
                matchDate = matchDate && (ticketDate <= end);
            }
        }

        return matchSearch && matchStatus && matchPriority && matchDate;
    });

    currentPage = 1; // Si filtraste, regresamos a la página 1
    renderTables();
}

// 3. DIBUJAR LAS TABLAS Y LA PAGINACIÓN
function renderTables() {
    const dashboardTable = document.getElementById('dashboard-tickets-tbody');
    const myTicketsTable = document.getElementById('my-tickets-table-body');
    
    // --- RENDER TABLA 1 (DASHBOARD) ---
    if (dashboardTable) {
        let dashboardHtml = '';
        // Mostramos solo los 5 más recientes del total de tickets (sin filtros)
        const top5 = allTickets.slice(0, 6); 
        
        if (top5.length === 0) {
            dashboardHtml = `<tr><td colspan="7" class="text-center py-6 text-slate-400 italic">No tienes tickets registrados.</td></tr>`;
        } else {
            top5.forEach(ticket => dashboardHtml += createTicketRow(ticket));
        }
        dashboardTable.innerHTML = dashboardHtml;
    }

    // --- RENDER TABLA 2 (MIS TICKETS CON FILTROS Y PAGINACIÓN) ---
    if (myTicketsTable) {
        let myTicketsHtml = '';
        
        // Calcular Paginación
        const totalItems = filteredTickets.length;
        const totalPages = Math.ceil(totalItems / ticketsPerPage) || 1;
        const startIndex = (currentPage - 1) * ticketsPerPage;
        const endIndex = startIndex + ticketsPerPage;
        
        const ticketsToShow = filteredTickets.slice(startIndex, endIndex);

        if (ticketsToShow.length === 0) {
            myTicketsHtml = `<tr><td colspan="8" class="text-center py-6 text-slate-400 italic">No se encontraron tickets con esos filtros.</td></tr>`;
        } else {
            ticketsToShow.forEach(ticket => myTicketsHtml += createTicketRow(ticket));
        }
        
        myTicketsTable.innerHTML = myTicketsHtml;
        
        // Dibujar controles de paginación
        renderPaginationControls(totalItems, totalPages, startIndex, endIndex);
    }
}

// Plantilla HTML de la fila
function createTicketRow(ticket) {
    const actionButton = `<a href="../../public/tickets/ticket-detail.php?id=${ticket.id}" class="inline-block text-slate-400 hover:text-blue-600 transition-colors p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20"><i class="fas fa-external-link-alt"></i></a>`;
    return `
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
            <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">${ticket.folio}</td>
            <td class="py-3 px-4 font-medium truncate max-w-[150px]">${ticket.titulo}</td>
            <td class="py-3 px-4 truncate max-w-[120px]">${ticket.empleado}</td>
            <td class="py-3 px-4 truncate max-w-[120px] ${ticket.agente === 'Sin asignar' ? 'text-slate-400 italic' : ''}">${ticket.agente}</td>
            <td class="py-3 px-4">${getPriorityBadge(ticket.prioridad)}</td>
            <td class="py-3 px-4">${getStatusIndicator(ticket.estado)}</td>
            <td class="py-3 px-4 text-xs text-slate-500">${ticket.fecha}</td>
            <td class="py-3 px-4 text-center">${actionButton}</td>
        </tr>
    `;
}

// 4. CONTROLES DE PAGINACIÓN
function renderPaginationControls(totalItems, totalPages, startIndex, endIndex) {
    const infoSpan = document.getElementById('my-pagination-info');
    const controlsDiv = document.getElementById('my-pagination-controls');
    
    if(!infoSpan || !controlsDiv) return;

    // Texto de info (Ej: Mostrando 1 al 5 de 12)
    const actualEnd = Math.min(endIndex, totalItems);
    infoSpan.innerText = totalItems > 0 ? `Mostrando ${startIndex + 1} al ${actualEnd} de ${totalItems} tickets` : 'Mostrando 0 tickets';

    // Botones
    let btnsHtml = `
        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="w-8 h-8 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-slate-700 dark:hover:bg-slate-800 transition">
            <i class="fas fa-chevron-left text-xs"></i>
        </button>
    `;
    
    // Crear numeritos de páginas (simplificado)
    for(let i = 1; i <= totalPages; i++) {
        btnsHtml += `
            <button onclick="changePage(${i})" class="w-8 h-8 flex items-center justify-center rounded border text-xs font-medium transition ${currentPage === i ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'}">
                ${i}
            </button>
        `;
    }

    btnsHtml += `
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="w-8 h-8 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-slate-700 dark:hover:bg-slate-800 transition">
            <i class="fas fa-chevron-right text-xs"></i>
        </button>
    `;

    controlsDiv.innerHTML = btnsHtml;
}

function changePage(newPage) {
    currentPage = newPage;
    renderTables();
}

// 5. LIMPIAR FILTROS
function resetMyTicketFilters() {
    document.getElementById('my-filter-search').value = '';
    document.getElementById('my-filter-status').value = 'all';
    document.getElementById('my-filter-priority').value = 'all';
    document.getElementById('my-filter-date-start').value = '';
    document.getElementById('my-filter-date-end').value = '';
    applyFilters();
}

// --- INICIALIZACIÓN Y EVENT LISTENERS ---
document.addEventListener("DOMContentLoaded", () => {
    
    // Configurar listeners de los filtros
    const searchInput = document.getElementById('my-filter-search');
    if(searchInput) searchInput.addEventListener('keyup', applyFilters); // Busca mientras escribes
    
    const selects = ['my-filter-status', 'my-filter-priority', 'my-filter-date-start', 'my-filter-date-end'];
    selects.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.addEventListener('change', applyFilters); // Busca al cambiar opciones
    });

    // Cargar datos por primera vez
    fetchTicketsData();

    // Actualizar en tiempo real cada 10 segundos
    setInterval(() => {
        fetchTicketsData();
    }, 10000);
});