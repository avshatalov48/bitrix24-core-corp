/**
 * @module selector/widget
 */
jn.define('selector/widget', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { uniqBy } = require('utils/array');
	const { isEqual, get, mergeImmutable } = require('utils/object');
	const { CommonSelectorProvider } = require('selector/providers/common');
	const { Feature } = require('feature');
	const { showToast } = require('toast');
	const { Type } = require('type');

	const SERVICE_SECTION_CODE = 'service';
	const COMMON_SECTION_CODE = 'common';

	const CREATE_BUTTON_CODE = 'create';
	const DEFAULT_RETURN_KEY = 'done';

	const isAirStyleSupported = Feature.isAirStyleSupported();

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
			undeselectableIds,
			returnKey,
			scopes,
			shouldRenderHiddenItemsInList,
			sectionTitles,
			animation,
			leftButtons,
			integrateSelectorToParentLayout,
		})
		{
			this.integrateSelectorToParentLayout = integrateSelectorToParentLayout ?? false;
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
			this.closeOnSelect = (
				this.allowMultipleSelection === false
				&& closeOnSelect
			);
			this.events = events || {};

			this.hiddenWidget = null;

			this.entityIds = Array.isArray(entityIds) ? entityIds : [entityIds];
			this.initSelectedIds = this.prepareInitSelectedIds(initSelectedIds);
			this.undeselectableIds = this.prepareInitSelectedIds(undeselectableIds);
			this.scopes = scopes;

			this.sectionTitles = sectionTitles || {};
			this.shouldRenderHiddenItemsInList = shouldRenderHiddenItemsInList ?? true;
			this.animation = animation || 'none';

			this.leftButtons = Type.isArrayFilled(leftButtons) ? leftButtons : [];

			this.setupProvider(provider);

			this.shouldSetInitiallySelectedItems = Array.isArray(this.initSelectedIds)
				? this.initSelectedIds.length > 0 : false;
		}

		getWidget()
		{
			return this.widget;
		}

		getProvider()
		{
			return this.provider;
		}

		getCurrentItems()
		{
			return this.currentItems;
		}

		getCurrentSelectedItems()
		{
			return this.currentSelectedItems;
		}

		addEvents(events)
		{
			this.events = {
				...this.events,
				...events,
			};
		}

		prepareInitSelectedIds(initSelectedIds)
		{
			initSelectedIds = Array.isArray(initSelectedIds) ? initSelectedIds : [];
			initSelectedIds = initSelectedIds.map((data) => {
				let entityId = '';
				let id = '';

				if (Array.isArray(data))
				{
					[entityId, id] = data;
				}
				else if (this.entityIds.length === 1)
				{
					entityId = this.entityIds[0];
					id = data;
				}
				else
				{
					throw new Error('EntitySelectorWidget: elements of initSelectedIds should contain entity id, if multiple entities are used in selector');
				}

				return [entityId, id.toString()];
			});

			return initSelectedIds;
		}

		setupProvider(provider)
		{
			const providerClass = provider.class ?? CommonSelectorProvider;
			// eslint-disable-next-line new-cap
			this.provider = new providerClass(
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

			this.provider.setPreselectedItems?.(this.initSelectedIds);
			this.provider.setCanUseRecent?.(this.canUseRecent);

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
				text = this.searchOptions.searchPlaceholderWithCreation || BX.message(
					'PROVIDER_SEARCH_CREATE_PLACEHOLDER',
				);
			}

			if (!text)
			{
				text = this.searchOptions.searchPlaceholderWithoutCreation;
			}

			return text;
		}

		show({ widgetParams = this.widgetParams } = {}, parentWidget = PageManager)
		{
			let airWidgetParams = {};
			const sendButtonName = widgetParams.sendButtonName ?? Loc.getMessage('PROVIDER_WIDGET_SELECT');

			if (isAirStyleSupported)
			{
				airWidgetParams = {
					sendButtonName: this.allowMultipleSelection ? sendButtonName : null,
					titleParams: {
						type: 'dialog',
					},
				};

				if (widgetParams.title)
				{
					airWidgetParams.titleParams.text = widgetParams.title;
				}
			}

			return new Promise((resolve, reject) => {
				if (this.widget)
				{
					resolve();

					return;
				}

				const openWidgetHandler = (widget) => {
					this.widget = widget;

					this.widget.setReturnKey(this.returnKey);

					if (Array.isArray(this.scopes))
					{
						this.widget.setScopes(this.scopes);
					}

					const placeholder = this.getSearchPlaceholder();
					if (placeholder)
					{
						this.widget.setPlaceholder(placeholder);
					}

					if (this.leftButtons)
					{
						this.widget.setLeftButtons(this.leftButtons);
					}

					if (!isAirStyleSupported)
					{
						this.widget.setRightButtons([
							{
								name: (
									this.closeOnSelect
										? BX.message('PROVIDER_WIDGET_CLOSE')
										: BX.message('PROVIDER_WIDGET_SELECT')
								),
								type: 'text',
								color: Color.accentMainLinks.toHex(),
								callback: () => this.close(),
							},
						]);
					}



					this.widget.allowMultipleSelection(this.allowMultipleSelection);
					this.provider.loadRecent?.();

					this.widget.on('send', () => this.close());
					this.widget.on('onViewShown', this.onViewShown);

					this.widget.setListener((eventName, data) => {
						const callbackName = `${eventName}Listener`;
						if (typeof this[callbackName] === 'function')
						{
							this[callbackName].apply(this, [data]);
						}
					});

					this.handleOnEventsCallback('onWidgetCreated');

					resolve(this.widget);
				};

				if (this.integrateSelectorToParentLayout)
				{
					openWidgetHandler(parentWidget);

					return;
				}

				const widgetManager = (parentWidget || PageManager);
				widgetManager
					.openWidget('selector', mergeImmutable(widgetParams, airWidgetParams))
					.then(openWidgetHandler)
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
			Keyboard.dismiss();
		}

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param text
		 * @param scope
		 */
		onListFillListener({ text, scope })
		{
			this.queryText = text.trim();

			if (text === '')
			{
				if (typeof this.searchOptions.onSearchCancelled === 'function')
				{
					this.searchOptions.onSearchCancelled({ scope });
				}
				else
				{
					this.provider.loadRecent?.();
				}

				return;
			}

			if (typeof this.searchOptions.onSearch === 'function')
			{
				this.searchOptions.onSearch({ text, scope });
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
		 * @param scope
		 */
		onItemSelectedListener({ text, item, scope })
		{
			this.handleOnEventsCallback('onItemSelected', { text, item, scope });

			if (item.disabled)
			{
				const nonSelectableErrorText = this.selectOptions.getNonSelectableErrorText?.(item) || '';
				if (nonSelectableErrorText.length > 0)
				{
					showToast({ message: nonSelectableErrorText }, this.widget);
				}

				return;
			}

			if (item.params?.code === CREATE_BUTTON_CODE)
			{
				this.createItems(text);
			}
		}

		onScopeChangedListener({ text, scope })
		{
			this.handleOnEventsCallback('onScopeChanged', { text, scope });
		}

		createItems(text)
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
				.handler(text, this.allowMultipleSelection)
				.then(
					(created) => {
						const items = Array.isArray(created) ? created : [created];
						this.manualSelection = true;
						let newSelected = [];
						items.forEach((item) => {
							if (item.id)
							{
								if (!this.provider.isInRecentCache?.(item))
								{
									this.provider.addToRecentCache?.(item);
								}

								const preparedItem = this.provider.prepareItemForDrawing(item);
								if (!this.isInSelected(preparedItem))
								{
									newSelected.push(preparedItem);
								}
							}
						});

						if (newSelected.length > 0)
						{
							this.provider.prepareResult(newSelected);
							if (this.allowMultipleSelection)
							{
								newSelected = [
									...newSelected,
									...this.currentSelectedItems,
								];
							}
							else
							{
								newSelected = [newSelected[0]];
							}
							this.setSelected(newSelected);
						}

						const closeParams = {
							items,
							queryText: this.queryText,
						};

						this.setIsItemCreating(false);
						this.resetQuery();

						if (this.createOptions.closeAfterCreation)
						{
							this.closeOnCreation(closeParams);
						}
					},
					(errors) => {
						this.setIsItemCreating(false);
					},
				).catch(console.error);
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
			Keyboard.dismiss();

			this.manualSelection = true;

			if (this.shouldRenderHiddenItemsInList && !this.hasItemsInCurrentItems(items))
			{
				this.setItems([...items, ...this.currentItems]);
			}

			this.setSelected(items);

			this.handleOnEventsCallback('onSelectedChanged', this.getEntityItems());

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
			this.createItems(this.queryText);
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

		onProviderRecentResult(items = [], cache = false)
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
				return this.searchOptions.startTypingWithCreationText || BX.message(
					'PROVIDER_WIDGET_START_TYPING_TO_CREATE',
				);
			}

			return this.searchOptions.startTypingText || BX.message('PROVIDER_WIDGET_START_TYPING_TO_SEARCH');
		}

		filterSelectedByItems(items)
		{
			return this.initSelectedIds.reduce((result, [entityId, selectedId]) => {
				const selectedItem = items.find((item) => {
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

		onProviderFetchResult(items, cache = false, title = null)
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

			this.setItems(items);
		}

		// endregion

		/**
		 * @param {array} itemsToCheck
		 */
		hasItemsInCurrentItems(itemsToCheck)
		{
			const isConvertibleToNumber = (value) => !Number.isNaN(Number(value));

			return itemsToCheck.every((itemToCheck) => {
				return this.currentItems.some((item) => {
					if (
						(isConvertibleToNumber(item.id) && Number(item.id) <= 0)
						|| (isConvertibleToNumber(itemToCheck.id) && Number(itemToCheck.id) <= 0)
					)
					{
						return false;
					}

					return item.id && itemToCheck.id && String(item.id || '') === String(itemToCheck.id || '');
				});
			});
		}

		isRecentSearch()
		{
			return this.queryText === '';
		}

		setItems(items, animation = this.animation)
		{
			if (!this.widget)
			{
				return;
			}

			items = uniqBy(items, 'id');

			const isRecent = this.isRecentSearch();
			const sections = [];

			const serviceItems = items.filter((item) => item.sectionCode === SERVICE_SECTION_CODE);
			if (serviceItems.length > 0)
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
				});

			const recentTitle = this.sectionTitles.recent ?? Loc.getMessage('PROVIDER_SEARCH_RECENT_SECTION_TITLE');
			const searchTitle = this.sectionTitles.search ?? Loc.getMessage('PROVIDER_SEARCH_SECTION_TITLE');

			const title = isRecent ? recentTitle : searchTitle;

			const buttonText = this.getCommonSectionButtonText();
			const styles = this.getCommonSectionStyles();

			sections.push({
				id: COMMON_SECTION_CODE,
				title,
				buttonText,
				styles,
				backgroundColor: Color.bgContentPrimary.toHex(),
			});

			if (!isEqual(this.currentSections, sections))
			{
				this.currentSections = sections;
				this.widget.setSections(this.currentSections);
			}

			items.forEach((item) => {
				item.undeselectable = this.undeselectableIds.some(([entityId, id]) => item.id === `${entityId}/${id}`);
			});

			if (!isEqual(this.currentItems, items))
			{
				this.currentItems = items;

				this.widget.setItems(this.currentItems, this.currentSections, { animate: animation });
			}

			if (isAirStyleSupported)
			{
				if (this.isCreationModeActive())
				{
					this.widget.setRightButtons([
						{
							type: 'plus',
							testId: 'ENTITY_SELECTOR_PLUS_BUTTON',
							callback: () => {
								this.createItems(this.queryText);
							},
						},
					]);
				}
				else
				{
					this.widget.setRightButtons([]);
				}
			}
		}

		isCreationModeActive()
		{
			const { canCreateWithEmptySearch, enableCreation } = this.createOptions;

			return enableCreation && (canCreateWithEmptySearch || !this.isRecentSearch());
		}

		getCommonSectionButtonText()
		{
			if (isAirStyleSupported)
			{
				return '';
			}

			if (this.isCreationModeActive())
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
						color: Color.base4.toHex(),
					},
				},
				button: {
					font: {
						size: 15,
						color: this.getIsItemCreating() ? Color.base2.toHex() : Color.accentMainLinks.toHex(),
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

			const isForbiddenTryToUnselectLast = (
				this.selectOptions.canUnselectLast === false
				&& this.currentSelectedItems.length === 1
				&& items.length === 0
			);

			if (isForbiddenTryToUnselectLast)
			{
				this.widget.setSelected(this.currentSelectedItems);

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

				if (this.shouldSetInitiallySelectedItems)
				{
					this.widget.setSelected(this.currentSelectedItems);

					this.shouldSetInitiallySelectedItems = false;
				}
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
			return this.currentSelectedItems.some(({ id }) => id === item.id);
		}

		getIsItemCreating()
		{
			return this.isItemCreating;
		}

		setIsItemCreating(isItemCreating)
		{
			this.isItemCreating = isItemCreating;

			const items = this.currentItems;

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
				this.hiddenWidget = this.widget;
				this.widget = null;
				this.handleOnEventsCallback('onViewHidden');
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
			this.handleOnEventsCallback('onViewRemoved');
		}

		handleOnEventsCallback(callbackName, ...params)
		{
			const callbackEvent = this.events[callbackName];
			if (callbackEvent)
			{
				callbackEvent(...params);
			}
		}

		onClose()
		{
			if (this.currentSelectedItems.length > 0)
			{
				this.provider.prepareResult(this.currentSelectedItems);
			}

			this.handleOnEventsCallback('onClose', this.getEntityItems());
		}

		onViewShown = () => {
			if (this.hiddenWidget)
			{
				this.widget = this.hiddenWidget;
			}
			this.handleOnEventsCallback('onViewShown', this.getEntityItems());
		};

		onWidgetClosed()
		{
			this.widget = null;
			this.handleOnEventsCallback('onWidgetClosed', this.getEntityItems());
		}

		closeOnCreation(entities)
		{
			if (this.events.onCreateBeforeClose)
			{
				this.events.onCreateBeforeClose(entities);
			}

			this.close().then(() => {
				if (this.events.onCreate)
				{
					this.events.onCreate(entities);
				}
			});
		}

		getEntityItems()
		{
			if (Array.isArray(this.currentSelectedItems))
			{
				return this.currentSelectedItems.map((item) => ({
					...item.params,
					imageUrl: item.imageUrl,
				}));
			}

			return [];
		}

		getCreateButtonItem()
		{
			return {
				title: this.getCreateButtonItemTitle(),
				type: 'button',
				unselectable: true,
				sectionCode: SERVICE_SECTION_CODE,
				params: { code: CREATE_BUTTON_CODE },
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
				title: this.searchOptions.noResultsText || BX.message('PROVIDER_SEARCH_NO_RESULTS'),
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
