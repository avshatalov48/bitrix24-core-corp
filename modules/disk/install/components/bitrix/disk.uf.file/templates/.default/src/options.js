export default class Options
{
	static urlUpload = null;
	static documentHandlers = null;
	static previewSize = {width: 115, height: 115};

	static set(optionsToSet: Object): void
	{
		for (let optionName in optionsToSet)
		{
			if (Options.hasOwnProperty(optionName))
			{
				Options[optionName] = optionsToSet[optionName];
			}
		}
	}

	static getDocumentHandlers(): Array<Object>
	{
		if (!Options.documentHandlers)
		{
			return [];
		}
		return Options.documentHandlers;
	}

	static getDocumentHandler(code): Object
	{
		if (!Options.documentHandlers)
		{
			return {};
		}

		let handler = Options.documentHandlers.find(function (handler) {
			return handler.code === code;
		});

		return handler || {};
	}
}