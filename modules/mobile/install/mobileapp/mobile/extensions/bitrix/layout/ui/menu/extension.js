/**
 * @module layout/ui/menu
 */
jn.define('layout/ui/menu', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Color } = require('tokens');
	const { Feature } = require('feature');
	const { qrauth } = require('qrauth/utils');
	const { mergeImmutable } = require('utils/object');
	const { MenuPosition } = require('layout/ui/menu/src/menu-position');

	const DEFAULT_MENU_SECTION_NAME = 'main';

	const airStyleSupported = Feature.isAirStyleSupported();

	let iconsPath = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/menu/icons/';

	if (airStyleSupported)
	{
		iconsPath += 'outline-';
	}

	/**
	 * @class UIMenuType
	 */
	const Types = {
		DESKTOP: 'desktop',
		HELPDESK: 'helpdesk',
	};

	/**
	 * @typedef {Object} UIMenuActionProps
	 * @property {string} id
	 * @property {string} testId
	 * @property {string} title
	 * @property {string} sectionCode
	 * @property {Function} onItemSelected
	 * @property {Icon} [icon]
	 * @property {Object} [counterValue]
	 * @property {boolean} [disable]
	 * @property {boolean} [checked]
	 * @property {string} [textColor]
	 * @property {boolean} [showTopSeparator]
	 *
	 * @class UIMenu
	 */
	class Menu
	{
		/**
		 * @param {Array<UIMenuActionProps>} actions
		 * @param options
		 */
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
			const popupConfig = this.getMenuConfig();
			this.popup.setData(...popupConfig);

			return this.popup;
		}

		/**
		 * @public
		 * @function show
		 * @params {object} options
		 * @params {MenuPosition} [options.position]
		 * @params {View|String} [options.target]
		 * @return void
		 */
		show(options = {})
		{
			const { target, position } = options;
			const popup = this.getPopup();

			if (target && typeof popup.setTarget === 'function')
			{
				popup.setTarget(target);
			}

			if (MenuPosition.has(position))
			{
				popup.setPosition(position.getValue());
			}

			popup.show();
		}

		hide()
		{
			this.getPopup().hide();
		}

		getMenuConfig()
		{
			const actions = this.getPreparedMenuActions();

			const items = [];
			const sectionMap = new Map();
			const actionsFlatMap = new Map();

			for (const action of actions.values())
			{
				this.prepareActions(action, items, sectionMap, actionsFlatMap);
			}

			const sections = [...sectionMap.values()];

			return [
				items,
				sections,
				(event, item) => {
					if (event === 'onItemSelected')
					{
						const action = actionsFlatMap.get(item.id);
						if (action?.callbacks?.onItemSelected)
						{
							action.callbacks.onItemSelected(event, item);
						}
					}
				},
			];
		}

		prepareActions(action, items, sectionMap, actionsFlatMap)
		{
			items.push(action.params);

			const sectionCode = action.params?.sectionCode ?? DEFAULT_MENU_SECTION_NAME;
			let section = sectionMap.get(sectionCode);
			if (!section)
			{
				section = {
					id: sectionCode,
					title: action.params?.sectionTitle ?? '',
				};
				sectionMap.set(sectionCode, section);
			}
			else if (action.params?.sectionTitle)
			{
				section.title = action.params.sectionTitle;
			}

			if (action.params?.nextMenu?.items)
			{
				for (const item of action.params.nextMenu.items)
				{
					actionsFlatMap.set(item.id, { params: item, callbacks: { onItemSelected: item.onItemSelected } });
					if (item.nextMenu?.items)
					{
						this.prepareActions({ params: item }, items, sectionMap, actionsFlatMap);
					}
				}
			}

			if (actionsFlatMap.has(action.params.id))
			{
				console.warn(`${action.params.id} already exists in actionsFlatMap so it was overwritten, check that all menu items have a unique ID`);
			}
			actionsFlatMap.set(action.params.id, action);
		}

		getActionConfigByType(type, showHint = true)
		{
			switch (type)
			{
				case Types.DESKTOP:
					return {
						id: 'desktop',
						title: BX.message('UI_MENU_ITEM_TYPE_DESKTOP_MSGVER_1'),
						iconUrl: `${iconsPath}desktop.png`,
						onItemSelected: (data) => () => {
							const promise = new Promise((resolve) => {
								const { qrUrl, qrUrlCallback } = data;
								if (qrUrl)
								{
									resolve(qrUrl);
								}
								else if (qrUrlCallback)
								{
									qrUrlCallback().then(resolve);
								}
							});

							promise.then((qrUrl) => {
								if (!qrUrl)
								{
									Alert.alert(
										BX.message('UI_MENU_ITEM_TYPE_QR_LINK_ERROR_TITLE'),
										BX.message('UI_MENU_ITEM_TYPE_QR_LINK_ERROR_TEXT'),
									);

									return;
								}

								qrauth.open({
									title: data.qrTitle || BX.message('UI_MENU_ITEM_TYPE_DESKTOP_MSGVER_1'),
									redirectUrl: qrUrl || '',
									showHint,
									analyticsSection: data.analyticsSection || '',
								});
							});
						},
					};

				case Types.HELPDESK:
					return {
						id: 'helpdesk',
						title: BX.message('UI_MENU_ITEM_TYPE_HELPDESK'),
						iconUrl: `${iconsPath}helpdesk.png`,
						onItemSelected: (data) => () => {
							helpdesk.openHelpArticle(data.articleCode, 'helpdesk');
						},
					};
			}

			throw new Error(`Not supported type ${type} of menu item in context menu.`);
		}

		getPreparedMenuActions()
		{
			const result = new Map();

			this.provider().forEach((action) => {
				if (action.type)
				{
					const showHint = (action.showHint === undefined ? true : action.showHint);
					action = {
						...this.getActionConfigByType(action.type, showHint),
						...action,
					};
					action.onItemSelected = action.onItemSelected(action.data);
				}

				const disable = BX.prop.getBoolean(action, 'disable', false);
				const isDestructive = BX.prop.getBoolean(action, 'isDestructive', false);
				const destructiveStyles = {
					title: {
						font: {
							color: Color.accentMainAlert.toHex(),
						},
					},
					icon: {
						color: Color.accentMainAlert.toHex(),
					},
				};

				const styles = mergeImmutable(
					isDestructive ? destructiveStyles : {},
					action.style ?? {},
				);

				result.set(action.id, {
					params: {
						id: action.id,
						testId: action.testId,
						title: action.title,
						iconUrl: this.getIconUrl(action),
						iconName: this.getIconName(action),
						showTopSeparator: action.showTopSeparator || false,
						checked: action.checked || false,
						showCheckedIcon: action.showCheckedIcon,
						sectionCode: action.sectionCode || DEFAULT_MENU_SECTION_NAME,
						sectionTitle: action.sectionTitle || '',
						disable,
						counterValue: action.counterValue || null,
						counterStyle: action.counterStyle || null,
						nextMenu: action.nextMenu || null,
						styles,
					},
					callbacks: {
						onItemSelected: action.onItemSelected,
					},
				});
			});

			return result;
		}

		/**
		 * @param {UIMenuActionProps} action
		 * @returns {string}
		 */
		getIconUrl(action)
		{
			const icon = action?.icon;

			if (Application.isBeta() && action?.iconUrl)
			{
				console.warn(`UIMenu: Please use the iconName parameter instead of <<${action?.iconUrl}>>.`);
			}

			return action?.iconUrl || icon?.getPath();
		}

		/**
		 * @param {UIMenuActionProps} action
		 * @returns {string}
		 */
		getIconName(action)
		{
			const icon = action?.icon;
			const iconName = action?.iconName;

			if (!iconName && !icon)
			{
				return null;
			}

			const isStringIcon = typeof iconName === 'string';

			if (Application.isBeta() && isStringIcon)
			{
				console.warn(`UIMenu: You are using an deprecated icon "<<${iconName}>>" type, you need to use enums "Icon.<name your icon>", example "cont { Icon } = require('assets/icons');`);
			}

			if (isStringIcon)
			{
				return iconName;
			}

			return icon?.getIconName?.() || iconName?.getIconName?.();
		}
	}

	module.exports = {
		UIMenu: Menu,
		UIMenuType: Types,
		UIMenuPosition: MenuPosition,
	};
});

(() => {
	const { UIMenu, UIMenuType } = jn.require('layout/ui/menu');

	this.UI = this.UI || {};
	this.UI.Menu = UIMenu;
	this.UI.Menu.Types = UIMenuType;
})();
