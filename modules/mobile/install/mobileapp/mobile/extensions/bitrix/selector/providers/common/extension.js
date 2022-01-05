(() =>
{
	const Color = (function(){
		const Colors = {
			background: {
				user: "#d5f1fc",
				userExtranet: "#ffa900",
				userAll: "#dbf188",
				"meta-user": "#dbf188",
				group: "#ade7e4",
				project: "#ade7e4",
				groupExtranet: "#ffa900",
				department: "#e2e3e5",
			},
			subtitle: {
				userExtranet: "#ca8600",
				groupExtranet: "#ca8600",
			},

			title: {
				userExtranet: "#ca8600",
				groupExtranet: "#ca8600",
			},
			tag:{
				groupExtranet:"#FFF599"
			}

		}

		return function(code, group = "background") {
			try {
				return Colors[group][code];
			}
			catch (e) {
				return "#f0f0f0"
			}
		};
	})()

	const getImage = function(name){
		const path = `/bitrix/mobileapp/mobile/extensions/bitrix/selector/providers/common/images/`;
		return `${currentDomain}${path}${name}.png`
	}

	/**
	 * @class CommonSelectorProvider
	 */
	class CommonProvider extends BaseSelectorProvider
	{
		constructor(context, options = {})
		{
			super(context);
			this.context = context;
			this.options = options;
			this.emptyResults = [];
			this.recentLoaded = false;
			this.searchFields = ['position','secondName', 'lastName', 'name'];
			this.entityWeight = {
				'meta-user': 100,
				'user': 90,
				"project":80,
				"department": 70
			}
			this.cache = new BasePickerCache(this.cacheId());
		}

		static title()
		{
			return ""
		}

		setQuery(value)
		{
			this.queryString = value
		}

		setOptions(options) {
			this.options = options;
			this.cache = new BasePickerCache(this.cacheId());
		}

		cacheId() {
			return CommonUtils.md5({id: this.providerId, context: this.context})
		}

		getAjaxDialog()
		{
			let entities = []
			if (this.options.entities)
			{
				Object.keys(this.options.entities).forEach(entryName => {
					let entry = this.options.entities[entryName];
					entry.id = entryName;
					entry.sort = this.entityWeight[entryName]
					entities.push(entry)

				})

				entities.sort((entry1, entry2)=> {
					if (entry1.sort > entry2.sort) return -1
					if (entry1.sort < entry2.sort) return 1
					return 0
				})

			}

			return {
				"id": "mobile",
				"context": this.context,
				entities
			};
		}

		doSearch(query)
		{
			query = query.trim()
			this.queryString = query
			let cachedItems = this.processResult(
				query,
				this.cache.get("recent").concat(this.cache.get("items"))
			)
			let result = cachedItems.filter((item, index, self) =>
				{
					return self.findIndex(item1 => item.id === item1.id) === index;
				});
			this.listener.onFetchResult(result, true);
			if (this.emptyResults.includes(query))
			{
				this.listener.onFetchResult([]);
				return;
			}
			let queryWords = query.split(" ").map(word => {
				word.replace("(", "")
					.replace("(", "")
					.replace(")", "")
					.trim()
				return word
			});

			BX.ajax.runAction('ui.entityselector.doSearch', {
				json: {
					dialog: this.getAjaxDialog(),
					searchQuery: {"queryWords": queryWords, "query": query, "dynamicSearchEntities": []}
				},
				getParameters: {context: this.context}
			}).then(response =>
			{
				let items = this.prepareItems(response.data.dialog.items);
				if (items.length === 0)
				{
					this.emptyResults.push(query)
				}

				this.cache.save(items, "items", {unique: true});
				if (query === this.queryString)
				{
					let sortedItems = this.processResult(query, items)
					this.listener.onFetchResult(sortedItems);
				}
			}).catch(e => {
				console.error(e);
				this.listener.onFetchResult([]);
			})
		}


		loadRecent(justLoad = false)
		{
			if (justLoad === false)
			{
				this.listener.onRecentResult(this.cache.get("recent", true), true);
				if (this.recentLoaded === true)
				{
					return;
				}
			}

			BX.ajax.runAction('ui.entityselector.load', {
				json: {dialog: this.getAjaxDialog()},
				getParameters: {context: this.context}
			}).then(response =>
			{
				let recentItems = this.getRecentFromResponse(response.data.dialog)
				let items = this.prepareItems(recentItems);
				this.cache.save(items, "recent", {saveDisk: true});
				if(justLoad === false)
				{
					this.listener.onRecentResult(items, false);
					this.recentLoaded = true;
				}

			})
		}

		getRecentFromResponse(dialog) {
			return dialog.recentItems
				.map(pair => {
				let entityId = pair[0]
				let id = pair[1]
				return dialog.items.find( item => item.entityId === entityId && item.id === id)
			})
				.filter(item => typeof item !== 'undefined')
				.sort((item1, item2) => {
					if (item1.entityId === "meta-user") return -1
					if (item2.entityId === "meta-user") return 1
					return 0
				})
		}

		prepareSelected(selected)
		{
			if (typeof selected === "object")
			{
				return Object.keys(selected)
					.reduce((result, entityId) =>
					{
						let groupSelected = selected[entityId].map(original => {
							let item = Object.assign({}, original);
							if (typeof item.id !== "undefined")
							{
								item.id = entityId + "/" + item.id;
							}

							if (!item.imageUrl) {
								item.imageUrl = getImage(entityId)
							}

							item.color = Color(entityId)
							return item
						});

						return result.concat(groupSelected)

					}, [])
			}

			return {};
		}

		prepareResult(items)
		{
			let recentItems = [];
			let result = items.reduce((result, item) => {
				let [entityId, id] = item.id.split("/")
				if (entityId && id)
				{
					recentItems.push({id,entityId});
					if (!result[entityId])
					{
						result[entityId] = []
					}

					let title = item.title;
					if (item.params && item.params.title)
					{
						title = item.params.title;
					}
					result[entityId].push({
						id,
						title: title,
						shortTitle: item.shortTitle,
						params: item.params,
						imageUrl: item.imageUrl,
						defaultImage:item.imageUrl.includes(getImage(entityId))
					});
				}

				return result;
			}, {});

			this.addRecentItems(recentItems).then(()=>this.loadRecent(true))
			return result;

		}

		addRecentItems(recentItems)
		{
			return new Promise((resolve, reject) => {
				if (Array.isArray(recentItems) && recentItems.length > 0)
				{
					BX.ajax.runAction('ui.entityselector.saveRecentItems', {
						json: {dialog: this.getAjaxDialog(), recentItems},
						getParameters: {context: this.context}
					}).then(result=>resolve(result))
						.catch(e=>reject(e))
				}
				else
				{
					reject()
				}
			})
		}

		id()
		{
			return "common";
		}

		prepareItemForDrawing(entity)
		{
			let item = {
				title: entity.title,
				sectionCode: "common",
				height: 64,
				color: Color(entity.entityId),
				styles:{
					title:{font:{size:16}},
					subtitle:{},
				},
				useLetterImage: true,
				id: `${entity.entityId}/${entity.id}`,
				imageUrl: entity.avatar,
				params: {
					title: entity.title,
					type: entity.entityId,
					id: entity.id,
				}
			}
			if (entity.entityId === "user")
			{
				if(entity.entityType === "extranet")
				{
					item.styles.title.font = {color:Color("userExtranet", "title")}
					item.color = Color("userExtranet")
				}
				item.subtitle = entity.customData.position;
				item.shortTitle = entity.customData.name;
				item.lastName = entity.customData.lastName;
				item.name = entity.customData.name;

				item.position = entity.customData.position;
			}

			else if (entity.entityId === "project")
			{
				item.subtitle = entity.title;
				item.title = BX.message("PROVIDER_COMMON_PROJECT");
				item.shortTitle = entity.title;
				item.name = entity.title;
				item.styles.title.font = {size: 12, color:"#b1b6bb", fontStyle:"bold"}
				item.styles.subtitle.font = {size: 17, color: "#333333"}

				if(entity.entityType === "extranet")
				{
					item.styles.subtitle.font.color = Color("groupExtranet", "title");
					item.color = Color("groupExtranet")
				}
			}
			else if (entity.entityId === "department")
			{
				item.subtitle = entity.title;
				item.shortTitle = entity.title;
				item.name = entity.title;
				item.title = BX.message("PROVIDER_COMMON_DEPARTMENT");
				item.styles.title.font = {size: 12, color:"#b1b6bb", fontStyle:"bold"}
				item.styles.subtitle.font = {size: 17, color: "#333333"}
			}

			if (!item.imageUrl) {
				item.imageUrl = getImage(entity.entityId)
			}

			if (this.isSingleChoose())
				item.type = "info";
			return item;
		}
	}

	window.CommonSelectorProvider = CommonProvider
})();