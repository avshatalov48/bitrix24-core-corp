import {Type, Loc, Text} from 'main.core';

export default function makeErrorMessageFromResponse(response)
{
	if (response.data && response.data.errors && Type.isArray(response.data.errors) && response.data.errors.length > 0)
	{
		return response.data.errors.reduce((acc, errorText) => {
			return `${acc}${Text.encode(errorText)}<br>`;
		}, '');
	}
	if(response.errors && Type.isArray(response.errors) && response.errors.length > 0)
	{
		return response.errors.reduce((result, error) => {
			return `${result}${Text.encode(error.message ? error.message : error)}<br>`;
		}, '');
	}

	return Loc.getMessage('CRM_ST_SAVE_ERROR2');
}
