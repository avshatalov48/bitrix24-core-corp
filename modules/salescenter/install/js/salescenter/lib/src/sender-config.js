import { Manager } from "salescenter.manager";


export class SenderConfig
{
	static BITRIX24 = 'bitrix24';

	static needConfigure(sender)
	{
		if (!sender.isAvailable || sender.isConnected)
		{
			return false
		}
		return true
	}

	static openSliderFreeMessages(url)
	{
		return () => {
			if (typeof url === 'string')
			{
				return Manager.openSlider(url);
			}

			if (typeof url === 'object' && url !== null)
			{
				if (url.type === 'ui_helper')
				{
					return BX.loadExt('ui.info-helper').then(() =>
					{
						BX.UI.InfoHelper.show(url.value);
					});
				}
			}

			return Promise.resolve();
		};
	}
}