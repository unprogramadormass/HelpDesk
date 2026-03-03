// --- CARGAR CHECKBOXES DE NIVELES (Al abrir el modal) ---

async function loadLevelsForCheckbox() {
    const container = document.getElementById('levels-checkbox-container');
    
    try {
        const response = await fetch('/HelpDesk/db/admin/incidencias/get-niveles-lista.php');
        const levels = await response.json();

        if (levels.length === 0) {
            container.innerHTML = '<p class="text-xs text-slate-400 col-span-2">No hay niveles creados aún.</p>';
            return;
        }

        let html = '';
        levels.forEach(lvl => {
            html += `
                <label class="flex items-center space-x-2 p-2 rounded hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition">
                    <input type="checkbox" value="${lvl.id}" class="level-checkbox w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600">
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">${lvl.nombre_mostrar}</span>
                </label>
            `;
        });
        container.innerHTML = html;

    } catch (error) {
        container.innerHTML = '<p class="text-xs text-red-400">Error cargando niveles.</p>';
        console.error(error);
    }
}

// --- ABRIR Y CERRAR MODAL ---

async function openIncidentModal(mode = 'create', data = {}) {
    const modal = document.getElementById('incidentModal');
    const title = document.getElementById('modal-inc-title');
    
    // Referencias a inputs
    const inpId = document.getElementById('inc-id');
    const inpOriginLvl = document.getElementById('inc-origin-level-id');
    const inpName = document.getElementById('inc-name');
    const inpSlaVal = document.getElementById('inc-sla-val');
    const inpSlaUnit = document.getElementById('inc-sla-unit'); // Asegúrate de tener este ID en tu HTML
    const inpPriority = document.getElementById('inc-priority');
    const containerCheckboxes = document.getElementById('levels-checkbox-container');

    // 1. Cargar checkboxes frescos
    await loadLevelsForCheckbox();

    if (mode === 'edit') {
        title.innerText = "Editar Incidencia";
        
        // Llenar datos básicos
        inpId.value = data.id;
        inpOriginLvl.value = data.originLevelId; // ID del nivel (tabla) donde está
        inpName.value = data.name;
        
        // Desglosar SLA (Ej: "30 Min" -> Val: 30, Unit: Min)
        // Asumimos que data.sla viene como numero entero en minutos desde BD (Ej: 30)
        // O si viene pre-formateado, adáptalo. Aquí asumo minutos totales.
        let totalMin = parseInt(data.sla); 
        if (totalMin >= 1440 && totalMin % 1440 === 0) { // Días
            inpSlaVal.value = totalMin / 1440;
            inpSlaUnit.value = 'Días';
        } else if (totalMin >= 60 && totalMin % 60 === 0) { // Horas
            inpSlaVal.value = totalMin / 60;
            inpSlaUnit.value = 'Hrs';
        } else { // Minutos
            inpSlaVal.value = totalMin;
            inpSlaUnit.value = 'Min';
        }

        if (data.priority) {
            inpPriority.value = data.priority.toUpperCase();
        }

        // 2. Marcar el checkbox del nivel donde está (y deshabilitar otros si es edición simple)
        // En edición simple de una tabla dinámica, usualmente solo editas ESE registro en ESA tabla.
        const checkboxes = containerCheckboxes.querySelectorAll('.level-checkbox');
        checkboxes.forEach(cb => {
            if (cb.value == data.originLevelId) {
                cb.checked = true;
                cb.disabled = true; // Bloqueamos para que no mueva la incidencia de tabla (complejidad extra)
            } else {
                cb.checked = false;
                cb.disabled = true; // En modo edición, nos centramos en actualizar este registro único
            }
        });

    } else {
        title.innerText = "Nueva Incidencia";
        document.getElementById('incidentForm').reset();
        inpId.value = '';
        inpOriginLvl.value = '';
        
        // Habilitar todos los checkboxes
        const checkboxes = containerCheckboxes.querySelectorAll('.level-checkbox');
        checkboxes.forEach(cb => { cb.disabled = false; cb.checked = false; });
    }
    
    // Mostrar modal
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100', 'pointer-events-auto');
}

function closeIncidentModal() {
    const modal = document.getElementById('incidentModal');
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
}

// --- GUARDAR INCIDENCIA (MULTIPLE) ---

async function saveIncident() {
    // Inputs
    const id = document.getElementById('inc-id').value;
    const originLevelId = document.getElementById('inc-origin-level-id').value;
    
    // Si es nuevo, tomamos los checkboxes seleccionados.
    // Si es editar, usamos el originLevelId
    let levelIds = [];
    if (!id) {
        // Modo CREAR: Checkboxes
        const checkboxes = document.querySelectorAll('.level-checkbox:checked');
        levelIds = Array.from(checkboxes).map(cb => cb.value);
    } else {
        // Modo EDITAR: El nivel original
        levelIds = [originLevelId];
    }

    const nombre = document.getElementById('inc-name').value;
    const slaVal = document.getElementById('inc-sla-val').value;
    const slaUnit = document.getElementById('inc-sla-unit').value;
    const prioridad = document.getElementById('inc-priority').value;

    if (levelIds.length === 0 && !id) {
        showToast("Selecciona al menos un nivel", "error");
        return;
    }
    if (!nombre.trim()) {
        showToast("Nombre obligatorio", "error");
        return;
    }

    try {
        const response = await fetch('/HelpDesk/db/admin/incidencias/save-incidencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id, // Si lleva ID, el PHP sabrá que es Update
                originLevelId: originLevelId, // Necesario para saber QUÉ tabla actualizar
                levelIds: levelIds, // Solo para Insert
                nombre: nombre,
                slaVal: slaVal,
                slaUnit: slaUnit,
                prioridad: prioridad
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeIncidentModal();
            lastIncidentsSnapshot = ''; 
            loadIncidentLevels(); 
        } else {
            showToast(result.message, 'error');
        }

    } catch (error) {
        console.error(error);
        showToast("Error de servidor", "error");
    }
}