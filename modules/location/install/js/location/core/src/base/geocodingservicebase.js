import {Location} from 'location.core';

/**
 * Base class for the source geocoding service
 */
export default class GeocodingServiceBase
{
	geocode(addressString: string): Promise<Array<Location>, Error>
	{
		throw new Error('Method geocode() must be implemented');
	}
}