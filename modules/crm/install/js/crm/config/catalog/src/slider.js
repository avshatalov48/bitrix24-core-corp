import {Const} from './const';
import {Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

export default class Slider
{
	static open(source = null, options = {})
	{
		let url = Const.url;
		if (Type.isStringFilled(source))
		{
			url += '?configCatalogSource=' + source;
		}

		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {

			const [data] = event.getData();

			if (data.eventId === 'BX.Crm.Config.Catalog:onAfterSaveSettings')
			{
				EventEmitter.emit(window, 'onCatalogSettingsSave');
			}
		});

		return new Promise((resolve) =>
		{
			return BX.SidePanel.Instance.open(
				url,
				{
					width: 1000,
					allowChangeHistory: false,
					cacheable: false,
					...options,
				}
			);
		});
	}
}
