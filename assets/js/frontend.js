/**
 * JavaScript para el frontend
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Validación del formulario de solicitud
        $('#um-solicitud-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            
            // Validación HTML5
            if (!form.checkValidity()) {
                e.stopPropagation();
                $(form).addClass('was-validated');
                return;
            }
            
            const $form = $(form);
            const $btn = $form.find('button[type="submit"]');
            const $spinner = $btn.find('.spinner-border');
            const originalText = $btn.html();
            const formData = new FormData(form);
            
            // Convertir FormData a objeto
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Agregar action
            data.action = 'um_crear_solicitud';
            
            // Deshabilitar botón y mostrar spinner
            $btn.prop('disabled', true);
            $spinner.removeClass('d-none');
            
            $.ajax({
                url: ultimaMilla.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        $('#um-form-messages').html(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>¡Éxito!</strong> ${response.data.message}<br>
                                <strong>Código de seguimiento:</strong> <code class="fs-5">${response.data.codigo}</code>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                        
                        // Resetear formulario
                        form.reset();
                        $form.removeClass('was-validated');
                        
                        // Scroll al mensaje
                        $('html, body').animate({
                            scrollTop: $('#um-form-messages').offset().top - 100
                        }, 500);
                    } else {
                        // Mostrar error
                        $('#um-form-messages').html(`
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error:</strong> ${response.data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#um-form-messages').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> Hubo un problema de conexión. Por favor, intenta nuevamente.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                },
                complete: function() {
                    // Restaurar botón
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });
        
        // Ver detalle de solicitud
        $(document).on('click', '.ver-detalle', function() {
            const solicitudId = $(this).data('solicitud-id');
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleSolicitud'));
            
            $('#modal-detalle-content').html(`
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `);
            
            modal.show();
            
            $.ajax({
                url: ultimaMilla.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'um_obtener_detalle_solicitud',
                    nonce: ultimaMilla.nonce,
                    solicitud_id: solicitudId
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        let html = `
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Código de Seguimiento:</strong><br>
                                    <span class="fs-5 text-primary">${data.codigo}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Estado Actual:</strong><br>
                                    <span class="badge bg-${data.estado_color} fs-6">${data.estado_label}</span>
                                </div>
                                <div class="col-12 mb-3">
                                    <strong>Fecha de Solicitud:</strong><br>
                                    ${data.fecha_solicitud}
                                </div>
                                <div class="col-12 mb-3">
                                    <strong>Dirección de Origen:</strong><br>
                                    ${data.direccion_origen}
                                </div>
                                <div class="col-12 mb-3">
                                    <strong>Dirección de Destino:</strong><br>
                                    ${data.direccion_destino}
                                </div>
                        `;
                        
                        if (data.descripcion) {
                            html += `
                                <div class="col-12 mb-3">
                                    <strong>Descripción:</strong><br>
                                    ${data.descripcion}
                                </div>
                            `;
                        }
                        
                        if (data.mensajero) {
                            html += `
                                <div class="col-12 mb-3">
                                    <strong>Mensajero Asignado:</strong><br>
                                    <i class="bi bi-person-circle"></i> ${data.mensajero}
                                </div>
                            `;
                        }
                        
                        if (data.fecha_programada) {
                            html += `
                                <div class="col-12 mb-3">
                                    <strong>Fecha Programada:</strong><br>
                                    ${data.fecha_programada}
                                </div>
                            `;
                        }
                        
                        if (data.fecha_entrega) {
                            html += `
                                <div class="col-12 mb-3">
                                    <strong>Fecha de Entrega:</strong><br>
                                    ${data.fecha_entrega}
                                </div>
                            `;
                        }
                        
                        html += '</div>';
                        
                        $('#modal-detalle-content').html(html);
                    } else {
                        $('#modal-detalle-content').html(`
                            <div class="alert alert-danger">${response.data.message}</div>
                        `);
                    }
                },
                error: function() {
                    $('#modal-detalle-content').html(`
                        <div class="alert alert-danger">Error de conexión. Por favor, intenta nuevamente.</div>
                    `);
                }
            });
        });
        
        // Auto-generar nombre de campo basado en la etiqueta
        $('#nuevo-campo-label').on('blur', function() {
            const label = $(this).val();
            const nameField = $('#nuevo-campo-name');
            
            if (label && !nameField.val()) {
                // Convertir etiqueta a nombre válido
                const name = label.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Quitar acentos
                    .replace(/[^a-z0-9]+/g, '_') // Reemplazar no alfanuméricos con _
                    .replace(/^_+|_+$/g, ''); // Quitar _ al inicio y final
                
                nameField.val(name);
            }
        });
        
    });
    
})(jQuery);
