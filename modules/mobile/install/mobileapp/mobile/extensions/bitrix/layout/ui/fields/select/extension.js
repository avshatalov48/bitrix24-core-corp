/**
 * @module layout/ui/fields/select
 */
jn.define('layout/ui/fields/select', (require, exports, module) => {

	const { chevronDown } = require('assets/common');
	const { BaseSelectField } = require('layout/ui/fields/base-select');
	const { stringify } = require('utils/string');
	const { isEqual } = require('utils/object');
	const { search } = require('utils/search');
	const { EmptyScreen } = require('layout/ui/empty-screen');

	const Type = {
		Picker: 'picker',
		Selector: 'selector',
	};

	const SELECTOR_SECTION_ID = 'all';

	const pathToIcons = `/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/select/icons/`;
	const DEFAULT_SELECTED_ICON = currentDomain + pathToIcons + 'selected.png';

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
				selectShowImages: BX.prop.getString(config, 'selectShowImages', true),
			};
		}

		prepareMode(config)
		{
			let mode;

			if (this.isMultiple())
			{
				mode = Type.Selector;
			}
			else
			{
				mode = BX.prop.getString(config, 'mode', Type.Picker);
			}

			if (mode !== Type.Selector)
			{
				mode = Type.Picker;
			}

			return mode;
		}

		prepareItems(items)
		{
			items = items.filter((item) => item.value !== '');

			if (this.isPicker() && !this.shouldPreselectFirstItem())
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

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return Text({
				style: this.styles.value,
				text: this.getSelectedItemsText(),
			});
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
				},
				Text({
					style: this.styles.value,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.getSelectedItemsText(),
				}),
				selectShowImage && View(
					{
						style: this.styles.arrowImageContainer,
					},
					Image({
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

		handleAdditionalFocusActions()
		{

			if (this.isSelector())
			{
				return this.handleSelectorFocusAction();
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
						})
					;
				},
			);

			return Promise.resolve();
		}

		handleSelectorFocusAction()
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
							this.selector.setRightButtons([{
								name: (
									this.isMultiple()
										? BX.message('FIELDS_SELECT_CHOOSE')
										: BX.message('FIELDS_SELECT_SELECTOR_DONE')
								),
								type: 'text',
								color: '#0b66c3',
								callback: () => this.applyChangesAndCloseSelector(),
							}]);
							this.selector.setSearchEnabled(true);
							this.selector.allowMultipleSelection(this.isMultiple());
							this.selectorSetItems(items);
							this.selectorSelectedItems = this.getSelectedSelectorItems();
							this.selector.setSelected(this.selectorSelectedItems);

							this.selector.setListener((eventName, data) => {
								const callbackName = eventName + 'Listener';

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
			if (!selectorItems.length)
			{
				return;
			}

			let items = selectorItems;

			if (text.trim())
			{
				const foundItems = search(selectorItems, text, ['title']);
				items = foundItems.length ? foundItems : [this.getEmptyResultButtonItem()];
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
						uri: EmptyScreen.makeLibraryImagePath('emptyList.png'),
						style: {
							width: 95,
							height: 95,
						},
					},
					title: () => Text({
						style: {
							fontWeight: '400',
							color: '#828B95',
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
				;
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

				return this.handleChange(values);
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
					color: this.isEmpty() ? '#a8adb4' : '#333333',
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
	}

	module.exports = {
		SelectType: 'select',
		SelectField: (props) => new SelectField(props),
	};

});
