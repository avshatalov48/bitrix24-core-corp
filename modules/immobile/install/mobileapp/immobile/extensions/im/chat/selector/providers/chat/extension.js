/**
 * @module im/chat/selector/providers/chat
 */
jn.define('im/chat/selector/providers/chat', (require, exports, module) => {

	const imagePath = '/bitrix/mobileapp/immobile/extensions/im/chat/selector/providers/chat/images/';

	const defaultChatOptions = {
		itemOptions: {
			CHANNEL: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_CHANNEL_SUBTITLE_MSGVER_1'),
			},
			ANNOUNCEMENT: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_ANNOUNCEMENT_SUBTITLE'),
			},
			GROUP: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_GROUP_SUBTITLE_MSGVER_1'),
			},
			VIDEOCONF: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_VIDEOCONF_SUBTITLE'),
			},
			CALL: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_CALL_SUBTITLE'),
			},
			CRM: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_CRM_SUBTITLE'),
			},
			SONET_GROUP: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_SONET_GROUP_SUBTITLE'),
			},
			CALENDAR: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_CALENDAR_SUBTITLE'),
			},
			TASKS: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_TASKS_SUBTITLE'),
			},
			SUPPORT24_NOTIFIER: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_SUPPORT24_NOTIFIER_SUBTITLE'),
				textColor: '#0165af',
			},
			SUPPORT24_QUESTION: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_SUPPORT24_QUESTION_SUBTITLE'),
				textColor: '#0165af',
				imageUrl: imagePath + 'avatar_24_question_x3.png',
			},
			LINES: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_LINES_SUBTITLE'),
				textColor:'#0a962f',
			},
			LIVECHAT: {
				subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_LINES_SUBTITLE'),
				textColor: '#0a962f',
			},
		},
	};

	const defaultOptions = {
		entities: {
			'user': {
				itemOptions: {
					default: {
						subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_EMPLOYEE_SUBTITLE'),
					},
					extranet: {
						subtitle: BX.message('MOBILE_EXT_CHAT_SELECTOR_USER_EXTRANET_SUBTITLE'),
						textColor:'#ca8600',
					}
				}
			},
			'im-chat': defaultChatOptions,
			'im-chat-user': defaultChatOptions,
			'im-bot': {
				itemOptions: {
					default: {
						textColor: '#725acc',
					},
					network: {
						textColor: '#0a962f',
					},
					support24: {
						textColor: '#0165af',
					},
				},
			},
			'imbot-network': {
				itemOptions: {
					NETWORK: {
						textColor: '#0a962f',
					},
				},
			},
		}
	}

	const colorGray = '#333';
	const colorWhite = '#fff';
	const colorLightGray = '#b1b6bb';

	class InstantPickerCache extends BasePickerCache
	{
		constructor(id)
		{
			super(id);

			this.writeCache = BasePickerCache.debounce((items, key) => this.storage.setObject(key, {items}), 10, this)
		}
	}

	/**
	 * @class ChatProvider
	 */
	class ChatProvider extends BaseSelectorProvider
	{
		constructor(context, options = {})
		{
			super(context);

			this.context = context;
			this.options = options;
			this.customItems = options.customItems || null;
			this.minSearchSize = options.minSearchSize || 3;

			this.emptyResults = [];
			this.recentLoaded = false;
			this.searchFields = [
				'position',
				'secondName',
				'lastName',
				'name',
				'title',
			];
			this.entityWeight = {
				'user': 100,
				'im-chat': 80,
				'im-bot': 70,
				'im-chat-user': 60,
			}
			this.cache = new InstantPickerCache(this.cacheId());
		}

		static title()
		{
			return '';
		}

		setQuery(value)
		{
			this.queryString = value;
		}

		setOptions(options)
		{
			this.options = options;
			this.cache = new InstantPickerCache(this.cacheId());
		}

		cacheId()
		{
			return CommonUtils.md5({
				id: this.id(),
				context: this.context
			});
		}

		getAjaxDialog()
		{
			let entities = [];
			if (this.options.entities)
			{
				Object.keys(this.options.entities)
					.forEach(entryName => {
						let entry = this.options.entities[entryName];
						entry.id = entryName;
						entry.sort = this.entityWeight[entryName];
						entities.push(entry);
					});

				entities.sort((entry1, entry2)=> {
					if (entry1.sort > entry2.sort)
					{
						return -1;
					}
					else if (entry1.sort < entry2.sort)
					{
						return 1;
					}

					return 0;
				})
			}

			return {
				'id': 'mobile',
				'context': this.context,
				entities,
			};
		}

		processResult(query, items, excludeFields = [])
		{
			try
			{
				query = query.toLowerCase();
				let queryWords = this.splitQueryByWords(query);
				let shouldMatch = queryWords.length;

				return items.map(item => {
					let sort = this.getEntityWeight(item.params.type);
					let matchCount = 0;
					let matchedWords = [];

					if (item.params.type === 'im-chat-user')
					{
						item.sort = sort;

						return item;
					}

					if (this.searchFields.length > 0 && query)
					{
						let reverse = this.searchFields.slice(0);
						reverse.reverse().forEach(name => {

							if (excludeFields.includes(name))
							{
								return;
							}

							let field = item[name];

							if (field)
							{
								let fieldWords = this.splitQueryByWords(field);

								let result = fieldWords.filter(word => {
									let items = queryWords.filter(queryWord => {
										let match = word.indexOf(queryWord) === 0 && !matchedWords.includes(queryWord);
										if (match)
										{
											matchedWords.push(queryWord);
										}

										return match;
									})

									return items.length > 0;
								});

								if (result.length > 0)
								{
									sort += this.searchFields.indexOf(name) + 1;
								}
							}
						})
					}
					else
					{
						sort = 1;
					}

					item.sort = (matchedWords.length >= shouldMatch) ? sort + matchCount: -1;
					return item;
				})
				.filter(item => item.sort >= 0)
				.sort((item1, item2) =>
				{
					if (item1.sort > item2.sort)
					{
						return -1
					}

					if (item1.sort < item2.sort)
					{
						return 1
					}

					return 0
				})
			}
			catch (e)
			{
				console.error(e);
				return items;
			}
		}

		doSearch(query)
		{
			this.queryString = query.trim();

			let items =
				this.cache.get('recent')
					.concat(this.cache.get('items'))
			;
			items = items.filter(item => {
				return Object.keys(this.options.entities).includes(item.params.entityId);
			});

			let cachedItems = this.processResult(query, items);

			let result = cachedItems.filter((item, index, self) => {
				return self.findIndex(item1 => item.id === item1.id) === index;
			});

			if (this.emptyResults.includes(query))
			{
				this.listener.onFetchResult([]);
				return;
			}

			if (query.length < this.minSearchSize)//without search loader
			{
				this.listener.onFetchResult(result);
				return;
			}

			result.push(this.getSearchingItem());
			this.listener.onFetchResult(result, true);

			const queryWords = this.splitQueryByWords(query);

			this.sendRequest(query, queryWords);
		}

		sendRequest(query, queryWords)
		{
			BX.ajax.runAction('ui.entityselector.doSearch', {
					json: {
						dialog: this.getAjaxDialog(),
						searchQuery: {
							'queryWords': queryWords,
							'query': query,
							'dynamicSearchEntities': []
						}
					},
					getParameters: {context: this.context}
				})
				.then(response => {
					let itemsFromCache =
						this.cache.get('recent')
							.concat(this.cache.get('items'))
					;
					itemsFromCache = this.processResult(query, itemsFromCache);
					const items = this.prepareItems(response.data.dialog.items);

					itemsFromCache.forEach(item => {
						const foundItem =  items.find(internalItem => internalItem.title === item.title);
						if (typeof foundItem === 'undefined')
						{
							items.push(item);
						}
					});

					if (items.length === 0)
					{
						this.emptyResults.push(query);
					}

					const cachedItems = items.filter(item => {
						return item.params.entityId === 'user'
							|| item.params.entityId === 'im-chat'
							|| item.params.entityId === 'im-bot'
						;
					});

					this.cache.save(cachedItems, 'items', { unique: true });
					if (query.trim() === this.queryString)
					{
						let sortedItems = this.processResult(query, items);
						this.listener.onFetchResult(sortedItems);
					}
				})
				.catch(e => {
					console.error(e);
					this.listener.onFetchResult([]);
				});
		}


		loadRecent(justLoad = false)
		{
			if (justLoad === false)
			{
				let recentItems = this.cache.get('recent', true);

				const hasRecentCache = recentItems.length > 0;

				recentItems = recentItems.filter(item => {
					return Object.keys(this.options.entities).includes(item.params.entityId);
				});

				if (this.customItems)
				{
					recentItems = [
						...this.customItems,
						...recentItems,
					];
				}

				if (!hasRecentCache)
				{
					recentItems.push(this.getLoadingItem());
				}

				this.listener.onRecentResult(recentItems, false);
				if (this.recentLoaded === true)
				{
					return;
				}
			}

			BX.ajax.runAction('ui.entityselector.load', {
				json: {
					dialog: this.getAjaxDialog(),
				},
				getParameters: {
					context: this.context,
				},
			})
				.then(response => {
					let recentItems = this.getRecentFromResponse(response.data.dialog);
					let items = this.prepareItems(recentItems);

					const hasRecentCache = this.cache.get('recent').length > 0;
					this.cache.save(items, 'recent', { saveDisk: true });

					if(justLoad === false)
					{
						if (!hasRecentCache)
						{
							if (this.customItems)
							{
								items = [
									...this.customItems,
									...items,
								];
							}

							this.listener.onRecentResult(items, false);
						}

						this.recentLoaded = true;
					}
				});
		}

		getRecentFromResponse(dialog)
		{
			return dialog.recentItems
				.map(pair => {
					let entityId = pair[0];
					let id = pair[1];
					return dialog.items.find(item => item.entityId === entityId && item.id === id);
				})
				.filter(item => typeof item !== 'undefined');
		}

		prepareSelected(selected)
		{
			if (typeof selected === 'object')
			{
				return Object.keys(selected)
					.reduce((result, entityId) =>
					{
						let groupSelected = selected[entityId].map(original => {
							let item = Object.assign({}, original);
							if (typeof item.id !== 'undefined')
							{
								item.id = entityId + '/' + item.id;
							}

							return item;
						});

						return result.concat(groupSelected);
					}, [])
			}

			return {};
		}

		filterDuplicateChatUsersItems(itemList)
		{
			const uniqItemList = [];
			const uniqItemIndexList = {};
			if (Array.isArray(itemList))
			{
				itemList.forEach((item, index) => {
					if (item.entityId && item.entityId !== 'im-chat-user')
					{
						uniqItemIndexList[item.id] = index;
					}
					else if (!(item.id in uniqItemIndexList))
					{
						uniqItemIndexList[item.id] = index;
					}
				});
			}
			for (let key in uniqItemIndexList) {
				if(uniqItemIndexList.hasOwnProperty(key)){
					uniqItemList.push(itemList[uniqItemIndexList[key]])
				}
			}

			return uniqItemList;
		}
		prepareResult(items)
		{
			let recentItems = [];

			let result = items.reduce((result, item) => {
				let [entityId, id] = item.id.split('/');
				if (entityId && id)
				{
					recentItems.push({id,entityId});

					if (entityId === 'im-chat' || entityId === 'im-chat-user')
					{
						id = 'chat' + id;
					}

					result.push({
						id,
						name: item.title,
						description: item.subtitle,
						avatar: item.imageUrl,
						color: item.color,
						customData: item.params.customData || [],
					});
				}

				return result;
			}, []);

			items = items.filter(item => {
				const entityType = item.id.split('/')[0];
				return entityType !== 'custom';
			});

			if (items.length > 0)
			{
				this.updateRecentCache(items);

				this.addRecentItems(recentItems).then(() => this.loadRecent(true));
			}

			if (this.singleSelection)
			{
				return result[0];
			}

			return result;
		}

		addRecentItems(recentItems)
		{
			return new Promise((resolve, reject) => {
				if (Array.isArray(recentItems) && recentItems.length > 0)
				{
					BX.ajax.runAction('ui.entityselector.saveRecentItems', {
						json: {
							dialog: this.getAjaxDialog(),
							recentItems
						},
						getParameters: {
							context: this.context
						},
					})
						.then(result => resolve(result))
						.catch(e => reject(e))
				}
				else
				{
					reject();
				}
			})
		}

		updateRecentCache(items)
		{
			let recent = this.cache.get('recent', true);
			if (recent.length > 0)
			{
				let lastSelectedIds = items.map(item => item.id);
				let lastSelectedItems = [];

				recent = recent.filter(item => {
					if (lastSelectedIds.includes(item.id))
					{
						lastSelectedItems.push(item);
						return false;
					}

					return true;
				});

				recent.unshift(...lastSelectedItems);

				this.cache.save(recent, 'recent', { saveDisk: true });
			}
		}

		id()
		{
			return 'chat';
		}

		prepareItemForDrawing(entity)
		{
			let item = {
				title: entity.title,
				sectionCode: 'chat',
				height: 64,
				color: this.getEntityOption(entity, 'textColor', colorLightGray),
				styles:{
					title:{ font:{ size:16 } },
					subtitle: {},
				},
				useLetterImage: true,
				id: `${entity.entityId}/${entity.id}`,
				imageUrl: entity.avatar ? entity.avatar : this.getEntityOption(entity, 'imageUrl', ''),
				params: {
					entityId: entity.entityId,
					entityType: entity.entityType,
					id: entity.id,
					title: entity.title,
					customData: entity.customData ? entity.customData : {},
					type: entity.entityId,
				}
			};

			if (entity.entityId === 'user')
			{
				if (entity.badges)
				{
					let itIsYouBadge = entity.badges.find(badge => badge.id === 'IT_IS_YOU');
					if (itIsYouBadge)
					{
						item.title += ' (' + String(itIsYouBadge.title).toLowerCase() + ')';
					}
				}

				if(entity.entityType === 'extranet')
				{
					item.styles.title.font.color = this.getEntityOption(entity, 'textColor', colorGray);
					item.subtitle = this.getEntityOption(entity, 'subtitle', '');
				}
				else
				{
					item.subtitle = entity.customData.position
						? entity.customData.position
						: this.getEntityOption(entity, 'subtitle', colorGray)
					;
				}

				item.name = entity.customData.name;
				item.lastName = entity.customData.lastName;
				item.secondName = entity.customData.secondName;
				item.shortTitle = entity.customData.name;
				item.position = entity.customData.position;
				item.color = entity.avatar && entity.avatar.indexOf('upload') !== -1 ? colorWhite : entity.customData['imUser'].COLOR;
			}
			else if (entity.entityId === 'im-chat' || entity.entityId === 'im-chat-user')
			{
				item.subtitle = this.getEntityOption(entity, 'subtitle', '');
				item.shortTitle = entity.title;
				item.styles.title.font.color = this.getEntityOption(entity, 'textColor', colorGray);
				item.color = entity.avatar && entity.avatar.indexOf('upload') !== -1 ? colorWhite : entity.customData['imChat'].COLOR;

				const generalChatId = Number(BX.componentParameters.get('IM_GENERAL_CHAT_ID', 0));
				if (entity.id === generalChatId)
				{
					item.imageUrl = imagePath + 'avatar_general_x3.png';
				}
			}
			else if (entity.entityId === 'im-bot')
			{
				item.subtitle = entity.customData['imUser'].WORK_POSITION;
				item.shortTitle = entity.title;
				item.styles.title.font.color = this.getEntityOption(entity, 'textColor', colorGray);
				item.color = entity.avatar && entity.avatar.indexOf('upload') !== -1 ? colorWhite : entity.customData['imUser'].COLOR;
			}
			else if (entity.entityId === 'imbot-network')
			{
				item.color = entity.avatar && entity.avatar.indexOf('upload') !== -1 ? colorWhite : entity.avatarOptions.color;
				item.styles.title.font.color = this.getEntityOption(entity, 'textColor', colorGray);
				item.subtitle = entity.subtitle;
			}

			return item;
		}

		getEntityOption(entity, option, defaultValue = false)
		{
			let entityOptions = defaultOptions.entities[entity.entityId];
			if (!entityOptions)
			{
				return defaultValue;
			}

			let itemOptions = entityOptions.itemOptions;
			if (!itemOptions)
			{
				return defaultValue;
			}

			let entityTypeOptions = itemOptions[entity.entityType];
			if (!entityTypeOptions)
			{
				if (itemOptions.default && itemOptions.default[option])
				{
					return itemOptions.default[option];
				}

				return defaultValue;
			}

			let entityTypeOption = entityTypeOptions[option];
			if (entityTypeOption)
			{
				return entityTypeOption;
			}

			return defaultValue;
		}

		getSearchingItem()
		{
			return {
				id: 'loading',
				title: BX.message("MOBILE_EXT_CHAT_SELECTOR_SEARCHING_ITEM"),
				type: 'loading',
				unselectable: true,
				sectionCode: 'common',
			}
		}

		getLoadingItem()
		{
			return {
				id: 'loading',
				title: BX.message("MOBILE_EXT_CHAT_SELECTOR_LOADING_ITEM"),
				type: 'loading',
				unselectable: true,
				sectionCode: 'common',
			}
		}

		splitQueryByWords(query)
		{
			const clearedQuery =
				query
					.replace(/\(/g, ' ')
					.replace(/\)/g, ' ')
					.replace(/\[/g, ' ')
					.replace(/]/g, ' ')
					.replace(/\{/g, ' ')
					.replace(/}/g, ' ')
					.replace(/</g, ' ')
					.replace(/>/g, ' ')
					.replace(/-/g, ' ')
					.replace(/#/g, ' ')
					.replace(/"/g, ' ')
					.replace(/'/g, ' ')
					.replace('/\s\s+/', ' ')
			;

			return clearedQuery
				.toLowerCase()
				.split(' ')
				.filter(word => word !== '')
			;
		}
	}

	module.exports = { ChatProvider };
});
