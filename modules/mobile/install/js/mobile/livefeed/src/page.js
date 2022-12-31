import {Type, Dom, Loc, Uri, ajax} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {Ajax} from 'mobile.ajax';

import {
	Instance,
	BalloonNotifierInstance,
	NextPageLoaderInstance,
	NotificationBarInstance,
	PinnedPanelInstance,
} from './feed';

class Page
{
	constructor()
	{
		this.isBusyGettingNextPage = false;
		this.isBusyRefreshing = false;

		this.pageNumber = 1;
		this.nextPageXhr = null;
		this.refreshXhr = null;

		this.nextUrl = '';

		this.requestErrorTimeout = {
			refresh: null,
			nextPage: null,
		};

		this.class = {
			notifier: 'lenta-notifier-waiter',
			notifierActive: 'lenta-notifier-shown',
		};

		this.onScroll = this.onScroll.bind(this);
		this.refreshErrorScroll = this.refreshErrorScroll.bind(this);
		this.nextPageErrorScroll = this.nextPageErrorScroll.bind(this);

		this.init();
	}

	init()
	{
		this.setPageNumber(1);
	}

	initScroll(enable, process_waiter)
	{
		enable = !!enable;
		process_waiter = !!process_waiter;

		if (enable)
		{
			document.removeEventListener('scroll', this.onScroll);
			document.addEventListener('scroll', this.onScroll);
		}
		else
		{
			document.removeEventListener('scroll', this.onScroll);
		}

		if (
			process_waiter
			&& document.getElementById('next_post_more')
		)
		{
			document.getElementById('next_post_more').style.display = (enable ? 'block' : 'none');
		}
	};

	onScroll()
	{
		const deviceMaxScroll = Instance.getMaxScroll();

		if (!(
			(
				window.pageYOffset >= deviceMaxScroll
				|| document.documentElement.scrollHeight <= window.innerHeight // when small workarea
			)
			&& (
				window.pageYOffset > 0 // refresh patch
				|| deviceMaxScroll > 0
			)
			&& !this.isBusyRefreshing
			&& !this.isBusyGettingNextPage
		))
		{
			return;
		}

		if (Instance.getOption('refreshFrameCacheNeeded', false) === true)
		{
			return;
		}

		document.removeEventListener('scroll', this.onScroll);

		this.isBusyGettingNextPage = true;

		this.nextPageXhr = Ajax.wrap({
			type: 'json',
			method: 'POST',
			url: this.getNextPageUrl(),
			data: {},
			callback: (data) => {
				this.nextPageXhr = null;

				if (
					Type.isPlainObject(data)
					&& Type.isPlainObject(data.PROPS)
					&& Type.isStringFilled(data.PROPS.CONTENT)
				)
				{
					if (
						Type.isUndefined(data.LAST_TS)
						|| parseInt(data.LAST_TS) <= 0
						|| parseInt(Loc.getMessage('MSLFirstPageLastTS')) <= 0
						|| parseInt(data.LAST_TS) < parseInt(Loc.getMessage('MSLFirstPageLastTS'))
						|| (
							parseInt(data.LAST_TS) === parseInt(Loc.getMessage('MSLFirstPageLastTS'))
							&& (
								parseInt(data.LAST_ID) <= 0
								|| parseInt(Loc.getMessage('MSLFirstPageLastId')) <= 0
								|| parseInt(data.LAST_ID) !== parseInt(Loc.getMessage('MSLFirstPageLastId'))
							)
						)
					)
					{
						this.processAjaxBlock(data.PROPS, {
							type: 'next',
							callback: () => {
								Instance.recalcMaxScroll();

								oMSL.registerBlocksToCheck();
								setTimeout(oMSL.checkNodesHeight.bind(oMSL), 100);

								EventEmitter.emit('BX.UserContentView.onRegisterViewAreaListCall', new BaseEvent({
									compatData: [{
										containerId: 'lenta_wrapper',
										className: 'post-item-contentview',
										fullContentClassName: 'post-item-full-content',
									}],
								}));
							}
						});

						let pageNumber = this.getPageNumber();

						if (
							parseInt(Loc.getMessage('MSLPageNavNum')) > 0
							&& pageNumber > 0
						)
						{
							this.setPageNumber(pageNumber + 1);
							let nextUrl = Uri.removeParam(this.getNextPageUrl(), ['PAGEN_' + Loc.getMessage('MSLPageNavNum')]);
							nextUrl = Uri.addParam(nextUrl, {
								[`PAGEN_${parseInt(Loc.getMessage('MSLPageNavNum'))}`]: (this.getPageNumber() + 1),
							});

							this.setNextPageUrl(nextUrl);
						}

						document.addEventListener('scroll', this.onScroll);
					}
				}
				else
				{
					this.requestError('nextPage', true);
				}

				this.isBusyGettingNextPage = false;
			},
			callback_failure: () => {
				this.requestError('nextPage', true);
				this.nextPageXhr = null;
				this.isBusyGettingNextPage = false;
			}
		});
	}

	refresh(bScroll, params)
	{
		bScroll = !!bScroll;

		if (
			this.isBusyGettingNextPage
			&& !Type.isNull(this.nextPageXhr)
		)
		{
			this.nextPageXhr.abort();
		}

		const notifier = document.getElementById('lenta_notifier');
		if (notifier)
		{
			notifier.classList.add(this.class.notifier);
		}

		Instance.setRefreshNeeded(false);
		Instance.setRefreshStarted(true);
		BalloonNotifierInstance.hideRefreshNeededNotifier();
		NextPageLoaderInstance.startWaiter();
		NotificationBarInstance.hideAll();

		this.isBusyRefreshing = true;

		let reloadUrl = Uri.removeParam(document.location.href, [ 'RELOAD', 'RELOAD_JSON', 'FIND' ]);
		reloadUrl = Uri.addParam(reloadUrl, {
			RELOAD: 'Y',
			RELOAD_JSON: 'Y',
		});

		if (
			Type.isPlainObject(params)
			&& Type.isStringFilled(params.find)
		)
		{
			reloadUrl = Uri.addParam(reloadUrl, {
				FIND: params.find,
			});
		}

		const headers = [
			{ name: 'BX-ACTION-TYPE', value: 'get_dynamic' },
			{ name: 'BX-REF', value: document.referrer },
			{ name: 'BX-CACHE-MODE', value: 'APPCACHE' },
			{ name: 'BX-APPCACHE-PARAMS', value: JSON.stringify(window.appCacheVars) },
			{ name: 'BX-APPCACHE-URL', value: (
				!Type.isUndefined(BX.frameCache)
				&& Type.isPlainObject(BX.frameCache.vars)
				&& Type.isStringFilled(BX.frameCache.vars.PAGE_URL)
					? BX.frameCache.vars.PAGE_URL
					: oMSL.curUrl
				) }
		];

		this.refreshXhr = Ajax.wrap({
			type: 'json',
			method: 'POST',
			url: reloadUrl,
			data: {},
			headers: headers,
			callback: (data) => {
				this.refreshXhr = null;
				this.setPageNumber(1);
				Instance.setRefreshStarted(false);
				Instance.setRefreshNeeded(false);
				NextPageLoaderInstance.stopWaiter();

				if (document.getElementById('lenta_notifier'))
				{
					document.getElementById('lenta_notifier').classList.remove(this.class.notifier);
				}

				app.exec('pullDownLoadingStop');
				app.exec('hideSearchBarProgress');

				if (
					Type.isPlainObject(data)
					&& Type.isPlainObject(data.PROPS)
					&& Type.isStringFilled(data.PROPS.CONTENT)
				)
				{
					this.setRefreshFrameCacheNeeded(false);

					BitrixMobile.LazyLoad.clearImages();
					app.hidePopupLoader();

					BalloonNotifierInstance.hideNotifier();
					BalloonNotifierInstance.hideRefreshNeededNotifier();

					if (!Type.isUndefined(data.COUNTER_TO_CLEAR))
					{
						BXMobileApp.onCustomEvent('onClearLFCounter', [ data.COUNTER_TO_CLEAR ], true);

						BXMobileApp.Events.postToComponent('onClearLiveFeedCounter', {
							counterCode: data.COUNTER_TO_CLEAR,
							serverTime: data.COUNTER_SERVER_TIME,
							serverTimeUnix: data.COUNTER_SERVER_TIME_UNIX,
						}, 'communication');
					}

					this.processAjaxBlock(data.PROPS, {
						type: 'refresh',
						callback: () => {
							PinnedPanelInstance.resetFlags();
							PinnedPanelInstance.init();

							if (
								!Type.isUndefined(BX.frameCache)
								&& document.getElementById('bxdynamic_feed_refresh')
								&& (
									Type.isUndefined(data.REWRITE_FRAMECACHE)
									|| data.REWRITE_FRAMECACHE !== 'N'
								)
							)
							{
								const serverTimestamp = (
									!Type.isUndefined(data.TS)
									&& parseInt(data.TS) > 0
										? parseInt(data.TS)
										: 0
								);

								if (serverTimestamp > 0)
								{
									Instance.setOptions({
										frameCacheTs: serverTimestamp,
									});
								}

								Instance.updateFrameCache({
									timestamp: serverTimestamp,
								});
							}

							oMSL.registerBlocksToCheck();

							//Android hack.
							//The processing of javascript and insertion of html works not so fast as expected
							setTimeout(() => {
								BitrixMobile.LazyLoad.showImages(); // when refresh
							}, 1000);

							BalloonNotifierInstance.initEvents();
						}
					});

					if (bScroll)
					{
						BitrixAnimation.animate({
							duration: 1000,
							start: {
								scroll: window.pageYOffset,
							},
							finish: {
								scroll: 0
							},
							transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
							step: (state) => {
								window.scrollTo(0, state.scroll);
							},
							complete: () => {}
						});
					}

					if (
						window.applicationCache
						&& data.isManifestUpdated == '1'
						&& !oMSL.appCacheDebug
						&& (
							window.applicationCache.status == window.applicationCache.IDLE
							|| window.applicationCache.status == window.applicationCache.UPDATEREADY
						)
					)//the manifest has been changed
					{
						window.applicationCache.update();
					}
				}
				else
				{
					this.requestError('refresh', true);
				}

				this.isBusyRefreshing = false;
			},
			callback_failure: () => {
				this.refreshXhr = null;

				Instance.setRefreshStarted(false);
				Instance.setRefreshNeeded(false);
				NextPageLoaderInstance.stopWaiter();

				if (document.getElementById('lenta_notifier'))
				{
					document.getElementById('lenta_notifier').classList.remove(this.class.notifier);
				}

				app.exec('pullDownLoadingStop');
				app.exec('hideSearchBarProgress');

				this.requestError('refresh', true);
				this.isBusyRefreshing = false;
			}
		});
	}

	processAjaxBlock(block, params)
	{
		if (
			!Type.isPlainObject(params)
			|| !Type.isStringFilled(params.type)
			|| ['refresh', 'next'].indexOf(params.type) < 0
		)
		{
			return;
		}

		let htmlWasInserted = false;
		let scriptsLoaded = false;

		processCSS(insertHTML);
		processExternalJS(processInlineJS);

		function processCSS(callback)
		{
			if (
				Type.isArray(block.CSS)
				&& block.CSS.length > 0
			)
			{
				BX.load(block.CSS, callback);
			}
			else
			{
				callback();
			}
		}

		function insertHTML()
		{
			if (params.type === 'refresh')
			{
				document.getElementById('lenta_wrapper_global').innerHTML = block.CONTENT;
			}
			else // next
			{
				document.getElementById('lenta_wrapper').insertBefore(
					Dom.create('div', {
						html: block.CONTENT
					}),
					document.getElementById('next_post_more')
				);
			}

			htmlWasInserted = true;
			if (scriptsLoaded)
			{
				processInlineJS();
			}
		}

		function processExternalJS(callback)
		{
			if (
				Type.isArray(block.JS)
				&& block.JS.length > 0
			)
			{
				BX.load(block.JS, callback); // to initialize
			}
			else
			{
				callback();
			}
		}

		function processInlineJS()
		{
			scriptsLoaded = true;

			if (htmlWasInserted)
			{
				ajax.processRequestData(block.CONTENT, {
					scriptsRunFirst: false,
					dataType: 'HTML',
					onsuccess: () => {
						if (Type.isFunction(params.callback))
						{
							params.callback();
						}
					}
				});
			}
		}
	}

	requestError(type, show)
	{
		if (!['refresh', 'nextPage'].includes(type))
		{
			type = 'refresh';
		}

		show = !!show;

		const errorBlock = document.getElementById(`lenta_${type.toLowerCase()}_error`);

		if (this.requestErrorTimeout[type])
		{
			clearTimeout(this.requestErrorTimeout[type]);
		}

		if (errorBlock)
		{
			if (show)
			{
				errorBlock.classList.add(this.class.notifierActive);
				if (type === 'refresh')
				{
					document.addEventListener('scroll', this.refreshErrorScroll);
				}
			}
			else
			{
				if (type === 'refresh')
				{
					document.removeEventListener('scroll', this.refreshErrorScroll);
				}
				errorBlock.classList.remove(this.class.notifierActive);
			}
		}
		else
		{
			this.requestErrorTimeout[type] = setTimeout(() => {
				this.requestError(type, show);
			}, 500);
		}

		if (type === 'nextPage')
		{
			this.initScroll(!show, true);
		}
	}

	refreshErrorScroll()
	{
		this.requestError('refresh', false);
	}

	nextPageErrorScroll()
	{
		this.requestError('nextPage', false);
	}

	setPageNumber(value)
	{
		this.pageNumber = parseInt(value);
	}

	getPageNumber()
	{
		return this.pageNumber;
	}

	setNextPageUrl(value)
	{
		this.nextUrl = value;
	}

	getNextPageUrl()
	{
		return this.nextUrl;
	}

	setRefreshFrameCacheNeeded(status)
	{
		Instance.setOptions({
			refreshFrameCacheNeeded: !!status
		});

		const refreshNeededNode = document.getElementById('next_page_refresh_needed');
		const nextPageCurtainNode = document.getElementById('next_post_more');

		if (
			refreshNeededNode
			&& nextPageCurtainNode
		)
		{
			refreshNeededNode.style.display = (!!status ? 'block' : 'none');
			nextPageCurtainNode.style.display = (!!status ? 'none' : 'block');
		}

		EventEmitter.emit('BX.UserContentView.onSetPreventNextPage', new BaseEvent({
			compatData: [ !!status ],
		}));
	}
}

export {
	Page
}