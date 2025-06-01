// jQuery UI Sortable para reordenar campos en el constructor de formularios
// Este archivo se carga solo en el panel admin

$(function() {
    // Hacer los campos del formulario reordenables
    $('#formFieldsContainer').sortable({
        handle: '.drag-field-handle',
        placeholder: 'sortable-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        items: '> .form-field',
        update: function(event, ui) {
            // Opcional: puedes actualizar el orden en un input oculto si lo necesitas
        }
    });
});
