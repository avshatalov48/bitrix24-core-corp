import { Type } from 'main.core';
import { DocumentMode } from 'sign.type';

export function decorateResultBeforeCompletion(
	innerCallback: () => Promise<boolean>,
	onSuccess: () => void | Promise<void>,
	onFail: () => void | Promise<void>,
): () => Promise<boolean>
{
	return async () => {
		let result = false;
		try
		{
			result = await innerCallback();
		}
		catch (e)
		{
			await onFail();
			throw e;
		}

		if (result)
		{
			await onSuccess();
		}
		else
		{
			await onFail();
		}

		return result;
	};
}

export function isTemplateMode(mode: string): boolean
{
	return mode === DocumentMode.template;
}

export function getFilledStringOrUndefined(value: string): string | undefined
{
	return Type.isStringFilled(value) ? value : undefined;
}
