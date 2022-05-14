var ChatPerformance = {};

ChatPerformance.isModernDevice = function()
{
	if (Application.getPlatform() === "android")
	{
		return false;
	}

	if (!device || !device.model)
	{
		return true;
	}

	if (device.model.toLowerCase().startsWith('iphone'))
	{
		return  parseInt(device.model.toLowerCase().replace('iphone', '')) >= 9;
	}

	if (device.model.toLowerCase().startsWith('ipad'))
	{
		return  parseInt(device.model.toLowerCase().replace('ipad', '')) >= 6;
	}
};
ChatPerformance.isAutoPlayVideoSupported = function()
{
	return this.isModernDevice();
};

ChatPerformance.isGestureQuoteSupported = function()
{
	if (Application.getPlatform() === "android")
	{
		return false;
	}

	if (!device || !device.model)
	{
		return true;
	}

	if (device.model.toLowerCase().startsWith('iphone'))
	{
		return  parseInt(device.model.toLowerCase().replace('iphone', '')) >= 7;
	}

	if (device.model.toLowerCase().startsWith('ipad'))
	{
		return  parseInt(device.model.toLowerCase().replace('ipad', '')) >= 6;
	}
};

ChatPerformance.getDialogShowTimeout = function()
{
	let isFastRender = false;

	if (Application.getPlatform() === "android")
	{
		isFastRender = false;
	}
	else if (device && device.model)
	{
		if (device.model.toLowerCase().startsWith('iphone'))
		{
			isFastRender = parseInt(device.model.toLowerCase().replace('iphone', '')) >= 10;
		}
		else if (device.model.toLowerCase().startsWith('ipad'))
		{
			isFastRender = parseInt(device.model.toLowerCase().replace('ipad', '')) >= 6;
		}
	}

	return isFastRender? 0: 100;
};