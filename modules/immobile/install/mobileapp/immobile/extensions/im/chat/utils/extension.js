"use strict";

/**
 * @module chat/utils
 */

var ChatUtils = {};



ChatUtils.isObjectChanged = function(currentProperties, newProperties)
{
	for (let name in newProperties)
	{
		if(!newProperties.hasOwnProperty(name))
		{
			continue;
		}

		if (typeof currentProperties[name] == 'undefined')
		{
			return true;
		}

		if (BX.type.isPlainObject(newProperties[name]))
		{
			if (!BX.type.isPlainObject(currentProperties[name]))
			{
				return true;
			}

			if (this.isObjectChanged(currentProperties[name], newProperties[name]) === true)
			{
				return true;
			}
		}
		else if (currentProperties[name] !== newProperties[name])
		{
			return true;
		}
	}

	return false;
};

ChatUtils.objectMerge = function(currentProperties, newProperties)
{
	for (let name in newProperties)
	{
		if(!newProperties.hasOwnProperty(name))
		{
			continue;
		}
		if (BX.type.isPlainObject(newProperties[name]))
		{
			if (!BX.type.isPlainObject(currentProperties[name]))
			{
				currentProperties[name] = {};
			}
			currentProperties[name] = this.objectMerge(currentProperties[name], newProperties[name]);
		}
		else
		{
			currentProperties[name] = newProperties[name];
		}
	}

	return currentProperties;
};

/**
 * @template T
 * @param {T} properties
 * @return {T}
 */
ChatUtils.objectClone = function(properties)
{
	let newProperties = {};
	if (properties === null)
		return null;

	if (typeof properties == 'object')
	{
		if (BX.type.isArray(properties))
		{
			newProperties = [];
			for (let i=0, l=properties.length; i<l; i++)
			{
				if (typeof properties[i] == "object")
				{
					newProperties[i] = this.objectClone(properties[i]);
				}
				else
				{
					newProperties[i] = properties[i];
				}
			}
		}
		else
		{
			newProperties =  {};
			if (properties.constructor)
			{
				if (BX.type.isDate(properties))
				{
					newProperties = new Date(properties);
				}
				else
				{
					newProperties = new properties.constructor();
				}
			}

			for (let i in properties)
			{
				if (!properties.hasOwnProperty(i))
				{
					continue;
				}
				if (typeof properties[i] == "object")
				{
					newProperties[i] = ChatUtils.objectClone(properties[i]);
				}
				else
				{
					newProperties[i] = properties[i];
				}
			}
		}
	}
	else
	{
		newProperties = properties;
	}

	return newProperties;
};

ChatUtils.getAvatar = function(url)
{
	if (!url || url.indexOf('/bitrix/js/im/images/blank.gif') >= 0)
	{
		return '';
	}

	url = url.indexOf('http') === 0? url: currentDomain+url;

	return encodeURI(url);
};

ChatUtils.getPathWithDomain = function(url)
{
	if (!url)
	{
		return '';
	}

	url = url.indexOf('http') === 0? url: currentDomain+url;

	return encodeURI(url);
};

ChatUtils.getTimestamp = function(atom)
{
	let date = atom? new Date(atom): new Date();
	return Math.round(date.getTime()/1000);
};

ChatUtils.htmlspecialcharsback = function(str)
{
	if(!str || !str.replace) return str;

	return str.replace(/\&quot;/g, '"').replace(/&#39;/g, "'").replace(/\&lt;/g, '<').replace(/\&gt;/g, '>').replace(/\&amp;/g, '&').replace(/\&nbsp;/g, ' ');
};

ChatUtils.throttle = function(callback, wait, context = this) {
	let timeout = null;
	let callbackArgs = null;

	const nextCallback = () => {
		callback.apply(context, callbackArgs);
		timeout = null;
	};

	return function() {
		if (!timeout)
		{
			callbackArgs = arguments;
			timeout = setTimeout(nextCallback, wait);
		}
	};
};

ChatUtils.debounce = function(callback, wait, context = this){
	let timeout = null;
	let callbackArgs = null;

	const nextCallback = () => {
		callback.apply(context, callbackArgs);
	};

	return function()
	{
		callbackArgs = arguments;

		clearTimeout(timeout);
		timeout = setTimeout(nextCallback, wait);
	}
};

ChatUtils.openVideoconf = function(alias)
{
	BXMobileApp.Events.postToComponent("CallEvents::openVideoConf", [alias], 'calls');
};

ChatUtils.getUuidv4 = function()
{
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
		return v.toString(16);
	});
};

ChatUtils.objectKeysToLowerCase = function(originalObject)
{
	if (typeof originalObject !== 'object' || originalObject === null)
	{
		return originalObject;
	}

	return Object.fromEntries(
		Object.entries(originalObject)
			.map(([key, value]) => {
				if (BX.type.isPlainObject(originalObject[key]))
				{
					if (key.indexOf('_') === -1)
					{
						return [key, ChatUtils.objectKeysToLowerCase(originalObject[key])];
					}

					return [key.toLowerCase(), ChatUtils.objectKeysToLowerCase(originalObject[key])];
				}
				if (key.indexOf('_') === -1)
				{
					return [key, value];
				}

				return [key.toLowerCase(), value];
			})
	);
}