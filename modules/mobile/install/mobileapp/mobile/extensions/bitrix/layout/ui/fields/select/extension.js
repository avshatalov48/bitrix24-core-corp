/**
 * @module layout/ui/fields/select
 */
jn.define('layout/ui/fields/select', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { chevronDown } = require('assets/common');
	const { BaseSelectField } = require('layout/ui/fields/base-select');
	const { stringify } = require('utils/string');
	const { isEqual } = require('utils/object');
	const { search } = require('utils/search');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { UIMenu } = require('layout/ui/menu');

	const Type = {
		Picker: 'picker',
		Selector: 'selector',
		ContextMenu: 'contextMenu',
		PopupMenu: 'popupMenu',
	};

	const SELECTOR_SECTION_ID = 'all';

	const pathToIcons = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/select/icons/';
	const DEFAULT_SELECTED_ICON = `${currentDomain}${pathToIcons}selected.png`;

	/**
	 * @class SelectField
	 */
	class SelectField extends BaseSelectField
	{
		constructor(props)
		{
			super(props);

			this.preparedItems = null;

			this.selector = null;
			this.selectorSelectedItems = [];
		}

		componentWillReceiveProps(nextProps)
		{
			super.componentWillReceiveProps(nextProps);

			this.preparedItems = null;
		}

		useHapticOnChange()
		{
			return true;
		}

		prepareValue(value)
		{
			value = super.prepareValue(value);

			if (this.isMultiple())
			{
				return value.filter((item) => item !== '');
			}

			return value;
		}

		prepareSingleValue(value)
		{
			if (Array.isArray(value))
			{
				value = value[0];
			}

			return stringify(value);
		}

		isEmpty()
		{
			return this.getSelectedItems().length === 0;
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				mode: this.prepareMode(config),
				items: BX.prop.getArray(config, 'items', []),
				defaultListTitle: BX.prop.getString(config, 'defaultListTitle', this.props.title || ''),
				selectShowImages: BX.prop.getBoolean(config, 'selectShowImages', true),
				isSearchEnabled: BX.prop.getBoolean(config, 'isSearchEnabled', true),
			};
		}

		prepareMode(config)
		{
			if (this.isMultiple())
			{
				return Type.Selector;
			}

			let mode = BX.prop.getString(config, 'mode', Type.Picker);

			if (!Object.values(Type).includes(mode))
			{
				mode = Type.Picker;
			}

			return mode;
		}

		prepareItems(items)
		{
			items = items.filter((item) => item.value !== '');

			if (
				(this.isPicker() || this.isContextMenu())
				&& !this.shouldPreselectFirstItem()
			)
			{
				items = [
					{
						value: '',
						name: BX.message('FIELDS_SELECT_EMPTY_TEXT'),
					},
					...items,
				];
			}

			return items.map((item) => {
				return {
					value: String(item.value),
					name: String(item.name),
					selectedName: String(item.selectedName || item.name || item.value),
				};
			});
		}

		getItems()
		{
			if (this.preparedItems === null)
			{
				this.preparedItems = this.prepareItems(super.getItems());
			}

			return this.preparedItems;
		}

		getSelectedItemsText()
		{
			let selectedText = (
				this.getSelectedItems()
					.map((item) => item.selectedName || item.name || item.value)
					.join(', ')
			);

			if (selectedText === '')
			{
				selectedText = BX.message('FIELDS_SELECT_CHOOSE');
			}

			return selectedText;
		}

		getValueStyle()
		{
			return this.styles.value || { color: AppTheme.colors.base1 };
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					onLongClick: this.getContentLongClickHandler(),
				},
				Text({
					style: this.getValueStyle(),
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getSelectedItemsText(),
				}),
			);
		}

		renderEditableContent()
		{
			const selectShowImage = this.getConfig().selectShowImages;
			if (this.isEmptyEditable())
			{
				return null;
			}

			return View(
				{
					style: this.styles.selectorWrapper,
					onLongClick: this.getContentLongClickHandler(),
					onClick: () => this.handleAdditionalFocusActions(),
				},
				Text({
					style: this.getValueStyle(),
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getSelectedItemsText(),
				}),
				selectShowImage && View(
					{
						style: this.styles.arrowImageContainer,
					},
					Image({
						tintColor: AppTheme.colors.base3,
						style: this.styles.arrowImage,
						svg: {
							content: chevronDown(),
						},
					}),
				),
			);
		}

		getMode()
		{
			return this.getConfig().mode;
		}

		isPicker()
		{
			return this.getMode() === Type.Picker;
		}

		isSelector()
		{
			return this.getMode() === Type.Selector;
		}

		isContextMenu()
		{
			return this.getMode() === Type.ContextMenu;
		}

		isPopupMenu()
		{
			return this.getMode() === Type.PopupMenu;
		}

		handleAdditionalFocusActions()
		{
			if (this.isSelector())
			{
				return this.handleSelectorFocusAction();
			}

			if (this.isContextMenu())
			{
				return this.handleContextMenuFocusAction();
			}

			if (this.isPopupMenu())
			{
				return this.handlePopupMenuFocusAction();
			}

			return this.handlePickerFocusAction();
		}

		handlePickerFocusAction()
		{
			dialogs.showPicker(
				{
					title: this.getConfig().defaultListTitle,
					items: this.getItems(),
					defaultValue: this.getValue(),
				},
				(event, item) => {
					this
						.removeFocus()
						.then(() => {
							if (event === 'onPick')
							{
								this.handleChange(item.value);
							}
						});
				},
			);

			return Promise.resolve();
		}

		handleContextMenuFocusAction()
		{
			const contextMenu = new ContextMenu({
				actions: this.getItems().map((item) => ({
					id: item.name,
					title: item.name,
					selected: this.getValue() === item.value,
					onClickCallback: () => this.removeFocus().then(() => this.handleChange(item.value)),
				})),
				params: {
					title: this.getTitleText(),
				},
				onClose: () => this.removeFocus(),
			});

			return contextMenu.show(this.getPageManager());
		}

		handlePopupMenuFocusAction()
		{
			const menu = new UIMenu(
				this.getItems().map((item) => ({
					id: item.name,
					title: item.name,
					checked: this.getValue() === item.value,
					onItemSelected: () => this.removeFocus().then(() => this.handleChange(item.value)),
				})),
			);
			menu.getPopup().on('hidden', () => this.removeFocus());
			menu.show({ target: this.fieldContainerRef });
		}

		handleSelectorFocusAction()
		{
			return this.openSelector();
		}

		/**
		 * @public
		 */
		openSelector()
		{
			return new Promise((resolve, reject) => {
				if (this.selector)
				{
					return resolve();
				}
				const items = this.getSelectorItems();
				const isEmptyItems = items.length === 0;
				const widgetName = isEmptyItems ? 'layout' : 'selector';

				this
					.getPageManager()
					.openWidget(widgetName, {
						title: this.getConfig().defaultListTitle,
						backdrop: {
							mediumPositionPercent: 70,
							horizontalSwipeAllowed: false,
						},
					})
					.then((widget) => {
						if (isEmptyItems)
						{
							this.showEmptyScreen(widget);
						}
						else
						{
							this.selector = widget;

							this.selector.enableNavigationBarBorder(false);
							this.selector.setRightButtons([
								{
									name: (
										this.isMultiple()
											? BX.message('FIELDS_SELECT_CHOOSE')
											: BX.message('FIELDS_SELECT_SELECTOR_DONE')
									),
									type: 'text',
									color: AppTheme.colors.accentMainLinks,
									callback: () => this.applyChangesAndCloseSelector(),
								},
							]);
							this.selector.setSearchEnabled(this.getConfig().isSearchEnabled);
							this.selector.allowMultipleSelection(this.isMultiple());
							this.selectorSetItems(items);
							this.selectorSelectedItems = this.getSelectedSelectorItems();
							this.selector.setSelected(this.selectorSelectedItems);

							this.selector.setListener((eventName, data) => {
								const callbackName = `${eventName}Listener`;

								if (this[callbackName])
								{
									this[callbackName].apply(this, [data]);
								}
							});
						}
						resolve();
					})
					.catch(reject)
				;
			});
		}

		onListFillListener({ text })
		{
			const selectorItems = this.getSelectorItems();
			if (selectorItems.length === 0)
			{
				return;
			}

			let items = selectorItems;

			if (text.trim())
			{
				const foundItems = search(selectorItems, text, ['title']);
				items = foundItems.length > 0 ? foundItems : [this.getEmptyResultButtonItem()];
			}

			this.selectorSetItems(items);
		}

		selectorSetItems(items)
		{
			if (!this.selector)
			{
				return;
			}
			this.selector.setItems(items, this.getSelectorSections());
		}

		showEmptyScreen(widget)
		{
			widget.showComponent(
				new EmptyScreen({
					image: {
						uri: EmptyScreen.makeLibraryImagePath('empty-list.svg'),
						style: {
							width: 95,
							height: 95,
						},
					},
					title: () => Text({
						style: {
							fontWeight: '400',
							color: AppTheme.colors.base3,
							fontSize: 17,
							textAlign: 'center',
						},
						text: BX.message('FIELDS_SELECT_SELECTOR_EMPTY_LIST'),
					}),
				}),
			);
			this.applyChangesAndCloseSelector();
		}

		getSelectorItems()
		{
			const items = this.getItems();

			return (
				items
					.map((item, index) => {
						return {
							id: item.value,
							title: item.name,
							sectionCode: SELECTOR_SECTION_ID,
							hideBottomLine: index === items.length - 1,
							useLetterImage: false,
							selectedImageUrl: DEFAULT_SELECTED_ICON,
						};
					})
			);
		}

		getSelectedSelectorItems()
		{
			const values = this.getValuesArray();

			return this.getSelectorItems().filter(({ id }) => values.includes(id));
		}

		getSelectorSections()
		{
			return [{ id: SELECTOR_SECTION_ID }];
		}

		closeSelector()
		{
			return new Promise((resolve) => {
				if (!this.selector)
				{
					return resolve();
				}

				this
					.removeFocus()
					.then(() => {
						this.selector.close();
						this.selector = null;
						resolve();
					})
					.catch(console.error);
			});
		}

		applyChangesAndCloseSelector()
		{
			return (
				this.removeFocus()
					.then(() => this.applySelectorChanges())
					.then(() => this.closeSelector())
			);
		}

		applySelectorChanges()
		{
			const selectedValues = this.selectorSelectedItems.map((item) => item.id);

			if (!isEqual(selectedValues, this.getValuesArray()))
			{
				const values = this.isMultiple() ? selectedValues : selectedValues[0];

				return this.handleChange(values, this.getItems());
			}

			return Promise.resolve();
		}

		/**
		 * Specific method call from list.setListener().
		 *
		 * @param item
		 */
		onSelectedChangedListener({ items })
		{
			this.selectorSelectedItems = items;
			this.selector.setSelected(this.selectorSelectedItems);

			if (!this.isMultiple())
			{
				void this.applyChangesAndCloseSelector();
			}
		}

		/**
		 * Specific method call from list.setListener().
		 * Works on iOS.
		 */
		onViewWillHiddenListener()
		{
			this.closeSelectorForIOS();
		}

		/**
		 * Specific method call from list.setListener().
		 * Works on everywhere but needs for Android.
		 */
		onViewHiddenListener()
		{
			this.closeSelectorForAndroid();
		}

		/**
		 * Specific method call from list.setListener().
		 * Works on Android.
		 */
		onViewRemovedListener()
		{
			this.closeSelectorForAndroid();
		}

		closeSelectorForIOS()
		{
			if (Application.getPlatform() === 'ios')
			{
				void this.closeSelector();
			}
		}

		closeSelectorForAndroid()
		{
			if (Application.getPlatform() === 'android')
			{
				void this.closeSelector();
			}
		}

		getEmptyResultButtonItem()
		{
			return {
				title: BX.message('FIELDS_SELECT_SELECTOR_NO_RESULTS'),
				type: 'button',
				unselectable: true,
				sectionCode: SELECTOR_SECTION_ID,
				hideBottomLine: true,
			};
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
					flexShrink: 2,
				},
				arrowImageContainer: {
					marginLeft: 2,
					width: 16,
					height: 16,
					alignSelf: 'center',
					justifyContent: 'center',
					alignItems: 'center',
				},
				arrowImage: {
					width: 7,
					height: 5,
				},
				value: {
					color: this.isEmpty() ? AppTheme.colors.base4 : AppTheme.colors.base1,
					fontSize: 16,
					flexShrink: 2,
				},
			};
		}

		getHiddenEmptyChildFieldStyles(styles)
		{
			return {
				...styles,
				innerWrapper: {
					flex: this.isEmpty() && !this.isReadOnly() ? 0 : 1,
				},
			};
		}

		renderEditIcon()
		{
			if (!this.isEmpty())
			{
				return null;
			}

			return View(
				{
					style: {
						marginLeft: 2,
						width: 16,
						height: 16,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Image(
					{
						style: {
							width: 7,
							height: 5,
						},
						svg: {
							content: chevronDown(this.getTitleColor()),
						},
					},
				),
			);
		}

		canCopyValue()
		{
			return true;
		}

		prepareValueToCopy()
		{
			return this.getSelectedItemsText();
		}
	}

	SelectField.propTypes = {
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

			mode: PropTypes.oneOf([Type.Picker, Type.Selector, Type.ContextMenu, Type.PopupMenu]),
			items: PropTypes.oneOfType([
				PropTypes.arrayOf(PropTypes.shape({
					value: PropTypes.string.isRequired,
					name: PropTypes.string.isRequired,
					selectedName: PropTypes.string,
				})),
				PropTypes.array,
			]),
			defaultListTitle: PropTypes.string,
			selectShowImages: PropTypes.bool,
			isSearchEnabled: PropTypes.bool,
		}),
	};

	SelectField.defaultProps = {
		...BaseSelectField.defaultProps,
		config: {
			...BaseSelectField.defaultProps.config,
			mode: Type.Picker,
			items: [],
			defaultListTitle: '',
			selectShowImages: true,
			isSearchEnabled: true,
		},
	};

	module.exports = {
		SelectType: 'select',
		SelectFieldClass: SelectField,
		SelectField: (props) => new SelectField(props),
	};
});
