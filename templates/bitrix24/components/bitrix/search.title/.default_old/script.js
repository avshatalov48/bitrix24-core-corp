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
		'SEARCH_PAGE': (typeof arParams.SEARCH_PAGE != 'undefined' ? arParams.SEARCH_PAGE : '')
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
		oDbUserSearchResult: {}
	};
	this.searchByAjax = false;
	this.currentItemId = null;

	this.CreateResultWrap = function()
	{
		if (_this.RESULT == null)
		{
			this.RESULT = document.body.appendChild(document.createElement("DIV"));
			this.RESULT.className = 'title-search-result title-search-result-header';
		}
	};

	this.MakeResultFromClientDB = function(arSearchStringAlternatives, searchStringOriginal)
	{
		var result = null;

		var key, i, j, entityCode, prefix = null;
		for (key = 0; key < arSearchStringAlternatives.length; key++)
		{
			searchString = arSearchStringAlternatives[key].toLowerCase();
			if (
				typeof _this.ITEMS.oDbUserSearchResult[searchString] != 'undefined'
				&& _this.ITEMS.oDbUserSearchResult[searchString].length > 0 // results from local DB
			)
			{
				for (i=0;i<_this.ITEMS.oDbUserSearchResult[searchString].length;i++)
				{
					entityCode =_this.ITEMS.oDbUserSearchResult[searchString][i];
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
									URL: _this.ITEMS.obClientDbData.menuitems[entityCode].entityId
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

			result.CATEGORIES['all'] = {
				ITEMS: [
					{
						NAME: BX.message('BITRIX24_SEARCHTITLE_ALL'),
						URL: BX.util.add_url_param(_this.arParams.SEARCH_PAGE, {'q': searchStringOriginal})
					}
				]
			};
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
		var htmlResult = null;
		var rows = [];
		var category = currentItem = tdClassName = itemBlock = null;
		var i = 0;

		var resultEmpty = true;

		if (typeof jsonResult.CATEGORIES != 'undefined')
		{
			for (var categoryId in jsonResult.CATEGORIES)
			{
				if (jsonResult.CATEGORIES.hasOwnProperty(categoryId))
				{
					if (resultEmpty)
					{
						resultEmpty = false;
					}
					category = jsonResult.CATEGORIES[categoryId];

					rows.push(BX.create('tr', {
						children: [
							BX.create('th', {
								props: {
									className: 'title-search-separator'
								}
							}),
							BX.create('td', {
								props: {
									className: 'title-search-separator'
								}
							})
						]
					}));

					if (typeof category.ITEMS != 'undefined')
					{
						i = 0;
						for (var itemId in category.ITEMS)
						{
							if (category.ITEMS.hasOwnProperty(itemId))
							{
								if (i >= 7)
								{
									break;
								}
								i++;

								currentItem = category.ITEMS[itemId];
								if (categoryId === 'all')
								{
									tdClassName = 'title-search-all';
								}
								else if (typeof currentItem.ICON != 'undefined')
								{
									tdClassName = 'title-search-item';
								}
								else
								{
									tdClassName = 'title-search-more';
								}

								if (
									typeof currentItem.TYPE != 'undefined'
									&& currentItem.TYPE.length > 0
								)
								{
									itemBlock = BX.create('a', {
										attrs: {
											href: currentItem.URL
										},
										children: [
											BX.create('span', {
												attrs: {
													style: (typeof currentItem.ICON != 'undefined' && currentItem.ICON.length > 0 ? "background-image: url('" + currentItem.ICON + "')" : '')
												},
												props: {
													className: 'title-search-item-img title-search-item-img-' + currentItem.TYPE
												}
											}),
											BX.create('span', {
												props: {
													className: 'title-search-item-text'
												},
												html: currentItem.NAME
											})
										]
									});
								}
								else
								{
									itemBlock = BX.create('a', {
										attrs: {
											href: currentItem.URL
										},
										html: currentItem.NAME
									});
								}

								rows.push(BX.create('tr', {
									attrs: {
										'bx-search-item-id': currentItem.ITEM_ID
									},
									children: [
										BX.create('th', {
											html: (itemId == 0 ? category.TITLE : '')
										}),
										BX.create('td', {
											props: {
												className: tdClassName
											},
											children: [
												itemBlock
											]
										})
									]
								}));
							}
						}
					}
				}
			}

			if (!!showWaiter)
			{
				rows.push(BX.create('tr', {
					children: [
						BX.create('th', {
						}),
						BX.create('td', {
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
			}

			if (!resultEmpty)
			{
				rows.push(BX.create('tr', {
					children: [
						BX.create('th', {
							props: {
								className: 'title-search-separator'
							}
						}),
						BX.create('td', {
							props: {
								className: 'title-search-separator'
							}
						})
					]
				}));
			}

			htmlResult = BX.create('table', {
				props: {
					className: 'title-search-result'
				},
				children: [
					BX.create('colgroup', {
						children: [
							BX.create('col', {
								attrs: {
									width: '150px'
								}
							}),
							BX.create('col', {
								attrs: {
									width: '*'
								}
							})
						]
					}),
					BX.create('tbody', {
						children: rows
					})
				]
			});
		}

		return htmlResult;
	};

	this.ShowResult = function(result, showWaiter)
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

		if(result != null)
		{
			if (typeof _this.arParams.FORMAT != 'undefined' && _this.arParams.FORMAT == 'json')
			{
				result = _this.BuildResult(result, !!showWaiter);
				BX.cleanNode(_this.RESULT);
				_this.RESULT.appendChild(result);
			}
			else
			{
				_this.RESULT.innerHTML = result;
			}
		}
		else
		{
			_this.RESULT.innerHTML = '';
		}

		_this.RESULT.style.display = _this.RESULT.innerHTML.length > 0 ? 'block' : 'none';
	};

	this.SyncResult = function(result)
	{
		var ajaxDbEntities = null;
		for (i=0;i<_this.arParams.CATEGORIES_ALL.length;i++)
		{
			if (
				typeof _this.arParams.CATEGORIES_ALL[i].CODE != 'undefined'
				&& typeof result.CATEGORIES[i] != 'undefined'
			)
			{
				if (_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_menuitems')
				{
					ajaxDbEntities = {};
					for (j=0;j<result.CATEGORIES[i].ITEMS.length;j++)
					{
						ajaxDbEntities[result.CATEGORIES[i].ITEMS[j].ITEM_ID] = _this.ConvertAjaxToClientDB(result.CATEGORIES[i].ITEMS[j], 'menuitems');
					}
					BX.onCustomEvent(_this, 'onFinderAjaxSuccess', [ ajaxDbEntities, _this.ITEMS, 'menuitems' ]);
				}
				else if (_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_sonetgroups')
				{
					ajaxDbEntities = {};
					for (j=0;j<result.CATEGORIES[i].ITEMS.length;j++)
					{
						ajaxDbEntities[result.CATEGORIES[i].ITEMS[j].ITEM_ID] = _this.ConvertAjaxToClientDB(result.CATEGORIES[i].ITEMS[j], 'sonetgroups');
					}
					BX.onCustomEvent(_this, 'onFinderAjaxSuccess', [ ajaxDbEntities, _this.ITEMS, 'sonetgroups' ]);
				}
				else if (_this.arParams.CATEGORIES_ALL[i].CODE == 'custom_users')
				{
					ajaxDbEntities = {};
					for (j=0;j<result.CATEGORIES[i].ITEMS.length;j++)
					{
						ajaxDbEntities[result.CATEGORIES[i].ITEMS[j].ITEM_ID] = _this.ConvertAjaxToClientDB(result.CATEGORIES[i].ITEMS[j], 'users');
					}
					BX.onCustomEvent(_this, 'onFinderAjaxSuccess', [ ajaxDbEntities, _this.ITEMS, 'users' ]);
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
				checksum: oEntity.CHECKSUM
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
		var tbl = BX.findChild(_this.RESULT, {'tag':'table','class':'title-search-result'}, true);

		if(!tbl)
			return false;

		var cnt = tbl.rows.length,
			i = 0;

		switch (keyCode)
		{
			case 27: // escape key - close search div
				_this.RESULT.style.display = 'none';
				_this.currentRow = -1;
				_this.UnSelectAll();
				return true;

			case 40: // down key - navigate down on search results

				if(_this.RESULT.style.display == 'none')
					_this.RESULT.style.display = 'block';

				var first = -1;
				for(i = 0; i < cnt; i++)
				{
					if(
						!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true)
						&& !BX.findChild(tbl.rows[i], {'class':'title-search-waiter'}, true)
					)
					{
						if(first == -1)
							first = i;

						if(_this.currentRow < i)
						{
							_this.currentRow = i;
							break;
						}
						else
						{
							_this.UnSelectItem(tbl, i);
						}
					}
				}

				if(i == cnt && _this.currentRow != i)
				{
					_this.currentRow = first;
				}

				if (!_this.searchByAjax)
				{
					_this.SaveCurrentItemId(tbl, _this.currentRow);
				}

				_this.SelectItem(tbl, _this.currentRow);
				return true;

			case 38: // up key - navigate up on search results
				if(_this.RESULT.style.display == 'none')
					_this.RESULT.style.display = 'block';

				var last = -1;
				for(i = cnt-1; i >= 0; i--)
				{
					if(
						!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true)
						&& !BX.findChild(tbl.rows[i], {'class':'title-search-waiter'}, true)
					)
					{
						if(last == -1)
							last = i;

						if(_this.currentRow > i)
						{
							_this.currentRow = i;
							break;
						}
						else
						{
							_this.UnSelectItem(tbl, i);
						}
					}
				}

				if(i < 0 && _this.currentRow != i)
				{
					_this.currentRow = last;
				}

				if (!_this.searchByAjax)
				{
					_this.SaveCurrentItemId(tbl, _this.currentRow);
				}

				_this.SelectItem(tbl, _this.currentRow);
				return true;

			case 13: // enter key - choose current search result
				if(_this.RESULT.style.display == 'block')
				{
					for(i = 0; i < cnt; i++)
					{
						if(_this.currentRow == i)
						{
							if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
							{
								var a = BX.findChild(tbl.rows[i], {'tag':'a'}, true);
								if(a)
								{
									window.location = a.href;
									return true;
								}
							}
						}
					}
				}
				return false;
		}

		return false;
	};

	this.UnSelectAll = function()
	{
		var tbl = BX.findChild(_this.RESULT, {'tag':'table','class':'title-search-result'}, true);
		if(tbl)
		{
			var cnt = tbl.rows.length;
			for(var i = 0; i < cnt; i++)
				tbl.rows[i].className = '';
		}
	};

	this.SelectItem = function(tbl, rowNum)
	{
		tbl.rows[rowNum].className = 'title-search-selected';
	};

	this.UnSelectItem = function(tbl, rowNum)
	{
		if(tbl.rows[rowNum].className == 'title-search-selected')
		{
			tbl.rows[rowNum].className = '';
		}
	};

	this.SaveCurrentItemId = function(tbl, rowNum)
	{
		this.currentItemId = tbl.rows[rowNum].getAttribute('bx-search-item-id');
	};

	this.EnableMouseEvents = function()
	{
		var tbl = BX.findChild(_this.RESULT, {'tag':'table','class':'title-search-result'}, true);
		if(tbl)
		{
			var cnt = tbl.rows.length;

			if (cnt > 0)
			{
				_this.currentRow = 1;
				_this.SelectItem(tbl, _this.currentRow);
			}

			var itemId = null;

			for(var i = 0; i < cnt; i++)
			{
				if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
				{
					tbl.rows[i].id = 'row_' + i;

					itemId = tbl.rows[i].getAttribute('bx-search-item-id');

					if (
						this.searchByAjax
						&& BX.type.isNotEmptyString(itemId)
						&& itemId == this.currentItemId
					)
					{
						_this.UnSelectAll();
						_this.currentRow = tbl.rows[i].id.substr(4);
						_this.SelectItem(tbl, _this.currentRow);
					}

					tbl.rows[i].onmouseover = function (e) {
						if(_this.currentRow != this.id.substr(4))
						{
							_this.UnSelectAll();
							_this.currentRow = this.id.substr(4);
							_this.SelectItem(tbl, _this.currentRow);
						}
					};
					tbl.rows[i].onmouseout = function (e) {
						this.className = '';
						_this.currentRow = -1;
					};
				}
			}
		}
	};

	this.onFocusLost = function(hide)
	{
		if (_this.RESULT != null)
		{
			setTimeout(function() {_this.RESULT.style.display = 'none'}, 250);
		}
	};

	this.onFocusGain = function()
	{
		_this.CreateResultWrap();
		if(_this.RESULT && _this.RESULT.innerHTML.length)
		{
			_this.ShowResult();
		}

		BX.bind(_this.INPUT, 'keyup', _this.onKeyUp);
		BX.bind(_this.INPUT, 'paste', _this.onPaste);
	};

	this.onKeyUp = function(event)
	{
		if (!_this.searchStarted)
		{
			return false;
		}

		var text = BX.util.trim(_this.INPUT.value);

		if (
			text == _this.oldValue
			|| text == _this.oldClientValue
			|| text == _this.startText
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
			_this.cache_key = _this.arParams.INPUT_ID + '|' + text;

			if (_this.cache[_this.cache_key] == null)
			{
				var arSearchStringAlternatives = [ text ];
				_this.oldClientValue = text;

				var obSearch = { searchString: text };

				BX.onCustomEvent('findEntityByName', [
					_this.ITEMS,
					obSearch,
					{ },
					_this.ITEMS.oDbUserSearchResult
				]); // get result from the clientDb

				if (obSearch.searchString != text) // if text was converted to another charset
				{
					arSearchStringAlternatives.push(obSearch.searchString);
				}

				var result = _this.MakeResultFromClientDB(arSearchStringAlternatives, text);
				_this.searchByAjax = false;
				_this.ShowResult(result, (text.length >= _this.arParams.MIN_QUERY_LEN));
				_this.EnableMouseEvents();

				if (text.length >= _this.arParams.MIN_QUERY_LEN)
				{
					_this.oldValue = text;
					_this.SendAjax(text);
				}
			}
			else
			{
				_this.ShowResult(_this.cache[_this.cache_key]);
				_this.currentRow = -1;
				_this.EnableMouseEvents();
			}
		}
		else
		{
			//_this.RESULT.style.display = 'none';
			_this.currentRow = -1;
			_this.UnSelectAll();
		}
	};

	this.SendAjax = BX.debounce(function(text)
	{
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
				if (typeof result != 'undefined')
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
					_this.ShowResult(result);
					_this.SyncResult(result);
					_this.currentRow = -1;
					_this.EnableMouseEvents();
				}
			}
		});
	}, 1000);

	this.onPaste = function(event)
	{

	};

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
				return BX.PreventDefault(event);
		}
	};

	this.Init = function()
	{
		this.CONTAINER = BX(this.arParams.CONTAINER_ID);
		this.INPUT = BX(this.arParams.INPUT_ID);
		this.startText = this.oldValue = this.INPUT.value;

		BX.bind(this.INPUT, "focus", BX.proxy(this.onFocusGain, this));
		BX.bind(window, "resize", BX.proxy(this.onWindowResize, this));
		BX.bind(this.INPUT, "blur", BX.proxy(this.onFocusLost));
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
				BX.ajax({
					url: this.arParams.AJAX_PAGE,
					method: 'POST',
					dataType: 'json',
					data: {
						'ajax_call' : 'y',
						'sessid': BX.bitrix_sessid(),
						'FORMAT': 'json',
						'q': 'empty', // for compatibility
						'get_all': params.entity
					},
					onsuccess: BX.delegate(function(data)
					{
						if (typeof data.ALLENTITIES != 'undefined')
						{
							BX.onCustomEvent('onFinderAjaxLoadAll', [ data.ALLENTITIES, this.ITEMS, params.entity ]);
						}
						params.callback();
					}, this),
					onfailure: function(data)
					{
					}
				});
			}

			this.ITEMS.initialized[params.entity] = true;
		}
	};


	BX.ready(function (){_this.Init(arParams);});
};