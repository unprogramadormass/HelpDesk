<?php
// Función para leer el archivo .env
function cargarVariablesEntorno($rutaArchivo) {
    if (!file_exists($rutaArchivo)) {
        die("Error crítico: No se encontró el archivo de configuración (.env).");
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        // Ignorar los comentarios (líneas que empiezan con #)
        if (strpos(trim($linea), '#') === 0) continue;

        // Separar el nombre de la variable y su valor
        list($nombre, $valor) = explode('=', $linea, 2);
        $nombre = trim($nombre);
        $valor = trim($valor);

        // Guardar en el entorno global de PHP
        if (!array_key_exists($nombre, $_SERVER) && !array_key_exists($nombre, $_ENV)) {
            putenv(sprintf('%s=%s', $nombre, $valor));
            $_ENV[$nombre] = $valor;
            $_SERVER[$nombre] = $valor;
        }
    }
}

// Ejecutamos la función apuntando al .env que está en la raíz
cargarVariablesEntorno(__DIR__ . '/../.env');
?>