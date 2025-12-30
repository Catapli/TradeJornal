// map-initializer.js
export async function initializeMap(lat, long) {
    const position = { lat: parseFloat(lat), lng: parseFloat(long) };
    // 38.780636, -0.435345
    // const position = { lat: lat, lng: long };

    const { Map } = await google.maps.importLibrary("maps");
    const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

    const map = new Map(document.getElementById("map"), {
        zoom: 18,
        center: position,
        mapId: "DEMO_MAP_ID",
    });

    const marker = new AdvancedMarkerElement({
        map: map,
        position: position,
        title: "Uluru",
    });

    return map;
}
