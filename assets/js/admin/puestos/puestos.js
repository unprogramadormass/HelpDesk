// Variable snapshot para Puestos
let lastPuestosSnapshot = '';

// INICIALIZACIÓN
document.addEventListener('DOMContentLoaded', () => {
    // Cargar Puestos
    loadPuestos();
    setInterval(loadPuestos, 3000);

    // --- CORRECCIÓN BUG ENTER (Para Puestos) ---
    const puestoForm = document.getElementById('puestoForm');
    if (puestoForm) {
        puestoForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Evita recarga
            savePuesto();       // Guarda
        });
    }
});

// --- MODALES ---

function openPuestoModal(mode = 'create', data = {}) {
    const modal = document.getElementById('puestoModal');
    const title = document.getElementById('puestoModalTitle');
    const inpId = document.getElementById('puesto-id');
    const inpName = document.getElementById('puesto-name');

    if (mode === 'edit') {
        title.innerText = "Editar Puesto";
        inpId.value = data.id;
        inpName.value = data.name;
    } else {
        title.innerText = "Nuevo Puesto";
        document.getElementById('puestoForm').reset();
        inpId.value = '';
    }
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100', 'pointer-events-auto');
}

function closePuestoModal() {
    const modal = document.getElementById('puestoModal');
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
}

// --- GUARDAR ---

async function savePuesto() {
    const id = document.getElementById('puesto-id').value;
    const nombre = document.getElementById('puesto-name').value;

    if (!nombre.trim()) {
        showToast("El nombre del puesto es obligatorio", "error");
        return;
    }

    try {
        const response = await fetch('/HelpDesk/db/admin/puestos/save-puestos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre })
        });

        if (!response.ok) throw new Error("Error HTTP");
        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            closePuestoModal();
            lastPuestosSnapshot = ''; // Forzar actualización
            loadPuestos();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error(error);
        showToast("Error de conexión", "error");
    }
}

// --- ELIMINAR ---

async function deletePuesto(id) {
    if (!confirm('¿Eliminar este puesto?')) return;

    try {
        const response = await fetch('/HelpDesk/db/admin/puestos/deleted-puestos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        if (!response.ok) throw new Error("Error HTTP");
        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            lastPuestosSnapshot = '';
            loadPuestos();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error(error);
        showToast("Error al eliminar", "error");
    }
}

// --- LEER (LOAD) ---

async function loadPuestos() {
    try {
        const response = await fetch('/HelpDesk/db/admin/puestos/get-puestos.php');
        if (!response.ok) return;

        const puestos = await response.json();
        const currentSnapshot = JSON.stringify(puestos);

        if (currentSnapshot === lastPuestosSnapshot) return;
        lastPuestosSnapshot = currentSnapshot;
        
        renderPuestos(puestos);

    } catch (error) {
        console.error("Error cargando puestos:", error);
    }
}

function renderPuestos(puestos) {
    const container = document.getElementById('puestos-container');
    
    if (puestos.length === 0) {
        container.innerHTML = '<p class="text-xs text-slate-400 text-center py-4 border border-dashed border-slate-300 rounded-lg">No hay puestos registrados.</p>';
        return;
    }

    let html = '';
    puestos.forEach(p => {
        html += `
            <div class="bg-white dark:bg-darkcard p-3 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700 flex justify-between items-center group hover:border-orange-300 transition animate-fade-in">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded bg-orange-50 dark:bg-orange-900/20 text-orange-600 flex items-center justify-center text-xs">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div>
                        <p class="font-medium text-slate-700 dark:text-slate-200 text-sm">${p.nombre}</p>
                    </div>
                </div>
                
                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="openPuestoModal('edit', { id: ${p.id}, name: '${p.nombre}' })" 
                        class="w-7 h-7 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-blue-600 transition flex items-center justify-center">
                        <i class="fas fa-pen text-xs"></i>
                    </button>
                    
                    <button onclick="deletePuesto(${p.id})" 
                        class="w-7 h-7 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 text-slate-400 hover:text-red-500 transition flex items-center justify-center">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}