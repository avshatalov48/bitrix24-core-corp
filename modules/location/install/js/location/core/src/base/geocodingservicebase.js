import {Location} from 'location.core';

export default class GeocodingServiceBase
{
	geocode(addressString: string): Promise<Array<Location>, Error>
	{
		throw new Error('Must be implemented');
	}
}