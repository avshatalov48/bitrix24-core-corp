import { Loc as LocCore } from 'main.core';

export class Loc
{
	static instance: Loc;
	static generalKeys = [
		'help_link',
		'agree_with_terms',
	];

	#messages: {[key: string]: string} = {
		header: null,
		submit: null,
		action_use: null,
		action_copy: null,
		action_copy_notify: null,
		max_capacity: null,
		placeholder: null,
	};

	static getInstance(): this
	{
		if (!Loc.instance)
		{
			Loc.instance = new Loc();
		}

		return Loc.instance;
	}

	/**
	 * Sets language space. For different interface may be used different phrases.
	 * See all bunches of phrases in lang/config.php.
	 *
	 * @param {string} spaceCode
	 */
	setSpace(spaceCode: string): void
	{
		Object.keys(this.#messages).forEach((key) => {
			this.#messages[key] = LocCore.getMessage(`AI_JS_PICKER_${spaceCode.toUpperCase()}_${key.toUpperCase()}`);
		});

		Loc.generalKeys.forEach((key) => {
			this.#messages[key] = LocCore.getMessage(`AI_JS_PICKER_GENERAL_${key.toUpperCase()}`);
		});
	}

	/**
	 * Returns phrase by certain message code.
	 *
	 * @param {messageCode} messageCode
	 * @return {string}
	 */
	getMessage(messageCode: string): string
	{
		return this.#messages[messageCode];
	}
}
