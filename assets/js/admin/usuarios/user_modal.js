let catalogosLoaded = false;

// --- ABRIR MODAL (CREAR O EDITAR) ---
async function openUserModal(mode = 'create', userId = null) {
    const modal = document.getElementById('userModal');
    const title = document.getElementById('userModalTitle');
    const form = document.getElementById('userForm');
    
    // 1. Asegurar que los catálogos (Selects) existan antes de intentar seleccionar un valor
    if (!catalogosLoaded) {
        await loadCatalogos();
    }

    // 2. Resetear todo el formulario para empezar limpio
    form.reset();
    document.getElementById('user-id').value = '';
    
    // Desmarcar todos los checkboxes de permisos
    const allPerms = document.querySelectorAll('input[name="permisos_check"]');
    allPerms.forEach(cb => cb.checked = false);

    // Limpiar selección múltiple de áreas
    const areaSelect = document.getElementById('area_id');
    if (areaSelect) {
        Array.from(areaSelect.options).forEach(opt => opt.selected = false);
        renderCustomMultiSelectUI(); // <-- NUEVO: Limpiamos los chips visualmente
    }

    if (mode === 'edit' && userId) {
        title.innerText = "Editar Usuario";
        document.getElementById('user-id').value = userId;

        // 3. Cargar datos del usuario
        try {
            const response = await fetch(`/HelpDesk/db/admin/usuarios/get-user-detail.php?id=${userId}`);
            const res = await response.json();

            if (res.success) {
                const u = res.data;

                // --- LLENAR INPUTS DE TEXTO ---
                document.getElementById('firstname').value = u.firstname;
                document.getElementById('secondname').value = u.secondname || '';
                document.getElementById('firstapellido').value = u.firstapellido;
                document.getElementById('secondapellido').value = u.secondapellido || '';
                document.getElementById('email').value = u.email;
                document.getElementById('celular').value = u.celular || '';
                document.getElementById('extension').value = u.extension || '';
                
                // --- LLENAR INPUTS DE ACCESO ---
                document.getElementById('username').value = u.username;
                
                // --- SELECCIONAR EN LISTAS DESPLEGABLES ---
                if(u.tipo_usuario) document.getElementById('rol_id').value = u.tipo_usuario;
                if(u.puesto_id) document.getElementById('puesto_id').value = u.puesto_id;
                if(u.sucursal_id) document.getElementById('sucursal_id').value = u.sucursal_id;
                if(u.incidencia_id) document.getElementById('incidencia_id').value = u.incidencia_id;
                if(u.estado_id) document.getElementById('status_id').value = u.estado_id;

                // --- NUEVO: MARCAR ÁREAS MÚLTIPLES ---
                if (u.areas_asignadas && Array.isArray(u.areas_asignadas)) {
                    u.areas_asignadas.forEach(areaId => {
                        const option = areaSelect.querySelector(`option[value="${areaId}"]`);
                        if (option) option.selected = true;
                    });
                    renderCustomMultiSelectUI(); // <-- NUEVO: Dibujamos los chips del usuario a editar
                }

                // --- MARCAR CHECKBOXES DE NOTIFICACIONES ---
                if (form.querySelector('input[name="noti_whatsapp"]')) 
                    form.querySelector('input[name="noti_whatsapp"]').checked = (u.noti_whatsapp == 1);
                
                if (form.querySelector('input[name="noti_email"]')) 
                    form.querySelector('input[name="noti_email"]').checked = (u.noti_email == 1);
                
                if (form.querySelector('input[name="noti_nuevo"]')) 
                    form.querySelector('input[name="noti_nuevo"]').checked = (u.noti_nuevo == 1);
                
                if (form.querySelector('input[name="noti_sistema"]')) 
                    form.querySelector('input[name="noti_sistema"]').checked = (u.noti_sistema == 1);

                // --- MARCAR PERMISOS ASIGNADOS ---
                if (u.permisos_asignados && Array.isArray(u.permisos_asignados)) {
                    u.permisos_asignados.forEach(permId => {
                        const checkbox = document.querySelector(`input[name="permisos_check"][value="${permId}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                }

            } else {
                showToast("Error al cargar datos del usuario", "error");
            }
        } catch (error) {
            console.error(error);
            showToast("Error de conexión al obtener detalles", "error");
        }

    } else {
        title.innerText = "Nuevo Usuario";
    }

    // Mostrar Modal
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100', 'pointer-events-auto');
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
}

// --- CARGAR SELECTS Y PERMISOS ---
async function loadCatalogos() {
    try {
        const response = await fetch('/HelpDesk/db/admin/usuarios/get-catalogo-usuario.php');
        const res = await response.json();

        if (res.success) {
            const data = res.data;

            // Helper modificado para considerar el select múltiple
            const fill = (id, list) => {
                const sel = document.getElementById(id);
                if(!sel) return;
                
                // Si es un select múltiple, no le ponemos la opción de "Seleccione..."
                if (sel.multiple) {
                    sel.innerHTML = '';
                } else {
                    sel.innerHTML = '<option value="">Seleccione...</option>';
                }
                
                list.forEach(item => {
                    const text = item.nombre || item.nombre_mostrar; 
                    sel.innerHTML += `<option value="${item.id}">${text}</option>`;
                });
            };

            fill('rol_id', data.roles);
            fill('puesto_id', data.puestos);
            
            fill('area_id', data.areas); // Llenará el select nativo oculto
            renderCustomMultiSelectUI(); // <-- NUEVO: Inicializamos la vista personalizada
            
            fill('sucursal_id', data.sucursales);
            fill('incidencia_id', data.niveles);

            // Generar Checkboxes de Permisos Dinámicamente
            const permContainer = document.getElementById('permisos-container');
            let permHtml = '';
            data.permisos.forEach(p => {
                permHtml += `
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 p-1 rounded">
                        <input type="checkbox" name="permisos_check" value="${p.id}" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span>${p.descripcion}</span>
                    </label>
                `;
            });
            permContainer.innerHTML = permHtml;

            catalogosLoaded = true;
        }
    } catch (error) {
        console.error("Error cargando catálogos:", error);
    }
}

// --- GUARDAR USUARIO ---
async function saveUser() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form); 
    const data = Object.fromEntries(formData.entries()); 

    // Agregar Checkboxes de Permisos
    const permisosChecks = document.querySelectorAll('input[name="permisos_check"]:checked');
    data.permisos = Array.from(permisosChecks).map(cb => cb.value);

    // --- Extraer áreas del select múltiple nativo ---
    const areaSelect = document.getElementById('area_id');
    if(areaSelect) {
        const selectedAreas = Array.from(areaSelect.selectedOptions);
        data.area_ids = selectedAreas.map(opt => opt.value);
    } else {
        data.area_ids = [];
    }

    // Agregar Checkboxes de Notificaciones
    data.noti_whatsapp = form.querySelector('input[name="noti_whatsapp"]').checked;
    data.noti_email = form.querySelector('input[name="noti_email"]').checked;
    data.noti_nuevo = form.querySelector('input[name="noti_nuevo"]').checked;
    data.noti_sistema = form.querySelector('input[name="noti_sistema"]').checked;

    if (!data.firstname || !data.firstapellido || !data.email || !data.username || !data.rol_id || !data.sucursal_id) {
        showToast("Complete los campos obligatorios (*)", "error");
        return;
    }

    try {
        const btn = document.querySelector("button[onclick='saveUser()']");
        btn.innerText = "Guardando...";
        btn.disabled = true;

        const response = await fetch('/HelpDesk/db/admin/usuarios/save-usuario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeUserModal();
            loadUsersTable(); 
        } else {
            showToast(result.message, 'error');
        }

    } catch (error) {
        console.error(error);
        showToast("Error de conexión", "error");
    } finally {
        const btn = document.querySelector("button[onclick='saveUser()']");
        btn.innerText = "Guardar Usuario";
        btn.disabled = false;
    }
}

// --- LÓGICA PARA EL SELECT MÚLTIPLE PERSONALIZADO (CHIPS) ---
function renderCustomMultiSelectUI() {
    const select = document.getElementById('area_id');
    const badgesContainer = document.getElementById('custom-select-badges');
    const dropdown = document.getElementById('custom-select-dropdown');
    
    if(!select || !badgesContainer || !dropdown) return;

    badgesContainer.innerHTML = ''; 
    dropdown.innerHTML = ''; 

    const options = Array.from(select.options);
    let hasSelection = false;

    options.forEach(option => {
        const isSelected = option.selected;
        if (isSelected) hasSelection = true;

        // 1. Crear elemento en el menú desplegable
        const div = document.createElement('div');
        div.className = `px-3 py-2 cursor-pointer text-sm flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors ${isSelected ? 'text-blue-600 font-medium bg-blue-50 dark:bg-slate-700/50' : 'text-slate-700 dark:text-slate-300'}`;
        div.innerHTML = `
            <span>${option.text}</span>
            ${isSelected ? '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : ''}
        `;
        
        div.onclick = (e) => {
            e.stopPropagation();
            option.selected = !option.selected; 
            renderCustomMultiSelectUI(); 
        };
        dropdown.appendChild(div);

        // 2. Crear la etiqueta (Chip)
        if (isSelected) {
            const badge = document.createElement('span');
            badge.className = "inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium border border-slate-300 dark:border-slate-600 shadow-sm";
            badge.innerHTML = `
                ${option.text}
                <button type="button" class="remove-badge ml-1 text-slate-400 hover:text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-full focus:outline-none transition-colors">
                    <svg class="w-3 h-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            `;
            
            badge.querySelector('.remove-badge').onclick = (e) => {
                e.stopPropagation();
                option.selected = false;
                renderCustomMultiSelectUI();
            };
            badgesContainer.appendChild(badge);
        }
    });

    if (!hasSelection) {
        badgesContainer.innerHTML = '<span class="text-slate-400 ml-1 text-xs">Seleccione áreas...</span>';
    }
}

// Eventos de carga inicial
document.addEventListener('DOMContentLoaded', () => {
    // 1. Prevenir Enter en el formulario
    const form = document.getElementById('userForm');
    if(form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            saveUser();
        });
    }

    // 2. Configurar el dropdown personalizado
    const trigger = document.getElementById('custom-select-trigger');
    const dropdown = document.getElementById('custom-select-dropdown');

    if (trigger && dropdown) {
        trigger.addEventListener('click', (e) => {
            if(e.target.closest('.remove-badge')) return; 
            dropdown.classList.toggle('hidden');
            dropdown.classList.toggle('flex');
        });

        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
                dropdown.classList.remove('flex');
            }
        });
    }
});