(() => {
	/**
	 * @class BaseSelectorEntity
	 */
	class BaseSelectorEntity
	{
		static make(props)
		{
			let {
				provider,
				searchOptions,
				createOptions,
				widgetParams,
				allowMultipleSelection
			} = props;

			provider = provider || {};
			searchOptions = searchOptions || {};
			createOptions = createOptions || {};
			widgetParams = widgetParams || {};

			if (!provider['context'])
			{
				provider.context = this.getContext();
			}

			provider.options = {
				entities: {
					[this.getEntityId()]: {
						options: provider.options || {},
						searchable: true,
						dynamicLoad: true,
						dynamicSearch: true
					}
				}
			};

			if (!searchOptions['startTypingText'])
			{
				searchOptions.startTypingText = this.getStartTypingText();
			}

			if (!searchOptions['searchPlaceholderWithCreation'])
			{
				searchOptions.searchPlaceholderWithCreation = this.getSearchPlaceholderWithCreation();
			}

			if (!searchOptions['searchPlaceholderWithoutCreation'])
			{
				searchOptions.searchPlaceholderWithoutCreation = this.getSearchPlaceholderWithoutCreation();
			}

			if (!createOptions.hasOwnProperty('enableCreation'))
			{
				createOptions.enableCreation = this.isCreationEnabled();
			}

			if (!this.isCreationEnabled())
			{
				createOptions.enableCreation = false;
			}

			if (!createOptions['createText'])
			{
				createOptions.createText = this.getCreateText();
			}

			if (!createOptions['creatingText'])
			{
				createOptions.creatingText = this.getCreatingText();
			}

			if (!createOptions['handler'])
			{
				createOptions.handler = this.getCreateEntityHandler(
					provider.options.entities[this.getEntityId()].options
				);
			}

			if (!widgetParams['title'])
			{
				widgetParams.title = this.getTitle();
			}

			if (!widgetParams.hasOwnProperty('useLargeTitleMode'))
			{
				widgetParams.useLargeTitleMode = true;
			}

			if (!BX.type.isBoolean(allowMultipleSelection))
			{
				allowMultipleSelection = false;
			}

			return new EntitySelectorWidget({
				entityId: this.getEntityId(),
				provider,
				searchOptions,
				createOptions,
				allowMultipleSelection,
				widgetParams,
				events: props.events || {},
				initSelectedIds: props.initSelectedIds || []
			});
		}

		static getEntityId()
		{
			throw new Error('Method must be implemented');
		}

		static getContext()
		{
			return null;
		}

		static getStartTypingText()
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

		static isCreationEnabled()
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
	}

	this.BaseSelectorEntity = BaseSelectorEntity;
})();
