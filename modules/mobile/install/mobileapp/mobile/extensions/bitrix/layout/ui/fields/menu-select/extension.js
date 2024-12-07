/**
 * @module layout/ui/fields/menu-select
 */
jn.define('layout/ui/fields/menu-select', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BaseSelectField } = require('layout/ui/fields/base-select');
	const { chevronDown } = require('assets/common');
	const { ContextMenu } = require('layout/ui/context-menu');

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

			if (items.length === 0)
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
					tintColor: AppTheme.colors.base3,
					resizeMode: 'center',
					svg: {
						content: chevronDown(),
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

			if (this.props.emptyValue)
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
							content: '<svg width="7" height="5" viewBox="0 0 7 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.09722 0.235352L4.02232 2.31025L3.49959 2.8249L2.98676 2.31025L0.91186 0.235352L0.179688 0.967524L3.50451 4.29235L6.82933 0.967524L6.09722 0.235352Z" fill="#A8ADB4"/></svg>',
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
			const {
				menuTitle,
				defaultSectionCode,
				shouldResizeContent,
				isCustomIconColor,
				showCancelButton = false,
			} = this.getConfig();

			const contextMenu = new ContextMenu({
				params: {
					showCancelButton,
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

			return contextMenu.show(this.getParentWidget()).then(() => this.setListeners(contextMenu.layoutWidget));
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

		renderLeftIcons()
		{
			if (this.isEmptyEditable() && this.getConfig().emptyValueIcon)
			{
				return Image(
					{
						style: {
							width: 24,
							height: 24,
							marginRight: 8,
						},
						svg: {
							content: this.getConfig().emptyValueIcon,
						},
					},
				);
			}

			return null;
		}

		renderEditIcon()
		{
			if (this.props.editIcon)
			{
				return this.props.editIcon;
			}

			if (this.isEmptyEditable())
			{
				return View(
					{
						style: {
							justifyContent: 'center',
							alignItems: 'center',
							width: 16,
							height: 16,
							marginLeft: 2,
						},
					},
					Image(
						{
							style: {
								height: 5,
								width: 7,
							},
							svg: {
								content: chevronDown(this.getTitleColor()),
							},
						},
					),
				);
			}

			return null;
		}

		getDefaultStyles()
		{
			const styles = this.getChildFieldStyles();

			if (this.hasHiddenEmptyView())
			{
				return this.getHiddenEmptyChildFieldStyles(styles);
			}

			return styles;
		}

		getChildFieldStyles()
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
					color: AppTheme.colors.base1,
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

		getHiddenEmptyChildFieldStyles(styles)
		{
			const isEmptyEditable = this.isEmptyEditable();
			const hasErrorMessage = this.hasErrorMessage();
			const isEmpty = this.isEmpty();
			const paddingBottomWithoutError = (isEmpty ? 18 : 9);

			return {
				...styles,
				title: {
					...styles.title,
					marginBottom: (isEmptyEditable ? 0 : styles.title.marginBottom),
				},
				innerWrapper: {
					flex: (isEmptyEditable ? null : 1),
					flexShrink: 2,
				},
				container: {
					...styles.container,
					height: (isEmptyEditable ? 0 : null),
					width: (isEmptyEditable ? 0 : null),
				},
				wrapper: {
					...styles.wrapper,
					paddingTop: (isEmpty ? 12 : 8),
					paddingBottom: (hasErrorMessage ? 5 : paddingBottomWithoutError),
				},
			};
		}

		canCopyValue()
		{
			return true;
		}

		prepareValueToCopy()
		{
			return this.getSelectedItemTitle();
		}
	}

	const itemShape = PropTypes.shape({
		id: PropTypes.number,
		title: PropTypes.string,
		subtitle: PropTypes.string,
		isSelected: PropTypes.bool,
		isDisabled: PropTypes.bool,
		icon: PropTypes.string,
		img: PropTypes.string,
		sectionCode: PropTypes.string,
	});

	MenuSelectField.propTypes = {
		...BaseSelectField.propTypes,
		config: PropTypes.shape({
			showAll: PropTypes.bool, // show more button with count if it's multiple
			styles: PropTypes.shape({
				externalWrapperBorderColor: PropTypes.string,
				externalWrapperBorderColorFocused: PropTypes.string,
				externalWrapperBackgroundColor: PropTypes.string,
				externalWrapperMarginHorizontal: PropTypes.number,
			}),
			deepMergeStyles: PropTypes.object, // override styles
			parentWidget: PropTypes.object, // parent layout widget
			copyingOnLongClick: PropTypes.bool,
			titleIcon: PropTypes.object,

			items: PropTypes.oneOfType([
				PropTypes.arrayOf(itemShape),
				PropTypes.array,
			]),
			/**
			 * @deprecated - same as items, but with different prop name
			 */
			menuItems: PropTypes.oneOfType([
				PropTypes.arrayOf(itemShape),
				PropTypes.array,
			]),

			showIcon: PropTypes.bool,
			partiallyHidden: PropTypes.bool,
			emptyValueIcon: PropTypes.string, // svg

			// context menu props
			menuTitle: PropTypes.string,
			defaultSectionCode: PropTypes.string,
			shouldResizeContent: PropTypes.bool,
			isCustomIconColor: PropTypes.bool,
			showCancelButton: PropTypes.bool,
		}),
	};

	MenuSelectField.defaultProps = {
		...BaseSelectField.defaultProps,
		config: {
			...BaseSelectField.defaultProps.config,
			showIcon: false,
			partiallyHidden: false,
			defaultSectionCode: 'default',
		},
	};

	module.exports = {
		MenuSelectType: 'menu-select',
		MenuSelectFieldClass: MenuSelectField,
		MenuSelectField: (props) => new MenuSelectField(props),
	};
});
