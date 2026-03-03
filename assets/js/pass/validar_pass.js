document.querySelector('.form-validar-pass').addEventListener('submit', async function (e) {
    e.preventDefault();
    const actual = document.getElementById('actual').value;

    try {
        const res = await fetch('/HelpDesk/db/login/validate_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ actual }),
            credentials: 'include' // <-- ¡Esto envía la cookie de sesión!
        });


        const data = await res.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Validación exitosa',
                text: 'Contraseña actual confirmada. Ahora puedes cambiarla.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Continuar',
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

            // Habilitar el formulario de cambio si estaba deshabilitado (opcional)
            document.querySelector('#nueva').disabled = false;
            document.querySelector('#confirmar').disabled = false;
            document.querySelector('form:not(.form-validar-pass) button[type="submit"]').disabled = false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'Error al validar contraseña',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Intentar de nuevo',
                background: '#1f1f1f',            // Color de fondo
                color: '#fff',                    // Color del texto
                iconColor: '#ff5e5e',             // Color del ícono
                confirmButtonColor: '#ff5e5e',    // Color del botón
                customClass: {
                    popup: 'mi-popup'
                },
                allowOutsideClick: true,
                allowEscapeKey: true,
                allowEnterKey: true,
                backdrop: true,
                heightAuto: false // <- evita que calcule automáticamente altura y mueva cosas
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'No se pudo conectar al servidor',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Intentar de nuevo',
            background: '#1f1f1f',            // Color de fondo
            color: '#fff',                    // Color del texto
            iconColor: '#ff5e5e',             // Color del ícono
            confirmButtonColor: '#ff5e5e',    // Color del botón
            customClass: {
                popup: 'mi-popup'
            },
            allowOutsideClick: true,
            allowEscapeKey: true,
            allowEnterKey: true,
            backdrop: true,
            heightAuto: false // <- evita que calcule automáticamente altura y mueva cosas
        });
    }
});