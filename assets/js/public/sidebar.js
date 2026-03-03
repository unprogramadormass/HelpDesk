// --- LÓGICA DE PESTAÑAS DE CONFIGURACIÓN ---
function switchConfigTab(tabId, button) {
    // 1. Ocultar todos los paneles
    const panels = document.querySelectorAll('.config-panel');
    panels.forEach(panel => panel.classList.add('hidden'));

    // 2. Mostrar el panel seleccionado
    const targetPanel = document.getElementById('panel-' + tabId);
    if (targetPanel) {
        targetPanel.classList.remove('hidden');
        // Reiniciar animación
        targetPanel.classList.remove('animate-fade-in');
        void targetPanel.offsetWidth; // Trigger reflow
        targetPanel.classList.add('animate-fade-in');
    }

    // 3. Actualizar estilos de botones del menú lateral
    const buttons = document.querySelectorAll('.config-tab-btn');
    buttons.forEach(btn => {
        // Quitar estilo activo (volver a estilo gris hover)
        btn.className = "config-tab-btn w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800";
    });

    // Poner estilo activo al botón clicado (Azul sólido)
    button.className = "config-tab-btn active w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all bg-blue-600 text-white shadow-md";
}

// --- FUNCIÓN MEJORADA PARA CAMBIAR VISTAS ---
function switchView(viewId, element) {
    
    // 1. GESTIÓN DE VISTAS (Mostrar/Ocultar contenido principal)
    const views = document.querySelectorAll('.view-section');
    views.forEach(view => {
        view.classList.add('hidden');
        view.classList.remove('animate-fade-in');
    });

    const targetView = document.getElementById('view-' + viewId);
    if (targetView) {
        targetView.classList.remove('hidden');
        void targetView.offsetWidth; // Reiniciar animación CSS
        targetView.classList.add('animate-fade-in');
        
        // Actualizar Título del Header (Opcional)
        const headerTitle = document.querySelector('header h2');
        const titles = {
            'dashboard': 'Panel de Control',
            'tickets': 'Gestión de Tickets',
            'mis tickets': 'Gestión de mis Tickets',
            'usuarios': 'Catálogo de Usuarios',
            'incidentes': 'Tipos de Incidente',
            'sucursales': 'Gestión de Sucursales',
            'privilegios': 'Roles y Permisos',
            'agentes': 'Equipo de Soporte',
            'reportes': 'Centro de Reportes',
            'configuracion': 'Configuración del Sistema'
        };
        if(headerTitle) headerTitle.innerText = titles[viewId] || 'Panel de Control';

        // Lógica específica para gráficas (si aplica)
        if (viewId === 'agentes' && typeof renderTeamChart === 'function') {
            setTimeout(renderTeamChart, 100);
        }
    }

    // 2. GESTIÓN DE ESTILOS DEL SIDEBAR (Aquí estaba el error de descuadre)
    if (element) {
        // A) Limpiar todos los estilos activos
        const allLinks = document.querySelectorAll('.nav-item');
        allLinks.forEach(link => {
            // Si es un enlace de SUBMENÚ
            if (link.classList.contains('submenu-link')) {
                link.className = "nav-item submenu-link flex items-center h-10 pl-20 text-slate-500 dark:text-slate-400 hover:text-blue-600 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors";
            } 
            // Si es un enlace PRINCIPAL
            else {
                link.className = "nav-item group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans";
            }
        });

        // B) Aplicar estilo activo al elemento clicado
        if (element.classList.contains('submenu-link')) {
            // Estilo Activo para SUBMENÚ (Texto azul, fondo sutil)
            element.className = "nav-item submenu-link flex items-center h-10 pl-20 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-slate-800 text-xs font-bold transition-colors border-r-2 border-blue-600";
        } else {
            // Estilo Activo para PRINCIPAL (Borde izquierdo, fondo azul claro)
            element.className = "nav-item group flex items-center h-12 bg-blue-50 dark:bg-slate-800/50 border-l-4 border-blue-600 text-blue-700 dark:text-blue-400";
        }
    }
}

// --- 1. FUNCIONALIDAD DEL SUBMENÚ (NUEVO) ---
function toggleSubmenu(id) {
    const sidebar = document.getElementById('sidebar');
    const submenu = document.getElementById(id);
    const arrow = document.getElementById('arrow-catalogo');
    
    // Si el sidebar está colapsado, ábrelo primero para ver el menú
    if (sidebar.classList.contains('w-20')) {
        toggleSidebar(); // Llama a la función principal para expandir
        // Pequeño retraso para que se vea natural al abrirse
        setTimeout(() => {
            submenu.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }, 200);
    } else {
        // Si ya está abierto, solo despliega
        submenu.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
    }
}
// 2. RESPONSIVE SIDEBAR
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    const isMobile = window.innerWidth < 768;

    if (isMobile) {
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.add('active');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.remove('active');
        }
    } else {
        if (sidebar.classList.contains('w-20')) {
            sidebar.classList.replace('w-20', 'w-64');
            sidebar.classList.remove('collapsed');
        } else {
            sidebar.classList.replace('w-64', 'w-20');
            sidebar.classList.add('collapsed');
        }
        // Ajustar gráficas después de la animación (0.4s + buffer)
        setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 450);
    }
}

window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    
    if (window.innerWidth >= 768) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('active');
        if(!sidebar.classList.contains('w-20') && !sidebar.classList.contains('w-64')) {
                sidebar.classList.add('w-64');
        }
    } else {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('active');
        if(sidebar.classList.contains('w-20')) {
            sidebar.classList.replace('w-20', 'w-64');
            sidebar.classList.remove('collapsed');
        }
    }
});
