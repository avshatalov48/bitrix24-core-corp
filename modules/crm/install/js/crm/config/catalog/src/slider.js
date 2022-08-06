import {Const} from './const';

export default class Slider
{
	static open(options = {})
	{
		return new Promise((resolve) =>
		{
			return BX.SidePanel.Instance.open(
				Const.url,
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
