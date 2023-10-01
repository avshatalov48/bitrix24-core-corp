'use strict';

/* Clean session variables after page restart */
if (typeof clearInterval === 'undefined')
{
	clearInterval = (id) => clearTimeout(id);
}

if (typeof ChatTimer !== 'undefined' && typeof ChatTimer.cleaner !== 'undefined')
{
	ChatTimer.cleaner();
}

var ChatTimer = {};

ChatTimer.init = function()
{
	this.list = {};

	this.updateInterval = 1000;

	clearInterval(this.updateIntervalId);
	this.updateIntervalId = setInterval(this.worker.bind(this), this.updateInterval);
};

ChatTimer.delete = function(elementId)
{
	delete this.list[elementId];
};

ChatTimer.start = function(type, id, time, callback, callbackParams)
{
	id = id === null ? 'default' : id;

	time = parseInt(time);
	if (time <= 0 || id.toString().length <= 0)
	{
		return false;
	}

	if (typeof this.list[type] === 'undefined')
	{
		this.list[type] = {};
	}

	this.list[type][id] = {
		dateStop: Date.now() + time,
		callback: typeof callback === 'function' ? callback : function() {},
		callbackParams: typeof callbackParams === 'undefined' ? {} : callbackParams,
	};

	return true;
};

ChatTimer.stop = function(type, id, skipCallback)
{
	id = id === null ? 'default' : id;

	if (id.toString().length <= 0 || typeof this.list[type] === 'undefined')
	{
		return false;
	}

	if (!this.list[type][id])
	{
		return true;
	}

	if (skipCallback !== true)
	{
		this.list[type][id].callback(id, this.list[type][id].callbackParams);
	}

	delete this.list[type][id];

	return true;
};

ChatTimer.stopAll = function(skipCallback)
{
	for (const type in this.list)
	{
		if (this.list.hasOwnProperty(type))
		{
			for (const id in this.list[type])
			{
				if (this.list[type].hasOwnProperty(id))
				{
					this.stop(type, id, skipCallback);
				}
			}
		}
	}

	return true;
};

ChatTimer.worker = function()
{
	for (const type in this.list)
	{
		if (!this.list.hasOwnProperty(type))
		{
			continue;
		}

		for (const id in this.list[type])
		{
			if (!this.list[type].hasOwnProperty(id) || this.list[type][id].dateStop > new Date())
			{
				continue;
			}
			this.stop(type, id);
		}
	}

	return true;
};

ChatTimer.cleaner = function()
{
	clearInterval(this.updateIntervalId);
	this.stopAll(true);

	console.warn('ChatTimer.cleaner: OK');

	return true;
};

/**
 * @desc Returns check is has live timer in list by type and id
 * @param {string} type
 * @param {string|number} id
 * @return (boolean}
 */
ChatTimer.isHasTimer = function(type, id)
{
	for (const typeTimer in this.list)
	{
		if (typeTimer === type)
		{
			for (const timerId in this.list[typeTimer])
			{
				if (timerId === id)
				{
					return true;
				}
			}
		}
	}

	return false;
};

ChatTimer.init();
