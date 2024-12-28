/**
 * @module selector/providers/common
 */
jn.define('selector/providers/common', (require, exports, module) => {
	const { Loc } = require('loc');
	const { withCurrentDomain } = require('utils/url');
	const { mergeImmutable } = require('utils/object');
	const { uniqBy, unique } = require('utils/array');
	const { debounce } = require('utils/function');
	const { stringify } = require('utils/string');
	const { BasePickerCache } = require('selector/utils/picker-cache');
	const { BaseSelectorProvider } = require('selector/providers/base');
	const { getEntityColor } = require('selector/providers/common/src/entity-color');
	const { getColor, getBackgroundColorStyles } = require('layout/ui/user/empty-avatar');
	const specialChars = '!"#$%&\'()*+,-.\/:;<=>?@[\\]^_`{|}';
	const specialCharsRegExp = new RegExp(`[${specialChars}]`, 'g');

	const EMPTY_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/user/images/empty-avatar.png';
	const DEFAULT_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/fields/user/images/default-avatar.png';
	const DEFAULT_SELECTOR_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/user.png';

	const isDefaultImage = (image) => image && (
		image.includes(EMPTY_AVATAR)
		|| image.includes(DEFAULT_AVATAR)
		|| image.includes(DEFAULT_SELECTOR_AVATAR)
	);

	/**
	 * @class CommonSelectorProvider
	 */
	class CommonSelectorProvider extends BaseSelectorProvider
	{
		constructor(context, options = {})
		{
			super(context);
			this.context = context;
			this.setOptions(options);

			this.emptyResults = [];
			this.recentLoaded = false;
			this.searchFields = ['position', 'secondName', 'lastName', 'name'];
			this.entityWeight = {
				'meta-user': 100,
				user: 90,
				project: 80,
				department: 70,
			};
			this.handlerPrepareItem = null;
			this.collator = undefined;

			this.runSearchActionDebounced = debounce(this.runSearchAction, 500, this);
		}

		static title()
		{
			return '';
		}

		getAvatarImage(name)
		{
			const path = '/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/';

			return `${currentDomain}${path}${name}.png`;
		}

		getColor(entityId, entityType)
		{
			if (this.useLettersForEmptyAvatar() && entityType === 'user')
			{
				return getColor(entityId);
			}

			return this.getEntityColor(entityType);
		}

		getEntityColor(entityType)
		{
			return getEntityColor(entityType);
		}

		getColorGradient(entityId, entityType)
		{
			if (this.useLettersForEmptyAvatar() && entityType === 'user')
			{
				return getBackgroundColorStyles(entityId).backgroundColorGradient;
			}

			return null;
		}

		resetQuery()
		{
			this.queryString = '';
		}

		setQuery(value)
		{
			this.queryString = value;
		}

		setOptions(options)
		{
			options = options || {};
			options.entities = options.entities || [];

			if (!Array.isArray(options.entities))
			{
				options.entities = Object.keys(options.entities).map((entityId) => ({
					...options.entities[entityId],
					id: entityId,
				}));
			}

			this.options = options;
			this.options.useLettersForEmptyAvatar = Boolean(options.useLettersForEmptyAvatar);

			this.cache = new BasePickerCache(this.cacheId());
		}

		setSearchFields(fields)
		{
			this.searchFields = fields;
		}

		setEntityWeight(entityWeight)
		{
			this.entityWeight = entityWeight;
		}

		setHandlerPrepareItem(handlerPrepareItem)
		{
			this.handlerPrepareItem = handlerPrepareItem;
		}

		cacheId()
		{
			const context = this.context;
			const entities = this.getSortedEntities();

			return CommonUtils.md5({ context, entities });
		}

		useRawResult()
		{
			return Boolean(this.options.useRawResult);
		}

		getAjaxDialog()
		{
			return {
				id: 'mobile',
				context: this.context,
				preselectedItems: this.preselectedItems,
				entities: this.getSortedEntities(),
				recentItemsLimit: this.options.recentItemsLimit,
			};
		}

		getSortedEntities()
		{
			const entities = this.options.entities.map((entity) => ({
				...entity,
				sort: this.entityWeight[entity.id] || 0,
			}));

			entities.sort((entry1, entry2) => {
				if (entry1.sort > entry2.sort)
				{
					return -1;
				}

				if (entry1.sort < entry2.sort)
				{
					return 1;
				}

				return 0;
			});

			return entities;
		}

		getItemBaseSort(item = {})
		{
			const { params = {} } = item;
			let sort = this.getEntityWeight(params.type);

			if (params.priorityPass)
			{
				sort += 50;
			}

			return sort;
		}

		processResult(query, items, excludeFields = [])
		{
			try
			{
				query = query.toLowerCase();
				const queryWords = this.splitQueryByWords(query);
				const uniqueQueryWords = unique(queryWords);

				return items.map((item) => {
					let sort = this.getItemBaseSort(item);

					const matchedWords = [];

					if (this.searchFields.length > 0 && query)
					{
						let fieldWeight = 0;
						const reverse = [...this.searchFields].reverse();

						reverse.forEach((name) => {
							if (excludeFields.includes(name))
							{
								return;
							}

							let field = item[name];
							if (!field && item.params)
							{
								const { customData } = item.params;
								if (customData)
								{
									field = customData[name];
								}
							}

							if (field && typeof field === 'string')
							{
								const fieldWords = this.splitQueryByWords(field);

								const result = fieldWords.filter((word) => {
									const items = queryWords.filter((queryWord) => {
										const match = this.compareWords(queryWord, word);
										if (match && !matchedWords.includes(queryWord))
										{
											matchedWords.push(queryWord);
										}

										return match;
									});

									return items.length > 0;
								});

								if (result.length > 0)
								{
									fieldWeight = this.searchFields.indexOf(name) + 1;
								}
							}
						});

						sort += fieldWeight;
					}
					else
					{
						sort = 1;
					}

					item.sort = matchedWords.length >= uniqueQueryWords.length ? sort : -1;

					return item;
				}).filter((item) => item.sort >= 0).sort((item1, item2) => {
					if (item1.sort > item2.sort)
					{
						return -1;
					}

					if (item1.sort < item2.sort)
					{
						return 1;
					}

					return 0;
				});
			}
			catch (e)
			{
				console.error(e);

				return items;
			}
		}

		compareWords(queryWord, word)
		{
			const collator = this.getCollator();
			if (collator)
			{
				word = word.slice(0, Math.max(0, queryWord.length));

				return collator.compare(queryWord, word) === 0;
			}

			return word.indexOf(queryWord) === 0;
		}

		getCollator()
		{
			if (this.collator === undefined)
			{
				if (Application.getPlatform() === 'ios' && Intl && Intl.Collator)
				{
					this.collator = new Intl.Collator(undefined, { sensitivity: 'base' });
				}
				else
				{
					this.collator = null;
				}
			}

			return this.collator;
		}

		doSearch(query)
		{
			query = query.trim();

			if (this.queryString === query)
			{
				return;
			}

			this.setQuery(query);

			const cachedItems = this.processResult(query, this.getAllItems());
			cachedItems.push(this.getLoadingItem());

			this.listener.onFetchResult(cachedItems, true);

			if (this.emptyResults.includes(query))
			{
				this.listener.onFetchResult(cachedItems, false);
			}
			else
			{
				this.runSearchActionDebounced(query);
			}
		}

		splitQueryByWords(query)
		{
			const clearedQuery = (
				query
					.replaceAll(specialCharsRegExp, ' ')
					.replaceAll(/\s\s+/g, ' ')
			);

			return (
				clearedQuery
					.toLowerCase()
					.split(' ')
					.filter((word) => word !== '')
			);
		}

		runSearchAction(query)
		{
			if (this.queryString !== query)
			{
				return;
			}

			const queryWords = this.splitQueryByWords(query);

			BX.ajax.runAction(
				'ui.entityselector.doSearch',
				{
					json: {
						dialog: this.getAjaxDialog(),
						searchQuery: {
							query,
							queryWords,
							dynamicSearchEntities: [],
						},
					},
					getParameters: { context: this.context },
				},
			).then((response) => {
				const items = this.prepareItems(response.data.dialog.items);
				if (items.length > 0)
				{
					this.addItems(items);
				}
				else
				{
					this.emptyResults.push(query);
				}

				if (query === this.queryString)
				{
					const allItems = this.getAllItems();
					let sortedItems = this.processResult(query, allItems);

					if (this.useRawResult())
					{
						this.cache.save(items, 'result');
						sortedItems = uniqBy([...sortedItems, ...items], 'id');
					}

					this.listener.onFetchResult(sortedItems);
				}
			}).catch((e) => {
				console.error(e);
			});
		}

		addItems(items, saveDisk = false)
		{
			const currentItems = this.cache.get('items');
			const mergedItems = [...currentItems, ...items];

			this.cache.save(mergedItems, 'items', { saveDisk, unique: true });
		}

		getAllItems()
		{
			const items = [
				...this.cache.get('recent'),
				...this.cache.get('items', !this.canUseRecent),
			];

			return uniqBy(items, 'id');
		}

		loadRecent(justLoad = false)
		{
			if (justLoad === false)
			{
				let recentItems = this.cache.get('recent', true);

				if (this.preselectedItems.length > 0)
				{
					const allItems = this.getAllItems();
					const preselectedItems = (
						this.preselectedItems
							.map((preselectedItem) => allItems.find((item) => item.id === `${preselectedItem[0]}/${preselectedItem[1]}`))
							.filter((item) => item)
					);

					if (preselectedItems.length === this.preselectedItems.length)
					{
						recentItems = uniqBy([...preselectedItems, ...recentItems], 'id');
					}
					else
					{
						recentItems = [];
					}
				}

				if (!this.recentLoaded && recentItems.length === 0)
				{
					recentItems.push(this.getLoadingItem());
				}

				this.listener.onRecentResult(recentItems, true);
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
			}).then((response) => {
				let recentItems = null;

				if (this.canUseRecent)
				{
					recentItems = this.getItemsFromResponse(response.data.dialog, 'recentItems');
				}
				else
				{
					recentItems = this.getItemsFromResponse(response.data.dialog, 'items');

					const items = this.prepareItems(response.data.dialog.items);
					if (items.length > 0)
					{
						this.addItems(items, true);
					}
				}
				this.cache.save(recentItems, 'recent', { saveDisk: true });

				const preselectedItems = this.getItemsFromResponse(response.data.dialog, 'preselectedItems');
				this.addItems(preselectedItems);

				if (justLoad === false)
				{
					const result = this.prepareRecentResult(preselectedItems, recentItems);

					this.listener.onRecentResult(result, false);
					this.recentLoaded = true;
				}
			}).catch(console.error);
		}

		getLoadingItem()
		{
			return {
				id: 'loading',
				title: Loc.getMessage('PROVIDER_COMMON_LOADING_ITEM2'),
				type: 'loading',
				unselectable: true,
				sectionCode: 'common',
			};
		}

		getItemsFromResponse(dialog, key)
		{
			let result = dialog[key] || [];

			if (key !== 'items')
			{
				result = result.map((item) => {
					const [entityId, id] = item;

					return dialog.items.find((item) => item.entityId === entityId && item.id.toString() === id.toString());
				});
			}
			result = result.filter((item) => item);

			return this
				.prepareItems(result)
				.map((item) => {
					item.params.priorityPass = true;

					return item;
				});
		}

		prepareRecentResult(preselectedItems, recentItems)
		{
			const resultItems = uniqBy([...preselectedItems, ...recentItems], 'id');

			return (
				resultItems
					.sort((item1, item2) => {
						if (item1.entityId === 'meta-user')
						{
							return -1;
						}

						if (item2.entityId === 'meta-user')
						{
							return 1;
						}

						return 0;
					})
			);
		}

		prepareSelected(selected)
		{
			if (typeof selected === 'object')
			{
				return Object.keys(selected)
					.reduce((result, entityId) => {
						const groupSelected = selected[entityId].map((original) => {
							const item = { ...original };

							item.color = this.getColor(item.id, entityId);
							item.colorGradient = this.getColorGradient(item.id, entityId);

							if (typeof item.id !== 'undefined')
							{
								item.id = `${entityId}/${item.id}`;
							}

							if (!item.imageUrl && !this.useLettersForEmptyAvatar())
							{
								item.imageUrl = this.getAvatarImage(entityId);
							}

							return item;
						});

						return [...result, ...groupSelected];
					}, []);
			}

			return {};
		}

		prepareResult(items)
		{
			const recentItems = [];

			const result = items.reduce((result, item) => {
				const [entityId, id] = item.id.split('/');

				if (this.preselectedItems.some((preselectedItem) => item.id === `${preselectedItem[0]}/${preselectedItem[1]}`))
				{
					return result;
				}

				if (entityId && id)
				{
					recentItems.push({ id, entityId });

					if (!result[entityId])
					{
						result[entityId] = [];
					}

					let title = item.title;
					if (item.params && item.params.title)
					{
						title = item.params.title;
					}
					result[entityId].push({
						id,
						title,
						subtitle: item.subtitle,
						shortTitle: item.shortTitle,
						params: item.params,
						imageUrl: isDefaultImage(item.imageUrl) && this.useLettersForEmptyAvatar() ? null : item.imageUrl,
						defaultImage: !this.useLettersForEmptyAvatar() && Boolean(item.imageUrl?.includes(
							this.getAvatarImage(entityId),
						)),
					});
				}

				return result;
			}, {});

			if (recentItems.length > 0)
			{
				this.updateRecentCache(items);
				this.addRecentItems(recentItems)
					.then(() => this.loadRecent(true))
					.catch(console.error);
			}

			return result;
		}

		addRecentItems(recentItems)
		{
			return new Promise((resolve, reject) => {
				if (Array.isArray(recentItems) && recentItems.length > 0)
				{
					BX.ajax.runAction('ui.entityselector.saveRecentItems', {
						json: { dialog: this.getAjaxDialog(), recentItems },
						getParameters: { context: this.context },
					}).then((result) => resolve(result))
						.catch((e) => reject(e));
				}
				else
				{
					reject();
				}
			});
		}

		updateRecentCache(items)
		{
			let recent = this.cache.get('recent', true);

			if (recent.length > 0)
			{
				const lastSelectedIds = new Set(items.map((item) => item.id));
				const lastSelectedItems = [];

				recent = recent.filter((item) => {
					if (lastSelectedIds.has(item.id))
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
			return 'common';
		}

		prepareItemForDrawing(entity)
		{
			const item = {
				title: stringify(entity.title),
				subtitle: stringify(entity.subtitle),
				shortTitle: stringify(entity.title),
				sectionCode: 'common',
				height: 64,
				color: this.getColor(entity.id, entity.entityId),
				colorGradient: this.getColorGradient(entity.id, entity.entityId),
				styles: {
					title: {},
					subtitle: {},
				},
				useLetterImage: true,
				id: `${entity.entityId}/${entity.id}`,
				imageUrl: withCurrentDomain(entity.avatar),
				params: {
					title: stringify(entity.title),
					type: entity.entityId,
					id: entity.id,
					entityId: entity.entityId,
					customData: entity.customData || {},
				},
				disabled: entity.customData?.isSelectable === false,
				type: entity.type,
				typeIconFrame: entity.typeIconFrame,
				imageColor: entity.imageColor,
				selectedImageColor: entity.selectedImageColor,
				selectedTypeIconFrame: entity.selectedTypeIconFrame,
				imageName: entity.imageName,
				imageSize: entity.imageSize,
			};

			let useLettersForEmptyAvatar = this.useLettersForEmptyAvatar();

			if (entity.entityType)
			{
				item.params.entityType = entity.entityType;
			}

			switch (entity.entityId)
			{
				case 'user':
				{
					if (entity.entityType === 'extranet')
					{
						item.styles.title.font = {
							color: this.getEntityColor('userExtranet', 'title'),
						};
						item.color = this.getColor(entity.id, 'userExtranet');
						item.colorGradient = this.getColorGradient(entity.id, 'userExtranet');
					}

					item.subtitle = stringify(entity.customData?.position);
					item.shortTitle = stringify(entity.customData?.name || entity.title);
					item.lastName = stringify(entity.customData?.lastName);
					item.name = stringify(entity.customData?.name);
					item.position = stringify(entity.customData?.position);

					useLettersForEmptyAvatar = true;

					break;
				}

				case 'project':
				{
					item.subtitle = Loc.getMessage('PROVIDER_COMMON_PROJECT');
					if (entity.customData?.isCollab)
					{
						item.subtitle = Loc.getMessage('PROVIDER_COMMON_COLLAB');
					}
					else if (entity.customData?.project)
					{
						item.subtitle = Loc.getMessage('PROVIDER_COMMON_PROJECT_NEW');
					}

					item.shortTitle = entity.title;
					item.name = entity.title;

					item.styles.title.font = {};
					item.styles.subtitle.font = {};

					if (entity.entityType === 'extranet')
					{
						item.styles.subtitle.font.color = this.getColor('groupExtranet', 'title');
						item.color = this.getEntityColor('groupExtranet');
					}

					useLettersForEmptyAvatar = true;

					break;
				}

				case 'department':
				{
					item.subtitle = Loc.getMessage('PROVIDER_COMMON_DEPARTMENT');
					item.shortTitle = entity.title;
					item.name = entity.title;

					item.styles.title.font = {};
					item.styles.subtitle.font = {};

					break;
				}

				case 'product':
				case 'store':
				{
					const parts = [];

					if (entity.caption && entity.caption.text)
					{
						parts.push(jnComponent.convertHtmlEntities(entity.caption.text));
					}

					if (entity.supertitle)
					{
						parts.push(entity.supertitle);
					}

					item.subtitle = parts.join(' - ');

					break;
				}

				default:
					// no default
					break;
			}

			if (item.imageUrl || item.avatar)
			{
				item.color = null;
			}

			// Note: Android doesn't support svg images in selector widget
			if ((!item.imageUrl || item.imageUrl.endsWith('.svg')) && !useLettersForEmptyAvatar)
			{
				item.imageUrl = this.getAvatarImage(entity.entityId);
			}

			if (this.isSingleChoose())
			{
				item.type = 'info';
			}

			if (this.handlerPrepareItem)
			{
				const preparedItem = this.handlerPrepareItem(entity);

				return mergeImmutable(item, preparedItem);
			}

			return item;
		}

		useLettersForEmptyAvatar()
		{
			return Boolean(this.options.useLettersForEmptyAvatar);
		}

		addToRecentCache(item)
		{
			if (this.cache)
			{
				this.cache.save(
					[
						...this.prepareItems([item]),
						...this.cache.get('recent'),
					],
					'recent',
					{ saveDisk: true },
				);
			}
		}

		isInRecentCache(item)
		{
			if (this.cache)
			{
				const preparedItem = this.prepareItems([item])[0];
				const cache = this.cache.get('recent');

				return Boolean(cache.some((cacheItem) => cacheItem.id === preparedItem.id));
			}

			return false;
		}
	}

	module.exports = { CommonSelectorProvider };
});
