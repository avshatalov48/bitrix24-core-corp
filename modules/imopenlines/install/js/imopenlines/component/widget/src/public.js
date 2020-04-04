/**
 * Bitrix OpenLines widget
 * Widget public interface
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Widget} from "./widget"
import {SubscriptionType} from "./const";

export class WidgetPublicManager
{
	constructor(config)
	{
		this.developerInfo = 'Do not use private methods.';
		this.__privateMethods__ = new Widget(config);
		this.__createLegacyMethods();
	}

	open(params)
	{
		return this.__privateMethods__.open(params);
	}

	close()
	{
		return this.__privateMethods__.close();
	}

	showNotification(params)
	{
		return this.__privateMethods__.showNotification(params);
	}

	getUserData()
	{
		return this.__privateMethods__.getUserData();
	}

	setUserRegisterData(params)
	{
		return this.__privateMethods__.setUserRegisterData(params);
	}

	setCustomData(params)
	{
		return this.__privateMethods__.setCustomData(params);
	}

	mutateTemplateComponent(id, params)
	{
		return this.__privateMethods__.mutateTemplateComponent(id, params);
	}

	addLocalize(phrases)
	{
		return this.__privateMethods__.addLocalize(phrases);
	}

	/**
	 *
	 * @param params {Object}
	 * @returns {Function|Boolean} - Unsubscribe callback function or False
	 */
	subscribe(params)
	{
		return this.__privateMethods__.subscribe(params);
	}

	start()
	{
		return this.__privateMethods__.start();
	}

	__createLegacyMethods()
	{
		if (typeof window.BX.LiveChat === 'undefined')
		{
			let sourceHref = document.createElement('a');
			sourceHref.href = this.__privateMethods__.host;

			let sourceDomain = sourceHref.protocol+'//'+sourceHref.hostname+(sourceHref.port && sourceHref.port != '80' && sourceHref.port != '443'? ":"+sourceHref.port: "");

			window.BX.LiveChat = {
				openLiveChat: () => {
					this.open({openFromButton: true});
				},
				closeLiveChat: () => {
					this.close();
				},
				addEventListener: (el, eventName, handler) =>
				{
					if (eventName === 'message')
					{
						this.subscribe({
							type: SubscriptionType.userMessage,
							callback: function (event)
							{
								handler({origin: sourceDomain, data: JSON.stringify({action: 'sendMessage'}), event});
							}
						});
					}
					else
					{
						console.warn('Method BX.LiveChat.addEventListener is not supported, user new format for subscribe.')
					}
				},
				setCookie: () => {},
				getCookie: () => {},
				sourceDomain
			};
		}

		if (typeof window.BxLiveChatInit === 'function')
		{
			let config = window.BxLiveChatInit();

			if (config.user)
			{
				this.__privateMethods__.setUserRegisterData(config.user);
			}
			if (config.firstMessage)
			{
				this.__privateMethods__.setCustomData(config.firstMessage)
			}
		}

		if (window.BxLiveChatLoader instanceof Array)
		{
			window.BxLiveChatLoader.forEach(callback => callback());
		}

		return true;
	}
}