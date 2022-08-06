"use strict";
/**
 * @bxjs_lang_path component.php
 */

var { EventType } = jn.require('im/messenger/const');

/* Clean session variables after page restart */
if (typeof clearInterval == 'undefined')
{
	clearInterval = (id) => clearTimeout(id);
}
if (typeof ChatCreate != 'undefined' && typeof ChatCreate.cleaner != 'undefined')
{
	ChatCreate.cleaner();
}

/* Chat create API */
var ChatCreate = {
	TYPE_OPEN: 'OPEN',
	TYPE_CHAT: 'CHAT',
};

ChatCreate.init = function()
{
	this.userId = parseInt(BX.componentParameters.get('USER_ID', 0));
	this.siteId = BX.componentParameters.get('SITE_ID', 's1');
	this.languageId = BX.componentParameters.get('LANGUAGE_ID', 'en');
	this.searchMinTokenLength = BX.componentParameters.get('SEARCH_MIN_SIZE', 3);

	/* set cross-links in class */
	let links = ['base', 'event', 'search'];
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

ChatCreate.openDialog = function(dialogId, dialogTitleParams)
{
	console.log('ChatCreate.openDialog', dialogId, dialogTitleParams);

	BX.postComponentEvent("onOpenDialog", [{
		dialogId : dialogId,
		dialogTitleParams : dialogTitleParams,
	}, true], 'im.recent');

	BX.postComponentEvent(EventType.messenger.openDialog, [{
		dialogId,
		dialogTitleParams,
	}], 'im.messenger');

	this.close();

	return true;
};

ChatCreate.alert = function(text)
{
	ChatCreateInterface.showAlert(text);
	return true;
};

ChatCreate.close = function()
{
	ChatCreateInterface.close();
	return true;
};

ChatCreate.cleaner = function()
{
	BX.listeners = {};

	console.warn('ChatCreate.cleaner: OK');
};


/* Event API */
ChatCreate.event = {};

ChatCreate.event.init = function ()
{
	this.debug = false;
	this.handlersList = {
		// first screen
		onViewShown : this.onPrivateView,
		onSearchShow : this.onPrivateSearchShow,
		onSearchHide : this.onPrivateSearchHide,
		onPrivateTypeText : this.onSearchText,
		onPrivateChat : this.onPrivateDialogOpen,
		onSearchItemSelected : this.onPrivateDialogOpen,
		// second screen
		onScopeSelected : this.onScopeSelected,
		onRecipientsView : this.onRecipientsView,
		onRecipientTypeText : this.onSearchText,
		onRecipientButtonSelected : this.onRecipientButtonSelected,
		// third screen
		onResult : this.onChatCreate,
		onInviteEmployees : this.onInviteEmployees
	};

	ChatCreateInterface.setListener(this.router.bind(this));
};

ChatCreate.event.router = function(eventName, eventResult)
{
	if (this.handlersList[eventName])
	{
		if (!(eventName == 'onPrivateTypeText' || eventName == 'onRecipientTypeText'))
		{
			console.log('ChatCreate.event.router: catch event - '+eventName, eventResult);
		}
		this.handlersList[eventName].apply(this, [eventResult])
	}
	else if (this.debug)
	{
		console.info('ChatCreate.event.router: skipped event - '+eventName+' '+JSON.stringify(eventResult));
	}
};

ChatCreate.event.onPrivateSearchShow = function(event)
{
	console.log('ChatCreate.event.onPrivateSearchShow', event);
};

ChatCreate.event.onPrivateSearchHide = function(event)
{
	console.log('ChatCreate.event.onPrivateSearchHide', event);
	ChatSearchScopes.clear();
	this.search.drawStartList();
};

ChatCreate.event.onPrivateDialogOpen = function(listElement)
{
	ChatSearchScopes.selectElement(listElement);

	if (listElement.params.action == 'item')
	{
		let dialogId = (listElement.params.type === "chat"? "chat": "") + listElement.params.id;
		this.base.openDialog(dialogId, {
			name: listElement.title,
			description: listElement.subtitle,
			avatar: listElement.imageUrl
		});
	}

	return true;
};

ChatCreate.event.onPrivateView = function()
{
	this.search.state = this.search.STATE_PRIVATE;
	ChatSearchScopes.setSkipList([], ChatSearchScopes.TYPE_USER);

	this.search.listType = 'user';
	BX.componentParameters.set('SCOPE', this.search.listType);
	ChatSearchScopes.setType(this.search.listType);

	BX.componentParameters.set('STATE', this.search.state);
};

ChatCreate.event.onScopeSelected = function(event)
{
	if (this.search.state == this.STATE_PRIVATE)
	{
		return false;
	}

	console.log('ChatCreate.event.onScopeSelected', event);
	this.search.listType = event.id;
	BX.componentParameters.set('SCOPE', event.id);
	ChatSearchScopes.setType(event.id);
	this.onSearchText({text: ChatSearchScopes.result.text})

	return true;
};

ChatCreate.event.onRecipientsView = function()
{
	ChatSearchScopes.setSkipList(this.search.skipList, ChatSearchScopes.TYPE_USER);
	this.search.state = this.search.STATE_CHAT_RECIPIENT;
	BX.componentParameters.set('STATE', this.search.state);
	this.search.drawStartList();
};

ChatCreate.event.onRecipientButtonSelected = function(listElement)
{
	console.log('ChatCreate.event.onRecipientButtonSelected', listElement);

	ChatSearchScopes.selectElement(listElement);
};

ChatCreate.event.onSearchText = function(event)
{
	console.log('ChatCreate.event.onSearchText', event);

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

ChatCreate.event.onChatCreate = function(event)
{
	console.log('ChatCreate.event.onChatCreate', event);

	let users = [];
	if (event.recipients)
	{
		event.recipients.forEach((recipient) => {
			users.push(recipient.id);
		});
	}

	let config = {
		'TYPE': event.type == 'public'? this.base.TYPE_OPEN: this.base.TYPE_CHAT,
		'TITLE': event.title,
	};
	if (users.length>0)
	{
		config.USERS = users;
	}
	if (event.icon)
	{
		config.AVATAR = event.icon;
	}

	BX.rest.callMethod('im.chat.add', config)
		.then((result) =>
		{
			if (typeof fabric != 'undefined')
			{
				fabric.Answers.sendCustomEvent("imChatAdd", {});
			}
			let chatId = parseInt(result.data());
			if (chatId > 0)
			{
				console.info("ChatCreate.event.onChatCreate: chat id:\n", result.data());
				this.base.openDialog('chat'+result.data(), {
					name: event.title,
					description: event.type == 'public'? BX.message('IM_CHAT_TYPE_OPEN_NEW'): BX.message('IM_CHAT_TYPE_CHAT_NEW'),
					avatar: ''
				});
			}
			else
			{
				console.error("ChatCreate.event.onChatCreate: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_CREATE_API_ERROR'));
			}
		})
		.catch((result) =>
		{
			let error = result.error();
			if (error.ex.error == 'NO_INTERNET_CONNECTION')
			{
				console.error("ChatCreate.event.onChatCreate - error: connection error", error.ex);
				this.base.alert(BX.message('IM_CREATE_CONNECTION_ERROR'));
			}
			else
			{
				console.error("ChatCreate.event.onChatCreate - error: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_CREATE_API_ERROR'));
			}
		});

	return true;
};

ChatCreate.event.onInviteEmployees = function(event)
{
	console.log('ChatCreate.event.onInviteEmployees', event);

	if (
		Application.getApiVersion() < 34
		|| !BX.componentParameters.get('INTRANET_INVITATION_CAN_INVITE', false)
	)
	{
		return false;
	}

	IntranetInvite.openRegisterSlider({
		originator: 'im.chat.create',
		registerUrl: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_URL', ''),
		rootStructureSectionId: BX.componentParameters.get('INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID', 0),
		adminConfirm: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM', false),
		disableAdminConfirm: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE', false),
		sharingMessage: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_SHARING_MESSAGE', '')
	});

	return true;
};

/* Search API */
ChatCreate.search = {
	STATE_PRIVATE: 'private',
	STATE_CHAT_RECIPIENT: 'chat'
};

ChatCreate.search.init = function ()
{
	this.state = BX.componentParameters.get('STATE', this.STATE_PRIVATE);
	this.listType = BX.componentParameters.get('SCOPE', ChatSearchScopes.TYPE_USER);

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
	ChatSearchScopes.setMinTokenLength(1, ChatSearchScopes.TYPE_DEPARTMENT);
};

ChatCreate.search.drawSearchResult = function (items, sections)
{
	console.log('ChatCreate.search.drawSearchResult', this.state, items);
	if (this.state == this.STATE_PRIVATE)
	{
		ChatCreateInterface.setSearchPrivateResult(items, sections);
	}
	else
	{
		items = items.filter((element) => !(element.source && element.source.bot && element.source.network));
		ChatCreateInterface.setRecipientsList(items, sections);
	}
};

ChatCreate.search.drawStartList = function()
{
	console.log('ChatCreate.search.drawStartList');
	if (this.state == this.STATE_CHAT_RECIPIENT)
	{
		this.drawSearchResult(this.prepareItems(), []);
	}
	else
	{
		this.drawSearchResult([], []);
	}
};

ChatCreate.search.prepareItems = function(type = this.listType)
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
				if (element.bot && element.network)
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
	else if (type == ChatSearchScopes.TYPE_DEPARTMENT)
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

	return items;
};

/* Initialization */
ChatCreate.init();