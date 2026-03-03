// assets/js/admin/config/config.general.js

let originalProfileData = {};

document.addEventListener('DOMContentLoaded', () => {
    loadConfigData();
    // Inicializar Tabs
    setupTabsListeners();
});

// --- CARGA DE DATOS PRINCIPAL ---
// Esta función orquesta todo: obtiene los datos y los reparte a los otros archivos
async function loadConfigData() {
    try {
        const response = await fetch('/HelpDesk/db/configuracion/get-config-data.php');
        const res = await response.json();

        if (res.success) {
            const p = res.profile;

            // 1. Llenar Perfil (General)
            fillProfileData(p);

            // 2. Llenar Notificaciones (Llama a función de config.notifications.js)
            if (typeof fillNotificationData === 'function') {
                fillNotificationData(p);
            }

            // 3. Renderizar Sesiones (Llama a función de config.sessions.js)
            // Activas
            if (typeof renderSessions === 'function' && res.sessions) {
                renderSessions(res.sessions);
            }

            // Historial
            if (typeof renderHistory === 'function' && res.history) {
                renderHistory(res.history);
            }

        } else {
            console.error("Error backend:", res.message);
        }
    } catch (error) {
        console.error("Error cargando config:", error);
    }
}

function fillProfileData(p) {
    document.getElementById('profile-firstname').value = p.firstname;
    document.getElementById('profile-secondname').value = p.secondname || '';
    document.getElementById('profile-firstapellido').value = p.firstapellido;
    document.getElementById('profile-secondapellido').value = p.secondapellido || '';
    document.getElementById('profile-email').value = p.email;
    document.getElementById('profile-celular').value = p.celular || '';
    document.getElementById('profile-extension').value = p.extension || '';

    // Guardar para cancelar
    originalProfileData = { ...p };

    // Avatar
    const avatarUrl = p.avatar 
        ? `/HelpDesk/assets/img/avatars/${p.avatar}` 
        : `https://api.dicebear.com/7.x/avataaars/svg?seed=${p.firstname}`;
    document.getElementById('profile-preview').src = avatarUrl;
}

// --- EDICIÓN DE PERFIL ---
function enableEditMode() {
    const inputs = document.querySelectorAll('.profile-input');
    inputs.forEach(input => {
        input.disabled = false;
        input.classList.remove('bg-slate-100', 'text-slate-500', 'disabled:cursor-not-allowed');
        input.classList.add('bg-white', 'text-slate-800');
    });
    document.getElementById('btn-edit-profile').classList.add('hidden');
    document.getElementById('action-buttons-profile').classList.remove('hidden');
}

function cancelEditMode() {
    const inputs = document.querySelectorAll('.profile-input');
    
    // Restaurar valores
    document.getElementById('profile-firstname').value = originalProfileData.firstname;
    document.getElementById('profile-secondname').value = originalProfileData.secondname || '';
    document.getElementById('profile-firstapellido').value = originalProfileData.firstapellido;
    document.getElementById('profile-secondapellido').value = originalProfileData.secondapellido || '';
    document.getElementById('profile-email').value = originalProfileData.email;
    document.getElementById('profile-celular').value = originalProfileData.celular || '';
    document.getElementById('profile-extension').value = originalProfileData.extension || '';

    inputs.forEach(input => {
        input.disabled = true;
        input.classList.add('bg-slate-100', 'text-slate-500', 'disabled:cursor-not-allowed');
        input.classList.remove('bg-white', 'text-slate-800');
    });
    document.getElementById('btn-edit-profile').classList.remove('hidden');
    document.getElementById('action-buttons-profile').classList.add('hidden');
}

async function saveProfileChanges() {
    const form = document.getElementById('profile-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/HelpDesk/db/configuracion/update-profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await response.json();
        
        if (res.success) {
            originalProfileData = { ...data };
            Swal.fire({
                icon: 'success',
                title: '¡Perfil actualizado!',
                text: 'Los cambios se han guardado correctamente.',
                confirmButtonColor: '#2563eb'
            });
            cancelEditMode();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: res.message,
                confirmButtonColor: '#2563eb'
            });
        }
    } catch (error) { 
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'Hubo un problema al conectar con el servidor.',
            confirmButtonColor: '#2563eb'
        });
    }
}

// --- FOTO DE PERFIL ---
function handleFileSelect() {
    const fileInput = document.getElementById('file-upload');
    const saveBtn = document.getElementById('btn-save-photo');
    const preview = document.getElementById('profile-preview');

    if (fileInput.files && fileInput.files[0]) {
        saveBtn.disabled = false;
        saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        
        const reader = new FileReader();
        reader.onload = function(e) { preview.src = e.target.result; }
        reader.readAsDataURL(fileInput.files[0]);
        
        saveBtn.onclick = uploadPhoto;
    }
}

async function uploadPhoto() {
    const fileInput = document.getElementById('file-upload');
    const formData = new FormData();
    formData.append('avatar', fileInput.files[0]);

    try {
        const response = await fetch('/HelpDesk/db/configuracion/upload-avatar.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();
        
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Foto actualizada!',
                text: 'Tu avatar se ha cambiado con éxito.',
                confirmButtonColor: '#2563eb'
            }).then(() => {
                // Opcional: recargar la página para actualizar el header si es necesario
                // window.location.reload(); 
            });
            document.getElementById('btn-save-photo').disabled = true;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: res.message,
                confirmButtonColor: '#2563eb'
            });
        }
    } catch (error) { 
        console.error(error); 
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'Hubo un problema al subir la imagen.',
            confirmButtonColor: '#2563eb'
        });
    }
}

// --- TABS (Navegación) ---
function setupTabsListeners() {
    // Si usas onclick en el HTML, esta función se llama switchConfigTab
    // y debe estar disponible globalmente.
    window.switchConfigTab = function(tabId, button) {
        const panels = document.querySelectorAll('.config-panel');
        panels.forEach(panel => panel.classList.add('hidden'));

        const targetPanel = document.getElementById('panel-' + tabId);
        if(targetPanel) {
            targetPanel.classList.remove('hidden');
            targetPanel.classList.remove('animate-fade-in');
            void targetPanel.offsetWidth; 
            targetPanel.classList.add('animate-fade-in');
        }

        const buttons = document.querySelectorAll('.config-tab-btn');
        buttons.forEach(btn => {
            btn.className = "config-tab-btn w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800";
        });
        
        button.className = "config-tab-btn active w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all bg-blue-600 text-white shadow-md";
    }
}