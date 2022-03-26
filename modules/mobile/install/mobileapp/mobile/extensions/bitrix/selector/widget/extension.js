(() =>
{
	const CREATE_BUTTON_CODE = 'create';

	/**
	 * @class EntitySelectorWidget
	 */
	class EntitySelectorWidget
	{
		constructor({
			entityId,
			provider,
			searchOptions,
			createOptions,
			widgetParams,
			allowMultipleSelection,
			events,
			initSelectedIds
		})
		{
			this.queryText = '';
			this.isItemCreating = false;
			this.currentItems = [];
			this.currentSelectedItems = [];

			this.entityId = entityId;
			this.searchOptions = searchOptions || {};
			this.createOptions = createOptions || {};
			this.widgetParams = widgetParams || {};
			this.allowMultipleSelection = allowMultipleSelection !== false;
			this.events = events || {};
			this.initSelectedIds = initSelectedIds || [];
			this.initSelectedIds = this.initSelectedIds.map(id => id.toString());

			this.setupProvider(provider);
		}

		setupProvider(provider)
		{
			this.provider = new CommonSelectorProvider(
				provider.context || null,
				provider.options || {}
			);
			this.provider.setPreselectedItems(
				this.initSelectedIds.map(initSelectedId => [this.entityId, initSelectedId])
			);
			this.provider.setListener({
				onFetchResult: this.onProviderFetchResult.bind(this),
				onRecentResult: this.onProviderRecentResult.bind(this)
			});
			this.provider.processResult = (query, items, excludeFields = []) => items;
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

		show({widgetParams} = {}, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				if (this.widget)
				{
					return resolve();
				}

				parentWidget = (parentWidget || PageManager);
				parentWidget.openWidget(
					'selector',
					{
						...(widgetParams || this.widgetParams),
						onReady: (widget) => {
							this.widget = widget;

							if (typeof this.widget.setPlaceholder === 'function')
							{
								const placeholder = this.getSearchPlaceholder();
								if (placeholder)
								{
									this.widget.setPlaceholder(placeholder);
								}
							}

							this.widget.setRightButtons([{
								name: BX.message('PROVIDER_WIDGET_DONE'),
								callback: () => this.close()
							}]);
							this.widget.allowMultipleSelection(this.allowMultipleSelection);
							this.provider.loadRecent();
							this.widget.setListener((eventName, data) => {
								const callbackName = eventName + 'Listener';

								if (typeof this[callbackName] === 'function')
								{
									this[callbackName].apply(this, [data])
								}
							});

							resolve();
						},
						onError: error => reject(error)
					}
				);
			});
		}

		// region widget event listeners

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param text
		 */
		onListFillListener({text})
		{
			this.queryText = text;

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
		onItemSelectedListener({text, item})
		{
			if (!(item && item.hasOwnProperty('params') && item.params.hasOwnProperty('code')))
			{
				return;
			}

			const buttonCode = item.params.code;

			switch (buttonCode)
			{
				case CREATE_BUTTON_CODE:
					if (
						!this.createOptions.enableCreation
						|| !this.createOptions.handler
						|| this.getIsItemCreating()
					)
					{
						return;
					}

					this.setIsItemCreating(true);

					this.createOptions.handler(text).then((item) => {
						if (item)
						{
							if (!this.provider.isInRecentCache(item))
							{
								this.provider.addToRecentCache(item);
							}

							const preparedItem = this.provider.prepareItemForDrawing(item);
							this.provider.prepareResult([preparedItem]);

							if (this.allowMultipleSelection)
							{
								if (!this.isInSelected(preparedItem))
								{
									this.setSelected([
										...[preparedItem],
										...this.currentSelectedItems
									]);
								}
								this.setIsItemCreating(false);
								this.resetQuery();
							}
							else
							{
								if (!this.isInSelected(preparedItem))
								{
									this.setSelected([preparedItem]);
								}
								void this.close();
							}
						}
					});
					break;
			}
		}

		/**
		 * Specific method call from widget.setListener().
		 *
		 * @param text
		 * @param scope
		 * @param items
		 */
		onSelectedChangedListener({text, scope, items})
		{
			this.currentSelectedItems = items;

			if (!this.allowMultipleSelection)
			{
				void this.close();
			}
		}

		/**
		 * Specific method call from widget.setListener().
		 */
		onViewHiddenListener()
		{
			this.onViewHidden();
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
			this.onViewHidden();
		}

		// endregion

		// region provider event handlers

		onProviderRecentResult(items)
		{
			const defaultItems = [
				{
					title: this.searchOptions.startTypingText || BX.message('PROVIDER_WIDGET_START_TYPING_TO_SEARCH'),
					type: 'button',
					unselectable: true
				}
			];

			this.setItems(items.length ? items : defaultItems);

			if (items.length && this.initSelectedIds.length)
			{
				this.setSelected(
					this.initSelectedIds.reduce((result, initSelectedId) => {
						const selectedItems = items.filter(item => {
							return (
								item.params.type === this.entityId
								&& item.params.id.toString() === initSelectedId.toString()
							)
						});
						if (
							selectedItems.length
							&& (
								this.allowMultipleSelection
								|| result.length === 0
							)
						)
						{
							result.push(selectedItems[0]);
						}
						return result;
					}, [])
				);
			}
		}

		onProviderFetchResult(items)
		{
			if (this.provider.queryString !== this.queryText)
			{
				return;
			}

			if (this.createOptions.enableCreation)
			{
				items.unshift(this.getCreateButtonItem());
			}

			this.setItems(items);
		}

		// endregion

		setItems(items)
		{
			if (!this.widget)
			{
				return;
			}

			this.currentItems = items;
			this.widget.setItems(items);
		}

		setSelected(items)
		{
			if (!this.widget)
			{
				return;
			}

			this.currentSelectedItems = items;
			this.widget.setSelected(items);
		}

		isInSelected(item)
		{
			return (this.currentSelectedItems.find(selectedItem => selectedItem.id === item.id) !== undefined);
		}

		getIsItemCreating()
		{
			return this.isItemCreating;
		}

		setIsItemCreating(isItemCreating)
		{
			this.isItemCreating = isItemCreating;

			this.setItems(
				this.currentItems.map((item) => {
					if (item.params && item.params.code === CREATE_BUTTON_CODE)
					{
						item.title = this.getCreateButtonItemTitle();
					}

					return item;
				})
			);
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

		extractEntityItems(items)
		{
			return items.map(item => item.params);
		}

		getCreateButtonItem()
		{
			return {
				title: this.getCreateButtonItemTitle(),
				type: 'button',
				unselectable: true,
				params: {'code': CREATE_BUTTON_CODE},
			};
		}

		getCreateButtonItemTitle()
		{
			return (
				this.isItemCreating
					? (this.createOptions.creatingText || BX.message('PROVIDER_WIDGET_CREATING_ITEM'))
					: (this.createOptions.createText || BX.message('PROVIDER_WIDGET_CREATE_ITEM'))
			);
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
					this.widget = null;
					resolve();
				});
			});
		}
	}

	this.EntitySelectorWidget = EntitySelectorWidget;
})();
