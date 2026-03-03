document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    // 1️⃣ VARIABLES GLOBALES DEL MODAL
    // ==========================================
    const modal = document.getElementById('ticketsModal');
    const inputNivelNombre = document.getElementById('nt-nivel-nombre');
    const inputNivelId = document.getElementById('nt-nivel-id');
    const selectAsunto = document.getElementById('nt-asunto');
    const inputPrioridad = document.getElementById('nt-prioridad');
    const inputPrioridadHidden = document.getElementById('nt-prioridad-hidden');
    
    // Variables para las áreas
    const selectAreaSelect = document.getElementById('nt-area-select');
    const containerAreaSingle = document.getElementById('nt-area-single-container');
    const inputAreaSingle = document.getElementById('nt-area-input');
    const hiddenAreaSingle = document.getElementById('nt-area-hidden');

    // Configuración Base de SweetAlert para el tema oscuro de HelpDesk
    const swalConfig = {
        background: '#121212',
        color: '#ffffff',
        customClass: { popup: 'swal-custom-glass border border-slate-700 rounded-2xl shadow-2xl' },
        confirmButtonColor: '#2563eb' // Azul corporativo que usamos en el login
    };


    // ==========================================
    // 2️⃣ LÓGICA PARA ABRIR EL MODAL Y CARGAR DATOS
    // ==========================================
    window.toggleModala = async function () {
        if (modal.classList.contains('hidden')) {
            try {
                // Limpiamos campos visuales al abrir
                if (inputPrioridad && inputPrioridadHidden) {
                    inputPrioridad.value = '';
                    inputPrioridadHidden.value = '';
                }
                if (selectAsunto) {
                    selectAsunto.innerHTML = '<option value="" disabled selected>Cargando problemas...</option>';
                    selectAsunto.disabled = true;
                }

                // Consultamos al backend
                const response = await fetch('../../../db/user/tickets/api_nuevo_ticket.php', {
                    credentials: 'include'
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error al cargar los datos del sistema.');
                }

                // Llenamos los datos
                cargarDatosUsuario(data.user);
                cargarAsuntos(data.incidencias);
                cargarAreas(data.user, data.areas);

            } catch (error) {
                console.error(error);
                // Alerta de Error al Cargar Datos
                Swal.fire({
                    ...swalConfig,
                    icon: 'warning',
                    title: 'Atención',
                    text: error.message,
                    confirmButtonText: 'Entendido'
                });
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

        // Resetear vista
        selectAreaSelect.innerHTML = '<option value="" disabled selected>Selecciona el área...</option>';
        selectAreaSelect.classList.add('hidden');
        selectAreaSelect.classList.remove('block');
        selectAreaSelect.disabled = true; 
        
        containerAreaSingle.classList.add('hidden');
        containerAreaSingle.classList.remove('block');
        hiddenAreaSingle.disabled = true;

        if (areas.length === 1) {
            // SOLO TIENE 1 ÁREA
            containerAreaSingle.classList.remove('hidden');
            containerAreaSingle.classList.add('block');
            inputAreaSingle.value = areas[0].nombre; 
            hiddenAreaSingle.value = areas[0].id; 
            hiddenAreaSingle.disabled = false;

        } else if (areas.length > 1) {
            // TIENE MÚLTIPLES ÁREAS
            selectAreaSelect.classList.remove('hidden');
            selectAreaSelect.classList.add('block');
            selectAreaSelect.disabled = false; 

            areas.forEach(a => {
                const option = document.createElement('option');
                option.value = a.id;
                option.textContent = a.nombre;
                selectAreaSelect.appendChild(option);
            });
            
        } else {
            // SIN ÁREAS
            containerAreaSingle.classList.remove('hidden');
            containerAreaSingle.classList.add('block');
            inputAreaSingle.value = "Sin área asignada (Contacte a sistemas)";
            inputAreaSingle.classList.add('text-red-500');
        }
    }

    // Actualizar campo de prioridad automáticamente al elegir un problema
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


    // ==========================================
    // 3️⃣ LÓGICA DE EVIDENCIAS (CHIPS Y DRAG&DROP)
    // ==========================================
    let archivosSeleccionados = []; // Arreglo en memoria para los archivos
    
    const fileInput = document.getElementById('nt-file');
    const filePreviewContainer = document.getElementById('file-preview-container');
    const dropZone = document.getElementById('drop-zone');

    if (fileInput && filePreviewContainer && dropZone) {
        // Evento botón "Subir"
        fileInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) {
                agregarArchivos(Array.from(e.target.files));
            }
            fileInput.value = ''; // Reseteamos el input
        });

        // Eventos visuales de Arrastrar
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        });

        // Evento Soltar
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                agregarArchivos(Array.from(e.dataTransfer.files));
            }
        });
    }

    function agregarArchivos(nuevosArchivos) {
        archivosSeleccionados = archivosSeleccionados.concat(nuevosArchivos);
        renderizarArchivos();
    }

    function renderizarArchivos() {
        if(!filePreviewContainer) return;
        filePreviewContainer.innerHTML = ''; 

        archivosSeleccionados.forEach((file, index) => {
            const chip = document.createElement('div');
            chip.className = "flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 pl-3 pr-2 py-1.5 rounded-lg shadow-sm group animate-fade-in";
            
            // Icono dinámico
            let icon = 'fas fa-file text-slate-400';
            if (file.type.includes('image')) icon = 'fas fa-image text-blue-500';
            else if (file.type.includes('pdf')) icon = 'fas fa-file-pdf text-red-500';
            else if (file.name.match(/\.(doc|docx)$/i)) icon = 'fas fa-file-word text-blue-600';
            else if (file.name.match(/\.(xls|xlsx)$/i)) icon = 'fas fa-file-excel text-green-500';

            // Tamaño dinámico
            const peso = file.size > 1024 * 1024 
                ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' 
                : (file.size / 1024).toFixed(0) + ' KB';

            chip.innerHTML = `
                <i class="${icon}"></i>
                <div class="flex flex-col">
                    <span class="truncate max-w-[120px] text-xs font-bold text-slate-700 dark:text-slate-300" title="${file.name}">${file.name}</span>
                    <span class="text-[9px] text-slate-400">${peso}</span>
                </div>
            `;

            // Botón "X" para eliminar archivo de la lista
            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.className = "ml-1 p-1 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-md transition-colors";
            btnRemove.innerHTML = '<i class="fas fa-times"></i>';
            btnRemove.onclick = () => {
                archivosSeleccionados.splice(index, 1); 
                renderizarArchivos(); 
            };

            chip.appendChild(btnRemove);
            filePreviewContainer.appendChild(chip);
        });
    }


    // ==========================================
    // 4️⃣ LÓGICA PARA GUARDAR EN BASE DE DATOS
    // ==========================================
    const formNuevoTicket = document.getElementById('form-nuevo-ticket');

    if (formNuevoTicket) {
        formNuevoTicket.addEventListener('submit', async (e) => {
            e.preventDefault(); 

            const formData = new FormData(formNuevoTicket);

            // Obtener el título desde el texto de la opción seleccionada
            if (selectAsunto && selectAsunto.selectedIndex >= 0) {
                const textoAsunto = selectAsunto.options[selectAsunto.selectedIndex].text;
                formData.append('titulo', textoAsunto); 
            } else {
                // Alerta Validación
                Swal.fire({
                    ...swalConfig,
                    icon: 'warning',
                    title: 'Faltan datos',
                    text: 'Por favor, selecciona un problema/asunto antes de enviar.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Integrar manualmente nuestros archivos guardados al FormData
            archivosSeleccionados.forEach(file => {
                formData.append('evidencia[]', file);
            });

            // Animación del botón guardando...
            const btnSubmit = formNuevoTicket.querySelector('button[type="submit"]');
            const originalBtnHtml = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            btnSubmit.disabled = true;

            try {
                // Enviar datos
                const response = await fetch('../../../db/user/tickets/save_ticket.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    
                    // Limpiamos formularios y memoria antes de cerrar
                    formNuevoTicket.reset();
                    archivosSeleccionados = [];
                    renderizarArchivos();
                    
                    if (typeof toggleModala === 'function') {
                        toggleModala(); // Cerramos el modal modal detrás de la alerta
                    }

                    // Alerta de Éxito
                    Swal.fire({
                        ...swalConfig,
                        icon: 'success',
                        title: '¡Ticket Generado!',
                        html: `Tu reporte ha sido registrado con el folio:<br><strong style="font-size:1.2rem; color:#2563eb;">${result.folio}</strong>`,
                        showConfirmButton: false,
                        timer: 2500 // Se cierra solo en 2.5 segundos
                    }).then(() => {
                        // Recargar página o tabla después de que se cierre la alerta
                        location.reload(); 
                        // Si tienes función para recargar tabla sin refrescar la página, úsala aquí en lugar de location.reload()
                        // reloadTableTickets(); 
                    });

                } else {
                    // Alerta de Error desde Backend
                    Swal.fire({
                        ...swalConfig,
                        icon: 'error',
                        title: 'Error al generar',
                        text: result.message || 'No se pudo guardar el ticket.',
                        confirmButtonText: 'Reintentar'
                    });
                }

            } catch (error) {
                console.error("Error al guardar el ticket:", error);
                // Alerta de Error de Conexión
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Fallo de conexión',
                    text: 'Hubo un problema de red al intentar comunicarse con el servidor.',
                    confirmButtonText: 'Entendido'
                });
            } finally {
                // Restauramos el botón
                btnSubmit.innerHTML = originalBtnHtml;
                btnSubmit.disabled = false;
            }
        });
    }
});