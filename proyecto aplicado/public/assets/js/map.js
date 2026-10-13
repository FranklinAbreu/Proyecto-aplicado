(() => {
    'use strict';

    const canvas = document.getElementById('destinations-map');
    const payloadNode = document.getElementById('map-destinations-data');
    if (!canvas || !payloadNode) return;

    const layout = canvas.closest('.map-layout');
    const statusNode = document.getElementById('map-status');
    const listNode = document.getElementById('route-list');
    const locateButton = document.getElementById('map-locate');
    const resetButton = document.getElementById('map-reset');
    const originOptions = document.getElementById('map-origin-options');
    const panelHeading = document.getElementById('map-panel-heading');
    const setStatus = (message, warning = false) => {
        statusNode.textContent = message;
        statusNode.classList.toggle('is-warning', warning);
    };

    if (!window.L) {
        setStatus('No pudimos cargar el mapa. Recarga la página para intentarlo de nuevo.', true);
        locateButton.disabled = true;
        return;
    }

    let payload;
    try {
        payload = JSON.parse(payloadNode.textContent);
    } catch (error) {
        setStatus('No pudimos cargar los destinos del mapa.', true);
        locateButton.disabled = true;
        return;
    }

    const validCoordinates = (lat, lng) => lat !== null && lng !== null
        && lat !== '' && lng !== '' && typeof lat !== 'boolean' && typeof lng !== 'boolean'
        && Number.isFinite(Number(lat)) && Number.isFinite(Number(lng))
        && Math.abs(Number(lat)) <= 90 && Math.abs(Number(lng)) <= 180;
    const places = (Array.isArray(payload.places) ? payload.places : [])
        .filter(place => place && validCoordinates(place.latitud, place.longitud));
    const origins = (Array.isArray(payload.origins) ? payload.origins : [])
        .filter(origin => origin && validCoordinates(origin.latitud, origin.longitud));
    const knownPlaces = new Map(places.map(place => [String(place.id), place]));
    const markers = new Map();
    const routeColors = ['#003876', '#ce1126', '#426c9a'];
    const routeLayers = [];
    const routeCards = [];
    const map = L.map(canvas, { scrollWheelZoom: false, zoomControl: true });
    let originLayer = null;
    let activeRequest = null;
    let operation = 0;

    const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);
    let tileWarningShown = false;
    tiles.on('tileerror', () => {
        if (tileWarningShown) return;
        tileWarningShown = true;
        const note = layout.querySelector('.map-service-note');
        note.textContent = 'El fondo del mapa no está disponible. Comprueba tu conexión; los destinos y las rutas siguen siendo consultables.';
    });

    function defaultView() {
        if (origins.length > 1) {
            map.fitBounds(origins.map(origin => [Number(origin.latitud), Number(origin.longitud)]), {
                padding: [56, 56], maxZoom: 11,
            });
        } else {
            map.setView([19.055, -70.16], 10);
        }
    }
    defaultView();

    function safeLink(url) {
        if (typeof url !== 'string' || !url) return null;
        try {
            const target = new URL(url, window.location.href);
            return target.origin === window.location.origin && /^https?:$/.test(target.protocol) ? target.href : null;
        } catch (error) {
            return null;
        }
    }

    function placePopup(place) {
        const content = document.createElement('div');
        content.className = 'map-popup';
        const heading = document.createElement('strong');
        heading.textContent = place.nombre;
        const location = document.createElement('p');
        location.textContent = [place.provincia, place.tipo].filter(Boolean).join(' · ');
        content.append(heading, location);
        const linkUrl = safeLink(place.url);
        if (linkUrl) {
            const link = document.createElement('a');
            link.href = linkUrl;
            link.textContent = 'Conocer este lugar →';
            content.append(link);
        }
        return content;
    }

    places.forEach(place => {
        const marker = L.marker([Number(place.latitud), Number(place.longitud)], {
            title: String(place.nombre),
            alt: String(place.nombre),
        }).addTo(map).bindPopup(placePopup(place));
        markers.set(String(place.id), marker);
    });

    function metricDistance(value) {
        const distance = Number(value);
        if (!Number.isFinite(distance) || distance < 0) return 'Distancia no disponible';
        if (distance < 1000) return `${Math.round(distance)} m`;
        return `${(distance / 1000).toLocaleString('es-DO', { maximumFractionDigits: 1 })} km`;
    }

    function metricDuration(value) {
        const seconds = Number(value);
        if (!Number.isFinite(seconds) || seconds < 0) return 'Tiempo no disponible';
        const minutes = Math.max(1, Math.round(seconds / 60));
        return minutes < 60 ? `${minutes} min` : `${Math.floor(minutes / 60)} h${minutes % 60 ? ` ${minutes % 60} min` : ''}`;
    }

    function focusDestination(place) {
        const marker = markers.get(String(place.id));
        if (marker) {
            map.setView(marker.getLatLng(), 13);
            marker.openPopup();
        }
    }

    function createCard(place, index, description, onSelect, route = false) {
        const card = document.createElement('article');
        card.className = `route-card${route ? ' has-route' : ''}`;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'route-select';
        const number = document.createElement('span');
        number.className = 'route-number';
        number.textContent = String(index + 1).padStart(2, '0');
        const text = document.createElement('span');
        text.className = 'route-card-copy';
        const heading = document.createElement('strong');
        heading.textContent = place.nombre;
        const subtitle = document.createElement('span');
        subtitle.className = 'route-place-location';
        subtitle.textContent = [place.provincia, place.tipo].filter(Boolean).join(' · ');
        const metric = document.createElement('span');
        metric.className = 'route-metric';
        metric.textContent = description;
        text.append(heading, subtitle, metric);
        const arrow = document.createElement('span');
        arrow.className = 'route-arrow';
        arrow.setAttribute('aria-hidden', 'true');
        arrow.textContent = '↗';
        button.append(number, text, arrow);
        button.setAttribute('aria-label', `${route ? 'Ver ruta a' : 'Ver en el mapa'} ${place.nombre}, ${description}`);
        button.addEventListener('click', onSelect);
        card.append(button);
        const linkUrl = safeLink((knownPlaces.get(String(place.id)) || place).url);
        if (linkUrl) {
            const link = document.createElement('a');
            link.className = 'route-detail-link';
            link.href = linkUrl;
            link.textContent = 'Ficha del destino →';
            card.append(link);
        }
        listNode.append(card);
        return { card, button };
    }

    function showInitialPlaces() {
        listNode.replaceChildren();
        const localPlaces = places.filter(place => /Cotuí|Fantino/i.test(place.nombre));
        (localPlaces.length ? localPlaces : places).slice(0, 3).forEach((place, index) => {
            createCard(place, index, 'Explorar este lugar', () => focusDestination(place));
        });
    }
    showInitialPlaces();

    function setBusy(busy) {
        locateButton.disabled = busy;
        originOptions.querySelectorAll('button').forEach(button => { button.disabled = busy; });
        layout.setAttribute('aria-busy', String(busy));
    }

    function clearRoutes() {
        routeLayers.forEach(layer => map.removeLayer(layer));
        routeLayers.length = 0;
        routeCards.length = 0;
        if (originLayer) map.removeLayer(originLayer);
        originLayer = null;
    }

    function selectRoute(index, fit = true) {
        routeLayers.forEach((layer, current) => {
            const selected = current === index;
            layer.setStyle({ weight: selected ? 6 : 4, opacity: selected ? 0.95 : 0.38 });
            routeCards[current].card.classList.toggle('is-active', selected);
            routeCards[current].button.setAttribute('aria-pressed', String(selected));
        });
        const selected = routeLayers[index];
        if (selected) {
            selected.bringToFront();
            layout.querySelector('.legend-route').style.backgroundColor = routeColors[index];
            if (fit) map.fitBounds(selected.getBounds(), { padding: [45, 45], maxZoom: 15 });
        }
    }

    function validGeometry(geometry) {
        return geometry && geometry.type === 'LineString'
            && Array.isArray(geometry.coordinates) && geometry.coordinates.length >= 2
            && geometry.coordinates.every(point => Array.isArray(point) && point.length >= 2
                && validCoordinates(point[1], point[0]));
    }

    function showNearby(nearby) {
        listNode.replaceChildren();
        (Array.isArray(nearby) ? nearby : []).slice(0, 3).forEach((item, index) => {
            if (!item || !item.place) return;
            createCard(item.place, index, `${metricDistance(item.distance_en_linea_m)} en línea recta`, () => focusDestination(item.place));
        });
    }

    async function loadRoutes(lat, lng, name) {
        if (!validCoordinates(lat, lng)) return;
        const currentOperation = ++operation;
        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        const request = activeRequest;
        clearRoutes();
        originLayer = L.circleMarker([Number(lat), Number(lng)], {
            radius: 9, color: '#ffffff', weight: 3, fillColor: '#ce1126', fillOpacity: 1,
        }).addTo(map).bindPopup(document.createTextNode(name));
        map.setView([Number(lat), Number(lng)], 11);
        resetButton.disabled = false;
        setBusy(true);
        setStatus(`Calculando destinos cercanos por carretera desde ${name}…`);
        panelHeading.textContent = 'Destinos cercanos';
        listNode.replaceChildren();
        const timeout = window.setTimeout(() => request.abort(), 22000);

        try {
            const endpoint = new URL(layout.dataset.routeEndpoint, window.location.href);
            endpoint.searchParams.set('lat', String(lat));
            endpoint.searchParams.set('lng', String(lng));
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' }, signal: request.signal,
            });
            const result = await response.json();
            if (currentOperation !== operation) return;
            if (response.status === 503 && result.status === 'unavailable') {
                showNearby(result.nearby);
                setStatus(`${result.message || 'El servicio de rutas no está disponible.'} Las distancias en línea recta no representan un recorrido por carretera.`, true);
                return;
            }
            if (!response.ok || result.status !== 'ok') {
                throw new Error(result.message || 'No pudimos calcular las rutas. Inténtalo de nuevo en unos momentos.');
            }

            const routes = (Array.isArray(result.routes) ? result.routes : []).filter(route => route && route.place
                && validGeometry(route.geometry) && Number.isFinite(Number(route.distance_m))
                && Number(route.distance_m) >= 0 && Number.isFinite(Number(route.duration_s))
                && Number(route.duration_s) >= 0).slice(0, 3);
            routes.forEach((route, index) => {
                const layer = L.geoJSON(route.geometry, {
                    style: { color: routeColors[index], weight: 4, opacity: 0.5 },
                }).addTo(map);
                layer.on('click', () => selectRoute(index));
                routeLayers.push(layer);
                const description = Number(route.distance_m) < 1
                    ? 'En el punto de partida'
                    : `${metricDistance(route.distance_m)} · ${metricDuration(route.duration_s)} aprox.`;
                routeCards.push(createCard(route.place, index, description,
                    () => selectRoute(index), true));
            });
            if (routes.length) {
                const firstTravelRoute = routes.findIndex(route => Number(route.distance_m) >= 1);
                selectRoute(firstTravelRoute >= 0 ? firstTravelRoute : 0, false);
                const bounds = L.featureGroup(routeLayers).getBounds();
                if (bounds.isValid()) map.fitBounds(bounds, { padding: [45, 45], maxZoom: 13 });
                const warnings = Array.isArray(result.warnings) ? result.warnings.filter(item => typeof item === 'string') : [];
                setStatus(`${routes.length} ${routes.length === 1 ? 'destino cercano' : 'destinos cercanos'} por carretera desde ${name}. Selecciona una tarjeta para ver su ruta.${warnings.length ? ` ${warnings.join(' ')}` : ''}`, warnings.length > 0);
            } else {
                showNearby(result.nearby);
                setStatus('No hay rutas por carretera disponibles desde este punto. Prueba con otro punto de partida.', true);
            }
        } catch (error) {
            if (currentOperation !== operation) return;
            setStatus(error.name === 'AbortError'
                ? 'El servicio tardó demasiado en responder. Puedes volver a calcular la ruta.'
                : (error.message && error.name === 'Error' ? error.message : 'No pudimos conectar con el servicio de rutas. Comprueba tu conexión e inténtalo de nuevo.'), true);
            showInitialPlaces();
        } finally {
            window.clearTimeout(timeout);
            if (currentOperation === operation) {
                activeRequest = null;
                setBusy(false);
            }
        }
    }

    origins.forEach(origin => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'map-origin-button';
        button.textContent = `Desde ${origin.nombre}`;
        button.addEventListener('click', () => loadRoutes(origin.latitud, origin.longitud, `el punto de referencia de ${origin.nombre}`));
        originOptions.append(button);
    });

    locateButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setStatus('Este navegador no puede obtener tu ubicación. Elige Cotuí o Fantino como punto de partida.', true);
            return;
        }
        const currentOperation = ++operation;
        setBusy(true);
        resetButton.disabled = false;
        setStatus('Permite el acceso a tu ubicación para encontrar destinos cercanos.');
        navigator.geolocation.getCurrentPosition(position => {
            if (currentOperation !== operation) return;
            loadRoutes(position.coords.latitude, position.coords.longitude, 'tu ubicación');
        }, error => {
            if (currentOperation !== operation) return;
            const messages = {
                1: 'No se permitió el acceso a tu ubicación. Puedes elegir Cotuí o Fantino como punto de partida.',
                2: 'No pudimos detectar tu ubicación. Elige un punto de referencia o vuelve a intentarlo.',
                3: 'La ubicación tardó demasiado en responder. Elige un punto de referencia o vuelve a intentarlo.',
            };
            setStatus(messages[error.code] || 'No pudimos detectar tu ubicación.', true);
            setBusy(false);
        }, { enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 });
    });

    resetButton.addEventListener('click', () => {
        operation++;
        if (activeRequest) activeRequest.abort();
        activeRequest = null;
        clearRoutes();
        map.closePopup();
        defaultView();
        showInitialPlaces();
        panelHeading.textContent = '¿Desde dónde salimos?';
        setStatus('Selecciona un punto de partida para ver hasta tres destinos cercanos.');
        setBusy(false);
        resetButton.disabled = true;
    });

    if (window.ResizeObserver) {
        new ResizeObserver(() => map.invalidateSize({ pan: false })).observe(canvas);
    }
})();
