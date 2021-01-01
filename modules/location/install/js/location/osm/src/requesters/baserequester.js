import {Address, Location, LocationType, AddressType} from 'location.core';

export default class BaseRequester
{
	languageId;
	serviceUrl;
	hostName;
	tokenContainer;

	constructor(props)
	{
		this.languageId = props.languageId;
		this.serviceUrl = props.serviceUrl;
		this.hostName = props.hostName;
		this.tokenContainer = props.tokenContainer;
	}

	createUrl(params: Object): string
	{
		throw new Error('Not implemented');
	}

	sendRequest(params: Object)
	{
		return fetch(this.createUrl(params), {
			method: 'GET',
			headers: new Headers({
				'Authorization': `Bearer ${this.tokenContainer.token}`,
				'Bx-Location-Osm-Host': this.hostName,
			}),
		})
			.then((response) => {

				if(response.status === 200)
				{
					return response.json();
				}

				if(response.status === 401 && !params.isUnAuth)
				{
					return this.#processUnauthorizedResponse(params);
				}

				console.error(`Response status: ${response.status}`);
				return null;
			});
	}

	request(params: Object): Promise<JSON>
	{
		return this.sendRequest(params)
			.then((json) =>
			{
				return json ? this.jsonToLocation(json) : [];
			})
			.catch((response) => {
				console.error(response);
			});
	}

	#processUnauthorizedResponse(params)
	{
		return this.tokenContainer.refreshToken()
			.then((sourceToken) => {
				params.isUnAuth = true;
				return this.sendRequest(params);
			});
	}

	jsonToLocation(responseJson)
	{
		let result;

		if(Array.isArray(responseJson))
		{
			result = [];

			if(responseJson.length > 0)
			{
				responseJson.forEach((item) =>
				{
					result.push(
						this.createLocation(item)
					);
				});
			}
		}
		else if(typeof responseJson === 'object')
		{
			result = this.createLocation(responseJson);
		}

		return result;
	}

	createLocation(responseItem)
	{
		return new Location({
			externalId: this.createExternalId(responseItem.osm_type, responseItem.osm_id),
			latitude: responseItem.lat,
			longitude: responseItem.lon,
			type: LocationType.UNKNOWN,
			name: responseItem.display_name,
			languageId: this.languageId,
			sourceCode: 'OSM'
		});
	}

	createExternalId(osmType: string, osmId: string)
	{
		return osmType.substr(0, 1).toLocaleUpperCase() + osmId;
	}
}