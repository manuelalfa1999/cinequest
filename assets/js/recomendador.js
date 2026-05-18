document.querySelectorAll('.opcion.seleccionada').forEach(label => {
    const input = label.querySelector('input');
    if (input) input.checked = true;
});

document.querySelectorAll('.opcion').forEach(label => {
    label.addEventListener('click', function() {
        const input = this.querySelector('input');
        const name = input.name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(i => {
            i.closest('.opcion').classList.remove('seleccionada');
        });
        this.classList.add('seleccionada');
        input.checked = true;
    });
});

const btnRecomendar = document.querySelector('.btn-recomendar');
if (btnRecomendar) {
    btnRecomendar.addEventListener('click', function(e) {
        const animo    = document.querySelector('input[name="animo"]:checked');
        const compania = document.querySelector('input[name="compania"]:checked');
        const tiempo   = document.querySelector('input[name="tiempo"]:checked');
        const formato  = document.querySelector('input[name="formato"]:checked');
        const momento  = document.querySelector('input[name="momento"]:checked');

        const aviso = document.getElementById('aviso-filtros');

        const campos = [
            { val: animo,    grid: document.querySelector('input[name="animo"]')?.closest('.opciones-grid') },
            { val: compania, grid: document.querySelector('input[name="compania"]')?.closest('.opciones-grid') },
            { val: tiempo,   grid: document.querySelector('input[name="tiempo"]')?.closest('.opciones-grid') },
            { val: formato,  grid: document.querySelector('input[name="formato"]')?.closest('.opciones-grid') },
            { val: momento,  grid: document.querySelector('input[name="momento"]')?.closest('.opciones-grid') },
        ];

        const falta = campos.some(c => !c.val);

        if (falta) {
            e.preventDefault();
            aviso.style.display = 'block';
            campos.forEach(c => {
                if (c.grid) c.grid.style.border = !c.val ? '2px solid #4fc3f7' : '2px solid transparent';
            });
        } else {
            aviso.style.display = 'none';
        }
    });
}