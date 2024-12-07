import { Type } from 'main.core';
import { loadExtensionWrapper } from '../copilot';

export class CopilotEula
{
	static async init(): Promise<boolean>
	{
		const Feature = await loadExtensionWrapper('bitrix24.license.feature');
		if (!Feature?.Feature)
		{
			return false;
		}

		const isRestrictionCheckInProgress = Type.isFunction(CopilotEula.#staticEulaRestrictCallback?.then);
		const isRestrictionNotChecked = CopilotEula.#staticEulaRestrictCallback === null;

		if (isRestrictionNotChecked || isRestrictionCheckInProgress)
		{
			try
			{
				if (isRestrictionNotChecked)
				{
					CopilotEula.#staticEulaRestrictCallback = Feature.Feature.checkEulaRestrictions('ai_available_by_version');
				}

				await CopilotEula.#staticEulaRestrictCallback;

				CopilotEula.#staticEulaRestrictCallback = false;

				return false;
			}
			catch (err)
			{
				if (err.callback)
				{
					CopilotEula.#staticEulaRestrictCallback = err.callback;

					return true;
				}

				console.error(err);

				return false;
			}
		}

		return Type.isFunction(CopilotEula.#staticEulaRestrictCallback);
	}

	static checkRestricted(): boolean
	{
		if (Type.isFunction(CopilotEula.#staticEulaRestrictCallback))
		{
			CopilotEula.#staticEulaRestrictCallback();

			return true;
		}

		return false;
	}

	static #staticEulaRestrictCallback: Function;
}
