/**
 * @module selector/widget
 */
jn.define('selector/widget', (require, exports, module) => {

	const { uniqBy } = require('utils/array');
	const { isEqual, get } = require('utils/object');
	const { CommonSelectorProvider } = require('selector/providers/common');

	const SERVICE_SECTION_CODE = 'service';
	const COMMON_SECTION_CODE = 'common';

	const CREATE_BUTTON_CODE = 'create';
	const DEFAULT_RETURN_KEY = 'done';

	/**
	 * @class EntitySelectorWidget
	 */
	class EntitySelectorWidget
	{
		constructor({
						entityIds,
						provider,
						searchOptions,
						createOptions,
						selectOptions,
						widgetParams,
						allowMultipleSelection,
						canUseRecent,
						closeOnSelect,
						events,
						initSelectedIds,
						returnKey,
					})
		{
			this.apiVersion = Application.getApiVersion();
			this.isApiVersionGreaterThan44 = this.apiVersion >= 44;
			this.isApiVersionGreaterThan45 = this.apiVersion >= 45;

			this.returnKey = returnKey || DEFAULT_RETURN_KEY;
			this.queryText = '';
			this.isItemCreating = false;
			this.manualSelection = false;

			this.currentItems = [];
			this.currentSections = [];
			this.currentSelectedItems = [];

			this.searchOptions = searchOptions || {};
			this.createOptions = createOptions || {};
			this.selectOptions = selectOptions || {};
			this.widgetParams = widgetParams || {};
			this.allowMultipleSelection = allowMultipleSelection !== false;
			this.canUseRecent = canUseRecent !== false;
			this.closeOnSelect = this.allowMultipleSelection === false && closeOnSelect;
			this.events = events || {};

			this.entityIds = Array.isArray(entityIds) ? entityIds : [entityIds];
			this.initSelectedIds = this.prepareInitSelectedIds(initSelectedIds);

			this.setupProvider(provider);
		}

		prepareInitSelectedIds(initSelectedIds)
		{
			initSelectedIds = Array.isArray(initSelectedIds) ? initSelectedIds : [];
			initSelectedIds = initSelectedIds.map((data) => {
				let entityId, id;

				if (Array.isArray(data))
				{
					[entityId, id] = data;
				}
				else
				{
					entityId = this.entityIds[0];
					id = data;
				}

				return [entityId, id.toString()];
			});

			return initSelectedIds;
		}

		setupProvider(provider)
		{
			this.provider = new CommonSelectorProvider(
				provider.context || null,
				provider.options || {},
			);

			if (this.searchOptions.searchFields)
			{
				this.provider.setSearchFields(this.searchOptions.searchFields);
			}

			if (this.searchOptions.entityWeight)
			{
				this.provider.setEntityWeight(this.searchOptions.entityWeight);
			}

			this.provider.setPreselectedItems(this.initSelectedIds);
			this.provider.setCanUseRecent(this.canUseRecent);

			this.provider.setListener({
				onFetchResult: this.onProviderFetchResult.bind(this),
				onRecentResult: this.onProviderRecentResult.bind(this),
			});
		}

		getSearchPlaceholder()
		{
			let text = null;

			if (this.createOptions.enableCreation)
			{
				text = this.searchOptions.searchPlaceholderWithCreation || BX.message('PROVIDER_SEARCH_CREATE_PLACEHOLDER');
			}

			if (!text)
			{
				text = this.searchOptions.searchPlaceholderWithoutCreation;
			}

			return text;
		}

		show({ widgetParams } = {}, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				if (this.widget)
				{
					return resolve();
				}

				parentWidget = (parentWidget || PageManager);
				parentWidget
					.openWidget('selector', (widgetParams || this.widgetParams))
					.then((widget) => {
						this.widget = widget;

						if (this.isApiVersionGreaterThan45)
						{
							this.widget.setReturnKey(this.returnKey);
						}

						if (typeof this.widget.setPlaceholder === 'function')
						{
							const placeholder = this.getSearchPlaceholder();
							if (placeholder)
							{
								this.widget.setPlaceholder(placeholder);
							}
						}

						this.widget.setRightButtons([{
							name: (
								this.closeOnSelect
									? BX.message('PROVIDER_WIDGET_CLOSE')
									: BX.message('PROVIDER_WIDGET_SELECT')
							),
							type: 'text',
							color: '#2066b0',
							callback: () => this.close(),
						}]);

						this.widget.allowMultipleSelection(this.allowMultipleSelection);
						this.provider.loadRecent();

						this.widget.setListener((eventName, data) => {
							const callbackName = eventName + 'Listener';
							if (typeof this[callbackName] === 'function')
							{
								this[callbackName].apply(this, [data]);
							}
						});

						resolve();
					})
					.catch(reject);
			});
		}

		// region widget event listeners

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param text
		 */
		clickEnterListener({ text })
		{
			if (!this.isApiVersionGreaterThan45)
			{
				return;
			}

			Keyboard.dismiss();
		}

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param text
		 */
		onListFillListener({ text })
		{
			this.queryText = text.trim();

			if (text === '')
			{
				this.provider.loadRecent();
			}
			else
			{
				this.provider.doSearch(text);
			}
		}

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param text
		 * @param item
		 */
		onItemSelectedListener({ text, item })
		{
			if (!(item && item.hasOwnProperty('params') && item.params.hasOwnProperty('code')))
			{
				return;
			}

			const buttonCode = item.params.code;

			switch (buttonCode)
			{
				case CREATE_BUTTON_CODE:
					this.createItem(text);
					break;
			}
		}

		createItem(text)
		{
			if (
				!this.createOptions.enableCreation
				|| !this.createOptions.handler
				|| this.getIsItemCreating()
			)
			{
				return;
			}

			this.setIsItemCreating(true);

			this.createOptions
				.handler(text)
				.then((item) => {
					if (item && item.id)
					{
						if (!this.provider.isInRecentCache(item))
						{
							this.provider.addToRecentCache(item);
						}

						this.manualSelection = true;

						const preparedItem = this.provider.prepareItemForDrawing(item);
						this.provider.prepareResult([preparedItem]);

						if (!this.isInSelected(preparedItem))
						{
							let selected = [preparedItem];

							if (this.allowMultipleSelection)
							{
								selected = [
									...selected,
									...this.currentSelectedItems,
								];
							}

							this.setSelected(selected);
						}
					}

					const closeParams = {
						...item,
						queryText: this.queryText,
					};

					this.setIsItemCreating(false);
					this.resetQuery();

					if (this.createOptions.closeAfterCreation)
					{
						this.closeOnCreation(closeParams);
					}
				})
			;
		}

		getEntityType(item)
		{
			return get(item, ['params', 'type'], null);
		}

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param text
		 * @param scope
		 * @param items
		 */
		onSelectedChangedListener({ text, scope, items })
		{
			this.manualSelection = true;

			if (!this.hasItemsInCurrentItems(items))
			{
				this.setItems([...items, ...this.currentItems]);
			}

			this.setSelected(items);

			if (this.closeOnSelect)
			{
				void this.close();
			}
		}

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param {Object} section
		 */
		sectionButtonClickListener(section)
		{
			this.createItem(this.queryText);
		}

		/**
		 * Specific method call from widget.setListener().
		 */
		onViewHiddenListener()
		{
			this.onViewHidden();
			this.onViewHiddenStrict();
		}

		/**
		 * Specific method call from widget.setListener().
		 * Works on iOS.
		 */
		onViewWillHiddenListener()
		{
			this.onViewHidden();
		}

		/**
		 * Specific method call from widget.setListener().
		 * Works on Android.
		 */
		onViewRemovedListener()
		{
			this.onViewRemoved();
		}

		// endregion

		// region provider event handlers

		onProviderRecentResult(items, cache = false)
		{
			if (this.queryText !== '')
			{
				return;
			}

			if (cache === false)
			{
				items = items.filter(({ id }) => id !== 'loading');
			}

			const hasOwnItems = items.length > 0;
			if (!hasOwnItems)
			{
				items.push({
					title: this.getEmptyItemTitle(),
					type: 'button',
					sectionCode: COMMON_SECTION_CODE,
					unselectable: true,
				});
			}

			this.setItems(items);

			if (!this.manualSelection && hasOwnItems)
			{
				const filteredSelectedItems = this.filterSelectedByItems(items);
				this.setSelected(filteredSelectedItems);
			}
		}

		getEmptyItemTitle()
		{
			if (this.createOptions.enableCreation)
			{
				return this.searchOptions.startTypingWithCreationText || BX.message('PROVIDER_WIDGET_START_TYPING_TO_CREATE');
			}

			return this.searchOptions.startTypingText || BX.message('PROVIDER_WIDGET_START_TYPING_TO_SEARCH');
		}

		filterSelectedByItems(items)
		{
			return this.initSelectedIds.reduce((result, [entityId, selectedId]) => {
				const selectedItem = items.find(item => {
					if (!item.params || !item.params.id)
					{
						return false;
					}

					return (
						this.entityIds.includes(item.params.type)
						&& item.params.type.toString() === entityId.toString()
						&& item.params.id.toString() === selectedId.toString()
					);
				});

				if (
					selectedItem
					&& (
						this.allowMultipleSelection
						|| result.length === 0
					)
				)
				{
					result.push(selectedItem);
				}

				return result;
			}, []);
		}

		onProviderFetchResult(items, cache = false)
		{
			if (this.provider.queryString !== this.queryText)
			{
				return;
			}

			if (cache === false)
			{
				items = items.filter(({ id }) => id !== 'loading');
			}

			if (items.length === 0)
			{
				items.unshift(this.getEmptyResultButtonItem());
			}

			if (this.createOptions.enableCreation && !this.isApiVersionGreaterThan44)
			{
				items.unshift(this.getCreateButtonItem());
			}

			this.setItems(items);
		}

		// endregion

		/**
		 * @param {array} itemsToCheck
		 */
		hasItemsInCurrentItems(itemsToCheck)
		{
			return itemsToCheck.every((itemToCheck) => {
				return this.currentItems.some((item) => {
					return item.id === itemToCheck.id;
				});
			});
		}

		setItems(items)
		{
			if (!this.widget)
			{
				return;
			}

			items = uniqBy(items, 'id');

			const isRecent = this.queryText === '';
			const sections = [];

			const serviceItems = items.filter((item) => item.sectionCode === SERVICE_SECTION_CODE);
			if (serviceItems.length)
			{
				serviceItems.forEach((item, index) => {
					item.hideBottomLine = index === items.length - 1;

					return item;
				});

				sections.push({ id: SERVICE_SECTION_CODE });
			}

			items
				.filter((item) => !item.sectionCode || item.sectionCode === COMMON_SECTION_CODE)
				.forEach((item, index) => {
					item.hideBottomLine = index === items.length - 1;
					item.sectionCode = COMMON_SECTION_CODE;

					return item;
				})
			;

			const title = (
				isRecent
					? BX.message('PROVIDER_SEARCH_RECENT_SECTION_TITLE')
					: BX.message('PROVIDER_SEARCH_SECTION_TITLE')
			);
			const buttonText = this.getCommonSectionButtonText(isRecent);
			const styles = this.getCommonSectionStyles();

			sections.push({
				id: COMMON_SECTION_CODE,
				title,
				buttonText,
				styles,
				backgroundColor: '#ffffff',
			});

			if (!isEqual(this.currentSections, sections))
			{
				this.currentSections = sections;
				this.widget.setSections(this.currentSections);
			}

			if (!isEqual(this.currentItems, items))
			{
				this.currentItems = items;
				this.widget.setItems(this.currentItems);
			}
		}

		getCommonSectionButtonText(isRecent)
		{
			const { canCreateWithEmptySearch, enableCreation } = this.createOptions;

			if (enableCreation && (canCreateWithEmptySearch || !isRecent))
			{
				return this.getCreateButtonItemTitle();
			}

			return '';
		}

		getCommonSectionStyles()
		{
			return {
				title: {
					font: {
						size: 15,
						color: '#525c69',
					},
				},
				button: {
					font: {
						size: 15,
						color: this.getIsItemCreating() ? '#525c69' : '#2066b0',
					},
				},
			};
		}

		setSelected(items)
		{
			if (!this.widget)
			{
				return;
			}

			if (
				this.selectOptions.canUnselectLast === false
				&& this.currentSelectedItems.length === 1
				&& items.length === 0
			)
			{
				return;
			}

			if (this.selectOptions.singleEntityByType)
			{
				items = this.filterTypeToOneEntity(items);
			}

			items = uniqBy(items, 'id');
			if (!isEqual(this.currentSelectedItems, items))
			{
				this.currentSelectedItems = items;
				this.widget.setSelected(this.currentSelectedItems);
			}
		}

		filterTypeToOneEntity(items)
		{
			const filterItems = {};
			items.forEach((item) => {
				const entityType = this.getEntityType(item);
				if (!filterItems[entityType] || (filterItems[entityType] && !this.isInSelected(item)))
				{
					filterItems[entityType] = item;
				}
			});

			return Object.values(filterItems);
		}

		isInSelected(item)
		{
			return this.currentSelectedItems.find(({ id }) => id === item.id) !== undefined;
		}

		getIsItemCreating()
		{
			return this.isItemCreating;
		}

		setIsItemCreating(isItemCreating)
		{
			this.isItemCreating = isItemCreating;

			let items = this.currentItems;

			if (!this.isApiVersionGreaterThan44)
			{
				items = items.map((item) => {
					if (item.params && item.params.code === CREATE_BUTTON_CODE)
					{
						item.title = this.getCreateButtonItemTitle();
					}

					return item;
				});
			}

			this.setItems(items);
		}

		resetQuery()
		{
			this.queryText = '';
			this.widget.setQueryText('');
			this.provider.loadRecent();
		}

		onViewHidden()
		{
			if (this.widget !== null)
			{
				this.widget = null;

				if (this.events.onViewHidden)
				{
					this.events.onViewHidden();
				}
			}
		}

		onViewHiddenStrict()
		{
			if (this.events.onViewHiddenStrict)
			{
				this.events.onViewHiddenStrict();
			}
		}

		onViewRemoved()
		{
			this.widget = null;
			if (this.events.onViewRemoved)
			{
				this.events.onViewRemoved();
			}
		}

		onClose()
		{
			if (this.currentSelectedItems.length > 0)
			{
				this.provider.prepareResult(this.currentSelectedItems);
			}

			if (this.events.onClose)
			{
				this.events.onClose(this.extractEntityItems(this.currentSelectedItems));
			}
		}

		onWidgetClosed()
		{
			this.widget = null;
			if (this.events.onWidgetClosed)
			{
				this.events.onWidgetClosed(this.extractEntityItems(this.currentSelectedItems));
			}
		}

		closeOnCreation(entity)
		{
			if (this.events.onCreateBeforeClose)
			{
				this.events.onCreateBeforeClose(entity);
			}

			this.close().then(() => {
				if (this.events.onCreate)
				{
					this.events.onCreate(entity);
				}
			});
		}

		extractEntityItems(items)
		{
			return items.map((item) => ({
				...item.params,
				imageUrl: item.imageUrl,
			}));
		}

		getCreateButtonItem()
		{
			return {
				title: this.getCreateButtonItemTitle(),
				type: 'button',
				unselectable: true,
				sectionCode: SERVICE_SECTION_CODE,
				params: { 'code': CREATE_BUTTON_CODE },
			};
		}

		getCreateButtonItemTitle()
		{
			return (
				this.getIsItemCreating()
					? (this.createOptions.creatingText || BX.message('PROVIDER_WIDGET_CREATING_ITEM'))
					: (this.createOptions.createText || BX.message('PROVIDER_WIDGET_CREATE_ITEM'))
			);
		}

		getEmptyResultButtonItem()
		{
			return {
				title: BX.message('PROVIDER_SEARCH_NO_RESULTS'),
				type: 'button',
				sectionCode: COMMON_SECTION_CODE,
				unselectable: true,
			};
		}

		close()
		{
			this.onClose();

			return new Promise((resolve) => {
				if (!this.widget)
				{
					return resolve();
				}

				this.widget.close(() => {
					this.onWidgetClosed();
					resolve();
				});
			});
		}
	}

	module.exports = { EntitySelectorWidget };
});
