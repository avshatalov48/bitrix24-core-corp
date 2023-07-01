/**
 * @bxjs_lang_path component.php
 * @var {Notify} notify
 */

(() =>
{
	class PresetSettings
	{
		constructor(list, items = [])
		{
			this.list = list;
			this.items = items;
			this.sections = [
				{
					title: BX.message("PRESET_TITLE"), id: "presets", height: 40, styles: {
						title: {
							font: {size: 16, fontStyle:"medium"},
						}
					}
				},
				{title: " ", id: "manual", height: 40},
			];
			this.needShowManualPresetSettings =  BX.componentParameters.get('showManualPresetSettings', false);
			this.setSelected("default");
			/**
			 * @type {RequestExecutor} request
			 */
			let request = new RequestExecutor("mobile.tabs.getdata", {});
			request.setCacheId(PresetSettings.cacheId())
				.setCacheHandler(result => this.drawList(result))
				.setHandler(result => this.drawList(result))
				.call(true);


			BX.addCustomEvent("onPresetChanged", (presetName)=>{

				this.setSelected(presetName);
				this.redraw();
			});

			this.list.setListener((event, item) =>
			{
				if (event === "onRefresh")
				{
					reload();
				}

				if (event === "onItemSelected")
				{
					if (item.id === "manual")
					{
						this.showManualSettings();
						return;
					}

					if (!item.params.selected)
					{
						let currentPreset = this.items.find(item => item.params.selected);
						this.setSelected(item.id);
						this.redraw();

						Notify.showIndicatorLoading();
						(new RequestExecutor("mobile.tabs.setpreset", {name: item.id}))
							.setHandler((result, more, error) =>
							{
								if (result != null)
								{
									PresetSettings.changeCurrentPreset(item.id);
									ifApi(29, () => {
										Notify.showIndicatorSuccess({hideAfter: 1000});
											setTimeout(() => Application.relogin(), 1500);
										}).else(() => {
											    Application.auth(()=>{
													Notify.hideCurrentIndicator();
													navigator.notification.alert("Restart App Please", () =>
													{
													}, "", 'OK');
												});

											}
										);
								}
								else
								{
									Notify.showIndicatorError({
										hideAfter: 2000,
										text: BX.message("PRESET_APPLY_ERROR"),
										fallbackText: BX.message("PRESET_APPLY_ERROR")
									});
									this.setSelected(currentPreset.id);
									this.redraw();
								}
							})
							.call(false);

					}
				}
			})
		}

		drawList(data)
		{
			if (data.presets)
			{
				this.items = Object.keys(data.presets.list).map(id =>
				{
					return {
						id: id,
						type: id === "manual" ? undefined : "info",
						sectionCode: id === "manual" ? "manual" : "presets",
						title: BX.message("PRESET_NAME_" + id.toLocaleUpperCase()),
						params: {selected: false}
					};
				});
				this.setSelected(data.presets.current);
				this.redraw();

				if (this.needShowManualPresetSettings)
				{
					this.needShowManualPresetSettings = false;
					this.showManualSettings();
				}
			}
		}

		setSelected(id)
		{
			this.items = this.items.map(item =>
			{
				if (item.id === id)
				{
					item.params = {selected: true};
					item.imageUrl = `${availableComponents["tab.settings"].path}images/check.png`;
				}
				else
				{
					item.params = {selected: false};
					item.imageUrl = `${availableComponents["tab.settings"].path}images/uncheck.png`;
				}

				return item;
			});
		}

		redraw()
		{
			BX.onViewLoaded(()=>this.list.setItems(this.items, this.sections));
		}

		showManualSettings()
		{
			const manualPresetItem = this.items.filter(item => (item.id === 'manual'));
			if (manualPresetItem.length)
			{
				PageManager.openWidget(
					"list",
					{
						title: manualPresetItem[0].title,
						onReady: obj => new ManualSettings(obj),
						onError: error => console.log(error),
					});
			}
		}

		static cacheId()
		{
			return "tab.settings.user." + env.userId;
		}

		static changeCurrentPreset(presetName = "")
		{
			Application.storage.updateObject(PresetSettings.cacheId(), {}, saved =>
			{
				if (saved["presets"])
				{
					saved["presets"]["current"] = presetName;
				}

				return saved;
			});

			BX.onCustomEvent("onPresetChanged", [presetName]);
		}
	}

	class ManualSettings
	{
		/**
		 * @typedef {BaseList} list
		 */
		constructor(list)
		{
			this.list = list;
			this.sections = [
				{
					title: BX.message("SETTINGS_TAB_ACTIVE_TITLE"), id: "active", backgroundColor: "#ffffff", height: 50,
					sortItemParams:{sort:"asc"},
					styles: {
						title: {
							font: {size: 16, fontStyle:"medium"},
						}
					}
				},
				{
					title: BX.message("SETTINGS_TAB_INACTIVE_TITLE"), id: "nonactive", backgroundColor: "#ffffff", height: 50, styles: {
						title: {
							font: {size: 16, fontStyle:"medium"},
						}
					}
				},
			];
			this.list = list;
			this.list.setListener((event, item) => this.eventHandler(event, item));
			this.list.setRightButtons([{name: BX.message("SETTINGS_TAB_BUTTON_DONE"), callback: ()=>this.save()}]);
			new RequestExecutor("mobile.tabs.getdata", {})
				.setCacheId("tab.settings.user." + env.userId)
				.setCacheHandler(result => this.drawList(result))
				.setHandler(result => this.drawList(result))
				.call(true);
		}

		save()
		{
			let activeTabs = this.items
				.filter(item=>item.sectionCode === "active")
				.reduce((result, item) => {
					result[item.id] = item.sortValues.sort;
					return result
				}, {});
			Notify.showIndicatorLoading();

			new RequestExecutor("mobile.tabs.setconfig", {config: activeTabs})
				.setHandler(result => {

					PresetSettings.changeCurrentPreset("manual");

					ifApi(29, () => {
						Notify.showIndicatorSuccess({hideAfter: 1000, text: BX.message("SETTINGS_TAB_APPLIED")});
						setTimeout(() => Application.relogin(), 1500);
					}).else(() => {
						Application.auth(()=>{
							Notify.hideCurrentIndicator();
							navigator.notification.alert("Restart App Please", () =>
							{
							}, "", 'OK');
						});
						}
					);

				})
				.call(false);
		}

		/**
		 *
		 * @param data
		 */
		drawList(data)
		{
			if (data["tabs"]["current"])
			{
				let activeTabsKeys = Object.keys(data["tabs"]["current"]);

				this.items = activeTabsKeys
					.map(tabId =>
					{
						let tab = data["tabs"]["current"][tabId];
						let index = activeTabsKeys.indexOf(tabId);
						return {
							title: tab["title"],
							id: tabId,
							imageUrl: `${availableComponents["tab.settings"].path}images/tabs/${tabId}.png`,
							sectionCode: "active",
							useLetterImage: true,
							styles:{title:{color: tab["canChangeSort"] === false ? "#959ca4": "#000000" }},
							unselectable: tab["canChangeSort"] === false ,
							params:{"remove": tab["canBeRemoved"]},
							type: "info",
							sortValues: {sort: tab["canChangeSort"] === false ? tab["sort"]: index}
						};
					})
					.map(ManualSettings.setAction)
				;
				let allTabs = data["tabs"]["list"];
				let inactiveItems = Object.keys(allTabs)
					.filter(tabId => !activeTabsKeys.includes(tabId))
					.map(tabId =>
					{
						return {
							title: allTabs[tabId]["title"],
							id: tabId,
							imageUrl: `${availableComponents["tab.settings"].path}images/tabs/${tabId}.png`,
							sectionCode: "nonactive",
							useLetterImage: true,
							unselectable: false,
							styles:{title:{color:"#000000"}},
							params:{"remove": true},
							type: "info",
							sortValues:{sort:-1},
						};
					}).map(ManualSettings.setAction);

				this.items = this.items.concat(inactiveItems);
				this.list.setItems(this.items, this.sections);
			}
		}

		static setAction(item)
		{
			if (item.sectionCode === "active" && item.params["remove"] === true)
			{
				item.actions = [
					{title: BX.message("SETTINGS_TAB_MAKE_INACTIVE"), color: "#5e5e5e", identifier: "remove"}
				];
			}
			else
			{
				item.actions = [];
			}

			return item;
		}

		eventHandler(event, item)
		{
			let active = this.items.filter(item => item.sectionCode === "active");
			let nonactive = this.items.filter(item => item.sectionCode === "nonactive");

			if ((event === "onItemSelected" || event === "onItemChanged"))
			{
				if(item.unselectable === true)
				{
					dialogs.showSnackbar({
						title:BX.message("SETTINGS_TAB_CANT_MOVE").replace("#title#", item.title),
						id:"cantmove",
						backgroundColor:"#AA333333",
						textColor:"#ffffff",
						hideOnTap:true,
						autoHide:true}, ()=>{});
					return;
				}
				if (item.sectionCode === "active")
				{
					let selectedItem = active.find(activeItem => item.id === activeItem.id);
					let index = active.indexOf(selectedItem);
					let replacedIndex = index > 0 ? index - 1 : index + 1;
					let replacedItem = active[replacedIndex];

					if(replacedItem.unselectable)
						return;
					let selectedItemsSortValues = Object.assign({}, selectedItem.sortValues);
					selectedItem.sortValues = Object.assign({}, replacedItem.sortValues);
					replacedItem.sortValues = selectedItemsSortValues;
					active.sort((item1, item2) => item1.sortValues.sort - item2.sortValues.sort);
					this.items = active.concat(nonactive).map(ManualSettings.setAction);
					this.list.updateItems([
						{filter: {id: selectedItem.id}, element: selectedItem},
						{filter: {id: replacedItem.id}, element: replacedItem}
					]);
				}
				else
				{
					let selectedItem = nonactive.find(activeItem => item.id === activeItem.id);
					let index = nonactive.indexOf(selectedItem);
					if (index >= 0)
					{
						let lastElement = active.length - 1;

						while(lastElement >= 0)
						{
							let lastItem = active[lastElement];
							let isMoveable = (typeof lastItem["unselectable"] == "undefined" || lastItem["unselectable"] === false);
							let isRemoveable = lastItem.params.remove === true;

							if(isMoveable && isRemoveable)
							{
								break;
							}

							lastElement--;
						}

						nonactive[index].sectionCode = "active";
						if (active.length >= 5)
						{
							if(lastElement >=0)
							{
								active[lastElement].sectionCode = "nonactive";
								nonactive[index].sortValues = active[lastElement].sortValues;
								active[lastElement].sortValues = {sort:-1};
							}

						}
						else
						{
							if(lastElement>=0)
							{
								nonactive[index].sortValues = {sort:active[lastElement].sortValues.sort + 1};
							}
						}

						this.items = active.concat(nonactive).map(ManualSettings.setAction);
						this.list.setItems(this.items, null, false);

					}
				}

				this.items.sort((item1, item2) => item1.sortValues.sort - item2.sortValues.sort);
			}
			else if (event === "onItemAction")
			{
				let selectedItem = active.find(activeItem => item.item.id === activeItem.id);
				let index = active.indexOf(selectedItem);

				if (index >= 0)
				{
					selectedItem.sectionCode = "nonactive";
					active.splice(index, 1);
					nonactive.unshift(selectedItem);
					this.items = active.concat(nonactive).map(ManualSettings.setAction);
					this.list.setItems(this.items, null, true);
				}
			}
			else if (event === "onRefresh")
			{
				new RequestExecutor("mobile.tabs.getdata", {})
					.setCacheId("tab.settings.user." + env.userId)
					.setCacheHandler(result => this.drawList(result))
					.setHandler(result =>
					{
						setTimeout(()=>this.drawList(result), 100);
						setTimeout(()=>this.list.stopRefreshing(), 400);
					})
					.call(true);
			}
		}
	}

	/**
	 * @var {BaseList} list
	 */
	list.stopRefreshing();
	(new PresetSettings(list));

})();