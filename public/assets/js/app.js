// Agrega una fila nueva a una tabla repetible usando el <template> embebido.
function addRow(tableId) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const template = table.querySelector('template');
    const index = tbody.children.length;
    let html = template.innerHTML.replaceAll('__INDEX__', index);
    const wrapper = document.createElement('tbody');
    wrapper.innerHTML = html.trim();
    tbody.appendChild(wrapper.firstElementChild);
}

// Elimina una fila repetible del formulario sin tocar aun la base de datos.
function removeRow(button) {
    const row = button.closest('tr');
    if (row) row.remove();
}

// Confirmacion centralizada antes del borrado logico del plan.
function confirmDelete() {
    return confirm('¿Seguro que deseas eliminar este plan? Se hará borrado lógico y no se mostrará en el listado.');
}
