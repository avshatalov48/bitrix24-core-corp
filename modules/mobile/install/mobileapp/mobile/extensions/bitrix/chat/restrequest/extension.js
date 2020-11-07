"use strict";

/* Clean session variables after page restart */
if (typeof ChatRestRequest != 'undefined' && typeof ChatRestRequest.cleaner != 'undefined')
{
	ChatRestRequest.cleaner();
}

var ChatRestRequest = { request: {} };

ChatRestRequest.register = function(name, xhr)
{
	this.request[name] = xhr;
	return true;
};

ChatRestRequest.unregister = function (name, abort)
{
	if (this.request[name])
	{
		if (abort)
		{
			this.request[name].abort();
		}
		delete this.request[name];
	}
};

ChatRestRequest.isActive = function(name)
{
	return !!this.request[name];
}

ChatRestRequest.get = function(name)
{
	return this.request[name]? this.request[name]: null;
};

ChatRestRequest.abort = function(name)
{
	if (this.request[name])
	{
		this.request[name].abort();
	}
	return true;
};

ChatRestRequest.cleaner = function()
{
	for (let name in this.request)
	{
		if (this.request.hasOwnProperty(name))
		{
			this.unregister(name, true);
		}
	}
	console.warn('ChatRestRequest.cleaner: OK');
};

