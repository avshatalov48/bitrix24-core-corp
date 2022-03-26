(() =>
{
	const defaultEntities = ['user', 'im-chat', 'im-bot'];

	const defaultOptions = {
		entities: {
			'user': {
				dynamicLoad: true,
				dynamicSearch: true,
				filters: [
					{
						id: 'im.userDataFilter',
					},
				],
			},
			'im-chat': {
				dynamicLoad: true,
				dynamicSearch: true,
				options: {
					searchableChatTypes: [
						'C',
						'L',
						'O',
					],
				},
			},
			'im-bot': {
				dynamicLoad: true,
				dynamicSearch: true,
				options: {
					searchableBotTypes: [
						'H',
						'B',
						'S',
						'N',
					],
				},
			},
		}
	}

	/**
	 * @class ChatSelector
	 */
	class ChatSelector extends EntitySelector
	{
		constructor(options = {})
		{
			const ui = options.ui ? options.ui : null;
			super(ui);

			this.singleSelection = false;
			this.entities = options.entities ? options.entities : defaultEntities;

			const providerOptions = options.providerOptions ? options.providerOptions : {};
			const context = options.context ? options.context : 'IM_NEXT_SEARCH';

			this.setProvider(new ChatSelectorProvider(context, providerOptions));

			this.setEntitiesOptions(defaultOptions.entities);
		}

		setEntitiesOptions(options)
		{
			if(typeof options === 'undefined')
				return this;

			let entities = this.entities.reduce((result, entityName)=>{
				result[entityName] = options[entityName] ? options[entityName] : defaultOptions.entities[entityName];

				return result;
			}, {});

			this.provider.setOptions({entities});

			return this;
		}

		setProvider(provider)
		{
			this.provider = provider;
			this.provider.listener = this;

			this.sections.push({
				id: 'custom',
				title: BX.message("MOBILE_EXT_CHAT_SELECTOR_SECTION_CUSTOM_TITLE").toUpperCase(),
				backgroundColor: '#ffffff',
			});

			this.sections.push({
				id: 'recent',
				backgroundColor: '#ffffff',
				title: BX.message("MOBILE_EXT_CHAT_SELECTOR_SECTION_RECENT_TITLE").toUpperCase(),
			});

			this.sections.push({
				id: 'common',
				backgroundColor: '#ffffff',
			});

			this.sections.unshift({ id: "service" });
			this.ui.setSections(this.sections);
		}

		onRecentResult(items, cache = false) {
			items.forEach(item => {
				if (item.sectionCode !== 'custom')
				{
					item.sectionCode = 'recent';
				}
			});

			this.scopeFilter(items, cache);
		}

		onFetchResult(items, cache = false)
		{
			if (this.items.length > 0)
			{
				let ids = this.items.map(item => item.id);
				items = items.filter(item => !ids.includes(item.id));
				this.items = this.items.concat(items);
			}
			else
			{
				this.items = items;
			}

			this.items.forEach(item => item.sectionCode = 'common');

			if (cache === false)
			{
				this.items = this.items.filter(item => item.id !== 'loading');
			}

			this.scopeFilter(this.items, cache);
		}
	}

	window.ChatSelector = ChatSelector;
})();
