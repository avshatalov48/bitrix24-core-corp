import { Type } from 'main.core';

export function prepareBaasContext(contextId: string):  string
{
	if (Type.isStringFilled(contextId) === false)
	{
		throw new TypeError('Parameter must be the filled string.');
	}

	return removeNumbersAfterUnderscore(contextId);
}

function removeNumbersAfterUnderscore(str: string): string
{
	return str
		.split('_')
		.map((strPart) => {
			if (Number.isNaN(parseInt(strPart, 10)))
			{
				return strPart;
			}

			return '';
		})
		.filter((strPart) => strPart)
		.join('_');
}
