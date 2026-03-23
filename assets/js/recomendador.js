// Resaltar opción seleccionada al hacer clic
document.querySelectorAll('.opcion').forEach(label => {
    label.addEventListener('click', function() {
        const name = this.querySelector('input').name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(input => {
            input.closest('.opcion').classList.remove('seleccionada');
        });
        this.classList.add('seleccionada');
    });
});