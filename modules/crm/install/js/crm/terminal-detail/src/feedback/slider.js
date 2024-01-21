BX.namespace("BX.Crm");

import {Type, Uri} from "main.core";

export default class Slider
{
	static openFeedbackForm()
	{
		const url = new Uri('/bitrix/components/bitrix/crm.feedback/slider.php');
		url.setQueryParams({
			sender_page: 'terminal',
		});

		return Slider.open(url.toString(), {width: 735});
	}

	static open(url, options)
	{
		if(!Type.isPlainObject(options))
		{
			options = {};
		}
		options = {...{cacheable: false, allowChangeHistory: false, events: {}}, ...options};
		return new Promise((resolve) =>
		{
			if(Type.isString(url) && url.length > 1)
			{
				options.events.onClose = function(event)
				{
					resolve(event.getSlider());
				};
				BX.SidePanel.Instance.open(url, options);
			}
			else
			{
				resolve();
			}
		});
	}
}