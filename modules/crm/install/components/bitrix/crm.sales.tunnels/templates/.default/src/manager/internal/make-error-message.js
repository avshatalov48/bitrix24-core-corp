import {Type, Loc} from 'main.core';

export default function makeErrorMessageFromResponse({data})
{
	if (Type.isArray(data.errors) && data.errors.length > 0)
	{
		return data.errors.join('<br>');
	}

	return Loc.getMessage('CRM_ST_SAVE_ERROR');
}