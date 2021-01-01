import {AutocompleteServiceBase} from 'location.core';

export default class AutocompleteService extends AutocompleteServiceBase
{
	#requester;

	constructor(params: {})
	{
		super();
		this.#requester = params.requester;
	}

	autocomplete(text: string, params: Object): Promise<Array<Location>, Error>
	{
		return this.#requester.request({query: text, params: params});
	}
}