BX.namespace("BX.B24SearchTitle");

BX.B24SearchTitle = function(arParams)
{
	var _this = this;

	this.arParams = {
		'AJAX_PAGE': arParams.AJAX_PAGE,
		'CONTAINER_ID': arParams.CONTAINER_ID,
		'INPUT_ID': arParams.INPUT_ID,
		'MIN_QUERY_LEN': parseInt(arParams.MIN_QUERY_LEN),
		'FORMAT': (typeof arParams.FORMAT != 'undefined' && arParams.FORMAT == 'json' ? 'json' : 'html'),
		'CATEGORIES_ALL': (typeof arParams.CATEGORIES_ALL != 'undefined' ? arParams.CATEGORIES_ALL : []),
		'USER_URL': (typeof arParams.USER_URL != 'undefined' ? arParams.USER_URL : ''),
		'GROUP_URL': (typeof arParams.GROUP_URL != 'undefined' ? arParams.GROUP_URL : ''),
		'WAITER_TEXT': (typeof arParams.WAITER_TEXT != 'undefined' ? arParams.WAITER_TEXT : ''),
		'CURRENT_TS': parseInt(arParams.CURRENT_TS),
		'GLOBAL_SEARCH_CATEGORIES': (typeof arParams.GLOBAL_SEARCH_CATEGORIES == 'object' ? arParams.GLOBAL_SEARCH_CATEGORIES : []),
		'MORE_USERS_URL': arParams.MORE_USERS_URL,
		'IS_CRM_INSTALLED': arParams.IS_CRM_INSTALLED == "Y"
		//'SEARCH_PAGE': (typeof arParams.SEARCH_PAGE != 'undefined' ? arParams.SEARCH_PAGE : '')
	};

	// !!! check this out !!!
	if(arParams.MIN_QUERY_LEN <= 0)
		arParams.MIN_QUERY_LEN = 1;

	this.cache = [];
	this.cache_key = null;

	this.startText = '';
	this.currentRow = -1;
	this.RESULT = null;
	this.CONTAINER = null;
	this.INPUT = null;
	this.xhr = null;
	this.blockAjax = false;
	this.searchStarted = false;
	this.ITEMS = {
		obClientDb: null,
		obClientDbData: {},
		obClientDbDataSearchIndex: {},
		bMenuInitialized: false,
		initialized: {
			sonetgroups: false,
			menuitems: false
		},
		oDbSearchResult: {}
	};
	this.searchByAjax = false;
	this.selectedItemDataId = null;//0;

	this.CreateResultWrap = function()
	{
		if (_this.RESULT == null)
		{
			this.RESULT = document.body.appendChild(document.createElement("DIV"));
			this.RESULT.className = 'title-search-result title-search-result-header search-title-top-result-header';
		}
	};

	this.MakeResultFromClientDB = function(arSearchStringAlternatives, searchStringOriginal)
	{
		var result = null;

		var key, i, j, entityCode, prefix = null;
		for (key = 0; key < arSearchStringAlternatives.length; key++)
		{
			var searchString = arSearchStringAlternatives[key].toLowerCase();
			if (
				typeof _this.ITEMS.oDbSearchResult[searchString] != 'undefined'
				&& _this.ITEMS.oDbSearchResult[searchString].length > 0 // results from local DB
			)
			{
				for (i=0;i<_this.ITEMS.oDbSearchResult[searchString].length;i++)
				{
					entityCode =_this.ITEMS.oDbSearchResult[searchString][i];
					prefix = entityCode.substr(0, 1);

					for (j=0;j<_this.arParams.CATEGORIES_ALL.length;j++)
					{
						if (
							typeof _this.arParams.CATEGORIES_ALL[j].CLIENTDB_PREFIX != 'undefined'
							&& _this.arParams.CATEGORIES_ALL[j].CLIENTDB_PREFIX == prefix
						)
						{
							if (result == null)
							{
								result = {};
							}
							if (typeof result.CATEGORIES == 'undefined')
							{
								result.CATEGORIES = {};
							}
							if (typeof result.CATEGORIES[j] == 'undefined')
							{
								result.CATEGORIES[j] = {
									ITEMS: [],
									TITLE : _this.arParams.CATEGORIES_ALL[j].TITLE
								};
							}

							if (prefix == "U")
							{
								result.CATEGORIES[j].ITEMS.push({
									ICON: (typeof _this.ITEMS.obClientDbData.users[entityCode].avatar != 'undefined' ? _this.ITEMS.obClientDbData.users[entityCode].avatar : ''),
									ITEM_ID:  entityCode,
									MODULE_ID: '',
									NAME: _this.ITEMS.obClientDbData.users[entityCode].name,
									PARAM1: '',
									URL: _this.arParams.USER_URL.replace('#user_id#', _this.ITEMS.obClientDbData.users[entityCode].entityId),
									TYPE: 'users'
								});
							}
							else if (prefix == "G")
							{
								if (
									typeof _this.ITEMS.obClientDbData.sonetgroups[entityCode].site != 'undefined'
									&& _this.ITEMS.obClientDbData.sonetgroups[entityCode].site == BX.message('SITE_ID')
								)
								{
									result.CATEGORIES[j].ITEMS.push({
										ICON: (typeof _this.ITEMS.obClientDbData.sonetgroups[entityCode].avatar != 'undefined' ? _this.ITEMS.obClientDbData.sonetgroups[entityCode].avatar : ''),
										ITEM_ID:  entityCode,
										MODULE_ID: '',
										NAME: _this.ITEMS.obClientDbData.sonetgroups[entityCode].name,
										PARAM1: '',
										URL: _this.arParams.GROUP_URL.replace('#group_id#', _this.ITEMS.obClientDbData.sonetgroups[entityCode].entityId),
										TYPE: 'sonetgroups',
										IS_MEMBER: (typeof _this.ITEMS.obClientDbData.sonetgroups[entityCode].isMember != 'undefined' && _this.ITEMS.obClientDbData.sonetgroups[entityCode].isMember == 'Y' ? 1 : 0)
									});
								}
							}
							else if (prefix == "M")
							{
								result.CATEGORIES[j].ITEMS.push({
									ICON: '',
									ITEM_ID:  entityCode,
									MODULE_ID: '',
									NAME: _this.ITEMS.obClientDbData.menuitems[entityCode].name,
									PARAM1: '',
									URL: _this.ITEMS.obClientDbData.menuitems[entityCode].entityId,
									CHAIN: (typeof _this.ITEMS.obClientDbData.menuitems[entityCode].chain != 'undefined' ? _this.ITEMS.obClientDbData.menuitems[entityCode].chain : false)
								});
							}
							break;
						}
					}
				}
			}
		}

		if (result !== null)
		{
			for (var categoryId in result.CATEGORIES)
			{
				if (result.CATEGORIES.hasOwnProperty(categoryId))
				{
					result.CATEGORIES[categoryId].ITEMS.sort(_this.resultCmp);
				}
			}
		}

		return result;
	};

	this.resultCmp = function(a, b)
	{
		if (
			typeof a.TYPE != 'undefined'
			&& typeof b.TYPE != 'undefined'
			&& a.TYPE == 'sonetgroups'
			&& b.TYPE == 'sonetgroups'
			&& typeof a.IS_MEMBER != 'undefined'
			&& typeof b.IS_MEMBER != 'undefined'
		)
		{
			if (a.IS_MEMBER == b.IS_MEMBER)
			{
				if (a.NAME == b.NAME)
				{
					return 0;
				}

				return (a.NAME < b.NAME ? -1 : 1);
			}

			return (a.IS_MEMBER > b.IS_MEMBER ? -1 : 1);
		}
		else
		{
			if (a.NAME == b.NAME)
			{
				return 0;
			}

			return (a.NAME < b.NAME ? -1 : 1);
		}
	};

	this.BuildResult = function(jsonResult, showWaiter)
	{
		var rows = [];
		var category = null;
		var itemBlock = null;
		var blockClassName = "";
		var resultEmpty = true;

		if (
			typeof jsonResult === "object"
			&& jsonResult
			&& typeof jsonResult.CATEGORIES != 'undefined'
			&& BX.type.isNotEmptyObject(jsonResult.CATEGORIES)
		)
		{
			for (var categoryId in jsonResult.CATEGORIES)
			{
				if (categoryId == "all")
					continue;

				if (jsonResult.CATEGORIES.hasOwnProperty(categoryId))
				{
					if (resultEmpty)
					{
						resultEmpty = false;
					}
					category = jsonResult.CATEGORIES[categoryId];

					if (typeof category.ITEMS != 'undefined')
					{
						var i = 0;
						var isMoreItems = false;
						var itemBlocks = [];

						for (var itemId in category.ITEMS)
						{
							if (category.ITEMS.hasOwnProperty(itemId))
							{
								if (i >= 7)
								{
									isMoreItems = true;
									break;
								}

								var currentItem = category.ITEMS[itemId];

								if (currentItem.TYPE == "all")
									continue;

								if (currentItem.TYPE == "users" || currentItem.TYPE == "sonetgroups")
								{
									blockClassName = 'search-title-top-block-' + currentItem.TYPE;
								}
								else
								{
									blockClassName = 'search-title-top-block-section';
								}

								itemBlock = this.BuildResultItem(currentItem);

								itemBlocks.push(itemBlock);
								i++;
							}
						}
						if (itemBlocks && currentItem)
						{
							rows.push(BX.create('div', {
								attrs: {"className": "search-title-top-block " + blockClassName},
								children: [
									BX.create('div', {
										props: {
											className: 'search-title-top-subtitle'
										},
										children: [
											BX.create("div", {
												props: {className: 'search-title-top-subtitle-text'},
												html: category.TITLE
											})
										]
									}),
									BX.create('div', {
										props: {
											className: 'search-title-top-list-wrap'
										},
										children: [
											BX.create("div", {
												attrs: {
													className: "search-title-top-list search-title-top-list-js",
													"bx-search-block-id" : currentItem.TYPE
												},
												children: itemBlocks
											})
										]
									})
								]
							}));

							//more items are in a separated block for selecting by keys
							if (isMoreItems && currentItem.TYPE == "users")
							{
								var moreItem = {
									"URL": this.arParams.MORE_USERS_URL + this.INPUT.value,
									"ITEM_ID" : currentItem.TYPE + "_more"
								};
								var moreBlock = this.BuildMoreBlock(moreItem);
								rows.push(moreBlock);
							}
						}
					}
				}
			}
		}

		//if (showWaiter)
		//{
			rows.push(BX.create('div', {
				attrs: {
					style: "margin-bottom: 20px;" + (!showWaiter ? "display:none;" : ""),
					id : "title-search-waiter"
				},
				children: [
					BX.create('div', {
						props: {
							className: 'title-search-waiter'
						},
						children: [
							BX.create('span', {
								props: {
									className: 'title-search-waiter-img'
								}
							}),
							BX.create('span', {
								props: {
									className: 'title-search-waiter-text'
								},
								html: _this.arParams.WAITER_TEXT
							})
						]
					})
				]
			}));
		//}


		rows = this.BuildGlobalSearchCategories(rows);

		var result = BX.create('div', {
			props: {
				className: 'search-title-top-result'
			},
			children: rows
		});

		return result;
	};

	this.BuildResultItem = function (currentItem)
	{
		if (!(typeof currentItem == "object" && currentItem))
			return;

		if (this.selectedItemDataId == null)
		{
			this.selectedItemDataId = currentItem.ITEM_ID;
		}

		var itemBlock = BX.create("div", {
			attrs: {
				className: "search-title-top-item search-title-top-item-js" + (this.selectedItemDataId == currentItem.ITEM_ID ? " search-title-top-item-selected" : ""),
				title: (typeof currentItem.CHAIN != 'undefined' && BX.type.isArray(currentItem.CHAIN) ? currentItem.CHAIN.join(' -> ') : ''),
				'bx-search-item-id': currentItem.ITEM_ID
			},
			children: [
				BX.create('a', {
					attrs: {
						href: currentItem.URL,
						className: "search-title-top-item-link"
					},
					children: [
						currentItem.TYPE == "users" || currentItem.TYPE == "sonetgroups" ?
							BX.create('span', {
								attrs: {
									style: (typeof currentItem.ICON != 'undefined' && currentItem.ICON.length > 0 ? "background-image: url('" + currentItem.ICON + "')" : '')
								},
								props: {
									className: 'search-title-top-item-img' + (!currentItem.ICON ? " search-title-top-item-img-default-" + currentItem.TYPE : "")// + currentItem.TYPE
								}
							}) : "",
						BX.create('span', {
							props: {
								className: 'search-title-top-item-text'
							},
							children: [
								BX.create("span", {
									html: currentItem.NAME
								})
							]
						})
					]
				}),
				currentItem.TYPE == "users" ?
					BX.create("span", {
						attrs: { className: "search-title-top-item-message"},
						events: {
							"click" : BX.proxy(function ()
							{
								if (BX.IM)
								{
									BXIM.openMessenger(this.userId);
								}
								else
								{
									window.open('', '', 'status=no,scrollbars=yes,resizable=yes,width=700,height=550,top='+Math.floor((screen.height - 550)/2-14)+',left='+Math.floor((screen.width - 700)/2-5)); return false;
								}
							}, {userId: currentItem.ITEM_ID.substring(1)})
						}
					}) : ""
			],
			events: {
				"mouseover" : BX.proxy(function () {
					this.UnSelectAll();
					this.SelectItem(BX.proxy_context);
				}, this),
				"mouseout" : BX.proxy(function () {
					this.UnSelectItem(BX.proxy_context);
					this.selectedItemDataId = null;
				}, this)
			}
		});

		return itemBlock;
	};

	this.BuildMoreBlock = function (item)
	{
		var block = BX.create('div', {
			attrs: {
				"className": "search-title-top-block search-title-top-more-block",
				"style": "margin-top: -35px;"
			},
			children: [
				BX.create('div', {
					props: {
						className: 'search-title-top-list-wrap'
					},
					children: [
						BX.create("div", {
							attrs: {
								className: "search-title-top-list search-title-top-list-js"
							},
							children: [
								BX.create("div", {
									attrs: {
										className: "search-title-top-more search-title-top-item-js",
										"bx-search-item-id" : item.ITEM_ID
									},
									children: [
										BX.create("a", {
											attrs: {
												className: "search-title-top-more-text",
												href: item.URL
											},
											html: BX.message("SEARCH_MORE")
										})
									]
								})
							]
						})
					]
				})
			]
		});

		return block;
	};

	this.BuildGlobalSearchCategories = function(rows)
	{
		//global search category
		var itemBlocks = [];

		for (var i in this.arParams.GLOBAL_SEARCH_CATEGORIES)
		{
			if (!this.arParams.GLOBAL_SEARCH_CATEGORIES.hasOwnProperty(i))
				continue;

			var limited = this.arParams.GLOBAL_SEARCH_CATEGORIES[i].limited === true;
			var item = {
				"NAME": this.arParams.GLOBAL_SEARCH_CATEGORIES[i].text,
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES[i].url + (limited ? "" : this.INPUT.value),
				"ITEM_ID" : i
			};

			var itemBlock = this.BuildResultItem(item);
			itemBlocks.push(itemBlock);
		}

		var block = BX.create('div', {
			attrs: {"className": "search-title-top-block search-title-top-block-tools", id: "search-title-block-tools"},
			children: [
				BX.create('div', {
					props: {
						className: 'search-title-top-subtitle'
					},
					children: [
						BX.create("div", {
							props: {className: 'search-title-top-subtitle-text'},
							html: BX.message("GLOBAL_SEARCH")
						})
					]
				}),
				BX.create('div', {
					attrs: { className: "search-title-top-list-height-wrap", id: "search-title-global-categories-height-wrap" },
					children: [
						BX.create('div', {
							attrs: {
								className: 'search-title-top-list-wrap', id: 'search-title-global-categories-wrap'
							},
							children: [
								BX.create("div", {
									attrs: {
										className: "search-title-top-list search-title-top-list-js"
									},
									children: itemBlocks
								}),
								BX.create("div", {
									attrs: {className: "search-title-top-arrow"}
								})
							]
						})
					]
				})
			]
		});

		rows.push(block);
		//this.toggleGlobalCategories("open");

		return rows;
	};

	this.BuildEntities = function (result)
	{
		var crmContact = [];
		var crmCompany= [];
		var crmDeal= [];
		var crmLead= [];
		var crmQuote= [];
		var crmInvoice= [];
		var diskItems= [];
		var taskItems= [];
		var crmContactMore = false, crmCompanyMore = false, crmDealMore = false, crmLeadMore = false,
			crmInvoiceMore = false, crmQuoteMore = false, diskMore = false, taskMore = false;

		var itemsData = result && result.data && BX.type.isArray(result.data.items) ? result.data.items : [];
		for (var i = 0; i < itemsData.length; i++)
		{
			var itemData = result.data.items[i];

			var item = {
				"NAME": BX.util.htmlspecialchars(itemData.title),
				"URL": itemData.links.show,
				"ITEM_ID" : itemData.type + itemData.id
			};

			if (itemData.type === "CONTACT")
			{
				if (crmContact.length < 10)
				{
					crmContact.push(item);
				}
				else
				{
					crmContactMore = true;
				}
			}
			else if (itemData.type === "COMPANY")
			{
				if (crmCompany.length < 10)
				{
					crmCompany.push(item);
				}
				else
				{
					crmCompanyMore = true;
				}
			}
			else if (itemData.type === "DEAL")
			{
				if (crmDeal.length < 10)
				{
					crmDeal.push(item);
				}
				else
				{
					crmDealMore = true;
				}
			}
			else if (itemData.type === "LEAD")
			{
				if (crmLead.length < 10)
				{
					crmLead.push(item);
				}
				else
				{
					crmLeadMore = true;
				}
			}
			else if (itemData.type === "QUOTE")
			{
				if (crmQuote.length < 10)
				{
					crmQuote.push(item);
				}
				else
				{
					crmQuoteMore = true;
				}
			}
			else if (itemData.type === "INVOICE")
			{
				if (crmInvoice.length < 10)
				{
					crmInvoice.push(item);
				}
				else
				{
					crmInvoiceMore = true;
				}
			}
			else if (itemData.module === "disk")
			{
				if (diskItems.length < 10)
				{
					diskItems.push(item);
				}
				else
				{
					diskMore = true;
				}
			}
			else if (itemData.type === "TASK")
			{
				if (taskItems.length < 10)
				{
					taskItems.push(item);
				}
				else
				{
					taskMore = true;
				}
			}
		}

		var limits = {};
		if (result && result.data && BX.type.isArray(result.data.limits))
		{
			result.data.limits.forEach(function(limit) {

				if (!BX.type.isPlainObject(limit))
				{
					return;
				}

				if (BX.type.isNotEmptyString(limit.type))
				{
					limits[limit.type.toLowerCase()] = limit;
				}
				else if (BX.type.isNotEmptyString(limit.module))
				{
					limits[limit.module.toLowerCase()] = limit;
				}
			});
		}

		this.BuildEntityBlock(crmDeal, "CRM: " + BX.message("SEARCH_CRM_DEAL"), "deal", limits.deal);
		if (crmDealMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["deal"]["url"] + this.INPUT.value,
				"ITEM_ID": "deal_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}
		this.BuildEntityBlock(crmContact, "CRM: " + BX.message("SEARCH_CRM_CONTACT"), "contact", limits.contact);
		if (crmContactMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["contact"]["url"] + this.INPUT.value,
				"ITEM_ID": "contact_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}

		this.BuildEntityBlock(crmCompany, "CRM: " + BX.message("SEARCH_CRM_COMPANY"), "company", limits.company);
		if (crmCompanyMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["company"]["url"] + this.INPUT.value,
				"ITEM_ID": "company_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}

		this.BuildEntityBlock(crmLead, "CRM: " + BX.message("SEARCH_CRM_LEAD"), "lead", limits.lead);
		if (crmLeadMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["lead"]["url"] + this.INPUT.value,
				"ITEM_ID": "lead_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}

		this.BuildEntityBlock(crmInvoice, "CRM: " + BX.message("SEARCH_CRM_INVOICE"), "invoice", limits.invoice);
		if (crmInvoiceMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["invoice"]["url"] + this.INPUT.value,
				"ITEM_ID": "invoice_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}

		this.BuildEntityBlock(crmQuote, "CRM: " + BX.message("SEARCH_CRM_QUOTE"), "quote", limits.quote);
		if (crmQuoteMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["quote"]["url"] + this.INPUT.value,
				"ITEM_ID": "quote_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}

		this.BuildEntityBlock(diskItems, BX.message("SEARCH_DISK"), "disk", limits.disk);
		if (diskMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["disk"]["url"] + this.INPUT.value,
				"ITEM_ID": "disk_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}

		this.BuildEntityBlock(taskItems, BX.message("SEARCH_TASKS"), "task", limits.task);
		if (taskMore)
		{
			item = {
				"URL": this.arParams.GLOBAL_SEARCH_CATEGORIES["tasks"]["url"] + this.INPUT.value,
				"ITEM_ID": "task_more"
			};
			var moreBlock = this.BuildMoreBlock(item);
			BX.firstChild(_this.RESULT).insertBefore(moreBlock, BX("search-title-block-tools"));
		}

		BX("title-search-waiter").style.display = "none";
		_this.checkSelectedItem();
	};

	this.BuildEntityBlock = function (items, blockTitle, entityType, limits)
	{
		if (items.length > 0)
		{
			var crmBlocks = [];
			for (var i in items)
			{
				var crmBlock = _this.BuildResultItem(items[i]);
				crmBlocks.push(crmBlock);
			}

			if (crmBlocks)
			{
				this.BuildEntity(crmBlocks, blockTitle, entityType);
			}
		}
		else if (BX.type.isPlainObject(limits))
		{
			this.buildLimits(limits, blockTitle);
		}
	};

	this.BuildEntity = function (crmBlocks, blockTitle, entityType)
	{
		var crmSection = (BX.create('div', {
			attrs: {"className": "search-title-top-block search-title-top-block-section"},
			children: [
				BX.create('div', {
					props: {
						className: 'search-title-top-subtitle'
					},
					children: [
						BX.create("div", {
							props: {className: 'search-title-top-subtitle-text'},
							html: blockTitle
						})
					]
				}),
				BX.create('div', {
					props: {
						className: 'search-title-top-list-wrap'
					},
					children: [
						BX.create("div", {
							attrs: {
								className: "search-title-top-list search-title-top-list-js",
								"bx-search-block-id" : entityType
							},
							children: crmBlocks
						})
					]
				})
			]
		}));

		BX.firstChild(_this.RESULT).insertBefore(crmSection, BX("search-title-block-tools"));
	};

	this.buildLimits = function(limits, blockTitle)
	{
		var limitsSection = BX.create('div', {
			attrs: {
				"className": "search-title-top-block search-title-top-block-section"
			},
			html:
			'<div class="search-title-top-subtitle">' +
				'<div class="search-title-top-subtitle-text">' + blockTitle + '</div>' +
			'</div>' +
			'<div class="search-title-top-list-wrap">' +
				'<div class="search-title-top-list">' +
					'<div class="search-title-top-list-limits">' +
						'<div class="search-title-top-list-limits-block">' +
							'<span class="search-title-top-list-limits-icon"></span>' +
						'</div>' +
						'<div class="search-title-top-list-limits-block">' +
							'<div class="search-title-top-list-limits-name">' +
								(BX.type.isString(limits.title) ? limits.title : '') +
							'</div>' +
							'<div class="search-title-top-list-limits-content">' +
								(BX.type.isString(limits.description) ? limits.description : '') +
							'</div>' +
							(
								BX.type.isArray(limits.buttons) && limits.buttons.length > 0
								?
									'<div class="ui-btn-container ui-btn-container-center">' +
										limits.buttons.join('') +
									'</div>'
								: ''
							) +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>'
		});

		BX.firstChild(_this.RESULT).insertBefore(limitsSection, BX("search-title-block-tools"));
	};

	this.checkSelectedItem = function ()
	{
		var selectedNode = BX.findChild(_this.RESULT, {className: "search-title-top-item-selected"}, true);

		if (BX.type.isDomNode(selectedNode) && BX("search-title-global-categories-wrap").contains(selectedNode))
		{
			var firstNode = BX.findChild(_this.RESULT, {className: "search-title-top-item-js"}, true);
			_this.UnSelectAll();
			_this.SelectItem(firstNode);
		}
	};

	this.ShowResult = function(result, showWaiter, afterAjax)
	{
		_this.CreateResultWrap();
		/* modified */
		var ieTop = 0;
		var ieLeft = 0;
		var ieWidth = 0;
		if(BX.browser.IsIE())
		{
			ieTop = 0;
			ieLeft = 1;
			ieWidth = -1;

			if(/MSIE 7/i.test(navigator.userAgent))
			{
				ieTop = -1;
				ieLeft = -1;
				ieWidth = -2;
			}
		}

		var pos = BX.pos(_this.CONTAINER);
		pos.width = pos.right - pos.left;
		_this.RESULT.style.position = 'absolute';
		_this.RESULT.style.top = pos.bottom + ieTop - 1 + 'px';/* modified */
		_this.RESULT.style.left = pos.left + ieLeft + 'px';/* modified */
		_this.RESULT.style.width = (pos.width + ieWidth) + 'px';/* modified */

		if (typeof _this.arParams.FORMAT != 'undefined' && _this.arParams.FORMAT == 'json')
		{
			result = _this.BuildResult(result, !!showWaiter);
			BX.cleanNode(_this.RESULT);
			if (BX.type.isDomNode(result) && result.innerHTML.length)
			{
				_this.RESULT.appendChild(result);
				if (BX.type.isDomNode(BX("search-title-block-tools")) && BX.type.isDomNode(BX("search-title-global-categories-wrap")))
				{
					BX.bind(BX("search-title-global-categories-wrap"), "mouseover", BX.proxy(function ()
					{
						_this.toggleGlobalCategories("open");
					}, _this));
					BX.bind(BX("search-title-global-categories-wrap"), "mouseout", BX.proxy(function ()
					{
						_this.toggleGlobalCategories("close");
					}, _this));

					_this.RESULT.style.display = 'block';
				}
				else
				{
					_this.RESULT.style.display = 'block';
				}


				if (afterAjax)
				{
					BX("title-search-waiter").style.display = "block";

					if (_this.arParams.IS_CRM_INSTALLED) //search in crm
					{
						var resCrm = BX.ajax.runAction("crm.api.entity.search", { data: { searchQuery: this.INPUT.value, options: { scope: 'index'/*, types: [ BX.CrmEntityType.names.contact ] */} } });
						resCrm.then(this.BuildEntities.bind(this));
					}

					var resDisk = BX.ajax.runAction("disk.commonActions.search", { data: { searchQuery: this.INPUT.value } });
					resDisk.then(this.BuildEntities.bind(this));

					var restTask = BX.ajax.runAction("tasks.task.search", { data: { searchQuery: this.INPUT.value } });
					restTask.then(this.BuildEntities.bind(this));
				}
			}
		}
		else
		{
			_this.RESULT.innerHTML = result;
		}
	};

	this.toggleGlobalCategories = function(mode)
	{
		var wrap = BX("search-title-global-categories-wrap");
		var heightWrap = BX("search-title-global-categories-height-wrap");

		if (!BX.type.isDomNode(wrap) || !BX.type.isDomNode(heightWrap))
			return;

		if (mode == "open")
		{
			BX.addClass(wrap, "search-title-top-list-wrap-hover");
			heightWrap.style.height = wrap.offsetHeight + "px";
		}
		else
		{
			var selectedItem = BX.findChild(wrap, {className: "search-title-top-item-selected"}, true, false);
			if (!selectedItem)
			{
				BX.removeClass(wrap, "search-title-top-list-wrap-hover");
				heightWrap.style.height = "";
			}
		}
	};

	this.SyncResult = function(result, searchString)
	{
		var
			ajaxDbEntities = null,
			ajaxUserCodeList = [],
			ajaxGroupCodeList = [],
			ajaxMenuItemCodeList = [],
			codes = [];

		searchString = searchString.toLowerCase();

		for (var i=0;i<_this.arParams.CATEGORIES_ALL.length;i++)
		{
			if (typeof _this.arParams.CATEGORIES_ALL[i].CODE != 'undefined')
			{
				if (typeof result.CATEGORIES[i] != 'undefined')
				{
					if (_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_menuitems')
					{
						ajaxDbEntities = {};
						for (var j=0;j<result.CATEGORIES[i].ITEMS.length;j++)
						{
							ajaxDbEntities[result.CATEGORIES[i].ITEMS[j].ITEM_ID] = _this.ConvertAjaxToClientDB(result.CATEGORIES[i].ITEMS[j], 'menuitems');
							ajaxMenuItemCodeList.push(result.CATEGORIES[i].ITEMS[j].ITEM_ID);
						}
						BX.onCustomEvent(_this, 'onFinderAjaxSuccess', [ ajaxDbEntities, _this.ITEMS, 'menuitems' ]);
					}
					else if (_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_sonetgroups')
					{
						ajaxDbEntities = {};
						for (j=0;j<result.CATEGORIES[i].ITEMS.length;j++)
						{
							ajaxDbEntities[result.CATEGORIES[i].ITEMS[j].ITEM_ID] = _this.ConvertAjaxToClientDB(result.CATEGORIES[i].ITEMS[j], 'sonetgroups');
							ajaxGroupCodeList.push(result.CATEGORIES[i].ITEMS[j].ITEM_ID);
						}
						BX.onCustomEvent(_this, 'onFinderAjaxSuccess', [ ajaxDbEntities, _this.ITEMS, 'sonetgroups' ]);
					}
					else if (_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_users')
					{
//						ajaxDbEntities = {};
						for (j=0;j<result.CATEGORIES[i].ITEMS.length;j++)
						{
//							ajaxDbEntities[result.CATEGORIES[i].ITEMS[j].ITEM_ID] = _this.ConvertAjaxToClientDB(result.CATEGORIES[i].ITEMS[j], 'users');
							ajaxUserCodeList.push(result.CATEGORIES[i].ITEMS[j].ITEM_ID);
						}
//						BX.onCustomEvent(_this, 'onFinderAjaxSuccess', [ ajaxDbEntities, _this.ITEMS, 'users' ]);
					}
				}

				var z = 0;

				if (
					_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_users'
					&& BX.type.isNotEmptyString(searchString)
					&& typeof _this.ITEMS.oDbSearchResult[searchString] != 'undefined'
					&& _this.ITEMS.oDbSearchResult[searchString].length > 0
				)
				{
					codes = [];
					for (z=0;z<_this.ITEMS.oDbSearchResult[searchString].length;z++)
					{
						if (_this.ITEMS.oDbSearchResult[searchString][z].match(/U(\d+)/) !== null)
						{
							codes.push(_this.ITEMS.oDbSearchResult[searchString][z]);
						}
					}

					if (codes.length > 0)
					{
						BX.onCustomEvent('syncClientDb', [
							_this.ITEMS,
							false, // name
							codes,
							ajaxUserCodeList,
							'users'
						]);
					}
				}

				if (
					_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_sonetgroups'
					&& BX.type.isNotEmptyString(searchString)
					&& typeof _this.ITEMS.oDbSearchResult[searchString] != 'undefined'
					&& _this.ITEMS.oDbSearchResult[searchString].length > 0
				)
				{
					codes = [];
					for (z=0;z<_this.ITEMS.oDbSearchResult[searchString].length;z++)
					{
						if (_this.ITEMS.oDbSearchResult[searchString][z].match(/G(\d+)/) !== null)
						{
							codes.push(_this.ITEMS.oDbSearchResult[searchString][z]);
						}
					}

					if (codes.length > 0)
					{
						BX.onCustomEvent('syncClientDb', [
							_this.ITEMS,
							false, // name
							codes,
							ajaxGroupCodeList,
							'sonetgroups'
						]);
					}
				}

				if (
					_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_menuitems'
					&& BX.type.isNotEmptyString(searchString)
					&& typeof _this.ITEMS.oDbSearchResult[searchString] != 'undefined'
					&& _this.ITEMS.oDbSearchResult[searchString].length > 0
				)
				{
					codes = [];
					for (z=0;z<_this.ITEMS.oDbSearchResult[searchString].length;z++)
					{
						if (_this.ITEMS.oDbSearchResult[searchString][z].match(/M\/(.+)/) !== null)
						{
							codes.push(_this.ITEMS.oDbSearchResult[searchString][z]);
						}
					}

					if (codes.length > 0)
					{
						BX.onCustomEvent('syncClientDb', [
							_this.ITEMS,
							false, // name
							codes,
							ajaxMenuItemCodeList,
							'menuitems'
						]);
					}
				}
			}
		}
	};

	this.ConvertAjaxToClientDB = function(oEntity, entity)
	{
		var result = null;
		if (entity == 'sonetgroups')
		{
			result = {
				id: 'G' + oEntity.ID,
				entityId: oEntity.ID,
				name: oEntity.NAME,
				avatar: oEntity.ICON,
				desc: '',
				isExtranet: (oEntity.IS_EXTRANET ? 'Y' : 'N'),
				site: oEntity.SITE,
				checksum: oEntity.CHECKSUM,
				isMember: (typeof oEntity.IS_MEMBER != 'undefined' &&  oEntity.IS_MEMBER ? 'Y' : 'N')
			};
		}
		else if (entity == 'menuitems')
		{
			result = {
				id: 'M' + oEntity.URL,
				entityId: oEntity.URL,
				name: oEntity.NAME,
				checksum: oEntity.CHECKSUM,
				chain: (typeof oEntity.CHAIN != 'undefined' && BX.type.isArray(oEntity.CHAIN) ? oEntity.CHAIN : null)
			};
		}
		else if (entity == 'users')
		{
			result = {
				id: 'U' + oEntity.ID,
				entityId: oEntity.ID,
				name: oEntity.NAME,
				login: oEntity.LOGIN,
				active: oEntity.ACTIVE,
				avatar: oEntity.ICON,
				desc: oEntity.DESCRIPTION,
				isExtranet: 'N',
				isEmail: 'N',
				checksum: oEntity.CHECKSUM
			};
		}

		return result;
	};

	this.onKeyPress = function(keyCode)
	{
		_this.CreateResultWrap();
		var popup = BX.findChild(_this.RESULT, {'tag':'div','class':'search-title-top-result'}, true);

		if(!popup)
			return false;

		var blocks = BX.findChildren(_this.RESULT, {"className" : "search-title-top-list-js"}, true);

		switch (keyCode)
		{
			case 27: // escape key - close search div
				_this.RESULT.style.display = 'none';
				break;

			case 40: // down key - navigate down on search results
				if(_this.RESULT.style.display == 'none')
					_this.RESULT.style.display = 'block';

				var items = BX.findChildren(_this.RESULT, {"className" : "search-title-top-item-js"}, true);

				if (this.selectedItemDataId === null)
				{
					_this.SelectItem(items[0]);
				}
				else
				{
					var currentItemNode = _this.RESULT.querySelector("[bx-search-item-id='" + _this.selectedItemDataId + "']");

					if (!BX.type.isDomNode(currentItemNode))
						return false;

					var currentBlockNode = BX.findParent(currentItemNode, {className: "search-title-top-list-js"}, true);

					if (!BX.type.isDomNode(currentBlockNode))
						return false;

					var currentBlockItems = BX.findChildren(currentBlockNode, {className: "search-title-top-item-js"}, true);
					var currentItemOffsetLeft = currentItemNode.offsetLeft;
					var currentItemOffsetTop = currentItemNode.offsetTop;
					var currentItemWidth = currentItemNode.offsetWidth;
					var currentItemOffsetRight = currentItemOffsetLeft + currentItemWidth;
					var rowItems = [];
					var nextTopOffset = null;

					for (var i in currentBlockItems)
					{
						if (currentBlockItems[i].offsetTop <= currentItemOffsetTop)
						{
							continue;
						}
						else
						{
							if (nextTopOffset === null)
								nextTopOffset = currentBlockItems[i].offsetTop;
						}

						if (nextTopOffset && currentBlockItems[i].offsetTop == nextTopOffset)
						{
							rowItems.push(currentBlockItems[i]);
						}
					}

					if (rowItems.length > 0)
					{
						_this.UnSelectAll();

						for (i in rowItems)
						{
							if (rowItems[i].offsetLeft + rowItems[i].offsetWidth > currentItemOffsetLeft)
							{
								var nextItem = rowItems[Number(i) + 1];
								//finding an appropriate down element
								if (
									nextItem
									&& nextItem.offsetLeft <= currentItemOffsetRight
								)
								{
									var leftItemDiff = rowItems[i].offsetLeft + rowItems[i].offsetWidth - currentItemOffsetLeft;
									var rightItemDiff = currentItemOffsetRight - nextItem.offsetLeft;

									if (rightItemDiff > leftItemDiff)
									{
										_this.SelectItem(nextItem);

										return true;
									}
								}

								_this.SelectItem(rowItems[i]);
								return true;
							}
						}

						//select last item in the row
						_this.SelectItem(rowItems[rowItems.length - 1]);
						return true;
					}
					else
					{
						for (var i in blocks)
						{
							if (blocks[i] == currentBlockNode)
							{
								//current selected item is the last item in the block, go to the next block
								if (blocks[Number(i) + 1])
								{
									_this.UnSelectAll();
									var item = BX.firstChild(blocks[Number(i) + 1], {className: "search-title-top-item-js"}, true);
									if (BX.type.isDomNode(item))
									{
										_this.SelectItem(item);
									}

									return true;
								}
							}
						}
					}
				}

				return true;

			case 38: // up key - navigate up on search results
				if(_this.RESULT.style.display == 'none')
					_this.RESULT.style.display = 'block';

				if (this.selectedItemDataId !== null)
				{
					currentItemNode = _this.RESULT.querySelector("[bx-search-item-id='" + _this.selectedItemDataId + "']");

					if (!BX.type.isDomNode(currentItemNode))
						return false;

					currentBlockNode = BX.findParent(currentItemNode, {className: "search-title-top-list-js"}, true);

					if (!BX.type.isDomNode(currentBlockNode))
						return false;

					currentBlockItems = BX.findChildren(currentBlockNode, {className: "search-title-top-item-js"}, true);
					currentItemOffsetLeft = currentItemNode.offsetLeft;
					currentItemOffsetTop = currentItemNode.offsetTop;
					currentItemWidth = currentItemNode.offsetWidth;
					currentItemOffsetRight = currentItemOffsetLeft + currentItemWidth;
					rowItems = [];
					nextTopOffset = null;

					currentBlockItems = currentBlockItems.reverse();

					for (i in currentBlockItems)
					{
						if (currentBlockItems[i].offsetTop >= currentItemOffsetTop)
						{
							continue;
						}
						else
						{
							if (nextTopOffset === null)
								nextTopOffset = currentBlockItems[i].offsetTop;
						}

						if (nextTopOffset && currentBlockItems[i].offsetTop == nextTopOffset)
						{
							rowItems.push(currentBlockItems[i]);
						}
					}

					rowItems = rowItems.reverse();

					if (rowItems.length > 0)
					{
						_this.UnSelectAll();

						for (i in rowItems)
						{
							if (rowItems[i].offsetLeft + rowItems[i].offsetWidth > currentItemOffsetLeft)
							{
								nextItem = rowItems[Number(i) + 1];
								//finding an appropriate down element
								if (
									nextItem
									&& nextItem.offsetLeft <= currentItemOffsetRight
								)
								{
									leftItemDiff = rowItems[i].offsetLeft + rowItems[i].offsetWidth - currentItemOffsetLeft;
									rightItemDiff = currentItemOffsetRight - nextItem.offsetLeft;

									if (rightItemDiff > leftItemDiff)
									{
										_this.SelectItem(nextItem);

										return true;
									}
								}

								_this.SelectItem(rowItems[i]);
								return true;
							}
						}

						//select last item in the row
						_this.SelectItem(rowItems[rowItems.length - 1]);
						return true;
					}
					else
					{
						//current selected item is the last item in the block, go to the next block
						for (var i in blocks)
						{
							if (blocks[i] == currentBlockNode)
							{
								if (blocks[Number(i) - 1])
								{
									_this.UnSelectAll();
									item = BX.firstChild(blocks[Number(i) - 1], {className: "search-title-top-item-js"}, true);
									if (BX.type.isDomNode(item))
									{
										_this.SelectItem(item);
									}
								}
							}
						}
					}
				}

				return true;

			case 39: // right key - navigate right on search results
				if (this.selectedItemDataId !== null)
				{
					currentItemNode = _this.RESULT.querySelector("[bx-search-item-id='" + _this.selectedItemDataId + "']");

					if (!BX.type.isDomNode(currentItemNode))
						return false;

					currentBlockNode = BX.findParent(currentItemNode, {className: "search-title-top-list-js"}, true);

					if (!BX.type.isDomNode(currentBlockNode))
						return false;

					currentBlockItems = BX.findChildren(currentBlockNode, {className: "search-title-top-item-js"}, true);
					currentItemOffsetLeft = currentItemNode.offsetLeft;
					currentItemOffsetTop = currentItemNode.offsetTop;

					for (i in currentBlockItems)
					{
						if (currentBlockItems[i].offsetTop != currentItemOffsetTop)
							continue;

						if (currentBlockItems[i].offsetLeft > currentItemOffsetLeft)
						{
							_this.UnSelectAll();
							_this.SelectItem(currentBlockItems[i]);

							return true;
						}
					}
				}

				return true;

			case 37: // left key - navigate left on search results
				if (this.selectedItemDataId !== null)
				{
					currentItemNode = _this.RESULT.querySelector("[bx-search-item-id='" + _this.selectedItemDataId + "']");

					if (!BX.type.isDomNode(currentItemNode))
						return false;

					currentBlockNode = BX.findParent(currentItemNode, {className: "search-title-top-list-js"}, true);

					if (!BX.type.isDomNode(currentBlockNode))
						return false;

					currentBlockItems = BX.findChildren(currentBlockNode, {className: "search-title-top-item-js"}, true);
					if (currentBlockItems)
					{
						currentBlockItems = currentBlockItems.reverse();
					}

					currentItemOffsetLeft = currentItemNode.offsetLeft;
					currentItemOffsetTop = currentItemNode.offsetTop;

					for (i in currentBlockItems)
					{
						if (currentBlockItems[i].offsetTop != currentItemOffsetTop)
							continue;

						if (currentBlockItems[i].offsetLeft < currentItemOffsetLeft)
						{
							_this.UnSelectAll();
							_this.SelectItem(currentBlockItems[i]);
							return true;
						}
					}
				}

				return true;

			case 13: // enter key - choose current search result
				if(_this.RESULT.style.display == 'block' && this.selectedItemDataId !== null)
				{
					currentItemNode = _this.RESULT.querySelector("[bx-search-item-id='" + _this.selectedItemDataId + "']");

					if (BX.type.isDomNode(currentItemNode))
					{
						var a = BX.findChild(currentItemNode, {'tag':'a'}, true);
						window.location = a.href;
					}
				}
				return false;
		}

		return false;
	};

	this.UnSelectAll = function()
	{
		var items = BX.findChildren(_this.RESULT, {"className" : "search-title-top-item-selected"}, true);
		for(var i = 0; i < items.length; i++)
		{
			_this.UnSelectItem(items[i]);
		}
	};

	this.SelectItem = function(element)
	{
		if (!BX.type.isDomNode(element))
			return;

		BX.addClass(element, "search-title-top-item-selected");
		_this.selectedItemDataId = element.getAttribute("bx-search-item-id");

		//check for toggle block
		var isGlobalSearchBlock = BX.findParent(element, {className: "search-title-top-block-tools"}, true);
		if (BX.type.isDomNode(isGlobalSearchBlock))
		{
			_this.toggleGlobalCategories("open");
		}
	};

	this.UnSelectItem = function(element)
	{
		if (!BX.type.isDomNode(element))
			return;

		BX.removeClass(element, "search-title-top-item-selected");

		//check for toggle block
		var isGlobalSearchBlock = BX.findParent(element, {className: "search-title-top-block-tools"}, true);
		if (BX.type.isDomNode(isGlobalSearchBlock))
		{
			_this.toggleGlobalCategories("close");
		}
	};

	/*this.onFocusLost = function()
	{
		if (_this.RESULT != null)
		{
			setTimeout(function() {
				if (!BX.SidePanel.Instance.isOpen())
				{
					_this.RESULT.style.display = 'none';
				}
			}, 250);
		}
	};*/

	this.onFocusGain = function()
	{
		if(_this.RESULT && _this.RESULT.innerHTML.length)
		{
			_this.RESULT.style.display = 'block';
		}
	};

	this.onKeyUp = function(event)
	{
		if (!_this.searchStarted)
		{
			return false;
		}

		event = event || window.event;

		if(
			event.keyCode == 37
			|| event.keyCode == 38
			|| event.keyCode == 39
			|| event.keyCode == 40
		)
			return;

		var text = BX.util.trim(_this.INPUT.value);

		if (
			text.length >= 1
			&& (
				text == _this.oldValue
				|| text == _this.oldClientValue
				|| text == _this.startText
			)
			&& !(
				text == _this.oldValue
				&& text != _this.oldClientValue
				&& _this.oldValue.length == _this.arParams.MIN_QUERY_LEN
				&& _this.oldClientValue.length == (_this.arParams.MIN_QUERY_LEN - 1)
			) // fix http://jabber.bx/view.php?id=96016
		)
		{
			return;
		}

		if (_this.xhr)
		{
			_this.xhr.abort();
		}

		if (text.length >= 1)
		{
			BX.removeClass(_this.CONTAINER.parentNode.parentNode, "header-search-empty");
			BX.addClass(_this.CONTAINER.parentNode.parentNode, "header-search-not-empty");

			_this.selectedItemDataId = null;

			_this.cache_key = _this.arParams.INPUT_ID + '|' + text;

			if (_this.cache[_this.cache_key] == null)
			{
				_this.blockAjax = false;

				var arSearchStringAlternatives = [ text ];
				_this.oldClientValue = text;

				var obSearch = { searchString: text };

				BX.onCustomEvent('findEntityByName', [
					_this.ITEMS,
					obSearch,
					{ },
					_this.ITEMS.oDbSearchResult
				]); // get result from the clientDb

				if (obSearch.searchString != text) // if text was converted to another charset
				{
					arSearchStringAlternatives.push(obSearch.searchString);
				}

				var result = _this.MakeResultFromClientDB(arSearchStringAlternatives, text);

				_this.searchByAjax = false;
				_this.ShowResult(result, (text.length >= _this.arParams.MIN_QUERY_LEN));

				if (text.length >= _this.arParams.MIN_QUERY_LEN)
				{
					_this.SendAjax(text);
				}
			}
			else
			{
				_this.blockAjax = true;
				_this.oldClientValue = text;
				_this.ShowResult(_this.cache[_this.cache_key], true, true);
			}
		}
		else
		{
			BX.addClass(_this.CONTAINER.parentNode.parentNode, "header-search-empty");
			BX.removeClass(_this.CONTAINER.parentNode.parentNode, "header-search-not-empty");

			if (_this.RESULT)
			{
				_this.RESULT.style.display = 'none';
			}
		}
	};

	this.SendAjax = BX.debounce(function(text)
	{
		if (_this.blockAjax)
		{
			return;
		}
		_this.oldValue = text;

		_this.xhr = BX.ajax({
			method: 'POST',
			dataType: _this.arParams.FORMAT,
			url: _this.arParams.AJAX_PAGE,
			data:  {
				'ajax_call':'y',
				'INPUT_ID':_this.arParams.INPUT_ID,
				'FORMAT':_this.arParams.FORMAT,
				'q':text
			},
			preparePost: true,
			onsuccess: function(result)
			{
				if (
					typeof result != 'undefined'
					&& result
					&& result.CATEGORIES != 'undefined'
				)
				{
					for (var categoryId in result.CATEGORIES)
					{
						if (result.CATEGORIES.hasOwnProperty(categoryId))
						{
							result.CATEGORIES[categoryId].ITEMS.sort(_this.resultCmp);
						}
					}

					_this.cache[_this.cache_key] = result;
					_this.searchByAjax = true;
					_this.ShowResult(result, false, true);
					_this.SyncResult(result, text);
				}
			}
		});
	}, 1000);

	this.onWindowResize = function()
	{
		if (_this.RESULT != null)
		{
			_this.ShowResult();
		}
	};

	this.onKeyDown = function(event)
	{
		event = event || window.event;

		_this.searchStarted = !(
			event.keyCode == 27
			|| event.keyCode == 40
			|| event.keyCode == 38
			|| event.keyCode == 13
		);

		if (_this.RESULT && _this.RESULT.style.display == 'block')
		{
			if(_this.onKeyPress(event.keyCode))
			{
				return BX.PreventDefault(event);
			}
		}
	};

	this.Init = function()
	{
		this.CONTAINER = BX(this.arParams.CONTAINER_ID);
		this.INPUT = BX(this.arParams.INPUT_ID);
		this.startText = this.oldValue = this.INPUT.value;

		BX.bind(this.INPUT, "focus", BX.proxy(this.onFocusGain, this));
		//BX.bind(window, "resize", BX.proxy(this.onWindowResize, this));
		//BX.bind(this.INPUT, "blur", BX.proxy(this.onFocusLost));
		this.INPUT.onkeydown = this.onKeyDown;
		
		BX.Finder(false, 'searchTitle', [], {}, _this);
		BX.onCustomEvent(_this, 'initFinderDb', [ this.ITEMS, 'searchTitle', null, ['users', 'sonetgroups', 'menuitems'], _this ]);
		setTimeout(function() {
			_this.CheckOldStorage(_this.ITEMS.obClientDbData);
		}, 5000);
		if (!this.ITEMS.bLoadAllInitialized)
		{
			BX.addCustomEvent('loadAllFinderDb', BX.delegate(function(params) {
				this.ItemsLoadAll(params);
			}, this));
			this.ITEMS.bLoadAllInitialized = true;
		}

		var closeIcon = BX.findChild(this.CONTAINER, {className: "search-title-top-delete"}, true);
		if (BX.type.isDomNode(closeIcon))
		{
			BX.bind(closeIcon, "click", BX.proxy(function (event)
			{
				this.INPUT.value = "";
				this.onKeyUp();
			}, this));
		}

		BX.bind(this.INPUT, "input", BX.proxy(function (event)
		{
			this.onKeyDown(event);
			this.onKeyUp(event);

			var loupeIcon = BX.findChild(this.CONTAINER, {className: "header-search-icon"}, true);
			if (BX.type.isDomNode(closeIcon))
			{
				loupeIcon.style.display = this.INPUT.value != "" ? "none" : "block";
			}

		}, this));

		BX.bind(document, "click", BX.proxy(this.checkAutoHide, this));
	};

	this.checkAutoHide = function(event)
	{
		if (
			_this.RESULT
			&& !_this.RESULT.contains(event.target)
			&& !document.forms["search-form"].contains(event.target)
		)
		{
			setTimeout(function() {
				_this.RESULT.style.display = 'none';
			}, 250);
		}
	};

	this.CheckOldStorage = function(obClientDbData)
	{
		if (!_this.ITEMS.obClientDb)
		{
			return;
		}

		var firstItem = null;
		var delta = 60*60*24*30; // 30 days
		var bNeedToClear = null;

		for (var key in obClientDbData)
		{
			if (obClientDbData.hasOwnProperty(key))
			{
				if (
					key == 'sonetgroups'
					|| key == 'menuitems'
				)
				{
					bNeedToClear = false;
					for (var code in obClientDbData[key])
					{
						if (obClientDbData[key].hasOwnProperty(code))
						{
							// first item
							firstItem = obClientDbData[key][code];
							if (
								typeof firstItem.timestamp != 'undefined'
								&& parseInt(firstItem.timestamp) > 0
								&& _this.arParams.CURRENT_TS > (parseInt(firstItem.timestamp) + delta)
							)
							{
								bNeedToClear = true;
							}
							break;
						}
					}
					if (bNeedToClear)
					{
						BX.Finder.clearEntityDb(_this.ITEMS.obClientDb, key);
					}
				}
			}
		}
	};

	this.ItemsLoadAll = function(params)
	{
		if (
			typeof params.entity != 'undefined'
			&& typeof this.ITEMS.initialized[params.entity] != 'undefined'
			&& !this.ITEMS.initialized[params.entity]
			&& typeof params.callback == 'function'
		)
		{
			if (
				params.entity == 'sonetgroups'
				|| params.entity == 'menuitems'
			)
			{
				BX.ajax.runAction('intranet.searchentity.getall', {
					data: {
						entity: params.entity
					},
				}).then(function(response) {
					if (typeof response.data.items != 'undefined')
					{
						BX.onCustomEvent('onFinderAjaxLoadAll', [ response.data.items, this.ITEMS, params.entity ]);
						params.callback();
					}
				}.bind(this), function (response) {
				});
			}

			this.ITEMS.initialized[params.entity] = true;
		}
	};

	BX.ready(function (){_this.Init(arParams);});
};
