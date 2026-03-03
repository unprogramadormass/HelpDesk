// Variable para el control de parpadeo (Snapshot)
let lastAreasSnapshot = '';

// Iniciar la carga al abrir la página
document.addEventListener('DOMContentLoaded', () => {
    loadAreas();
    // Revisa cambios cada 3 segundos
    setInterval(loadAreas, 3000);

    const areaForm = document.getElementById('areaForm');
    if (areaForm) {
        areaForm.addEventListener('submit', function(e) {
            e.preventDefault(); // ¡Alto! Detiene la recarga de la página
            saveArea();         // Ejecuta la función de guardar
        });
    }
});

// --- LÓGICA DE MODALES ---

function openAreaModal(mode = 'create', data = {}) {
    const modal = document.getElementById('areaModal');
    const title = document.getElementById('areaModalTitle');
    const inpId = document.getElementById('area-id');
    const inpName = document.getElementById('area-name');

    if (mode === 'edit') {
        title.innerText = "Editar Área";
        inpId.value = data.id;
        inpName.value = data.name;
    } else {
        title.innerText = "Nueva Área";
        document.getElementById('areaForm').reset();
        inpId.value = '';
    }
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100', 'pointer-events-auto');
}

function closeAreaModal() {
    const modal = document.getElementById('areaModal');
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
}

// --- GUARDAR (CREATE / UPDATE) ---

async function saveArea() {
    const id = document.getElementById('area-id').value;
    const nombre = document.getElementById('area-name').value;

    if (!nombre.trim()) {
        showToast("El nombre es obligatorio", "error");
        return;
    }

    try {
        // RUTA CORREGIDA: Apunta exactamente a donde guardaste el PHP
        const response = await fetch('/HelpDesk/db/admin/areas/save-area.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre })
        });

        // Verificamos si la respuesta es válida antes de procesar
        if (!response.ok) throw new Error("Error en la petición");

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success'); 
            closeAreaModal();
            lastAreasSnapshot = ''; // Forzamos actualización
            loadAreas();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error("Error al guardar:", error);
        showToast("Error al conectar con el servidor", "error");
    }
}

// --- ELIMINAR ---

async function deleteArea(id) {
    if (!confirm('¿Estás seguro de eliminar esta área?')) return;

    try {
        const response = await fetch('/HelpDesk/db/admin/areas/eliminar_area.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        if (!response.ok) throw new Error("Error en la petición");

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            lastAreasSnapshot = ''; 
            loadAreas();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error("Error al eliminar:", error);
        showToast("Error al eliminar", "error");
    }
}

// --- CARGA DE DATOS (READ) ---

async function loadAreas() {
    try {
        // AQUÍ ESTABA EL DETALLE: Usamos la ruta absoluta que probaste en el navegador
        const response = await fetch('/HelpDesk/db/admin/areas/get-areas.php');
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const areas = await response.json();

        // 1. Convertimos a texto para comparar
        const currentSnapshot = JSON.stringify(areas);

        // 2. Si es idéntico a lo que ya hay, no hacemos nada (Evita parpadeo)
        if (currentSnapshot === lastAreasSnapshot) return;
        
        // 3. Si cambió, guardamos la nueva foto y renderizamos
        lastAreasSnapshot = currentSnapshot;
        renderAreas(areas);

    } catch (error) {
        console.error("Error cargando áreas:", error);
    }
}

function renderAreas(areas) {
    const container = document.getElementById('areas-container');
    
    if (areas.length === 0) {
        container.innerHTML = '<p class="text-xs text-slate-400 text-center py-4 border border-dashed border-slate-300 rounded-lg">No hay áreas registradas.</p>';
        return;
    }

    let html = '';
    
    areas.forEach(area => {
        html += `
            <div class="bg-white dark:bg-darkcard p-3 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700 flex justify-between items-center group hover:border-blue-300 transition animate-fade-in">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-building"></i>
                    </div>
                    <span class="font-medium text-slate-700 dark:text-slate-200 text-sm">${area.nombre}</span>
                </div>
                
                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="openAreaModal('edit', { id: ${area.id}, name: '${area.nombre}' })" 
                        class="w-7 h-7 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-blue-600 transition flex items-center justify-center">
                        <i class="fas fa-pen text-xs"></i>
                    </button>
                    
                    <button onclick="deleteArea(${area.id})" 
                        class="w-7 h-7 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 text-slate-400 hover:text-red-500 transition flex items-center justify-center">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}