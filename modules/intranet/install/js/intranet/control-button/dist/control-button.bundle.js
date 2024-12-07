/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,im_public_iframe) {
	'use strict';

	var _templateObject, _templateObject2;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var ControlButton = /*#__PURE__*/function () {
	  function ControlButton() {
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, ControlButton);
	    this.container = params.container;
	    if (!main_core.Type.isDomNode(this.container)) {
	      return;
	    }
	    this.entityType = params.entityType || '';
	    this.entityId = params.entityId || '';
	    if (!this.entityType || !this.entityId) {
	      return;
	    }
	    this.items = params.items || [];
	    this.mainItem = params.mainItem || 'videocall';
	    this.entityData = params.entityData || {};
	    var analyticsLabelParam = params.analyticsLabel || {};
	    if (this.items.length === 0) {
	      switch (this.entityType) {
	        case 'task':
	          {
	            this.items = ['chat', 'videocall', 'blog_post', 'calendar_event'];
	            break;
	          }
	        case 'calendar_event':
	          {
	            this.items = ['chat', 'videocall', 'blog_post', 'task'];
	            break;
	          }
	        case 'workgroup':
	          {
	            this.items = ['chat', 'videocall'];
	            break;
	          }
	        default:
	          {
	            this.items = ['chat', 'videocall', 'blog_post', 'task', 'calendar_event'];
	          }
	      }
	    }
	    this.contextBx = window.top.BX || window.BX;
	    this.sliderId = "controlButton:".concat(this.entityType + this.entityId).concat(Math.floor(Math.random() * 1000));
	    this.isVideoCallEnabled = main_core.Reflection.getClass("".concat(this.contextBx, ".Call.Util")) ? this.contextBx.Call.Util.isWebRTCSupported() : true;
	    this.chatLockCounter = 0;
	    if (!main_core.Type.isPlainObject(analyticsLabelParam)) {
	      analyticsLabelParam = {};
	    }
	    this.analyticsLabel = _objectSpread({
	      entity: this.entityType
	    }, analyticsLabelParam);
	    this.analytics = params.analytics || {};
	    if (!main_core.Type.isPlainObject(this.analytics)) {
	      this.analytics = {};
	    }
	    this.buttonClassName = params.buttonClassName || '';
	    this.renderButton();
	    this.subscribeEvents();
	  }
	  babelHelpers.createClass(ControlButton, [{
	    key: "destroy",
	    value: function destroy() {
	      this.contextBx.Event.EventEmitter.unsubscribe('BX.Calendar:onEntrySave', this.onCalendarSave);
	      this.contextBx.Event.EventEmitter.unsubscribe('SidePanel.Slider:onMessage', this.onPostSave);
	    }
	  }, {
	    key: "subscribeEvents",
	    value: function subscribeEvents() {
	      this.contextBx.Event.EventEmitter.subscribe('BX.Calendar:onEntrySave', this.onCalendarSave.bind(this));
	      this.contextBx.Event.EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onPostSave.bind(this));
	    }
	  }, {
	    key: "onCalendarSave",
	    value: function onCalendarSave(event) {
	      if (event instanceof this.contextBx.Event.BaseEvent) {
	        var data = event.getData();
	        if (data.sliderId === this.sliderId) {
	          var params = {
	            postEntityType: this.entityType.toUpperCase(),
	            sourceEntityType: this.entityType.toUpperCase(),
	            sourceEntityId: this.entityId,
	            sourceEntityData: this.entityData,
	            entityType: 'CALENDAR_EVENT',
	            entityId: data.responseData.entryId
	          };
	          this.addEntityComment(params);
	        }
	      }
	    }
	  }, {
	    key: "onPostSave",
	    value: function onPostSave(event) {
	      var _event$getCompatData = event.getCompatData(),
	        _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	        sliderEvent = _event$getCompatData2[0];
	      if (sliderEvent.getEventId() === 'Socialnetwork.PostForm:onAdd') {
	        var data = sliderEvent.getData();
	        if (data.originatorSliderId === this.sliderId) {
	          var params = {
	            postEntityType: this.entityType.toUpperCase(),
	            sourceEntityType: this.entityType.toUpperCase(),
	            sourceEntityId: this.entityId,
	            sourceEntityData: this.entityData,
	            entityType: 'BLOG_POST',
	            entityId: data.successPostId
	          };
	          this.addEntityComment(params);
	        }
	      }
	    }
	  }, {
	    key: "renderButton",
	    value: function renderButton() {
	      var isChatButton = !this.isVideoCallEnabled || this.mainItem === 'chat';
	      var onClickValue = isChatButton ? this.openChat.bind(this) : this.startVideoCall.bind(this);
	      var buttonTitle = isChatButton ? main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_CHAT') : main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_NAME_MSG_1');
	      var buttonClass = "".concat(isChatButton ? 'ui-btn-icon-chat-blue' : 'ui-btn-icon-camera-blue', " intranet-control-btn ui-btn-light-border ui-btn-icon-inline ").concat(this.buttonClassName);
	      this.button = this.items.length > 1 ? main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"ui-btn-split ", "\">\n\t\t\t\t\t\t<button class=\"ui-btn-main\" onclick=\"", "\">", "</button>\n\t\t\t\t\t\t<button class=\"ui-btn-menu\" onclick=\"", "\"></button> \n\t\t\t\t\t</div>\n\t\t\t\t"])), buttonClass, onClickValue, buttonTitle, this.showMenu.bind(this)) : main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<button class=\"ui-btn ", "\" onclick=\"", "\">", "</button>"])), buttonClass, onClickValue, buttonTitle);
	      main_core.Dom.append(this.button, this.container);
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      main_core.Dom.addClass(this.button, 'ui-btn-wait');
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      main_core.Dom.removeClass(this.button, 'ui-btn-wait');
	    }
	  }, {
	    key: "getAvailableItems",
	    value: function getAvailableItems() {
	      var _this = this;
	      return new Promise(function (resolve, reject) {
	        var availableItems = window.sessionStorage.getItem('b24-controlbutton-available-items');
	        if (availableItems) {
	          resolve(availableItems);
	          return;
	        }
	        _this.showLoader();
	        main_core.ajax.runAction('intranet.controlbutton.getAvailableItems', {
	          data: {}
	        }).then(function (response) {
	          window.sessionStorage.setItem('b24-controlbutton-available-items', response.data);
	          _this.hideLoader();
	          resolve(response.data);
	        });
	      });
	    }
	  }, {
	    key: "showMenu",
	    value: function showMenu() {
	      var _this2 = this;
	      this.getAvailableItems().then(function (availableItems) {
	        _this2.items = _this2.items.filter(function (item) {
	          return item && availableItems.includes(item);
	        });
	        var menuItems = [];
	        _this2.items.forEach(function (item) {
	          // eslint-disable-next-line default-case
	          switch (item) {
	            case 'videocall':
	              if (_this2.isVideoCallEnabled) {
	                menuItems.push({
	                  text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_VIDEOCALL'),
	                  className: 'menu-popup-item-videocall',
	                  onclick: function onclick() {
	                    _this2.startVideoCall();
	                    _this2.popupMenu.close();
	                  }
	                });
	              }
	              break;
	            case 'chat':
	              menuItems.push({
	                text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_CHAT'),
	                className: 'menu-popup-item-chat',
	                onclick: function onclick() {
	                  _this2.openChat();
	                  _this2.popupMenu.close();
	                }
	              });
	              break;
	            case 'task':
	              menuItems.push({
	                text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_TASK'),
	                className: 'menu-popup-item-task',
	                onclick: function onclick() {
	                  _this2.openTaskSlider();
	                  _this2.popupMenu.close();
	                }
	              });
	              break;
	            case 'calendar_event':
	              menuItems.push({
	                text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_MEETING'),
	                className: 'menu-popup-item-meeting',
	                onclick: function onclick() {
	                  _this2.openCalendarSlider();
	                  _this2.popupMenu.close();
	                }
	              });
	              break;
	            case 'blog_post':
	              menuItems.push({
	                text: main_core.Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_POST'),
	                className: 'menu-popup-item-post',
	                onclick: function onclick() {
	                  _this2.openPostSlider();
	                  _this2.popupMenu.close();
	                }
	              });
	              break;
	          }
	        });
	        _this2.popupMenu = new main_popup.Menu({
	          bindElement: _this2.button,
	          items: menuItems,
	          offsetLeft: 80,
	          offsetTop: 5
	        });
	        _this2.popupMenu.show();
	      });
	    }
	  }, {
	    key: "openChat",
	    value: function openChat() {
	      var _this3 = this;
	      this.showLoader();
	      var analytics = this.analytics.openChat || {};
	      main_core.ajax.runAction('intranet.controlbutton.getChat', this.getAjaxConfig(analytics)).then(function (response) {
	        if (response.data) {
	          im_public_iframe.Messenger.openChat("chat".concat(parseInt(response.data, 10)));
	        }
	        _this3.chatLockCounter = 0;
	        _this3.hideLoader();
	      }, function (response) {
	        if (response.errors[0].code === 'lock_error' && _this3.chatLockCounter < 4) {
	          _this3.chatLockCounter++;
	          _this3.openChat();
	        } else {
	          _this3.showHintPopup(response.errors[0].message);
	          _this3.hideLoader();
	        }
	      });
	    }
	  }, {
	    key: "startVideoCall",
	    value: function startVideoCall() {
	      var _this4 = this;
	      var videoCallContext = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      this.showLoader();
	      var analytics = this.analytics.startVideoCall || {};
	      if (main_core.Type.isPlainObject(analytics) && main_core.Type.isStringFilled(analytics.c_sub_section) && videoCallContext) {
	        analytics.c_sub_section = videoCallContext;
	      }
	      main_core.ajax.runAction('intranet.controlbutton.getVideoCallChat', this.getAjaxConfig(analytics)).then(function (response) {
	        if (response.data) {
	          im_public_iframe.Messenger.startVideoCall("chat".concat(response.data), true);
	        }
	        _this4.chatLockCounter = 0;
	        _this4.hideLoader();
	      }, function (response) {
	        if (response.errors[0].code === 'lock_error' && _this4.chatLockCounter < 4) {
	          _this4.chatLockCounter++;
	          _this4.startVideoCall();
	        } else {
	          _this4.showHintPopup(response.errors[0].message);
	          _this4.hideLoader();
	        }
	      });
	    }
	  }, {
	    key: "addEntityComment",
	    value: function addEntityComment(params) {
	      main_core.ajax.runAction('socialnetwork.api.livefeed.createEntityComment', {
	        data: {
	          params: params
	        }
	      });
	    }
	  }, {
	    key: "openCalendarSlider",
	    value: function openCalendarSlider() {
	      var _this5 = this;
	      this.showLoader();
	      var analytics = this.analytics.openCalendarSlider || {};
	      main_core.ajax.runAction('intranet.controlbutton.getCalendarLink', this.getAjaxConfig(analytics)).then(function (response) {
	        var users = [];
	        if (main_core.Type.isArrayLike(response.data.userIds)) {
	          users = response.data.userIds.map(function (userId) {
	            return {
	              id: parseInt(userId, 10),
	              entityId: 'user'
	            };
	          });
	        }
	        new (window.top.BX || window.BX).Calendar.SliderLoader(0, {
	          sliderId: _this5.sliderId,
	          participantsEntityList: users,
	          entryName: response.data.name,
	          entryDescription: response.data.desc
	        }).show();
	        _this5.hideLoader();
	      });
	    }
	  }, {
	    key: "openTaskSlider",
	    value: function openTaskSlider() {
	      var _this6 = this;
	      this.showLoader();
	      var analytics = this.analytics.openTaskSlider || {};
	      main_core.ajax.runAction('intranet.controlbutton.getTaskLink', this.getAjaxConfig(analytics)).then(function (response) {
	        BX.SidePanel.Instance.open(response.data.link, {
	          requestMethod: 'post',
	          requestParams: response.data
	        });
	        _this6.hideLoader();
	      });
	    }
	  }, {
	    key: "openPostSlider",
	    value: function openPostSlider() {
	      var _this7 = this;
	      this.showLoader();
	      var analytics = this.analytics.openPostSlider || {};
	      main_core.ajax.runAction('intranet.controlbutton.getPostLink', this.getAjaxConfig(analytics)).then(function (response) {
	        BX.SidePanel.Instance.open(response.data.link, {
	          requestMethod: 'post',
	          requestParams: {
	            POST_TITLE: response.data.title,
	            POST_MESSAGE: response.data.message,
	            destTo: response.data.destTo
	          },
	          data: {
	            sliderId: _this7.sliderId
	          }
	        });
	        _this7.hideLoader();
	      });
	    }
	  }, {
	    key: "getAjaxConfig",
	    value: function getAjaxConfig(analytics) {
	      var config = {
	        data: {
	          entityType: this.entityType,
	          entityId: this.entityId,
	          entityData: this.entityData
	        }
	      };
	      if (main_core.Type.isPlainObject(analytics) && main_core.Type.isStringFilled(analytics.event) && main_core.Type.isStringFilled(analytics.tool)) {
	        config.analytics = analytics;
	      } else {
	        config.analyticsLabel = this.analyticsLabel;
	      }
	      return config;
	    }
	  }, {
	    key: "showHintPopup",
	    value: function showHintPopup(message) {
	      if (!message) {
	        return;
	      }
	      new main_popup.Popup("inviteHint".concat(main_core.Text.getRandom(8)), this.button, {
	        content: message,
	        zIndex: 15000,
	        angle: true,
	        offsetTop: 0,
	        offsetLeft: 50,
	        closeIcon: false,
	        autoHide: true,
	        darkMode: true,
	        overlay: false,
	        maxWidth: 400,
	        events: {
	          onAfterPopupShow: function onAfterPopupShow() {
	            var _this8 = this;
	            setTimeout(function () {
	              _this8.close();
	            }, 5000);
	          }
	        }
	      }).show();
	    }
	  }]);
	  return ControlButton;
	}();

	exports.ControlButton = ControlButton;

}((this.BX.Intranet = this.BX.Intranet || {}),BX,BX.Main,BX.Messenger.v2.Lib));
//# sourceMappingURL=control-button.bundle.js.map
