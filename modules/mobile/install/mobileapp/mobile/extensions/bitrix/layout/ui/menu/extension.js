(() => {
	const DEFAULT_MENU_SECTION_NAME = 'main';

	const Types = {
		DESKTOP: 'desktop',
		HELPDESK: 'helpdesk'
	};

	/**
	 * @class UI.Menu
	 */
	class Menu
	{
		constructor(actions, options = {})
		{
			this.popup = null;
			this.provider = null;

			if (Array.isArray(actions))
			{
				this.provider = () => actions;
			}
			else if (typeof actions === 'function')
			{
				this.provider = actions;
			}

			if (!this.provider)
			{
				throw new TypeError('Incorrect type of actions');
			}
		}

		getPopup()
		{
			if (!this.popup)
			{
				this.popup = dialogs.createPopupMenu();
			}

			// @todo optional items recalculation?
			this.popup.setData(...this.getMenuConfig());

			return this.popup;
		}

		show()
		{
			this.getPopup().show();
		}

		getUniqueSections(itemSections)
		{
			return (
				itemSections
					.filter((value, index, arr) => arr.indexOf(value) === index)
					.map((id) => {
						return {id, title: ''};
					})
			);
		}

		getMenuConfig()
		{
			const actions = this.getPreparedMenuActions();

			const items = [];
			const itemSections = [];

			for (let action of actions.values())
			{
				items.push(action.params);
				itemSections.push(action.params.sectionCode);
			}

			const sections = this.getUniqueSections(itemSections);

			return [
				items,
				sections,
				(event, item) => {
					if (event === 'onItemSelected')
					{
						const onItemSelected = actions.get(item.id).callbacks.onItemSelected;
						onItemSelected(event, item);
					}
				}
			];
		}

		getActionConfigByType(type, showHint = true)
		{
			const iconsPath = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/menu/icons/';

			switch (type)
			{
				case Types.DESKTOP:
					return {
						id: 'desktop',
						title: BX.message('UI_MENU_ITEM_TYPE_DESKTOP'),
						iconUrl: iconsPath + 'desktop.png',
						onItemSelected: (data) => () => {
							qrauth.open({
								title: data.qrTitle || BX.message('UI_MENU_ITEM_TYPE_DESKTOP'),
								redirectUrl: data.qrUrl || '',
								showHint: showHint,
							});
						}
					};

				case Types.HELPDESK:
					return {
						id: 'helpdesk',
						title: BX.message('UI_MENU_ITEM_TYPE_HELPDESK'),
						iconUrl: iconsPath + 'helpdesk.png',
						onItemSelected: (data) => () => {
							helpdesk.openHelpArticle(data.articleCode, 'helpdesk');
						}
					};
			}

			throw new Error(`Not supported type ${type} of menu item in context menu.`)
		}

		getPreparedMenuActions()
		{
			const result = new Map();

			this.provider().forEach((action) => {
				if (action.type)
				{
					const showHint = (action.showHint !== undefined ? action.showHint : true);
					action = {
						...this.getActionConfigByType(action.type, showHint),
						...action
					};
					action.onItemSelected = action.onItemSelected(action.data);
				}

				result.set(action.id, {
					params: {
						id: action.id,
						title: action.title,
						iconUrl: action.iconUrl || '',
						showTopSeparator: action.showTopSeparator || false,
						sectionCode: action.sectionCode || DEFAULT_MENU_SECTION_NAME
					},
					callbacks: {
						onItemSelected: action.onItemSelected
					}
				});
			});

			return result;
		}
	}

	this.UI = this.UI || {};
	this.UI.Menu = Menu;
	this.UI.Menu.Types = Types;
})();
