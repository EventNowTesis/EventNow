var map = L.map('mi_mapa').setView([-34.6037, -58.3816], 13);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

var marcadorActual = null;

function buscarDireccion() {
    var inputDireccion = document.getElementById('direccion').value;

    if (!inputDireccion) {
        alert("Por favor ingresa una dirección");
        return;
    }

    var url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(inputDireccion)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                var lat = parseFloat(data[0].lat);
                var lon = parseFloat(data[0].lon);
                var nombreLugar = data[0].display_name;

                map.setView([lat, lon], 16);

                if (marcadorActual) {
                    map.removeLayer(marcadorActual);
                }

                marcadorActual = L.marker([lat, lon]).addTo(map)
                    .bindPopup(`<b>Resultado:</b><br>${nombreLugar}`)
                    .openPopup();

            } else {
                alert("No se encontró la dirección ingresada.");
            }
        })
        .catch(error => {
            console.error("Error al consultar la API:", error);
            alert("Ocurrió un error al realizar la búsqueda.");
        });
}
