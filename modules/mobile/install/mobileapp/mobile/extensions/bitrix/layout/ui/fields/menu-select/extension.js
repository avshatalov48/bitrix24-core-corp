/**
 * @module layout/ui/fields/menu-select
 */
jn.define('layout/ui/fields/menu-select', (require, exports, module) => {

	const { BaseSelectField } = require('layout/ui/fields/base-select');

	/**
	 * @class MenuSelectField
	 */
	class MenuSelectField extends BaseSelectField
	{
		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				menuTitle: BX.prop.getString(config, 'menuTitle', ''),
				items: this.getMenuItems(config),
				showIcon: BX.prop.getBoolean(config, 'showIcon', false),
				partiallyHidden: BX.prop.getBoolean(config, 'partiallyHidden', false),
				defaultSectionCode: BX.prop.getString(config, 'defaultSectionCode', 'default'),
				emptyValueIcon: BX.prop.getString(config, 'emptyValueIcon', ''),
			};
		}

		getMenuItems(config)
		{
			let items = BX.prop.getArray(config, 'menuItems', []);

			if (!items.length)
			{
				items = BX.prop.getArray(config, 'items', []);
			}

			return items;

		}

		shouldShowIcon()
		{
			return this.getConfig().showIcon;
		}

		shouldShowPartiallyHidden()
		{
			return this.getConfig().partiallyHidden;
		}

		getItemId(item)
		{
			return item.id;
		}

		getSelectedItem()
		{
			return this.getItems().find((item) => this.getItemId(item) === this.getValue());
		}

		getSelectedItemTitle()
		{
			const selectedItem = this.getSelectedItem();

			return selectedItem ? selectedItem.title : BX.message('FIELDS_SELECT_EMPTY_TEXT');
		}

		getSelectedItemIcon()
		{
			if (!this.shouldShowIcon())
			{
				return null;
			}

			const selectedItem = this.getSelectedItem();

			return (selectedItem ? selectedItem.icon : null);
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				(this.getSelectedItemIcon() && Image({
					style: this.styles.icon,
					svg: {
						content: this.getSelectedItemIcon(),
					},
				})),
				Text({
					style: this.styles.value,
					text: this.getSelectedItemTitle(),
				}),
			);
		}

		renderEditableContent()
		{
			if (this.isEmpty() && this.props.emptyValue)
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: this.styles.selectorWrapper,
				},
				(this.getSelectedItemIcon() && Image({
					style: this.styles.icon,
					svg: {
						content: this.getSelectedItemIcon(),
					},
				})),
				Text({
					style: this.styles.value,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getSelectedItemTitle(),
				}),
				Image({
					style: {
						width: 7,
						height: 5,
					},
					resizeMode: 'center',
					svg: {
						content: `<svg width="7" height="5" viewBox="0 0 7 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.09722 0.235352L4.02232 2.31025L3.49959 2.8249L2.98676 2.31025L0.91186 0.235352L0.179688 0.967524L3.50451 4.29235L6.82933 0.967524L6.09722 0.235352Z" fill="#A8ADB4"/></svg>`,
					},
				}),
			);
		}

		renderEmptyContent()
		{
			const config = this.getConfig();

			if (this.isReadOnly())
			{
				return View(
					{
						style: this.styles.selectorWrapper,
					},
					(config.emptyValueIcon && Image({
						style: this.styles.icon,
						svg: {
							content: config.emptyValueIcon,
						},
					})),
					super.renderEmptyContent(),
				);
			}
			else if (this.props.emptyValue)
			{
				return View(
					{
						style: this.styles.selectorWrapper,
					},
					(config.emptyValueIcon && Image({
						style: this.styles.icon,
						svg: {
							content: config.emptyValueIcon,
						},
					})),
					Text({
						style: this.styles.emptyValue,
						numberOfLines: 1,
						ellipsize: 'end',
						text: this.props.emptyValue,
					}),
					Image({
						style: {
							width: 7,
							height: 5,
						},
						resizeMode: 'center',
						svg: {
							content: `<svg width="7" height="5" viewBox="0 0 7 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.09722 0.235352L4.02232 2.31025L3.49959 2.8249L2.98676 2.31025L0.91186 0.235352L0.179688 0.967524L3.50451 4.29235L6.82933 0.967524L6.09722 0.235352Z" fill="#A8ADB4"/></svg>`,
						},
					}),
				);
			}

			return super.renderEmptyContent();
		}

		isSelected({ id })
		{
			const values = this.getValuesArray();

			return values.includes(id);
		}

		handleAdditionalFocusActions()
		{
			const { menuTitle, defaultSectionCode, shouldResizeContent, isCustomIconColor } = this.getConfig();

			const contextMenu = new ContextMenu({
				params: {
					showCancelButton: false,
					showActionLoader: false,
					title: menuTitle,
					showPartiallyHidden: this.shouldShowPartiallyHidden(),
					shouldResizeContent,
					isCustomIconColor,
				},
				actions: this.getItems().map((item) => ({
					id: String(item.id),
					title: String(item.title),
					subtitle: (item.subtitle ? String(item.subtitle) : ''),
					isSelected: this.isSelected(item),
					isDisabled: item.isDisabled,
					data: {
						svgIcon: item.icon,
						imgUri: item.img,
					},
					sectionCode: (item.sectionCode || defaultSectionCode),
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => this.handleChange(item.id, item.title));
						resolve({ closeMenu: false });
					}),
				})),
			});

			return contextMenu.show(this.getParentWidget()).then(
				() => this.setListeners(contextMenu.layoutWidget),
				() => {
				},
			);
		}

		setListeners(contextMenuWidget)
		{
			contextMenuWidget.setListener((eventName, data) => {
				const callbackName = `${eventName}Listener`;
				if (typeof this[callbackName] === 'function')
				{
					this[callbackName].apply(this, [data]);
				}
			});
		}

		onViewHiddenListener()
		{
			super.removeFocus();
		}

		onViewWillHiddenListener()
		{
			super.removeFocus();
		}

		onViewRemovedListener()
		{
			super.removeFocus();
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				selectorWrapper: {
					flexDirection: 'row',
					alignItems: 'center',
					height: 24,
				},
				emptyValue: {
					...styles.emptyValue,
					flex: undefined,
					marginRight: 4,
					marginLeft: (this.getConfig().emptyValueIcon ? 6 : undefined),
				},
				value: {
					color: '#333333',
					fontSize: 16,
					marginRight: 4,
					marginLeft: (this.getSelectedItemIcon() ? 6 : undefined),
				},
				icon: {
					width: 24,
					height: 24,
					alignSelf: 'center',
				},
			};
		}
	}

	module.exports = {
		MenuSelectType: 'menu-select',
		MenuSelectField: (props) => new MenuSelectField(props),
	};

});
