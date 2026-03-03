// Variable para guardar el estado anterior de los datos
let lastRolesSnapshot = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchRoles(); // Carga inicial
    // Revisar cambios cada 3 segundos (Polling eficiente)
    setInterval(fetchRoles, 3000); 
});

async function fetchRoles() {
    try {
        // Ajusta la ruta hacia tu archivo PHP
        const response = await fetch('/HelpDesk/db/admin/roles/get-roles.php');
        const roles = await response.json();

        // --- SISTEMA ANTI-PARPADEO ---
        // Convertimos el objeto a texto para compararlo
        const currentSnapshot = JSON.stringify(roles);
        
        // Si los datos son EXACTAMENTE iguales a la última vez, no hacemos nada.
        // Esto evita que el usuario vea parpadeos o pierda la posición del scroll.
        if (currentSnapshot === lastRolesSnapshot) {
            return;
        }

        // Si algo cambió, actualizamos la "foto" y renderizamos
        lastRolesSnapshot = currentSnapshot;
        renderRoles(roles);

    } catch (error) {
        console.error("Error cargando roles:", error);
    }
}

function renderRoles(roles) {
    const container = document.getElementById('roles-container');
    let html = '';

    roles.forEach(role => {
        // Definir clases de colores dinámicos basados en lo que mandó el PHP
        const colors = {
            purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30',
            blue:   'bg-blue-100 text-blue-600 dark:bg-blue-900/30',
            orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30',
            gray:   'bg-gray-100 text-gray-600 dark:bg-gray-700',
            green:  'bg-green-100 text-green-600 dark:bg-green-900/30',
            slate:  'bg-slate-100 text-slate-600 dark:bg-slate-700'
        };

        const iconClass = colors[role.color] || colors.slate;

        html += `
            <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col md:flex-row justify-between items-center gap-4 animate-fade-in">
                
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="w-10 h-10 rounded-full ${iconClass} flex items-center justify-center">
                        <i class="fas ${role.icono}"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 dark:text-white">${role.nombre}</h4>
                        <p class="text-xs text-slate-500">${role.descripcion}</p>
                    </div>
                </div>

                <div class="text-sm text-slate-500 font-medium">
                    ${role.total_usuarios} Usuario${role.total_usuarios !== 1 ? 's' : ''}
                </div>

            
            </div>
        `;
    });

    container.innerHTML = html;
}