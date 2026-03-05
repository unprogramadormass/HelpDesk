<?php
require_once(__DIR__ . '/../../../db/security/validacion.php'); 
verificarAcceso([4, 5]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    <title>Operative Dashboard | HelpDesk</title>
    <link rel="icon" href="/HelpDesk/assets/img/public/ICO.ico" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

                <a href="#" onclick="switchView('asignados', this); return false;" class="nav-item group flex items-center h-12 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-blue-600 dark:hover:text-slate-200 theme-trans">
                    <div class="w-20 flex-shrink-0 flex justify-center items-center"><i class="fas fa-clipboard-list w-5 text-center text-lg"></i></div>
                    <span class="sidebar-text font-medium text-sm">Mis Tickets</span>
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
    
                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tickets Totales</p>
                                    <h3 id="totalTickets" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                                </div>
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                                    <i class="fas fa-clipboard-list text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Abiertos o en Proceso</p>
                                    <h3 id="pendingTickets" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                                </div>
                                <div class="p-3 bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Finalizados</p>
                                    <h3 id="closedTickets" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                                </div>
                                <div class="p-3 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-darkcard p-6 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 card-hover">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cancelados</p>
                                    <h3 id="cancelledTickets" class="text-2xl md:text-3xl font-bold text-slate-800 dark:text-white mt-1">...</h3>
                                </div>
                                <div class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg">
                                    <i class="fas fa-ban text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <!-- Termina Info Rapìda contador de tickets -->


                
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-4 h-full bg-white dark:bg-darkcard rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden flex flex-col min-h-[500px]">
                            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-darkcard">
                                
                                <div class="flex items-center gap-2 font-bold text-lg text-slate-700 dark:text-slate-200">
                                    <i class='bx bx-notepad text-blue-600'></i>
                                    <h3>Mis Pendientes</h3>
                                </div>

                                <div class="flex items-center gap-2">
                                    <select id="todo-filter" class="bg-slate-50 border border-slate-200 text-slate-600 text-xs font-medium rounded-lg focus:ring-blue-500 focus:border-blue-500 block py-2 pl-3 pr-8 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-300 outline-none transition-all cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700">
                                        <option value="todas">Todas</option>
                                        <option value="pendientes">Pendientes</option>
                                        <option value="completadas">Completadas</option>
                                        <option value="eliminadas">Eliminadas</option>
                                    </select>
                                    
                                    <button onclick="openAddPendiente()" class="group bg-blue-600 text-white w-9 h-9 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-all shadow-md shadow-blue-500/30 active:scale-95">
                                        <i class='fas fa-plus text-base transform transition-transform duration-300 group-hover:rotate-90 animate-pulse group-hover:animate-none'></i>
                                    </button>
                               </div>
                            </div>

                            <div class="p-4 flex-1 flex flex-col overflow-hidden bg-slate-50/30 dark:bg-slate-900/20">
                                <ul id="listaTareas" class="space-y-3 overflow-y-auto flex-1 pr-1 custom-scrollbar">
                                    <li class="p-4 bg-white dark:bg-slate-800/80 rounded-xl text-center text-slate-400 text-sm italic border border-slate-100 dark:border-slate-700 shadow-sm">
                                        No hay pendientes anotados.
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="lg:col-span-8 h-full flex flex-col bg-white dark:bg-darkcard rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                            <div class="p-5 md:p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-darkcard">
                                <h3 class="font-bold text-lg text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                    <i class="fas fa-ticket-alt text-blue-600 text-base"></i> Tickets Recientes
                                </h3>
                                <button onclick="toggleModala()" class="w-full sm:w-auto text-sm bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition shadow-md shadow-blue-500/30 font-medium flex items-center justify-center gap-2 active:scale-95">
                                    Nuevo Ticket <i class="fas fa-arrow-right text-xs"></i>
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto flex-1 p-4 md:p-5">
                                <table class="w-full text-left border-collapse min-w-[700px]">
                                    <thead>
                                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-xs uppercase font-semibold tracking-wide">
                                            <th class="py-3.5 px-4 rounded-tl-xl">ID</th>
                                            <th class="py-3.5 px-4">Asunto</th>
                                            <th class="py-3.5 px-4">Empleado</th>
                                            <th class="py-3.5 px-4">Agente</th>
                                            <th class="py-3.5 px-4">Prioridad</th>
                                            <th class="py-3.5 px-4">Estado</th>
                                            <th class="py-3.5 px-4">Fecha</th>
                                            <th class="py-3.5 px-4 rounded-tr-xl text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dashboard-tickets-tbody" class="text-sm text-slate-600 dark:text-slate-300 divide-y divide-slate-100 dark:divide-slate-700/50">    
                                    </tbody>
                                </table>
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


    
    <div id="ticketsModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="toggleModala()"></div>
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0 pointer-events-none">
            <div class="relative inline-block w-full max-w-3xl p-6 sm:p-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 shadow-2xl rounded-2xl pointer-events-auto animate-fade-in">
                
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        Crear Nuevo Ticket
                    </h3>
                    <button type="button" onclick="toggleModala()" class="text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 p-2 rounded-lg transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="form-nuevo-ticket" class="space-y-6" enctype="multipart/form-data">
                    
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3"><i class="fas fa-user mr-1"></i> Datos del Solicitante</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Nombre</label>
                                <p id="nt-nombre" class="text-sm font-medium text-slate-700 dark:text-slate-300">...</p>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Folio</label>
                                <p id="nt-folio" class="text-sm font-medium text-slate-700 dark:text-slate-300">...</p>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Sucursal</label>
                                <p id="nt-sucursal" class="text-sm font-medium text-slate-700 dark:text-slate-300">...</p>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Extensión</label>
                                <p id="nt-ext" class="text-sm font-medium text-slate-700 dark:text-slate-300">...</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div hidden>
                            <label class="block mb-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">Nivel de Incidencia</label>
                            <input type="text" id="nt-nivel-nombre" readonly class="w-full bg-slate-100 border border-slate-200 text-slate-500 font-bold text-sm rounded-xl block p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 outline-none cursor-not-allowed" placeholder="Cargando nivel...">
                            <input type="hidden" id="nt-nivel-id" name="nivel_incidencia_id" required>
                        </div>
                        <div>
                            <label class="block mb-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">Asunto / Problema <span class="text-red-500">*</span></label>
                            <select id="nt-asunto" name="TITULO_incidencia_id" required disabled class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 block p-3 dark:bg-slate-900/50 dark:border-slate-600 dark:text-white outline-none transition-all disabled:opacity-50 cursor-pointer">
                                <option value="" disabled selected>Cargando problemas...</option>
                            </select>
                        </div>

                        <div hidden>
                            <label class="block mb-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">Prioridad Asignada</label>
                            <input type="text" id="nt-prioridad" readonly class="w-full bg-slate-100 border border-slate-200 text-slate-500 font-bold text-sm rounded-xl block p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 outline-none cursor-not-allowed" placeholder="Se asignará automáticamente...">
                            <input type="hidden" id="nt-prioridad-hidden" name="prioridad" value="">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">Área / Departamento <span class="text-red-500">*</span></label>
                            
                            <select id="nt-area-select" name="area_id" required class="hidden w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 p-3 dark:bg-slate-900/50 dark:border-slate-600 dark:text-white outline-none transition-all cursor-pointer">
                                <option value="" disabled selected>Cargando áreas...</option>
                            </select>

                            <div id="nt-area-single-container" class="hidden">
                                <input type="text" id="nt-area-input" readonly class="w-full bg-slate-100 border border-slate-200 text-slate-500 font-bold text-sm rounded-xl block p-3 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 outline-none cursor-not-allowed">
                                <input type="hidden" id="nt-area-hidden" name="area_id">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">Descripción detallada <span class="text-red-500">*</span></label>
                        <textarea name="descripcion" rows="4" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 block p-3 dark:bg-slate-900/50 dark:border-slate-600 dark:text-white outline-none transition-all resize-none custom-scrollbar" placeholder="Describe el problema, paso a paso..."></textarea>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">Adjuntar Evidencia (Opcional)</label>
                        
                        <div class="flex items-center justify-center w-full">
                            <label id="drop-zone" for="nt-file" class="flex flex-col items-center justify-center w-full h-24 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 dark:hover:bg-bray-800 dark:bg-slate-900/50 hover:bg-slate-100 dark:border-slate-600 dark:hover:border-slate-500 dark:hover:bg-slate-800 transition-all duration-200">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 mb-2"></i>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><span class="font-semibold text-blue-600">Haz clic para subir</span> o arrastra y suelta aquí</p>
                                    <p class="text-[10px] text-slate-400 mt-1">PDF, DOCX, XLSX, JPG, PNG (Puedes subir varios)</p>
                                </div>
                                <input id="nt-file" type="file" multiple class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" />
                            </label>
                        </div>

                        <div id="file-preview-container" class="mt-3 flex flex-wrap gap-2 empty:hidden">
                            </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-5 mt-2 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" onclick="toggleModala()" class="w-full sm:w-auto px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">Cancelar</button>
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                            <i class="fas fa-paper-plane"></i> Generar Ticket
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div id="todoModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
    
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="toggleTodoModal()"></div>
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0 pointer-events-none">
            <div class="relative inline-block w-full max-w-md p-6 sm:p-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-800 shadow-2xl rounded-2xl pointer-events-auto animate-fade-in">
                
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
                            <i class="fas fa-tasks text-lg"></i>
                        </div>
                        Nuevo Pendiente
                    </h3>
                    <button type="button" onclick="toggleTodoModal()" class="text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 p-2 rounded-lg transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="form-nuevo-pendiente" class="space-y-5">
                    
                    <div>
                        <label class="block mb-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">¿Qué necesitas recordar? <span class="text-red-500">*</span></label>
                        <textarea name="tarea" rows="3" required class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block p-3 dark:bg-slate-900/50 dark:border-slate-600 dark:text-white outline-none transition-all shadow-sm resize-none custom-scrollbar" placeholder="Ej. Enviar reporte de incidencias a gerencia..."></textarea>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-5 mt-2 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" onclick="toggleTodoModal()" class="w-full sm:w-auto px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    



    <!-- HEADER JS  -->
    <script src="../../../assets/js/header/user_data_g.js"></script>
    <script src="../../../assets/js/header/notificaciones.js"></script>


    <script src="../../../assets/js/config/config.sessions.js"></script>
    <script src="../../../assets/js/config/config.notifications.js"></script>
    <script src="../../../assets/js/config/config.security.js"></script>
    <script src="../../../assets/js/config/config.general.js"></script>
    <script src="../../../assets/js/public/cache.js"></script>
    <script src="../../../assets/js/public/darkmode.js"></script>
    <script src="../../../assets/js/public/sidebar.js"></script>

    <script src="../../../assets/js/user/dashboard/data_info.js"></script>
    <script src="../../../assets/js/user/dashboard/api_pendientes.js"></script>
    <script src="../../../assets/js/user/tickets/user-t.js"></script>
    <script src="../../../assets/js/user/tickets/api_nuevo_ticket.js"></script>
    <script src="../../../assets/js/user/tickets/save_t.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  

    <script>
        // Función para mostrar / ocultar el modal de Tickets
            function toggleModala() {
                const modal = document.getElementById('ticketsModal');
                if (modal) {
                    modal.classList.toggle('hidden');
                }
            }
            function toggleTodoModal() {
                const modal = document.getElementById('todoModal');
                if (modal) {
                    modal.classList.toggle('hidden');
                }
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