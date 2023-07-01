/**
 * @module im/chat/selector/chat
 */
jn.define('im/chat/selector/chat', (require, exports, module) => {
	const { ChatProvider } = require('im/chat/selector/providers/chat');

	const defaultEntities = ['user', 'im-chat', 'im-bot', 'im-chat-user'];

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
					fillDialogWithDefaultValues: false,
				},
			},
			'im-chat-user': {
				dynamicLoad: true,
				dynamicSearch: true,
				options: {
					searchableChatTypes: [
						'C',
						'O',
					],
					fillDialogWithDefaultValues: false,
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
					fillDialogWithDefaultValues: false,
				},
			},
			'imbot-network' : {
				dynamicSearch: true,
				options: {
					filterExistingLines: true,
				}
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
			this.isNetworkSearchEnabled = false;
			this.isNetworkSearchAvailable = options.isNetworkSearchAvailable ? options.isNetworkSearchAvailable : false;

			const providerOptions = options.providerOptions ? options.providerOptions : {};
			const context = options.context ? options.context : 'IM_NEXT_SEARCH';

			this.createSearchingSections();
			this.setProvider(new ChatProvider(context, providerOptions));

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
				backgroundColor: '#f6f7f8',
			});

			this.sections.push({
				id: 'recent',
				backgroundColor: '#f6f7f8',
				title: BX.message("MOBILE_EXT_CHAT_SELECTOR_SECTION_RECENT_TITLE").toUpperCase(),
			});

			this.sections.push(this.commonSection.getUiSection());
			this.sections.push(this.commonUserSection.getUiSection());
			this.sections.push(this.networkSection.getUiSection());

			this.sections.unshift({ id: "service" });
			this.ui.setSections(this.sections);
		}

		createSearchingSections()
		{
			this.commonSection = new Section({
				id: 'common',
				defaultSize: 20,
				maxSize: 50,
				uiSection: {
					id: 'common',
					title: BX.message("MOBILE_EXT_CHAT_SELECTOR_SECTION_COMMON_TITLE").toUpperCase(),
					backgroundColor: '#f6f7f8',
				},
			});

			this.commonUserSection = new Section({
				id: 'common-chat-user',
				defaultSize: 5,
				maxSize: 15,
				uiSection: {
					id: 'common-chat-user',
					title: BX.message("MOBILE_EXT_CHAT_SELECTOR_SECTION_COMMON_CHAT_USER_TITLE").toUpperCase(),
					backgroundColor: '#f6f7f8',
				},
			});

			this.networkSection = new Section({
				id: 'network',
				defaultSize: 20,
				maxSize: 50,
				uiSection: {
					id: 'network',
					title: BX.message("MOBILE_EXT_CHAT_SELECTOR_SECTION_NETWORK_TITLE").toUpperCase(),
					backgroundColor: '#f6f7f8',
				},
			});
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
			if (this.provider.queryString !== this.query)
			{
				return;
			}

			this.items = items;

			this.items.forEach(item => this.addSectionCodeForItem(item));

			if (cache === false)
			{
				this.items = this.items.filter(item => item.id !== 'loading');
			}
			this.groupSections(this.items);

			this.scopeFilter(this.getResultedItems(cache), cache);
		}

		onItemSelected(data) {
			if (this.singleSelection && data.item.type === 'info')
			{
				this.ui.close(() => this.onResult(this.provider.prepareResult([data.item])));
			}
		}

		onSearchSectionButtonClick(data)
		{
			this.updateSectionsItems(data.id);
		}

		onClickShowMore(data)
		{
			this.updateSectionsItems(data.sectionCode);
		}

		onClickShowNetwork()
		{
			this.entities = ['user', 'im-chat', 'im-bot', 'im-chat-user', 'imbot-network'];
			this.setEntitiesOptions(defaultOptions.entities);
			this.isNetworkSearchEnabled = true;
			this.provider.emptyResults = []; //to re-execute the query if there are no local results
			this.provider.doSearch(this.query);
		}

		onSearchNetworkItemSelected(data)
		{
			const lineId = data.params.id;
			BX.rest.callBatch({
				network_join: {
					method: 'imopenlines.network.join',
					params: {
						CODE: lineId
					}
				},
			},
				(result) => {
					data.id = 'im-bot/' + result.network_join.data();
					data.params.id = result.network_join.data();
					data.sectionCode = 'common';
					this.ui.onSearchItemSelected(data);
			})
		}

		/**
		 *
		 * @param {string} changedSection
		 */
		updateSectionsItems(changedSection)
		{
			switch (changedSection)
			{
				case 'common':
					this.commonSection.switchState();
					break;
				case 'common-chat-user':
					this.commonUserSection.switchState();
					break;
				case 'network':
					this.networkSection.switchState();
					break;
			}

			this.updateList(this.getResultedItems());
		}

		groupSections(itemList)
		{
			this.commonSection.clear();
			this.commonUserSection.clear();
			this.networkSection.clear();

			for (const item of itemList)
			{
				if (item.id === 'loading')
				{
					this.commonSection.addItem(item);
					continue;
				}
				if (item.params.entityId === 'im-chat-user')
				{
					this.commonUserSection.addItem(item);
					continue;
				}
				if (item.params.entityId === 'imbot-network')
				{
					this.networkSection.addItem(item);
					continue;
				}
				this.commonSection.addItem(item);
			}
		}

		getButtonSearchNetwork()
		{
			return {
				id: 'show-network',
				title: BX.message("MOBILE_EXT_CHAT_SELECTOR_BUTTON_SEARCH_NETWORK"),
				type: 'button',
				sectionCode: 'network',
				styles: {
					title: {
						font:{
							color: '#3bc8f5'
						}
					}
				},

			}
		}

		addSectionCodeForItem(item)
		{
			if (!item.params)
			{
				item.sectionCode = 'common';
				return;
			}

			switch (item.params.entityId)
			{
				case 'im-chat-user':
					item.sectionCode = 'common-chat-user';
					return;
				case 'imbot-network':
					item.sectionCode = 'network';
					return;
				default:
					item.sectionCode = 'common';
					return;
			}
		}

		/**
		 * @return {Object[]}
		 */
		getResultedItems(cache = false)
		{
			let result = [
				...this.commonSection.getItems(),
				...this.commonUserSection.getItems(),
			];

			if(this.isNetworkSearchAvailable && !this.isNetworkSearchEnabled)
			{
				if(!cache && this.provider.queryString.length >= this.provider.minSearchSize)
				{
					result.push(this.getButtonSearchNetwork());
				}
			}
			else
			{
				result = result.concat(this.networkSection.getItems());
			}

			return result;
		}
	}

	class Section
	{
		/**
		 * @param {Object} options
		 * @param {string} options.id
		 * @param {number} options.defaultSize
		 * @param {number} options.maxSize
		 * @param {Object} options.uiSection
		 */
		constructor(options)
		{
			this.id = options.id;
			this.defaultSize = options.defaultSize;
			this.maxSize = options.maxSize;
			this.uiSection = options.uiSection;

			this.isOpen = false;
			this.itemList = [];
		}

		getItems()
		{
			const size = this.isOpen ? this.maxSize : this.defaultSize;
			const result =
				this.itemList.length < size
					? this.itemList
					: this.itemList.slice(0, size)
			;

			if(Application.getApiVersion() < 44 && this.itemList.length >= size && !this.isOpen)
			{
				result.push(this.getButtonMore(this.uiSection.id));

				return result;
			}

			this.setButtonTextBySize(size);

			return result;
		}

		switchState()
		{
			this.isOpen = !this.isOpen;
		}

		/**
		 * @param {number} size
		 */
		setButtonTextBySize(size)
		{
			if(this.isOpen)
			{
				this.uiSection.buttonText = BX.message("MOBILE_EXT_CHAT_SELECTOR_TITLE_BUTTON_LESS").toUpperCase();
				return;
			}

			if(this.itemList.length >= size)
			{
				this.uiSection.buttonText = BX.message("MOBILE_EXT_CHAT_SELECTOR_TITLE_BUTTON_MORE").toUpperCase();
				return;
			}

			this.uiSection.buttonText = '';
		}

		/**
		 * @param {string} sectionCode
		 * @return {{sectionCode, id: string, title: string, type: string}}
		 */
		getButtonMore(sectionCode)
		{
			return {
				id: 'show-more',
				title: BX.message("MOBILE_EXT_CHAT_SELECTOR_BUTTON_MORE"),
				type: 'button',
				sectionCode: sectionCode,
			}
		}

		getUiSection()
		{
			return this.uiSection;
		}

		/**
		 * @param {Object} item
		 */
		addItem(item)
		{
			this.itemList.push(item);
		}

		clear()
		{
			this.itemList = [];
		}
	}

	module.exports = { ChatSelector };
});
