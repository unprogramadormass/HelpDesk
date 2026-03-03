document.addEventListener("DOMContentLoaded", () => {
    
    // USAMOS sessionStorage para vistas, modales y SIDEBAR
    const STORAGE = window.sessionStorage; 
    const KEY_VIEW = 'app_state_view';
    const KEY_MODAL = 'app_state_modal';
    const KEY_SIDEBAR = 'app_state_sidebar'; // <-- NUEVA CLAVE PARA EL MENÚ

    // 1. RECUPERAR VISTA (Pestaña donde estabas)
    const savedView = STORAGE.getItem(KEY_VIEW);
    if (savedView) {
        let navElement = document.querySelector(`a[onclick*="switchView('${savedView}'"]`) || 
                         document.querySelector(`button[onclick*="switchView('${savedView}'"]`);
        
        if (window.switchView) {
            window.switchView(savedView, navElement);
        }
    }

    // 2. RECUPERAR MODAL (Si dejaste un formulario abierto)
    const savedModal = STORAGE.getItem(KEY_MODAL);
    if (savedModal) {
        setTimeout(() => {
            switch (savedModal) {
                case 'ticketsModal': if(typeof toggleModal === 'function') toggleModal(); break;
                case 'userModal': if(typeof openUserModal === 'function') openUserModal(); break;
                case 'incidentModal': if(typeof openIncidentModal === 'function') openIncidentModal(); break;
                case 'levelModal': if(typeof openLevelModal === 'function') openLevelModal(); break;
                case 'branchModal': if(typeof openBranchModal === 'function') openBranchModal(); break;
                case 'roleModal': if(typeof openRoleModal === 'function') openRoleModal(); break;
            }
        }, 100);
    }

    // 3. RECUPERAR ESTADO DEL SIDEBAR (NUEVO)
    const savedSidebar = STORAGE.getItem(KEY_SIDEBAR);
    if (savedSidebar) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        const isMobile = window.innerWidth < 768;

        if (sidebar) {
            if (savedSidebar === 'collapsed') {
                if (isMobile) {
                    sidebar.classList.add('-translate-x-full');
                    if(overlay) overlay.classList.remove('active');
                } else {
                    sidebar.classList.replace('w-64', 'w-20');
                    sidebar.classList.add('collapsed');
                }
            } else if (savedSidebar === 'open') {
                if (isMobile) {
                    sidebar.classList.remove('-translate-x-full');
                    if(overlay) overlay.classList.add('active');
                } else {
                    sidebar.classList.replace('w-20', 'w-64');
                    sidebar.classList.remove('collapsed');
                }
            }
        }
    }

    // 4. VIGILANTE DE ACCIONES (Interceptamos tus funciones para guardar estado)
    
    // A) Cuando cambias de vista
    const originalSwitchView = window.switchView;
    window.switchView = function(viewId, element) {
        STORAGE.setItem(KEY_VIEW, viewId); 
        STORAGE.removeItem(KEY_MODAL); 
        return originalSwitchView(viewId, element);
    };

    // B) Cuando abres un modal
    function wrapOpenModal(funcName, modalId) {
        if (window[funcName]) {
            const originalFunc = window[funcName];
            window[funcName] = function(...args) {
                STORAGE.setItem(KEY_MODAL, modalId); 
                return originalFunc.apply(this, args);
            };
        }
    }

    wrapOpenModal('toggleModal', 'ticketsModal');
    wrapOpenModal('openUserModal', 'userModal');
    wrapOpenModal('openIncidentModal', 'incidentModal');
    wrapOpenModal('openLevelModal', 'levelModal');
    wrapOpenModal('openBranchModal', 'branchModal');
    wrapOpenModal('openRoleModal', 'roleModal');

    // C) Cuando cierras un modal
    function wrapCloseModal(funcName) {
        if (window[funcName]) {
            const originalFunc = window[funcName];
            window[funcName] = function(...args) {
                STORAGE.removeItem(KEY_MODAL); 
                return originalFunc.apply(this, args);
            };
        }
    }

    wrapCloseModal('closeUserModal');
    wrapCloseModal('closeIncidentModal');
    wrapCloseModal('closeLevelModal');
    wrapCloseModal('closeBranchModal');
    wrapCloseModal('closeRoleModal');
    
    // D) VIGILANTE DEL SIDEBAR (NUEVO)
    // Interceptamos toggleSidebar para guardar si quedó abierto o cerrado
    if (typeof window.toggleSidebar === 'function') {
        const originalToggleSidebar = window.toggleSidebar;
        window.toggleSidebar = function(...args) {
            // 1. Ejecutamos tu función original para que haga la animación
            const result = originalToggleSidebar.apply(this, args);
            
            // 2. Evaluamos cómo quedó el menú después del click
            const sidebarEl = document.getElementById('sidebar');
            const isMobileEl = window.innerWidth < 768;
            let isCollapsed = false;

            if (isMobileEl) {
                isCollapsed = sidebarEl.classList.contains('-translate-x-full'); // En celular está colapsado si está fuera de pantalla
            } else {
                isCollapsed = sidebarEl.classList.contains('w-20'); // En PC está colapsado si mide w-20
            }

            // 3. Lo guardamos en memoria
            STORAGE.setItem(KEY_SIDEBAR, isCollapsed ? 'collapsed' : 'open');
            
            return result;
        };
    }

    // Caso especial para el botón de cerrar historial
    const btnCloseHistory = document.querySelector('#ticketsModal button');
    if(btnCloseHistory){
        btnCloseHistory.addEventListener('click', () => STORAGE.removeItem(KEY_MODAL));
    }
});