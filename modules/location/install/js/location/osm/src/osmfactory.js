import {LocationRepository, SourceRepository} from 'location.core';
import {Leaflet} from '../leaflet/src/leaflet';
import OSM from './osm';
import AutocompleteService from './autocompleteservice';
import SearchRequester from './requesters/searchrequester';
import GeocodingService from './geocodingservice';
import ReverseRequester from './requesters/reverserequester';
import MapService from './mapservice';
import TileLayerAuth from '../leaflet/src/tilelayerauth';
import TokenContainer from './tokencontainer';

export type OSMFactoryProps = {
	languageId: string,
	sourceLanguageId: string,
	token: string,
	serviceUrl: string,
	hostName: string,
}

export default class OSMFactory
{
	static createOSMSource(params: OSMFactoryProps)
	{
		const tokenContainer = new TokenContainer({
			token: params.token,
			sourceRepository: new SourceRepository()
		});

		const osmParams =	{
			languageId: params.languageId,
			sourceLanguageId: params.sourceLanguageId
		};

		const searchRequester = new SearchRequester({
			languageId: params.languageId,
			tokenContainer: tokenContainer,
			serviceUrl: params.serviceUrl,
			hostName: params.hostName,
		});
	
		const reverseRequester = new ReverseRequester({
			languageId: params.languageId,
			serviceUrl: params.serviceUrl,
			hostName: params.hostName,
			tokenContainer: tokenContainer
		});

		osmParams.autocompleteService = new AutocompleteService({
			requester: searchRequester
		});

		const geocodingService = new GeocodingService({
			searchRequester: searchRequester,
			reverseRequester: reverseRequester
		});

		osmParams.geocodingService = geocodingService;

		osmParams.mapService = new MapService({
			languageId: params.languageId,
			geocodingService: geocodingService,
			mapFactoryMethod: Leaflet.map,
			markerFactoryMethod: Leaflet.marker,
			locationRepository: new LocationRepository(),
			sourceLanguageId: params.sourceLanguageId,
			tileLayerFactoryMethod: () => {
				const tileLayerAuth = new TileLayerAuth();
				tileLayerAuth.setTokenContainer(tokenContainer);
				tileLayerAuth.setHostName(params.hostName);
				return tileLayerAuth;
			},
			serviceUrl: params.serviceUrl,
		});

		return new OSM(osmParams);
	}
}