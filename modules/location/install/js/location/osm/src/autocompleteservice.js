import {AutocompleteServiceBase} from 'location.core';

export default class AutocompleteService extends AutocompleteServiceBase
{
	#requester;
	/** {number} Radius in kilometers */
	#biasBoundRadius = 50;

	constructor(params: {})
	{
		super();
		this.#requester = params.requester;
	}

	autocomplete(text: string, params: Object): Promise<Array<Location>, Error>
	{
		let queryParams = {query: text, params: params};

		if(params.userCoordinates)
		{
			queryParams.viewbox = this.#getBoundsFromLatLng(
				params.userCoordinates[0],
				params.userCoordinates[1]
			);
		}

		return this.#requester.request(queryParams);
	}

	#getBoundsFromLatLng(lat: string, lng: string): string
	{
		let latChange = this.#biasBoundRadius/111.2;
		let lonChange = Math.abs(Math.cos(lat*(Math.PI/180)));

		let bounds = {
			latA : lat - latChange,
			lonA : lng - lonChange,
			latB : lat + latChange,
			lonB : lng + lonChange
		};

		return `${bounds.lonA},${bounds.latA},${bounds.lonB},${bounds.latB}`;
	}
}