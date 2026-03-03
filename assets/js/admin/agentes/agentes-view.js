// assets/js/admin/agentes/agentes.view.js

let teamChartInstance = null;
let currentRange = 'week';

document.addEventListener('DOMContentLoaded', () => {
    
    loadAgentesView();

    const select = document.querySelector('#view-agentes select'); 
    if(select) {
        select.addEventListener('change', (e) => {
            currentRange = e.target.value === 'Último Mes' ? 'month' : 'week';
            
            loadAgentesView();
        });
    }
});

async function loadAgentesView() {
    const url = `/HelpDesk/db/admin/agentes/get-agentes.php?range=${currentRange}`;
    

    try {
        const response = await fetch(url);
        
        // 1. Verificación HTTP
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }

        // 2. Verificación de contenido
        const text = await response.text();
        

        try {
            const res = JSON.parse(text);
            if (res.success) {
                
                renderAgentesCards(res.data);
                renderTeamChart(res.chart);
            } else {
                document.getElementById('agentes-grid').innerHTML = `<div class="col-span-4 text-red-500 bg-red-50 p-4 rounded border border-red-200 text-center">Error: ${res.message}</div>`;
            }
        } catch (e) {
        }

    } catch (error) {
        document.getElementById('agentes-grid').innerHTML = `<div class="col-span-4 text-red-500 text-center">Error de conexión. Revisa la consola (F12).</div>`;
    }
}

function renderAgentesCards(agentes) {
    const container = document.getElementById('agentes-grid');
    if (!container) return;

    if (agentes.length === 0) {
        container.innerHTML = '<p class="col-span-4 text-center text-slate-400 py-10">No se encontraron agentes en la base de datos.</p>';
        return;
    }

    let html = '';
    agentes.forEach(ag => {
        // Avatar
        const avatarUrl = ag.avatar 
            ? `/HelpDesk/assets/img/avatars/${ag.avatar}` 
            : `https://api.dicebear.com/7.x/avataaars/svg?seed=${ag.firstname}`;

        // Rol
        let rol = 'Agente';
        if(ag.tipo_usuario == 1) rol = 'Admin';
        if(ag.tipo_usuario == 2) rol = 'Supervisor';

        // Status
        const isConnected = (ag.connected == 1);
        const statusColor = isConnected ? 'bg-green-500' : 'bg-red-500';
        
        // Barra Color
        let barColor = 'bg-blue-600';
        let txtColor = 'text-blue-600';
        if(ag.eficacia >= 90) { barColor = 'bg-emerald-500'; txtColor = 'text-emerald-500'; }
        else if(ag.eficacia < 70) { barColor = 'bg-orange-500'; txtColor = 'text-orange-500'; }

        html += `
            <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 hover:shadow-md transition-all">
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative">
                        <img src="${avatarUrl}" class="w-14 h-14 rounded-full border bg-slate-50">
                        <span class="absolute bottom-0 right-0 w-3.5 h-3.5 ${statusColor} border-2 border-white rounded-full"></span>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-white text-lg">${ag.firstname} ${ag.firstapellido || ''}</h3>
                        <p class="text-xs text-slate-500">${rol}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                    <div class="flex flex-col items-center justify-center border-r border-slate-200 dark:border-slate-600">
                        <span class="text-[10px] uppercase text-slate-400 font-bold tracking-wider">Abiertos</span>
                        <p class="text-xl font-bold text-slate-700 dark:text-white mt-1">${ag.tickets_abiertos}</p>
                    </div>
                    
                    <div class="flex flex-col items-center justify-center">
                        <span class="text-[10px] uppercase text-slate-400 font-bold tracking-wider">Resueltos</span>
                        <p class="text-xl font-bold text-emerald-500 mt-1">${ag.tickets_resueltos}</p>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-500">Eficacia</span>
                        <span class="font-bold ${txtColor}">${ag.eficacia}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                        <div class="${barColor} h-1.5 rounded-full" style="width: ${ag.eficacia}%"></div>
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function renderTeamChart(chartData) {
    if (!chartData || !chartData.labels) {
        return;
    }

    const ctx = document.getElementById('teamPerformanceChart'); // Aquí la llamaste 'ctx'
    if (!ctx) return;

    if (teamChartInstance) {
        teamChartInstance.destroy();
    }
    
    const isDark = document.documentElement.classList.contains('dark');
    const colorText = isDark ? '#cbd5e1' : '#64748b';
    const colorGrid = isDark ? '#334155' : '#f1f5f9';

    // CORRECCIÓN AQUÍ: Antes decía 'canvas.getContext', ahora es 'ctx.getContext'
    teamChartInstance = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: colorText, usePointStyle: true } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colorGrid, borderDash: [5, 5] },
                    ticks: { color: colorText }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: colorText }
                }
            }
        }
    });
}