import * as Leaflet from './vendor/leaflet-src.esm';
import TileLayerAuth from './tilelayerauth';
import './vendor/leaflet.css';

Leaflet.Icon.Default.imagePath = '/bitrix/js/location/osm/leaflet/images/';

export {
	Leaflet,
	TileLayerAuth
};