import * as Type from './types';

class YandexCaptcha
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
			return (b24form.common.properties.captcha.yandexCaptcha || {}).key;
		}

		return null;
	}

	getResponse(): string | null
	{
		return this.#response;
	}

	verify(callback: Function): void
	{
		if (!window.smartCaptcha)
		{
			return;
		}

		if (callback)
		{
			this.#callback = callback;
		}
		this.#response = '';
		window.smartCaptcha.execute(this.#widgetId);
	}

	render(target): void
	{
		if (!window.smartCaptcha)
		{
			return;
		}

		this.#target = target;
		window.smartCaptcha.render(target, {
			sitekey: this.getKey(),
			invisible: true,
			callback: (response): void => {
				this.#response = response;
				if (this.#callback)
				{
					this.#callback();
					this.#callback = null;
				}
			},
		});
	}
}

export default YandexCaptcha;
