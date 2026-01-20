/**
 * JavaScript para el área de administración
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        console.log('Última Milla Admin JS cargado');
        console.log('AJAX URL:', ultimaMillaAdmin.ajaxUrl);
        console.log('Nonce:', ultimaMillaAdmin.nonce);
        
        // ============================================
        // FUNCIONES DE MODAL (WordPress Native)
        // ============================================
        function openModal(modalId) {
            $('#' + modalId).addClass('active');
            $('#modal-backdrop').addClass('active');
            $('body').css('overflow', 'hidden');
        }
        
        function closeModal(modalId) {
            $('#' + modalId).removeClass('active');
            $('#modal-backdrop').removeClass('active');
            $('body').css('overflow', '');
        }
        
        // Cerrar modal con botón X
        $(document).on('click', '[data-modal-close]', function() {
            const modalId = $(this).data('modal-close');
            closeModal(modalId);
        });
        
        // Cerrar modal con click en backdrop
        $(document).on('click', '#modal-backdrop', function() {
            $('.um-modal.active').removeClass('active');
            $(this).removeClass('active');
            $('body').css('overflow', '');
        });
        
        // Cerrar modal con tecla ESC
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape') {
                $('.um-modal.active').removeClass('active');
                $('#modal-backdrop').removeClass('active');
                $('body').css('overflow', '');
            }
        });
        
        // ============================================
        // DATATABLES
        // ============================================
        // Inicializar DataTable
        if ($('#tabla-solicitudes').length) {
            console.log('Inicializando DataTable de solicitudes...');
            
            const i18n = typeof ultimaMillaAdmin !== 'undefined' && ultimaMillaAdmin.i18n ? ultimaMillaAdmin.i18n : {
                processing: "Procesando...",
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron registros coincidentes",
                emptyTable: "No hay datos disponibles en la tabla",
                paginate: {
                    first: "Primero",
                    previous: "Anterior",
                    next: "Siguiente",
                    last: "Último"
                }
            };
            
            const table = $('#tabla-solicitudes').DataTable({
                language: i18n,
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[1, 'desc']], // Ordenar por fecha descendente
                columnDefs: [
                    {
                        targets: -1, // Última columna (Acciones)
                        orderable: false,
                        searchable: false
                    }
                ],
                stateSave: true, // Guardar estado en localStorage
                stateDuration: 60 * 60 * 24, // 24 horas
            });
            
            // Botón personalizado para limpiar filtros
            if ($('.dataTables_filter').length) {
                $('.dataTables_filter').append(
                    '<button type="button" class="button button-small" id="btn-limpiar-busqueda">' +
                    '<span class="dashicons dashicons-dismiss"></span> Limpiar' +
                    '</button>'
                );
            }
            
            // Limpiar búsqueda
            $(document).on('click', '#btn-limpiar-busqueda', function() {
                table.search('').draw();
            });
            
            // Evento para filtros rápidos
            $(document).on('click', '.filtro-estado-dt', function() {
                const estado = $(this).data('estado');
                
                // Actualizar estado activo de botones
                $('.filtro-estado-dt').removeClass('active');
                $(this).addClass('active');
                
                // Determinar columna de estado (depende de si hay columnas Cliente y Mensajero)
                const numColumns = table.columns().header().length;
                const estadoColumn = numColumns === 8 ? 6 : 4; // Si tiene 8 columnas es admin, sino cliente/mensajero
                
                // Filtrar tabla
                table.column(estadoColumn).search(estado).draw();
            });
        }
        
        // Inicializar DataTable para formularios
        if ($('#tabla-formularios').length) {
            console.log('Inicializando DataTable de formularios...');
            console.log('i18n disponible:', typeof ultimaMillaAdmin !== 'undefined' && typeof ultimaMillaAdmin.i18n !== 'undefined');
            
            const i18n = typeof ultimaMillaAdmin !== 'undefined' && ultimaMillaAdmin.i18n ? ultimaMillaAdmin.i18n : {
                processing: "Procesando...",
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron registros coincidentes",
                emptyTable: "No hay datos disponibles en la tabla",
                paginate: {
                    first: "Primero",
                    previous: "Anterior",
                    next: "Siguiente",
                    last: "Último"
                }
            };
            
            try {
                $('#tabla-formularios').DataTable({
                    language: i18n,
                    pageLength: 10,
                    order: [[0, 'desc']], // Ordenar por ID descendente
                    columnDefs: [
                        {
                            targets: -1, // Última columna (Acciones)
                            orderable: false,
                            searchable: false
                        },
                        {
                            targets: 2, // Columna Shortcode
                            orderable: false
                        }
                    ],
                    stateSave: true,
                    stateDuration: 60 * 60 * 24
                });
                console.log('DataTable de formularios inicializado correctamente');
            } catch(error) {
                console.error('Error al inicializar DataTable de formularios:', error);
            }
        } else {
            console.log('Tabla #tabla-formularios no encontrada');
        }
        
        // Guardar formulario (nombre y estado)
        $(document).on('click', '#btn-guardar-formulario', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const originalText = $btn.text();
            const $status = $('#save-status');
            
            const formId = $('#form-id').val();
            const title = $('#form-title').val().trim();
            const status = $('#form-status').val();
            
            if (!title) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Por favor ingresa un nombre para el formulario',
                    confirmButtonText: 'Entendido'
                });
                $('#form-title').focus();
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="um-spinner"></span> Guardando...');
            $status.html('');
            
            $.ajax({
                url: ultimaMillaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'um_guardar_formulario',
                    nonce: ultimaMillaAdmin.nonce,
                    form_id: formId,
                    title: title,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span class="dashicons dashicons-yes-alt text-success"></span> ' + response.data.message);
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado!',
                            text: response.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        setTimeout(function() {
                            $status.html('');
                        }, 3000);
                    } else {
                        $status.html('');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data.message
                        });
                    }
                    $btn.prop('disabled', false).text(originalText);
                },
                error: function() {
                    $status.html('');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor'
                    });
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Abrir modal de nuevo campo
        $(document).on('click', '#btn-abrir-nuevo-campo', function(e) {
            e.preventDefault();
            openModal('modalNuevoCampo');
        });
        
        // Copiar shortcode
        $(document).on('click', '.copiar-shortcode', function(e) {
            e.preventDefault();
            console.log('Click en copiar shortcode');
            
            const shortcode = $(this).data('shortcode');
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            console.log('Shortcode a copiar:', shortcode);
            
            // Copiar al portapapeles
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    console.log('Shortcode copiado exitosamente');
                    $btn.html('<span class="dashicons dashicons-yes-alt"></span>');
                    $btn.addClass('button-primary');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Copiado!',
                        text: 'Shortcode copiado al portapapeles',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    setTimeout(function() {
                        $btn.html(originalHtml);
                        $btn.removeClass('button-primary');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Error al copiar:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo copiar al portapapeles'
                    });
                });
            } else {
                // Fallback para navegadores antiguos
                console.log('Usando fallback para copiar');
                const textArea = document.createElement('textarea');
                textArea.value = shortcode;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    console.log('Shortcode copiado con fallback');
                    $btn.html('<span class="dashicons dashicons-yes-alt"></span>');
                    $btn.addClass('button-primary');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Copiado!',
                        text: 'Shortcode copiado al portapapeles',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    setTimeout(function() {
                        $btn.html(originalHtml);
                        $btn.removeClass('button-primary');
                    }, 2000);
                } catch (err) {
                    console.error('Error con fallback:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo copiar al portapapeles'
                    });
                }
                
                document.body.removeChild(textArea);
            }
        });
        
        // Mostrar/ocultar opciones según tipo de campo
        $('#nuevo-campo-tipo').on('change', function() {
            if ($(this).val() === 'select') {
                $('#campo-opciones-container').show();
            } else {
                $('#campo-opciones-container').hide();
            }
        });
        
        // Guardar nuevo campo
        $('#btn-guardar-campo').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();
            
            const formId = $('#nuevo-campo-form-id').val();
            const fieldType = $('#nuevo-campo-tipo').val();
            const fieldLabel = $('#nuevo-campo-label').val();
            const fieldName = $('#nuevo-campo-name').val();
            const fieldRequired = $('#nuevo-campo-required').is(':checked') ? 1 : 0;
            const fieldOptionsText = $('#nuevo-campo-opciones').val();
            
            // Validar
            if (!fieldLabel || !fieldName) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor completa todos los campos obligatorios',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            
            // Convertir opciones a array
            let fieldOptions = [];
            if (fieldType === 'select' && fieldOptionsText) {
                fieldOptions = fieldOptionsText.split('\n').filter(opt => opt.trim() !== '');
            }
            
            $btn.prop('disabled', true).html('<span class="um-spinner"></span> Guardando...');
            
            $.ajax({
                url: ultimaMillaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'um_guardar_campo_formulario',
                    nonce: ultimaMillaAdmin.nonce,
                    form_id: formId,
                    field_type: fieldType,
                    field_label: fieldLabel,
                    field_name: fieldName,
                    field_required: fieldRequired,
                    field_options: fieldOptions
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Campo agregado!',
                            text: 'El campo se ha agregado correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.data.message || 'Error al guardar el campo'
                        });
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor'
                    });
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Eliminar campo
        $(document).on('click', '.eliminar-campo', function() {
            const fieldId = $(this).data('field-id');
            const $fieldItem = $(this).closest('.um-form-field-item');
            
            Swal.fire({
                title: '¿Eliminar campo?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: ultimaMillaAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'um_eliminar_campo_formulario',
                            nonce: ultimaMillaAdmin.nonce,
                            field_id: fieldId
                        },
                        success: function(response) {
                            if (response.success) {
                                $fieldItem.fadeOut(300, function() {
                                    $(this).remove();
                                });
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: 'El campo ha sido eliminado',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.data.message || 'Error al eliminar el campo'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de conexión',
                                text: 'No se pudo conectar con el servidor'
                            });
                        }
                    });
                }
            });
        });
        
        // Eliminar formulario (desde la lista)
        $(document).on('click', '.eliminar-formulario', function(e) {
            e.preventDefault();
            
            const formId = $(this).data('form-id');
            const formTitle = $(this).data('form-title');
            const $row = $(this).closest('tr');
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            Swal.fire({
                title: '¿Eliminar formulario?',
                html: `¿Estás seguro de que deseas eliminar el formulario <strong>"${formTitle}"</strong>?<br><br>Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $btn.prop('disabled', true).html('<span class="um-spinner"></span>');
                    
                    $.ajax({
                        url: ultimaMillaAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'um_eliminar_formulario',
                            nonce: ultimaMillaAdmin.nonce,
                            form_id: formId
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: 'El formulario ha sido eliminado',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                
                                // Eliminar la fila con animación
                                $row.fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    // Verificar si quedan registros
                                    const remainingRows = $('#tabla-formularios tbody tr').length;
                                    if (remainingRows === 0) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.data.message || 'Error al eliminar el formulario'
                                });
                                $btn.prop('disabled', false).html(originalHtml);
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de conexión',
                                text: 'No se pudo conectar con el servidor'
                            });
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    });
                }
            });
        });
        
        // Eliminar formulario (desde la página de edición)
        $(document).on('click', '.eliminar-formulario-edit', function(e) {
            e.preventDefault();
            
            const formId = $(this).data('form-id');
            const formTitle = $(this).data('form-title');
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            Swal.fire({
                title: '¿Eliminar formulario?',
                html: `
                    ¿Estás seguro de que deseas eliminar el formulario <strong>"${formTitle}"</strong>?
                    <br><br>
                    Se eliminarán también todos los campos personalizados.
                    <br><br>
                    <strong>Esta acción no se puede deshacer.</strong>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar todo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $btn.prop('disabled', true).html('<span class="um-spinner"></span> Eliminando...');
                    
                    $.ajax({
                        url: ultimaMillaAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'um_eliminar_formulario',
                            nonce: ultimaMillaAdmin.nonce,
                            form_id: formId
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: 'Redirigiendo...',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Redirigir a la lista de formularios
                                    window.location.href = ultimaMillaAdmin.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=ultima-milla-formularios');
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.data.message || 'Error al eliminar el formulario'
                                });
                                $btn.prop('disabled', false).html(originalHtml);
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de conexión',
                                text: 'No se pudo conectar con el servidor'
                            });
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    });
                }
            });
        });
        
        // Ver detalle de solicitud
        $(document).on('click', '.ver-detalle-admin', function(e) {
            e.preventDefault();
            
            const solicitudId = $(this).data('solicitud-id');
            
            $('#modal-detalle-admin-content').html(`
                <p style="text-align: center;">
                    <span class="um-spinner"></span><br>
                    Cargando información...
                </p>
            `);
            
            openModal('modalDetalleAdmin');
            
            $.ajax({
                url: ultimaMillaAdmin.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'um_obtener_detalle_solicitud',
                    nonce: ultimaMillaAdmin.nonce,
                    solicitud_id: solicitudId
                },
                success: function(response) {
                    console.log('Respuesta AJAX:', response);
                    
                    if (response.success) {
                        const data = response.data;
                        let html = `
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Código:</th>
                                    <td><strong class="text-primary" style="font-size: 16px;">${data.codigo}</strong></td>
                                </tr>
                                <tr>
                                    <th scope="row">Estado:</th>
                                    <td><span class="um-badge ${data.estado_color}">${data.estado_label}</span></td>
                                </tr>
                                <tr>
                                    <th scope="row">Fecha de Solicitud:</th>
                                    <td>${data.fecha_solicitud}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Dirección de Origen:</th>
                                    <td>${data.direccion_origen}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Dirección de Destino:</th>
                                    <td>${data.direccion_destino}</td>
                                </tr>
                        `;
                        
                        if (data.descripcion) {
                            html += `
                                <tr>
                                    <th scope="row">Descripción:</th>
                                    <td>${data.descripcion}</td>
                                </tr>
                            `;
                        }
                        
                        if (data.mensajero) {
                            html += `
                                <tr>
                                    <th scope="row">Mensajero Asignado:</th>
                                    <td>${data.mensajero}</td>
                                </tr>
                            `;
                        }
                        
                        if (data.fecha_entrega) {
                            html += `
                                <tr>
                                    <th scope="row">Fecha de Entrega:</th>
                                    <td>${data.fecha_entrega}</td>
                                </tr>
                            `;
                        }
                        
                        html += '</table>';
                        
                        $('#modal-detalle-admin-content').html(html);
                    } else {
                        $('#modal-detalle-admin-content').html(`
                            <div class="notice notice-error"><p>${response.data.message}</p></div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', status, error);
                    console.error('Respuesta completa:', xhr.responseText);
                    
                    $('#modal-detalle-admin-content').html(`
                        <div class="notice notice-error">
                            <p><strong>Error de conexión:</strong> ${error || 'Por favor, intenta nuevamente.'}</p>
                        </div>
                    `);
                }
            });
        });
        
        // Cambiar estado
        $(document).on('click', '.cambiar-estado', function(e) {
            e.preventDefault();
            
            const solicitudId = $(this).data('solicitud-id');
            const estadoActual = $(this).data('estado-actual');
            
            $('#modal-solicitud-id').val(solicitudId);
            $('#modal-nuevo-estado').val(estadoActual);
            
            openModal('modalCambiarEstado');
        });
        
        $('#btn-guardar-estado').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();
            const solicitudId = $('#modal-solicitud-id').val();
            const nuevoEstado = $('#modal-nuevo-estado').val();
            
            $btn.prop('disabled', true).html('<span class="um-spinner"></span> Guardando...');
            
            $.ajax({
                url: ultimaMillaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'um_actualizar_estado',
                    nonce: ultimaMillaAdmin.nonce,
                    solicitud_id: solicitudId,
                    estado: nuevoEstado
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error al actualizar el estado');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Error de conexión');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Asignar mensajero
        $(document).on('click', '.asignar-mensajero', function(e) {
            e.preventDefault();
            
            const solicitudId = $(this).data('solicitud-id');
            const mensajeroActual = $(this).data('mensajero-actual');
            
            $('#modal-asignar-solicitud-id').val(solicitudId);
            $('#modal-mensajero').val(mensajeroActual);
            
            openModal('modalAsignarMensajero');
        });
        
        $('#btn-guardar-mensajero').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();
            const solicitudId = $('#modal-asignar-solicitud-id').val();
            const mensajeroId = $('#modal-mensajero').val();
            
            $btn.prop('disabled', true).html('<span class="um-spinner"></span> Asignando...');
            
            $.ajax({
                url: ultimaMillaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'um_asignar_mensajero',
                    nonce: ultimaMillaAdmin.nonce,
                    solicitud_id: solicitudId,
                    mensajero_id: mensajeroId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error al asignar mensajero');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Error de conexión');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
        
    });
    
})(jQuery);
