// assets/js/admin/usuarios/users-view.js

let filtersLoaded = false;

document.addEventListener('DOMContentLoaded', () => {
    loadUsersTable();
});

// --- FUNCIÓN PRINCIPAL DE CARGA ---
async function loadUsersTable() {
    // Referencia a los inputs con los nuevos IDs (usr-...)
    const inputSearch = document.getElementById('usr-filter-search');
    const inputRole = document.getElementById('usr-filter-role');
    const inputStatus = document.getElementById('usr-filter-status');

    // Validación de seguridad
    if (!inputSearch || !inputRole || !inputStatus) {
        console.error("Error: No encuentro los inputs con ID 'usr-filter-...'. Revisa tu HTML en index.php");
        return;
    }

    const search = inputSearch.value;
    const role = inputRole.value;
    const status = inputStatus.value;

    const url = `/HelpDesk/db/admin/usuarios/get-usuarios-data.php?search=${encodeURIComponent(search)}&rol=${role}&estado=${status}`;

    try {
        const response = await fetch(url);
        // Leemos texto primero para evitar errores de parseo si PHP falla
        const text = await response.text(); 
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                // 1. Actualizar contadores (KPIs)
                updateKPIs(data.kpis);
                
                // 2. Llenar selects (solo la primera vez)
                if (!filtersLoaded) {
                    fillFilters(data.meta);
                    filtersLoaded = true;
                }

                // 3. Pintar la tabla
                renderUsersTable(data.usuarios);

            } else {
                console.error("Error backend:", data.message);
            }
        } catch (e) {
            console.error("Error parseando JSON:", e);
            console.log("Respuesta recibida:", text);
        }

    } catch (error) {
        console.error("Error conexión:", error);
    }
}

// --- 1. ACTUALIZAR CONTADORES ---
function updateKPIs(kpis) {
    if (!kpis) return;
    // Usamos || 0 por seguridad si viene null
    const totalEl = document.getElementById('kpi-total');
    const activeEl = document.getElementById('kpi-active');
    const suspendedEl = document.getElementById('kpi-suspended');
    const inactiveEl = document.getElementById('kpi-inactive');

    if (totalEl) totalEl.innerText = kpis.total || 0;
    if (activeEl) activeEl.innerText = kpis.activos || 0;
    if (suspendedEl) suspendedEl.innerText = kpis.suspendidos || 0;
    if (inactiveEl) inactiveEl.innerText = kpis.inactivos || 0;
}

// --- 2. LLENAR FILTROS (SELECTS) ---
function fillFilters(meta) {
    if (!meta) return;
    const roleSelect = document.getElementById('usr-filter-role');
    const statusSelect = document.getElementById('usr-filter-status');

    // Reiniciamos los selects manteniendo la opción "Todos"
    if (roleSelect) roleSelect.innerHTML = '<option value="0">Todos</option>';
    if (statusSelect) statusSelect.innerHTML = '<option value="0">Todos</option>';

    if (meta.roles && roleSelect) {
        meta.roles.forEach(r => {
            roleSelect.innerHTML += `<option value="${r.id}">${r.nombre}</option>`;
        });
    }

    if (meta.estados && statusSelect) {
        meta.estados.forEach(e => {
            statusSelect.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
        });
    }
}

// --- 3. RENDERIZAR TABLA ---
function renderUsersTable(usuarios) {
    const tbody = document.getElementById('users-table-body');
    if (!tbody) return;

    if (!usuarios || usuarios.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-slate-400 text-xs">No se encontraron usuarios.</td></tr>`;
        return;
    }

    let html = '';
    usuarios.forEach(u => {
        // Lógica de Avatar
        const avatarUrl = u.avatar 
            ? `/HelpDesk/assets/img/avatars/${u.avatar}` 
            : `https://api.dicebear.com/7.x/avataaars/svg?seed=${u.username}`;
        
        // Lógica de Fecha
        const fecha = new Date(u.fecha_creacion).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });

        // Lógica de Estado (Badges)
        let statusBadge = '';
        if (u.estado_id == 1) statusBadge = '<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"><span class="w-1.5 h-1.5 rounded-full bg-green-600"></span> Activo</span>';
        else if (u.estado_id == 2) statusBadge = '<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400"><span class="w-1.5 h-1.5 rounded-full bg-orange-600"></span> Suspendido</span>';
        else statusBadge = '<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400"><span class="w-1.5 h-1.5 rounded-full bg-red-600"></span> Inactivo</span>';

        // Lógica de Rol (Badges simples)
        let roleColor = 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
        if (u.nombre_rol === 'Administrador') roleColor = 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300';
        if (u.nombre_rol === 'Agente') roleColor = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';

        html += `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition border-b border-slate-50 dark:border-slate-800">
                <td class="p-4 flex items-center gap-3">
                    <img src="${avatarUrl}" class="w-9 h-9 rounded-full border border-slate-200 bg-white object-cover">
                    <div>
                        <p class="font-bold text-slate-800 dark:text-white text-sm">${u.firstname} ${u.firstapellido}</p>
                        <p class="text-[10px] text-slate-400">${u.email} • ${u.folio || 'S/N'}</p>
                    </div>
                </td>
                <td class="p-4">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase ${roleColor}">${u.nombre_rol || 'Sin Rol'}</span>
                </td>
                <td class="p-4 text-slate-600 dark:text-slate-300 text-xs">${u.nombre_sucursal || 'Sin Asignar'}</td>
                <td class="p-4 text-slate-500 text-xs">${fecha}</td>
                <td class="p-4">${statusBadge}</td>
                <td class="p-4 text-right">
                    <button onclick="openUserModal('edit', ${u.id})" class="text-slate-400 hover:text-blue-600 p-2 transition"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// --- 4. RESETEAR FILTROS ---
function resetUserFilters() {
    const s = document.getElementById('usr-filter-search');
    const r = document.getElementById('usr-filter-role');
    const st = document.getElementById('usr-filter-status');

    if (s) s.value = '';
    if (r) r.value = '0';
    if (st) st.value = '0';
    
    loadUsersTable();
}