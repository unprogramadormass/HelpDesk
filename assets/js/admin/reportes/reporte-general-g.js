// Espera a que todo el HTML esté cargado para ejecutar el script
document.addEventListener('DOMContentLoaded', function() {
    
    // Busca el botón específico para generar el reporte general
    const btnGenerarGeneral = document.querySelector('button[data-report="general"]');

    if (btnGenerarGeneral) {
        btnGenerarGeneral.addEventListener('click', function() {
            // Encuentra el contenedor padre para buscar los inputs solo dentro de esta tarjeta
            const reportCard = this.closest('.report-card');
            
            // 1. Recolecta los valores de los filtros de esta tarjeta de reporte
            const fechaDesde = reportCard.querySelector('#report-desde-general').value;
            const fechaHasta = reportCard.querySelector('#report-hasta-general').value;

            const metricasCheckboxes = reportCard.querySelectorAll('input[name="Incluir_comentarios"]');
            const incluirComentarios = metricasCheckboxes[0].checked;

            // Validación simple para asegurar que se seleccionen las fechas
            if (!fechaDesde || !fechaHasta) {
                Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Por favor, selecciona un rango de fechas.' });
                return;
            }

            // Muestra una alerta de "Cargando" para mejorar la experiencia de usuario
            Swal.fire({
                title: 'Generando Reporte...',
                text: 'Por favor espera mientras procesamos los datos.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            // 2. Envía los datos al backend usando fetch
            fetch('/HelpDesk/db/admin/data/reportes/reporte_general_g.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fechaDesde,
                    fechaHasta,
                    incluirComentarios
                })
            })
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor.');
                return response.json();
            })
            .then(data => {
                Swal.close(); // Cierra la alerta de "Cargando"
                
                if (data.error) {
                     Swal.fire('Error', data.error, 'error');
                     return;
                }

                if (!data || data.length === 0) {
                    Swal.fire('Sin Resultados', 'No se encontraron tickets con los filtros seleccionados.', 'info');
                    return;
                }

                // 3. Si hay datos, llama a la función para crear el Excel
                generarExcel(data, { incluirComentarios });
            })
            .catch(error => {
                Swal.close();
                console.error('Error al generar el reporte:', error);
                Swal.fire('Error', 'No se pudo generar el reporte. Revisa la consola para más detalles.', 'error');
            });
        });
    }

    /**
     * Genera y descarga un archivo Excel a partir de un array de datos.
     * @param {Array} data El array de objetos (tickets).
     * @param {Object} options Opciones para incluir columnas adicionales.
     */
    async function generarExcel(data, options) {
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet("Reporte de Tickets");

        // --- TÍTULO ---
        worksheet.mergeCells("A1:K1"); // Ampliado para incluir la nueva columna
        const titleCell = worksheet.getCell("A1");
        titleCell.value = "Lista de Tickets General";
        titleCell.font = { size: 16, bold: true, color: { argb: "FFFFFFFF" } };
        titleCell.alignment = { horizontal: "center", vertical: "middle" };
        titleCell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "4F81BD" } };

        worksheet.addRow([]);

        // --- ENCABEZADOS ---
        const headers = [
            "ID Ticket", "Título", "Sucursal", 
            "Categoría", // <-- 1. COLUMNA AÑADIDA
            "Creador", "Agente Asignado",
            "Estado", "Prioridad", "Fecha Creación", "Fecha Cierre", "Descripción"
        ];
        if (options.incluirComentarios) headers.push("Comentarios");

        const headerRow = worksheet.addRow(headers);

        headerRow.eachCell((cell) => {
            cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "305496" } };
            cell.border = {
                top: { style: "thin" }, left: { style: "thin" },
                bottom: { style: "thin" }, right: { style: "thin" }
            };
        });

        // --- FILAS DE DATOS ---
        data.forEach(ticket => {
            const row = [
                ticket.id,
                ticket.titulot,
                ticket.sucursal,
                ticket.categoria || "Sin categoría", // <-- 2. DATO AÑADIDO PARA LA NUEVA COLUMNA
                ticket.nombre_creador,
                ticket.nombre_agente || "No asignado",
                ticket.estadot,
                ticket.prioridadt,
                ticket.fecha_creaciont,
                ticket.fecha_cierret || "N/A",
                ticket.descripciont
            ];
            if (options.incluirComentarios) row.push(ticket.comentarios || "Sin comentarios");

            worksheet.addRow(row);
        });

        // --- ANCHO DE COLUMNAS ---
        const colWidths = [10, 40, 25, 25, 30, 30, 15, 12, 20, 20, 60]; // <-- 3. ANCHO AÑADIDO
        if (options.incluirComentarios) colWidths.push(80);

        worksheet.columns.forEach((col, i) => {
            col.width = colWidths[i] || 20;
        });

        // --- 4. AÑADIR EL AUTOFILTRO A LA FILA DE ENCABEZADOS (FILA 3) ---
        const endColumn = options.incluirComentarios ? 'L' : 'K';
        worksheet.autoFilter = `A3:${endColumn}3`;

        // --- GENERAR Y DESCARGAR ---
        const buffer = await workbook.xlsx.writeBuffer();
        const today = new Date();
        const dateStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
        const timeStr = `${String(today.getHours()).padStart(2, "0")}-${String(today.getMinutes()).padStart(2, "0")}`;
        const fileName = `reporte_general_${dateStr}_${timeStr}.xlsx`;

        // saveAs se usa si tienes la librería FileSaver.js, si no, usa el método del blob
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
});
