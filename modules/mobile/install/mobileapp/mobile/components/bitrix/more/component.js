/**
 * @bxjs_lang_path component.php
 */
(function ()
{
	window.version = 1.0;
	let items = [];
	let sections = [];
	let SITE_ID = BX.componentParameters.get("SITE_ID", "s1");
	const { Color } = jn.require('tokens');

	const { debounce } = jn.require('utils/function');
	const { MoreTabNavigator } = jn.require('navigator/more-tab');
	const moreTabNavigator = new MoreTabNavigator();

	/**
	 * @let  BaseList menu
	 */

	let More = {
		updated: false,
		findIn: function (items, query)
		{
			query = query.toUpperCase();
			let searchResult = items.filter(item =>
			{
				let section = sections.find(section => section.id === item.sectionCode);
				if (item.title && item.title.toUpperCase().indexOf(
					query) >= 0 || section && section.title && section.title.toUpperCase().indexOf(query) >= 0)
				{
					if (!item.type || (item.type != "button" && item.type != "userinfo"))
					{
						return item;
					}
				}
			})
				.map(item =>
				{
					let section = sections.find(section => section.id === item.sectionCode);
					item.subtitle = section ? section.title : "";
					item.useLetterImage = true;
					return item;
				});

			return searchResult;
		},
		find: function (query)
		{
			let result = this.findIn(items, query);
			let groupItems = [];
			items.forEach(item =>
			{
				if (item.type === "group")
				{
					let section = sections.find(section => section.id === item.sectionCode);
					groupItems = groupItems.concat(this.findIn(item.params.items, query)
						.map(groupItem =>
							{
								groupItem.subtitle = section.title + " -> " + item.title;
								return groupItem;
							}
						));
				}
			});

			return result.concat(groupItems);
		},
		updateCounters(siteCounters)
		{
			const counters = Object.keys(siteCounters);

			const preparedItems = items
				.filter((item) => counters.includes(item?.params?.counter))
				.map((item) => {
					const counter = item.params.counter;
					this.currentCounters[counter] = siteCounters[counter];

					const isNewStyles = item.imageName && this.enableNewStyle();

					return {
						filter: { 'params.counter': counter },
						element: {
							messageCount: siteCounters[counter],
							styles: isNewStyles ? this.getItemStyles(this.showHighlighted(item, siteCounters[counter])) : item.styles,
						},
					};
				});

			if (preparedItems.length > 0)
			{
				// eslint-disable-next-line no-undef
				menu.updateItems(preparedItems);
				Application.setBadges({ more: this.getTotalCounter(siteCounters) });
			}
		},
		enableNewStyle()
		{
			return Application.getApiVersion() > 55;
		},
		initCache: function ()
		{
			let cachedResult = this.getCache();

			if (cachedResult)
			{
				this.handleResult(cachedResult);
			}
			else
			{
				this.handleResult(result);
			}

		},
		getCache: function ()
		{
			let result = null;
			try
			{
				result = JSON.parse(Application.sharedStorage().get("more"));
			}
			catch (e)
			{
				//do nothing
			}

			return result;
		},
		forceReload:function (){
			BX.rest.callMethod("mobile.component.customparams.set", {"name": "more", "clear": true})
				.then(() => reload())
		},
		updateMenu: function ()
		{
			BX.ajax({url: component.resultUrl, dataType: "json"})
				.then(result =>
				{
					if (result.menu)
					{
						BX.onCustomEvent("onMenuResultUpdated", [result]);
						More.updated = true;
						Application.sharedStorage().set("more", JSON.stringify(result));
						this.handleResult(result);
						this.redraw();
					}
				})
				.catch(e => console.error(e));
		},
		redraw()
		{
			BX.onViewLoaded(() => {
				this.drawPopupMenu();
				const cachedCounters = Application.sharedStorage().get('userCounters');

				try
				{
					const counters = cachedCounters ? JSON.parse(cachedCounters) : {};

					const preparedItems = this.prepareItemsBeforeRedraw(counters[SITE_ID]);
					const totalCounter = this.getTotalCounter(counters[SITE_ID]);
					Application.setBadges({ more: totalCounter });

					// eslint-disable-next-line no-undef
					menu.setItems(preparedItems, sections);
				}
				catch (e)
				{
					console.error(e);
				}
			});
		},
		getTotalCounter(counters = {})
		{
			return Object.keys(counters)
				.filter((counter) => this.counterList.includes(counter))
				.reduce((acc, counter) => acc + counters[counter], 0);
		},
		prepareItemsBeforeRedraw(counters = {})
		{
			return items.map((item) => {
				const isNewStyles = item.imageName && this.enableNewStyle();
				const counter = counters[item?.params?.counter];

				return {
					...item,
					messageCount: counters[item?.params?.counter],
					styles: isNewStyles ? this.getItemStyles(this.showHighlighted(item, counter)) : item.styles,
				};
			});
		},
		getItemStyles(showHighlighted = false)
		{
			return {
				title: {
					font: {
						useColor: true,
						color: showHighlighted ? Color.accentMainPrimary.toHex() : Color.base1.toHex(),
					},
				},
				image: {
					image: {
						tintColor: showHighlighted ? Color.accentMainPrimary.toHex() : Color.base0.toHex(),
						contentHeight: 24,
					},
					border: {
						color: showHighlighted ? Color.accentSoftBlue1.toHex() : Color.bgSeparatorPrimary.toHex(),
						width: 1,
					},
				},
			};
		},
		showHighlighted(item, counter)
		{
			if (item?.params?.highlightWithCounter)
			{
				return item?.params?.showHighlighted && Number.isInteger(counter) && counter > 0;
			}

			return item?.params?.showHighlighted;
		},
		drawPopupMenu()
		{
			const popupPoints = this.popupMenuItems;
			this.popup = dialogs.createPopupMenu();
			this.popup.setData(popupPoints, [{ id: 'menu', title: '' }], (event, item) => {
				if (event === 'onItemSelected')
				{
					const menuItem = this.popupMenuItems.find((menuItem) => menuItem.id === item.id);
					if (menuItem.onclick)
					{
						(function()
						{
							eval(menuItem.onclick);
						}).bind(item)();
					}
				}
			});

			const buttons = [];
			buttons.push(
				{ type: 'search', callback: () => menu.showSearchBar() },
				{ type: 'more', callback: () => this.popup.show() }
			);
			menu.setRightButtons(buttons);
		},
		handleResult(result)
		{
			const customCounters = {
				[env.siteId]: result.customCounters || {},
			};
			const menuStructure = result.menu;
			const counters = {};
			counters[String(env.siteId)] = {};
			if (result.counterList)
			{
				this.counterList = result.counterList;
			}

			if (result.popupMenuItems)
			{
				this.popupMenuItems = result.popupMenuItems;
			}

			Application.sharedStorage('menuComponentSettings').set('invite', JSON.stringify(result.invite));

			if (menuStructure)
			{
				items = [];
				sections = [];
				menuStructure
					.filter((section) => !(typeof section !== 'object'
							|| (typeof section.items === 'undefined')
							|| (section.min_api_version && section.min_api_version > Application.getApiVersion())
							|| section.hidden === true
					))
					.forEach(
						(section) => {
							const sectionCode = `section_${section.sort}`;
							let sectionItems = [];
							if (section.items)
							{
								sectionItems = section.items
									.filter(({ hidden }) => !hidden)
									.map((item) => {
										if (item.params && item.params.counter)
										{
											this.counterList.push(item.params.counter);
											if (typeof this.currentCounters[item.params.counter] !== 'undefined')
											{
												item.messageCount = this.currentCounters[item.params.counter];
											}
										}

										if (item.imageName)
										{
											if (this.enableNewStyle())
											{
												item.color = null;
												item.styles = this.getItemStyles(
													this.showHighlighted(item, this.currentCounters[item.params.counter]),
												);
											}
											else
											{
												item.imageName = null;
											}
										}

										return item;
									});
							}

							sections.push({
								title: section.title,
								// height: 36,
								styles: {
									title: { font: { size: 13, fontStyle: 'semibold' } },
								},

								id: sectionCode,
							});

							items = [...items, ...sectionItems];
						},
					);

				BX.postComponentEvent('onSetUserCounters', [customCounters], 'communication');
			}
		},
		init()
		{
			menu.setListener((eventName, data) => this.listener(eventName, data));
			items = items.filter((item) => item !== false).map((item) =>
			{
				if (item.type !== 'destruct')
				{
					item.styles = {
						title: {
							color: '#FF4E5665',
						},
					};
				}

				if (item.params.counter)
				{
					this.counterList.push(item.params.counter);
				}

				return item;
			});

			BX.addCustomEvent('onPullEvent-crm', (command) => {
				if (command === 'was_inited')
				{
					this.forceReload();
				}
			});
			BX.addCustomEvent('onPullEvent-main', (command, params) => {
				if (command === 'user_counter' && params[SITE_ID])
				{
					More.updateCounters(params[SITE_ID]);
				}
			});

			BX.addCustomEvent('onUpdateUserCounters', (data) => {
				if (data[SITE_ID])
				{
					setTimeout(() => {
						More.updateCounters(data[SITE_ID]);
					}, 100);
				}
			});

			BX.addCustomEvent('shouldReloadMenu', () => this.updateMenu());
			this.initCache();
			this.redraw();
			this.updateMenu();
		},
		listener(eventName, data)
		{
			let item = null;
			switch (eventName)
			{
				case 'onUserTypeText':
					if (data.text.length > 0)
					{
						menu.setSearchResultItems(More.find(data.text), []);
					}
					else
					{
						menu.setSearchResultItems([], []);
					}

					break;
				case 'onItemAction':
					item = data.item;
					if (item.params.actionOnclick)
					{
						eval(item.params.actionOnclick);
					}

					break;
				case 'onItemSelected':
				case 'onSearchItemSelected':
					item = data;
					if (item.type === 'group')
					{
						PageManager.openComponent('JSComponentSimpleList', {
							title: item.title,
							params: {
								items: item.params.items,
							},
						});
					}
					else if (item.params.onclick)
					{
						More.debouncedOnClick(item);
					}
					else if (item.params.action)
					{
						Application.exit();
					}
					else if (item.params.url)
					{
						if (item.params._type && item.params._type === 'list')
						{
							PageManager.openList(item.params);
						}
						else
						{
							let backdrop = undefined;
							if (typeof item.params.backdrop === 'object')
							{
								if (Object.keys(item.params.backdrop).length > 0)
								{
									backdrop = item.params.backdrop;
								}
								else
								{
									backdrop = {};
								}
							}

							PageManager.openPage({
								url: item.params.url,
								useSearchBar: item.params.useSearchBar,
								titleParams: { text: item.title, type: 'section' },
								cache: (item.params.cache !== false),
								backdrop,
							});
						}
					}

					break;
				case 'onRefresh':
					menu.stopRefreshing();
					this.updateMenu();

					break;
				default:
			}
		},
		debouncedOnClick: debounce((item) => {
			(() => {
				// eslint-disable-next-line no-eval
				eval(item.params.onclick);
			}).call(item);
		}, 50),
		counterList: [],
		currentCounters: {},
		getItemById(id)
		{
			return items.find((item) => item?.params?.id === id);
		},
		triggerItemOnClick(item)
		{
			if (item.params.onclick)
			{
				(function()
				{
					eval(item
						.params
						.onclick);
				}
				).call(item);
			}
		},
	};

	moreTabNavigator.unsubscribeFromPushNotifications();
	moreTabNavigator.subscribeToPushNotifications(More);
	More.init();
	// eslint-disable-next-line no-undef
	qrauth.listenUniversalLink();
	window.updateMenuItem = (id, data) => {
		menu.updateItems([
			{ filter: { id }, element: data },
		]);
	};

	BX.onCustomEvent('onMenuLoaded', [this.result]);
	/**
	 * @var {MobileIntent} mobileIntent
	 */
	const mobileIntent = jn.require('intent');
	mobileIntent.execute();
}).bind(this)();
