// Al cargar la página, sincronizar opciones ya marcadas por PHP
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
    const animo    = document.querySelector('input[name="animo"]:checked');
    const compania = document.querySelector('input[name="compania"]:checked');
    const tiempo   = document.querySelector('input[name="tiempo"]:checked');

    const aviso = document.getElementById('aviso-filtros');

    if (!animo || !compania || !tiempo) {
        e.preventDefault();
        aviso.style.display = 'block';

        if (!animo) document.querySelector('.campo:nth-child(1) .opciones-grid').style.border = '2px solid #e94560';
        if (!compania) document.querySelector('.campo:nth-child(2) .opciones-grid').style.border = '2px solid #e94560';
        if (!tiempo) document.querySelector('.campo:nth-child(3) .opciones-grid').style.border = '2px solid #e94560';
    } else {
        aviso.style.display = 'none';
    }
});