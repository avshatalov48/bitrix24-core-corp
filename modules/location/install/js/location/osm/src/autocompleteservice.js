import {AutocompleteServiceBase, Location} from 'location.core';
import type {AutocompleteServiceParams} from 'location.core';

export default class AutocompleteService extends AutocompleteServiceBase
{
	#autocompleteRequester;

	constructor(props)
	{
		super(props);
		this.#autocompleteRequester = props.autocompleteRequester;
	}

	autocomplete(text: String, autocompleteParams: AutocompleteServiceParams): Promise<Array<Location>, Error>
	{
		if (text === '')
		{
			return new Promise((resolve) =>
			{
				resolve([]);
			});
		}

		return this.#autocompleteRequester.request({text, autocompleteParams});
	}
}