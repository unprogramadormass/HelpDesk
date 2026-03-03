// Variable para controlar si el modal está abierto (opcional)
let isLevelModalOpen = false;

// --- FUNCIONES DEL MODAL ---

function openLevelModal() {
    const modal = document.getElementById('levelModal');
    // Resetear el input
    document.getElementById('levelNameInput').value = '';
    
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100', 'pointer-events-auto');
    isLevelModalOpen = true;
    
    // Enfocar el input automáticamente para mejor UX
    setTimeout(() => document.getElementById('levelNameInput').focus(), 100);
}

function closeLevelModal() {
    const modal = document.getElementById('levelModal');
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
    isLevelModalOpen = false;
}

// --- FUNCIÓN PARA GUARDAR (CREAR TABLA) ---

async function saveNewLevel() {
    const nameInput = document.getElementById('levelNameInput');
    const nombreNivel = nameInput.value.trim();

    if (!nombreNivel) {
        // Asumiendo que tienes la funcion showToast de los ejemplos anteriores
        if(typeof showToast === "function") {
            showToast("Por favor escribe un nombre para el nivel.", "error");
        } else {
            alert("El nombre es obligatorio");
        }
        return;
    }

    try {
        // Deshabilitar botón para evitar doble clic
        const saveBtn = document.querySelector("button[onclick='saveNewLevel()']");
        const originalText = saveBtn.innerText;
        saveBtn.disabled = true;
        saveBtn.innerText = "Creando...";

        // Petición al Backend
        const response = await fetch('/HelpDesk/db/admin/incidencias/create-nincidencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre: nombreNivel })
        });

        const result = await response.json();

        if (result.success) {
            if(typeof showToast === "function") showToast(result.message, 'success');
            closeLevelModal();
            // Aquí podrías llamar a una función para recargar la lista de niveles si la tienes visible
            // loadLevels(); 
        } else {
            if(typeof showToast === "function") showToast(result.message, 'error');
            else alert("Error: " + result.message);
        }

    } catch (error) {
        console.error("Error:", error);
        if(typeof showToast === "function") showToast("Error de conexión con el servidor", "error");
    } finally {
        // Restaurar botón
        const saveBtn = document.querySelector("button[onclick='saveNewLevel()']");
        saveBtn.disabled = false;
        saveBtn.innerText = "Crear Nivel";
    }
}

// Agregar soporte para tecla Enter en el input
document.addEventListener('DOMContentLoaded', () => {
    const levelInput = document.getElementById('levelNameInput');
    if(levelInput) {
        levelInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                saveNewLevel();
            }
        });
    }
});