"use strict";
/**
 * @bxjs_lang_path extension.php
 */

/**
 * @requires module:chat/utils
 * @requires module:chat/messengercommon
 * @module chat/dataconverter
 */

var ChatDataConverter = {};

ChatDataConverter.init = function(config)
{
	this.userId = parseInt(config.userId)? parseInt(config.userId): 0;
	this.generalChatId = parseInt(config.generalChatId)? parseInt(config.generalChatId): 0;
	this.listType = config.listType == 'lines'? 'lines': 'recent';
	this.updateRuntimeData = typeof config.updateRuntimeDataFunction == 'function'? config.updateRuntimeDataFunction: (element) => {};
	this.imagePath = component.path+'images';
};

ChatDataConverter.getElementFormat = function(element)
{
	let item = {};
	// item.useEstimatedHeight = true;
	item.id = element.id;

	item.params = {
		id : element.id,
		date : ChatUtils.getTimestamp(element.message.date),
		type : element.type,
		useLetterImage : true,
	};

	item.sortValues = {
		order : item.params.date
	};

	if (element.type == 'user')
	{
		item.title = element.user.name+(element.user.id == this.userId? ' ('+BX.message("IM_YOU")+')': '');
		item.imageUrl = ChatUtils.getAvatar(element.user.avatar);
		item.color = element.user.color;
		item.sectionCode = element.pinned? 'pinned': 'general';
		item.subtitle = element.message.text;
	}
	else
	{
		item.title = element.chat.name;
		item.imageUrl = ChatUtils.getAvatar(element.chat.avatar);

		if (element.chat.id == this.generalChatId && !item.imageUrl)
		{
			item.imageUrl = this.imagePath+'/avatar_general.png';
		}

		item.color = element.chat.color;
		if (this.listType == 'lines' && element.chat.type == 'lines')
		{
			if (element.lines.status < 40)
			{
				item.sectionCode = 'work';
				let session = ChatMessengerCommon.linesGetSession(element.chat);
				if (session && session.dateCreate)
				{
					item.sortValues.order = session.dateCreate;
					item.params.date = session.dateCreate;
				}
			}
			else
			{
				item.sectionCode = 'answered';
			}

			if (element.pinned)
			{
				item.sectionCode = 'pinned';
			}
		}
		else
		{
			item.sectionCode = element.pinned? 'pinned': 'general';
		}

		let prefix = '';
		if (element.message.author_id == this.userId)
		{
			prefix = BX.message('IM_YOU_2');
		}
		else if (element.message.author_id)
		{
			if (!element.user.first_name)
			{
				prefix = element.user.name+': ';
			}
			else
			{
				prefix = element.user.first_name+(element.user.last_name? ' '+element.user.last_name.substr(0, 1)+'.': '')+': ';
			}
		}

		item.subtitle = prefix+element.message.text;
	}

	item.messageCount = element.counter;

	item.backgroundColor = element.pinned? '#f6f6f6': '#ffffff';

	item.styles = {};
	item.styles.avatar = this.getAvatarFormat(element);
	item.styles.title = this.getTitleFormat(element.type, element[element.type]);
	item.styles.subtitle = this.getTextFormat(element);
	item.styles.date = this.getDateFormat(element);
	item.styles.counter = this.getCounterFormat(element);

	item.actions = this.getActionList(element);

	this.updateRuntimeData(element);

	return item;
};

ChatDataConverter.getElementFormatByEntity = function(type, entity)
{
	let result = {
		id: entity.id,
		type: type,
		message: {
			id: 0,
			text: "",
			file: false,
			author_id: 0,
			attach: false,
			date: new Date(),
			status: "received"
		},
	};

	if (type == 'user')
	{
		result.user = entity;
	}
	else
	{
		result.user = {};
		result.chat = entity;
	}

	return this.getElementFormat(result);
};

ChatDataConverter.getAvatarFormat = function(element)
{
	let result = {};
	if (element.type == 'user')
	{
		let status = this.getUserImageCode(element);
		if (status)
		{
			result = {image: {name: 'status_'+status}};
		}
	}
	else
	{
		if (element.chat.type == 'lines')
		{
			let status = this.getLinesImageCode(element);
			result = {image: {name: 'status_'+status}};

			let session = ChatMessengerCommon.linesGetSession(element.chat);
			if (session.crm == 'Y')
			{
				result.additionalImage = {name: 'special_status_crm'};
			}
		}
		else
		{
			if (element.chat.id == this.generalChatId)
			{
				if (ChatUtils.getAvatar(element.chat.avatar))
				{
					result = {
						image: {name: 'status_dialog_general'}
					};
				}
			}
			else if (element.chat.type == 'chat')
			{
				result = {
					image: {name: 'status_dialog_chat'}
				};
			}
			else if (element.chat.type == 'open')
			{
				result = {
					image: {name: 'status_dialog_open'}
				};
			}
		}
	}

	return result;
};

ChatDataConverter.getTitleFormat = function(type, entity)
{
	let result = {};
	if (type == 'user')
	{
		if (entity.id == this.userId)
		{
			result = {
				image: {name: 'name_status_owner'}
			};
		}
		else if (entity.network)
		{
			result = {
				color: '#0a962f',
				image: {name: 'name_status_network'}
			};
		}
		else if (entity.bot)
		{
			result = {
				color: '#725acc',
				image: {name: 'name_status_bot'}
			};
		}
		else if (entity.extranet)
		{
			result = {
				color: '#ca7b00',
				image: {name: 'name_status_extranet'}
			};
		}
		else if (entity.connector)
		{
			result = {
				color: '#0a962f',
				image: {name: 'name_status_network'}
			};
		}
		else
		{
			let status = ChatMessengerCommon.getUserStatus(entity);
			if (status == 'vacation')
			{
				result = {
					image: {name: 'name_status_vacation'}
				};
			}
			else if (status == 'birthday')
			{
				result = {
					image: {name: 'name_status_birthday'}
				};
			}
		}
	}
	else if (type == 'chat')
	{
		if (entity.type == 'lines')
		{
			if (this.listType == 'recent')
			{
				result = {
					color: '#16938b',
					image: {name: 'name_status_lines'},
				};
			}
			else if (entity.owner == this.userId)
			{
				result = {
					image: {name: 'name_status_owner'},
				};
			}
			else if (entity.owner == 0)
			{
				result = {
					image: {name: 'name_status_new'},
					color: '#e66467',
				};
			}
		}
		else if (entity.type == 'call')
		{
			result = {
				image: {name: 'name_status_call'},
			};
		}
		else
		{
			if (entity.extranet)
			{
				result = {
					color: '#ca7b00',
					image: {name: 'name_status_extranet'}
				};
			}
			if (entity.mute_list[this.userId])
			{
				result.additionalImage = {name: 'name_status_mute'};
			}
		}
	}

	return result;
};

ChatDataConverter.getCounterFormat = function(element)
{
	let result = {};
	if (element.type != 'chat')
	{
		return result;
	}

	if (element.chat.type == 'lines' || element.chat.type == 'call')
	{
	}
	else
	{
		if (element.chat.mute_list[this.userId])
		{
			result = {backgroundColor: '#B8BBC1'};
		}
	}

	return result;
};

ChatDataConverter.getDateFormat = function(element)
{
	let name = '';
	let sizeMultiplier = 0.7;
	if (element.message.author_id == this.userId)
	{
		if (element.type == 'user' && element.user.id == this.userId)
		{
			name = 'message_delivered';
		}
		else if (element.message.status == 'received')
		{
			name = 'message_send';
		}
		else if (element.message.status == 'error')
		{
			name = 'message_error';
		}
		else if (element.message.status == 'delivered')
		{
			name = 'message_delivered';
		}
		else if (element.pinned)
		{
			name = 'message_pin';
			sizeMultiplier = 0.9;
		}
	}
	else
	{
		if (element.pinned)
		{
			name = 'message_pin';
			sizeMultiplier = 0.9;
		}
		else
		{
			return {};
		}
	}

	return {image: {name: name, sizeMultiplier: sizeMultiplier}};
};

ChatDataConverter.getTextFormat = function(element)
{
	let result = {};
	if (element.writing)
	{
		result = {animation:{color:"#777777", type:"bubbles"}};
	}
	else if (element.message.author_id == this.userId)
	{
		result = {image: {name : 'reply', sizeMultiplier: 0.7}};
	}

	return result;
};

ChatDataConverter.getUserImageCode = function(element)
{
	let icon = '';
	if (element.type != 'user')
	{
		return '';
	}

	let data = ChatMessengerCommon.getUserStatus(element.user, false);
	if (data.status == 'vacation' && (element.user.extranet || element.user.bot || element.user.network))
	{
		icon = data.status;
	}
	else if (data.status == 'birthday' && (element.user.extranet || element.user.bot || element.user.network))
	{
		icon = data.status;
	}
	else if (
		data.originStatus == 'away'
		|| data.originStatus == 'dnd'
		|| data.originStatus == 'guest'
		|| data.originStatus == 'idle'
		|| data.originStatus == 'mobile'
		|| data.originStatus == 'call'
	)
	{
		icon = data.originStatus;
	}

	return icon;
};

ChatDataConverter.getLinesImageCode = function(element)
{
	if (element.type != 'chat' || element.chat.type != 'lines')
	{
		return '';
	}

	let result = 'world';
	let source = (element.chat.entity_id.split('|'))[0];

	if (source == 'livechat')
	{
		result = 'livechat';
	}
	else if (source == 'viber')
	{
		result = 'viber';
	}
	else if (source == 'telegrambot')
	{
		result = 'telegram';
	}
	else if (source == 'instagram')
	{
		result = 'instagram';
	}
	else if (source == 'vkgroup')
	{
		result = 'vk';
	}
	else if (source == 'facebook')
	{
		result = 'fbm';
	}
	else if (source == 'facebookcomments')
	{
		result = 'facebook';
	}
	else if (source == 'network')
	{
		result = 'network';
	}
	else if (source == 'botframework.skype')
	{
		result = 'skype';
	}
	else if (source == 'botframework.slack')
	{
		result = 'slack';
	}
	else if (source == 'botframework.kik')
	{
		result = 'kik';
	}
	else if (source == 'botframework.groupme')
	{
		result = 'groupme';
	}
	else if (source == 'botframework.twilio')
	{
		result = 'twilio';
	}
	else if (source == 'botframework.webchat')
	{
		result = 'webchat';
	}
	else if (source == 'botframework.emailoffice365')
	{
		result = 'email';
	}
	else if (source == 'botframework.telegram')
	{
		result = 'telegram';
	}
	else if (source == 'botframework.facebookmessenger')
	{
		result = 'fbm';
	}

	return result;
};

ChatDataConverter.getActionList = function(element)
{
	let result = false;
	if (element.type == 'user')
	{
		result = [];
		result.push({
			title : element.pinned? BX.message("ELEMENT_MENU_UNPIN"): BX.message("ELEMENT_MENU_PIN"),
			identifier : element.pinned? "unpin": "pin",
			color : "#3e99ce",
			iconName : "action_"+(element.pinned? "unpin": "pin"),
		});
		result.push({
			title : BX.message("ELEMENT_MENU_DELETE"),
			identifier : "hide",
			iconName : "action_delete",
			color : "#df532d",
		});
	}
	else
	{
		if (element.chat.type == 'lines')
		{
			if (element.chat.owner == 0)
			{
				result = [
					{
						title : BX.message("ELEMENT_MENU_ANSWER"),
						identifier : "operatorAnswer",
						iconName : "action_answer",
						color : "#aac337"
					},
					{
						title : BX.message("ELEMENT_MENU_SKIP"),
						color : "#df532d",
						iconName : "action_skip",
						identifier : "operatorSkip",
					},
					{
						title : BX.message("ELEMENT_MENU_SPAM"),
						color : "#e89d2a",
						iconName : "action_spam",
						identifier : "operatorSpam",
					},
				];
			}
			else if (element.chat.owner == this.userId)
			{
				result = [
					{
						title : BX.message("ELEMENT_MENU_FINISH"),
						iconName : "action_finish",
						identifier : "operatorFinish",
						color : "#aac337",
					},
					{
						title : element.pinned? BX.message("ELEMENT_MENU_UNPIN"): BX.message("ELEMENT_MENU_PIN"),
						identifier : element.pinned? "unpin": "pin",
						iconName : "action_"+(element.pinned? "unpin": "pin"),
						color : "#3e99ce"
					},
					{
						title : BX.message("ELEMENT_MENU_SPAM"),
						color : "#e8a441",
						identifier : "operatorSpam",
						iconName : "action_spam",
					},
				];
			}
			else
			{
				result = [
					{
						title : element.pinned? BX.message("ELEMENT_MENU_UNPIN"): BX.message("ELEMENT_MENU_PIN"),
						identifier : element.pinned? "unpin": "pin",
						iconName : "action_"+(element.pinned? "unpin": "pin"),
						color : "#3e99ce"
					},
					{
						title : BX.message("ELEMENT_MENU_LEAVE"),
						identifier : "leave",
						iconName : "action_delete",
						color : "#df532d",
					},
				];
			}
		}
		else
		{
			result = [
				{
					title : element.chat.mute_list[this.userId]? BX.message("ELEMENT_MENU_UNMUTE"): BX.message("ELEMENT_MENU_MUTE"),
					identifier : element.chat.mute_list[this.userId]? "unmute": "mute",
					iconName : "action_"+(element.chat.mute_list[this.userId]? "unmute": "mute"),
					color : "#aaabac"
				},
				{
					title : element.pinned? BX.message("ELEMENT_MENU_UNPIN"): BX.message("ELEMENT_MENU_PIN"),
					iconName : "action_"+(element.pinned? "unpin": "pin"),
					identifier : element.pinned? "unpin": "pin",
					color : "#3e99ce"
				},
				{
					title : BX.message("ELEMENT_MENU_DELETE"),
					iconName : "action_delete",
					identifier : "hide",
					color : "#df532d"
				},
			];
		}
	}

	return result;
};

ChatDataConverter.getListFormat = function (list)
{
	let result = [];

	list.forEach((element) => {
		if (!element) return;

		element.user = this.getUserDataFormat(element.user);

		if (typeof element.message != 'undefined')
		{
			element.message.date = new Date(element.message.date);
		}

		if (typeof element.chat != 'undefined')
		{
			element.chat.date_create = new Date(element.chat.date_create);
		}

		result.push(element);
	});

	return result;
};

ChatDataConverter.getUserListFormat = function (list)
{
	let result = [];

	list.forEach((element) => {
		if (!element) return;

		element = this.getUserDataFormat(element);

		result.push(element);
	});

	return result;
};

ChatDataConverter.getUserDataFormat = function (user, options = {})
{
	let {dateAtom = false} = options;

	if (!user)
	{
		user = {id: 0};
	}
	if (user.id > 0)
	{
		if (typeof (user.last_activity_date) != 'undefined')
		{
			user.last_activity_date = (!dateAtom? new Date(user.last_activity_date): user.last_activity_date.toString());
		}
		if (typeof (user.mobile_last_date) != 'undefined')
		{
			user.mobile_last_date = (!dateAtom? new Date(user.mobile_last_date): user.mobile_last_date.toString());
		}
		if (typeof (user.idle) != 'undefined')
		{
			user.idle = user.idle? (!dateAtom? new Date(user.idle): user.idle.toString()): false;
		}
		if (typeof (user.absent) != 'undefined')
		{
			user.absent = user.absent? (!dateAtom? new Date(user.absent): user.absent.toString()): false;
		}
	}

	return user;
};

ChatDataConverter.getSearchElementFormat = function(element, recent)
{
	let item = {};
	let type = '';

	if (recent)
	{
		type = element.type;

		item = ChatUtils.objectClone(element);
		item.sectionCode = 'recent';
		item.useLetterImage = true;

		item.actions = [{
			title : BX.message("ELEMENT_MENU_DELETE"),
			identifier : "delete",
			destruct: true,
			color : "#df532d"
		}];
	}
	else
	{
		type = typeof element.owner == 'undefined'? 'user': 'chat';
		let elementClone = ChatUtils.objectClone(element);

		element = {type: type};
		element[type] = elementClone;

		item.sectionCode = type;
		item.useLetterImage = true;
	}

	item.params = {
		action: 'item'
	};

	if (type == 'user')
	{
		item.id = element.user.id;
		item.params.id = element.user.id;

		item.title = element.user.name+(element.user.id == this.userId? ' ('+BX.message("IM_YOU")+')': '');
		item.imageUrl = ChatUtils.getAvatar(element.user.avatar);
		item.color = element.user.color;
		item.shortTitle = element.user.first_name? element.user.first_name: element.user.name;
		item.subtitle = element.user.work_position? element.user.work_position: BX.message("IM_LIST_EMPLOYEE");
	}
	else
	{
		item.id = 'chat'+element.chat.id;
		item.params.id = 'chat'+element.chat.id;

		item.title = element.chat.name;
		item.shortTitle = element.chat.name;
		item.subtitle = element.chat.type == "open"? BX.message('IM_LIST_CHAT_OPEN'): BX.message('IM_LIST_CHAT');
		item.imageUrl = ChatUtils.getAvatar(element.chat.avatar);
		item.color = element.chat.color;
	}

	item.styles = {};
	item.styles.title = this.getTitleFormat(type, element[type]);

	return item;
};


ChatDataConverter.getListElementByUser = function(element)
{
	let item = {};

	item.source = ChatUtils.objectClone(element);
	item.id = parseInt(item.source.id);
	item.params = {
		id: item.id,
		action: 'item'
	};

	item.useLetterImage = true;
	item.sectionCode = 'user';
	item.title = item.source.name+(item.source.id == this.userId? ' ('+BX.message("IM_YOU")+')': '');
	item.imageUrl = ChatUtils.getAvatar(item.source.avatar);
	item.color = item.source.color;
	item.shortTitle = item.source.first_name? item.source.first_name: item.source.name;
	item.subtitle = item.source.work_position? item.source.work_position: BX.message("IM_LIST_EMPLOYEE");

	item.styles = {};
	item.styles.title = this.getTitleFormat('user', item.source);

	return item;
};

ChatDataConverter.getListElementByChat = function(element)
{
	let item = {};

	item.source = ChatUtils.objectClone(element);
	item.id = 'chat'+parseInt(item.source.id);
	item.params = {
		id: item.id,
		action: 'item'
	};

	item.sectionCode = 'chat';
	item.useLetterImage = true;
	item.title = item.source.name;
	item.imageUrl = ChatUtils.getAvatar(item.source.avatar);
	item.color = item.source.color;
	item.shortTitle = item.source.name;

	if (item.source.type == "chat")
	{
		item.subtitle = BX.message('IM_LIST_CHAT');
	}
	else if (item.source.type == "open")
	{
		item.subtitle = BX.message('IM_LIST_OPEN');
	}

	item.styles = {};
	item.styles.title = this.getTitleFormat('chat', item.source);

	return item;
};

ChatDataConverter.getListElementByLine = function(element)
{
	let item = {};

	item.source = ChatUtils.objectClone(element);
	item.id = 'queue'+parseInt(item.source.id);
	item.params = {
		id: item.id,
		action: 'item'
	};

	item.sectionCode = 'line';
	item.useLetterImage = true;
	item.title = item.source.name;
	item.imageUrl = '';
	item.color = '#16938b';
	item.shortTitle = item.source.name;

	item.styles = {};

	return item;
};

ChatDataConverter.getListElementByDepartment = function(element)
{
	let item = {};

	item.source = ChatUtils.objectClone(element);
	item.id = 'department'+parseInt(item.source.id);
	item.params = {
		id: item.id,
		action: 'item'
	};

	let subtitle = '';
	if (item.source.full_name.indexOf(item.source.name+' / ') === 0)
	{
		let length = (item.source.name+' / ').length;
		subtitle = item.source.full_name.substr(length)
	}

	item.sectionCode = 'department';
	item.useLetterImage = true;
	item.title = item.source.name;
	item.subtitle = subtitle;
	item.imageUrl = '';
	item.color = '#737373';
	item.shortTitle = item.source.name;

	item.styles = {};

	return item;
};


ChatDataConverter.getPushFormat = function(push)
{
	if (typeof (push) !== 'object' || typeof (push.params) === 'undefined')
	{
		return {'ACTION' : 'NONE'};
	}

	let result = {};
	try
	{
		result = JSON.parse(push.params);
	}
	catch (e)
	{
		result = {'ACTION' : push.params};
	}

	if (result.TAG)
	{
		result.ACTION = result.TAG;
		delete result.TAG;
	}

	return result;
};
