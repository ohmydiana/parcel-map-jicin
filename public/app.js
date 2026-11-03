const AREA_BOUNDS = [15.3284, 50.3957, 15.428, 50.4598];
const PARCEL_COLOR = '#1f4fa3';
const CADASTRAL_AREA_COLOR = '#b0127b';
const SELECTED_COLOR = '#f0a800';

const elements = {
    message: document.getElementById('detail-message'),
    content: document.getElementById('detail-content'),
    number: document.getElementById('parcel-number'),
    cadastralArea: document.getElementById('parcel-cadastral-area'),
    area: document.getElementById('parcel-area'),
    landType: document.getElementById('parcel-land-type'),
    landUse: document.getElementById('parcel-land-use'),
    ruianLink: document.getElementById('parcel-ruian-link'),
    clearSelection: document.getElementById('clear-selection'),
};

const map = new maplibregl.Map({
    container: 'map',
    bounds: AREA_BOUNDS,
    fitBoundsOptions: { padding: 24 },
    maxZoom: 20,
    style: {
        version: 8,
        sources: {
            osm: {
                type: 'raster',
                tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                tileSize: 256,
                maxzoom: 19,
                attribution: '© <a href="https://www.openstreetmap.org/copyright">přispěvatelé OpenStreetMap</a>',
            },
            cadastre: {
                type: 'vector',
                tiles: [`${window.location.origin}/api/tiles/{z}/{x}/{y}`],
                bounds: AREA_BOUNDS,
                minzoom: 10,
                maxzoom: 16,
                attribution: '© <a href="https://cuzk.gov.cz">ČÚZK</a>',
            },
        },
        layers: [
            {
                id: 'base-map',
                type: 'raster',
                source: 'osm',
                paint: { 'raster-saturation': -0.85, 'raster-opacity': 0.75 },
            },
            {
                id: 'parcels-fill',
                type: 'fill',
                source: 'cadastre',
                'source-layer': 'parcels',
                paint: {
                    'fill-color': ['case', ['boolean', ['feature-state', 'selected'], false], SELECTED_COLOR, PARCEL_COLOR],
                    'fill-opacity': ['case', ['boolean', ['feature-state', 'selected'], false], 0.65, 0.04],
                },
            },
            {
                id: 'parcels-outline',
                type: 'line',
                source: 'cadastre',
                'source-layer': 'parcels',
                paint: {
                    'line-color': PARCEL_COLOR,
                    'line-width': ['interpolate', ['linear'], ['zoom'], 12, 0.25, 16, 1, 19, 1.5],
                    'line-opacity': ['interpolate', ['linear'], ['zoom'], 11, 0.35, 15, 0.9],
                },
            },
            {
                id: 'cadastral-areas-outline',
                type: 'line',
                source: 'cadastre',
                'source-layer': 'cadastral_areas',
                paint: {
                    'line-color': CADASTRAL_AREA_COLOR,
                    'line-width': ['interpolate', ['linear'], ['zoom'], 11, 2, 16, 3],
                },
            },
        ],
    },
});

map.addControl(new maplibregl.NavigationControl({ showCompass: false }));
map.addControl(new maplibregl.ScaleControl());

let selectedParcelId = null;
let detailRequest = null;

function setSelectedParcel(id) {
    if (selectedParcelId !== null) {
        map.setFeatureState({ source: 'cadastre', sourceLayer: 'parcels', id: selectedParcelId }, { selected: false });
    }

    selectedParcelId = id;

    if (id !== null) {
        map.setFeatureState({ source: 'cadastre', sourceLayer: 'parcels', id }, { selected: true });
    }
}

function showMessage(text) {
    elements.message.textContent = text;
    elements.message.hidden = false;
    elements.content.hidden = true;
}

function showParcel(parcel) {
    elements.number.textContent = parcel.number;
    elements.cadastralArea.textContent = parcel.cadastralArea.name;
    elements.area.textContent = `${parcel.areaM2.toLocaleString('cs-CZ')} m²`;
    elements.landType.textContent = parcel.landType;
    elements.landUse.textContent = parcel.landUse ?? 'neuvedeno';
    elements.ruianLink.href = parcel.ruianUrl;
    elements.message.hidden = true;
    elements.content.hidden = false;
}

async function loadParcel(id) {
    detailRequest?.abort();
    detailRequest = new AbortController();
    showMessage('Načítám údaje o parcele…');

    try {
        const response = await fetch(`/api/parcels/${id}`, { signal: detailRequest.signal });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        showParcel(await response.json());
    } catch (error) {
        if (error.name !== 'AbortError') {
            showMessage('Údaje o parcele se nepodařilo načíst. Klikněte na parcelu znovu.');
        }
    }
}

function clearSelection() {
    detailRequest?.abort();
    setSelectedParcel(null);
    showMessage('Kliknutím na parcelu v mapě zobrazíte její údaje.');
}

map.on('click', (event) => {
    const [parcel] = map.queryRenderedFeatures(event.point, { layers: ['parcels-fill'] });

    if (!parcel) {
        clearSelection();

        return;
    }

    setSelectedParcel(parcel.id);
    loadParcel(parcel.id);
});

map.on('mousemove', 'parcels-fill', () => {
    map.getCanvas().style.cursor = 'pointer';
});

map.on('mouseleave', 'parcels-fill', () => {
    map.getCanvas().style.cursor = '';
});

elements.clearSelection.addEventListener('click', clearSelection);