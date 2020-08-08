/**
 * Base class for AutocompleteServices
 */
export default class AutocompleteServiceBase
{
	autocomplete(text: string, params: Object): Promise
	{
		throw new Error('Must be implemented');
	}
}