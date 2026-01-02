import { Type } from 'main.core';
import Notifier from './error-notifier/notifier';
import Notifier0 from './error-notifier/notifier-0';
import Notifier200 from './error-notifier/notifier-200';
import Notifier403 from './error-notifier/notifier-403';

export default class ErrorNotifierFactory
{
	static createFromResponse(response = {}): Notifier
	{
		if (response && Type.isArrayLike(response.errors))
		{
			const error = [...response.errors].shift();

			if (error.customData && error.customData.status)
			{
				switch (error.customData.status)
				{
					case Notifier0.STATUS:
						return new Notifier0(
							error.message,
							error.customData.effective_url,
							error.customData.body,
						);
					case Notifier403.STATUS:
						return new Notifier403(
							error.message,
							error.customData.effective_url,
							error.customData.body,
						);
					default:
						return new Notifier200(
							error.message,
							error.customData.effective_url,
							error.customData.body,
						);
				}
			}

			switch (error.code)
			{
				case Notifier0.CODE:
					return new Notifier0(
						error.message,
						error.customData.effective_url,
						error.customData.body,
					);
				case Notifier403.CODE:
					return new Notifier403(
						error.message,
						error.customData.effective_url,
						error.customData.body,
					);
				default:
					return new Notifier(
						error.message,
					);
			}
		}

		return new Notifier();
	}
}
