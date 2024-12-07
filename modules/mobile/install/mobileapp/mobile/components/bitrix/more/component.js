/**
 * @bxjs_lang_path component.php
 */
(function ()
{
	window.version = 1.0;
	let items = [];
	let sections = [];
	let SITE_ID = BX.componentParameters.get("SITE_ID", "s1");

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
		updateCounters: function (siteCounters)
		{
			let counters = Object.keys(siteCounters);
			let totalCount = 0;
			let updateCountersData = counters.filter(counter => this.counterList.includes(counter))
				.map(counter =>
				{
					this.currentCounters[counter] = siteCounters[counter];
					return {filter: {"params.counter": counter}, element: {messageCount: siteCounters[counter]}}
				});

			if (updateCountersData.length > 0)
			{
				menu.updateItems(updateCountersData);
				Object.values(this.currentCounters).forEach(count => totalCount += count);
				Application.setBadges({more: totalCount});
			}
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
						setTimeout(()=>this.redraw(), 100);
					}
				})
				.catch(e => console.error(e));
		},
		redraw: function ()
		{
			BX.onViewLoaded(()=>
			{
				this.drawPopupMenu();
				menu.setItems(items, sections);
				setTimeout(() =>
				{
					let cachedCounters = Application.sharedStorage().get('userCounters');
					if (cachedCounters)
					{
						try
						{
							let counters = JSON.parse(cachedCounters);
							if (counters[SITE_ID])
							{
								this.updateCounters(counters[SITE_ID]);
							}

						}
						catch (e)
						{
							//do nothing
						}
					}

				}, 0);
			});
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
			const menuStructure = result.menu;
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
							|| section.hidden == true
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
					this.updateCounters(params[SITE_ID]);
				}
			});

			BX.addCustomEvent('onUpdateUserCounters', (data) => {
				if (data[SITE_ID])
				{
					this.updateCounters(data[SITE_ID]);
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
						(function()
						{
							eval(item.params.onclick);
						}).call(item);
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
