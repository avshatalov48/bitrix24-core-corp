/**
 * Bitrix OpenLines widget
 * Cookie manager
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {LocalStorage} from "im.tools.localstorage";

export const Cookie =
{
	get(siteId, name)
	{
		let cookieName = siteId? siteId+'_'+name: name;

		if (navigator.cookieEnabled)
		{
			let result = document.cookie.match(new RegExp(
				"(?:^|; )" + cookieName.replace(/([.$?*|{}()\[\]\\\/+^])/g, '\\$1') + "=([^;]*)"
			));

			if (result)
			{
				return decodeURIComponent(result[1]);
			}
		}

		if (LocalStorage.isEnabled())
		{
			let result = LocalStorage.get(siteId, 0, name, undefined);
			if (typeof result !== 'undefined')
			{
				return result;
			}
		}

		if (typeof window.BX.LiveChatCookie === 'undefined')
		{
			window.BX.LiveChatCookie = {};
		}

		return window.BX.LiveChatCookie[cookieName];
	},
	set(siteId, name, value, options)
	{
		options = options || {};

		let expires = options.expires;
		if (typeof(expires) == "number" && expires)
		{
			let currentDate = new Date();
			currentDate.setTime(currentDate.getTime() + expires * 1000);
			expires = options.expires = currentDate;
		}

		if (expires && expires.toUTCString)
		{
			options.expires = expires.toUTCString();
		}

		value = encodeURIComponent(value);

		let cookieName = siteId? siteId+'_'+name: name;
		let updatedCookie = cookieName + "=" + value;

		for (let propertyName in options)
		{
			if (!options.hasOwnProperty(propertyName))
			{
				continue;
			}
			updatedCookie += "; " + propertyName;

			let propertyValue = options[propertyName];
			if (propertyValue !== true)
			{
				updatedCookie += "=" + propertyValue;
			}
		}

		document.cookie = updatedCookie;

		if (typeof window.BX.LiveChatCookie === 'undefined')
		{
			BX.LiveChatCookie = {};
		}

		window.BX.LiveChatCookie[cookieName] = value;
		LocalStorage.set(siteId, 0, name, value);

		return true;
	}
};