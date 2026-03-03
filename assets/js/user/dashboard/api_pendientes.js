const API_PENDIENTES = '/HelpDesk/db/user/dashboard/api_pendientes.php';

// Variable global para saber si estamos editando o creando
let currentEditId = null;

// --- FUNCIÓN PARA ABRIR EL MODAL COMO "NUEVO PENDIENTE" ---
function openAddPendiente() {
    currentEditId = null; // Limpiamos el ID (Modo Crear)
    const form = document.getElementById('form-nuevo-pendiente');
    if(form) form.reset(); // Vaciamos el texto
    
    // Cambiamos el título del modal para que diga "Nuevo"
    const modalTitle = document.querySelector('#todoModal h3');
    if(modalTitle) modalTitle.innerHTML = `<div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400"><i class="fas fa-tasks text-lg"></i></div> Nuevo Pendiente`;
    
    toggleTodoModal(); // Abrimos la ventana
}

// 1. CARGAR PENDIENTES
function loadPendientes() {
    const filtro = document.getElementById('todo-filter').value;
    const lista = document.getElementById('listaTareas');
    lista.innerHTML = '<li class="p-4 text-center text-slate-400 text-sm italic">Cargando...</li>';

    fetch(`${API_PENDIENTES}?action=fetch&filtro=${filtro}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                lista.innerHTML = '';
                if (data.data.length === 0) {
                    lista.innerHTML = `<li class="p-4 bg-white dark:bg-slate-800/80 rounded-xl text-center text-slate-400 text-sm italic border border-slate-100 dark:border-slate-700 shadow-sm">No hay pendientes en esta categoría.</li>`;
                    return;
                }

                data.data.forEach(tarea => {
                    const isCompletada = tarea.estado === 'completada';
                    const isEliminada = tarea.estado === 'eliminada';
                    
                    // Escapamos las comillas simples para evitar que se rompa el botón de Editar
                    const safeText = tarea.descripcion.replace(/'/g, "\\'");
                    
                    // Diseño de cada tarjeta de pendiente
                    lista.innerHTML += `
                        <li class="p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm flex justify-between items-start gap-4 transition-all ${isCompletada ? 'opacity-60' : ''} ${isEliminada ? 'border-red-200 bg-red-50 dark:bg-red-900/10' : ''}">
                            <div class="flex flex-col flex-1">
                                <p class="text-sm font-medium ${isCompletada ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-200'}">${tarea.descripcion}</p>
                                <span class="text-[10px] text-slate-400 mt-1"><i class="fas fa-clock"></i> ${tarea.fecha}</span>
                            </div>
                            
                            <div class="relative">
                                <button onclick="toggleTodoMenu(${tarea.id})" class="p-1 text-slate-400 hover:text-blue-600 focus:outline-none">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                
                                <div id="todo-menu-${tarea.id}" class="todo-dropdown hidden absolute right-0 mt-1 w-40 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-100 dark:border-slate-700 z-10 overflow-hidden">
                                    ${!isCompletada && !isEliminada ? `<button onclick="updatePendiente(${tarea.id}, 'completada')" class="w-full text-left px-4 py-2 text-xs font-medium text-green-600 hover:bg-slate-50 dark:hover:bg-slate-700/50"><i class="fas fa-check mr-2"></i> Completada</button>` : ''}
                                    ${isCompletada && !isEliminada ? `<button onclick="updatePendiente(${tarea.id}, 'pendiente')" class="w-full text-left px-4 py-2 text-xs font-medium text-orange-500 hover:bg-slate-50 dark:hover:bg-slate-700/50"><i class="fas fa-undo mr-2"></i> Deshacer</button>` : ''}
                                    ${!isEliminada ? `<button onclick="editPendiente(${tarea.id}, '${safeText}')" class="w-full text-left px-4 py-2 text-xs font-medium text-blue-600 hover:bg-slate-50 dark:hover:bg-slate-700/50"><i class="fas fa-pen mr-2"></i> Editar</button>` : ''}
                                    ${!isEliminada ? `<button onclick="updatePendiente(${tarea.id}, 'eliminada')" class="w-full text-left px-4 py-2 text-xs font-medium text-red-600 hover:bg-slate-50 dark:hover:bg-slate-700/50"><i class="fas fa-trash mr-2"></i> Eliminar</button>` : ''}
                                    ${isEliminada ? `<button onclick="updatePendiente(${tarea.id}, 'pendiente')" class="w-full text-left px-4 py-2 text-xs font-medium text-blue-600 hover:bg-slate-50 dark:hover:bg-slate-700/50"><i class="fas fa-trash-restore mr-2"></i> Restaurar</button>` : ''}
                                </div>
                            </div>
                        </li>
                    `;
                });
            }
        });
}

// 2. GUARDAR / EDITAR PENDIENTE (Interceptar formulario)
const formNuevoPendiente = document.getElementById('form-nuevo-pendiente');
if (formNuevoPendiente) {
    formNuevoPendiente.addEventListener('submit', function(e) {
        e.preventDefault(); // Evita que la página recargue
        const descripcion = this.tarea.value;

        // Decidimos si creamos uno nuevo o editamos uno existente
        let payload = {};
        if (currentEditId) {
            payload = { action: 'edit', id: currentEditId, descripcion: descripcion };
        } else {
            payload = { action: 'add', descripcion: descripcion };
        }

        fetch(API_PENDIENTES, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.reset(); // Limpia el formulario
                currentEditId = null; // Reiniciamos la variable
                toggleTodoModal(); // Cierra el modal
                loadPendientes(); // Recarga la lista
            } else {
                console.error("Error del backend:", data);
            }
        })
        .catch(error => console.error("Hubo un problema con la petición:", error));
    });
}

// 3. ACTUALIZAR ESTADO (Completar / Eliminar / Restaurar)
function updatePendiente(id, nuevoEstado) {
    fetch(API_PENDIENTES, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_status', id: id, estado: nuevoEstado })
    }).then(() => loadPendientes());
}

// 4. ABRIR MODAL PARA EDITAR TEXTO
function editPendiente(id, textoActual) {
    // Cerramos el menú actual para que no estorbe
    document.getElementById(`todo-menu-${id}`).classList.add('hidden');
    
    // Configuramos el ID que vamos a editar
    currentEditId = id; 
    
    // Llenamos el textarea con el texto actual
    const form = document.getElementById('form-nuevo-pendiente');
    if(form) form.tarea.value = textoActual;
    
    // Cambiamos el título del modal para que diga "Editar"
    const modalTitle = document.querySelector('#todoModal h3');
    if(modalTitle) modalTitle.innerHTML = `<div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400"><i class="fas fa-pen text-lg"></i></div> Editar Pendiente`;
    
    // Abrimos la ventana
    toggleTodoModal(); 
}

// 5. FUNCIONES VISUALES (Dropdowns y Filtros)
function toggleTodoMenu(id) {
    // Ocultar todos los demás menús primero
    document.querySelectorAll('.todo-dropdown').forEach(el => {
        if (el.id !== `todo-menu-${id}`) el.classList.add('hidden');
    });
    // Alternar el menú clickeado
    document.getElementById(`todo-menu-${id}`).classList.toggle('hidden');
}

// Cerrar el menú de puntitos si el usuario hace clic en otro lado
document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('.todo-dropdown').forEach(el => el.classList.add('hidden'));
    }
});

// Escuchar cambios en el select ("Todas", "Completadas", etc.)
const todoFilter = document.getElementById('todo-filter');
if(todoFilter) {
    todoFilter.addEventListener('change', loadPendientes);
}

// Arrancar funciones al cargar
document.addEventListener("DOMContentLoaded", () => {
    loadPendientes(); // Carga la lista inicial
});