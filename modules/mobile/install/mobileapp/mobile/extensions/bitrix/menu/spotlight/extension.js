/**
 * @bxjs_lang_path extension.php
 */
(() =>
{
	class MenuSpotlightOptions
	{
		constructor()
		{
			this.seenSpotlights = [];

			const seenSpotlights = Application.storage.get('seen_spotlights', '');
			if (typeof seenSpotlights == 'string' && seenSpotlights.length)
			{
				this.seenSpotlights = seenSpotlights.split(',');
			}
		}
		wasSpotlightSeen(id)
		{
			return this.seenSpotlights.indexOf(id) > -1;
		}
		markSpotlightSeen(id)
		{
			if (!this.wasSpotlightSeen(id))
			{
				this.seenSpotlights.push(id);
			}
		}
		saveSeenSpotlights()
		{
			Application.storage.set('seen_spotlights', this.seenSpotlights.join(','));
		}
	}
	let menuSpotlightOptions = new MenuSpotlightOptions();

	class MenuSpotlight
	{
		constructor(items)
		{
			this.delayCount = 0;
			this.setItemsFromArray(items);
			this.bindEvents();
		}

		setItemsFromArray(items)
		{
			if (Array.isArray(items))
			{
				this.items = items.map(item => MenuSpotlightItem.createFromArray(item));
			}
		}

		bindEvents()
		{
			BX.addCustomEvent('onMenuLoaded', (menuLoaded) =>
			{
				this.initDelayCount();
				this.initItemsFromEventResult(menuLoaded);

				BX.addCustomEvent(
					'onMenuResultUpdated',
					(menuResultUpdated) =>
					{
						this.initItemsFromEventResult(menuResultUpdated);
					}
				);
			})
		}

		initItemsFromEventResult(result)
		{
			console.trace("initItemsFromEventResult");
			const spotlights = result && result.hasOwnProperty('spotlights') ? result.spotlights : [];
			this.setItemsFromArray(spotlights);
			this.tryToShowSpotlight();
		}

		initDelayCount()
		{
			this.delayCount = Application.storage.getNumber('start_count', 0);
			if (this.delayCount <= this.getMaxStartCount())
			{
				Application.storage.setNumber('start_count', ++this.delayCount);
			}
		}

		tryToShowSpotlight()
		{
			const candidates = this.items.filter(
				(spotlight) => ( // not seen before and has enough delayCount and api version
					!spotlight.isAlreadySeen()
					&& spotlight.getDelayCount() <= this.delayCount
					&& spotlight.getMinApiVersion() <= Application.getApiVersion()
				)
			);

			if (candidates.length)
			{
				const spotlight = candidates[0];
				setTimeout(() => {
					if (!PageManager.getNavigator().isVisible() || !PageManager.getNavigator().isActiveTab())
					{
						spotlight.markAsSeen();

						let spotlightApi = dialogs.createSpotlight();
						spotlightApi.setTarget(spotlight.getMenuId());
						let hint = {};
						if (spotlight.getText().length)
						{
							hint = {...hint, text: spotlight.getText()};
						}
						if (spotlight.getIcon().length)
						{
							hint = {...hint, icon: spotlight.getIcon()};
						}
						spotlightApi.setHint(hint);
						spotlightApi.show();
					}
				}, 100);
			}
		}

		getMaxStartCount()
		{
			return 20; // possibly will be enough
		}
	}

	class MenuSpotlightItem
	{
		constructor()
		{
			this.id = null;
			this.minApiVersion = 0;
			this.delayCount = 0;
			this.menuId = '';
			this.text = '';
			this.icon = '';
		}
		getId()
		{
			return this.id;
		}
		setId(id)
		{
			this.id = id;
		}
		getMinApiVersion()
		{
			return this.minApiVersion;
		}
		setMinApiVersion(minApiVersion)
		{
			this.minApiVersion = parseInt(minApiVersion);
		}
		getDelayCount()
		{
			return this.delayCount;
		}
		setDelayCount(delayCount)
		{
			this.delayCount = parseInt(delayCount);
		}
		getMenuId()
		{
			return this.menuId;
		}
		setMenuId(menuId)
		{
			this.menuId = '' + menuId;
		}
		getText()
		{
			return this.text;
		}
		setText(text)
		{
			this.text = '' + text;
		}
		getIcon()
		{
			return this.icon;
		}
		setIcon(icon)
		{
			this.icon = '' + icon;
		}
		isAlreadySeen()
		{
			const id = this.getId();
			if (!id)
			{
				return true;
			}
			if (
				id === 'stress'
				&& Application.storage.getBoolean('seen_stress_spotlight', false)
			)
			{
				return true;
			}
			return menuSpotlightOptions.wasSpotlightSeen(id);
		}
		markAsSeen()
		{
			const id = this.getId();
			if (!id)
			{
				return true;
			}
			menuSpotlightOptions.markSpotlightSeen(id);
			menuSpotlightOptions.saveSeenSpotlights();
		}

		static createFromArray(params)
		{
			const item = new MenuSpotlightItem();
			if (params.hasOwnProperty('id'))
			{
				item.setId(params.id);
			}
			if (params.hasOwnProperty('minApiVersion'))
			{
				item.setMinApiVersion(params.minApiVersion);
			}
			if (params.hasOwnProperty('delayCount'))
			{
				item.setDelayCount(params.delayCount);
			}
			if (params.hasOwnProperty('menuId'))
			{
				item.setMenuId(params.menuId);
			}
			if (params.hasOwnProperty('text'))
			{
				item.setText(params.text);
			}
			if (params.hasOwnProperty('icon'))
			{
				item.setIcon(params.icon);
			}

			return item;
		}
	}

	new MenuSpotlight(this && this.result ? this.result : null);

})();
