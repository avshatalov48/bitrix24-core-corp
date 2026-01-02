import { Type } from 'main.core';

export function getBackUrl(): string
{
	const backUrl = window.location.pathname;
	const query = getQueryString(['logout', 'login', 'back_url_pub', 'user_lang']);

	return backUrl + (query.length > 0 ? `?${query}` : '');
}

function getQueryString(ignoredParams): string
{
	const query = window.location.search.slice(1);

	if (!Type.isStringFilled(query))
	{
		return '';
	}

	const vars = query.split('&');
	const checkedIgnoredParams = Type.isArray(ignoredParams) ? ignoredParams : [];

	let result = '';
	for (const variable of vars)
	{
		const pair = variable.split('=');
		const equal = variable.indexOf('=');
		const key = pair[0];
		const value = Type.isStringFilled(pair[1]) ? pair[1] : false;
		if (!checkedIgnoredParams.includes(key))
		{
			if (result !== '')
			{
				result += '&';
			}
			result += key + (equal === -1 ? '' : '=') + (value === false ? '' : value);
		}
	}

	return result;
}
