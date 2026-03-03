// assets/js/admin/config/config.security.js

// 1. Validar Contraseña Actual
async function validateCurrentPass() {
    const pass = document.getElementById('current-pass').value;
    const errorMsg = document.getElementById('pass-error');
    
    if(!pass) {
        errorMsg.textContent = "Ingresa tu contraseña actual.";
        errorMsg.classList.remove('hidden');
        return;
    }

    try {
        const response = await fetch('/HelpDesk/db/configuracion/change.password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'validate', currentPass: pass })
        });
        const res = await response.json();

        if (res.success) {
            // Ocultar Paso 1, Mostrar Paso 2
            document.getElementById('password-step-1').classList.add('hidden');
            document.getElementById('password-step-2').classList.remove('hidden');
            errorMsg.classList.add('hidden');
        } else {
            errorMsg.textContent = res.message || "La contraseña es incorrecta.";
            errorMsg.classList.remove('hidden');
        }
    } catch (error) { 
        console.error(error); 
        // Alerta estética en caso de fallo de internet/servidor
        Swal.fire({
            title: 'Error de conexión',
            text: 'No se pudo contactar al servidor para validar la contraseña.',
            icon: 'error',
            confirmButtonColor: '#3b82f6',
            customClass: { popup: 'dark:bg-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 rounded-xl' }
        });
    }
}

// 2. Actualizar Nueva Contraseña
async function updatePassword() {
    const newPass = document.getElementById('new-pass').value;
    const confirmPass = document.getElementById('confirm-pass').value;
    const errorMsg = document.getElementById('new-pass-error');

    // Validaciones frontend básicas (Se mantienen visuales en el DOM por UX)
    if (!newPass || !confirmPass) {
        errorMsg.textContent = "Todos los campos son obligatorios.";
        errorMsg.classList.remove('hidden');
        return;
    }
    if (newPass.length < 6) {
        errorMsg.textContent = "La contraseña debe tener al menos 6 caracteres.";
        errorMsg.classList.remove('hidden');
        return;
    }
    if (newPass !== confirmPass) {
        errorMsg.textContent = "Las contraseñas no coinciden.";
        errorMsg.classList.remove('hidden');
        return;
    }

    try {
        const response = await fetch('/HelpDesk/db/configuracion/change.password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', newPass: newPass })
        });
        const res = await response.json();

        if (res.success) {
            // Reemplazo del alert() normal por un Toast elegante
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Contraseña actualizada exitosamente',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: { popup: 'dark:bg-slate-800 dark:text-white border border-slate-200 dark:border-slate-700' }
            });
            
            cancelPasswordChange(); // Resetea todo
        } else {
            // Reemplazo del mensaje de error simple por un Popup
            Swal.fire({
                title: 'Error al actualizar',
                text: res.message || "No se pudo cambiar la contraseña.",
                icon: 'error',
                confirmButtonColor: '#3b82f6',
                customClass: { popup: 'dark:bg-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 rounded-xl' }
            });
            errorMsg.classList.add('hidden'); // Ocultamos el error de texto si usamos la alerta
        }
    } catch (error) {
        console.error(error);
        // Reemplazo del alert() de error de conexión por un Popup
        Swal.fire({
            title: 'Error de conexión',
            text: 'Hubo un problema al comunicarse con el servidor.',
            icon: 'error',
            confirmButtonColor: '#3b82f6',
            customClass: { popup: 'dark:bg-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 rounded-xl' }
        });
    }
}

// 3. Cancelar / Resetear Formulario
function cancelPasswordChange() {
    // Volver a mostrar paso 1
    document.getElementById('password-step-2').classList.add('hidden');
    document.getElementById('password-step-1').classList.remove('hidden');
    
    // Limpiar inputs
    document.getElementById('current-pass').value = '';
    document.getElementById('new-pass').value = '';
    document.getElementById('confirm-pass').value = '';
    
    // Ocultar errores
    document.getElementById('pass-error').classList.add('hidden');
    document.getElementById('new-pass-error').classList.add('hidden');
}