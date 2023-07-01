"use strict";
/**
 * @bxjs_lang_path extension.php
 */

/**
 * @module chat/dataconverter
 */

var ChatDataConverter = {};

ChatDataConverter.init = function(config)
{
	this.userId = parseInt(config.userId)? parseInt(config.userId): 0;
	this.generalChatId = parseInt(config.generalChatId)? parseInt(config.generalChatId): 0;
	this.isIntranetInvitationAdmin = config.isIntranetInvitationAdmin === true;
	this.listType = config.listType === 'lines'? 'lines': 'recent';
	this.updateRuntimeData = typeof config.updateRuntimeDataFunction == 'function'? config.updateRuntimeDataFunction: (element) => {};
	this.imagePath = component.path+'images';
};

ChatDataConverter.getElementFormat = function(element)
{
	let item = {};
	// item.useEstimatedHeight = true;
	item.id = element.id;
	item.date = ChatUtils.getTimestamp(element.message.date);

	item.params = {
		id : element.id,
		type : element.type,
		useLetterImage : true,
	};

	item.sortValues = {
		order : item.date
	};

	if (element.type == 'user')
	{
		item.title = element.user.name+(element.user.id == this.userId? ' ('+BX.message("IM_YOU")+')': '');
		item.imageUrl = ChatUtils.getAvatar(element.user.avatar);
		if (!item.imageUrl && !element.user.last_activity_date)
		{
			item.imageUrl = this.imagePath + '/avatar_wait_x3.png';
		}
		item.color = element.user.color;
		item.sectionCode = element.pinned? 'pinned': 'general';
		item.subtitle = element.message.text;

		if (item.subtitle && element.message.id > 0)
		{
			if (element.invited && !element.user.last_activity_date)
			{
				item.color = "#6b6b6b";
			}
		}
		else
		{
			if (element.invited && !element.user.last_activity_date)
			{
				item.color = "#6b6b6b";
				item.subtitle = BX.message("USER_INVITED_2");
			}
			else if (element.user.work_position)
			{
				item.subtitle = element.user.work_position;
			}
			else if (element.user.extranet)
			{
				item.subtitle = BX.message("IM_LIST_EXTRANET");
			}
			else
			{
				item.subtitle = BX.message("IM_LIST_EMPLOYEE");
			}
		}
	}
	else if (element.type == 'notification')
	{
		item.title = BX.message("NOTIFICATION_TITLE");
		item.imageUrl = this.imagePath + '/avatar_notify_x3.png';
		item.sectionCode = element.pinned? 'pinned': 'general';
		let messageText = ChatMessengerCommon.purifyText(element.message.text, element.message.params);
		item.subtitle = element.user.first_name && element.user.last_name ?
			element.user.first_name + ' ' + element.user.last_name.charAt(0) + ': ' + messageText :
			messageText;
	}
	else
	{
		item.title = element.chat.name;
		item.imageUrl = ChatUtils.getAvatar(element.chat.avatar);

		if (!item.imageUrl)
		{
			if (element.chat.id == this.generalChatId)
			{
				item.imageUrl = this.imagePath+'/avatar_general_x3.png';
			}
			else if (element.chat.type == 'support24Notifier')
			{
				item.imageUrl = this.imagePath+'/avatar_24_x3.png';
			}
			else if (element.chat.type == 'support24Question')
			{
				item.imageUrl = this.imagePath+'/avatar_24_question_x3.png';
			}
		}

		item.color = element.chat.color;
		if (this.listType == 'lines' && element.chat.type == 'lines')
		{
			if (!element.lines || element.lines.status < 10)
			{
				item.sectionCode = 'new';
				let session = ChatMessengerCommon.linesGetSession(element.chat);
				if (session && session.dateCreate)
				{
					item.sortValues.order = session.dateCreate;
					item.params.date = session.dateCreate;
				}
			}
			else if (element.lines.status < 40)
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

	if (Application.getApiVersion() < 34)
	{
		if (!element.counter && element.unread)
		{
			item.messageCount = 1;
		}
	}
	else
	{
		item.unread = !element.counter && element.unread;
	}

	item.backgroundColor = element.pinned? '#f6f6f6': '#ffffff';

	item.styles = {};
	item.styles.avatar = this.getAvatarFormat(element);
	item.styles.title = this.getTitleFormat(element);
	item.styles.subtitle = this.getTextFormat(element);
	item.styles.date = this.getDateFormat(element);
	item.styles.counter = this.getCounterFormat(element);

	item.actions = this.getActionList(element);

	item.menuMode = 'dialog';

	this.updateRuntimeData(element);

	return item;
};

ChatDataConverter.getElementByEntity = function(type, entity)
{
	let result = {
		id: parseInt(entity.id),
		type: type,
		counter: 0,
		invited: false,
		pinned: false,
		title: "",
		avatar: {
			url: "",
			color: ""
		},
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
		result.avatar.color =  entity.color;
		result.avatar.url =  entity.avatar;
		result.title =  entity.name;
	}
	else
	{
		result.user = {};
		result.chat = entity;
		result.avatar.color =  entity.color;
		result.avatar.url =  entity.avatar;
		result.title =  entity.name;
	}

	return result;
};

ChatDataConverter.getAvatarFormat = function(element)
{
	let result = {};
	if (element.type == 'user')
	{
		if (element.user.network && element.user.external_auth_id === 'support24')
		{

		}
		else
		{
			let status = this.getUserImageCode(element);
			if (
				status === 'idle'
				|| status === 'break-idle'
				|| status === 'break'
				|| status === 'video'
			)
			{
				result = {
					image : {
						url: this.imagePath+'/status_user_'+status+'_x3.png'
					}
				}
			}
			else if (status)
			{
				result = {image: {name: 'status_'+status}};
			}
		}
	}
	else if (element.type == 'notification')
	{

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
	}

	return result;
};

ChatDataConverter.getTitleFormat = function(element)
{
	let result = {};
	if (element.type == 'user')
	{
		if (element.user.id == this.userId)
		{
			result = {
				image: {name: 'name_status_owner'}
			};
		}
		else if (element.user.network && element.user.external_auth_id === 'support24')
		{
			if (ChatUtils.getAvatar(element.user.avatar))
			{
				result = {
					image : {
						url: this.imagePath + '/status_24.png'
					}
				};
			}
		}
		else if (element.user.network)
		{
			result = {
				color: '#0a962f',
				image: {name: 'name_status_network'}
			};
		}
		else if (element.user.bot)
		{
			result = {
				color: '#725acc',
				image: {name: 'name_status_bot'}
			};
		}
		else if (element.user.extranet)
		{
			result = {
				color: '#ca7b00',
				image: {name: 'name_status_extranet'}
			};
		}
		else if (element.user.connector)
		{
			result = {
				color: '#0a962f',
				image: {name: 'name_status_network'}
			};
		}
		else
		{
			let status = ChatMessengerCommon.getUserStatus(element.user);
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
	else if (element.type == 'chat')
	{
		if (element.chat.type == 'lines')
		{
			if (this.listType == 'recent')
			{
				result = {
					color: '#16938b',
					image: {name: 'name_status_lines'},
				};
			}
			else if (element.chat.owner == this.userId)
			{
				result = {
					image: {name: 'name_status_owner'},
				};
			}
			else if (element.chat.owner == 0)
			{
				result = {
					image: {name: 'name_status_new'},
					color: '#e66467',
				};
			}
		}
		else if (element.chat.type == 'open' && element.chat.id !== this.generalChatId)
		{
			result = {
				image: {
					url: this.imagePath + '/status_dialog_open.png',
				}
			};
		}
		else if (element.chat.type == 'call')
		{
			result = {
				image: {name: 'name_status_call'},
			};
		}
		else if (element.chat.type == 'support24Notifier' || element.chat.type == 'support24Question')
		{
			result = {
				color: '#0165af',
			};

			if (ChatUtils.getAvatar(element.chat.avatar))
			{
				result.image = {
					url: this.imagePath + '/status_24.png',
				};
			}
		}
		else
		{
			if (element.chat.extranet)
			{
				result = {
					color: '#ca7b00',
					image: {name: 'name_status_extranet'}
				};
			}

			//move from the condition when the native bug with 2 icons not displayed at the same time is fixed.
			if (element.chat.mute_list[this.userId])
			{
				result.additionalImage = {name: 'name_status_mute'};
			}
		}
	}

	result.font = {
		fontStyle: "semibold",
		color: typeof result.color != "undefined"? result.color: '#333333'
	};

	return result;
};

ChatDataConverter.getCounterFormat = function(element)
{
	let result = {backgroundColor: '#47AADE'};
	if (element.type != 'chat')
	{
		return result;
	}

	if (!(element.chat.type == 'lines' || element.chat.type == 'call'))
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
	let url = '';
	let sizeMultiplier = 0.7;

	if (element.message.author_id == this.userId)
	{
		if (element.type == 'user' && element.user.id == this.userId)
		{
			name = 'message_delivered';
		}
		else if (element.liked)
		{
			url = this.imagePath + '/status_reaction.png';
			sizeMultiplier = 1.2;
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

	const date = {
		image: {
			sizeMultiplier,
			url,
		}
	};

	if (name !== '')
	{
		date.image.name = name;
	}

	return date;
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
	else if (
		element.type === 'user'
		&& element.invited
		&& !element.user.last_activity_date
	)
	{
		result = {
			font: {
				size: '14',
				color: '#27a1d6',
				fontStyle: 'medium',
			},
			cornerRadius: 12,
			backgroundColor: "#D9F5FD",
			padding: {
				top:3.5,
				right:12,
				bottom:3.5,
				left:12
			}
		}
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
		|| data.originStatus == 'break-idle'
		|| data.originStatus == 'break'
		|| data.originStatus == 'mobile'
		|| data.originStatus == 'call'
		|| data.originStatus == 'video'
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

		if (element.invited && !element.user.last_activity_date)
		{
			if (
				this.isIntranetInvitationAdmin
				|| element.invited && element.invited.originator_id == this.userId
			)
			{
				if (element.invited && element.invited.can_resend)
				{
					result.push({
						title : BX.message("ELEMENT_MENU_INVITE_RESEND"),
						identifier : "inviteResend",
						color : "#aac337"
					});
				}
				result.push({
					title : BX.message("ELEMENT_MENU_INVITE_CANCEL"),
					color : "#df532d",
					identifier : "inviteCancel",
				});
			}
		}
		else
		{
			result.push({
				title : element.pinned? BX.message("ELEMENT_MENU_UNPIN"): BX.message("ELEMENT_MENU_PIN"),
				identifier : element.pinned? "unpin": "pin",
				color : "#3e99ce",
				iconName : "action_"+(element.pinned? "unpin": "pin"),
				direction: 'leftToRight'
			});

			result.push({
				title : element.unread || element.counter? BX.message("ELEMENT_MENU_READ"): BX.message("ELEMENT_MENU_UNREAD"),
				iconName : "action_"+(element.unread || element.counter? "read": "unread"),
				identifier : element.unread || element.counter? "read": "unread",
				color : "#23ce2c",
				direction: 'leftToRight',
				fillOnSwipe: true,
			});

			if (!element.user.bot)
			{
				result.push({
					title : BX.message("ELEMENT_MENU_PROFILE"),
					identifier : "profile",
					color : "#3e99ce",
					iconName : "action_userlist",
				});
			}

			if (
				!element.options
				|| !element.options.default_user_record
			)
			{
				result.push({
					title : BX.message("ELEMENT_MENU_HIDE"),
					iconName : "action_delete",
					identifier : "hide",
					color : "#df532d"
				});
			}
		}

	}
	else if (element.type == 'notification')
	{
		result = [];
		result.push({
			title : element.pinned? BX.message("ELEMENT_MENU_UNPIN"): BX.message("ELEMENT_MENU_PIN"),
			identifier : element.pinned? "unpin": "pin",
			color : "#3e99ce",
			iconName : "action_"+(element.pinned? "unpin": "pin"),
			direction: 'leftToRight'
		});
		result.push({
			title : element.unread || element.counter? BX.message("ELEMENT_MENU_READ"): BX.message("ELEMENT_MENU_UNREAD"),
			iconName : "action_"+(element.unread || element.counter? "read": "unread"),
			identifier : element.unread || element.counter? "read": "unread",
			color : "#23ce2c",
			direction: 'leftToRight',
			fillOnSwipe: true,
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
						color : "#3e99ce",
						direction: 'leftToRight'
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
						color : "#3e99ce",
						direction: 'leftToRight'
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
			result = [];
			if (element.chat.type !== 'announcement' && element.chat.type !== 'support24Question')
			{
				result.push({
					title : element.chat.mute_list[this.userId]? BX.message("ELEMENT_MENU_UNMUTE"): BX.message("ELEMENT_MENU_MUTE"),
					identifier : element.chat.mute_list[this.userId]? "unmute": "mute",
					iconName : "action_"+(element.chat.mute_list[this.userId]? "unmute": "mute"),
					color : "#aaabac"
				});
			}

			result.push({
				title : BX.message("ELEMENT_MENU_HIDE"),
				iconName : "action_delete",
				identifier : "hide",
				color : "#df532d"
			});

			result.push({
				title : element.pinned? BX.message("ELEMENT_MENU_UNPIN"): BX.message("ELEMENT_MENU_PIN"),
				iconName : "action_"+(element.pinned? "unpin": "pin"),
				identifier : element.pinned? "unpin": "pin",
				color : "#3e99ce",
				direction: 'leftToRight'
			});

			result.push({
				title : element.unread || element.counter? BX.message("ELEMENT_MENU_READ"): BX.message("ELEMENT_MENU_UNREAD"),
				iconName : "action_"+(element.unread || element.counter? "read": "unread"),
				identifier : element.unread || element.counter? "read": "unread",
				color : "#23ce2c",
				direction: 'leftToRight',
				fillOnSwipe: true,
			});
		}
	}

	return result;
};

ChatDataConverter.getListFormat = function (list)
{
	let result = [];
	let resultIndex = {};

	list.forEach((element) =>
	{
		if (!element)
		{
			return;
		}

		element = this.getListElement(element);

		if (resultIndex[element.id] !== undefined)
		{
			if (element.options && element.options.default_user_record)
			{
				return;
			}
			result[resultIndex[element.id]] = element;
		}
		else
		{
			let newLength = result.push(element);
			resultIndex[element.id] = newLength - 1;
		}
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
		if (user.last_activity_date)
		{
			user.last_activity_date = !dateAtom? new Date(user.last_activity_date): user.last_activity_date.toString();
		}
		else
		{
			user.last_activity_date = false;
		}

		if (user.mobile_last_date)
		{
			user.mobile_last_date = !dateAtom? new Date(user.mobile_last_date): user.mobile_last_date.toString();
		}
		else
		{
			user.mobile_last_date = false;
		}

		if (user.idle)
		{
			user.idle = !dateAtom? new Date(user.idle): user.idle.toString();
		}
		else
		{
			user.idle = false;
		}

		if (user.absent)
		{
			user.absent = !dateAtom? new Date(user.absent): user.absent.toString();
		}
		else
		{
			user.absent = false;
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
		item.params.external_auth_id = element.user.external_auth_id;

		item.title = element.user.name+(element.user.id == this.userId? ' ('+BX.message("IM_YOU")+')': '');

		item.imageUrl = ChatUtils.getAvatar(element.user.avatar);
		if (!item.imageUrl && !element.user.last_activity_date)
		{
			item.imageUrl = this.imagePath + '/avatar_wait_x3.png';
		}

		item.color = element.user.color;
		item.shortTitle = element.user.first_name? element.user.first_name: element.user.name;
		item.subtitle = element.user.work_position? element.user.work_position: '';
		if (!element.user.work_position)
		{
			item.subtitle = element.user.extranet? BX.message("IM_LIST_EXTRANET"): BX.message("IM_LIST_EMPLOYEE");
		}
	}
	else if (type == 'notification')
	{

	}
	else
	{
		item.id = 'chat'+element.chat.id;
		item.params.id = 'chat'+element.chat.id;

		item.title = element.chat.name;
		item.shortTitle = element.chat.name;
		item.subtitle = element.chat.type == "open"? BX.message('IM_LIST_CHAT_OPEN_NEW'): BX.message('IM_LIST_CHAT_NEW');
		item.imageUrl = ChatUtils.getAvatar(element.chat.avatar);
		if (!item.imageUrl)
		{
			if (element.chat.id == this.generalChatId)
			{
				item.imageUrl = this.imagePath + '/avatar_general_x3.png';
			}
			else if (element.chat.type == 'support24Notifier')
			{
				item.imageUrl = this.imagePath+'/avatar_24_x3.png';
			}
		}
		item.color = element.chat.color;
	}

	item.styles = {};
	item.styles.title = this.getTitleFormat(type, element[type]);

	return item;
};



ChatDataConverter.getListElement = function(element)
{
	if (!element)
	{
		return null;
	}

	element.user = this.getUserDataFormat(element.user);

	if (typeof element.message != 'undefined')
	{
		element.message.date = new Date(element.message.date);
		element.message.text = ChatMessengerCommon.purifyText(element.message.text, element.message.params);
	}

	if (typeof element.chat != 'undefined')
	{
		element.chat.date_create = new Date(element.chat.date_create);
	}

	return element;
}

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
	if (!item.imageUrl && !item.source.last_activity_date)
	{
		item.imageUrl = this.imagePath + '/avatar_wait_x3.png';
	}
	item.color = item.source.color;
	item.shortTitle = item.source.first_name? item.source.first_name: item.source.name;
	item.subtitle = item.source.work_position? item.source.work_position: '';
	if (!item.subtitle)
	{
		item.subtitle = item.source.extranet? BX.message("IM_LIST_EXTRANET"): BX.message("IM_LIST_EMPLOYEE");
	}

	item.styles = {};
	item.styles.title = this.getTitleFormat(element);

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
	if (!item.imageUrl)
	{
		if (item.source.id == this.generalChatId)
		{
			item.imageUrl = this.imagePath + '/avatar_general_x3.png';
		}
		else if (item.source.type == 'support24Notifier')
		{
			item.imageUrl = this.imagePath+'/avatar_24_x3.png';
		}
	}

	item.color = item.source.color;
	item.shortTitle = item.source.name;

	if (item.source.type == "chat")
	{
		item.subtitle = BX.message('IM_LIST_CHAT_NEW');
	}
	else if (item.source.type == "open")
	{
		item.subtitle = BX.message('IM_LIST_OPEN');
	}

	item.styles = {};
	item.styles.title = this.getTitleFormat(element);

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

ChatDataConverter.getCallListElement = function (callStatus, call)
{
	let elementConfig = {};
	if (callStatus === 'local')
	{
		elementConfig = {
			text: BX.message('CALL_STATUS_OPEN'),
			color: "#EEF2F4",
			background: "#47AADE",
			canJoin: true,
		}
	}
	else if (callStatus === 'none')
	{
		elementConfig = {
			text: BX.message('CALL_STATUS_JOIN'),
			color: "#EEF2F4",
			background: "#91C000",
			canJoin: true,
		}
	}
	else
	{
		elementConfig = {
			text: BX.message('CALL_STATUS_REMOTE'),
			color: "#525C69",
			background: "#EEF2F4",
			canJoin: false
		}
	}

	return {
		id: 'call'+call.id,
		title: call.associatedEntity.name,
		subtitle: elementConfig.text,
		imageUrl: ChatUtils.getAvatar(call.associatedEntity.avatar),
		useLetterImage: true,
		unselectable: true,
		color: "#368c00",
		sectionCode: 'call',
		params: {
			call: {id: call.id, provider: call.provider, associatedEntity: call.associatedEntity},
			isLocal: callStatus === 'local',
			canJoin: elementConfig.canJoin,
			type: 'call',
		},
		styles: {
			title: {
				image: { name: "status_call", sizeMultiplier: 1.4},
				font: { fontStyle: "semibold" }
			},
			subtitle: {
				font: { size: '13', fontStyle: 'medium', color: elementConfig.color },
				cornerRadius: 12,
				backgroundColor: elementConfig.background,
				padding: {top:3.5, right:12, bottom:3.5, left:12}
			}
		},
	}
}

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

ChatDataConverter.preparePushFormat = function(element)
{
	let indexToNameMap =
	{
		1: "chat",
		2: "chatId",
		3: "counter",
		4: "dialogId",
		5: "files",
		6: "message",
		8: "users",
		9: "name",
		10: "avatar",
		11: "color",
		12: "notify",
		13: "type",
		14: "extranet",

		20: "date_create",
		21: "owner",
		23: "entity_id",
		24: "entity_type",
		203: "entity_data_1",
		204: "entity_data_2",
		205: "entity_data_3",
		201: "call",
		202: "call_number",

		40: "first_name",
		41: "last_name",
		42: "gender",
		43: "work_position",
		400: "active",
		401: "birthday",
		402: "bot",
		403: "connector",
		404: "external_auth_id",
		406: "network",

		65: "textLegacy",
		61: "date",
		62: "prevId",
		63: "params",
		64: "senderId",
		601: "system",

		80: "extension",
		81: "image",
		82: "progress",
		83: "size",
		84: "status",
		85: "urlDownload",
		86: "urlPreview",
		87: "urlShow",
		88: "width",
		89: "height",
	};

	let result = ChatDataConverter.changeKeysRecursive(element, indexToNameMap);

	if (
		result.chat
		&& typeof result.chat === 'object'
		&& typeof result.chat.id !== 'undefined'
	)
	{
		let chat = {};
		chat.id = result.chat.id;
		chat.avatar = result.chat.avatar || '';
		chat.call = result.chat.call || '0';
		chat.call_number = result.chat.call_number || '';
		chat.color = result.chat.color;
		chat.date_create = result.chat.date_create;
		chat.entity_data_1 = result.chat.entity_data_1 || '';
		chat.entity_data_2 = result.chat.entity_data_2 || '';
		chat.entity_data_3 = result.chat.entity_data_3 || '';
		chat.entity_id = result.chat.entity_id || '';
		chat.entity_type = result.chat.entity_type || '';
		chat.extranet = result.chat.extranet || false;
		chat.manager_list = [];
		chat.mute_list = {};
		chat.name = ChatUtils.htmlspecialcharsback(result.chat.name);
		chat.owner = result.chat.owner;
		chat.type = result.chat.type;

		result.chat = {[chat.id]: chat};
	}
	else
	{
		result.chat = {};
	}

	let userId = 0;
	let userName = '';

	if (
		result.users
		&& typeof result.users === 'object'
		&& typeof result.users.id !== 'undefined'
	)
	{
		let lastActivityDate = new Date();
		if (typeof result.message === 'object' && result.message.date)
		{
			lastActivityDate = result.message.date;
		}

		let user = {};
		user.id = result.users.id;
		user.color = result.users.color;
		user.first_name = result.users.first_name;
		user.last_name = result.users.last_name;
		user.name = result.users.name;
		user.idle = false;
		user.departments = [];
		user.absent = result.users.absent || false;
		user.active = result.users.active || true;
		user.avatar = result.users.avatar || '';
		user.birthday = result.users.birthday || false;
		user.bot = result.users.bot || false;
		user.connector = result.users.connector || false;
		user.network = result.users.network || false;
		user.extranet = result.users.extranet || false;
		user.external_auth_id = result.users.external_auth_id || 'default';
		user.work_position = result.users.work_position || '';
		user.gender = result.users.gender === 'F'? 'F': 'M';
		user.last_activity_date = lastActivityDate;

		userId = user.id;
		userName = user.name;

		result.users = {[user.id]: user};
	}
	else
	{
		result.users = {};
	}

	if (result.files && typeof result.files === 'object')
	{
		let files = {};
		for (let fileId in result.files)
		{
			if (!result.files.hasOwnProperty(fileId))
			{
				continue;
			}

			let file = {};

			file.id = result.files[fileId].id;
			file.authorId = userId;
			file.authorName = userName;
			file.chatId = result.chatId;
			file.date = new Date().toISOString();
			file.image = result.files[fileId].image || false;
			file.extension = result.files[fileId].extension;
			file.name = result.files[fileId].name;
			file.type = result.files[fileId].type;
			file.progress = result.files[fileId].progress || 100;
			file.status = result.files[fileId].status || 'done';
			file.urlDownload = result.files[fileId].urlDownload || '';
			file.urlPreview = result.files[fileId].urlPreview || '';
			file.urlShow = result.files[fileId].urlShow || '';

			files[fileId] = file;
		}

		result.files = files;
	}
	else
	{
		result.files = {};
	}

	result.notify = result.notify || true;

	if (
		result.message
		&& typeof result.message === 'object'
		&& typeof result.message.id !== 'undefined'
	)
	{
		let message = {};
		message.id = result.message.id;
		message.chatId = result.chatId;
		message.date = result.message.date;
		message.params = result.message.params;
		message.prevId = result.message.prevId;
		message.recipientId = result.dialogId;
		message.senderId = result.message.senderId;
		message.system = result.message.system || 'N';
		message.text = result.message.text || '';
		message.textOriginal = result.message.textOriginal || '';
		message.push = true;

		result.message = message;
	}

	result.userInChat = {};

	return result;
};

ChatDataConverter.changeKeysRecursive = function(object, indexToNameMap)
{
	if (!object || typeof object !== 'object')
	{
		return object;
	}

	if (object instanceof Array)
	{
		return object.map(element => ChatDataConverter.changeKeysRecursive(element, indexToNameMap));
	}

	let result = {};
	for (let index in object)
	{
		if (!object.hasOwnProperty(index))
		{
			continue;
		}

		let key = indexToNameMap[index]? indexToNameMap[index]: index;
		result[key] = ChatDataConverter.changeKeysRecursive(object[index], indexToNameMap);
	}

	return result;
};

ChatDataConverter.prepareNotificationPushFormat = function(element)
{
	const indexToNameMap = {
		1: "id",
		2: "type",
		3: "date",
		4: "text",
		6: "tag",
		7: "onlyFlash",
		8: "originalTag",
		9: "settingName",
		10: "counter",
		11: "userId",
		12: "userName",
		13: "userColor",
		14: "userAvatar",
		15: "userLink",
		16: "params",
		17: "buttons",
	};

	let convertedParams = ChatDataConverter.changeKeysRecursive(element, indexToNameMap);

	return {
		id: convertedParams['id'],
		counter: convertedParams['counter'],
		date: convertedParams['date'],
		type: convertedParams['type'],
		text: convertedParams['text'],
		text_converted: convertedParams['text'],
		tag: convertedParams['tag'],
		onlyFlash: convertedParams['onlyFlash'],
		originalTag: convertedParams['originalTag'],
		settingName: convertedParams['settingName'],
		userId: convertedParams['userId'],
		userName: convertedParams['userName'],
		userColor: convertedParams['userColor'],
		userAvatar: convertedParams['userAvatar'],
		userLink: convertedParams['userLink'],
		params: convertedParams['params'],
		buttons: convertedParams['buttons'],
	}
};
