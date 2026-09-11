(function () {
  function initReveal() {
    var elementos = document.querySelectorAll(".reveal");
    if (!elementos.length) return;

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("in-view");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );

    elementos.forEach(function (el) {
      observer.observe(el);
    });
  }

  document.addEventListener("DOMContentLoaded", initReveal);
})();
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('mapa_detalle');
  if (!el) return;

  var zona = el.dataset.zona.trim();
  var map = L.map('mapa_detalle', { scrollWheelZoom: false });

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map);

  var coordMatch = zona.match(/^(-?\d+(\.\d+)?)\s*,\s*(-?\d+(\.\d+)?)$/);

  if (coordMatch) {
    var lat = parseFloat(coordMatch[1]);
    var lon = parseFloat(coordMatch[3]);
    map.setView([lat, lon], 16);
    L.marker([lat, lon]).addTo(map);
  } else {
    var url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(zona)}`;
    fetch(url)
      .then(r => r.json())
      .then(data => {
        if (data.length > 0) {
          var lat = parseFloat(data[0].lat);
          var lon = parseFloat(data[0].lon);
          map.setView([lat, lon], 16);
          L.marker([lat, lon]).addTo(map).bindPopup(zona);
        } else {
          map.setView([-34.6037, -58.3816], 11); 
          el.insertAdjacentHTML('afterend', '<p style="font-size:13px;color:#888;">No se pudo ubicar la dirección en el mapa.</p>');
        }
      })
      .catch(() => {
        map.setView([-34.6037, -58.3816], 11);
      });
  }
});