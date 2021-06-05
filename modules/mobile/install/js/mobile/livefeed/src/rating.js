import {EventEmitter} from 'main.core.events';

class Rating extends EventEmitter
{
	constructor()
	{
		super();
		this.init();
	}

	init()
	{
		this.setEventNamespace('BX.Mobile.Livefeed');
		this.subscribe('onFeedInit', this.onFeedInit.bind(this));
	}

	onFeedInit()
	{
		if (!window.BXRL)
		{
			return;
		}

		Object.keys(window.BXRL).forEach((key) => {
			if (
				key !== 'manager'
				&& key !== 'render'
			)
			{
				delete window.BXRL[key];
			}
		});
	}
}

export {
	Rating
}