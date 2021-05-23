import {Type, Loc, Text} from 'main.core';

export default function makeErrorMessageFromResponse({data})
{
	if (Type.isArray(data.errors) && data.errors.length > 0)
	{
		return data.errors.reduce((acc, errorText) => {
			return `${acc}${Text.encode(errorText)}<br>`;
		}, '');
	}

	return Loc.getMessage('CRM_ST_SAVE_ERROR');
}