(() => {

	const require = (ext) => jn.require(ext);

	const { EntitySelectorWidget } = require('selector/widget');

	/**
	 * @class BaseSelectorEntity
	 * @abstract
	 */
	class BaseSelectorEntity
	{
		static make(props)
		{
			let {
				entityIds,
				provider,
				searchOptions,
				createOptions,
				widgetParams,
				allowMultipleSelection,
				closeOnSelect,
			} = props;

			if (!Array.isArray(entityIds) || entityIds.length === 0)
			{
				entityIds = [this.getEntityId()];
			}

			provider = this.prepareProvider(provider, entityIds);
			widgetParams = this.prepareWidgetParams(widgetParams);
			searchOptions = this.prepareSearchOptions(searchOptions);
			createOptions = this.prepareCreateOptions(createOptions, provider.options);

			if (!BX.type.isBoolean(allowMultipleSelection))
			{
				allowMultipleSelection = false;
			}

			if (!BX.type.isBoolean(closeOnSelect))
			{
				closeOnSelect = true;
			}

			const entitySelectorWidget = new EntitySelectorWidget({
				entityIds,
				provider,
				searchOptions,
				createOptions,
				selectOptions: props.selectOptions || {},
				canUseRecent: props.canUseRecent,
				allowMultipleSelection,
				closeOnSelect,
				widgetParams,
				events: props.events || {},
				initSelectedIds: props.initSelectedIds || [],
				returnKey: BaseSelectorEntity.getReturnKey(),
			});

			entitySelectorWidget.provider.setHandlerPrepareItem(this.prepareItemForDrawing);

			return entitySelectorWidget;
		}

		static getEntityId()
		{
			throw new Error('Method must be implemented');
		}

		static prepareProvider(provider, entityIds)
		{
			provider = provider || {};

			if (!provider['context'])
			{
				provider.context = this.getContext();
			}

			provider.options = {
				entities: this.getEntitiesOptions(provider.options, entityIds),
				useRawResult: this.useRawResult(),
			};

			return provider;
		}

		static prepareWidgetParams(widgetParams)
		{
			widgetParams = widgetParams || {};

			if (!widgetParams['title'])
			{
				widgetParams.title = this.getTitle();
			}

			return widgetParams;
		}

		static prepareSearchOptions(searchOptions)
		{
			searchOptions = searchOptions || {};

			if (!searchOptions['startTypingText'])
			{
				searchOptions.startTypingText = this.getStartTypingText();
			}

			if (!searchOptions['startTypingWithCreationText'])
			{
				searchOptions.startTypingWithCreationText = this.getStartTypingWithCreationText();
			}

			if (!searchOptions['searchPlaceholderWithCreation'])
			{
				searchOptions.searchPlaceholderWithCreation = this.getSearchPlaceholderWithCreation();
			}

			if (!searchOptions['searchPlaceholderWithoutCreation'])
			{
				searchOptions.searchPlaceholderWithoutCreation = this.getSearchPlaceholderWithoutCreation();
			}

			if (!searchOptions['searchFields'])
			{
				searchOptions.searchFields = this.getSearchFields();
			}

			if (!searchOptions['entityWeight'])
			{
				searchOptions.entityWeight = this.getEntityWeight();
			}

			return searchOptions;
		}

		static prepareCreateOptions(createOptions, providerOptions)
		{
			createOptions = createOptions || {};

			if (!createOptions.hasOwnProperty('enableCreation'))
			{
				createOptions.enableCreation = this.isCreationEnabled(providerOptions);
			}

			if (!this.isCreationEnabled(providerOptions))
			{
				createOptions.enableCreation = false;
			}

			if (!createOptions.hasOwnProperty('closeAfterCreation'))
			{
				createOptions.closeAfterCreation = true;
			}

			if (!createOptions.hasOwnProperty('canCreateWithEmptySearch'))
			{
				createOptions.canCreateWithEmptySearch = this.canCreateWithEmptySearch();
			}

			if (!createOptions['createText'])
			{
				createOptions.createText = this.getCreateText();
			}

			if (!createOptions['creatingText'])
			{
				createOptions.creatingText = this.getCreatingText();
			}

			if (createOptions.enableCreation && !createOptions['handler'])
			{
				createOptions.handler = this.getCreateEntityHandler(
					providerOptions.entities[0].options,
				);
			}

			return createOptions;
		}

		static getEntitiesOptions(providerOptions, entityIds)
		{
			return [{
				id: entityIds[0],
				options: providerOptions || {},
				searchable: true,
				dynamicLoad: true,
				dynamicSearch: true,
			}];
		}

		static getContext()
		{
			return null;
		}

		static getStartTypingText()
		{
			return null;
		}

		static getStartTypingWithCreationText()
		{
			return null;
		}

		static getSearchPlaceholderWithCreation()
		{
			return null;
		}

		static getSearchPlaceholderWithoutCreation()
		{
			return null;
		}

		/**
		 * Returns field names to search in entity item and its custom data.
		 * The last field in array has the highest priority.
		 *
		 * @returns {string[]}
		 */
		static getSearchFields()
		{
			return [
				'subtitle',
				'title',
			];
		}

		static getEntityWeight()
		{
			return null;
		}

		static isCreationEnabled(providerOptions)
		{
			return false;
		}

		static canCreateWithEmptySearch()
		{
			return false;
		}

		static getCreateText()
		{
			return null;
		}

		static getCreatingText()
		{
			return null;
		}

		static getCreateEntityHandler(providerOptions)
		{
			return null;
		}

		static getTitle()
		{
			return null;
		}

		static getReturnKey()
		{
			return 'done';
		}

		static prepareItemForDrawing()
		{
			return null;
		}

		static useRawResult()
		{
			return false;
		}
	}

	this.BaseSelectorEntity = BaseSelectorEntity;
})();
