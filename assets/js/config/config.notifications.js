// assets/js/admin/config/config.notifications.js

document.addEventListener('DOMContentLoaded', () => {
    setupNotificationListeners();
});

// Llamada desde config.general.js cuando llegan los datos
function fillNotificationData(p) {
    setCheckbox('noti_whatsapp', p.noti_whatsapp);
    setCheckbox('noti_email', p.noti_email);
    setCheckbox('noti_nuevo', p.noti_nuevo);
    setCheckbox('noti_sistema', p.noti_sistema);
}

function setCheckbox(name, value) {
    const chk = document.querySelector(`#panel-notificaciones input[name="${name}"]`);
    if(chk) chk.checked = (value == 1);
}

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

            // Reutilizamos el endpoint de update-profile
            await fetch('/HelpDesk/db/configuracion/update-profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        });
    });
}