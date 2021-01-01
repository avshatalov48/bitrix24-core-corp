import {Location} from 'location.core';

/**
 * Base class for autocomplete source services
 */
export default class AutocompleteServiceBase
{
	autocomplete(text: string, params: Object): Promise<Array<Location>, Error>
	{
		throw new Error('Method autocomplete() Must be implemented');
	}
}