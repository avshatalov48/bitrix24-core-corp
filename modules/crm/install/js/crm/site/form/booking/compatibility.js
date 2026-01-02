(function() {
	if (!webPacker || !window.BX || !BX.message)
	{
		return;
	}

	module.properties = module.properties || {};
	for(const code in module.properties)
	{
		BX.message[code] = module.properties[code];
	}

	webPacker.getModules().forEach((mod) => {
		mod.messages = mod.messages || {};
		for (const code in mod.messages)
		{
			const mess = mod.messages[code];
			if (typeof mess === 'undefined' || mess === '')
			{
				continue;
			}

			BX.message[code] = mess;
		}
	});
})();
