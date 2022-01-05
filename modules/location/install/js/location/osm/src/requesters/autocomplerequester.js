import type {AutocompleteServiceParams} from 'location.core';
import BaseRequester from './baserequester';
import OSM from '../osm';

export default class AutocompleteRequester extends BaseRequester
{
	#autocompletePromptsCount;
	#sourceCode;
	#autocompleteReplacements;

	constructor(props)
	{
		super(props);
		this.#autocompletePromptsCount = props.autocompletePromptsCount || 7;
		this.#sourceCode = props.sourceCode || OSM.code;
		this.#autocompleteReplacements = props.autocompleteReplacements || {};
	}

	#processQuery(query: string): string
	{
		let result = query;

		for (const i in this.#autocompleteReplacements)
		{
			if (this.#autocompleteReplacements.hasOwnProperty(i))
			{
				result = result.replace(i, this.#autocompleteReplacements[i])
			}
		}

		return result;
	}

	createUrl(params: {text: string, autocompleteServiceParams: AutocompleteServiceParams}): string
	{
		const text = this.#processQuery(params.text);
		const autocompleteServiceParams = params.autocompleteServiceParams;
		let result = `${this.serviceUrl}/?`
			+ 'action=osmgateway.autocomplete.autocomplete'
			+ `&params[q]=${encodeURIComponent(text)}`
			+ `&params[limit]=${this.#autocompletePromptsCount}`
			+ `&params[lang]=${this.sourceLanguageId}`
			+ `&params[version]=2`;

		if (autocompleteServiceParams.biasPoint)
		{
			const lat = autocompleteServiceParams.biasPoint.latitude;
			const lon = autocompleteServiceParams.biasPoint.longitude;

			if (lat && lon)
			{
				result += `&params[lat]=${lat}&params[lon]=${lon}`;
			}
		}

		return result;
	}
}
