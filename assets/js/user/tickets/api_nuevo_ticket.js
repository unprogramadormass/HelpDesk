document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById('ticketsModal');
    const inputNivelNombre = document.getElementById('nt-nivel-nombre');
    const inputNivelId = document.getElementById('nt-nivel-id');
    const selectAsunto = document.getElementById('nt-asunto');
    const inputPrioridad = document.getElementById('nt-prioridad');
    const inputPrioridadHidden = document.getElementById('nt-prioridad-hidden');
    
    // Nuevas variables para el manejo de áreas
    const selectAreaSelect = document.getElementById('nt-area-select');
    const containerAreaSingle = document.getElementById('nt-area-single-container');
    const inputAreaSingle = document.getElementById('nt-area-input');
    const hiddenAreaSingle = document.getElementById('nt-area-hidden');

    window.toggleModala = async function () {

        if (modal.classList.contains('hidden')) {

            try {
                // Limpiamos campos visuales
                if (inputPrioridad && inputPrioridadHidden) {
                    inputPrioridad.value = '';
                    inputPrioridadHidden.value = '';
                }
                
                if (selectAsunto) {
                    selectAsunto.innerHTML = '<option value="" disabled selected>Cargando problemas...</option>';
                    selectAsunto.disabled = true;
                }

                const response = await fetch('../../../db/user/tickets/api_nuevo_ticket.php', {
                    credentials: 'include'
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error);
                }

                cargarDatosUsuario(data.user);
                cargarAsuntos(data.incidencias);
                cargarAreas(data.user, data.areas);

            } catch (error) {
                console.error(error);
                alert("Atención: " + error.message);
                return; // Evita que el modal se abra si hay error
            }
        }

        modal.classList.toggle('hidden');
    };

    function cargarDatosUsuario(user) {
        document.getElementById('nt-nombre').innerText = `${user.firstname} ${user.firstapellido}`;
        document.getElementById('nt-folio').innerText = user.folio || 'N/A';
        document.getElementById('nt-sucursal').innerText = user.sucursal_nombre || 'No asignada';
        document.getElementById('nt-ext').innerText = user.extension || 'N/A';

        // Llenamos el input bloqueado con el nombre del nivel y el hidden con el ID
        if (inputNivelNombre && inputNivelId) {
            inputNivelNombre.value = user.nivel_nombre || 'Sin nivel';
            inputNivelId.value = user.incidencia_id || '';
        }
    }

    function cargarAsuntos(incidencias) {
        if (!selectAsunto) return;

        selectAsunto.innerHTML = '<option value="" disabled selected>Selecciona el problema...</option>';

        if (incidencias.length > 0) {
            incidencias.forEach(inc => {
                const option = document.createElement('option');
                option.value = inc.id; 
                option.textContent = inc.nombre;
                option.dataset.prioridad = inc.prioridad;
                selectAsunto.appendChild(option);
            });
            selectAsunto.disabled = false;
        } else {
            selectAsunto.innerHTML = '<option value="" disabled selected>No hay problemas configurados...</option>';
        }
    }

    function cargarAreas(user, areas) {
        if (!selectAreaSelect || !containerAreaSingle) return;

        // 1. Reseteamos los elementos (los ocultamos y deshabilitamos)
        selectAreaSelect.innerHTML = '<option value="" disabled selected>Selecciona el área...</option>';
        selectAreaSelect.classList.add('hidden');
        selectAreaSelect.classList.remove('block');
        selectAreaSelect.disabled = true; // Para que no se envíe en el form
        
        containerAreaSingle.classList.add('hidden');
        containerAreaSingle.classList.remove('block');
        hiddenAreaSingle.disabled = true; // Para que no se envíe en el form

        // 🔥 ELIMINAMOS LA REGLA DEL ROL 'CORP' QUE FORZABA "SISTEMAS" 🔥

        // 2. Evaluamos cuántas áreas tiene el usuario realmente en la base de datos
        if (areas.length === 1) {
            // SOLO TIENE 1 ÁREA: Mostramos el input bloqueado
            containerAreaSingle.classList.remove('hidden');
            containerAreaSingle.classList.add('block');
            
            inputAreaSingle.value = areas[0].nombre; // Mostramos el texto real de su área
            hiddenAreaSingle.value = areas[0].id; // Guardamos el ID en el oculto
            hiddenAreaSingle.disabled = false; // Lo habilitamos para que se envíe con el form

        } else if (areas.length > 1) {
            // TIENE 2 O MÁS ÁREAS: Mostramos el select
            selectAreaSelect.classList.remove('hidden');
            selectAreaSelect.classList.add('block');
            selectAreaSelect.disabled = false; // Lo habilitamos para que se envíe

            areas.forEach(a => {
                const option = document.createElement('option');
                option.value = a.id;
                option.textContent = a.nombre;
                selectAreaSelect.appendChild(option);
            });
            
        } else {
            // ERROR: NO TIENE ÁREAS ASIGNADAS
            containerAreaSingle.classList.remove('hidden');
            containerAreaSingle.classList.add('block');
            inputAreaSingle.value = "Sin área asignada (Contacte a sistemas)";
            inputAreaSingle.classList.add('text-red-500');
        }
    }

    /* ======================
       EVENTOS
    ====================== */

    if (selectAsunto) {
        selectAsunto.addEventListener('change', () => {
            const selectedOption = selectAsunto.options[selectAsunto.selectedIndex];
            const prioridad = selectedOption.dataset.prioridad;

            if (inputPrioridad && inputPrioridadHidden) {
                inputPrioridad.value = `🔴 ${prioridad}`;
                inputPrioridadHidden.value = prioridad;
            }
        });
    }
});