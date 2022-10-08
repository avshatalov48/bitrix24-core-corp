"use strict";
/**
 * @bxjs_lang_path component.php
 */

/* Clean session variables after page restart */
if (typeof clearInterval == 'undefined')
{
	clearInterval = (id) => clearTimeout(id);
}
if (typeof ChatUserSelector != 'undefined' && typeof ChatUserSelector.cleaner != 'undefined')
{
	ChatUserSelector.cleaner();
}

/* Chat user selector API */
var ChatUserSelector = {};

ChatUserSelector.init = function()
{
	this.userId = parseInt(BX.componentParameters.get('USER_ID', 0));
	this.dialogId = BX.componentParameters.get('DIALOG_ID', 0);

	let config = Application.storage.getObject('settings.chat', {
		historyShow: true
	});

	this.historyShow = config.historyShow;

	if (!this.dialogId)
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

ChatUserSelector.openDialog = function(dialogId, dialogTitleParams)
{
	console.log('ChatUserSelector.openDialog', dialogId, dialogTitleParams);

	BX.postComponentEvent("onOpenDialog", [{
		dialogId : dialogId,
		dialogTitleParams : dialogTitleParams,
	}, true], 'im.recent');

	BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{
		dialogId: dialogId,
		dialogTitleParams,
	}], 'im.messenger');

	this.close();

	return true;
};

ChatUserSelector.alert = function(text)
{
	ChatUserSelectorInterface.showAlert(text);
	return true;
};

ChatUserSelector.close = function()
{
	ChatUserSelectorInterface.close();
	return true;
};

ChatUserSelector.cleaner = function()
{
	BX.listeners = {};

	console.warn('ChatUserSelector.cleaner: OK');
};


/* Event API */
ChatUserSelector.event = {};

ChatUserSelector.event.init = function ()
{
	this.debug = false;

	this.handlersList = {
		onUserTypeText : this.onUserTypeText,
		onRecipientsReceived : this.onRecipientsReceived,
		onRecipientButtonSelected : this.onRecipientButtonSelected,
		onScopeSelected : this.onScopeSelected
	};

	ChatUserSelectorInterface.setListener(this.router.bind(this));
};

ChatUserSelector.event.router = function(eventName, eventResult)
{
	if (this.handlersList[eventName])
	{
		if (eventName != 'onUserTypeText')
		{
			console.log('ChatUserSelector.event.router: catch event - '+eventName, eventResult);
		}
		this.handlersList[eventName].apply(this, [eventResult])
	}
	else if (this.debug)
	{
		console.info('ChatUserSelector.event.router: skipped event - '+eventName+' '+JSON.stringify(eventResult));
	}
};

ChatUserSelector.event.onScopeSelected = function(event)
{
	console.log('ChatUserSelector.event.onScopeSelected', event);
	this.search.listType = event.id;
	ChatSearchScopes.setType(event.id);
	this.onUserTypeText({text: ChatSearchScopes.result.text})
};

ChatUserSelector.event.onUserTypeText = function(event)
{
	console.log('ChatUserSelector.event.onSearchText', event);

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

ChatUserSelector.event.onRecipientButtonSelected = function(listElement)
{
	console.log('ChatUserSelector.event.onRecipientButtonSelected', listElement);

	ChatSearchScopes.selectElement(listElement);
};

ChatUserSelector.event.onRecipientsReceived = function(event)
{
	console.log('ChatUserSelector.event.onRecipientsReceived', event);

	let users = [];
	let isChat = this.base.dialogId.toString().substr(0, 4) == 'chat';
	if (!isChat)
	{
		users.push(
			ChatUserSelector.dialogId
		);
	}

	if (event)
	{
		event.forEach((recipient) => {
			users.push(recipient.id);
		});
	}

	if (isChat)
	{
		this.rest.chatExtend({
			'CHAT_ID': this.base.dialogId.toString().substr(4),
			'USERS': users,
			'HIDE_HISTORY': this.base.historyShow? 'N': 'Y'
		});
	}
	else
	{
		this.rest.chatAdd({
			'USERS': users,
		});
	}

	return true;
};


/* Rest API */
ChatUserSelector.rest = {};

ChatUserSelector.rest.chatAdd = function (params)
{
	BX.rest.callMethod('im.chat.add', params)
		.then((result) =>
		{
			let chatId = parseInt(result.data());
			if (chatId > 0)
			{
				console.info("ChatUserSelector.rest.chatAdd: chat id:\n", result.data());
				this.base.openDialog('chat'+result.data(), false);
			}
			else
			{
				console.error("ChatUserSelector.rest.chatAdd: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_SELECTOR_API_ERROR'));
			}
		})
		.catch((result) =>
		{
			let error = result.error();
			if (error.ex.error == 'NO_INTERNET_CONNECTION')
			{
				console.error("ChatUserSelector.rest.chatAdd - error: connection error", error.ex);
				this.base.alert(BX.message('IM_USER_SELECTOR_CONNECTION_ERROR'));
			}
			else
			{
				console.error("ChatUserSelector.rest.chatAdd - error: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_SELECTOR_API_ERROR'));
			}
		});
};

ChatUserSelector.rest.chatExtend = function (params)
{
	BX.rest.callMethod('im.chat.user.add', params)
		.then((result) =>
		{
			if (result.data())
			{
				console.info("ChatUserSelector.rest.chatExtend: success");
				this.base.close();
			}
			else
			{
				console.error("ChatUserSelector.rest.chatExtend: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_SELECTOR_API_ERROR'));
			}
		})
		.catch((result) =>
		{
			let error = result.error();
			if (error.ex.error === 'NO_INTERNET_CONNECTION')
			{
				console.error("ChatUserSelector.rest.chatExtend - error: connection error", error.ex);
				this.base.alert(BX.message('IM_USER_SELECTOR_CONNECTION_ERROR'));
			}
			else if (
				error.ex.error === 'NOTHING_TO_ADD'
				|| error.ex.error === 'EMPTY_CHAT_ID'
				|| error.ex.error === 'EMPTY_USER_ID'
				|| error.ex.error === 'EMPTY_USER_ID_BY_PRIVACY'
				|| error.ex.error === 'AUTHORIZE_ERROR'
			)
			{
				console.error("ChatUserSelector.rest.chatExtend - correct error", error.ex);
				this.base.alert(error.ex.error_description);
			}
			else
			{
				console.error("ChatUserSelector.rest.chatExtend - error: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_SELECTOR_API_ERROR'));
			}
		});
};


/* Search API */
ChatUserSelector.search = {};

ChatUserSelector.search.init = function ()
{
	this.listType = ChatSearchScopes.TYPE_USER;

	this.listUsers = BX.componentParameters.get('LIST_USERS', []);
	this.listDepartment = BX.componentParameters.get('LIST_DEPARTMENTS', []);
	this.skipList = BX.componentParameters.get('SKIP_LIST', []);

	ChatSearchScopes.init({
		listType: this.listType,
		dataConverterInited: true,
		minTokenLength: this.base.searchMinTokenLength,
		onDrawSearchResult: this.drawSearchResult.bind(this)
	});

	ChatSearchScopes.setList(this.listUsers, ChatSearchScopes.TYPE_USER);
	ChatSearchScopes.setList(this.listDepartment, ChatSearchScopes.TYPE_DEPARTMENT);
	ChatSearchScopes.setSkipList(this.skipList, ChatSearchScopes.TYPE_USER);
	ChatSearchScopes.setSkipByProperty([
		['network', true],
		['connector', true]
	], ChatSearchScopes.TYPE_USER);
	ChatSearchScopes.setMinTokenLength(1, ChatSearchScopes.TYPE_DEPARTMENT);
};

ChatUserSelector.search.drawSearchResult = function (items, sections)
{
	console.log('ChatUserSelector.search.drawSearchResult', items);
	ChatUserSelectorInterface.setSearchResult(items, sections);
};

ChatUserSelector.search.drawStartList = function()
{
	console.log('ChatUserSelector.search.drawStartList');

	let items = [];
	let itemsIndex = {};

	if (this.listType == ChatSearchScopes.TYPE_USER)
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
	else if (this.listType == ChatSearchScopes.TYPE_DEPARTMENT)
	{
		if (this.listDepartment.length > 0)
		{
			this.listDepartment.map(element =>
			{
				if (!element || itemsIndex[element.id])
				{
					return false;
				}

				let item = ChatDataConverter.getListElementByDepartment(element);

				items.push(item);
				itemsIndex[item.id] = true;

				return true;
			});
		}
		else
		{
			items.push(
				{title : BX.message("IM_DEPARTMENT_START"), sectionCode: this.listType, type:"button", unselectable: true, params: { action: 'empty'}}
			);
		}
	}

	ChatUserSelectorInterface.setSearchResult(items, []);
}
/* Initialization */
ChatUserSelector.init();