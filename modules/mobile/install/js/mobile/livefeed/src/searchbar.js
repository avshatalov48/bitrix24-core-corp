import {Instance, PageInstance} from './feed';
import {Post} from './post';
import {Type, Runtime} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

class SearchBar extends EventEmitter
{
	constructor()
	{
		super();
		this.findTextMode = false;
		this.ftMinTokenSize = 3;
		this.hideByRefresh = false;
	}

	init(params)
	{
		if (BXMobileAppContext.getApiVersion() < Instance.getApiVersion('pageMenu'))
		{
			return;
		}

		if (Type.isPlainObject(params) && parseInt(params.ftMinTokenSize) > 0)
		{
			this.ftMinTokenSize = parseInt(params.ftMinTokenSize);
		}

		this.subscribe('onSearchBarCancelButtonClicked', this.searchBarEventCallback.bind(this));
		this.subscribe('onSearchBarSearchButtonClicked', this.searchBarEventCallback.bind(this));
		EventEmitter.subscribe('BX.MobileLivefeed.SearchBar::setHideByRefresh', this.setHideByRefresh.bind(this));
		EventEmitter.subscribe('BX.MobileLivefeed.SearchBar::unsetHideByRefresh', this.unsetHideByRefresh.bind(this));

		BXMobileApp.UI.Page.params.set({
			useSearchBar: true,
		});

		app.exec('setParamsSearchBar', {
			params: {
				callback: (event) => {
					if (
						event.eventName === 'onSearchButtonClicked'
						&& Type.isPlainObject(event.data)
						&& Type.isStringFilled(event.data.text)
					)
					{
						if (event.data.text.length >= this.ftMinTokenSize)
						{
							this.findTextMode = true;
						}

						this.emit('onSearchBarSearchButtonClicked', new BaseEvent({
							data: {
								text: event.data.text,
							},
						}));
					}
					else if ([ 'onCancelButtonClicked', 'onSearchHide' ].includes(event.eventName))
					{
						this.emit('onSearchBarCancelButtonClicked', new BaseEvent({
							data: {},
						}));
					}
				}
			}
		});
	}

	searchBarEventCallback(event)
	{
		const eventData = event.getData();
		const text = (Type.isPlainObject(eventData) && Type.isStringFilled(eventData.text) ? eventData.text : '');

		if (text.length >= this.ftMinTokenSize)
		{
			app.exec('showSearchBarProgress');
			this.emitRefreshEvent(text);
		}
		else
		{
			if (this.findTextMode)
			{
				if (!this.hideByRefresh)
				{
					EventEmitter.emit('BX.MobileLF:onSearchBarRefreshAbort');
				}

				if (BX.frameCache)
				{
					app.exec('hideSearchBarProgress');

					BX.frameCache.readCacheWithID('framecache-block-feed', (params) => {

						const container = document.getElementById('bxdynamic_feed_refresh');
						if (
							!Type.isArray(params.items)
							|| !container
						)
						{
							this.emitRefreshEvent();
							return;
						}

						const block = params.items.find(item => {
							return (
								Type.isStringFilled(item.ID)
								&& item.ID === 'framecache-block-feed'
							);
						});

						if (Type.isUndefined(block))
						{
							return;
						}

						Runtime.html(container, block.CONTENT).then(() => {
							BX.processHTML(block.CONTENT, true);
						});

						Post.moveTop();

						setTimeout(() => {
							BitrixMobile.LazyLoad.showImages();
						}, 1000);
					});
				}
				else
				{
					this.emitRefreshEvent();
				}
			}

			this.findTextMode = false;
		}
	}

	setHideByRefresh()
	{
		this.hideByRefresh = true;
	}

	unsetHideByRefresh()
	{
		this.hideByRefresh = false;
	}


	emitRefreshEvent(text)
	{
		if (PageInstance.refreshXhr)
		{
			return;
		}

		text = text || '';

		const event = new BaseEvent({
			compatData: [{
				text: text,
			}],
		});
		EventEmitter.emit('BX.MobileLF:onSearchBarRefreshStart', event);
	}
}

export {
	SearchBar
}