<?php
require_once(__DIR__ . '/../../../db/security/validacion.php');
verificarAcceso([1, 2]); 

$db_name_actual = $_SESSION['db_name'] ?? 'Desconocida';
$codigo_emp_actual = $_SESSION['codigo_empresa'] ?? 'N/A';
$nombre_empresa = $_SESSION['nombre_empresa'] ?? 'Empresa';

// Generar iniciales (Ej: "San Pablo" -> "SP")
$palabras = explode(" ", $nombre_empresa);
$iniciales = "";
foreach ($palabras as $p) {
    if (strlen($p) > 0) $iniciales .= strtoupper($p[0]);
}
$iniciales = substr($iniciales, 0, 2); // Máximo 2 letras

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | HelpDesk</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/HelpDesk/assets/img/public/ICO.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { 
                        darkbg: '#0f172a', 
                        darkcard: '#1e293b',
                        darksidebar: '#020617'
                    },
                    width: { '20': '5rem', '64': '16rem' }
                }
            }
        }
    </script>
    
    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); background-color: rgba(37, 99, 235, 0.1); }
            to { opacity: 1; transform: translateY(0); background-color: transparent; }
        }

        .new-ticket-row {
            animation: slideIn 0.8s ease-out forwards;
        }

        /* Evita el parpadeo de los números al cambiar */
        .kpi-value {
            transition: all 0.3s ease;
        }

        /* Personalización del Scrollbar para Webkit (Chrome, Edge, Safari) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5); /* Color gris suave */
            border-radius: 20px;
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
        }
        /* index.php línea 44 */
        .modal { transition: opacity 0.25s ease; pointer-events: none; opacity: 0; }
        .modal.active { opacity: 1; pointer-events: auto; }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        /* 1. ANIMACIÓN DE COLORES (Suave para el tema oscuro) */
        .theme-transition, .theme-transition *, .theme-transition *:before, .theme-transition *:after {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: ease;
            transition-duration: 300ms;
        }

        /* 2. ANIMACIÓN DEL SIDEBAR (Geometría específica restaurada) */
        /* Usamos !important en la transición para asegurar que sobrescriba cualquier otra regla */
        aside#sidebar {
            transition-property: width, transform, background-color, border-color;
            transition-duration: 0.4s; /* Tiempo perfecto para suavidad */
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); /* Efecto "elástico" profesional */
            will-change: width, transform; /* Optimización de rendimiento */
        }

        .sidebar-text { transition: opacity 0.2s ease; white-space: nowrap; opacity: 1; }
        .collapsed .sidebar-text { opacity: 0; pointer-events: none; transition-duration: 0.1s; }
        
        /* Utilidades */
        .modal { transition: opacity 0.25s ease; pointer-events: none; opacity: 0; }
        .modal.active { opacity: 1; pointer-events: auto; }
        #mobile-overlay { transition: opacity 0.3s ease; pointer-events: none; opacity: 0; }
        #mobile-overlay.active { opacity: 1; pointer-events: auto; }
        /* Animación de entrada suave */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }

    </style>
</head>

<body class="bg-slate-50 text-slate-800 dark:bg-darkbg dark:text-slate-200 theme-transition">

    <div class="flex h-screen overflow-hidden relative">
        <!-- Inicia Boton Menu Hamburguesa pmobil -->
        <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-20 md:hidden backdrop-blur-sm"></div>
        <!-- Termina Boton Menu Hamburguesa mobil -->

        <!-- Incia Menu Hamburguesa -->
        <aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-30 bg-white dark:bg-darksidebar border-r border-slate-200 dark:border-slate-800 flex flex-col shadow-xl flex-shrink-0 w-64 transform -translate-x-full md:translate-x-0 h-full theme-trans overflow-hidden transition-all duration-300">
    
            <div class="h-16 flex items-center border-b border-slate-100 dark:border-slate-800 shrink-0">
                <div class="w-20 flex-shrink-0 flex justify-center items-center">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-xs shadow-md text-white">
                        <?= $iniciales ?>
                    </div>
                </div>
                <div class="sidebar-text overflow-hidden pr-2">
                    <h1 class="font-bold text-lg tracking-tight text-slate-800 dark:text-slate-100 truncate w-full">HelpDesk</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate w-full" title="<?= htmlspecialchars($nombre_empresa) ?>">
                        <?= htmlspecialchars($nombre_empresa) ?>
                    </p>
                </div>
            </div>

            <nav class="flex-1 py-4 space-y-1 overflow-y-auto overflow-x-hidden custom-scrollbar">
                
                <a href="#" onclick="switchView('dashboard', this); return false;" class="nav-item group flex items-center h-12 bg-blue-50 dark:bg-slate-800/50 border-l-4 border-blue-600 text-blue-700 dark:text-blue-400">
                    <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-chart-pie w-5 text-center text-lg"></i></div>
                    <span class="sidebar-text font-medium text-sm">Dashboard</span>
                </a>

                <a href="#" onclick="switchView('tickets', this); return false;" class="nav-item group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans">
                    <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-ticket-alt w-5 text-center text-lg"></i></div>
                    <span class="sidebar-text font-medium text-sm">Tickets</span>
                </a>

                <a href="#" onclick="switchView('asignados', this); return false;" class="nav-item group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans">
                    <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-clipboard-list w-5 text-center text-lg"></i></div>
                    <span class="sidebar-text font-medium text-sm">Mis Tickets</span>
                </a>

                <div>
                    <button onclick="toggleSubmenu('submenu-catalogo')" class="w-full group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans focus:outline-none">
                        <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-folder-open w-5 text-center text-lg"></i></div>
                        <div class="sidebar-text flex-1 flex justify-between items-center pr-4">
                            <span class="font-medium text-sm">Catálogo</span>
                            <i id="arrow-catalogo" class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                        </div>
                    </button>
                    
                    <div id="submenu-catalogo" class="hidden bg-slate-50 dark:bg-slate-900/30 overflow-hidden transition-all border-l-4 border-transparent">
                        <a href="#" onclick="switchView('usuarios', this); return false;" class="nav-item submenu-link flex items-center h-10 pl-20 text-slate-500 dark:text-slate-400 hover:text-blue-600 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i class="fas fa-user w-6"></i> Usuarios
                        </a>
                        <a href="#" onclick="switchView('incidentes', this); return false;" class="nav-item submenu-link flex items-center h-10 pl-20 text-slate-500 dark:text-slate-400 hover:text-blue-600 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i class="fas fa-exclamation-triangle w-6"></i> Incidentes
                        </a>
                        <a href="#" onclick="switchView('sucursales', this); return false;" class="nav-item submenu-link flex items-center h-10 pl-20 text-slate-500 dark:text-slate-400 hover:text-blue-600 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i class="fas fa-building w-6"></i> Sucursales
                        </a>
                        <a href="#" onclick="switchView('privilegios', this); return false;" class="nav-item submenu-link flex items-center h-10 pl-20 text-slate-500 dark:text-slate-400 hover:text-blue-600 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i class="fas fa-user-shield w-6"></i> Roles
                        </a>
                        <a href="#" onclick="switchView('ap', this); return false;" class="nav-item submenu-link flex items-center h-10 pl-20 text-slate-500 dark:text-slate-400 hover:text-blue-600 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i class="fas fa-sitemap w-6"></i> Areas Y Puestos
                        </a>
                    </div>
                </div>

                <a href="#" onclick="switchView('agentes', this); return false;" class="nav-item group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans">
                    <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-users-cog w-5 text-center text-lg"></i></div>
                    <span class="sidebar-text font-medium text-sm">Agentes</span>
                </a>

                <a href="#" onclick="switchView('reportes', this); return false;" class="nav-item group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans">
                    <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-file-invoice-dollar w-5 text-center text-lg"></i></div>
                    <span class="sidebar-text font-medium text-sm">Reportes (Pendiente)</span>
                </a>

                <a href="#" onclick="switchView('configuracion', this); return false;" class="nav-item group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans">
                    <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-cog w-5 text-center text-lg"></i></div>
                    <span class="sidebar-text font-medium text-sm">Configuración</span>
                </a>

            </nav>
            
            <div class="h-10 border-t border-slate-100 dark:border-slate-800 flex items-center text-slate-500 dark:text-slate-400 shrink-0 theme-trans">
                <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-server text-[10px]"></i></div>
                <div class="sidebar-text flex-1 flex items-center justify-between pr-4 overflow-hidden">
                    <p class="text-[10px] truncate" title="<?= htmlspecialchars($db_name_actual) ?>">DB: <?= htmlspecialchars($db_name_actual) ?></p>
                    
                    <span class="relative flex h-2 w-2 ml-2" id="db-status-container" title="Conexión Estable">
                        <span id="db-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span id="db-dot" class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    
                </div>
            </div>
            <div class="h-10 border-b border-slate-100 dark:border-slate-800 flex items-center text-slate-500 dark:text-slate-400 shrink-0 theme-trans">
                <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-id-card text-[10px]"></i></div>
                <div class="sidebar-text"><p class="text-[10px]">Lic: <?= htmlspecialchars($codigo_emp_actual) ?></p></div>
            </div>
            <div onclick="confirmLogout()" class="h-16 flex items-center hover:bg-slate-50 dark:hover:bg-slate-800/50 transition cursor-pointer text-slate-500 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 shrink-0 theme-trans">
                <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-sign-out-alt text-lg"></i></div>
                <span class="sidebar-text text-sm font-medium">Cerrar Sesión</span>
            </div>
        </aside>
        <!-- Termina Menu Hamburguesa -->

        <div class="flex-1 flex flex-col overflow-hidden relative w-full">
            
            <!-- Inicia Header -->
            <header class="h-16 bg-white dark:bg-darkcard shadow-sm flex items-center justify-between px-4 md:px-8 z-10 border-b border-slate-100 dark:border-slate-700">
                <!-- Inicia Boton Menu Hamburguesa pc -->
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="text-slate-400 hover:text-blue-600 focus:outline-none transform active:scale-95 transition p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-lg md:text-xl font-semibold text-slate-700 dark:text-slate-200 truncate">Panel de Control</h2>
                </div>
                <!-- Termina Boton Menu Hamburguesa pc -->
                
                <div class="flex items-center gap-3 md:gap-6">

                    <!-- boton modo negro -->
                    <button id="theme-toggle" class="p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 focus:outline-none">
                        <i id="theme-icon" class="fas fa-moon text-lg transform transition-transform duration-500 rotate-0"></i>
                    </button>
                    <!-- boton modo negro -->


                    <!-- notificaciones -->
                    <div class="relative">
                        <!-- boton notificaciones -->
                        <button onclick="toggleNotifications()" class="relative focus:outline-none p-1">
                            <i class="fas fa-bell text-slate-400 text-lg cursor-pointer hover:text-blue-600 dark:hover:text-blue-400"></i>
                            <span id="notifBadge" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full min-w-[18px] h-[18px] flex items-center justify-center text-[10px] font-bold transition-all duration-300 opacity-0 scale-0">
                                <!-- Se llenará dinámicamente -->
                            </span>
                        </button>
                        <!-- boton notificaciones -->

                        <!-- notificaciones -->
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-4 w-72 md:w-80 bg-white dark:bg-darkcard rounded-xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden z-50 transform origin-top-right">
                            <div class="p-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                                <h4 class="font-bold text-sm text-slate-700 dark:text-slate-200">Notificaciones</h4>
                                <button id="markAllReadBtn" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium px-2 py-1 rounded transition-colors duration-200">
                                    Marcar como leídas
                                </button>
                            </div>
                            <ul id="notifList" class="max-h-64 overflow-y-auto">
                                <li class="p-4 text-center text-xs text-slate-500">Cargando...</li>
                            </ul>
                        </div>
                        <!-- notificaciones -->
                    </div>
                    <!-- notificaciones -->

                    <!-- mini data user -->
                    <div class="flex items-center gap-3 border-l pl-3 md:pl-6 border-slate-200 dark:border-slate-700">
                        <div class="text-right hidden md:block">
                            <p id="headerUserName" class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                Cargando...
                            </p>
                            <p id="headerUserRole" class="text-xs text-slate-500 dark:text-slate-400">
                                ...
                            </p>
                        </div>
                        <img id="headerUserImg" 
                            src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" 
                            alt="User" 
                            class="w-8 h-8 md:w-10 md:h-10 rounded-full border-2 border-slate-200 dark:border-slate-600">
                    </div>
                    <!-- mini data user -->
                </div>
            </header>
            <!-- Termina Header -->

            <div id="toast-container" class="fixed top-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none"></div>


            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 dark:bg-darkbg p-4 md:p-8 theme-trans relative">
    
                <div id="view-dashboard" class="view-section animate-fade-in">


                    <!-- Inicia Info Rapìda contador de tickets -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                        
                        <!-- Total -->
                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tickets Totales</p>
                                    <h3 id="totalTickets" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">0</h3>
                                    <p id="totalChange" class="text-green-500 text-sm mt-2">
                                        <i class="fas fa-arrow-up"></i> 0% vs mes anterior
                                    </p>
                                </div>
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                                    <i class="fas fa-clipboard-list text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pendientes -->
                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pendientes</p>
                                    <h3 id="pendingTickets" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">0</h3>
                                    <p class="text-orange-500 text-sm mt-2 font-medium">Atención requerida</p>
                                </div>
                                <div class="p-3 bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tiempo Promedio -->
                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tiempo Promedio</p>
                                    <h3 id="avgTime" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">0 min</h3>
                                    <p id="timeChange" class="text-green-500 text-sm mt-2">
                                        <i class="fas fa-arrow-down"></i> 0 min
                                    </p>
                                </div>
                                <div class="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg">
                                    <i class="fas fa-stopwatch text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Satisfacción -->
                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Satisfacción</p>
                                    <h3 id="satisfaction" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">0%</h3>
                                    <p id="satisfactionText" class="text-slate-400 text-sm mt-2">0 tickets cerrados</p>
                                </div>
                                <div class="p-3 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg">
                                    <i class="fas fa-smile text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Termina Info Rapìda contador de tickets -->


                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8 mb-8">
                        <div class="bg-white dark:bg-darkcard p-4 md:p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 lg:col-span-2">
                            <h3 class="font-bold text-lg text-slate-700 dark:text-slate-200 mb-4">Flujo de Tickets</h3>
                            <div class="h-64"><canvas id="mainChart"></canvas></div>
                        </div>
                        <div class="bg-white dark:bg-darkcard p-4 md:p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
                            <h3 class="font-bold text-lg text-slate-700 dark:text-slate-200 mb-4">Distribución</h3>
                            <div class="h-64 flex justify-center"><canvas id="pieChart"></canvas></div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-darkcard p-4 md:p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <h3 class="font-bold text-lg text-slate-700 dark:text-slate-200">Tickets Recientes</h3>
                            <button onclick="toggleModal()" class="w-full sm:w-auto text-sm bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition shadow-md shadow-blue-500/30">
                                Ver Todo <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[600px]">
                                <thead>
                                    <tr class="text-slate-400 text-xs uppercase border-b border-slate-100 dark:border-slate-700">
                                        <th class="py-3">ID</th>
                                        <th class="py-3">Asunto</th>
                                        <th class="py-3">Empleado</th>
                                        <th class="py-3">Agente</th>
                                        <th class="py-3">Prioridad</th>
                                        <th class="py-3">Estado</th>
                                        <th class="py-3">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm text-slate-600 dark:text-slate-300">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="view-tickets" class="view-section hidden animate-fade-in pb-10">
    
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Gestión de Tickets</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Administra, filtra y asigna los tickets de soporte.</p>
                    </div>

                    <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                            
                            <div class="lg:col-span-1">
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Buscar</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <i class="fas fa-search text-slate-400 text-xs"></i>
                                    </div>
                                    <input type="text" id="filter-search" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2 dark:bg-slate-800 dark:border-slate-600 dark:placeholder-slate-400 dark:text-white" placeholder="ID, Asunto o Agente...">
                                </div>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Estado</label>
                                <select id="filter-status" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                    <option value="all">Todos</option>
                                    <option value="abierto">Abierto</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="proceso">En Proceso</option>
                                    <option value="resuelto">Resuelto</option>
                                    <option value="sin_asignar">Sin Asignar</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Prioridad</label>
                                <select id="filter-priority" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                    <option value="all">Todas</option>
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Desde</label>
                                <input id="filter-date-start" type="date" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400">
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Hasta</label>
                                <input type="date" id="filter-date-end" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400">
                            </div>

                            <div class="flex items-end">
                                <button onclick="resetTicketFilters()" class="w-full text-sm bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition flex items-center justify-center gap-1">
                                    <i class="fas fa-redo-alt text-xs"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase">
                                    <tr>
                                        <th class="p-4 w-24">ID</th>
                                        <th class="p-4 w-40">Asunto</th>
                                        <th class="p-4 w-40">Empleado</th>
                                        <th class="p-4 w-40">Agente</th>
                                        <th class="p-4 w-32">Prioridad</th>
                                        <th class="p-4 w-32">Estado</th>
                                        <th class="p-4 w-32">Fecha</th>
                                        <th class="p-4 w-20 text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tickets-table-body" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">


                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <span id="pagination-info-T" class="text-xs text-slate-500 dark:text-slate-400">
                            </span>
                            <div id="pagination-controls-T" class="flex items-center gap-1">
                            </div>
                        </div>          
                    </div>
                </div>

                <div id="view-asignados" class="view-section hidden animate-fade-in pb-10">
    
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Gestión de Mis Tickets</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Administra, filtra y asigna los tickets de soporte.</p>
                    </div>

                    <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                            
                            <div class="lg:col-span-1">
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Buscar</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <i class="fas fa-search text-slate-400 text-xs"></i>
                                    </div>
                                    <input type="text" id="my-filter-search" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2 dark:bg-slate-800 dark:border-slate-600 dark:placeholder-slate-400 dark:text-white" placeholder="ID, Asunto o Agente...">
                                </div>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Estado</label>
                                <select id="my-filter-status" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                    <option value="all">Todos</option>
                                    <option value="abierto">Abierto</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="proceso">En Proceso</option>
                                    <option value="resuelto">Resuelto</option>
                                    <option value="sin_asignar">Sin Asignar</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Prioridad</label>
                                <select id="my-filter-priority" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                    <option value="all">Todas</option>
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Desde</label>
                                <input id="my-filter-date-start" type="date" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400">
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Hasta</label>
                                <input id="my-filter-date-end" type="date" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400">
                            </div>

                            <div class="flex items-end">
                                <button onclick="resetMyTicketFilters()" class="w-full text-sm bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition flex items-center justify-center gap-1">
                                    <i class="fas fa-redo-alt text-xs"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase">
                                    <tr>
                                        <th class="p-4 w-24">ID</th>
                                        <th class="p-4 w-40">Asunto</th>
                                        <th class="p-4 w-40">Empleado</th>
                                        <th class="p-4 w-40">Agente</th>
                                        <th class="p-4 w-32">Prioridad</th>
                                        <th class="p-4 w-32">Estado</th>
                                        <th class="p-4 w-32">Fecha</th>
                                        <th class="p-4 w-20 text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="my-tickets-table-body" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
    

                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <span id="my-pagination-info" class="text-xs text-slate-500 dark:text-slate-400">
                            </span>
                            <div id="my-pagination-controls" class="flex items-center gap-1">
                            </div>
                        </div>          
                    </div>
                </div>

                <!-- listo -->
                <div id="view-usuarios" class="view-section hidden animate-fade-in pb-10">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Catálogo de Usuarios</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Administra el acceso y roles del sistema.</p>
                        </div>
                        <button onclick="openUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2 shadow-lg shadow-blue-500/30">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Total Usuarios</p>
                                <h3 id="kpi-total" class="text-2xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Activos</p>
                                <h3 id="kpi-active" class="text-2xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 flex items-center justify-center"><i class="fas fa-user-check"></i></div>
                        </div>
                        <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Suspendidos</p>
                                <h3 id="kpi-suspended" class="text-2xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-orange-50 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center"><i class="fas fa-user-clock"></i></div>
                        </div>
                        <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Inactivos</p>
                                <h3 id="kpi-inactive" class="text-2xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600 flex items-center justify-center"><i class="fas fa-user-slash"></i></div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-darkcard p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                            
                            <div class="lg:col-span-2">
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Buscar</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"><i class="fas fa-search text-slate-400 text-xs"></i></div>
                                    <input type="text" id="usr-filter-search" onkeyup="loadUsersTable()" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white" placeholder="Nombre, Correo o #Folio...">
                                </div>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Rol</label>
                                <select id="usr-filter-role" onchange="loadUsersTable()" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                    <option value="0">Todos</option>
                                    </select>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Estado</label>
                                <select id="usr-filter-status" onchange="loadUsersTable()" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                    <option value="0">Todos</option>
                                    </select>
                            </div>

                            <div>
                                <button onclick="resetUserFilters()" class="w-full text-sm bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition flex items-center justify-center gap-1">
                                    <i class="fas fa-undo-alt text-xs"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[800px]">
                                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase">
                                    <tr>
                                        <th class="p-4">Usuario</th>
                                        <th class="p-4">Rol</th>
                                        <th class="p-4">Sucursal</th>
                                        <th class="p-4">Fecha Ingreso</th>
                                        <th class="p-4">Estado</th>
                                        <th class="p-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="users-table-body" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- listo -->

                <!-- listo -->
                <div id="view-incidentes" class="view-section hidden animate-fade-in pb-10">
    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Matriz de Incidentes</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Configura niveles, tiempos de respuesta y prioridades.</p>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="openLevelModal()" class="bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                <i class="fas fa-layer-group"></i> Crear Nivel
                            </button>
                            <button onclick="openIncidentModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition shadow-lg shadow-blue-500/30 flex items-center gap-2">
                                <i class="fas fa-plus"></i> Nueva Incidencia
                            </button>
                        </div>
                    </div>

                    <div id="incident-levels-container" class="space-y-8">
                    </div>

                </div>
                <!-- listo -->

                <!-- listo -->
                <div id="view-sucursales" class="view-section hidden animate-fade-in pb-10">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Sucursales</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Gestión de ubicaciones y oficinas.</p>
                        </div>
                        <button onclick="openBranchModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2 shadow-lg shadow-blue-500/30">
                            <i class="fas fa-plus"></i> Nueva Sucursal
                        </button>
                    </div>

                    <div id="branches-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>

                </div>
                <!-- listo -->

                <!-- listo -->
                <div id="view-privilegios" class="view-section hidden animate-fade-in pb-10">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Roles</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400"> </p>
                        </div>
                        
                        <!--
                        <button onclick="openRoleModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-2 shadow-lg shadow-blue-500/30">
                            <i class="fas fa-shield-alt"></i> Crear Rol
                        </button> -->
                    </div>

                    <div id="roles-container" class="space-y-4">
                    </div>

                </div>
                <!-- listo -->

                <!-- listo -->
                <div id="view-ap" class="view-section hidden animate-fade-in pb-10">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Áreas y Puestos</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Gestiona la estructura organizacional.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center bg-slate-100 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                                <h3 class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                    <i class="fas fa-building text-blue-500"></i> Áreas / Deptos.
                                </h3>
                                <button onclick="openAreaModal()" class="bg-white dark:bg-slate-700 hover:bg-blue-50 dark:hover:bg-slate-600 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-slate-600 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm">
                                    <i class="fas fa-plus"></i> Nueva Área
                                </button>
                            </div>

                            <div id="areas-container" class="space-y-3">
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center bg-slate-100 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                                <h3 class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                    <i class="fas fa-briefcase text-orange-500"></i> Puestos
                                </h3>
                                <button onclick="openPuestoModal()" class="bg-white dark:bg-slate-700 hover:bg-orange-50 dark:hover:bg-slate-600 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-slate-600 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm">
                                    <i class="fas fa-plus"></i> Nuevo Puesto
                                </button>
                            </div>

                            <div id="puestos-container" class="space-y-3">
                            </div>
                        </div>

                    </div>
                </div>
                <!-- listo -->

                <!-- Medio listo -->
                <div id="view-agentes" class="view-section hidden animate-fade-in pb-10">
    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Equipo de Soporte</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Vista general del rendimiento y carga de trabajo.</p>
                        </div>
                        <!--
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition shadow-lg shadow-blue-500/30 flex items-center gap-2">
                            <i class="fas fa-user-plus"></i> Nuevo Agente (pendiente)
                        </button>-->
                    </div>

                    <div id="agentes-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    </div>

                    <div class="mt-8 bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 transition-all">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="font-bold text-lg text-slate-800 dark:text-white">Rendimiento Comparativo del Equipo</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Tickets resueltos en los últimos 7 días.</p>
                            </div>
                            <select class="bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-lg block p-2 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                <option>Última Semana</option>
                                <option>Último Mes</option>
                            </select>
                        </div>
                        <div class="h-72 w-full">
                            <canvas id="teamPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
                

                <div id="view-reportes" class="view-section hidden animate-fade-in pb-10">
    
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Centro de Reportes</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Genera y descarga informes detallados en formato Excel.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                        <div class="report-card bg-white dark:bg-darkcard rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 overflow-hidden flex flex-col h-full transition-all duration-300">
                            <div class="p-6 flex-1">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-blue-500/30 shadow-lg shrink-0">
                                        <i class="fas fa-file-alt text-lg"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white truncate">Reporte General</h3>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                                    Exporta un listado completo de tickets con detalles técnicos.
                                </p>

                                <div class="space-y-6">
                                    
                                    <div>
                                        <label class="block mb-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Rango de Fechas</label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <input type="date" id="report-desde-general" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400 w-full">
                                            <input type="date" id="report-hasta-general" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400 w-full">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Opciones</label>
                                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-4">
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <input type="checkbox" name="Incluir_comentarios" checked class="w-4 h-4 text-blue-600 bg-white border-slate-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-slate-700 dark:border-slate-600">
                                                <span class="text-sm text-slate-600 dark:text-slate-300 font-medium group-hover:text-blue-600 transition">Incluir Comentarios</span>
                                            </label>
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <input type="checkbox" class="w-4 h-4 text-blue-600 bg-white border-slate-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:bg-slate-700 dark:border-slate-600">
                                                <span class="text-sm text-slate-600 dark:text-slate-300 font-medium group-hover:text-blue-600 transition">Solo Cerrados</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30">
                                <button data-report="general" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-bold rounded-lg text-sm px-5 py-3 text-center transition flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-download"></i> Generar Excel
                                </button>
                            </div>
                        </div>

                        <div class="report-card bg-white dark:bg-darkcard rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 overflow-hidden flex flex-col h-full transition-all duration-300">
                            <div class="p-6 flex-1">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-indigo-500/30 shadow-lg shrink-0">
                                        <i class="fas fa-users text-lg"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white truncate">Bitácora de Agentes</h3>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                                    Métricas detalladas de productividad y eficiencia.
                                </p>

                                <div class="space-y-6">
                                    <div>
                                        <label class="block mb-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Agente</label>
                                        <select id="agentes-select" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-white">
                                            <option selected value="">Selecciona un agente...</option>
                                            </select>
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Rango de Fechas</label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <input type="date" id="report-desde-agente" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400 w-full">
                                            <input type="date" id="report-hasta-agente" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400 w-full">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Métricas a incluir</label>
                                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-100 dark:border-slate-700 flex flex-col gap-3">
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <input type="checkbox" value="tickets_asignados-agente" checked class="w-4 h-4 text-indigo-600 bg-white border-slate-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-slate-700 dark:border-slate-600">
                                                <span class="text-sm text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-500 transition">Tickets Asignados</span>
                                            </label>
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <input type="checkbox" value="tickets_cerrados-agente" checked class="w-4 h-4 text-indigo-600 bg-white border-slate-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-slate-700 dark:border-slate-600">
                                                <span class="text-sm text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-500 transition">Tickets Cerrados</span>
                                            </label>
                                            <label class="flex items-center space-x-3 cursor-pointer group">
                                                <input type="checkbox" value="tickets_abiertos-agente" class="w-4 h-4 text-indigo-600 bg-white border-slate-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-slate-700 dark:border-slate-600">
                                                <span class="text-sm text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-500 transition">Tickets Abiertos</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30">
                                <button data-report="agentes" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-bold rounded-lg text-sm px-5 py-3 text-center transition flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-download"></i> Generar Excel
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Medio listo -->



                <div id="view-configuracion" class="view-section hidden animate-fade-in pb-10">
    
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Configuración</h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

                        <div class="lg:col-span-1">
                            <nav class="space-y-2 flex flex-col">
                                <button onclick="switchConfigTab('general', this)" class="config-tab-btn active w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all bg-blue-600 text-white shadow-md">
                                    <i class="fas fa-user-circle w-5 text-center"></i> Información General
                                </button>
                                
                                <button onclick="switchConfigTab('seguridad', this)" class="config-tab-btn w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <i class="fas fa-lock w-5 text-center"></i> Seguridad
                                </button>
                                
                                <button onclick="switchConfigTab('notificaciones', this)" class="config-tab-btn w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <i class="fas fa-bell w-5 text-center"></i> Notificaciones
                                </button>

                                <button onclick="switchConfigTab('sesiones', this)" class="config-tab-btn w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <i class="fas fa-laptop-house w-5 text-center"></i> Sesiones Activas
                                </button>
                            </nav>
                        </div>

                        <div class="lg:col-span-3">
                            
                            <div class="bg-white dark:bg-darkcard rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 min-h-[500px]">

                                <div id="panel-general" class="config-panel animate-fade-in">
                                    <div class="mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Información del Perfil</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Gestiona tu foto y datos personales.</p>
                                    </div>

                                    <div class="mb-8 pb-8 border-b border-slate-100 dark:border-slate-700">
                                        <label class="block mb-4 text-sm font-medium text-slate-700 dark:text-slate-300">Foto de Perfil</label>
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                                            
                                            <div class="w-24 h-24 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden border-2 border-slate-200 dark:border-slate-600 relative group">
                                                <img id="profile-preview" src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="w-full h-full object-cover">
                                            </div>
                                            
                                            <div class="flex-1">
                                                <input type="file" id="file-upload" class="hidden" accept="image/*" onchange="handleFileSelect()">
                                                
                                                <div class="flex gap-4 mb-3">
                                                    <button onclick="document.getElementById('file-upload').click()" class="text-blue-600 hover:underline text-sm font-medium flex items-center gap-2">
                                                        <i class="fas fa-cloud-upload-alt"></i> Seleccionar Imagen
                                                    </button>
                                                </div>
                                                
                                                <div class="flex gap-3">
                                                    <button id="btn-save-photo" disabled class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-700">
                                                        <i class="fas fa-save"></i> Guardar Foto
                                                    </button>
                                                    
                                                    <button id="btn-delete-photo" disabled class="bg-red-500 text-white px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-red-600">
                                                        <i class="fas fa-trash"></i> Eliminar Foto
                                                    </button>
                                                </div>
                                                <p class="text-[10px] text-slate-400 mt-2">Formatos: JPG, PNG. Máx 5 MB.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="profile-form">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Datos Personales</h4>
                                            
                                            <button type="button" id="btn-edit-profile" onclick="enableEditMode()" class="text-blue-600 hover:text-blue-700 text-sm font-medium transition">
                                                <i class="fas fa-pen mr-1"></i> Editar Información
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                            <div>
                                                <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">1er Nombre:</label>
                                                <input type="text" id="profile-firstname" name="firstname" disabled class="profile-input w-full rounded-lg p-2.5 border border-transparent transition-colors bg-white text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-800 dark:disabled:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">2do Nombre:</label>
                                                <input type="text" id="profile-secondname" name="secondname" disabled class="profile-input w-full rounded-lg p-2.5 border border-transparent transition-colors bg-white text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-800 dark:disabled:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Correo Electrónico</label>
                                                <input type="email" id="profile-email" name="email" disabled class="profile-input w-full rounded-lg p-2.5 border border-transparent transition-colors bg-white text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-800 dark:disabled:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">1er Apellido:</label>
                                                <input type="text" id="profile-firstapellido" name="firstapellido" disabled class="profile-input w-full rounded-lg p-2.5 border border-transparent transition-colors bg-white text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-800 dark:disabled:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">2do Apellido:</label>
                                                <input type="text" id="profile-secondapellido" name="secondapellido" disabled class="profile-input w-full rounded-lg p-2.5 border border-transparent transition-colors bg-white text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-800 dark:disabled:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Celular</label>
                                                <input type="tel" id="profile-celular" name="celular" disabled class="profile-input w-full rounded-lg p-2.5 border border-transparent transition-colors bg-white text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-800 dark:disabled:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>

                                            <div>
                                                <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Extensión</label>
                                                <input type="number" id="profile-extension" name="extension" disabled class="profile-input w-full rounded-lg p-2.5 border border-transparent transition-colors bg-white text-slate-800 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-white dark:disabled:bg-slate-800 dark:disabled:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            </div>
                                        </div>

                                        <div id="action-buttons-profile" class="hidden flex justify-end gap-3 animate-fade-in mt-4">
                                            <button type="button" onclick="cancelEditMode()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm font-medium transition dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200">
                                                Cancelar
                                            </button>
                                            <button type="button" onclick="saveProfileChanges()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition shadow-md">
                                                Guardar Cambios
                                            </button>
                                        </div>
                                    </form>

                                </div>


                                <div id="panel-seguridad" class="config-panel hidden animate-fade-in">
                                    <div class="mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Cambiar Contraseña</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Es necesario validar tu identidad primero.</p>
                                    </div>

                                    <div class="max-w-md mx-auto mt-8">
                                        
                                        <div id="password-step-1" class="animate-fade-in">
                                            <div class="mb-4">
                                                <label class="block mb-2 text-sm font-bold text-slate-600 dark:text-slate-400">Ingresa tu contraseña actual</label>
                                                <div class="relative">
                                                    <input type="password" id="current-pass" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 pl-4 dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••">
                                                    <i class="fas fa-lock absolute right-4 top-4 text-slate-400"></i>
                                                </div>
                                                <p id="pass-error" class="hidden text-red-500 text-xs mt-1">La contraseña es incorrecta.</p>
                                            </div>
                                            <button onclick="validateCurrentPass()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg text-sm font-bold transition shadow-lg">
                                                Validar
                                            </button>
                                        </div>

                                        <div id="password-step-2" class="hidden animate-fade-in">
                                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 mb-6 flex items-center gap-2 text-green-700 dark:text-green-400 text-xs font-medium">
                                                <i class="fas fa-check-circle"></i> Identidad validada correctamente.
                                            </div>
                                            
                                            <div class="space-y-4 mb-6">
                                                <div>
                                                    <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Nueva Contraseña</label>
                                                    <input type="password" id="new-pass" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Confirmar Nueva Contraseña</label>
                                                    <input type="password" id="confirm-pass" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <p id="new-pass-error" class="hidden text-red-500 text-xs"></p>
                                            </div>

                                            <div class="flex gap-3">
                                                <button onclick="cancelPasswordChange()" class="w-1/2 bg-slate-200 hover:bg-slate-300 text-slate-700 py-2.5 rounded-lg text-sm font-bold transition dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600">
                                                    Cancelar
                                                </button>
                                                <button onclick="updatePassword()" class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg text-sm font-bold transition shadow-lg">
                                                    Actualizar
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                

                                <div id="panel-notificaciones" class="config-panel hidden animate-fade-in">
                                    <div class="mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Preferencias de Notificación</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Elige qué alertas deseas recibir.</p>
                                    </div>
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Nuevos Tickets</p>
                                                <p class="text-xs text-slate-500">Alerta al crear ticket.</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="noti_nuevo" class="sr-only peer">
                                                <div class="w-11 h-6 bg-slate-200 rounded-full peer dark:bg-slate-700 peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                            </label>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">WhatsApp</p>
                                                <p class="text-xs text-slate-500">Recibir mensajes directos.</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="noti_whatsapp" class="sr-only peer">
                                                <div class="w-11 h-6 bg-slate-200 rounded-full peer dark:bg-slate-700 peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                            </label>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Correo Electrónico</p>
                                                <p class="text-xs text-slate-500">Resúmenes y alertas.</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="noti_email" class="sr-only peer">
                                                <div class="w-11 h-6 bg-slate-200 rounded-full peer dark:bg-slate-700 peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                            </label>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Sistema</p>
                                                <p class="text-xs text-slate-500">Avisos de mantenimiento.</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="noti_sistema" class="sr-only peer">
                                                <div class="w-11 h-6 bg-slate-200 rounded-full peer dark:bg-slate-700 peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                               <div id="panel-sesiones" class="config-panel hidden animate-fade-in">
                                    <div class="mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Sesiones Activas</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Dispositivos donde has iniciado sesión recientemente.</p>
                                    </div>

                                    <div id="sesiones-activas" class="space-y-4">
                                        </div>

                                    <br>

                                    <div class="mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Historial de sesiones</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Registro de sesiones cerradas anteriormente.</p>
                                    </div>
                                    
                                    <div id="sesiones-historial" class="space-y-4">
                                    </div>
                                </div>
                                                                

                            </div>
                        </div>
                    </div>
                </div>




                <div id="view-construccion" class="view-section hidden animate-fade-in">
                    <div class="h-96 flex flex-col items-center justify-center text-slate-400">
                        <i class="fas fa-tools text-6xl mb-4 opacity-50"></i>
                        <h2 class="text-xl font-bold">En Construcción</h2>
                        <p>Esta opción estará disponible próximamente.</p>
                    </div>
                </div>

            </main>

        </div>
    </div>

    <div id="ticketsModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-darkcard w-full max-w-4xl rounded-xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 flex flex-col max-h-[90vh]">
            <div class="p-4 md:p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h3 class="text-lg md:text-xl font-bold text-slate-800 dark:text-white">Historial Completo</h3>
                <button onclick="toggleModal()" class="text-slate-400 hover:text-red-500 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 md:p-6 overflow-y-auto">
                <div class="flex gap-4 mb-4">
                    <input type="text" id="searchTicketInput" placeholder="Buscar ticket..." class="w-full px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="overflow-x-auto">
                    <table id="historyTableBody" class="w-full text-left border-collapse min-w-[500px]">
                         <thead>
                            <tr class="bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 text-xs uppercase">
                                <th class="p-3">ID</th>
                                <th class="p-3">Asunto</th>
                                <th class="p-3">Estado</th>
                                <th class="p-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="historialtt" class="text-sm text-slate-600 dark:text-slate-300 divide-y divide-slate-100 dark:divide-slate-700">
                           
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Medio listo -->
    <div id="agentModal" class="modal fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 flex flex-col max-h-[90vh]">
            
            <div class="flex justify-end p-4 pb-0">
                <button onclick="closeAgentModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-xl p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700/50">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="px-8 pb-8 relative overflow-y-auto">
                
                <div class="flex flex-col sm:flex-row items-center sm:items-start mb-8 gap-6 border-b border-slate-100 dark:border-slate-700 pb-6">
                    <img id="modal-agent-img" src="" class="w-24 h-24 rounded-full border-4 border-slate-50 dark:border-slate-700 shadow-md bg-white object-cover">
                    
                    <div class="text-center sm:text-left flex-1 pt-2">
                        <h2 id="modal-agent-name" class="text-2xl font-bold text-slate-800 dark:text-white">Cargando...</h2>
                        <p id="modal-agent-role" class="text-blue-600 dark:text-blue-400 font-medium mb-2">...</p>
                        
                        <div id="modal-agent-status-container" class="flex flex-wrap justify-center sm:justify-start gap-2">
                            </div>
                    </div>

                    <div class="flex gap-3 mt-4 sm:mt-2">
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition shadow-md flex items-center gap-2">
                            <i class="fas fa-envelope"></i> Mensaje (PENDIENTE)
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold text-slate-800 dark:text-white mb-4 text-sm uppercase tracking-wider">Contacto</h4>
                            <ul class="space-y-4 text-sm">
                                <li class="flex items-center gap-3 text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center"><i class="fas fa-envelope"></i></div>
                                    <span id="modal-contact-email" class="truncate">...</span>
                                </li>
                                <li class="flex items-center gap-3 text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
                                    <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center"><i class="fas fa-phone"></i></div>
                                    <span id="modal-contact-ext">...</span>
                                </li>
                                <li class="flex items-center gap-3 text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
                                    <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center"><i class="fas fa-map-marker-alt"></i></div>
                                    <span id="modal-contact-sucursal">...</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-bold text-slate-800 dark:text-white mb-3 text-sm uppercase tracking-wider">Skills (PENDIENTE)</h4>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-full text-xs">Soporte Gral</span>
                                <span class="px-3 py-1 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-full text-xs">Atención</span>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-slate-800/40 p-5 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm">
                            <h4 class="font-bold text-slate-800 dark:text-white mb-4 text-sm">Rendimiento Semanal (PENDIENTE)</h4>
                            <div class="h-56 w-full">
                                <canvas id="agentPerformanceChart"></canvas>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- Medio listo -->

    <!-- listo -->
    <div id="userModal" class="modal fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 flex flex-col max-h-[90vh]">

            <!-- HEADER -->
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <div>
                    <h3 id="userModalTitle" class="text-lg font-bold text-slate-800 dark:text-white">Nuevo Usuario</h3>
                    <p class="text-xs text-slate-500">Complete la información para registrar el acceso.</p>
                </div>
                <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-xl"><i class="fas fa-times"></i></button>
            </div>

            <!-- FORMULARIO -->
            <div class="p-6 overflow-y-auto">
                <form id="userForm" enctype="multipart/form-data">
                    <input type="hidden" id="user-id" name="id"> 

                    <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-4 tracking-wider">Datos Personales</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Primer Nombre *</label>
                            <input id="firstname" name="firstname" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Segundo Nombre</label>
                            <input id="secondname" name="secondname" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Primer Apellido *</label>
                            <input id="firstapellido" name="firstapellido" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Segundo Apellido</label>
                            <input id="secondapellido" name="secondapellido" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Correo Electrónico *</label>
                            <input id="email" name="email" type="email" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Celular</label>
                            <input id="celular" name="celular" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Extensión</label>
                            <input id="extension" name="extension" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Avatar</label>
                            <input id="avatar" name="avatar" type="file" accept="image/*" class="w-full text-sm">
                        </div>

                    </div>

                    <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-4 tracking-wider border-t border-slate-100 dark:border-slate-700 pt-4">Acceso y Permisos</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Username *</label>
                            <input id="username" name="username" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Contraseña</label>
                            <input id="password" name="password" type="password" placeholder="12345678" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Rol *</label>
                            <select id="rol_id" name="rol_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white"></select>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Puesto</label>
                            <select id="puesto_id" name="puesto_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white"></select>
                        </div>

                       <div class="relative w-full" id="area-custom-wrapper">
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Área(s) Asignada(s) *</label>
                            
                            <select id="area_id" name="area_ids[]" multiple class="hidden"></select>
                            
                            <div id="custom-select-trigger" class="w-full min-h-[42px] bg-slate-50 border border-slate-200 rounded-lg p-1.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white flex flex-wrap gap-1 items-center cursor-pointer transition-colors hover:border-blue-400">
                                <div id="custom-select-badges" class="flex flex-wrap gap-1 flex-1 items-center min-h-[24px]">
                                    <span class="text-slate-400 ml-1 text-xs">Seleccione áreas...</span>
                                </div>
                                <div class="px-2 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <div id="custom-select-dropdown" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-xl hidden flex-col max-h-56 overflow-y-auto">
                                </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Sucursal *</label>
                            <select id="sucursal_id" name="sucursal_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white"></select>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Nivel de Incidencias</label>
                            <select id="incidencia_id" name="incidencia_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white"></select>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400">Estado Inicial</label>
                            <select id="status_id" name="status_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                <option value="1">Activo</option>
                                <option value="2">Suspendido</option>
                                <option value="3">Desactivado</option>
                            </select>
                        </div>

                    </div>

                    <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-3 tracking-wider border-t border-slate-100 dark:border-slate-700 pt-4">Permisos</h4>
                    <div id="permisos-container" class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-6 text-xs text-slate-600 dark:text-slate-400">
                        <p>Cargando permisos...</p>
                    </div>

                    <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-3 tracking-wider">Notificaciones</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 text-xs text-slate-600 dark:text-slate-400">
                        <label class="flex items-center gap-2"><input type="checkbox" name="noti_whatsapp" class="rounded text-blue-600"> WhatsApp</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="noti_email" class="rounded text-blue-600"> Email</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="noti_nuevo" class="rounded text-blue-600"> Nuevos Tickets</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="noti_sistema" class="rounded text-blue-600"> Sistema</label>
                    </div>

                </form>
            </div>

            <!-- BOTONES -->
            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 flex justify-end gap-3">
                <button onclick="closeUserModal()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-medium transition dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                    Cancelar
                </button>
                <button onclick="saveUser()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition shadow-md">
                    Guardar Usuario
                </button>
            </div>

        </div>
    </div>
    <!-- listo -->

    <!-- listo -->
    <div id="incidentModal" class="modal fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 flex flex-col max-h-[90vh]">
            
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h3 id="modal-inc-title" class="text-lg font-bold text-slate-800 dark:text-white">Nueva Incidencia</h3>
                <button onclick="closeIncidentModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-xl"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-6 overflow-y-auto">
                <form id="incidentForm" class="space-y-5">
                    <input type="hidden" id="inc-id">
                    <input type="hidden" id="inc-origin-level-id">

                    <div>
                        <label class="block mb-2 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">
                            Asignar a Niveles (Selección Múltiple)
                        </label>
                        <div id="levels-checkbox-container" class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto p-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg">
                            <p class="text-xs text-slate-400 col-span-2 text-center">Cargando niveles...</p>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Selecciona uno o más niveles para crear esta incidencia en ellos.</p>
                    </div>

                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Nombre del Incidente</label>
                        <input type="text" id="inc-name" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Ej. Falla de VPN">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">SLA (Respuesta)</label>
                            <div class="flex gap-2">
                                <input type="number" id="inc-sla-val" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="2">
                                <select id="inc-sla-unit" class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                    <option value="Min">Min</option>
                                    <option value="Hrs">Hrs</option>
                                    <option value="Días">Días</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Prioridad Default</label>
                            <select id="inc-priority" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                                <option value="BAJA" class="text-green-600 font-bold">BAJA</option>
                                <option value="MEDIA" class="text-orange-500 font-bold">MEDIA</option>
                                <option value="ALTA" class="text-red-500 font-bold">ALTA</option>
                                <option value="CRÍTICA" class="text-red-700 font-bold">CRÍTICA</option>
                            </select>
                        </div>
                    </div>

                </form>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 flex justify-end gap-3">
                <button onclick="closeIncidentModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm font-medium transition dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600">Cancelar</button>
                <button onclick="saveIncident()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition shadow-md">Guardar</button>
            </div>
        </div>
    </div>
    <!-- listo -->

    <!-- listo -->
    <div id="levelModal" class="modal fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600">
            
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Nuevo Nivel de Atención</h3>
                <button onclick="closeLevelModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-xl"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-6">
                <div class="mb-4">
                    <label class="block mb-2 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Nombre del Nivel / Mesa</label>
                    <input type="text" id="levelNameInput" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ej. Nivel 3 - Infraestructura">
                    <p class="text-[10px] text-slate-400 mt-2">Este nombre aparecerá como título de la nueva tabla de incidentes.</p>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="closeLevelModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm font-medium transition dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600">Cancelar</button>
                    <button onclick="saveNewLevel()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition shadow-md">Crear Nivel</button>
                </div>
            </div>
        </div>
    </div>
    <!-- listo -->

    <!-- listo -->
    <!-- Sucursales -->
    <div id="branchModal" class="modal fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 flex flex-col max-h-[90vh]">
            
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h3 id="branchModalTitle" class="text-lg font-bold text-slate-800 dark:text-white">Nueva Sucursal</h3>
                <button onclick="closeBranchModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition text-xl"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-6 overflow-y-auto">
                <form id="branchForm" class="space-y-4">
                    <input type="hidden" id="branch-id">
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Nombre Sucursal</label>
                            <input type="text" id="branch-name" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Ej. Sucursal Norte">
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Código</label>
                            <input type="text" id="branch-code" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white uppercase" placeholder="SP-00X">
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Dirección Completa</label>
                        <textarea id="branch-address" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="Calle, Número, Colonia, CP..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Teléfono</label>
                            <input type="tel" id="branch-phone" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="33 1234 5678">
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Gerente Encargado</label>
                            <input type="text" id="branch-manager" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white" placeholder="Nombre del responsable">
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Estado Operativo</label>
                        <select id="branch-status" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white">
                            <option value="OPERATIVA">Operativa (Activa)</option>
                            <option value="REMODELACION">En Remodelación</option>
                            <option value="CERRADA">Cerrada Temporalmente</option>
                            <option value="BAJA">Baja Definitiva</option>
                        </select>
                    </div>

                </form>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 flex justify-end gap-3">
                <button onclick="closeBranchModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm font-medium transition dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600">Cancelar</button>
                <button onclick="saveBranch()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition shadow-md">Guardar Sucursal</button>
            </div>
        </div>
    </div>
    <!-- Sucursales -->
    <!-- listo -->

    <!-- listo -->
    <div id="areaModal" class="modal fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 flex flex-col">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h3 id="areaModalTitle" class="text-base font-bold text-slate-800 dark:text-white">Nueva Área</h3>
                <button onclick="closeAreaModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5">
                <form id="areaForm" class="space-y-4">
                    <input type="hidden" id="area-id">
                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Nombre del Área</label>
                        <input type="text" id="area-name" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="Ej. Contabilidad">
                    </div>
                </form>
            </div>
            <div class="p-5 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 flex justify-end gap-2">
                <button onclick="closeAreaModal()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-bold transition dark:bg-slate-700 dark:text-white">Cancelar</button>
                <button onclick="saveArea()" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-md">Guardar</button>
            </div>
        </div>
    </div>
    <!-- listo -->

    <!-- listo -->
    <div id="puestoModal" class="modal fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 flex flex-col">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                <h3 id="puestoModalTitle" class="text-base font-bold text-slate-800 dark:text-white">Nuevo Puesto</h3>
                <button onclick="closePuestoModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5">
                <form id="puestoForm" class="space-y-4">
                    <input type="hidden" id="puesto-id">
                    <div>
                        <label class="block mb-1 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Nombre del Puesto</label>
                        <input type="text" id="puesto-name" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-orange-500" placeholder="Ej. Auxiliar Contable">
                    </div>
                </form>
            </div>
            <div class="p-5 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 flex justify-end gap-2">
                <button onclick="closePuestoModal()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-bold transition dark:bg-slate-700 dark:text-white">Cancelar</button>
                <button onclick="savePuesto()" class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold transition shadow-md">Guardar</button>
            </div>
        </div>
    </div>
    <!-- listo -->


    <div id="globalActionMenu" class="hidden fixed w-40 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-100 dark:border-slate-600 z-[9999] text-left overflow-hidden transform scale-95 transition-all duration-200 origin-top-right">
        </div>

    <div id="assignModal" class="modal fixed inset-0 bg-black/60 z-[100] flex items-center justify-center backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white dark:bg-darkcard w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-600 transform scale-95 transition-transform duration-300">
            
            <div class="p-5 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                <h3 class="text-base font-bold text-slate-800 dark:text-white">Asignar Ticket</h3>
                <button onclick="closeAssignModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-6">
                <p id="assignModalTicketInfo" class="text-xs text-slate-500 mb-4 font-mono font-bold text-center bg-slate-100 dark:bg-slate-800 p-2 rounded">#TCK-0000</p>
                
                <form id="assignForm" class="space-y-4">
                    <input type="hidden" id="assign-ticket-id">
                    <div>
                        <label class="block mb-2 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Seleccionar Agente</label>
                        <select id="assign-agent-select" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Cargando agentes...</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="p-5 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30 flex justify-end gap-2">
                <button onclick="closeAssignModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-bold transition dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600">Cancelar</button>
                <button onclick="submitAssignment()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-md">Guardar Asignación</button>
            </div>
        </div>
    </div>





    <!-- HEADER JS  -->
    <script src="../../../assets/js/header/user_data_g.js"></script>
    <script src="../../../assets/js/header/notificaciones.js"></script>
    <script src="../../../assets/js/admin/sucursales/save-suc.js"></script>
    <script src="../../../assets/js/admin/roles/get-roles.js"></script>
    <script src="../../../assets/js/admin/areas/areas.js"></script>
    <script src="../../../assets/js/admin/puestos/puestos.js"></script>
    <script src="../../../assets/js/admin/incidencias/create-nincidencia.js"></script>
    <script src="../../../assets/js/admin/incidencias/get-nincidencias.js"></script>
    <script src="../../../assets/js/admin/incidencias/incidents-monage.js"></script>


    <script src="../../../assets/js/admin/usuarios/users-view.js"></script>
    <script src="../../../assets/js/admin/usuarios/user_modal.js"></script>


    <script src="../../../assets/js/admin/agentes/agentes-view.js"></script>
    <script src="../../../assets/js/admin/agentes/agente-modal-perfil.js"></script>
    <script src="../../../assets/js/admin/reportes/reporte-agente-g.js"></script>
    <script src="../../../assets/js/admin/reportes/reporte-general-g.js"></script>
    <script src="../../../assets/js/config/config.sessions.js"></script>
    <script src="../../../assets/js/config/config.notifications.js"></script>
    <script src="../../../assets/js/config/config.security.js"></script>
    <script src="../../../assets/js/config/config.general.js"></script>
    <script src="../../../assets/js/dashboard/get-dashboard.js"></script>
    <script src="../../../assets/js/public/cache.js"></script>
    <script src="../../../assets/js/public/darkmode.js"></script>
    <script src="../../../assets/js/public/sidebar.js"></script>
    <script src="../../../assets/js/admin/tickets/tickets.view.js"></script>
    <script src="../../../assets/js/admin/tickets/my-tickets.view.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Monitoreo de conexión a la Base de Datos
        setInterval(function() {
            // Hacemos una petición ligera a cualquier archivo que use BD (ej. get_dashboard_stats.php o conexion.php)
            // Si el servidor o la BD caen, esto dará error.
            fetch('/helpdesk/db/conexion.php', { method: 'HEAD', cache: 'no-store' })
            .then(response => {
                if (response.ok) {
                    setConnectionStatus('online');
                } else {
                    setConnectionStatus('offline');
                }
            })
            .catch(() => {
                setConnectionStatus('offline'); // Si hay error de red
            });
        }, 15000); // Comprueba cada 15 segundos

        function setConnectionStatus(status) {
            const ping = document.getElementById('db-ping');
            const dot = document.getElementById('db-dot');
            const container = document.getElementById('db-status-container');

            if (status === 'online') {
                ping.classList.remove('hidden', 'bg-red-400', 'bg-yellow-400');
                ping.classList.add('bg-green-400');
                dot.classList.remove('bg-red-500', 'bg-yellow-500');
                dot.classList.add('bg-green-500');
                container.title = "Conexión Estable";
            } else {
                // Si se pierde la conexión, quitamos la animación de ping y lo ponemos rojo
                ping.classList.add('hidden'); 
                dot.classList.remove('bg-green-500');
                dot.classList.add('bg-red-500');
                container.title = "Sin conexión a la Base de Datos";
            }
        }
    </script>
  

    <script>
        //  MODAL
        function toggleModal() {
            document.getElementById('ticketsModal').classList.toggle('active');
        }
    </script>


    <script>
        function confirmLogout() {
            console.log("1. Función confirmLogout ejecutada");
            
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: "Tu sesión actual en este dispositivo será finalizada.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log("2. Usuario confirmó salir. Llamando a processLogout...");
                    // Ejecutar el cierre de sesión
                    processLogout();
                } else {
                    console.log("2. Usuario canceló el cierre de sesión.");
                }
            });
        }

        async function processLogout() {
            const url = '../../../db/login/logout.php'; // Asegúrate que esta ruta sea correcta desde donde estás
            console.log("3. Iniciando petición fetch a:", url);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                console.log("4. Objeto Response recibido:", response);
                console.log("   Status:", response.status);
                console.log("   StatusText:", response.statusText);

                // Verificamos si la respuesta es válida antes de intentar leer el JSON
                if (!response.ok) {
                    console.error("ERROR HTTP: La respuesta de red no fue ok.");
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Intentamos obtener el texto primero por si el backend devuelve HTML (error común en PHP)
                const textData = await response.text();
                console.log("5. Respuesta TEXTO cruda del Backend:", textData);

                let data;
                try {
                    data = JSON.parse(textData);
                    console.log("6. JSON parseado correctamente:", data);
                } catch (e) {
                    console.error("ERROR PARSING JSON: El backend no devolvió un JSON válido. Revisa el log #5.");
                    console.error(e);
                    Swal.fire('Error Técnico', 'El servidor devolvió datos inválidos (probablemente HTML de error PHP). Revisa la consola.', 'error');
                    return; // Detenemos aquí si no es JSON
                }

                if (data.success) {
                    console.log("7. Success es TRUE. Redirigiendo...");
                    
                    // --- DESCOMENTAR ESTO CUANDO FUNCIONE ---
                    window.location.href = '/HelpDesk/index.php'; 
                    // ----------------------------------------
                    
                    Swal.fire('Debug', 'Sesión cerrada. (Redirección pausada para ver consola)', 'success');

                } else {
                    console.warn("7. Success es FALSE. Mensaje del backend:", data.message);
                    Swal.fire('Error', data.message || 'No se pudo cerrar la sesión correctamente', 'error');
                }

            } catch (error) {
                console.error('8. ERROR EN EL CATCH (Red o Código):', error);
                
                // Forzar salida en caso de error de red para no dejar al usuario atrapado
                // --- DESCOMENTAR ESTO CUANDO FUNCIONE ---
                // window.location.href = '/HelpDesk/login.php'; 
            }
        }
    </script>

</body>
</html>