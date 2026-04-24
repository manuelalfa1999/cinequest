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

document.querySelector('.btn-recomendar').addEventListener('click', function(e) {
    const animo        = document.querySelector('input[name="animo"]:checked');
    const compania     = document.querySelector('input[name="compania"]:checked');
    const tiempo       = document.querySelector('input[name="tiempo"]:checked');
    const concentracion= document.querySelector('input[name="concentracion"]:checked');
    const momento      = document.querySelector('input[name="momento"]:checked');

    const aviso = document.getElementById('aviso-filtros');
    const campos = [
        { val: animo,         sel: '.campo:nth-child(1) .opciones-grid' },
        { val: compania,      sel: '.campo:nth-child(2) .opciones-grid' },
        { val: tiempo,        sel: '.campo:nth-child(3) .opciones-grid' },
        { val: concentracion, sel: '.campo:nth-child(4) .opciones-grid' },
        { val: momento,       sel: '.campo:nth-child(5) .opciones-grid' },
    ];

    const falta = campos.some(c => !c.val);

    if (falta) {
        e.preventDefault();
        aviso.style.display = 'block';
        campos.forEach(c => {
            const el = document.querySelector(c.sel);
            if (el) el.style.border = !c.val ? '2px solid #4fc3f7' : '2px solid transparent';
        });
    } else {
        aviso.style.display = 'none';
    }
});