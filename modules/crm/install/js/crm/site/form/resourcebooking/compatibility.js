(function () {


	if (!webPacker)
	{
		return;
	}


	if (!window.BX || !BX.message || !BX.Calendar.Loc)
	{
		return;
	}

	module.properties = module.properties || {};
	for(var code in module.properties)
	{
		if (!module.properties.hasOwnProperty(code))
		{
			continue;
		}

		BX.message[code] = module.properties[code];
	}

	webPacker.getModules().forEach(function (mod) {
		mod.messages = mod.messages || {};
		for(var code in mod.messages)
		{
			if (!mod.messages.hasOwnProperty(code))
			{
				continue;
			}

			var mess = mod.messages[code];
			if (typeof mess ==='undefined' || mess === '')
			{
				continue;
			}

			BX.message[code] = mess;
		}
	});


})();