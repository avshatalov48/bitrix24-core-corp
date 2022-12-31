import {Const} from './const';
import {Type} from 'main.core';

export default class Slider
{
	static open(source = null, options = {})
	{
		let url = Const.url;
		if (Type.isStringFilled(source))
		{
			url += '?configCatalogSource=' + source;
		}
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
