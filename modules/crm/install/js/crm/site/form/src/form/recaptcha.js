import * as Type from './types';

class ReCaptcha
{
	#key: string;
	#use: boolean = false;
	#widgetId: string;
	#response: string;
	#target: string|Element;
	#callback: Function;

	adjust(options: Type.ReCaptcha): void
	{
		if (typeof options.key !== 'undefined')
		{
			this.#key = options.key;
		}

		if (typeof options.use !== 'undefined')
		{
			this.#use = options.use;
		}
	}

	canUse(): boolean
	{
		return this.#use && this.getKey();
	}

	isVerified(): boolean
	{
		return !this.canUse() || Boolean(this.#response);
	}

	getKey(): string | null
	{
		if (this.#key)
		{
			return this.#key;
		}

		// eslint-disable-next-line no-undef
		if (b24form && b24form.common)
		{
			// eslint-disable-next-line no-undef
			return (b24form.common.properties.captcha.recaptcha || {}).key;
		}

		return null;
	}

	getResponse(): string
	{
		return this.#response;
	}

	verify(callback: Function): void
	{
		if (!window.grecaptcha)
		{
			return;
		}

		if (callback)
		{
			this.#callback = callback;
		}
		this.#response = '';
		window.grecaptcha.execute(this.#widgetId);
	}

	render(target): void
	{
		if (!window.grecaptcha)
		{
			return;
		}

		this.#target = target;
		this.#widgetId = window.grecaptcha.render(
			target,
			{
				sitekey: this.getKey(), // this.#key,
				badge: 'inline',
				size: 'invisible',
				callback: (response): void => {
					this.#response = response;
					if (this.#callback)
					{
						this.#callback();
						this.#callback = null;
					}
				},
				'error-callback': (): void => {
					this.#response = '';
				},
				'expired-callback': (): void => {
					this.#response = '';
				},
			},
		);
	}
}

export default ReCaptcha;
