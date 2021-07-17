"use strict";
/**
 * @bxjs_lang_path extension.php
 */

// DEPENDENCIES
// chat/utils
// chat/restrequest
// chat/dataconveter
// chat/timer



var ChatSearchScopes = {
	TYPE_USER: 'user',
	TYPE_CHAT: 'chat',
	TYPE_LINE: 'line',
	TYPE_DEPARTMENT: 'department',
	TYPE_DEPARTMENT_USER: 'departmentUser',
	MORE_TYPE_CACHE: 'cache',
	MORE_TYPE_EXTERNAL: 'external'
};


/* PUBLIC */

ChatSearchScopes.init = function (config)
{
	this.debug = false;

	this.businessOnly = config.businessOnly === true;

	this.resultLimit = parseInt(config.resultLimit)? config.resultLimit: 30;

	this.minTokenLength = {};
	this.minTokenLengthDefault = parseInt(config.minTokenLength)? parseInt(config.minTokenLength): BX.componentParameters.get('SEARCH_MIN_SIZE', 3);

	if (typeof config.onDrawSearchResult == 'function')
	{
		this.onDrawSearchResult = config.onDrawSearchResult;
	}
	else
	{
		this.onDrawSearchResult = () => {};
		console.error("ChatSearchScopes.init: function for draw result is not specified");
	}

	if (!config.dataConverterInited)
	{
		ChatDataConverter.init({
			'userId': config.userId,
			'generalChatId': config.generalChatId
		});
	}

	this.list = {};
	this.skipList = {};
	this.skipByProperty = {};
	this.externalSearchEnable = {};

	this.cacheIndex = {};
	this.cacheList = {};
	this.cacheRequest = {};

	this.setType(config.listType);

	this.result = {
		text: '',
		progressIcon: false,
		moreButton: false,
		offset: {},
		items: [],
		itemsIndex: {},
		types: {},
	};
};

ChatSearchScopes.setType = function(type = this.TYPE_USER)
{
	this.listType = type;

	if (typeof this.cacheIndex[this.listType] == 'undefined')
	{
		this.cacheIndex[this.listType] = {};
	}
	if (typeof this.cacheList[this.listType] == 'undefined')
	{
		this.cacheList[this.listType] = [];
	}
	if (typeof this.cacheRequest[this.listType] == 'undefined')
	{
		this.cacheRequest[this.listType] = {};
	}

	if (typeof this.list[this.listType] == 'undefined')
	{
		this.list[this.listType] = [];
	}
	if (typeof this.skipList[this.listType] == 'undefined')
	{
		this.skipList[this.listType] = [];
	}
	if (typeof this.skipByProperty[this.listType] == 'undefined')
	{
		this.skipByProperty[this.listType] = {};
	}

	return true;
};

ChatSearchScopes.setSkipList = function(skipList, type = this.listType)
{
	this.skipList[type] = skipList;
};

ChatSearchScopes.setSkipByProperty = function(propertyList, type = this.listType)
{
	this.skipByProperty[type] = propertyList;
};

ChatSearchScopes.setList = function(list, type = this.listType)
{
	this.list[type] = list;
};

ChatSearchScopes.setMinTokenLength = function(list = this.minTokenLengthDefault, type = this.listType)
{
	this.minTokenLength[type] = list;
};

ChatSearchScopes.getMinTokenLength = function(type = this.listType)
{
	return typeof this.minTokenLength[type] == 'number'? this.minTokenLength[type]: this.minTokenLengthDefault;
};

ChatSearchScopes.setExternalSearchEnable = function(result = true, type = this.listType)
{
	this.externalSearchEnable[type] = result === true;
};

ChatSearchScopes.find = function(text, offset, type = this.listType)
{
	text = text.toString().trim();

	if (!text)
	{
		this.clear();
	}
	else if (
		this.externalSearchEnable[type] !== false
		&& text.length >= this.getMinTokenLength(type)
	)
	{
		ChatTimer.stop('search', null, true);
		ChatRestRequest.abort('search');

		this.setMoreButton(false);

		this.setText(text);
		this.setItems([]);

		this.findLocal(text, offset, type).then(result => {
			this.setItems(result.items);
			this.setProgressIcon(true);
			this.drawSearch();

			this.findExternal(result.text, result.offset, result.type);
		});
	}
	else
	{
		ChatTimer.stop('search', null, true);
		ChatRestRequest.abort('search');

		this.setMoreButton(false);

		this.setText(text);
		this.setItems([]);

		this.findLocal(text, offset, type).then(result => {
			this.setItems(result.items);
			this.setProgressIcon(false);
			this.drawSearch();
		});
	}

	return true;
};


ChatSearchScopes.selectElement = function(listElement)
{
	if (listElement.params.action != 'more')
	{
		return true;
	}

	this.setProgressIcon(true);
	this.setMoreButton(false);
	this.drawSearch();

	this.findExternal(this.result.text, this.result.offset, listElement.params.value);

	return true;
};

ChatSearchScopes.updateElement = function(dialogId, params, type = this.listType)
{
	if (typeof this.cacheIndex[type][dialogId] == 'undefined')
	{
		return false;
	}

	this.cacheList[type][this.cacheIndex[type][dialogId]] = ChatUtils.objectMerge(this.cacheList[type][this.cacheIndex[type][dialogId]], params);

	return true;
};

ChatSearchScopes.deleteElement = function(dialogId, type = this.listType)
{
	if (typeof this.cacheIndex[type][dialogId] == 'undefined')
	{
		return false;
	}

	delete this.cacheList[type][this.cacheIndex[dialogId]];
	delete this.cacheIndex[type][dialogId];

	return true;
};

ChatSearchScopes.getElement = function(dialogId, type = this.listType)
{
	let item = null;

	if (typeof this.cacheIndex[type][dialogId] != 'undefined')
	{
		item = this.cacheList[type][this.cacheIndex[type][dialogId]];
	}

	return item;
};


ChatSearchScopes.clear = function(redraw)
{
	ChatTimer.stop('search', null, true);
	ChatRestRequest.abort('search');

	this.setText('');
	this.setProgressIcon(false);
	this.setMoreButton(false);
	this.setOffset(0);
	this.setItems([]);

	if (redraw)
	{
		this.drawSearch();
	}

	return true;
};



/* PRIVATE */

ChatSearchScopes.findLocal = function(text, offset, type = this.listType)
{
	offset = offset || 0;

	let cachePromise = new BX.Promise();

	let findFields = ['name'];
	if (type == this.TYPE_USER)
	{
		findFields = ['name', 'work_position'];
	}
	else if (type == this.TYPE_DEPARTMENT)
	{
		findFields = ['name', 'full_name'];
	}

	cachePromise.fulfill({
		type: type,
		text: text,
		offset: offset,
		items: this.filter(
			text,
			findFields,
			[this.list[type].concat(this.cacheList[type])]
		)
	});

	return cachePromise;
};

ChatSearchScopes.findExternal = function(text, offset, type = this.listType)
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

			this.indexItems(items, result.type);

			let findFields = ['name'];
			if (type == this.TYPE_USER)
			{
				findFields = ['name', 'work_position'];
			}
			else if (type == this.TYPE_DEPARTMENT)
			{
				findFields = ['name', 'full_name'];
			}

			if (type == this.TYPE_CHAT)
			{
				items = items.slice(0, this.resultLimit).filter((element) => {
					return typeof this.result.itemsIndex[element.id] == 'undefined'
				});
			}
			else
			{
				items = this.filter(result.text, findFields, [items]).filter((element) => {
					return typeof this.result.itemsIndex[element.id] == 'undefined'
				});
			}

			if (items.length > 0 || result.offset == 0)
			{
				this.appendItems(items, false, result.type);
				if (items.length > 0)
				{
					this.setMoreButton(result.hasMore);
				}
			}
			else
			{
				this.setMoreButton(result.hasMore, BX.message('SEARCH_MORE_READY'));
			}

			this.setProgressIcon(false);

			this.setOffset(result.offset+result.limit, result.type);
		}
		else
		{
			this.setProgressIcon(false);
			this.setMoreButton(false);
		}
		this.drawSearch();
	}).catch(result => {

		if (result.error.ex.error == 'REQUEST_CANCELED')
		{
			console.info("ChatSearchScopes.find: execute request canceled by user", result.error.ex);
		}
		else
		{
			console.error("ChatSearchScopes.find: error has occurred ", result.error);

			this.setProgressIcon(result.type, false);
			this.setMoreButton(result.type, false);
			this.drawSearch();
		}
	});

	ChatTimer.stop('search', null, true);

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
		ChatTimer.start('search', null, 1000, (id, params) => {
			this.restRequest(params.text, params.limit, params.offset, params.promise, params.type)
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

ChatSearchScopes.restRequest = function(text, limit = 15, offset = 0, promise, type = this.listType)
{
	limit = parseInt(limit);
	offset = parseInt(offset);
	promise = promise || new BX.Promise();

	let requestMethod = '';
	let requestParams = {};

	if (type == this.TYPE_USER || type == this.TYPE_CHAT || type == this.TYPE_DEPARTMENT)
	{
		requestMethod = 'im.search.'+type;
		requestParams = {
			'FIND': text,
			'LIMIT': limit,
			'OFFSET': offset
		};
		if (
			(type == this.TYPE_USER || type == this.TYPE_CHAT)
			&& this.businessOnly
		)
		{
			requestParams['BUSINESS'] = 'Y';
		}
	}
	else
	{
		promise.reject({
			type: type,
			text: text,
			offset: offset,
			limit: limit,
			error: {
				ex: {
					error: 'REQUEST_CANCELED',
					error_description: 'Type is not implemented!',
				}
			}
		});

		return false;
	}

	console.info(`ChatSearchScopes.restRequest: %c${requestMethod}`, 'font-weight: bold;', requestParams);

	ChatRestRequest.abort('search');

	BX.rest.callMethod(requestMethod, requestParams, null, (xhr) => {
		ChatRestRequest.register('search', xhr);
	}).then((result) =>
	{
		ChatRestRequest.unregister('search');
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
				type: type,
				text: result.query.TEXT,
				offset: result.query.OFFSET,
				limit: result.query.LIMIT,
				error: result.error()
			});
		}
	})
	.catch((result) =>
	{
		ChatRestRequest.unregister('search');

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

ChatSearchScopes.filter = function(text, fields, sources)
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

ChatSearchScopes.indexItems = function(items, type = this.listType)
{
	items.map((element) => {
		if (typeof this.cacheIndex[type][element.id] == 'undefined')
		{
			this.cacheIndex[type][element.id] = this.cacheList[type].length;
			this.cacheList[type].push(element);
		}
		else
		{
			this.cacheList[type][this.cacheIndex[type][element.id]] = element;
		}
	});

	return true;
};

ChatSearchScopes.setText = function(text)
{
	this.result.text = text;
};

ChatSearchScopes.setProgressIcon = function(active)
{
	this.result.progressIcon = active == true;
};

ChatSearchScopes.setMoreButton = function(active, text)
{
	this.result.moreButton = {active: active == true, text: text? text: BX.message("SEARCH_MORE")};
};

ChatSearchScopes.setOffset = function(offset)
{
	this.result.offset = offset || 0;
};

ChatSearchScopes.setItems = function(items, filter)
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
			items = items.filter((element) => typeof this.result.itemsIndex[element.id] == 'undefined')
		}
		items.map((element) => {
			if (this.skipList[this.listType].length > 0 && this.skipList[this.listType].indexOf(element.id) > -1)
			{
				return true;
			}
			if (this.skipByProperty[this.listType].length > 0)
			{
				const skipResult = this.skipByProperty[this.listType].find(skipProperty => {
					return (
						typeof element[skipProperty[0]] !== 'undefined'
						&& element[skipProperty[0]] === skipProperty[1]
					);
				});
				if (skipResult)
				{
					console.warn(element);
					return true;
				}
			}
			this.result.itemsIndex[element.id] = true;
			if (this.listType == this.TYPE_USER)
			{
				result.push(ChatDataConverter.getListElementByUser(element));
			}
			else if (this.listType == this.TYPE_CHAT)
			{
				result.push(ChatDataConverter.getListElementByChat(element));
			}
			else if (this.listType == this.TYPE_LINE)
			{
				result.push(ChatDataConverter.getListElementByLine(element));
			}
			else if (this.listType == this.TYPE_DEPARTMENT)
			{
				result.push(ChatDataConverter.getListElementByDepartment(element));
			}

			return true;
		});
	}

	this.result.items = result;
};

ChatSearchScopes.appendItems = function(items, filter)
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
		if (this.skipByProperty[this.listType].length > 0)
		{
			const skipResult = this.skipByProperty[this.listType].find(skipProperty => {
				return (
					typeof element[skipProperty[0]] !== 'undefined'
					&& element[skipProperty[0]] === skipProperty[1]
				);
			});
			if (skipResult)
			{
				return true;
			}
		}
		this.result.itemsIndex[element.id] = true;

		if (this.listType == this.TYPE_USER)
		{
			result.push(ChatDataConverter.getListElementByUser(element));
		}
		else if (this.listType == this.TYPE_CHAT)
		{
			result.push(ChatDataConverter.getListElementByChat(element));
		}
		else if (this.listType == this.TYPE_LINE)
		{
			result.push(ChatDataConverter.getListElementByLine(element));
		}
		else if (this.listType == this.TYPE_DEPARTMENT)
		{
			result.push(ChatDataConverter.getListElementByDepartment(element));
		}

		return true;
	});

	this.result.items = this.result.items.concat(result);
};

ChatSearchScopes.drawSearch = function()
{
	let executeTime = new Date();

	let items = ChatUtils.objectClone(this.result.items);

	if (this.result.progressIcon)
	{
		items.push({title : BX.message("SEARCH"), type : "loading", unselectable: true, sectionCode: this.listType, params: { action: 'progress'}});
	}

	if (!this.result.progressIcon && this.result.items.length <= 0)
	{
		items.push(
			{title : BX.message("SEARCH_EMPTY").replace("#TEXT#", this.result.text), sectionCode: this.listType, type:"button", unselectable: true, params: { action: 'empty'}}
		);
	}

	if (this.result.moreButton && this.result.moreButton['active'])
	{
		items.push({title : this.result.moreButton['text'], type : "button", sectionCode: this.listType, params: { action: 'more', value: this.listType}});
	}


	this.onDrawSearchResult(items, [{
		title : BX.message(`SEARCH_CATEGORY_${this.listType}`),
		id : this.listType,
		backgroundColor : "#FFFFFF"
	}]);

	console.info("ChatSearchScopes.drawSearch: update search results - "+items.length+" elements ("+(new Date() - executeTime)+'ms)', this.result.text);

	return true;
};