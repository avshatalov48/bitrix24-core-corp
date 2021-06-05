import type {AutocompleteServiceParams} from 'location.core';
import {LocationType} from 'location.core';
import BaseRequester from './baserequester';

export default class AutocompleteRequester extends BaseRequester
{
	#locationBiasScale; // 0.1 - 10
	#autocompletePromptsCount;
	#sourceCode;

	constructor(props)
	{
		super(props);
		this.#autocompletePromptsCount = props.autocompletePromptsCount || 7;
		this.#locationBiasScale = props.locationBiasScale || 9;
		this.#sourceCode = props.sourceCode || 'OSM';
	}

	createUrl(params: {text: string, autocompleteParams: AutocompleteServiceParams}): string
	{
		const text = params.text;
		const autocompleteParams = params.autocompleteParams;
		let result = `${this.serviceUrl}/?`
			+ 'action=osmgateway.autocomplete.autocomplete'
			+ `&params[q]=${encodeURIComponent(text)}`
			+ `&params[limit]=${this.#autocompletePromptsCount}`
			+ `&params[lang]=${this.languageId}`;

		if (autocompleteParams.locationForBias)
		{
			const lat = autocompleteParams.locationForBias.latitude;
			const lon = autocompleteParams.locationForBias.longitude;

			result += `&params[lat]=${lat}`
				+ `&params[lon]=${lon}`
				+ `&params[location_bias_scale]=${this.#locationBiasScale}`;
		}

		if (autocompleteParams.filter && autocompleteParams.filter.types)
		{
			if (autocompleteParams.filter.types.indexOf(LocationType.BUILDING) !== -1)
			{
				result += '&params[osm_tag]=building&params[osm_tag]=place';
			}

			if (autocompleteParams.filter.types.indexOf(LocationType.STREET) !== -1)
			{
				result += '&params[osm_tag]=highway';
			}

			if (autocompleteParams.filter.types.indexOf(LocationType.LOCALITY) !== -1)
			{
				result += '&params[osm_tag]=:locality';
			}
		}

		return result;
	}
}