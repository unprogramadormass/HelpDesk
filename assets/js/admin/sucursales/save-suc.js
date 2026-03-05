function openBranchModal(mode = 'create', data = {}) {
    const modal = document.getElementById('branchModal');
    const title = document.getElementById('branchModalTitle');
    const inpId = document.getElementById('branch-id');
    
    // Referencias a inputs
    const inpName = document.getElementById('branch-name');
    const inpCode = document.getElementById('branch-code');
    const inpAddress = document.getElementById('branch-address');
    const inpPhone = document.getElementById('branch-phone');
    const inpManager = document.getElementById('branch-manager');
    const inpStatus = document.getElementById('branch-status');

    if (mode === 'edit') {
        title.innerText = "Editar Sucursal";
        // Llenar datos si estamos editando
        inpId.value = data.id || ''; 
        inpName.value = data.name || '';
        inpCode.value = data.code || '';
        inpAddress.value = data.address || '';
        inpPhone.value = data.phone || '';
        inpManager.value = data.manager || '';
        inpStatus.value = data.status || 'OPERATIVA';
    } else {
        title.innerText = "Nueva Sucursal";
        document.getElementById('branchForm').reset(); // Limpiar formulario
        inpId.value = '';
    }

    // Mostrar Modal con animación
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100', 'pointer-events-auto');
}

function closeBranchModal() {
    const modal = document.getElementById('branchModal');
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
}

// --- LÓGICA DE GUARDADO (BACKEND) ---

async function saveBranch() {
    // 1. Obtener los valores de los inputs
    const branchData = {
        id: document.getElementById('branch-id').value, 
        nombre: document.getElementById('branch-name').value,
        folio: document.getElementById('branch-code').value,
        direccion: document.getElementById('branch-address').value,
        telefono: document.getElementById('branch-phone').value,
        estatus: document.getElementById('branch-status').value
    };

    // Validación simple desde el cliente
    if (!branchData.nombre || !branchData.folio) {
        showToast("El nombre y el código son obligatorios", "error");
        return;
    }

    try {
        // 2. Enviar datos al PHP
        const response = await fetch('../../../db/admin/sucursales/save-suc.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(branchData)
        });

        // 3. Procesar respuesta del servidor
        const result = await response.json();

        if (result.success) {
            // ÉXITO: Verde y cerrar
            showToast(result.message, 'success');
            closeBranchModal();
            
            lastDataSnapshot = ''; 
            loadBranches();
        } else {
            // ERROR LÓGICO (BD): Rojo
            showToast(result.message, 'error');
        }

    } catch (error) {
        console.error("Error de red:", error);
        showToast("Error al conectar con el servidor", "error");
    }
}

// --- SISTEMA DE NOTIFICACIONES (TOASTS) ---

function showToast(message, type) {
    const container = document.getElementById('toast-container');
    
    // Crear el elemento
    const toast = document.createElement('div');
    
    // Estilos base
    let colors = type === 'success' 
        ? 'bg-green-600 border-green-700 text-white' 
        : 'bg-red-600 border-red-700 text-white';
        
    // Icono
    let icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';

    toast.className = `${colors} flex items-center gap-3 px-6 py-4 rounded-lg shadow-2xl border transform transition-all duration-300 translate-x-10 opacity-0 min-w-[300px]`;
    toast.innerHTML = `
        <span class="text-xl">${icon}</span>
        <span class="font-medium text-sm">${message}</span>
    `;

    // Añadir al DOM
    container.appendChild(toast);

    // Animar entrada
    setTimeout(() => {
        toast.classList.remove('translate-x-10', 'opacity-0');
    }, 10);

    // Eliminar después de 4 segundos
    setTimeout(() => {
        toast.classList.add('translate-x-10', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}


// Variable global para el control de parpadeo
let lastDataSnapshot = '';

document.addEventListener('DOMContentLoaded', () => {
    loadBranches(); 
    setInterval(loadBranches, 3000); 
});


async function loadBranches() {
    try {
        const response = await fetch('../../../db/admin/sucursales/get-suc.php');
        const branches = await response.json();
        
        // Anti-parpadeo
        const currentDataSnapshot = JSON.stringify(branches);
        if (currentDataSnapshot === lastDataSnapshot) return; 
        lastDataSnapshot = currentDataSnapshot;

        const container = document.getElementById('branches-grid');
        
        if (branches.length === 0) {
            container.innerHTML = '<p class="text-slate-500 col-span-3 text-center py-10">No hay sucursales.</p>';
            return;
        }

        let htmlContent = '';

        branches.forEach(branch => {
            const manager = branch.gerente || 'Sin asignar';
            const rawStatus = branch.estatus || 'OPERATIVA'; 
            
            // Colores
            let statusClass = '';
            const s = rawStatus.toUpperCase(); 

            if (s === 'OPERATIVA') statusClass = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
            else if (s === 'BAJA' || s === 'CERRADA') statusClass = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
            else if (s === 'REMODELACION') statusClass = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
            else statusClass = 'bg-gray-100 text-gray-700';

            const initials = branch.nombre ? branch.nombre.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() : 'S';

            // --- LÓGICA DE BÚSQUEDA EN MAPS (TAL COMO PEDISTE) ---
            
            // 1. Variable BASE con la marca (Helpdesk)
            const base = "Helpdesk ";

            // 2. Limpiamos variables de la BD (quitamos espacios extra)
            const nombreSucursal = branch.nombre ? branch.nombre.trim() : ''; // Ej: "Zalatitan"
            const direccionSucursal = branch.direccion ? branch.direccion.replace(/\n/g, ' ').trim() : '';

            // 3. Concatenación Mágica: "Base" + "Nombre BD" + "Dirección"
            // Resultado: "Helpdesk Zalatitan Av. Zalatitan 370..."
            const busquedaCompleta = `${base} ${nombreSucursal} ${direccionSucursal}`;

            // 4. Generamos la URL oficial de búsqueda de Google
            const mapUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(busquedaCompleta)}`;
            
            // --------------------------------------------------------

            htmlContent += `
                <div class="bg-white dark:bg-darkcard p-5 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-md transition animate-fade-in">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center font-bold">
                            ${initials}
                        </div>
                        <span class="px-2 py-1 ${statusClass} rounded text-[10px] font-bold uppercase">
                            ${rawStatus}
                        </span>
                    </div>
                    
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white">${branch.nombre}</h3>
                    <p class="text-xs text-slate-500 mb-4">Código: ${branch.folio}</p>
                    
                    <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <p><i class="fas fa-map-marker-alt w-5 text-slate-400"></i> ${branch.direccion}</p>
                        <p><i class="fas fa-phone w-5 text-slate-400"></i> ${branch.telefono}</p>
                        <p><i class="fas fa-user-tie w-5 text-slate-400"></i> Gerente: ${manager}</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-2">
                        <button onclick="openBranchModal('edit', {
                                id: '${branch.id}',
                                name: '${branch.nombre}',
                                code: '${branch.folio}',
                                address: '${branch.direccion ? branch.direccion.replace(/\n/g, ' ') : ''}', 
                                phone: '${branch.telefono}',
                                manager: '${manager}',
                                status: '${rawStatus}'
                            })" 
                            class="text-xs text-slate-500 hover:text-blue-600 border border-slate-200 dark:border-slate-600 px-3 py-1.5 rounded transition">
                            Editar
                        </button>
                        
                        <a href="${mapUrl}" target="_blank"
                           class="text-xs text-slate-500 hover:text-blue-600 border border-slate-200 dark:border-slate-600 px-3 py-1.5 rounded transition inline-block text-center decoration-0 cursor-pointer">
                            Mapa
                        </a>
                    </div>
                </div>
            `;
        });

        container.innerHTML = htmlContent;

    } catch (error) {
        console.error('Error cargando sucursales:', error);
    }
}