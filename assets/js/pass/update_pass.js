document.querySelector('form:not(.form-validar-pass)').addEventListener('submit', async function (e) {
    e.preventDefault();

    const nueva = document.getElementById('nueva').value;
    const confirmar = document.getElementById('confirmar').value;

    if (nueva !== confirmar) {
        Swal.fire({
            icon: 'warning',
            title: 'Las contraseñas no coinciden',
            text: 'Por favor, asegúrate de escribir la misma contraseña dos veces.',
            background: '#1f1f1f',            // Color de fondo
            color: '#fff',                    // Color del texto
            customClass: {
                popup: 'mi-popup'
            },
            allowOutsideClick: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            backdrop: true,
            heightAuto: false // <- evita que calcule automáticamente altura y mueva cosas
        });
        return;
    }

    try {
        const res = await fetch('/HelpDesk/db/login/change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nueva, confirmar })
        });

        const data = await res.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Contraseña actualizada',
                text: 'Redirigiendo...',
                background: '#1f1f1f',            // Color de fondo
                color: '#fff',                    // Color del texto
                iconColor: '#2881f5',             // Color del ícono
                confirmButtonColor: '#2881f5',    // Color del botón
                customClass: {
                    popup: 'mi-popup'
                },
                allowOutsideClick: true,
                allowEscapeKey: true,
                allowEnterKey: true,
                backdrop: true,
                heightAuto: false // <- evita que calcule automáticamente altura y mueva cosas
            });

            setTimeout(() => {
                window.location.href = data.redirect;
            }, 2000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'No se pudo actualizar la contraseña'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'No se pudo conectar al servidor'
        });
    }
});