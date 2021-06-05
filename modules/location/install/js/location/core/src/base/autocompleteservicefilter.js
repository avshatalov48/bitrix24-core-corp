import {LocationType} from 'location.core';

/**
 * Base class for the autocomplete filter.
 */
export default class AutocompleteServiceFilter
{
	types: Array<LocationType> = [];

	reset()
	{
		this.types = [];
	}
}