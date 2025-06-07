import Routing from "fos-router"

document.addEventListener('DOMContentLoaded', () => {
    fetch(Routing.generate('travel_diary_visited_countries'))
        .then(response => response.json())
        .then(data => {
            const visited = (data.visited || []).map(c => c.toUpperCase());
            const upcoming = (data.upcoming || []).map(c => c.toUpperCase());

            fetch('/countries.json')
                .then(res => res.json())
                .then(geojson => {
                    const map = L.map('map', {
                        worldCopyJump: false, // empêche la duplication horizontale
                        maxBounds: [[-90, -180], [90, 180]], // limite la vue au monde réel
                        maxBoundsViscosity: 1.0, // empêche de sortir complètement des bounds
                        minZoom: 3,
                        maxZoom: 6
                    }).setView([50, 20], 3);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                    function getCountryCode(feature) {
                        return (feature.properties.isoA2 || '').toUpperCase();
                    }

                    function getColor(code) {
                        if (visited.includes(code) && upcoming.includes(code)) return '#F2C14B'; // jaune
                        if (visited.includes(code)) return '#55B5A6'; // bleu
                        if (upcoming.includes(code)) return '#EA7987'; // orange
                        return '#ccc'; // gris neutre
                    }

                    L.geoJSON(geojson, {
                        style: feature => ({
                            fillColor: getColor(getCountryCode(feature)),
                            weight: 1,
                            color: 'black',
                            fillOpacity: 0.7,
                        }),
                        onEachFeature: (feature, layer) => {
                            const code = getCountryCode(feature);
                            layer.bindPopup(`<b>${feature.properties.name}</b><br>Code: ${code}`);
                            layer.on('click', () => {
                                map.fitBounds(layer.getBounds());
                            });
                        },
                        // 👇 C'est cette option qui empêche le wrapping
                        wrap: false
                    }).addTo(map);


                    const legend = document.getElementById('legend');
                    legend.innerHTML = `
            <i style="background: #007bff"></i> Visité<br>
            <i style="background: #ff7f00"></i> À venir<br>
            <i style="background: #ffff00"></i> À venir + déjà visité<br>
            <i style="background: #ccc"></i> Non visité<br>
          `;
                });
        });
});