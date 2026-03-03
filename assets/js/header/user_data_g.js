document.addEventListener("DOMContentLoaded", function() {
    
    function updateUserData() {
        // Referencias a los elementos del HTML
        const userNameElem = document.getElementById("headerUserName");
        const userRoleElem = document.getElementById("headerUserRole");
        const userImgElem  = document.getElementById("headerUserImg");

        // RUTA AL PHP (Verifica que sea correcta en tu proyecto)
        const url = '/HelpDesk/db/header/user_data.php'; 

        fetch(url)
            .then(response => {
                // Si el archivo no existe (404), lanzamos error
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                return response.json();
            })
            .then(data => {
                // Si el PHP devolvió un error (ej. "No hay sesión activa")
                if (data.error) {
                    console.warn("⚠️ Header User Data:", data.error);
                    if(userNameElem) userNameElem.textContent = "Invitado";
                    return;
                }

                // 1. Actualizar Nombre (Ej: "Juan M.")
                if (userNameElem && data.nombre_mostrar) {
                    if (userNameElem.textContent !== data.nombre_mostrar) {
                        userNameElem.textContent = data.nombre_mostrar;
                    }
                }

                // 2. Actualizar Rol (Ej: "Auxiliar - Agente")
                if (userRoleElem && data.rol_mostrar) {
                    if (userRoleElem.textContent !== data.rol_mostrar) {
                        userRoleElem.textContent = data.rol_mostrar;
                    }
                }

                // 3. Actualizar Avatar
                if (userImgElem) {
                    let avatarSrc;
                    
                    // Si trae imagen de BD
                    if (data.avatar && data.avatar.trim() !== "") {
                        avatarSrc = `/HelpDesk/assets/img/avatars/${data.avatar}`;
                    } else {
                        // Imagen por defecto (DiceBear)
                        // Usamos el nombre para generar una semilla única
                        const seed = data.nombre_mostrar || "User";
                        avatarSrc = `https://api.dicebear.com/7.x/avataaars/svg?seed=${seed}`;
                    }

                    // Solo cambiamos el src si es diferente para evitar parpadeos
                    if (userImgElem.getAttribute('src') !== avatarSrc) {
                         userImgElem.src = avatarSrc;
                    }
                }
            })
            .catch(error => {
                console.error("❌ Error actualizando header:", error);
            });
    }

    // Ejecutar al inicio
    updateUserData();

    // Actualizar cada 2 segundos
    setInterval(updateUserData, 2000); 
});