import { Loc } from 'main.core';

const LENGTH_RANGE = {
	MIN: 100,
	MAX: 5000,
};

export class PromptLength
{
	#errorMessage: ?string = null;

	validate(prompt: string): boolean
	{
		if (prompt.length < LENGTH_RANGE.MIN)
		{
			this.#errorMessage = Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_PROMPT_LENGTH_MIN_ERROR', {
				'#MIN_VALUE#': LENGTH_RANGE.MIN,
			});

			return false;
		}

		if (prompt.length > LENGTH_RANGE.MAX)
		{
			this.#errorMessage = Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_PROMPT_LENGTH_MAX_ERROR', {
				'#MAX_VALUE#': LENGTH_RANGE.MAX,
			});

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
