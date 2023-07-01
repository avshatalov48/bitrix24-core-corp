"use strict";
/**
 * @bxjs_lang_path extension.php
 */

/**
 * @requires module:chat/utils
 * @module chat/messengercommon
 */

// after change any function in this file, sync with IM DESKTOP and IM MOBILE

var ChatMessengerCommon = {};

ChatMessengerCommon.getUserStatus = function(userData, onlyStatus) // after change this code, sync with IM and MOBILE
{
	onlyStatus = onlyStatus !== false;

	var online = this.getOnlineData(userData);

	var status = '';
	var statusText = '';
	var originStatus = '';
	var originStatusText = '';
	if (!userData)
	{
		status = 'guest';
		statusText = BX.message('IM_STATUS_GUEST');
	}
	else if (userData.network)
	{
		status = 'network';
		statusText = BX.message('IM_STATUS_NETWORK');
	}
	else if (userData.bot)
	{
		status = 'bot';
		statusText = BX.message('IM_STATUS_BOT');
	}
	else if (userData.connector)
	{
		status = userData.status == 'offline'? 'lines': 'lines-online';
		statusText = BX.message('IM_CL_USER_LINES');
	}
	else if (userData.status == 'guest')
	{
		status = 'guest';
		statusText = BX.message('IM_STATUS_GUEST');
	}
	else if (this.getCurrentUser() == userData.id)
	{
		status = userData.status? userData.status.toString(): '';
		statusText = status? BX.message('IM_STATUS_'+status.toUpperCase()): '';
	}
	else if (!online.isOnline)
	{
		status = 'offline';
		statusText = BX.message('IM_STATUS_OFFLINE');
	}
	else if (this.getUserMobileStatus(userData))
	{
		status = 'mobile';
		statusText = BX.message('IM_STATUS_MOBILE');
	}
	else if (this.getUserIdleStatus(userData, online))
	{
		status = userData.status === 'break'? 'break-idle': 'idle';
		statusText = BX.message('IM_STATUS_AWAY_TITLE').replace('#TIME#', this.getUserIdle(userData));
	}
	else
	{
		status = userData.status? userData.status.toString(): '';
		statusText = BX.message('IM_STATUS_'+status.toUpperCase());
	}

	if (this.isBirthday(userData.birthday) && (userData.status == 'online' || !online.isOnline))
	{
		var originStatus = status;
		var originStatusText = statusText;

		status = 'birthday';
		if (online.isOnline)
		{
			statusText = BX.message('IM_M_BIRTHDAY_MESSAGE_SHORT');
		}
		else
		{
			statusText = BX.message('IM_STATUS_OFFLINE');
		}
	}
	else if (userData.absent)
	{
		var originStatus = status;
		var originStatusText = statusText;

		status = 'vacation';
		if (online.isOnline)
		{
			statusText = BX.message('IM_STATUS_ONLINE');
		}
		else
		{
			statusText = BX.message('IM_STATUS_VACATION');
		}
	}

	return onlyStatus? status: {
		status: status,
		statusText: statusText,
		originStatus: originStatus? originStatus: status,
		originStatusText: originStatusText? originStatusText: statusText,
	};
};

ChatMessengerCommon.getUserMobileStatus = function(userData) // after change this code, sync with IM and MOBILE
{
	if (!userData)
		return false;

	var status = false;
	var mobile_last_date = userData.mobile_last_date;
	var last_activity_date = userData.last_activity_date;
	if (
		(new Date())-mobile_last_date < BX.user.getSecondsForLimitOnline()*1000
		&& last_activity_date-mobile_last_date < 300*1000
	)
	{
		status = true;
	}

	return status;
};

ChatMessengerCommon.getUserIdleStatus = function(userData, online) // after change this code, sync with IM and MOBILE
{
	if (!userData)
		return '';

	online = online? online: BX.user.getOnlineStatus(userData.last_activity_date);

	return userData.idle && online.isOnline;
};

ChatMessengerCommon.getUserPosition = function(userData)
{
	if (!userData)
		return '';

	var position = '';
	if(userData.work_position)
	{
		position = userData.work_position;
	}
	else if (userData.extranet || userData.network)
	{
		position = BX.message('IM_CL_USER_EXTRANET');
	}
	else if (userData.bot)
	{
		position = BX.message('IM_CL_BOT');
	}
	else
	{
		position = this.isIntranet()? BX.message('IM_CL_USER_B24'): BX.message('IM_CL_USER');
	}

	return position
};

ChatMessengerCommon.getChatDescription = function(chatData)
{
	if (!chatData)
		return '';

	var description = '';
	if (chatData.type == 'call')
	{
		description = BX.message("IM_CHAT_CALL");
	}
	else if (chatData.type == 'lines' || chatData.type == 'livechat')
	{
		description = BX.message("IM_CHAT_LINES");
	}
	else
	{
		if (chatData.type == 'open')
		{
			description = BX.message("IM_CHAT_OPEN_NEW");
		}
		else
		{
			description = BX.message("IM_CHAT_GROUP_NEW");
		}
	}

	return description
};

ChatMessengerCommon.linesGetSession = function(chatData) // after change this code, sync with IM and MOBILE
{
	var session = null;
	if (!chatData || chatData.type != "lines")
			return session;

	session = {};
	session.source = this.linesGetSource(chatData);

	var source = chatData.entity_id.toString().split('|');

	session.connector = source[0];
	session.canVoteHead = this.linesCanVoteAsHead(source[1]);

	var sessionData = chatData.entity_data_1.toString().split('|');
	var crmData = chatData.entity_data_2.toString().split('|');

	session.crm = typeof(sessionData[0]) != 'undefined' && sessionData[0] == 'Y'? 'Y': 'N';
	session.crmEntityType = typeof(sessionData[1]) != 'undefined'? sessionData[1]: 'NONE';
	session.crmEntityId = typeof(sessionData[2]) != 'undefined'? sessionData[2]: 0;
	session.crmLink = '';
	session.pin = typeof(sessionData[3]) != 'undefined' && sessionData[3] == 'Y'? 'Y': 'N';
	session.wait = typeof(sessionData[4]) != 'undefined' && sessionData[4] == 'Y'? 'Y': 'N';
	session.id = typeof(sessionData[5]) != 'undefined'? parseInt(sessionData[5]): Math.round(new Date()/1000)+chatData.id;
	session.dateCreate = typeof(sessionData[6]) != 'undefined' || sessionData[6] > 0? parseInt(sessionData[6]): session.id;
	session.lineId = typeof(sessionData[7]) != 'undefined' && sessionData[7] > 0? parseInt(sessionData[7]) : source[1];

	session.crmLinkLead = '';
	session.crmLead = 0;
	session.crmLinkCompany = '';
	session.crmCompany = 0;
	session.crmLinkContact = '';
	session.crmContact = 0;
	session.crmLinkDeal = '';
	session.crmDeal = 0;

	if(crmData)
	{
		var index;

		for (index = 0; index < crmData.length; index = index+2)
		{
			if(crmData[index] == 'LEAD' && crmData[index+1] != 0 && crmData[index+1] != 'undefined')
			{
				session.crmLinkLead = this.linesGetCrmPath('LEAD', crmData[index+1]);
				session.crmLead = crmData[index+1];
			}
			if(crmData[index] == 'COMPANY' && crmData[index+1] != 0 && crmData[index+1] != 'undefined')
			{
				session.crmLinkCompany = this.linesGetCrmPath('COMPANY', crmData[index+1]);
				session.crmCompany = crmData[index+1];
			}
			if(crmData[index] == 'CONTACT' && crmData[index+1] != 0 && crmData[index+1] != 'undefined')
			{
				session.crmLinkContact = this.linesGetCrmPath('CONTACT', crmData[index+1]);
				session.crmContact = crmData[index+1];
			}
			if(crmData[index] == 'DEAL' && crmData[index+1] != 0 && crmData[index+1] != 'undefined')
			{
				session.crmLinkDeal = this.linesGetCrmPath('DEAL', crmData[index+1]);
				session.crmDeal = crmData[index+1];
			}
			else
			{
				session.crmDeal = 0;
			}
		}
	}

	if (session.crmEntityType != 'NONE')
	{
		session.crmLink = this.linesGetCrmPath(session.crmEntityType, session.crmEntityId);
	}

	return session;
};

ChatMessengerCommon.linesGetSource = function(chatData) // after change this code, sync with IM and MOBILE
{
	var sourceId = '';
	if (!chatData || !(chatData.type == 'livechat' || chatData.type == 'lines'))
		return sourceId;

	if (chatData.type == 'livechat')
	{
		sourceId = 'livechat';
	}
	else
	{
		sourceId = (chatData.entity_id.toString().split('|'))[0];
	}

	if (sourceId == 'skypebot')
	{
		sourceId = 'skype';
	}
	else
	{
		sourceId = sourceId.replace('.', '_');
	}

	return sourceId;
};

ChatMessengerCommon.isBirthday = function(birthday) // after change this code, sync with IM and MOBILE
{
	var date = new Date();
	var currentDate = ("0" + date.getDate().toString()).substr(-2)+'-'+("0" + (date.getMonth() + 1).toString()).substr(-2);
	return birthday == currentDate;
};

ChatMessengerCommon.purifyText = function(text, params) // after change this code, sync with IM and MOBILE
{
	text = text? text.toString(): '';

	if (params && (params.WITH_ATTACH || params.ATTACH && params.ATTACH.length > 0))
	{
		if (params.ATTACH && params.ATTACH.length > 0)
		{
			const attachText = [];

			let skipAttachBlock = false;
			params.ATTACH.forEach(element => {
				if (element.DESCRIPTION === 'SKIP_MESSAGE')
				{
					skipAttachBlock = true;
				}
				else if (element.DESCRIPTION)
				{
					attachText.push(element.DESCRIPTION);
				}
			});

			if (!skipAttachBlock)
			{
				text = text
					+ (text? ' ': '')
					+ (attachText.length > 0? attachText.join(' '): '['+BX.message('IM_F_ATTACH')+']')
				;
			}
		}
		else if (params.WITH_ATTACH)
		{
			text = text
				+ (text? ' ': '')
				+ '['+BX.message('IM_F_ATTACH')+']'
			;
		}
	}

	if (text)
	{
		text = this.trimText(text);

		if (text.indexOf('/me') == 0)
		{
			text = text.substr(4);
		}
		else if (text.indexOf('/loud') == 0)
		{
			text = text.substr(6);
		}
		if (text.substr(-6) == '<br />')
		{
			text = text.substr(0, text.length-6);
		}
		text = text.replace(/<br><br \/>/ig, '<br />');
		text = text.replace(/<br \/><br>/ig, '<br />');
		text = text.replace(/\[br]/ig, ' ');

		text = text.replace(/\[CODE\]\n?([\0-\uFFFF]*?)\[\/CODE\]/ig, function(whole,text) {
			return '['+BX.message('IM_M_CODE_BLOCK')+'] ';
		});

		text = text.replace(/\[PUT(?:=(?:.+?))?\](?:.+?)?\[\/PUT]/ig, function(match)
		{
			return match.replace(/\[PUT(?:=(.+))?\](.+?)?\[\/PUT]/ig, function(whole, command, text) {
				return  text? text: command;
			});
		});

		text = text.replace(/\[SEND(?:=(?:.+?))?\](?:.+?)?\[\/SEND]/ig, function(match)
		{
			return match.replace(/\[SEND(?:=(.+))?\](.+?)?\[\/SEND]/ig, function(whole, command, text) {
				return  text? text: command;
			});
		});
		text = text.replace(
			/\[context=(chat\d+|\d+:\d+)\/(\d+)](.*?)\[\/context]/gi,
			(whole, dialogId, messageId, message) => message
		);

		text = text.replace(/\[[buis]\](.*?)\[\/[buis]\]/ig, '$1');
		text = text.replace(/\[url\](.*?)\[\/url\]/ig, '$1');
		text = text.replace(/\[url=([^\]]+)](.*?)\[\/url]/ig, '$2');
		text = text.replace(/\[RATING=([1-5]{1})\]/ig, function(whole, rating) {return '['+BX.message('IM_F_RATING')+'] ';});
		text = text.replace(/\[ATTACH=([0-9]{1,})\]/ig, function(whole, rating) {return '['+BX.message('IM_F_ATTACH')+'] ';});
		text = text.replace(/\[USER=([0-9]+)( REPLACE)?](.*?)\[\/USER]/i, '$3');
		text = text.replace(/\[CHAT=(imol\|)?([0-9]{1,})\](.*?)\[\/CHAT\]/ig, (whole, imol, chatId, text) => text);
		text = text.replace(/\[CALL=(.*?)](.*?)\[\/CALL\]/ig, '$2');
		text = text.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/ig, '$2');
		text = text.replace(/\[size=(\d+)](.*?)\[\/size]/ig, '$2');
		text = text.replace(/\[color=#([0-9a-f]{3}|[0-9a-f]{6})](.*?)\[\/color]/ig, '$2');
		text = text.replace(/<img.*?data-code="([^"]*)".*?>/ig, '$1');
		text = text.replace(/<span.*?title="([^"]*)".*?>.*?<\/span>/ig, '($1)');
		text = text.replace(/<img.*?title="([^"]*)".*?>/ig, '($1)');
		text = text.replace(/\[ATTACH=([0-9]{1,})\]/ig, function(whole, command, text) {return command == 10000? '': '['+BX.message('IM_F_ATTACH')+'] ';});
		text = text.replace(/<s>([^"]*)<\/s>/ig, ' ');
		text = text.replace(/\[s\]([^"]*)\[\/s\]/ig, ' ');
		text = text.replace(/\[icon\=([^\]]*)\]/ig, function(whole)
		{
			var title = whole.match(/title\=(.*[^\s\]])/i);
			if (title && title[1])
			{
				title = title[1];
				if (title.indexOf('width=') > -1)
				{
					title = title.substr(0, title.indexOf('width='))
				}
				if (title.indexOf('height=') > -1)
				{
					title = title.substr(0, title.indexOf('height='))
				}
				if (title.indexOf('size=') > -1)
				{
					title = title.substr(0, title.indexOf('size='))
				}
				if (title)
				{
					title = '('+this.trimText(title)+')';
				}
			}
			else
			{
				title = '('+BX.message('IM_M_ICON')+')';
			}
			return title;
		}.bind(this));

		text = text.split('<br />')
			.map(function(element) { return element.replace(/(&gt;&gt;).+/ig, " ["+BX.message("IM_M_QUOTE_BLOCK")+"] ") })
			.join(' ')
			.replace(/<\/?[^>]+>/gi, '')
			.replace(/------------------------------------------------------(.*?)------------------------------------------------------/gmi, " ["+BX.message("IM_M_QUOTE_BLOCK")+"] ")
			.replace(/-{54}(.*?)-{54}/gs, "["+BX.message("IM_M_QUOTE_BLOCK")+"]")
		;

		text = this.trimText(text);
	}

	if (!text || text.length <= 0)
	{
		if (params && (params.WITH_FILE || params.FILE_ID && params.FILE_ID.length > 0))
		{
			text = '['+BX.message('IM_F_FILE')+']';
		}
		else
		{
			text = BX.message('IM_M_DELETED');
		}
	}

	return text;
};

ChatMessengerCommon.getOnlineData = function(userData)
{
	let online = {};
	if (!userData)
	{
		return online;
	}

	if (userData.id == this.getCurrentUser())
	{
		userData.last_activity_date = new Date();
		userData.mobile_last_date = new Date(0);
		userData.idle = false;

		if (typeof RecentList != 'undefined')
		{
			RecentList.userData.last_activity_date = userData.last_activity_date;
			RecentList.userData.mobile_last_date = userData.mobile_last_date;
			RecentList.userData.idle = userData.idle;
		}
	}

	online = BX.user.getOnlineStatus(userData.last_activity_date);

	return online;
};

ChatMessengerCommon.trimText = function(string)
{
	if (BX.type.isString(string))
		return string.replace(/^[\s\r\n]+/g, '').replace(/[\s\r\n]+$/g, '');
	else
		return string;
};

ChatMessengerCommon.getUserIdle = function (userId) {return ''};

ChatMessengerCommon.getUserLastDate = function(userId, userData) {return '';};

ChatMessengerCommon.isIntranet = function() {return true;};

ChatMessengerCommon.isMobileNative = function() {return true;};

ChatMessengerCommon.getCurrentUser = function() {return BX.componentParameters.get('USER_ID', 0);};

ChatMessengerCommon.getDialogId = function()
{
	this.BXIM = window.BXIM;

	if (this.BXIM.messenger.currentTab.toString().substr(0, 4) == 'chat')
	{
		return this.BXIM.messenger.currentTab;
	}

	return parseInt(this.BXIM.messenger.currentTab);
};

ChatMessengerCommon.getChatUsers = function()
{
	this.BXIM = window.BXIM;

	if (this.BXIM.messenger.currentTab.toString().substr(0, 4) != 'chat')
	{
		return [parseInt(this.BXIM.messenger.currentTab)];
	}

	var chatId = this.BXIM.messenger.currentTab.toString().substr(4);
	var result = [];
	if (this.BXIM.messenger.userInChat[chatId])
	{
		result = this.BXIM.messenger.userInChat[chatId].map((item) => parseInt(item))
	}

	return result;
};

ChatMessengerCommon.linesCanVoteAsHead = function() {return false;};

ChatMessengerCommon.linesGetCrmPath = function() {return '';};


/* Dump for BX API */
if (typeof (BX.user) == 'undefined')
{
	BX.user = {};

	BX.user.getOnlineStatus = function(lastseen, now, utc)
	{
		lastseen = BX.type.isDate(lastseen) ? lastseen : (BX.type.isNumber(lastseen) ? new Date(lastseen * 1000) : new Date(0));
		now = BX.type.isDate(now) ? now : (BX.type.isNumber(now) ? new Date(now * 1000) : new Date());
		utc = !!utc;

		var result = {
			'isOnline': false,
			'status': 'offline',
			'statusText': BX.message('U_STATUS_OFFLINE'),
			'lastSeen': lastseen,
			'lastSeenText': '',
			'now': now,
			'utc': utc
		};

		if (lastseen.getTime() === 0)
		{
			return result;
		}

		result.isOnline = now.getTime() - lastseen.getTime() <= parseInt(BX.message('LIMIT_ONLINE'))*1000;
		result.status = result.isOnline? 'online': 'offline';
		result.statusText = BX.message('U_STATUS_'+result.status.toUpperCase());

		if (lastseen.getTime() > 0 && now.getTime() - lastseen.getTime() > 300*1000)
		{
			result.lastSeenText = BX.date.formatLastActivityDate(lastseen, now, utc);
		}

		return result;
	};
}

BX.user.getSecondsForLimitOnline = function()
{
	return parseInt(BX.message.LIMIT_ONLINE);
};

if (typeof (BX.date) == 'undefined')
{
	BX.date = {};
	BX.date.formatLastActivityDate = function (lastseen, now, utc) {return '';}
}



