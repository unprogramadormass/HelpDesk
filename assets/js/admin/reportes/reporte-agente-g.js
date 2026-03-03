document.addEventListener('DOMContentLoaded', function() {

    const selectAgente = document.getElementById('agentes-select');
    const btnGenerarAgente = document.querySelector('button[data-report="agentes"]');
    if(btnGenerarAgente) {
        btnGenerarAgente.id = 'btn-generar-agente';
    }


    // --- 1. Cargar agentes en el select (SIN CAMBIOS) ---
    function cargarAgentes() {
        if (!selectAgente) return;
        fetch('/HelpDesk/db/admin/reportes/g_agente.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) { console.error(data.error); return; }
                selectAgente.innerHTML = '<option value="">Selecciona un agente...</option>';
                data.forEach(agente => {
                    const option = document.createElement('option');
                    option.value = agente.id;
                    option.textContent = `${agente.nombres} ${agente.apellidos}`;
                    selectAgente.appendChild(option);
                });
            })
            .catch(error => console.error('Error al cargar agentes:', error));
    }

    // --- 2. Lógica para generar el reporte (SIN CAMBIOS, excepto la llamada a la nueva función) ---
    if (btnGenerarAgente) {
        btnGenerarAgente.addEventListener('click', function() {
            const reportCard = this.closest('.report-card');
            const agenteId = selectAgente.value;
            const fechaDesde = document.getElementById('report-desde-agente').value;
            const fechaHasta = document.getElementById('report-hasta-agente').value;

            const incluirAsignados = reportCard.querySelector('input[value="tickets_asignados-agente"]').checked;
            const incluirCerrados = reportCard.querySelector('input[value="tickets_cerrados-agente"]').checked;
            const incluirAbiertos = reportCard.querySelector('input[value="tickets_abiertos-agente"]').checked;

            if (!agenteId || !fechaDesde || !fechaHasta) {
                Swal.fire('Faltan Datos', 'Por favor, selecciona un agente y un rango de fechas.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Generando Reporte de Agente...',
                text: 'Esto puede tomar un momento.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('/HelpDesk/db/admin/reportes/reporte_agente_g.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    agenteId,
                    fechaDesde,
                    fechaHasta,
                    incluirAsignados,
                    incluirCerrados,
                    incluirAbiertos
                })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.error) {
                    Swal.fire('Error', data.error, 'error');
                    return;
                }

                if (!data.bitacora || !data.bitacora.incidencias || data.bitacora.incidencias.length === 0) {
                    Swal.fire('Sin Resultados', 'No se encontraron incidencias para generar la bitácora en las fechas seleccionadas.', 'info');
                    return;
                }

                // Llamamos a la nueva función asíncrona de ExcelJS
                generarExcelConExcelJS(data);
            })
            .catch(error => {
                Swal.close();
                console.error('Error al generar el reporte:', error);
                Swal.fire('Error', 'No se pudo generar el reporte. Revisa la consola.', 'error');
            });
        });
    }

    
    // --- 3. Función para construir el Excel con EXCELJS (MODIFICADA) ---
    async function generarExcelConExcelJS(data) {
        // Crear un nuevo libro de trabajo
        const workbook = new ExcelJS.Workbook();

        // --- Hoja 1: Bitácora con diseño específico ---
        if (data.bitacora && data.bitacora.datos) {
            const worksheet = workbook.addWorksheet('Bitácora');

            // --- ESTILOS REUTILIZABLES ---
            const estiloBordeFino = {
                top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' }
            };
            const estiloCentrado = { vertical: 'middle', horizontal: 'center' };

            // --- Fila 1: Título principal ---
            const titleRow = worksheet.getRow(1);
            titleRow.getCell(3).value = `BITÁCORA PERSONAL DE ${data.bitacora.nombreAgente.toUpperCase()}`;
            worksheet.mergeCells('C1:T1');
            titleRow.getCell(3).style = {
                font: { bold: true, size: 17, color: { argb: 'FF000000' } },
                alignment: estiloCentrado,
                fill: { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFADD8E6' } }
            };

            // --- Fila 3: Subtítulo "SUCURSALES" ---
            const subtitleRow = worksheet.getRow(3);
            subtitleRow.getCell(3).value = 'SUCURSALES';
            worksheet.mergeCells('C3:T3');
            subtitleRow.getCell(3).style = {
                font: { bold: true, size: 15, color: { argb: 'FF000000' } },
                alignment: estiloCentrado,
                fill: { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFC000' } },
                border: estiloBordeFino
            };

            // --- Fila 4: Encabezados ---
            const headerRow = worksheet.getRow(4);
            headerRow.getCell(1).value = 'INCIDENCIAS';
            worksheet.mergeCells('A4:B4');
            headerRow.getCell(1).style = {
                font: { bold: true, size: 15 },
                alignment: estiloCentrado,
                fill: { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD9D9D9' } },
                border: estiloBordeFino
            };

            // Encabezados de sucursales individuales
            data.bitacora.sucursales.forEach((sucursal, index) => {
                if (index < 18) {
                    const cell = headerRow.getCell(3 + index);
                    cell.value = sucursal;
                    cell.style = {
                        font: { bold: true, size: 13 },
                        alignment: estiloCentrado,
                        // ===== CAMBIO 1: COLOR DE FONDO DE SUCURSALES =====
                        fill: { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF8EA9DB' } },
                        border: estiloBordeFino
                    };
                }
            });

            // --- Filas de Datos (5 en adelante) ---
            // Primero, solo se añaden los valores
            data.bitacora.incidencias.forEach((incidencia, rowIndex) => {
                const currentRowIndex = 5 + rowIndex;
                const row = worksheet.getRow(currentRowIndex);
                row.getCell(1).value = incidencia;
                worksheet.mergeCells(`A${currentRowIndex}:B${currentRowIndex}`);
                data.bitacora.sucursales.forEach((sucursal, colIndex) => {
                    if (colIndex < 18) {
                        const cantidad = data.bitacora.datos[incidencia]?.[sucursal] || 0;
                        if (cantidad > 0) {
                            row.getCell(3 + colIndex).value = cantidad;
                        }
                    }
                });
            });
            
            // Segundo, se aplican los estilos a toda la tabla de datos
            for (let r = 5; r < 5 + data.bitacora.incidencias.length; r++) {
                for (let c = 1; c <= 20; c++) {
                    const cell = worksheet.getCell(r, c);
                    cell.border = estiloBordeFino;

                    if (c <= 2) { // Columnas A y B (donde va el nombre de la incidencia)
                        cell.alignment = { vertical: 'middle', horizontal: 'left', indent: 1 };
                        cell.font = { bold: true, size: 13}; // Letra en negrita
                        // ===== CAMBIO 2: COLOR DE FONDO DE CADA INCIDENCIA =====
                        cell.fill = {
                            type: 'pattern',
                            pattern: 'solid',
                            fgColor: { argb: 'FFB4C6E7' }
                        };
                    } else { // Columnas C en adelante (los números)
                        cell.alignment = estiloCentrado;
                    }
                }
            }

            worksheet.columns = [
                { width: 30 }, { width: 5 },
                ...Array(18).fill({ width: 12 })
            ];
            
            worksheet.views = [{
                state: 'frozen', xSplit: 2, ySplit: 4
            }];
        }

        // El resto de la función (crearHojaDeListado y la descarga) permanece igual.
        function crearHojaDeListado(nombreHoja, tickets) {
            // Corrección de la sesión anterior para crear la hoja aunque esté vacía
            const worksheet = workbook.addWorksheet(nombreHoja);

            worksheet.columns = [
                { header: 'ID', key: 'id', width: 8 }, { header: 'Título', key: 'titulot', width: 45 }, { header: 'Categoría', key: 'categoria', width: 30 },
                { header: 'Sucursal', key: 'sucursal', width: 30 }, { header: 'Estado', key: 'estadot', width: 20 }, { header: 'Prioridad', key: 'prioridadt', width: 15 },
                { header: 'Creador', key: 'nombre_creador', width: 35 }, { header: 'Fecha Creación', key: 'fecha_creaciont', width: 22 }, { header: 'Fecha Cierre', key: 'fecha_cierret', width: 22 }
            ];

            worksheet.getRow(1).eachCell(cell => {
                cell.font = { bold: true };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD9E1F2' } };
                cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
            });

            if (tickets && tickets.length > 0) {
                const ticketsFormateados = tickets.map(t => ({ ...t, fecha_cierret: t.fecha_cierret || 'N/A' }));
                worksheet.addRows(ticketsFormateados);
            }
            
            worksheet.views = [{ state: 'frozen', ySplit: 1 }];
        }

        crearHojaDeListado('Tickets Asignados', data.asignados);
        crearHojaDeListado('Tickets Cerrados', data.cerrados);
        crearHojaDeListado('Tickets Abiertos', data.abiertos);

        const agenteSeleccionado = selectAgente.options[selectAgente.selectedIndex].text.replace(/\s+/g, '_');
        const fileName = `BITACORA_PERSONAL_${agenteSeleccionado}_${new Date().toISOString().slice(0, 10)}.xlsx`;
        
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    cargarAgentes();
});