import { Type } from 'main.core';

type UrlProvidedParamsType = {
	focusNodeId: ?number,
};

const UrlParamNames = Object.freeze({
	FocusNodeId: 'focusNodeId',
});

export class UrlProvidedParamsService
{
	static getParams(): UrlProvidedParamsType
	{
		const paramMap = this.#getParamsFromUrl();

		return {
			focusNodeId: this.#castAndValidate(UrlParamNames.FocusNodeId, paramMap.get(UrlParamNames.FocusNodeId)),
		};
	}

	static #getParamsFromUrl(): Map<$Values<typeof UrlParamNames>, string>
	{
		const paramMap = new Map();

		const urlSearchParams = new URLSearchParams(document.location.search);
		Object.values(UrlParamNames).forEach((paramName: string) => {
			const paramValue = urlSearchParams.get(paramName);
			if (!paramValue)
			{
				return;
			}

			paramMap.set(paramName, paramValue);
		});

		return paramMap;
	}

	static #castAndValidate(paramName: string, value: ?string): string | number | null
	{
		if (Type.isUndefined(value))
		{
			return null;
		}

		let castedValue = value;
		if (paramName === UrlParamNames.FocusNodeId)
		{
			castedValue = Number(value);
			if (!Type.isInteger(castedValue))
			{
				return null;
			}
		}

		return castedValue;
	}
}
