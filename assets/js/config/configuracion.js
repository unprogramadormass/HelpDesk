
document.addEventListener('DOMContentLoaded', () => {
    // Cargar datos al iniciar la página
    loadConfigData();
    
    // Configurar guardado automático de notificaciones
    setupNotificationListeners();
});

// --- 1. CARGA DE DATOS (Perfil, Notificaciones y Sesiones) ---
async function loadConfigData() {
    try {
        const response = await fetch('/HelpDesk/db/configuracion/get-config-data.php');
        const res = await response.json();

        if (res.success) {
            const p = res.profile;
            
            // Llenar inputs de Perfil
            document.getElementById('profile-firstname').value = p.firstname;
            document.getElementById('profile-secondname').value = p.secondname || '';
            document.getElementById('profile-firstapellido').value = p.firstapellido;
            document.getElementById('profile-secondapellido').value = p.secondapellido || '';
            document.getElementById('profile-email').value = p.email;
            document.getElementById('profile-celular').value = p.celular || '';
            document.getElementById('profile-extension').value = p.extension || '';

            // Guardar datos originales por si cancela edición
            originalProfileData = { ...p };

            // Avatar
            const avatarUrl = p.avatar ? `/HelpDesk/assets/img/avatars/${p.avatar}` : `https://api.dicebear.com/7.x/avataaars/svg?seed=${p.firstname}`;
            document.getElementById('profile-preview').src = avatarUrl;

            // Llenar Checkboxes de Notificaciones
            setCheckbox('noti_whatsapp', p.noti_whatsapp);
            setCheckbox('noti_email', p.noti_email);
            setCheckbox('noti_nuevo', p.noti_nuevo);
            setCheckbox('noti_sistema', p.noti_sistema);

            // Renderizar Sesiones
            renderSessions(res.sessions);
        } else {
            console.error("Error backend:", res.message);
        }
    } catch (error) {
        console.error("Error cargando config:", error);
    }
}

// Helper para marcar checkboxes
function setCheckbox(name, value) {
    const chk = document.querySelector(`#panel-notificaciones input[name="${name}"]`);
    if(chk) chk.checked = (value == 1);
}

// --- 2. EDICIÓN DE PERFIL ---
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
    
    // Restaurar valores originales
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
            // Actualizar datos originales con los nuevos
            originalProfileData = { ...data };
            
            // Mostrar alerta de éxito (usando showToast si existe, o alert simple)
            if(typeof showToast === 'function') {
                showToast("Perfil actualizado correctamente", "success");
            } else {
                alert("Perfil actualizado correctamente");
            }
            
            cancelEditMode(); // Volver a modo lectura
        } else {
            alert("Error al guardar: " + res.message);
        }
    } catch (error) { 
        console.error(error);
        alert("Error de conexión con el servidor");
    }
}

// --- 3. MANEJO DE FOTO DE PERFIL ---
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
        
        // Agregar evento click al botón guardar foto
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
            alert("Foto actualizada correctamente");
            document.getElementById('btn-save-photo').disabled = true;
            // Recargar header para ver foto nueva
            if(typeof updateUserData === 'function') updateUserData(); 
        } else {
            alert("Error: " + res.message);
        }
    } catch (error) { console.error(error); }
}

// --- 4. SEGURIDAD (PASSWORD) ---
async function validateCurrentPass() {
    const pass = document.getElementById('current-pass').value;
    const errorMsg = document.getElementById('pass-error');
    
    if(!pass) return;

    try {
        const response = await fetch('/HelpDesk/db/configuracion/change-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'validate', currentPass: pass })
        });
        const res = await response.json();

        if (res.success) {
            document.getElementById('password-step-1').classList.add('hidden');
            document.getElementById('password-step-2').classList.remove('hidden');
            errorMsg.classList.add('hidden');
        } else {
            errorMsg.classList.remove('hidden');
        }
    } catch (error) { console.error(error); }
}

function cancelPasswordChange() {
    document.getElementById('password-step-2').classList.add('hidden');
    document.getElementById('password-step-1').classList.remove('hidden');
    document.getElementById('current-pass').value = '';
}

// --- 5. NOTIFICACIONES (Guardado Automático) ---
function setupNotificationListeners() {
    const checks = document.querySelectorAll('#panel-notificaciones input[type="checkbox"]');
    checks.forEach(chk => {
        chk.addEventListener('change', async () => {
            const data = {
                seccion: 'notificaciones',
                noti_whatsapp: document.querySelector('input[name="noti_whatsapp"]').checked,
                noti_email: document.querySelector('input[name="noti_email"]').checked,
                noti_nuevo: document.querySelector('input[name="noti_nuevo"]').checked,
                noti_sistema: document.querySelector('input[name="noti_sistema"]').checked
            };

            await fetch('/HelpDesk/db/configuracion/update-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        });
    });
}

// --- 6. SESIONES ---
function renderSessions(sessions) {
    const container = document.querySelector('#panel-sesiones .space-y-4');
    if(!container) return;
    
    if (sessions.length === 0) {
        container.innerHTML = '<p class="text-xs text-slate-400">No hay historial de sesiones.</p>';
        return;
    }

    let html = '';
    sessions.forEach(s => {
        let icon = 'fa-desktop';
        if(s.dispositivo.toLowerCase().includes('mobile') || s.dispositivo.toLowerCase().includes('iphone')) icon = 'fa-mobile-alt';
        
        const badge = s.es_actual ? 
            `<span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold dark:bg-green-900/50 dark:text-green-400">Actual</span>` : '';

        // Botón Cerrar (Solo visual por ahora, necesitarías crear close_session.php para que funcione)
        const btnClose = !s.es_actual ? 
            `<button class="text-red-500 hover:text-red-700 text-xs font-bold border border-red-200 dark:border-red-900/30 px-3 py-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">Cerrar Sesión</button>` : '';

        html += `
            <div class="flex items-center justify-between p-4 ${s.es_actual ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100' : 'bg-white dark:bg-slate-800/50 border-slate-100'} rounded-xl border">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 ${s.es_actual ? 'bg-white' : 'bg-slate-100'} dark:bg-slate-700 rounded-full flex items-center justify-center text-blue-600 shadow-sm">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            ${s.dispositivo} ${badge}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">${s.ubicacion} • ${s.ultimo_acceso}</p>
                    </div>
                </div>
                ${btnClose}
            </div>
        `;
    });
    container.innerHTML = html;
}

// --- 7. TABS (Navegación entre paneles) ---
function switchConfigTab(tabId, button) {
    const panels = document.querySelectorAll('.config-panel');
    panels.forEach(panel => panel.classList.add('hidden'));

    const targetPanel = document.getElementById('panel-' + tabId);
    if(targetPanel) {
        targetPanel.classList.remove('hidden');
        targetPanel.classList.remove('animate-fade-in');
        void targetPanel.offsetWidth; // Trigger reflow
        targetPanel.classList.add('animate-fade-in');
    }

    const buttons = document.querySelectorAll('.config-tab-btn');
    buttons.forEach(btn => {
        btn.className = "config-tab-btn w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800";
    });
    
    button.className = "config-tab-btn active w-full text-left px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-3 transition-all bg-blue-600 text-white shadow-md";
}