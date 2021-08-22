import * as Type from './types';

class ReCaptcha
{
	#key: string;
	#use: boolean = false;
	#widgetId: string;
	#response: string;
	#target: string|Element;
	#callback: Function;

	adjust(options: Type.ReCaptcha)
	{
		if (typeof options.key !== "undefined")
		{
			this.#key = options.key;
		}
		if (typeof options.use !== "undefined")
		{
			this.#use = options.use;
		}
	}

	canUse()
	{
		return this.#use && this.getKey();
	}

	isVerified()
	{
		return !this.canUse() || !!this.#response;
	}

	getKey()
	{
		if (this.#key)
		{
			return this.#key;
		}

		if (b24form && b24form.common)
		{
			return (b24form.common.properties.recaptcha || {}).key;
		}

		return null;
	}

	getResponse()
	{
		return this.#response;
	}

	verify(callback: Function)
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

	render(target)
	{
		if (!window.grecaptcha)
		{
			return;
		}

		this.#target = target;
		this.#widgetId = window.grecaptcha.render(
			target,
			{
				sitekey: this.getKey(), //this.#key,
				badge: 'inline',
				size: 'invisible',
				callback: (response) => {
					this.#response = response;
					if (this.#callback)
					{
						this.#callback();
						this.#callback = null;
					}
				},
				'error-callback': () => {
					this.#response = '';
				},
				'expired-callback': () => {
					this.#response = '';
				},
			}
		);
	}
}

export default ReCaptcha