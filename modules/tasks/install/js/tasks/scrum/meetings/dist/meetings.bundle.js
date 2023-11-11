/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_loader,main_popup,ui_popupcomponentsmaker,main_core_events,main_core,ui_dialogs_messagebox) {
	'use strict';

	function _classStaticPrivateFieldSpecSet(receiver, classConstructor, descriptor, value) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
	function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	var Culture = /*#__PURE__*/function () {
	  function Culture() {
	    babelHelpers.classCallCheck(this, Culture);
	  }
	  babelHelpers.createClass(Culture, [{
	    key: "setData",
	    value: function setData(data) {
	      this.data = data;
	    }
	  }, {
	    key: "getDayMonthFormat",
	    value: function getDayMonthFormat() {
	      return this.data.dayMonthFormat;
	    }
	  }, {
	    key: "getLongDateFormat",
	    value: function getLongDateFormat() {
	      return this.data.longDateFormat;
	    }
	  }, {
	    key: "getShortTimeFormat",
	    value: function getShortTimeFormat() {
	      return this.data.shortTimeFormat;
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (!_classStaticPrivateFieldSpecGet(Culture, Culture, _instance)) {
	        _classStaticPrivateFieldSpecSet(Culture, Culture, _instance, new Culture());
	      }
	      return _classStaticPrivateFieldSpecGet(Culture, Culture, _instance);
	    }
	  }]);
	  return Culture;
	}();
	var _instance = {
	  writable: true,
	  value: void 0
	};

	var _templateObject, _templateObject2, _templateObject3;
	var CreatedEvent = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(CreatedEvent, _EventEmitter);
	  function CreatedEvent() {
	    var _this;
	    babelHelpers.classCallCheck(this, CreatedEvent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CreatedEvent).call(this));
	    _this.setEventNamespace('BX.Tasks.Scrum.CreatedEvent');
	    _this.node = null;
	    return _this;
	  }
	  babelHelpers.createClass(CreatedEvent, [{
	    key: "render",
	    value: function render(event) {
	      if (event === null) {
	        return '';
	      }
	      event.color = event.color === '' ? '#86b100' : event.color;
	      var colorBorder = this.convertHexToRGBA(event.color, 0.5);
	      var colorBackground = this.convertHexToRGBA(event.color, 0.15);
	      this.node = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div\n\t\t\t\tclass=\"tasks-scrum__widget-meetings--timetable-content\"\n\t\t\t\tstyle=\"background: ", "; --meetings-border-color: ", ";\"\n\t\t\t>\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-navigation\">\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-time\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-name\">", "</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), colorBackground, colorBorder, this.getFormattedTime(event.from, event.to), this.renderVideoCallButton(), main_core.Text.encode(event.name), event.repeatable ? this.renderRepetition() : '');
	      main_core.Event.bind(this.node, 'click', this.openViewCalendarSidePanel.bind(this, event));
	      return this.node;
	    }
	  }, {
	    key: "openViewCalendarSidePanel",
	    value: function openViewCalendarSidePanel(event) {
	      new window.top.BX.Calendar.SliderLoader(event.id, {
	        entryDateFrom: new Date(event.from * 1000)
	      }).show();
	      this.emit('showView');
	    }
	  }, {
	    key: "renderVideoCallButton",
	    value: function renderVideoCallButton() {
	      return '';
	      var videoCallUiClasses = 'ui-btn-split ui-btn-light-border ui-btn-xs ui-btn-light ui-btn-no-caps';
	      return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-video-call ", "\">\n\t\t\t\t<button class=\"ui-btn-main\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t\t<div class=\"ui-btn-menu\"></div>\n\t\t\t</div>\n\t\t"])), videoCallUiClasses, main_core.Loc.getMessage('TSM_VIDEO_CALL_BUTTON'));
	    }
	  }, {
	    key: "renderRepetition",
	    value: function renderRepetition() {
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-repetition\">\n\t\t\t\t<i class=\"tasks-scrum__widget-meetings--timetable-repetition-icon\"></i>\n\t\t\t\t<span>", "</span>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TSM_REPETITION_TITLE'));
	    }
	  }, {
	    key: "convertHexToRGBA",
	    value: function convertHexToRGBA(hexCode, opacity) {
	      var hex = hexCode.replace('#', '');
	      if (hex.length === 3) {
	        hex = "".concat(hex[0]).concat(hex[0]).concat(hex[1]).concat(hex[1]).concat(hex[2]).concat(hex[2]);
	      }
	      var r = parseInt(hex.substring(0, 2), 16);
	      var g = parseInt(hex.substring(2, 4), 16);
	      var b = parseInt(hex.substring(4, 6), 16);
	      return "rgba(".concat(r, ",").concat(g, ",").concat(b, ",").concat(opacity, ")");
	    }
	  }, {
	    key: "getFormattedTime",
	    value: function getFormattedTime(from, to) {
	      /* eslint-disable */
	      return "".concat(BX.date.format(Culture.getInstance().getShortTimeFormat(), from, null, true), " - ").concat(BX.date.format(Culture.getInstance().getShortTimeFormat(), to, null, true));
	      /* eslint-enable */
	    }
	  }]);
	  return CreatedEvent;
	}(main_core_events.EventEmitter);

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4, _templateObject5, _templateObject6;
	var ListEvents = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(ListEvents, _EventEmitter);
	  function ListEvents() {
	    var _this;
	    babelHelpers.classCallCheck(this, ListEvents);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ListEvents).call(this));
	    _this.setEventNamespace('BX.Tasks.Scrum.ListEvents');
	    _this.listIsShown = false;
	    _this.todayEvent = null;
	    _this.node = null;
	    _this.listNode = null;
	    _this.buttonNode = null;
	    return _this;
	  }
	  babelHelpers.createClass(ListEvents, [{
	    key: "setTodayEvent",
	    value: function setTodayEvent(todayEvent) {
	      this.todayEvent = todayEvent;
	    }
	  }, {
	    key: "existsTodayEvent",
	    value: function existsTodayEvent() {
	      return this.todayEvent !== null;
	    }
	  }, {
	    key: "render",
	    value: function render(listEvents) {
	      var visibility = listEvents.length === 0 ? '' : '--visible';
	      this.listIsShown = listEvents.length > 0 && !this.existsTodayEvent();
	      this.node = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--plan-content  ", "\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), visibility, this.renderList(listEvents), this.renderButton());
	      return this.node;
	    }
	  }, {
	    key: "renderList",
	    value: function renderList(listEvents) {
	      var _this2 = this;
	      var list = new Map();
	      var groupedList = new Map();
	      var sort = new Map();
	      listEvents.forEach(function (event) {
	        var key = _this2.getFormattedDate(event.from);
	        var group = groupedList.has(key) ? groupedList.get(key) : new Set();
	        group.add(event);
	        sort.set(key, event.from);
	        groupedList.set(key, group);
	      });
	      var sortedMap = new Map(babelHelpers.toConsumableArray(sort.entries()).sort(function (first, second) {
	        return first[1] - second[1];
	      }));
	      babelHelpers.toConsumableArray(sortedMap.keys()).forEach(function (key) {
	        list.set(key, groupedList.get(key));
	      });
	      var visibility = this.existsTodayEvent() ? '' : '--visible';
	      this.listNode = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--plan ", "\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t\t\n\t\t"])), visibility, this.renderSeparator(), this.renderEvents(list));
	      main_core.Event.bind(this.listNode, 'transitionend', this.onTransitionEnd.bind(this));
	      return this.listNode;
	    }
	  }, {
	    key: "renderSeparator",
	    value: function renderSeparator() {
	      if (!this.existsTodayEvent()) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--header-separator\">\n\t\t\t\t<span>", "</span>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TSM_PLANNING_EVENTS_TITLE'));
	    }
	  }, {
	    key: "renderEvents",
	    value: function renderEvents(list) {
	      var _this3 = this;
	      if (list.size === 0) {
	        return '';
	      }
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-wrapper\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.toConsumableArray(list.values()).map(function (group, index) {
	        return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-day\">\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-title\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t"])), main_core.Text.encode(babelHelpers.toConsumableArray(list.keys())[index]), babelHelpers.toConsumableArray(group.values()).map(function (event) {
	          var createdEvent = new CreatedEvent();
	          createdEvent.subscribe('showView', function () {
	            return _this3.emit('showView');
	          });
	          return createdEvent.render(event);
	        }));
	      }));
	    }
	  }, {
	    key: "renderButton",
	    value: function renderButton() {
	      var _this4 = this;
	      this.buttonNode = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings-btn-box-center \">\n\t\t\t\t<button\n\t\t\t\t\tclass=\"tasks-scrum__widget-meetings--plan-btn ui-qr-popupcomponentmaker__btn --border --visible\"\n\t\t\t\t\tdata-role=\"toggle-list-events\"\n\t\t\t\t>\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t"])), this.getButtonText());
	      main_core.Event.bind(this.buttonNode, 'click', function () {
	        _this4.listIsShown = !_this4.listIsShown;
	        if (_this4.listIsShown) {
	          _this4.showList();
	        } else {
	          _this4.hideList();
	        }
	        _this4.buttonNode.querySelector('button').textContent = _this4.getButtonText();
	      });
	      return this.buttonNode;
	    }
	  }, {
	    key: "showList",
	    value: function showList() {
	      this.listNode.style.height = "".concat(this.listNode.scrollHeight, "px");
	      main_core.Dom.addClass(this.listNode, '--visible');
	    }
	  }, {
	    key: "hideList",
	    value: function hideList() {
	      this.listNode.style.height = "".concat(this.listNode.scrollHeight, "px");
	      this.listNode.clientHeight;
	      this.listNode.style.height = '0';
	      main_core.Dom.removeClass(this.listNode, '--visible');
	    }
	  }, {
	    key: "onTransitionEnd",
	    value: function onTransitionEnd() {
	      if (this.listNode.style.height !== '0px') {
	        this.listNode.style.height = 'auto';
	      }
	    }
	  }, {
	    key: "getButtonText",
	    value: function getButtonText() {
	      if (this.listIsShown) {
	        return main_core.Loc.getMessage('TSM_MEETINGS_SCHEDULED_BUTTON_HIDE');
	      } else {
	        return main_core.Loc.getMessage('TSM_MEETINGS_SCHEDULED_BUTTON');
	      }
	    }
	  }, {
	    key: "getFormattedDate",
	    value: function getFormattedDate(ts) {
	      /* eslint-disable */
	      return BX.date.format(Culture.getInstance().getDayMonthFormat(), ts, null, true);
	      /* eslint-enable */
	    }
	  }]);
	  return ListEvents;
	}(main_core_events.EventEmitter);

	var RequestSender = /*#__PURE__*/function () {
	  function RequestSender() {
	    babelHelpers.classCallCheck(this, RequestSender);
	  }
	  babelHelpers.createClass(RequestSender, [{
	    key: "sendRequest",
	    value: function sendRequest(controller, action) {
	      var data = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runAction('bitrix:tasks.scrum.' + controller + '.' + action, {
	          data: data
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "getMeetings",
	    value: function getMeetings(data) {
	      return this.sendRequest('calendar', 'getMeetings', data);
	    }
	  }, {
	    key: "getChat",
	    value: function getChat(data) {
	      return this.sendRequest('calendar', 'getChat', data);
	    }
	  }, {
	    key: "saveEventInfo",
	    value: function saveEventInfo(data) {
	      return this.sendRequest('calendar', 'saveEventInfo', data);
	    }
	  }, {
	    key: "closeTemplates",
	    value: function closeTemplates(data) {
	      return this.sendRequest('calendar', 'closeTemplates', data);
	    }
	  }, {
	    key: "showErrorAlert",
	    value: function showErrorAlert(response, alertTitle) {
	      if (main_core.Type.isUndefined(response.errors)) {
	        console.error(response);
	        return;
	      }
	      if (response.errors.length) {
	        var firstError = response.errors.shift();
	        if (firstError) {
	          var errorCode = firstError.code ? firstError.code : '';
	          var message = firstError.message + ' ' + errorCode;
	          var title = alertTitle ? alertTitle : main_core.Loc.getMessage('TSM_MEETINGS_ERROR_POPUP_TITLE');
	          ui_dialogs_messagebox.MessageBox.alert(message, title);
	        }
	      }
	    }
	  }]);
	  return RequestSender;
	}();

	var _templateObject$2, _templateObject2$2, _templateObject3$2, _templateObject4$1, _templateObject5$1, _templateObject6$1, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11;
	var Meetings = /*#__PURE__*/function () {
	  function Meetings(params) {
	    babelHelpers.classCallCheck(this, Meetings);
	    this.groupId = parseInt(params.groupId, 10);
	    this.requestSender = new RequestSender();
	    this.todayEvent = new CreatedEvent();
	    this.listEvents = new ListEvents();
	    this.listEvents.subscribe('showView', this.onShowView.bind(this));
	    this.menu = null;
	    this.eventTemplatesMenu = null;
	  }
	  babelHelpers.createClass(Meetings, [{
	    key: "showMenu",
	    value: function showMenu(targetNode) {
	      if (this.menu && this.menu.isShown()) {
	        this.menu.close();
	        return;
	      }
	      var response = this.requestSender.getMeetings({
	        groupId: this.groupId
	      }).then(function (meetingsResponse) {
	        Culture.getInstance().setData(meetingsResponse.data.culture);
	        return meetingsResponse;
	      });
	      this.menu = new ui_popupcomponentsmaker.PopupComponentsMaker({
	        id: 'tasks-scrum-meetings-widget',
	        target: targetNode,
	        cacheable: false,
	        content: [{
	          html: [{
	            html: this.renderMeetings(response)
	          }]
	        }, {
	          html: [{
	            html: this.renderChats(response)
	          }]
	        }]
	      });
	      this.menu.show();
	    }
	  }, {
	    key: "renderMeetings",
	    value: function renderMeetings(response) {
	      var _this = this;
	      return response.then(function (meetingsResponse) {
	        _this.meetingsNode = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings tasks-scrum__widget-meetings--scope\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), _this.renderMeetingsHeader(meetingsResponse), _this.renderEventTemplates(meetingsResponse), _this.renderScheduledMeetings(meetingsResponse));
	        return _this.meetingsNode;
	      })["catch"](function (meetingsResponse) {
	        _this.requestSender.showErrorAlert(meetingsResponse);
	      });
	    }
	  }, {
	    key: "renderChats",
	    value: function renderChats(response) {
	      var _this2 = this;
	      return response.then(function (chatsResponse) {
	        var chats = chatsResponse.data.chats;
	        return main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings tasks-scrum__widget-meetings--scope\">\n\t\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--header\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"ui-icon ui-icon-service-livechat tasks-scrum__widget-meetings--icon-chats\"\n\t\t\t\t\t\t\t><i></i></div>\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--header-title\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('TSM_CHATS_HEADER_TITLE'), _this2.renderChatsList(chats), _this2.renderChatsEmpty(chats));
	      })["catch"](function (errorResponse) {
	        _this2.requestSender.showErrorAlert(errorResponse);
	      });
	    }
	  }, {
	    key: "renderChatsList",
	    value: function renderChatsList(chats) {
	      var _this3 = this;
	      var visibility = chats.length > 0 ? '--visible' : '';
	      return main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--chat-content ", "\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), visibility, chats.map(function (chat) {
	        var chatIconClass = chat.icon === '' ? 'default' : '';
	        var chatIconStyle = chat.icon === '' ? '' : "background-image: url('".concat(chat.icon, "');");
	        var chatNode = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--chat-container\">\n\t\t\t\t\t\t\t\t<div class=\"ui-icon ui-icon-common-company tasks-scrum__widget-meetings--chat-icon\">\n\t\t\t\t\t\t\t\t\t<i\n\t\t\t\t\t\t\t\t\tclass=\"chat-icon ", "\"\n\t\t\t\t\t\t\t\t\t\tstyle=\"", "\"\n\t\t\t\t\t\t\t\t\t></i>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--chat-info\">\n\t\t\t\t\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--chat-name\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"users-icon tasks-scrum__widget-meetings--chat-users\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t"])), chatIconClass, chatIconStyle, chat.name, _this3.renderChatUser(chat.users));
	        main_core.Event.bind(chatNode, 'click', _this3.openChat.bind(_this3, chat, chatNode));
	        return chatNode;
	      }));
	    }
	  }, {
	    key: "openChat",
	    value: function openChat(chat, chatNode) {
	      var _this4 = this;
	      var loader = new main_loader.Loader({
	        target: chatNode,
	        size: 34,
	        mode: 'inline',
	        color: 'rgba(82, 92, 105, 0.9)'
	      });
	      loader.show();
	      this.requestSender.getChat({
	        groupId: this.groupId,
	        chatId: chat.id
	      }).then(function () {
	        if (top.window.BXIM) {
	          top.BXIM.openMessenger("chat".concat(parseInt(chat.id, 10)));
	          _this4.menu.close();
	        }
	      })["catch"](function (response) {
	        _this4.requestSender.showErrorAlert(response);
	      });
	    }
	  }, {
	    key: "renderChatUser",
	    value: function renderChatUser(users) {
	      var uiIconClasses = 'tasks-scrum__widget-meetings--chat-icon-user ui-icon ui-icon-common-user';
	      return main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t", "\n\t\t"])), users.map(function (user) {
	        var src = user.photo ? encodeURI(main_core.Text.encode(user.photo.src)) : null;
	        var photoStyle = src ? "background-image: url('".concat(src, "');") : '';
	        return main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"user-icon ", "\" title=\"", "\">\n\t\t\t\t\t\t\t<i style=\"", "\"></i>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"])), uiIconClasses, main_core.Text.encode(user.name), photoStyle);
	      }));
	    }
	  }, {
	    key: "renderChatsEmpty",
	    value: function renderChatsEmpty(chats) {
	      var visibility = chats.length > 0 ? '' : '--visible';
	      return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--content\">\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--empty-chats ", "\">\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--empty-name\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--empty-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), visibility, main_core.Loc.getMessage('TSM_CHATS_EMPTY_TITLE'), main_core.Loc.getMessage('TSM_CHATS_EMPTY_TEXT'));
	    }
	  }, {
	    key: "renderMeetingsHeader",
	    value: function renderMeetingsHeader(response) {
	      var calendarSettings = response.data.calendarSettings;
	      var uiClasses = 'ui-btn-split ui-btn-light-border ui-btn-xs ui-btn-light ui-btn-no-caps ui-btn-round';
	      var node = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--header\">\n\n\t\t\t\t<div class=\"ui-icon ui-icon-service-calendar tasks-scrum__widget-meetings--icon-calendar\">\n\t\t\t\t\t<i></i>\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--header-title\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--btn-create ", "\">\n\t\t\t\t\t<button class=\"ui-btn-main\" data-role=\"create-default-event\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t\t<div class=\"ui-btn-menu\" data-role=\"show-menu-event-templates\"></div>\n\t\t\t\t</div>\n\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('TSM_MEETINGS_HEADER_TITLE'), uiClasses, main_core.Loc.getMessage('TSM_MEETINGS_CREATE_BUTTON'));
	      var button = node.querySelector('button');
	      var menu = node.querySelector('.ui-btn-menu');
	      main_core.Event.bind(button, 'click', this.showEventSidePanel.bind(this));
	      main_core.Event.bind(menu, 'click', this.showMenuWithEventTemplates.bind(this, button, calendarSettings));
	      return node;
	    }
	  }, {
	    key: "renderEventTemplates",
	    value: function renderEventTemplates(response) {
	      var _this5 = this;
	      var mapCreatedEvents = response.data.mapCreatedEvents;
	      var listEvents = response.data.listEvents;
	      var isTemplatesClosed = response.data.isTemplatesClosed;
	      var calendarSettings = response.data.calendarSettings;
	      var templateVisibility = isTemplatesClosed || this.isAllEventsCreated(mapCreatedEvents) ? '' : '--visible';
	      var emptyVisibility = isTemplatesClosed && listEvents.length === 0 ? '--visible' : '';
	      var contentVisibilityClass = emptyVisibility === '' && templateVisibility === '' ? '--content-hidden' : '';
	      var node = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--content ", "\">\n\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--creation-block ", "\">\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"tasks-scrum__widget-meetings--creation-close-btn\"\n\t\t\t\t\t\tdata-role=\"close-event-templates\"\n\t\t\t\t\t></span>\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--create-element-info\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--empty-meetings ", "\">\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--empty-name\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--empty-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<button class=\"tasks-scrum__widget-meetings--one-click-btn ui-qr-popupcomponentmaker__btn\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), contentVisibilityClass, templateVisibility, main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATES_INFO'), babelHelpers.toConsumableArray(this.getEventTemplates(calendarSettings).values()).map(function (eventTemplate) {
	        if (main_core.Type.isArray(mapCreatedEvents) || main_core.Type.isUndefined(mapCreatedEvents[eventTemplate.id])) {
	          return _this5.renderEventTemplate(eventTemplate);
	        }
	        return '';
	      }), emptyVisibility, main_core.Loc.getMessage('TSM_MEETINGS_EMPTY_TITLE'), main_core.Loc.getMessage('TSM_MEETINGS_EMPTY_TEXT'), main_core.Loc.getMessage('TSM_MEETINGS_CREATE_ONE_CLICK'));
	      var closeButton = node.querySelector('.tasks-scrum__widget-meetings--creation-close-btn');
	      var oneClickButton = node.querySelector('.tasks-scrum__widget-meetings--one-click-btn');
	      var templatesNode = node.querySelector('.tasks-scrum__widget-meetings--creation-block');
	      var emptyNode = node.querySelector('.tasks-scrum__widget-meetings--empty-meetings');
	      main_core.Event.bind(closeButton, 'click', function () {
	        main_core.Dom.removeClass(templatesNode, '--visible');
	        var isExistsEvent = listEvents.length;
	        if (!isExistsEvent) {
	          main_core.Dom.addClass(emptyNode, '--visible');
	        }
	        _this5.requestSender.closeTemplates({
	          groupId: _this5.groupId
	        })["catch"](function (errorResponse) {
	          _this5.requestSender.showErrorAlert(errorResponse);
	        });
	      });
	      main_core.Event.bind(oneClickButton, 'click', function () {
	        main_core.Dom.addClass(templatesNode, '--visible');
	        main_core.Dom.removeClass(emptyNode, '--visible');
	      });
	      return node;
	    }
	  }, {
	    key: "renderScheduledMeetings",
	    value: function renderScheduledMeetings(response) {
	      var todayEvent = response.data.todayEvent;
	      var listEvents = response.data.listEvents;
	      var todayEventVisibility = main_core.Type.isNull(todayEvent) ? '' : '--visible';
	      this.listEvents.setTodayEvent(todayEvent);
	      return main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable\">\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-container ", "\">\n\t\t\t\t\t<div class=\"tasks-scrum__widget-meetings--timetable-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), todayEventVisibility, main_core.Loc.getMessage('TSM_TODAY_EVENT_TITLE'), this.todayEvent.render(todayEvent), this.listEvents.render(listEvents, todayEvent));
	    }
	  }, {
	    key: "renderEventTemplate",
	    value: function renderEventTemplate(eventTemplate) {
	      var node = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"tasks-scrum__widget-meetings--create-element ", "\">\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--create-element-title\">\n\t\t\t\t\t<span class=\"tasks-scrum__widget-meetings--create-element-name\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"ui-hint\">\n\t\t\t\t\t\t<i\n\t\t\t\t\t\t\tclass=\"ui-hint-icon\"\n\t\t\t\t\t\t\tdata-hint=\"", "\"\n\t\t\t\t\t\t\tdata-hint-no-icon\n\t\t\t\t\t\t\tdata-hint-html\n\t\t\t\t\t\t></i>\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"tasks-scrum__widget-meetings--create-btn\">\n\t\t\t\t\t<button\n\t\t\t\t\t\tclass=\"ui-qr-popupcomponentmaker__btn\"\n\t\t\t\t\t\tdata-role=\"create-event-template-", "\"\n\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(eventTemplate.uiClass), main_core.Text.encode(eventTemplate.name), eventTemplate.hint, eventTemplate.id, main_core.Loc.getMessage('TSM_MEETINGS_CREATE_BUTTON'));
	      var createButton = node.querySelector('.tasks-scrum__widget-meetings--create-btn');
	      main_core.Event.bind(createButton, 'click', this.openCalendarSidePanel.bind(this, eventTemplate));
	      this.initHints(node);
	      return node;
	    }
	  }, {
	    key: "onShowView",
	    value: function onShowView() {
	      this.menu.close();
	    }
	  }, {
	    key: "showEventSidePanel",
	    value: function showEventSidePanel() {
	      this.openCalendarSidePanel();
	    }
	  }, {
	    key: "showMenuWithEventTemplates",
	    value: function showMenuWithEventTemplates(targetNode, calendarSettings) {
	      var _this6 = this;
	      if (this.eventTemplatesMenu && this.eventTemplatesMenu.getPopupWindow().isShown()) {
	        this.eventTemplatesMenu.close();
	        return;
	      }
	      this.eventTemplatesMenu = new main_popup.Menu({
	        id: 'tsm-event-templates-menu',
	        bindElement: targetNode,
	        closeByEsc: true
	      });
	      this.eventTemplatesMenu.addMenuItem({
	        text: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_BLANK'),
	        delimiter: true
	      });
	      this.getEventTemplates(calendarSettings).forEach(function (eventTemplate) {
	        _this6.eventTemplatesMenu.addMenuItem({
	          text: eventTemplate.name,
	          onclick: function onclick(event, menuItem) {
	            _this6.openCalendarSidePanel(eventTemplate);
	            menuItem.getMenuWindow().close();
	          }
	        });
	      });
	      this.eventTemplatesMenu.getPopupWindow().subscribe('onClose', function () {
	        _this6.eventTemplatesMenu.destroy();
	      });
	      this.eventTemplatesMenu.show();
	    }
	  }, {
	    key: "openCalendarSidePanel",
	    value: function openCalendarSidePanel(eventTemplate) {
	      var _this7 = this;
	      var participantsEntityList = eventTemplate ? eventTemplate.roles : [];
	      var formData = eventTemplate ? {
	        name: eventTemplate.name,
	        description: eventTemplate.desc,
	        from: eventTemplate.from,
	        to: eventTemplate.to,
	        color: eventTemplate.color,
	        rrule: eventTemplate.rrule
	      } : {
	        name: '',
	        description: '',
	        color: '#86b100'
	      };
	      var sliderId = main_core.Text.getRandom();
	      new window.top.BX.Calendar.SliderLoader(0, {
	        sliderId: sliderId,
	        participantsSelectorEntityList: [{
	          id: 'user'
	        }, {
	          id: 'project-roles',
	          options: {
	            projectId: this.groupId
	          },
	          dynamicLoad: true
	        }],
	        formDataValue: formData,
	        participantsEntityList: participantsEntityList,
	        type: 'group',
	        ownerId: this.groupId
	      }).show();
	      top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onLoad', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          sliderEvent = _event$getCompatData2[0];
	        if (sliderId === sliderEvent.getSlider().getUrl().toString()) {
	          top.BX.Event.EventEmitter.subscribeOnce('BX.Calendar:onEntrySave', function (baseEvent) {
	            var data = baseEvent.getData();
	            if (sliderId === data.sliderId) {
	              if (eventTemplate) {
	                _this7.requestSender.saveEventInfo({
	                  groupId: _this7.groupId,
	                  templateId: eventTemplate.id,
	                  eventId: data.responseData.entryId
	                }).then(function () {
	                  _this7.menu.close();
	                })["catch"](function (response) {
	                  _this7.requestSender.showErrorAlert(response);
	                });
	              }
	              main_core.ajax.runAction('bitrix:tasks.scrum.info.saveAnalyticsLabel', {
	                data: {},
	                analyticsLabel: {
	                  scrum: 'Y',
	                  action: 'create_meet',
	                  template: eventTemplate ? eventTemplate.id : 'custom'
	                }
	              });
	            }
	          });
	        }
	      });
	    }
	  }, {
	    key: "getEventTemplates",
	    value: function getEventTemplates(calendarSettings) {
	      var eventTemplates = new Set();
	      var daysNumberMap = {
	        MO: 1,
	        TU: 2,
	        WE: 3,
	        TH: 4,
	        FR: 5,
	        SA: 6,
	        SU: 7
	      };
	      var weekStartDay = calendarSettings.weekStart[Object.keys(calendarSettings.weekStart)[0]];
	      var weekStartDayNumber = daysNumberMap[weekStartDay];
	      eventTemplates.add({
	        id: 'daily',
	        entityId: 'project-roles',
	        name: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_DAILY'),
	        desc: '',
	        from: new Date(new Date().setHours(calendarSettings.workTimeStart, 0, 0)),
	        to: new Date(new Date().setHours(calendarSettings.workTimeStart, 15, 0)),
	        rrule: {
	          FREQ: 'WEEKLY',
	          INTERVAL: 1,
	          BYDAY: calendarSettings.weekDays
	        },
	        roles: [{
	          id: "".concat(this.groupId, "_M"),
	          entityId: 'project-roles'
	        }, {
	          id: "".concat(this.groupId, "_E"),
	          entityId: 'project-roles'
	        }],
	        uiClass: 'widget-meetings__sprint-daily',
	        color: '#2FC6F6',
	        hint: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_DAILY')
	      });
	      eventTemplates.add({
	        id: 'planning',
	        entityId: 'project-roles',
	        name: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_PLANNING'),
	        desc: '',
	        from: new Date(this.getNextWeekStartDate(weekStartDayNumber).setHours(calendarSettings.workTimeStart, 0, 0)),
	        to: new Date(this.getNextWeekStartDate(weekStartDayNumber).setHours(calendarSettings.workTimeStart + 1, 0, 0)),
	        rrule: {
	          FREQ: 'WEEKLY',
	          INTERVAL: calendarSettings.interval,
	          BYDAY: calendarSettings.weekStart
	        },
	        roles: [{
	          id: "".concat(this.groupId, "_A"),
	          entityId: 'project-roles'
	        }, {
	          id: "".concat(this.groupId, "_M"),
	          entityId: 'project-roles'
	        }, {
	          id: "".concat(this.groupId, "_E"),
	          entityId: 'project-roles'
	        }],
	        uiClass: 'widget-meetings__sprint-planning',
	        color: '#DA51D4',
	        hint: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_PLANNING')
	      });
	      eventTemplates.add({
	        id: 'review',
	        entityId: 'project-roles',
	        name: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_REVIEW'),
	        desc: '',
	        from: new Date(this.getNextWeekStartDate(weekStartDayNumber).setHours(calendarSettings.workTimeStart, 0, 0)),
	        to: new Date(this.getNextWeekStartDate(weekStartDayNumber).setHours(calendarSettings.workTimeStart + 1, 0, 0)),
	        rrule: {
	          FREQ: 'WEEKLY',
	          INTERVAL: calendarSettings.interval,
	          BYDAY: calendarSettings.weekStart
	        },
	        roles: [{
	          id: "".concat(this.groupId, "_A"),
	          entityId: 'project-roles'
	        }, {
	          id: "".concat(this.groupId, "_M"),
	          entityId: 'project-roles'
	        }, {
	          id: "".concat(this.groupId, "_E"),
	          entityId: 'project-roles'
	        }],
	        uiClass: 'widget-meetings__sprint-review',
	        color: '#FF5752',
	        hint: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_REVIEW')
	      });
	      eventTemplates.add({
	        id: 'retrospective',
	        entityId: 'project-roles',
	        name: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_NAME_RETROSPECTIVE'),
	        desc: '',
	        from: new Date(this.getNextWeekStartDate(weekStartDayNumber).setHours(calendarSettings.workTimeStart, 0, 0)),
	        to: new Date(this.getNextWeekStartDate(weekStartDayNumber).setHours(calendarSettings.workTimeStart + 1, 0, 0)),
	        rrule: {
	          FREQ: 'WEEKLY',
	          INTERVAL: calendarSettings.interval,
	          BYDAY: calendarSettings.weekStart
	        },
	        roles: [{
	          id: "".concat(this.groupId, "_M"),
	          entityId: 'project-roles'
	        }, {
	          id: "".concat(this.groupId, "_E"),
	          entityId: 'project-roles'
	        }],
	        uiClass: 'widget-meetings__sprint-retrospective',
	        color: '#FF5752',
	        hint: main_core.Loc.getMessage('TSM_MEETINGS_EVENT_TEMPLATE_HINT_RETROSPECTIVE')
	      });
	      return eventTemplates;
	    }
	  }, {
	    key: "isAllEventsCreated",
	    value: function isAllEventsCreated(mapCreatedEvents) {
	      if (main_core.Type.isArray(mapCreatedEvents)) {
	        return false;
	      }
	      return main_core.Type.isInteger(mapCreatedEvents.daily) && main_core.Type.isInteger(mapCreatedEvents.planning) && main_core.Type.isInteger(mapCreatedEvents.review) && main_core.Type.isInteger(mapCreatedEvents.retrospective);
	    }
	  }, {
	    key: "getNextWeekStartDate",
	    value: function getNextWeekStartDate(weekStartDayNumber) {
	      var date = new Date();
	      var targetDate = new Date();
	      var delta = weekStartDayNumber - date.getDay();
	      if (delta >= 0) {
	        targetDate.setDate(date.getDate() + delta);
	      } else {
	        targetDate.setDate(date.getDate() + 7 + delta);
	      }
	      return targetDate;
	    }
	  }, {
	    key: "initHints",
	    value: function initHints(node) {
	      BX.UI.Hint.init(node);
	    }
	  }]);
	  return Meetings;
	}();

	exports.Meetings = Meetings;

}((this.BX.Tasks.Scrum = this.BX.Tasks.Scrum || {}),BX,BX.Main,BX.UI,BX.Event,BX,BX.UI.Dialogs));
//# sourceMappingURL=meetings.bundle.js.map
