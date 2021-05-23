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
					let location = this.createLocation(item);
					if (location)
					{
						result.push(location);
					}
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
		const externalId = this.createExternalId(responseItem.osm_type, responseItem.osm_id);
		if (!externalId)
		{
			return null;
		}

		return new Location({
			externalId: externalId,
			latitude: responseItem.lat,
			longitude: responseItem.lon,
			type: this.convertLocationType(responseItem.type),
			name: responseItem.display_name,
			languageId: this.languageId,
			sourceCode: 'OSM'
		});
	}

	// We need this at least for defining the right zoom on map
	convertLocationType(type: string)
	{
		const typeMap = {
			country: LocationType.COUNTRY,
			municipality: LocationType.LOCALITY,
			city: LocationType.LOCALITY,
			town: LocationType.LOCALITY,
			village: LocationType.LOCALITY,
			postal_town: LocationType.LOCALITY,
			road: LocationType.STREET,
			street_address: LocationType.ADDRESS_LINE_1,
			county: LocationType.ADM_LEVEL_4,
			state_district: LocationType.ADM_LEVEL_3,
			state: LocationType.ADM_LEVEL_2,
			region: LocationType.ADM_LEVEL_1,
			floor: LocationType.FLOOR,
			postal_code: AddressType.POSTAL_CODE,
			room: LocationType.ROOM,
			sublocality: LocationType.SUB_LOCALITY,
			city_district: LocationType.SUB_LOCALITY_LEVEL_1,
			district: LocationType.SUB_LOCALITY_LEVEL_1,
			borough: LocationType.SUB_LOCALITY_LEVEL_1,
			suburb: LocationType.SUB_LOCALITY_LEVEL_1,
			subdivision: LocationType.SUB_LOCALITY_LEVEL_1,
			house_number: LocationType.BUILDING,
			house_name: LocationType.BUILDING,
			building: LocationType.BUILDING
		};

		let result = LocationType.UNKNOWN;

		if(typeof typeMap[type] !== 'undefined')
		{
			result = typeMap[type];
		}

		return result;
	}

	createExternalId(osmType: string, osmId: string)
	{
		if (!osmType || !osmId)
		{
			return null;
		}

		return osmType.substr(0, 1).toLocaleUpperCase() + osmId;
	}
}