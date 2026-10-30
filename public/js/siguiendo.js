var input = document.getElementById('buscar-anfitrion');
var tarjetas = document.querySelectorAll('.seguido-card');

input.addEventListener('input', function () {
    var texto = input.value.toLowerCase();

    for (var i = 0; i < tarjetas.length; i++) {
        var nombre = tarjetas[i].getAttribute('data-nombre');

        if (nombre.indexOf(texto) === -1) {
            tarjetas[i].style.display = 'none';
        } else {
            tarjetas[i].style.display = 'flex';
        }
    }
});