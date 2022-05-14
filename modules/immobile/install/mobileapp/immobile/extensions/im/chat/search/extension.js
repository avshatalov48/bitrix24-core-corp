"use strict";
/**
 * @bxjs_lang_path extension.php
 */

// DEPENDENCIES
// chat/utils
// chat/restrequest
// chat/dataconveter
// chat/timer



var ChatSearch = {
	TYPE_USER: 'user',
	TYPE_CHAT: 'chat',
	MORE_TYPE_CACHE: 'cache',
	MORE_TYPE_EXTERNAL: 'external'
};


/* PUBLIC */

ChatSearch.init = function (config)
{
	this.debug = false;

	this.showChat = config.showChat !== false;
	this.businessOnly = config.businessOnly === true;

	this.resultLimit = parseInt(config.resultLimit)? config.resultLimit: 30;
	this.minTokenLength = parseInt(config.minTokenLength)? parseInt(config.minTokenLength): BX.componentParameters.get('SEARCH_MIN_SIZE', 3);

	if (typeof config.onDrawSearchResult == 'function')
	{
		this.onDrawSearchResult = config.onDrawSearchResult;
	}
	else
	{
		this.onDrawSearchResult = () => {};
		console.error("ChatSearch.init: function for draw result is not specified");
	}

	if (!config.dataConverterInited)
	{
		ChatDataConverter.init({
			'userId': config.userId,
			'generalChatId': config.generalChatId
		});
	}

	this.cacheIndex = {};
	this.cacheList = [];
	this.cacheRequest = {};

	this.lastSearchList = [];
	this.recentList = [];
	this.colleaguesList = [];
	this.skipList = [];

	this.result = {
		text: '',
		progressIcon: {},
		moreButton: {},
		offset: {},
		items: [],
		itemsIndex: {},
		types: {},
	};
};

ChatSearch.setSkipList = function(skipList)
{
	this.skipList = skipList;
};

ChatSearch.setRecentList = function(recentList)
{
	this.recentList = recentList;
};

ChatSearch.setColleaguesList = function(colleaguesList)
{
	this.colleaguesList = colleaguesList;
};

ChatSearch.setLastSearchList = function(lastSearchList)
{
	this.lastSearchList = lastSearchList;
};

ChatSearch.getElement = function(dialogId)
{
	let item = null;
	if (typeof this.cacheIndex[dialogId] != 'undefined')
	{
		item = this.cacheList[this.cacheIndex[dialogId]];
	}
	return item;
};

ChatSearch.find = function(text, offset)
{
	text = text.toString().trim();

	if (!text)
	{
		this.clear();
	}
	else if (text.length >= this.minTokenLength)
	{
		ChatTimer.stop('search', this.TYPE_USER, true);
		ChatRestRequest.abort('search-'+this.TYPE_USER);

		this.setMoreButton(this.TYPE_USER, false);
		this.setMoreButton(this.TYPE_CHAT, false);

		this.setText(text);
		this.setItems([]);

		this.findLocal(text, offset).then(result => {
			this.setItems(result.items);

			this.setProgressIcon(this.TYPE_USER, true);

			this.drawSearch();

			this.findExternal(this.TYPE_USER, result.text, result.offset);
		});
	}
	else
	{
		ChatTimer.stop('search', this.TYPE_USER, true);
		ChatRestRequest.abort('search-'+this.TYPE_USER);

		this.setMoreButton(this.TYPE_USER, false);
		this.setMoreButton(this.TYPE_CHAT, false);

		this.setText(text);
		this.setItems([]);

		this.findLocal(text, offset).then(result => {
			this.setItems(result.items);

			this.setProgressIcon(this.TYPE_USER, false);
			this.setProgressIcon(this.TYPE_CHAT, false);

			this.drawSearch();
		});
	}

	return true;
};

ChatSearch.selectElement = function(listElement)
{
	if (listElement.params.action == 'more')
	{
		let type = listElement.params.value;

		this.setProgressIcon(type, true);
		this.setMoreButton(type, false);
		this.drawSearch();
		this.findExternal(type, this.result.text, this.result.offset[listElement.params.value]);
	}

	return true;
};

ChatSearch.updateElement = function(dialogId, params)
{
	if (typeof this.cacheIndex[dialogId] != 'undefined')
	{
		this.cacheList[this.cacheIndex[dialogId]] = ChatUtils.objectMerge(this.cacheList[this.cacheIndex[dialogId]], params);
	}

	return true;
};

ChatSearch.deleteElement = function(dialogId)
{
	if (typeof this.cacheIndex[dialogId] != 'undefined')
	{
		delete this.cacheList[this.cacheIndex[dialogId]];
		delete this.cacheIndex[dialogId];
	}

	return true;
};

ChatSearch.clear = function(redraw)
{
	ChatTimer.stop('search', this.TYPE_USER, true);

	ChatRestRequest.abort('search-'+this.TYPE_USER);
	ChatRestRequest.abort('search-'+this.TYPE_CHAT);

	this.setText('');
	this.setProgressIcon(false);
	this.setMoreButton(this.TYPE_USER, false);
	this.setMoreButton(this.TYPE_CHAT, false);
	this.setOffset(this.TYPE_USER, 0);
	this.setOffset(this.TYPE_CHAT, 0);
	this.setItems([]);

	if (redraw)
	{
		this.drawSearch();
	}

	return true;
};



/* PRIVATE */

ChatSearch.findLocal = function(text, offset)
{
	offset = offset || 0;

	let cachePromise = new BX.Promise();

	let recentUserItems = [];
	let recentChatItems = [];

	this.lastSearchList.concat(this.recentList).concat(this.cacheList).map(element => {
		if (!element)
			return true;

		if (element.type == 'user')
		{
			recentUserItems.push(element.user);
		}
		else if (this.showChat)
		{
			recentChatItems.push(element.chat);
		}
	});

	recentUserItems = recentUserItems.concat(this.colleaguesList);

	let userItems = this.filter(text, ['name', 'work_position'], [recentUserItems]);
	let chatItems = this.filter(text, ['name'], [recentChatItems]);

	cachePromise.fulfill({
		text: text,
		offset: offset,
		items: userItems.slice(0, this.resultLimit).concat(chatItems.slice(0, this.resultLimit))
	});

	return cachePromise;
};

ChatSearch.findExternal = function(type, text, offset)
{
	offset = offset || 0;

	let promise = new BX.Promise();
	promise.then(result =>
	{
		this.cacheRequest[result.type][result.text][result.limit][result.offset] = result;

		let items = [];
		if (result.count)
		{
			for (let i in result.items)
			{
				if(!result.items.hasOwnProperty(i)) { continue; }
				items.push(result.items[i]);
			}

			this.indexItems(result.type, items);

			items = this.filter(result.text, ['name', 'work_position'], [items]).filter((element) => {
				return typeof this.result.itemsIndex[element.id] == 'undefined'
			});
			if (items.length > 0 || result.offset == 0)
			{
				this.appendItems(items, false);
				this.setMoreButton(result.type, result.hasMore);
			}
			else
			{
				this.setMoreButton(result.type, result.hasMore, BX.message('SEARCH_MORE_READY'));
			}

			this.setProgressIcon(result.type, false);

			this.setOffset(result.type, result.offset+result.limit);
		}
		else
		{
			this.setProgressIcon(result.type, false);
			this.setMoreButton(result.type, false);
		}
		this.drawSearch();
	}).catch(result => {

		if (result.error.ex.error == 'REQUEST_CANCELED')
		{
			console.info("ChatSearch.find: execute request canceled by user", result.error.ex);
		}
		else
		{
			console.error("ChatSearch.find: error has occurred ", result.error);

			this.setProgressIcon(result.type, false);
			this.setMoreButton(result.type, false);
			this.drawSearch();
		}
	});

	ChatTimer.stop('search', type, true);

	if (typeof this.cacheRequest[type] == 'undefined')
	{
		this.cacheRequest[type] = {};
	}
	if (typeof this.cacheRequest[type][text] == 'undefined')
	{
		this.cacheRequest[type][text] = {};
	}
	if (typeof this.cacheRequest[type][text][this.resultLimit] == 'undefined')
	{
		this.cacheRequest[type][text][this.resultLimit] = {};
	}
	if (typeof this.cacheRequest[type][text][this.resultLimit][offset] == 'undefined')
	{
		this.cacheRequest[type][text][this.resultLimit][offset] = false;
	}

	if (this.cacheRequest[type][text][this.resultLimit][offset])
	{
		promise.fulfill(this.cacheRequest[type][text][this.resultLimit][offset]);
	}
	else
	{
		ChatTimer.start('search', type, 1000, (id, params) =>
		{
			this.restRequest(params.type, params.text, params.limit, params.offset, params.promise)
		}, {
			type: type.toString(),
			text: text.toString(),
			limit: this.resultLimit.toString(),
			offset: offset.toString(),
			promise: promise
		});
	}

	return true;
};

ChatSearch.restRequest = function(type, text, limit, offset, promise)
{
	limit = parseInt(limit) || 15;
	offset = parseInt(offset) || 0;
	promise = promise || new BX.Promise();
	type = type == 'chat'? 'chat': 'user';

	ChatRestRequest.abort('search-'+type);

	let params = {
		'FIND': text,
		'LIMIT': limit,
		'OFFSET': offset
	};
	if (this.businessOnly)
	{
		params['BUSINESS'] = 'Y';
	}

	BX.rest.callMethod('im.search.'+type, params, null, (xhr) => {
		ChatRestRequest.register('search-'+type, xhr);
	}).then((result) =>
	{
		let type = result.query.method == 'im.search.user'? this.TYPE_USER: this.TYPE_CHAT;

		ChatRestRequest.unregister('search-'+type);
		if (result.data())
		{
			let hasMore = parseInt(result.total())-(parseInt(result.query.data.OFFSET)+parseInt(result.query.data.LIMIT)) > 0;

			let counter = 0;
			for (let i in result.data())
			{
				counter++;
			}

			promise.fulfill({
				type: type,
				text: result.query.data.FIND,
				offset: result.query.data.OFFSET,
				limit: result.query.data.LIMIT,
				total: result.total()? result.total(): 0,
				count: counter,
				hasMore: hasMore,
				items: result.data(),
			});
		}
		else
		{
			promise.reject({
				type: result.query.method == 'im.search.user'? this.TYPE_USER: this.TYPE_CHAT,
				text: result.query.TEXT,
				offset: result.query.OFFSET,
				limit: result.query.LIMIT,
				error: result.error()
			});
		}
	})
	.catch((result) =>
	{
		let type = result.query.method == 'im.search.user'? this.TYPE_USER: this.TYPE_CHAT;

		ChatRestRequest.unregister('search-'+type);

		promise.reject({
			type: type,
			text: result.query.TEXT,
			offset: result.query.OFFSET,
			limit: result.query.LIMIT,
			error: result.error()
		});
	});

	return promise;
};

ChatSearch.filter = function(text, fields, sources)
{
	let result = [];
	let foundElementId = {};

	sources.forEach((source) =>
	{
		let sourceResult = source.filter(item =>
		{
			let fieldResult = false;
			fields.forEach((field) =>
			{
				if (item[field] && !foundElementId[item.id])
				{
					if (item[field].toUpperCase().indexOf(text.toUpperCase()) == 0)
					{
						fieldResult = true;
						foundElementId[item.id] = true;
					}
					else
					{
						item[field].toUpperCase().split(' ').forEach((word) =>
						{
							if (word.indexOf(text.toUpperCase()) == 0)
							{
								fieldResult = true;
								foundElementId[item.id] = true;
							}
						});
					}
				}
			});

			return fieldResult;
		});

		result = result.concat(sourceResult);
	});

	return result.slice(0, this.resultLimit);
};

ChatSearch.indexItems = function(type, items)
{
	items.map((element) => {
		let item = {
			avatar: {},
			user: {id: 0}
		};
		if (type == 'chat')
		{
			item.type = 'chat';
			item.id = 'chat'+element.id;

			element.id = parseInt(element.id);
			element.date_create = new Date(element.date_create);
			item.chat = element.chat;

			item.avatar.url = element.avatar;
			item.avatar.color = element.color;
			item.title = element.name;
		}
		else
		{
			item.type = 'user';
			item.id = parseInt(element.id);

			item.user = element = ChatDataConverter.getUserDataFormat(element);

			item.avatar.url = element.avatar;
			item.avatar.color = element.color;
			item.title = element.name;
		}

		if (typeof this.cacheIndex[item.id] != 'undefined')
		{
			this.cacheList[this.cacheIndex[item.id]] = item;
		}
		else
		{
			this.cacheIndex[item.id] = this.cacheList.length;
			this.cacheList.push(item);
		}
	});

	return true;
};

ChatSearch.setText = function(text)
{
	this.result.text = text;
};

ChatSearch.setProgressIcon = function(type, active)
{
	this.result.progressIcon[type] = active == true;
};

ChatSearch.setMoreButton = function(type, active, text)
{
	this.result.moreButton[type] = {active: active == true, text: text? text: BX.message("SEARCH_MORE")};
};

ChatSearch.setOffset = function(type, offset)
{
	this.result.offset[type] = offset || 0;
};

ChatSearch.setItems = function(items, filter)
{
	let result = [];
	if (items.length <= 0)
	{
		this.result.itemsIndex = {};
	}
	else
	{
		if (filter !== false)
		{
			items = items.filter((element) => {
				return typeof this.result.itemsIndex[element.id] == 'undefined'
			})
		}
		items.map((element) => {
			if (this.skipList.length > 0 && this.skipList.indexOf(element.id) > -1)
			{
				return true;
			}
			this.result.itemsIndex[element.id] = true;
			result.push(ChatDataConverter.getSearchElementFormat(element));

			return true;
		});
	}

	this.result.items = result;
};

ChatSearch.appendItems = function(items, filter)
{
	let result = [];
	if (filter !== false)
	{
		items = items.filter((element) => {
			return typeof this.result.itemsIndex[element.id] == 'undefined'
		})
	}
	items.map((element) => {
		if (this.skipList.length > 0 && this.skipList.indexOf(element.id) > -1)
		{
			return true;
		}
		this.result.itemsIndex[element.id] = true;
		result.push(ChatDataConverter.getSearchElementFormat(element));
	});

	this.result.items = this.result.items.concat(result);
};

ChatSearch.drawSearch = function()
{
	let executeTime = new Date();

	let items = ChatUtils.objectClone(this.result.items);

	if (this.result.moreButton[this.TYPE_USER] && this.result.moreButton[this.TYPE_USER]['active'])
	{
		items.push({title : this.result.moreButton[this.TYPE_USER]['text'], type : "button", sectionCode: 'user', params: { action: 'more', value: 'user'}});
	}

	if (this.result.moreButton[this.TYPE_CHAT] && this.result.moreButton[this.TYPE_CHAT]['active'])
	{
		items.push({title : this.result.moreButton[this.TYPE_CHAT]['text'], type : "button", sectionCode: 'chat', params: { action: 'more', value: 'chat'}});
	}

	let showProgress = false;
	if (this.result.progressIcon[this.TYPE_USER] || this.result.progressIcon[this.TYPE_CHAT])
	{
		showProgress = true;
		if (!this.result.types[this.TYPE_USER] && !this.result.types[this.TYPE_CHAT])
		{
			items.push({title : BX.message("SEARCH"), type : "loading", unselectable: true, params: { action: 'progress'}});
		}
		else
		{
			if (this.result.progressIcon[this.TYPE_USER])
			{
				items.push({title : BX.message("SEARCH"), type : "loading", unselectable: true, sectionCode: 'user', params: { action: 'progress'}});
			}
			if (this.result.progressIcon[this.TYPE_CHAT])
			{
				items.push({title : BX.message("SEARCH"), type : "loading", unselectable: true, sectionCode: 'chat', params: { action: 'progress'}});
			}
		}
	}

	if (!showProgress && this.result.items.length <= 0)
	{
		items.push(
			{title : BX.message("SEARCH_EMPTY").replace("#TEXT#", this.result.text), type:"button", unselectable: true, params: { action: 'empty'}}
		);
	}

	let section = [];

	this.result.types[this.TYPE_USER] = false;
	this.result.types[this.TYPE_CHAT] = false;

	for (let i=0, l=items.length; i<l; i++)
	{
		if (items[i].sectionCode == 'user')
		{
			this.result.types[this.TYPE_USER] = true;
		}
		else if (items[i].sectionCode == 'chat')
		{
			this.result.types[this.TYPE_CHAT] = true;
		}
	}

	if (this.result.types[this.TYPE_USER])
	{
		section.push({title : BX.message("SEARCH_EMPLOYEES"), id : "user", backgroundColor : "#FFFFFF"});
	}
	if (this.result.types[this.TYPE_CHAT])
	{
		section.push({title : BX.message("SEARCH_CHATS"), id : "chat", backgroundColor : "#FFFFFF"});
	}

	this.onDrawSearchResult(items, section);

	console.info("ChatSearch.drawSearch: update search results - "+items.length+" elements ("+(new Date() - executeTime)+'ms)', this.result.text);

	return true;
};