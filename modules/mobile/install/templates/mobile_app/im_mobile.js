/* IM Mobile */
(function ()
{

	if (BX.ImLegacy)
		return;

	BX.MessengerMobileLegacy = function ()
	{
		this.notifyLoadFlag = false;
		this.notifyLastId = 0;
		this.messageCountArray = {};
		this.counterMessages = 0;
		this.counterNotifications = 0;
		this.timeoutUpdateCounters = null;

		this.timeoutRefreshMessage = null;
		this.timeoutRefreshNotifications = null;
		this.timeoutReadMessage = null;
		this.timeoutUpdateStateLight = null;
		this.timeoutAnimation = null;
		this.intervalAnimation = null;

		this.messageTmpIndex = 0;
		this.sendAjaxTry = 0;
		this.sendMessageFlag = false;

		this.historyMessage = {};
		this.historyMessageCount = 20;
		this.historyPage = 1;

		this.audio = {};
		this.audio.newMessage = null;
		this.audio.send = null;
		this.audio.reminder = null;
		this.audio.ready = false;

		this.writing = null;
		this.writingList = {};
		this.writingListTimeout = {};
		this.writingSendList = {};
		this.writingSendListTimeout = {};
	}

	BX.MessengerMobileLegacy.prototype.init = function (params)
	{
		BX.addCustomEvent("onPullError", BX.delegate(function (error)
		{
			if (error == 'AUTHORIZE_ERROR')
			{
				app.BasicAuth({success: BX.delegate(function ()
				{
					setTimeout(BX.delegate(this.updateStateLight, this), 1000);
				}, this)});
			}
		}, this));

		BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", BX.delegate(function (params)
		{
			//app.BasicAuth({success: BX.delegate(function(){
			setTimeout(BX.delegate(this.updateStateLight, this), 1000);
			//}, this)});
		}, this));

		BXMobileApp.addCustomEvent("onImError", BX.delegate(function (params)
		{
			if (params.error == 'AUTHORIZE_ERROR')
			{
				app.BasicAuth();
			}
			else if (params.error == 'RECENT_RELOAD')
			{
				app.BasicAuth({success: BX.delegate(function ()
				{
					setTimeout(BX.delegate(this.updateStateLight, this), 1000);
				}, this)});
			}
		}, this));

		BX.addCustomEvent("onPullEvent-im", BX.delegate(function (command, params)
		{
			return false;
			if (command == 'readMessage')
			{
				this.messageCountArray[params.userId] = 0;
				this.updateCounters();
			}
			else if (command == 'readMessageChat')
			{
				this.messageCountArray['chat' + params.chatId] = 0;
				this.updateCounters();
			}
			else if (command == 'chatUserLeave')
			{
				if (params.userId == BX.message('USER_ID'))
				{
					this.messageCountArray['chat' + params.chatId] = 0;
					this.updateCounters();
				}
			}
			else if (command == 'readNotify')
			{
				this.notifyLastId = parseInt(params.lastId);
				this.counterNotifications = 0;
				this.updateCounters();
			}
			else if (command == 'message' || command == 'messageChat')
			{
				var userId = params.MESSAGE.senderId;
				if (userId == BX.message('USER_ID'))
				{
					this.messageCountArray[params.MESSAGE.recipientId] = 0;
					this.updateCounters();
					return;
				}
				if (command == 'messageChat')
					userId = params.MESSAGE.recipientId;

				if (typeof(this.messageCountArray[userId]) != 'undefined')
					this.messageCountArray[userId]++;
				else
					this.messageCountArray[userId] = 1;

				app.getVar({'var': 'PAGE_ID', 'from': 'current', 'callback': BX.delegate(function (PAGE_ID)
				{
					if (PAGE_ID == 'DIALOG' + userId)
						this.messageCountArray[userId] = 0;

					this.updateCounters();
				}, this)});
			}
			else if (command == 'notify')
			{
				lastId = parseInt(params.id);
				if (this.notifyLastId < lastId)
					this.notifyLastId = lastId;

				this.counterNotifications++;
				this.updateCounters();

				if (!this.notifyLoadFlag)
				{
					clearTimeout(this.notifyTimeout);
					this.notifyTimeout = setTimeout(BX.delegate(function ()
					{
						this.notifyLoadFlag = true;
						app.refreshPanelPage('notifications');
					}, this), 600);
				}
			}
		}, this));

		BXMobileApp.addCustomEvent("onNotificationsLastId", BX.delegate(function (lastId)
		{
			this.notifyLoadFlag = false;
			lastId = parseInt(lastId);
			if (this.notifyLastId < lastId)
				this.notifyLastId = lastId;
		}, this));

		BX.addCustomEvent("onDialogOpen", BX.delegate(function (params)
		{
			this.messageCountArray[params.id] = 0;
			this.updateCounters();
		}, this));


		app.setPanelPages({
			'messages_page': (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/im/index.php?NEW",
			'messages_open_empty': true,
			'notifications_page': (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/im/notify.php",
			'notifications_open_empty': true
		});

		this.updateStateLight();
		this.initAudio();
	}

	BX.MessengerMobileLegacy.prototype.openSearch = function ()
	{
		app.openTable({
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/index.php?mobile_action=get_user_list',
			callback: function (data)
			{
				if (!(data && data.a_users && data.a_users[0]))
					return;

				var user = data.a_users[0];
				BX.MobileTools.openChat(user['ID'], {
					name: user['NAME'],
					description: user['WORK_POSITION'],
					avatar: user['IMAGE']
				});
			},
			set_focus_to_search: true,
			markmode: true,
			multiple: false,
			return_full_mode: true,
			modal: true,
			alphabet_index: true,
			outsection: false
		});
	}

	BX.MessengerMobileLegacy.prototype.initAudio = function ()
	{
		return;

		if (this.audio.ready)
			return;

		this.audio.ready = true;

		BX.ready(BX.delegate(function ()
		{
			var divAudio = BX.create("div", { attrs: {style: "display: none"}, children: [
				this.audio.reminder = BX.create("audio", { props: {style: { display: "none" }}, children: [
					BX.create("source", { attrs: { src: "/bitrix/js/im/audio/reminder.ogg", type: "audio/ogg; codecs=vorbis" }}),
					BX.create("source", { attrs: { src: "/bitrix/js/im/audio/reminder.mp3", type: "audio/mpeg" }})
				]}),
				this.audio.newMessage = BX.create("audio", { props: {style: { display: "none" }}, children: [
					BX.create("source", { attrs: { src: "/bitrix/js/im/audio/new-message-2.ogg", type: "audio/ogg; codecs=vorbis" }}),
					BX.create("source", { attrs: { src: "/bitrix/js/im/audio/new-message-2.mp3", type: "audio/mpeg" }})
				]}),
				this.audio.send = BX.create("audio", { props: {style: { display: "none" }}, children: [
					BX.create("source", { attrs: { src: "/bitrix/js/im/audio/send.ogg", type: "audio/ogg; codecs=vorbis" }}),
					BX.create("source", { attrs: { src: "/bitrix/js/im/audio/send.mp3", type: "audio/mpeg" }})
				]})
			]});
			document.body.insertBefore(divAudio, document.body.firstChild);
		}, this));
	}


	BX.MessengerMobileLegacy.prototype.updateCounters = function ()
	{
		clearTimeout(this.timeoutUpdateCounters);
		this.timeoutUpdateCounters = setTimeout(BX.delegate(function ()
		{
			this.counterMessages = 0;
			for (var i in this.messageCountArray)
				this.counterMessages += parseInt(this.messageCountArray[i]);

			app.setBadge(parseInt(this.counterMessages + this.counterNotifications));
			app.setCounters({
				'messages': this.counterMessages,
				'notifications': this.counterNotifications
			});
		}, this), 500);
	}
	BX.MessengerMobileLegacy.prototype.sendMessage = function (recipientId, messageText)
	{
		var userIsChat = false;
		if (recipientId.toString().substr(0, 4) == 'chat')
		{
			userIsChat = true;
			var chatId = recipientId.toString().substr(4);
			if (parseInt(chatId) <= 0)
				return false;
		}
		else
		{
			if (parseInt(recipientId) <= 0)
				return false;
		}

		if (typeof(messageText) != 'undefined')
		{
			messageText = BX.util.trim(messageText + '');
		}
		else
		{
			var messengerTextarea = BX('send-message-input');
			messageText = BX.util.trim(messengerTextarea.value);
			messengerTextarea.value = '';
			messengerTextarea.blur();
		}

		if (messageText.length == 0)
			return false;

		var messageTmpIndex = this.messageTmpIndex;
		this.drawMessage({
			'id': 'temp' + messageTmpIndex,
			'senderId': BX.message('USER_ID'),
			'recipientId': recipientId,
			'date': BX.message('IM_MESSENGER_DELIVERED'),
			'text': BX.MessengerMobileLegacy.prepareText(messageText, true, true)
		});
		if (!userIsChat)
			this.endSendWriting(recipientId);
		this.messageTmpIndex++;

		this.sendMessageAjax(messageTmpIndex, recipientId, messageText, userIsChat);

		return false;
	}

	BX.MessengerMobileLegacy.prototype.sendMessageAjax = function (messageTmpIndex, recipientId, messageText, sendMessageToChat)
	{
		BX.addClass(BX('messagetemp' + messageTmpIndex, true), 'im-block-load');
		BX('messagetemp' + messageTmpIndex, true).appendChild(BX.create('div', {props: { className: "im-message-loading" }}));

		this.sendMessageFlag = true;
		BX.ajax({
			url: '/mobile/ajax.php?mobile_action=im',
			method: 'POST',
			dataType: 'json',
			data: {'IM_SEND_MESSAGE': 'Y', 'CHAT': sendMessageToChat ? 'Y' : 'N', 'ID': 'temp' + messageTmpIndex, 'RECIPIENT_ID': recipientId, 'MESSAGE': messageText, 'TAB': recipientId, 'MOBILE': 'Y', 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function (data)
			{
				if (typeof(data) == 'undefined')
					data = {'ERROR': BX.message('IM_MESSENGER_NOT_DELIVERED')};

				this.sendMessageFlag = false;

				BX.removeClass(BX('messagetemp' + messageTmpIndex, true), 'im-block-load');
				if (data.ERROR.length == 0)
				{
					this.sendAjaxTry = 0;
					BXMobileApp.onCustomEvent('onMessageAdd', {'userId': recipientId, 'text': data.SEND_MESSAGE}, true);

					var textElement = BX.findChild(BX('messagetemp' + messageTmpIndex), {className: "im-block-text"}, true);
					if (textElement)
						textElement.innerHTML = data.SEND_MESSAGE;

					var lastMessageElementDate = BX.findChild(BX('messagetemp' + messageTmpIndex), {className: "im-block-info"}, true);
					if (lastMessageElementDate)
					{
						lastMessageElementDate.innerHTML = "";
						lastMessageElementDate.innerHTML = data.SEND_DATE_FORMAT;
					}

					BX('messagetemp' + messageTmpIndex).setAttribute('id', 'message' + data.ID)
				}
				else if (data.ERROR == 'AUTHORIZE_ERROR' && this.sendAjaxTry <= 5)
				{
					this.sendAjaxTry++;
					app.BasicAuth({
						success: BX.delegate(function ()
						{
							this.sendMessageAjax(messageTmpIndex, recipientId, messageText, sendMessageToChat);
						}, this),
						failture: BX.delegate(function ()
						{
							setTimeout(BX.delegate(function ()
							{
								this.sendMessageAjax(messageTmpIndex, recipientId, messageText, sendMessageToChat);
							}, this), 3000);
						}, this)
					});
				}
				else if (data.ERROR == 'SESSION_ERROR' && this.sendAjaxTry <= 3)
				{
					this.sendAjaxTry++;
					BX.message({'bitrix_sessid': data.BITRIX_SESSID});
					setTimeout(BX.delegate(function ()
					{
						this.sendMessageAjax(messageTmpIndex, recipientId, messageText, sendMessageToChat);
					}, this), 1000);
				}
				else
				{
					BX.addClass(BX('messagetemp' + messageTmpIndex, true), 'im-undelivered');
					BX('messagetemp' + messageTmpIndex, true).appendChild(BX.create('div', {props: { className: "im-undelivered-message-icon" }}));

					var reason = data.ERROR;
					if (data.ERROR == 'SESSION_ERROR' || data.ERROR == 'AUTHORIZE_ERROR' || data.ERROR == 'UNKNOWN_ERROR' || data.ERROR == 'IM_MODULE_NOT_INSTALLED')
						reason = BX.message('IM_MESSENGER_NOT_DELIVERED');

					var node = BX.create('div', {props: { className: "im-undelivered-btn-block" }, children: [
						BX.create('input', {
							attrs: {type: 'button', value: BX.message('IM_MESSENGER_ND_RETRY')},
							props: { className: "im-undelivered-btn" },
							events: {
								touchend: BX.delegate(function ()
								{
									BX.proxy_context.classList.remove('im-undelivered-btn-press');
									BX.removeClass(BX('messagetemp' + messageTmpIndex, true), 'im-undelivered');
									BX.remove(BX.proxy_context.parentNode);
									var undeliveredMessageIcon = BX.findChild(BX('messagetemp' + messageTmpIndex, true), {className: "im-undelivered-message-icon"}, true);
									if (undeliveredMessageIcon)
									{
										BX.remove(undeliveredMessageIcon);
									}
									this.sendAjaxTry = 0;
									this.sendMessageAjax(messageTmpIndex, recipientId, messageText, sendMessageToChat);
								}, this),
								touchstart: function ()
								{
									this.classList.add('im-undelivered-btn-press');
								}
							}
						}),
						BX.create('span', {props: { className: "im-undelivered-text" }, html: reason})
					]});
					BX("im-blocks", true).insertBefore(node, BX('messagetemp' + messageTmpIndex, true).nextSibling);

					this.sendAjaxTry = 0;
					var lastMessageElementDate = BX.findChild(BX('messagetemp' + messageTmpIndex, true), {className: "im-block-info"}, true);
					if (lastMessageElementDate)
					{
						lastMessageElementDate.innerHTML = "";
						lastMessageElementDate.innerHTML = BX.MessengerMobileLegacy.formatDate(BX.MessengerMobileLegacy.getNowDate());
					}
				}
			}, this),
			onfailure: BX.delegate(function ()
			{
				BX.addClass(BX('messagetemp' + messageTmpIndex, true), 'im-undelivered');
				BX('messagetemp' + messageTmpIndex, true).appendChild(BX.create('div', {props: { className: "im-undelivered-message-icon" }}));

				var reason = BX.message('IM_MESSENGER_NOT_DELIVERED');

				var node = BX.create('div', {props: { className: "im-undelivered-btn-block" }, children: [
					BX.create('input', {
						attrs: {type: 'button', value: BX.message('IM_MESSENGER_ND_RETRY')},
						props: { className: "im-undelivered-btn" },
						events: {
							touchend: BX.delegate(function ()
							{
								BX.proxy_context.classList.remove('im-undelivered-btn-press');
								BX.removeClass(BX('messagetemp' + messageTmpIndex, true), 'im-undelivered');
								BX.remove(BX.proxy_context.parentNode);
								var undeliveredMessageIcon = BX.findChild(BX('messagetemp' + messageTmpIndex, true), {className: "im-undelivered-message-icon"}, true);
								if (undeliveredMessageIcon)
								{
									BX.remove(undeliveredMessageIcon);
								}
								this.sendAjaxTry = 0;
								this.sendMessageAjax(messageTmpIndex, recipientId, messageText, sendMessageToChat);
							}, this),
							touchstart: function ()
							{
								this.classList.add('im-undelivered-btn-press');
							}
						}
					}),
					BX.create('span', {props: { className: "im-undelivered-text" }, html: reason})
				]});
				BX("im-blocks", true).insertBefore(node, BX('messagetemp' + messageTmpIndex, true).nextSibling);

				this.sendAjaxTry = 0;
				var lastMessageElementDate = BX.findChild(BX('messagetemp' + messageTmpIndex, true), {className: "im-block-info"}, true);
				if (lastMessageElementDate)
				{
					lastMessageElementDate.innerHTML = "";
					lastMessageElementDate.innerHTML = BX.MessengerMobileLegacy.formatDate(BX.MessengerMobileLegacy.getNowDate());
				}
			}, this)
		});
	}


	BX.MessengerMobileLegacy.prototype.drawMessage = function (message, appendTop)
	{
		appendTop = appendTop == true ? true : false;
		if (typeof(USER) == undefined)
		{
			alert('ERROR: Array USER undefined!');
			return false;
		}

		message.text = message.text.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/ig, '<a href="/mobile/users/?user_id=$1&FROM_DIALOG=Y">$2</a>');
		message.text = message.text.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/ig, '$2');

		var files = '';
		if (message.params && message.params.FILE_ID)
		{
			for (var i = 0; i < message.params.FILE_ID.length; i++)
			{
				var file = message.files[message.params.FILE_ID[i]];
				if (!file)
					continue;

				if (file['urlPreview'] && file['urlShow'])
				{
					file['name'] = '<a href="'+file['urlShow']+'">'+file['name']+'</a>';
				}
				files = files+'<div id="im-file-'+file['id']+'">'+file['name']+'</div>';
			}
			if (files)
			{
				files = '<div class="im-block-text-files"><div class="im-block-text-title">'+BX.message('IM_FILES')+':</div>'+files+'</div>';
			}
		}

		if (message.senderId == 0 || message.system == 'Y')
		{
			var node = BX.create('div', {
				attrs: {
					id: 'message' + message.id
				},
				props: { className: "im-block im-block-in im-block-system" },
				html: '<div class="im-block-cont">\
							<div class="im-block-title"></div>\
							<div class="im-block-text" id="messageText'+message.id+'">' + message.text + '</div>\
							<div class="im-block-info">' + (parseInt(message.date) > 0 ? BX.MessengerMobileLegacy.formatDate(message.date) : message.date) + '</div>\
						</div>'
			});
		}
		else
		{
			var node = BX.create('div', {
				attrs: {
					id: 'message' + message.id
				},
				props: { className: "im-block im-block-" + (message.senderId == BX.message('USER_ID') ? 'out' : 'in') },
				html: '<div class="ml-avatar"><div class="ml-avatar-sub" style="background-image:url(\'' + (USERS[message.senderId]['avatar']) + '\'); background-size:cover;"></div></div>\
							<div class="im-block-cont">\
							<div class="im-block-title">' + USERS[message.senderId]['name'] + '</div>\
							<div class="im-block-text" id="messageText'+message.id+'">' + message.text + '</div>\
							' + files + '\
							<div class="im-block-info">' + (parseInt(message.date) > 0 ? BX.MessengerMobileLegacy.formatDate(message.date) : message.date) + '</div>\
						</div>'
			});
		}
		var emptyBlock = BX('im-block-empty');
		if (emptyBlock != null)
		{
			BX.remove(emptyBlock);
		}
		var container = BX("im-blocks", true);
		if (appendTop)
		{
			if (container.firstChild)
				container.insertBefore(node, container.firstChild);
			else
				container.insertBefore(node, this.writing);
		}
		else
		{
			container.insertBefore(node, this.writing);

			clearTimeout(this.timeoutAnimation);
			this.timeoutAnimation = setTimeout(function ()
			{
				clearInterval(this.intervalAnimation);
				if (window.platform == 'android')
				{
					var scrollPositionMax = document.body.scrollHeight - document.body.offsetHeight;
				}
				else
				{
					var scrollPositionOriginal = document.body.scrollTop;
					document.body.scrollTop = document.body.offsetHeight;
					var scrollPositionMax = document.body.scrollTop;
					document.body.scrollTop = scrollPositionOriginal;
				}

				this.intervalAnimation = BitrixAnimation.animate({
					duration: 1000,
					start: { scroll: document.body.scrollTop },
					finish: { scroll: scrollPositionMax },
					transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
					step: function (state)
					{
						document.body.scrollTop = state.scroll;
					},
					complete: function ()
					{
					}
				});
			}, 200);
		}

		if (BX.hasClass(this.writing, 'im-block-writing-read'))
		{
			DIALOG_LAST_READ = '';
			BX.removeClass(this.writing, 'im-block-writing-read');
			this.writing.innerHTML = DIALOG_LAST_READ;
		}

		return node.offsetHeight + 20;
	}


	BX.MessengerMobileLegacy.prototype.readMessage = function (userId, lastId)
	{
		lastId = parseInt(lastId) > 0 ? parseInt(lastId) : 'N';

		clearTimeout(this.timeoutReadMessage);
		this.timeoutReadMessage = setTimeout(function ()
		{
			BX.ajax({
				url: '/mobile/ajax.php?mobile_action=im',
				method: 'POST',
				dataType: 'json',
				data: {'IM_READ_MESSAGE': 'Y', 'USER_ID': userId, 'LAST_ID': lastId, 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
				onsuccess: BX.delegate(function (data)
				{
					if (data.ERROR.length == 0)
					{
						this.sendAjaxTry = 0;
					}
					else if (data.ERROR == 'AUTHORIZE_ERROR' && this.sendAjaxTry <= 3)
					{
						this.sendAjaxTry++;
						BXMobileApp.onCustomEvent('onImError', {error: data.ERROR}, true);

						setTimeout(BX.delegate(function ()
						{
							this.readMessage(userId, lastId);
						}, this), 2000);
					}
					else if (data.ERROR == 'SESSION_ERROR' && this.sendAjaxTry <= 3)
					{
						this.sendAjaxTry++;
						BX.message({'bitrix_sessid': data.BITRIX_SESSID});
						setTimeout(BX.delegate(function ()
						{
							this.readMessage(userId, lastId);
						}, this), 1000);
					}
					else
					{
						this.sendAjaxTry = 0;
					}
				}, this),
				onfailure: BX.delegate(function (data)
				{
					this.sendAjaxTry = 0;
				}, this)
			});
		}, 500);
	}

	BX.MessengerMobileLegacy.prototype.confirmRequest = function (params)
	{
		BX.ajax({
			url: '/mobile/ajax.php?mobile_action=im',
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'IM_NOTIFY_CONFIRM': 'Y', 'NOTIFY_ID': params.notifyId, 'NOTIFY_VALUE': params.notifyValue, 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()}
		});

		return false;
	};

	BX.MessengerMobileLegacy.prototype.updateStateLight = function ()
	{
		clearTimeout(this.timeoutUpdateStateLight);
		this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
		{
			BX.ajax({
				url: '/mobile/ajax.php?mobile_action=im',
				method: 'POST',
				dataType: 'json',
				timeout: 20,
				data: {'IM_UPDATE_STATE_LIGHT': 'Y', 'MOBILE': 'Y', 'SITE_ID': BX.message('SITE_ID'), 'NOTIFY': 'Y', 'MESSAGE': 'Y', 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
				onsuccess: BX.delegate(function (data)
				{
					if (data.ERROR.length == 0)
					{
						if (BX.PULL && data.PULL_CONFIG)
						{
							BX.PULL.updateChannelID({
								'METHOD': data.PULL_CONFIG.METHOD,
								'CHANNEL_ID': data.PULL_CONFIG.CHANNEL_ID,
								'CHANNEL_DT': data.PULL_CONFIG.CHANNEL_DT,
								'PATH': data.PULL_CONFIG.PATH,
								'LAST_ID': data.PULL_CONFIG.LAST_ID,
								'PATH_WS': data.PULL_CONFIG.PATH_WS
							});
						}

						if (data.COUNTER_MESSAGES)
							this.counterMessages = parseInt(data.COUNTER_MESSAGES);
						if (data.COUNTER_NOTIFICATIONS)
							this.counterNotifications = parseInt(data.COUNTER_NOTIFICATIONS);
						if (data.NOTIFY_LAST_ID)
							this.notifyLastId = parseInt(data.NOTIFY_LAST_ID);

						if (this.counterMessages > 0 && data.COUNTER_UNREAD_MESSAGES && typeof(data.COUNTER_UNREAD_MESSAGES) == 'object')
						{
							this.counterMessages = 0;
							this.messageCountArray = {};
							for (var i in data.COUNTER_UNREAD_MESSAGES)
							{
								this.counterMessages += data.COUNTER_UNREAD_MESSAGES[i].MESSAGE.counter;
								this.messageCountArray[i] = data.COUNTER_UNREAD_MESSAGES[i].MESSAGE.counter;
							}
							BXMobileApp.onCustomEvent('onUpdateUserCounters', data.COUNTER_UNREAD_MESSAGES, true);
						}
						else
						{
							this.messageCountArray = {};
							BXMobileApp.onCustomEvent('onUpdateUserCounters', data.COUNTER_UNREAD_MESSAGES, true);
						}
						this.updateCounters();

						if (data.COUNTERS && typeof(data.COUNTERS) == 'object')
							BXMobileApp.onCustomEvent('onUpdateSocnetCounters', data.COUNTERS, true);

						if (this.counterNotifications > 0 && !this.notifyLoadFlag)
						{
							clearTimeout(this.notifyTimeout);
							this.notifyTimeout = setTimeout(BX.delegate(function ()
							{
								this.notifyLoadFlag = true;
								app.refreshPanelPage('notifications');
							}, this), 600);
						}

						this.sendAjaxTry = 0;

						clearTimeout(this.timeoutUpdateStateLight);
						this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
						{
							this.updateStateLight();
						}, this), 80000);
					}
					else if (data.ERROR == 'AUTHORIZE_ERROR' && this.sendAjaxTry <= 3)
					{
						this.sendAjaxTry++;
						BXMobileApp.onCustomEvent('onImError', {error: data.ERROR}, true);

						clearTimeout(this.timeoutUpdateStateLight);
						this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
						{
							this.updateStateLight();
						}, this), 2000);
					}
					else if (data.ERROR == 'SESSION_ERROR' && this.sendAjaxTry <= 3)
					{
						this.sendAjaxTry++;
						BX.message({'bitrix_sessid': data.BITRIX_SESSID});

						clearTimeout(this.timeoutUpdateStateLight);
						this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
						{
							this.updateStateLight();
						}, this), 1000);
					}
					else
					{
						this.sendAjaxTry = 0;
					}
				}, this),
				onfailure: BX.delegate(function (data)
				{
					this.sendAjaxTry = 0;
				}, this)
			});
		}, this), 300);
	}

	BX.MessengerMobileLegacy.prototype.getHistory = function (userId)
	{
		this.historyPage = Math.floor(this.historyMessageCount / 20) + 1;
		BX.ajax({
			url: '/mobile/ajax.php?mobile_action=im',
			method: 'POST',
			dataType: 'json',
			data: {'IM_HISTORY_LOAD_MORE': 'Y', 'USER_ID': userId, 'PAGE_ID': this.historyPage, 'MOBILE': 'Y', 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function (data)
			{
				var count = 0;
				var height = 0;
				var heightFirst = 0;
				var arHistory = [];
				var arHistorySort = [];
				for (var i in data.MESSAGE)
				{
					if (this.historyMessage[data.MESSAGE[i].id])
						continue;

					data.MESSAGE[i].date = parseInt(data.MESSAGE[i].date) + parseInt(BX.message('USER_TZ_OFFSET'));
					data.MESSAGE[i].text = BX.MessengerMobileLegacy.prepareText(data.MESSAGE[i].text, false, true);
					arHistory.push(data.MESSAGE[i]);
					arHistorySort.push(data.MESSAGE[i]);
				}
				arHistorySort.sort(function (i, ii)
				{
					i = i.id;
					ii = ii.id;
					if (i < ii)
					{
						return 1;
					} else if (i > ii)
					{
						return -1;
					} else
					{
						return 0;
					}
				});
				for (var i = 0; i < arHistorySort.length; i++)
				{
					height += this.drawMessage(arHistorySort[i], true);

					if (heightFirst == 0)
						heightFirst = height;

					this.historyMessage[arHistorySort[i].id] = true;
					this.historyMessageCount++;
					count++;
				}

				app.pullDownLoadingStop();
				document.body.scrollTop = height + 20;

				clearInterval(this.intervalAnimation);
				this.intervalAnimation = BitrixAnimation.animate({
					duration: 1500,
					start: { scroll: document.body.scrollTop },
					finish: { scroll: height - heightFirst},
					transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
					step: function (state)
					{
						document.body.scrollTop = state.scroll;
					},
					complete: function ()
					{
					}
				});

				if (count < 20)
				{
					app.pullDown({'enable': false});
					return;
				}
			}, this),
			onfailure: function (data)
			{
				app.pullDownLoadingStop();
			}
		});
	}

	BX.MessengerMobileLegacy.getNowDate = function (today)
	{
		var currentDate = (new Date);
		if (today == true)
			currentDate = (new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), 0, 0, 0));

		return Math.round((+currentDate / 1000)) + parseInt(BX.message("SERVER_TZ_OFFSET")) + parseInt(BX.message("USER_TZ_OFFSET"));
	};

	BX.MessengerMobileLegacy.formatDate = function (timestamp)
	{
		var format = [
			["tommorow", BX.message("IM_FORMAT_DATETIME_TOMMOROW")],
			["today", BX.message("IM_FORMAT_DATETIME_TODAY")],
			["yesterday", BX.message("IM_FORMAT_DATETIME_YESTERDAY")],
			["", BX.date.convertBitrixFormat(BX.message("IM_FORMAT_DATETIME"))]
		];
		return BX.date.format(format, parseInt(timestamp) + parseInt(BX.message("SERVER_TZ_OFFSET")), BX.MessengerMobileLegacy.getNowDate(), true);
	}

	BX.MessengerMobileLegacy.prepareText = function (text, prepare, quote)
	{
		prepare = prepare == true ? true : false;
		quote = quote == true ? true : false;

		text = BX.util.trim(text);
		if (prepare)
			text = BX.util.htmlspecialchars(text);

		if (quote)
		{
			text = text.replace(/------------------------------------------------------<br \/>(.*?)\[(.*?)\]<br \/>(.*?)------------------------------------------------------(<br \/>)?/g, "<div class=\"bx-messenger-content-quote\"><span class=\"bx-messenger-content-quote-icon\"></span><div class=\"bx-messenger-content-quote-wrap\"><div class=\"bx-messenger-content-quote-name\">$1 <span class=\"bx-messenger-content-quote-time\">$2</span></div>$3</div></div>");
			text = text.replace(/------------------------------------------------------<br \/>(.*?)<br \/>------------------------------------------------------(<br \/>)?/g, "<div class=\"bx-messenger-content-quote\"><span class=\"bx-messenger-content-quote-icon\"></span><div class=\"bx-messenger-content-quote-wrap\">$1</div></div>");
		}
		if (prepare)
			text = text.replace(/\n/gi, '<br />');

		text = text.replace(/\t/gi, '&nbsp;&nbsp;&nbsp;&nbsp;');

		return text;
	}
	BX.MessengerMobileLegacy.prototype.drawReadMessage = function (userId, messageId, date)
	{
		DIALOG_LAST_READ = BX.message('IM_MESSENGER_READED').replace('#DATE#', BX.MessengerMobileLegacy.formatDate(date));
		if (!this.writingList[userId] && DIALOG_ID == userId)
		{
			this.showReadMessage();
		}
	}
	BX.MessengerMobileLegacy.prototype.showReadMessage = function ()
	{
		if (DIALOG_LAST_READ == '')
		{
			BX.removeClass(this.writing, 'im-block-writing-read');
		}
		else
		{
			BX.addClass(this.writing, 'im-block-writing-read');
		}

		this.writing.innerHTML = DIALOG_LAST_READ;
	}
	BX.MessengerMobileLegacy.prototype.startWriting = function (userId)
	{
		this.writingList[userId] = true;
		this.drawWriting(userId);
		clearTimeout(this.writingListTimeout[userId]);
		this.writingListTimeout[userId] = setTimeout(BX.delegate(function ()
		{
			this.endWriting(userId);
		}, this), 30000);
	}
	BX.MessengerMobileLegacy.prototype.drawWriting = function (userId)
	{
		if (this.writingList[userId] && DIALOG_ID == userId)
		{
			BX.removeClass(this.writing, 'im-block-writing-read');
			BX.addClass(this.writing, 'im-block-writing-write');
			this.writing.innerHTML = BX.message('IM_MESSENGER_WRITING').replace('#USER_NAME#', '<b>' + USERS[userId].name + '</b>');
		}
		else if (!this.writingList[userId] && DIALOG_ID == userId)
		{
			BX.removeClass(this.writing, 'im-block-writing-write');
			this.writing.innerHTML = '';
			this.showReadMessage();
		}
	}
	BX.MessengerMobileLegacy.prototype.endWriting = function (userId, fast)
	{
		fast = fast == true ? true : false;

		clearTimeout(this.writingListTimeout[userId]);
		this.writingList[userId] = false;
		this.drawWriting(userId);

		if (fast)
		{
			BX.removeClass(this.writing, 'im-block-writing-write');
			this.showReadMessage();
		}
	}
	BX.MessengerMobileLegacy.prototype.sendWriting = function (userId)
	{
		if (parseInt(userId) > 0 && !this.writingSendList[userId])
		{
			clearTimeout(this.writingSendListTimeout[userId]);
			this.writingSendList[userId] = true;
			BX.ajax({
				url: '/mobile/ajax.php?mobile_action=im',
				method: 'POST',
				dataType: 'json',
				data: {'IM_START_WRITING': 'Y', 'DIALOG_ID': DIALOG_ID, 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()}
			});
			this.writingSendListTimeout[userId] = setTimeout(BX.delegate(function ()
			{
				this.endSendWriting(userId);
			}, this), 30000);
		}
	}
	BX.MessengerMobileLegacy.prototype.endSendWriting = function (userId)
	{
		if (parseInt(userId) <= 0)
			return false;

		clearTimeout(this.writingSendListTimeout[userId]);
		this.writingSendList[userId] = false;
	}

	BX.MessengerMobileLegacy.prototype.leaveFromChat = function (chatId)
	{
		BX.ajax({
			url: '/mobile/ajax.php?mobile_action=im',
			method: 'POST',
			dataType: 'json',
			timeout: 60,
			data: {'IM_CHAT_LEAVE': 'Y', 'CHAT_ID': chatId, 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: function (data)
			{
				if (data.ERROR == '')
				{
					app.closeController();
				}
			}
		});
	}

	BX.MessengerMobileLegacy.prototype.autoScroll = function ()
	{
		if (document.body.scrollHeight <= window.innerHeight)
			return false;

		clearInterval(this.intervalAnimation);
		this.intervalAnimation = BitrixAnimation.animate({
			duration: 1000,
			start: { scroll: document.body.scrollTop },
			finish: { scroll: document.body.scrollHeight  },
			transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
			step: function (state)
			{
				document.body.scrollTop = state.scroll;
			},
			complete: function ()
			{
			}
		});
	}

	BX.ImLegacy = new BX.MessengerMobileLegacy;
	window.BX.ImLegacy = BX.ImLegacy;
})();