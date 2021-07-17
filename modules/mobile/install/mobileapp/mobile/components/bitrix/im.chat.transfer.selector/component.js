"use strict";
/**
 * @bxjs_lang_path component.php
 */

/* Clean session variables after page restart */
if (typeof clearInterval == 'undefined')
{
	clearInterval = (id) => clearTimeout(id);
}
if (typeof ChatTransferSelector != 'undefined' && typeof ChatTransferSelector.cleaner != 'undefined')
{
	ChatTransferSelector.cleaner();
}

/* Chat user selector API */
var ChatTransferSelector = {};

ChatTransferSelector.init = function()
{
	this.userId = parseInt(BX.componentParameters.get('USER_ID', 0));
	this.chatId = BX.componentParameters.get('CHAT_ID', 0);

	if (!this.chatId)
	{
		this.close();
		return false;
	}

	this.searchMinTokenLength = BX.componentParameters.get('SEARCH_MIN_SIZE', 3);

	/* set cross-links in class */
	let links = ['base', 'event', 'rest', 'search'];
	links.forEach((subClass) => {
		if (typeof this[subClass] != 'undefined')
		{
			links.forEach((element) => {
				if (element == 'base')
				{
					this[subClass]['base'] = this;
				}
				else if (subClass != element)
				{
					this[subClass][element] = this[element];
				}
			});
		}
	});

	ChatDataConverter.init({
		'userId': this.userId,
		'generalChatId': this.generalChatId
	});

	this.event.init();
	this.search.init();

	return true;
};

ChatTransferSelector.alert = function(text)
{
	ChatTransferSelectorInterface.showAlert(text);
	return true;
};

ChatTransferSelector.close = function()
{
	ChatTransferSelectorInterface.close();
	return true;
};

ChatTransferSelector.cleaner = function()
{
	BX.listeners = {};
	console.warn('ChatTransferSelector.cleaner: OK');
};


/* Event API */
ChatTransferSelector.event = {};

ChatTransferSelector.event.init = function ()
{
	this.debug = false;

	this.handlersList = {
		onUserTypeText : this.onUserTypeText,
		onRecipientsReceived : this.onRecipientsReceived,
		onRecipientButtonSelected : this.onRecipientButtonSelected,
		onScopeSelected : this.onScopeSelected
	};

	ChatTransferSelectorInterface.setListener(this.router.bind(this));
};

ChatTransferSelector.event.router = function(eventName, eventResult)
{
	if (this.handlersList[eventName])
	{
		if (eventName != 'onUserTypeText')
		{
			console.log('ChatTransferSelector.event.router: catch event - '+eventName, eventResult);
		}
		this.handlersList[eventName].apply(this, [eventResult])
	}
	else if (this.debug)
	{
		console.info('ChatTransferSelector.event.router: skipped event - '+eventName+' '+JSON.stringify(eventResult));
	}
};

ChatTransferSelector.event.onScopeSelected = function(event)
{
	console.log('ChatTransferSelector.event.onScopeSelected', event);
	this.search.listType = event.id;
	ChatSearchScopes.setType(event.id);
	this.onUserTypeText({text: ChatSearchScopes.result.text})
};

ChatTransferSelector.event.onUserTypeText = function(event)
{
	console.log('ChatTransferSelector.event.onSearchText', event);

	let text = event.text.trim();
	if (!text)
	{
		ChatSearchScopes.clear();
		this.search.drawStartList();
	}
	else
	{
		ChatSearchScopes.find(text);
	}
};

ChatTransferSelector.event.onRecipientButtonSelected = function(listElement)
{
	console.log('ChatTransferSelector.event.onRecipientButtonSelected', listElement);

	ChatSearchScopes.selectElement(listElement);
};

ChatTransferSelector.event.onRecipientsReceived = function(event)
{
	console.log('ChatTransferSelector.event.onRecipientsReceived', event);

	let transferId = 0;

	if (event)
	{
		event.forEach((recipient) => {
			transferId = recipient.id;
		});
	}

	this.rest.transfer({
		'CHAT_ID': this.base.chatId,
		'TRANSFER_ID': transferId
	});

	return true;
};


/* Rest API */
ChatTransferSelector.rest = {};

ChatTransferSelector.rest.transfer = function (params)
{
	BX.rest.callMethod('imopenlines.operator.transfer', params)
		.then((result) =>
		{
			if (result.data())
			{
				console.info("ChatTransferSelector.rest.transfer: success");
				BX.postWebEvent("onLinesTransferSuccess", {chatId: params.CHAT_ID});
				this.base.close();
			}
			else
			{
				console.error("ChatTransferSelector.rest.transfer: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_SELECTOR_API_ERROR'));
			}
		})
		.catch((result) =>
		{
			let error = result.error();
			if (error.ex.error == 'NO_INTERNET_CONNECTION')
			{
				console.error("ChatTransferSelector.rest.transfer - error: connection error", error.ex);
				this.base.alert(BX.message('IM_SELECTOR_CONNECTION_ERROR'));
			}
			else
			{
				console.error("ChatTransferSelector.rest.transfer - error: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_SELECTOR_API_ERROR'));
			}
		});
};


/* Search API */
ChatTransferSelector.search = {};

ChatTransferSelector.search.init = function ()
{
	this.listType = ChatSearchScopes.TYPE_USER;

	this.listUsers = BX.componentParameters.get('LIST_USERS', []);
	this.listLines = BX.componentParameters.get('LIST_LINES', []);
	this.businessOnly = BX.componentParameters.get('BUSINESS_ONLY', false);
	this.skipList = BX.componentParameters.get('SKIP_LIST', []);

	ChatSearchScopes.init({
		listType: this.listType,
		businessOnly: this.businessOnly,
		dataConverterInited: true,
		minTokenLength: this.base.searchMinTokenLength,
		onDrawSearchResult: this.drawSearchResult.bind(this)
	});

	ChatSearchScopes.setSkipList(this.skipList, ChatSearchScopes.TYPE_USER);
	ChatSearchScopes.setList(this.listUsers, ChatSearchScopes.TYPE_USER);
	ChatSearchScopes.setList(this.listLines, ChatSearchScopes.TYPE_LINE);
	ChatSearchScopes.setSkipByProperty([
		['bot', true],
	], ChatSearchScopes.TYPE_USER);
	ChatSearchScopes.setExternalSearchEnable(false, ChatSearchScopes.TYPE_LINE);
};

ChatTransferSelector.search.drawSearchResult = function (items, sections)
{
	console.log('ChatTransferSelector.search.drawSearchResult', items);
	ChatTransferSelectorInterface.setSearchResult(items, sections);
};

ChatTransferSelector.search.drawStartList = function()
{
	console.log('ChatTransferSelector.search.drawStartList');
	this.drawSearchResult(this.prepareItems(), []);
};

ChatTransferSelector.search.prepareItems = function(type = this.listType)
{
	let items = [];
	let itemsIndex = {};

	if (type == ChatSearchScopes.TYPE_USER)
	{
		if (this.listUsers.length > 0)
		{
			this.listUsers.map(element =>
			{
				if (!element || itemsIndex[element.id])
				{
					return false;
				}

				let item = ChatDataConverter.getListElementByUser(element);

				items.push(item);
				itemsIndex[item.id] = true;

				return true;
			});
		}
		items = items.filter((element) => this.skipList.indexOf(element.id) == -1);
	}
	else if (type == ChatSearchScopes.TYPE_LINE)
	{
		if (this.listLines.length > 0)
		{
			this.listLines.map(element =>
			{
				if (!element || itemsIndex[element.id])
				{
					return false;
				}

				let item = ChatDataConverter.getListElementByLine(element);

				items.push(item);
				itemsIndex[item.id] = true;

				return true;
			});
		}
	}

	return items;
};

/* Initialization */
ChatTransferSelector.init();