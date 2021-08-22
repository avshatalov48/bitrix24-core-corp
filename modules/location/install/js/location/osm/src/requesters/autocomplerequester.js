import type {AutocompleteServiceParams} from 'location.core';
import {LocationType} from 'location.core';
import BaseRequester from './baserequester';

export default class AutocompleteRequester extends BaseRequester
{
	#locationBiasScale; // 0.1 - 10
	#autocompletePromptsCount;
	#sourceCode;
	#autocompleteReplacements;

	constructor(props)
	{
		super(props);
		this.#autocompletePromptsCount = props.autocompletePromptsCount || 7;
		this.#locationBiasScale = props.locationBiasScale || 9;
		this.#sourceCode = props.sourceCode || 'OSM';
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
			+ `&params[lang]=${this.languageId}`;

		if (autocompleteServiceParams.locationForBias)
		{
			const lat = autocompleteServiceParams.locationForBias.latitude;
			const lon = autocompleteServiceParams.locationForBias.longitude;

			if (lat && lon)
			{
				result += `&params[lat]=${lat}`
					+ `&params[lon]=${lon}`
					+ `&params[location_bias_scale]=${this.#locationBiasScale}`;
			}

			if (autocompleteServiceParams.locationForBias.address)
			{
				const address = autocompleteServiceParams.locationForBias.address;

				if (address.getFieldValue(LocationType.LOCALITY))
				{
					result += `&params[probable_city]=${address.getFieldValue(LocationType.LOCALITY)}`;
				}
			}
		}

		if (autocompleteServiceParams.filter && autocompleteServiceParams.filter.types)
		{
			if (autocompleteServiceParams.filter.types.indexOf(LocationType.BUILDING) !== -1)
			{
				result += '&params[osm_tag][]=building&params[osm_tag][]=place&params[osm_tag][]=amenity';
			}

			if (autocompleteServiceParams.filter.types.indexOf(LocationType.STREET) !== -1)
			{
				result += '&params[osm_tag][]=highway';
			}

			if (autocompleteServiceParams.filter.types.indexOf(LocationType.LOCALITY) !== -1)
			{
				result += '&params[osm_tag][]=:locality';
			}
		}

		return result;
	}
}