import { Loc } from 'main.core';

export class BordersAccord
{
	#errorMessage: ?string = null;

	validate(lowBorder: number, highBorder: number): boolean
	{
		if (highBorder < lowBorder)
		{
			this.#errorMessage = Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_VALIDATION_ERROR');

			return false;
		}

		this.#errorMessage = null;

		return true;
	}

	getError(): ?string
	{
		return this.#errorMessage;
	}
}
