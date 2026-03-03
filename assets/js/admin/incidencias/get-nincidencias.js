// Variable snapshot para evitar parpadeos
let lastIncidentsSnapshot = '';

// Iniciar carga
document.addEventListener('DOMContentLoaded', () => {
    loadIncidentLevels();
    setInterval(loadIncidentLevels, 4000); // Actualizar cada 4 seg
});

async function loadIncidentLevels() {
    try {
        const response = await fetch('/HelpDesk/db/admin/incidencias/get-nincidencias.php');
        if (!response.ok) return;

        const levelsData = await response.json();
        
        // Anti-parpadeo
        const currentSnapshot = JSON.stringify(levelsData);
        if (currentSnapshot === lastIncidentsSnapshot) return;
        lastIncidentsSnapshot = currentSnapshot;

        renderIncidentLevels(levelsData);

    } catch (error) {
        console.error("Error cargando matriz de incidentes:", error);
    }
}

function renderIncidentLevels(levels) {
    const container = document.getElementById('incident-levels-container');
    
    if (levels.length === 0) {
        container.innerHTML = '<div class="text-center py-10 text-slate-400">No hay niveles configurados. Crea uno nuevo.</div>';
        return;
    }

    let htmlMain = '';

    levels.forEach((level, index) => {
        // Colores rotativos para los badges de "Nivel X" (Azul, Morado, Naranja, Verde...)
        const colors = [
            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
        ];
        const badgeColor = colors[index % colors.length];

        // Construir filas de la tabla (Incidencias)
        let rowsHtml = '';
        
        if (level.incidencias && level.incidencias.length > 0) {
            level.incidencias.forEach(inc => {
                // Lógica de colores para Prioridad
                let priorityClass = '';
                const p = inc.prioridad.toUpperCase();
                
                if (p === 'BAJA') priorityClass = 'text-green-600';
                else if (p === 'MEDIA') priorityClass = 'text-orange-500';
                else if (p === 'ALTA') priorityClass = 'text-red-500';
                else if (p === 'CRÍTICA') priorityClass = 'text-purple-600 font-extrabold';

                // Icono dinámico simple (opcional, por defecto file-alt)
                let icon = 'fa-file-alt';
                if(inc.nombre.toLowerCase().includes('pass')) icon = 'fa-key';
                if(inc.nombre.toLowerCase().includes('hard')) icon = 'fa-print';
                if(inc.nombre.toLowerCase().includes('app')) icon = 'fa-mobile-alt';

                rowsHtml += `
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                        <td class="p-4 font-bold flex items-center gap-2 whitespace-nowrap">
                            <i class="fas ${icon} text-slate-400"></i> ${inc.nombre}
                        </td>
                        <td class="p-4 text-slate-500 text-xs min-w-[200px]">${inc.tiempo_resolucion} Minutos (Est.)</td>
                        <td class="p-4 whitespace-nowrap">
                             <span class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-xs">
                                ${inc.tiempo_resolucion} Min
                             </span>
                        </td>
                        <td class="p-4 whitespace-nowrap">
                            <span class="${priorityClass} font-bold text-xs uppercase">${inc.prioridad}</span>
                        </td>
                        <td class="p-4 text-right whitespace-nowrap">
                            <button onclick="openIncidentModal('edit', {
                                    id: ${inc.id},
                                    originLevelId: ${level.id},  // Pasamos el ID del nivel actual (tabla)
                                    name: '${inc.nombre}',
                                    sla: '${inc.tiempo_resolucion}', // Minutos desde BD
                                    priority: '${inc.prioridad}'
                                })" 
                                class="text-slate-400 hover:text-blue-600 transition">
                                <i class="fas fa-pen"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        } else {
            rowsHtml = `<tr><td colspan="5" class="p-4 text-center text-xs text-slate-400">Aún no hay incidencias en este nivel.</td></tr>`;
        }

        // Construir el bloque completo del Nivel
        htmlMain += `
            <div class="level-group animate-fade-in">
                <div class="flex items-center gap-3 mb-4">
                    <span class="${badgeColor} px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        ${level.id}
                    </span>
                    <h3 class="text-lg font-bold text-slate-700 dark:text-slate-200">
                        ${level.nombre_mostrar}
                    </h3>
                </div>
                
                <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto w-full"> 
                        <table class="w-full text-left border-collapse min-w-[700px]"> 
                            <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase sticky top-0">
                                <tr>
                                    <th class="p-4 w-1/4 whitespace-nowrap">Categoría</th>
                                    <th class="p-4 w-1/3 whitespace-nowrap">Tiempo / Desc</th>
                                    <th class="p-4 whitespace-nowrap">SLA</th>
                                    <th class="p-4 whitespace-nowrap">Prioridad</th>
                                    <th class="p-4 text-right whitespace-nowrap">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                                ${rowsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = htmlMain;
}