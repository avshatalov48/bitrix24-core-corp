(function (exports,main_polyfill_customevent,pull_components_status,ui_vue_components_smiles,im_component_dialog,im_component_textarea,imopenlines_component_message,rest_client,main_md5,main_date,pull_client,im_model,im_controller,im_tools_localstorage,im_provider_rest,im_provider_pull,im_tools_logger,im_const,ui_icons,ui_forms,im_utils,ui_vue,ui_vue_vuex) {
	'use strict';

	/**
	 * Bitrix OpenLines widget
	 * Widget constants
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	function GetObjectValues(source) {
	  var destination = [];

	  for (var value in source) {
	    if (source.hasOwnProperty(value)) {
	      destination.push(source[value]);
	    }
	  }

	  return destination;
	}
	/* region 01. Constants */


	var VoteType = Object.freeze({
	  none: 'none',
	  like: 'like',
	  dislike: 'dislike'
	});
	var LanguageType = Object.freeze({
	  russian: 'ru',
	  ukraine: 'ua',
	  world: 'en'
	});
	var FormType = Object.freeze({
	  none: 'none',
	  like: 'like',
	  smile: 'smile',
	  consent: 'consent',
	  welcome: 'welcome',
	  offline: 'offline',
	  history: 'history'
	});
	var LocationType = Object.freeze({
	  topLeft: 1,
	  topMiddle: 2,
	  topBottom: 3,
	  bottomLeft: 6,
	  bottomMiddle: 5,
	  bottomRight: 4
	});
	var LocationStyle = Object.freeze({
	  1: 'top-left',
	  2: 'top-center',
	  3: 'top-right',
	  6: 'bottom-left',
	  5: 'bottom-center',
	  4: 'bottom-right'
	});
	var SubscriptionType$1 = Object.freeze({
	  configLoaded: 'configLoaded',
	  widgetOpen: 'widgetOpen',
	  widgetClose: 'widgetClose',
	  sessionStart: 'sessionStart',
	  sessionOperatorChange: 'sessionOperatorChange',
	  sessionFinish: 'sessionFinish',
	  operatorMessage: 'operatorMessage',
	  userForm: 'userForm',
	  userMessage: 'userMessage',
	  userVote: 'userVote',
	  every: 'every'
	});
	var SubscriptionTypeCheck = GetObjectValues(SubscriptionType$1);
	var RestMethod = Object.freeze({
	  widgetUserRegister: 'imopenlines.widget.user.register',
	  widgetConfigGet: 'imopenlines.widget.config.get',
	  widgetDialogGet: 'imopenlines.widget.dialog.get',
	  widgetUserGet: 'imopenlines.widget.user.get',
	  widgetUserConsentApply: 'imopenlines.widget.user.consent.apply',
	  widgetVoteSend: 'imopenlines.widget.vote.send',
	  widgetFormSend: 'imopenlines.widget.form.send',
	  pullServerTime: 'server.time',
	  pullConfigGet: 'pull.config.get'
	});
	var RestMethodCheck = GetObjectValues(RestMethod);
	var RestAuth = Object.freeze({
	  guest: 'guest'
	});

	/**
	 * Bitrix OpenLines widget
	 * Cookie manager
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	var Cookie = {
	  get: function get(siteId, name) {
	    var cookieName = siteId ? siteId + '_' + name : name;

	    if (navigator.cookieEnabled) {
	      var result = document.cookie.match(new RegExp("(?:^|; )" + cookieName.replace(/([.$?*|{}()\[\]\\\/+^])/g, '\\$1') + "=([^;]*)"));

	      if (result) {
	        return decodeURIComponent(result[1]);
	      }
	    }

	    if (im_tools_localstorage.LocalStorage.isEnabled()) {
	      var _result = im_tools_localstorage.LocalStorage.get(siteId, 0, name, undefined);

	      if (typeof _result !== 'undefined') {
	        return _result;
	      }
	    }

	    if (typeof window.BX.LiveChatCookie === 'undefined') {
	      window.BX.LiveChatCookie = {};
	    }

	    return window.BX.LiveChatCookie[cookieName];
	  },
	  set: function set(siteId, name, value, options) {
	    options = options || {};
	    var expires = options.expires;

	    if (typeof expires == "number" && expires) {
	      var currentDate = new Date();
	      currentDate.setTime(currentDate.getTime() + expires * 1000);
	      expires = options.expires = currentDate;
	    }

	    if (expires && expires.toUTCString) {
	      options.expires = expires.toUTCString();
	    }

	    value = encodeURIComponent(value);
	    var cookieName = siteId ? siteId + '_' + name : name;
	    var updatedCookie = cookieName + "=" + value;

	    for (var propertyName in options) {
	      if (!options.hasOwnProperty(propertyName)) {
	        continue;
	      }

	      updatedCookie += "; " + propertyName;
	      var propertyValue = options[propertyName];

	      if (propertyValue !== true) {
	        updatedCookie += "=" + propertyValue;
	      }
	    }

	    document.cookie = updatedCookie;

	    if (typeof window.BX.LiveChatCookie === 'undefined') {
	      BX.LiveChatCookie = {};
	    }

	    window.BX.LiveChatCookie[cookieName] = value;
	    im_tools_localstorage.LocalStorage.set(siteId, 0, name, value);
	    return true;
	  }
	};

	/**
	 * Bitrix OpenLines widget
	 * Widget model (Vuex Builder model)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	var WidgetModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
	  babelHelpers.inherits(WidgetModel, _VuexBuilderModel);

	  function WidgetModel() {
	    babelHelpers.classCallCheck(this, WidgetModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(WidgetModel, [{
	    key: "getName",

	    /**
	     * @inheritDoc
	     */
	    value: function getName() {
	      return 'widget';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        common: {
	          configId: 0,
	          configName: '',
	          host: this.getVariable('common.host', location.protocol + '//' + location.host),
	          pageMode: this.getVariable('common.pageMode', false),
	          copyright: this.getVariable('common.copyright', true),
	          copyrightUrl: this.getVariable('common.copyrightUrl', 'https://bitrix24.com'),
	          location: this.getVariable('common.location', LocationType.bottomRight),
	          styles: {
	            backgroundColor: this.getVariable('styles.backgroundColor', '#17a3ea'),
	            iconColor: this.getVariable('styles.iconColor', '#ffffff')
	          },
	          vote: {
	            enable: false,
	            messageText: this.getVariable('vote.messageText', ''),
	            messageLike: this.getVariable('vote.messageLike', ''),
	            messageDislike: this.getVariable('vote.messageDislike', '')
	          },
	          textMessages: {
	            bxLivechatOnlineLine1: this.getVariable('textMessages.bxLivechatOnlineLine1', ''),
	            bxLivechatOnlineLine2: this.getVariable('textMessages.bxLivechatOnlineLine2', ''),
	            bxLivechatOffline: this.getVariable('textMessages.bxLivechatOffline', '')
	          },
	          online: false,
	          operators: [],
	          connectors: [],
	          showForm: FormType.none,
	          uploadFile: false,
	          showed: false,
	          reopen: false,
	          dragged: false,
	          textareaHeight: 0,
	          showConsent: false,
	          consentUrl: '',
	          dialogStart: false
	        },
	        dialog: {
	          sessionId: 0,
	          sessionClose: true,
	          userVote: VoteType.none,
	          userConsent: false,
	          operator: {
	            name: '',
	            firstName: '',
	            lastName: '',
	            workPosition: '',
	            avatar: '',
	            online: false
	          }
	        },
	        user: {
	          id: -1,
	          hash: '',
	          name: '',
	          firstName: '',
	          lastName: '',
	          avatar: '',
	          email: '',
	          phone: '',
	          www: '',
	          gender: 'M',
	          position: ''
	        }
	      };
	    }
	  }, {
	    key: "getStateSaveException",
	    value: function getStateSaveException() {
	      return {
	        common: {
	          host: null,
	          pageMode: null,
	          copyright: null,
	          copyrightUrl: null,
	          styles: null,
	          dragged: null,
	          showed: null,
	          showConsent: null,
	          showForm: null,
	          uploadFile: null,
	          location: null
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this = this;

	      return {
	        common: function common(state, payload) {
	          if (typeof payload.configId === 'number') {
	            state.common.configId = payload.configId;
	          }

	          if (typeof payload.configName === 'string') {
	            state.common.configName = payload.configName;
	          }

	          if (typeof payload.online === 'boolean') {
	            state.common.online = payload.online;
	          }

	          if (im_utils.Utils.types.isPlainObject(payload.vote)) {
	            if (typeof payload.vote.enable === 'boolean') {
	              state.common.vote.enable = payload.vote.enable;
	            }

	            if (typeof payload.vote.messageText === 'string') {
	              state.common.vote.messageText = payload.vote.messageText;
	            }

	            if (typeof payload.vote.messageLike === 'string') {
	              state.common.vote.messageLike = payload.vote.messageLike;
	            }

	            if (typeof payload.vote.messageDislike === 'string') {
	              state.common.vote.messageDislike = payload.vote.messageDislike;
	            }
	          }

	          if (im_utils.Utils.types.isPlainObject(payload.textMessages)) {
	            if (typeof payload.textMessages.bxLivechatOnlineLine1 === 'string') {
	              state.common.textMessages.bxLivechatOnlineLine1 = payload.textMessages.bxLivechatOnlineLine1;
	            }

	            if (typeof payload.textMessages.bxLivechatOnlineLine2 === 'string') {
	              state.common.textMessages.bxLivechatOnlineLine2 = payload.textMessages.bxLivechatOnlineLine2;
	            }

	            if (typeof payload.textMessages.bxLivechatOffline === 'string') {
	              state.common.textMessages.bxLivechatOffline = payload.textMessages.bxLivechatOffline;
	            }
	          }

	          if (typeof payload.dragged === 'boolean') {
	            state.common.dragged = payload.dragged;
	          }

	          if (typeof payload.textareaHeight === 'number') {
	            state.common.textareaHeight = payload.textareaHeight;
	          }

	          if (typeof payload.showConsent === 'boolean') {
	            state.common.showConsent = payload.showConsent;
	          }

	          if (typeof payload.consentUrl === 'string') {
	            state.common.consentUrl = payload.consentUrl;
	          }

	          if (typeof payload.showed === 'boolean') {
	            state.common.showed = payload.showed;
	            payload.reopen = payload.showed;
	          }

	          if (typeof payload.reopen === 'boolean') {
	            state.common.reopen = payload.showed;
	          }

	          if (typeof payload.copyright === 'boolean') {
	            state.common.copyright = payload.copyright;
	          }

	          if (typeof payload.dialogStart === 'boolean') {
	            state.common.dialogStart = payload.dialogStart;
	          }

	          if (payload.operators instanceof Array) {
	            state.common.operators = payload.operators;
	          }

	          if (payload.connectors instanceof Array) {
	            state.common.connectors = payload.connectors;
	          }

	          if (typeof payload.uploadFilePlus !== 'undefined') {
	            state.common.uploadFile = state.common.uploadFile + 1;
	          }

	          if (typeof payload.uploadFileMinus !== 'undefined') {
	            state.common.uploadFile = state.common.uploadFile - 1;
	          }

	          if (typeof payload.showForm === 'string' && typeof FormType[payload.showForm] !== 'undefined') {
	            state.common.showForm = payload.showForm;
	          }

	          if (typeof payload.location === 'number' && typeof LocationStyle[payload.location] !== 'undefined') {
	            state.common.location = payload.location;
	          }

	          if (_this.isSaveNeeded({
	            common: payload
	          })) {
	            _this.saveState(state);
	          }
	        },
	        dialog: function dialog(state, payload) {
	          if (typeof payload.sessionId === 'number') {
	            state.dialog.sessionId = payload.sessionId;
	          }

	          if (typeof payload.sessionClose === 'boolean') {
	            state.dialog.sessionClose = payload.sessionClose;
	          }

	          if (typeof payload.userConsent === 'boolean') {
	            state.dialog.userConsent = payload.userConsent;
	          }

	          if (typeof payload.userVote === 'string' && typeof payload.userVote !== 'undefined') {
	            state.dialog.userVote = payload.userVote;
	          }

	          if (im_utils.Utils.types.isPlainObject(payload.operator)) {
	            if (typeof payload.operator.name === 'string' || typeof payload.operator.name === 'number') {
	              state.dialog.operator.name = payload.operator.name.toString();
	            }

	            if (typeof payload.operator.lastName === 'string' || typeof payload.operator.lastName === 'number') {
	              state.dialog.operator.lastName = payload.operator.lastName.toString();
	            }

	            if (typeof payload.operator.firstName === 'string' || typeof payload.operator.firstName === 'number') {
	              state.dialog.operator.firstName = payload.operator.firstName.toString();
	            }

	            if (typeof payload.operator.workPosition === 'string' || typeof payload.operator.workPosition === 'number') {
	              state.dialog.operator.workPosition = payload.operator.workPosition.toString();
	            }

	            if (typeof payload.operator.avatar === 'string') {
	              if (!payload.operator.avatar || payload.operator.avatar.startsWith('http')) {
	                state.dialog.operator.avatar = payload.operator.avatar;
	              } else {
	                state.dialog.operator.avatar = state.common.host + payload.operator.avatar;
	              }
	            }

	            if (typeof payload.operator.online === 'boolean') {
	              state.dialog.operator.online = payload.operator.online;
	            }
	          }

	          if (_this.isSaveNeeded({
	            dialog: payload
	          })) {
	            _this.saveState(state);
	          }
	        },
	        user: function user(state, payload) {
	          if (typeof payload.id === 'number') {
	            state.user.id = payload.id;
	          }

	          if (typeof payload.hash === 'string' && payload.hash !== state.user.hash) {
	            state.user.hash = payload.hash;
	            Cookie.set(null, 'LIVECHAT_HASH', payload.hash, {
	              expires: 365 * 86400,
	              path: '/'
	            });
	          }

	          if (typeof payload.name === 'string' || typeof payload.name === 'number') {
	            state.user.name = payload.name.toString();
	          }

	          if (typeof payload.firstName === 'string' || typeof payload.firstName === 'number') {
	            state.user.firstName = payload.firstName.toString();
	          }

	          if (typeof payload.lastName === 'string' || typeof payload.lastName === 'number') {
	            state.user.lastName = payload.lastName.toString();
	          }

	          if (typeof payload.avatar === 'string') {
	            state.user.avatar = payload.avatar;
	          }

	          if (typeof payload.email === 'string') {
	            state.user.email = payload.email;
	          }

	          if (typeof payload.phone === 'string' || typeof payload.phone === 'number') {
	            state.user.phone = payload.phone.toString();
	          }

	          if (typeof payload.www === 'string') {
	            state.user.www = payload.www;
	          }

	          if (typeof payload.gender === 'string') {
	            state.user.gender = payload.gender;
	          }

	          if (typeof payload.position === 'string') {
	            state.user.position = payload.position;
	          }

	          if (_this.isSaveNeeded({
	            user: payload
	          })) {
	            _this.saveState(state);
	          }
	        }
	      };
	    }
	  }]);
	  return WidgetModel;
	}(ui_vue_vuex.VuexBuilderModel);

	/**
	 * Bitrix OpenLines widget
	 * Rest client (base on BX.RestClient)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	var WidgetRestClient =
	/*#__PURE__*/
	function () {
	  function WidgetRestClient(params) {
	    babelHelpers.classCallCheck(this, WidgetRestClient);
	    this.queryAuthRestore = false;
	    this.setAuthId(RestAuth.guest);
	    this.restClient = new rest_client.RestClient({
	      endpoint: params.endpoint,
	      queryParams: this.queryParams,
	      cors: true
	    });
	  }

	  babelHelpers.createClass(WidgetRestClient, [{
	    key: "setAuthId",
	    value: function setAuthId(authId) {
	      var customAuthId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';

	      if (babelHelpers.typeof(this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }

	      if (authId == RestAuth.guest || typeof authId === 'string' && authId.match(/^[a-f0-9]{32}$/)) {
	        this.queryParams.livechat_auth_id = authId;
	      } else {
	        console.error("%LiveChatRestClient.setAuthId: auth is not correct (%c".concat(authId, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }

	      if (authId == RestAuth.guest && typeof customAuthId === 'string' && customAuthId.match(/^[a-f0-9]{32}$/)) {
	        this.queryParams.livechat_custom_auth_id = customAuthId;
	      }

	      return true;
	    }
	  }, {
	    key: "getAuthId",
	    value: function getAuthId() {
	      if (babelHelpers.typeof(this.queryParams) !== 'object') {
	        this.queryParams = {};
	      }

	      return this.queryParams.livechat_auth_id || null;
	    }
	  }, {
	    key: "callMethod",
	    value: function callMethod(method, params, callback, sendCallback) {
	      var _this = this;

	      var logTag = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : null;

	      if (!logTag) {
	        logTag = im_utils.Utils.getLogTrackingParams({
	          name: method
	        });
	      }

	      var promise = new BX.Promise();
	      this.restClient.callMethod(method, params, null, sendCallback, logTag).then(function (result) {
	        _this.queryAuthRestore = false;
	        promise.fulfill(result);
	      }).catch(function (result) {
	        var error = result.error();

	        if (error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER') {
	          _this.setAuthId(error.ex.hash);

	          if (method === RestMethod.widgetUserRegister) {
	            console.warn("BX.LiveChatRestClient: ".concat(error.ex.error_description, " (").concat(error.ex.error, ")"));
	            _this.queryAuthRestore = false;
	            promise.reject(result);
	            return false;
	          }

	          if (!_this.queryAuthRestore) {
	            console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');
	            _this.queryAuthRestore = true;

	            _this.restClient.callMethod(method, params, null, sendCallback, logTag).then(function (result) {
	              _this.queryAuthRestore = false;
	              promise.fulfill(result);
	            }).catch(function (result) {
	              _this.queryAuthRestore = false;
	              promise.reject(result);
	            });

	            return false;
	          }
	        }

	        _this.queryAuthRestore = false;
	        promise.reject(result);
	      });
	      return promise;
	    }
	  }, {
	    key: "callBatch",
	    value: function callBatch(calls, callback, bHaltOnError, sendCallback, logTag) {
	      var _this2 = this;

	      var resultCallback = function resultCallback(result) {

	        for (var method in calls) {
	          if (!calls.hasOwnProperty(method)) {
	            continue;
	          }

	          var _error = result[method].error();

	          if (_error && _error.ex.error == 'LIVECHAT_AUTH_WIDGET_USER') {
	            _this2.setAuthId(_error.ex.hash);

	            if (method === RestMethod.widgetUserRegister) {
	              console.warn("BX.LiveChatRestClient: ".concat(_error.ex.error_description, " (").concat(_error.ex.error, ")"));
	              _this2.queryAuthRestore = false;
	              callback(result);
	              return false;
	            }

	            if (!_this2.queryAuthRestore) {
	              console.warn('BX.LiveChatRestClient: your auth-token has expired, send query with a new token');
	              _this2.queryAuthRestore = true;

	              _this2.restClient.callBatch(calls, callback, bHaltOnError, sendCallback, logTag);

	              return false;
	            }
	          }
	        }

	        _this2.queryAuthRestore = false;
	        callback(result);
	        return true;
	      };

	      return this.restClient.callBatch(calls, resultCallback, bHaltOnError, sendCallback, logTag);
	    }
	  }]);
	  return WidgetRestClient;
	}();

	/**
	 * Bitrix OpenLines widget
	 * Widget Rest answers (Rest Answer Handler)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */

	var WidgetRestAnswerHandler =
	/*#__PURE__*/
	function (_BaseRestAnswerHandle) {
	  babelHelpers.inherits(WidgetRestAnswerHandler, _BaseRestAnswerHandle);

	  function WidgetRestAnswerHandler() {
	    var _this;

	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, WidgetRestAnswerHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetRestAnswerHandler).call(this, props));
	    _this.widget = props.widget;
	    return _this;
	  }

	  babelHelpers.createClass(WidgetRestAnswerHandler, [{
	    key: "handleImopenlinesWidgetConfigGetSuccess",
	    value: function handleImopenlinesWidgetConfigGetSuccess(data) {
	      this.store.commit('widget/common', {
	        configId: data.configId,
	        configName: data.configName,
	        vote: data.vote,
	        textMessages: data.textMessages,
	        operators: data.operators || [],
	        online: data.online,
	        consentUrl: data.consentUrl,
	        connectors: data.connectors || []
	      });
	      this.store.commit('application/set', {
	        disk: data.disk
	      });
	      this.widget.addLocalize(data.serverVariables);
	      im_tools_localstorage.LocalStorage.set(this.widget.getSiteId(), 0, 'serverVariables', data.serverVariables || {});
	    }
	  }, {
	    key: "handleImopenlinesWidgetUserRegisterSuccess",
	    value: function handleImopenlinesWidgetUserRegisterSuccess(data) {
	      this.widget.restClient.setAuthId(data.hash);
	      var previousData = [];

	      if (typeof this.store.state.messages.collection[this.controller.getChatId()] !== 'undefined') {
	        previousData = this.store.state.messages.collection[this.controller.getChatId()];
	      }

	      this.store.commit('messages/initCollection', {
	        chatId: data.chatId,
	        messages: previousData
	      });
	      this.store.commit('dialogues/initCollection', {
	        dialogId: data.dialogId,
	        fields: {
	          entityType: 'LIVECHAT',
	          type: 'livechat'
	        }
	      });
	      this.store.commit('application/set', {
	        dialog: {
	          chatId: data.chatId,
	          dialogId: 'chat' + data.chatId
	        }
	      });
	    }
	  }, {
	    key: "handleImopenlinesWidgetUserGetSuccess",
	    value: function handleImopenlinesWidgetUserGetSuccess(data) {
	      this.store.commit('widget/user', {
	        id: data.id,
	        hash: data.hash,
	        name: data.name,
	        firstName: data.firstName,
	        lastName: data.lastName,
	        phone: data.phone,
	        avatar: data.avatar,
	        email: data.email,
	        www: data.www,
	        gender: data.gender,
	        position: data.position
	      });
	      this.store.commit('application/set', {
	        common: {
	          userId: data.id
	        }
	      });
	    }
	  }, {
	    key: "handleImopenlinesWidgetDialogGetSuccess",
	    value: function handleImopenlinesWidgetDialogGetSuccess(data) {
	      this.store.commit('messages/initCollection', {
	        chatId: data.chatId
	      });
	      this.store.commit('widget/dialog', {
	        sessionId: data.sessionId,
	        sessionClose: data.sessionClose,
	        userVote: data.userVote,
	        userConsent: data.userConsent,
	        operator: data.operator
	      });
	      this.store.commit('application/set', {
	        dialog: {
	          chatId: data.chatId,
	          dialogId: 'chat' + data.chatId,
	          diskFolderId: data.diskFolderId
	        }
	      });
	    }
	  }, {
	    key: "handleImDialogMessagesGetSuccess",
	    value: function handleImDialogMessagesGetSuccess(data) {
	      if (data.messages && data.messages.length > 0 && !this.widget.isDialogStart()) {
	        this.store.commit('widget/common', {
	          dialogStart: true
	        });
	      }
	    }
	  }, {
	    key: "handleImMessageAddSuccess",
	    value: function handleImMessageAddSuccess(messageId, message) {
	      this.widget.messagesQueue = this.widget.messagesQueue.filter(function (el) {
	        return el.id != message.id;
	      });
	      this.widget.sendEvent({
	        type: SubscriptionType$1.userMessage,
	        data: {
	          id: messageId,
	          text: message.text
	        }
	      });
	    }
	  }, {
	    key: "handleImMessageAddError",
	    value: function handleImMessageAddError(error, message) {
	      this.widget.messagesQueue = this.widget.messagesQueue.filter(function (el) {
	        return el.id != message.id;
	      });
	    }
	  }]);
	  return WidgetRestAnswerHandler;
	}(im_provider_rest.BaseRestAnswerHandler);

	/**
	 * Bitrix OpenLines widget
	 * Widget pull commands (Pull Command Handler)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */

	var WidgetImPullCommandHandler =
	/*#__PURE__*/
	function () {
	  babelHelpers.createClass(WidgetImPullCommandHandler, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'im';
	    }
	  }], [{
	    key: "create",
	    value: function create() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return new this(params);
	    }
	  }]);

	  function WidgetImPullCommandHandler(params) {
	    babelHelpers.classCallCheck(this, WidgetImPullCommandHandler);
	    this.controller = params.controller;
	    this.store = params.store;
	    this.widget = params.widget;
	  }

	  babelHelpers.createClass(WidgetImPullCommandHandler, [{
	    key: "handleMessageChat",
	    value: function handleMessageChat(params, extra, command) {
	      if (params.message.senderId != this.controller.getUserId()) {
	        this.widget.sendEvent({
	          type: SubscriptionType$1.operatorMessage,
	          data: params
	        });

	        if (!this.store.state.widget.common.showed && !this.widget.onceShowed) {
	          this.widget.onceShowed = true;
	          this.widget.open();
	        }
	      }
	    }
	  }]);
	  return WidgetImPullCommandHandler;
	}();

	var WidgetImopenlinesPullCommandHandler =
	/*#__PURE__*/
	function () {
	  babelHelpers.createClass(WidgetImopenlinesPullCommandHandler, null, [{
	    key: "create",
	    value: function create() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return new this(params);
	    }
	  }]);

	  function WidgetImopenlinesPullCommandHandler() {
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, WidgetImopenlinesPullCommandHandler);
	    this.controller = params.controller;
	    this.store = params.store;
	    this.widget = params.widget;
	  }

	  babelHelpers.createClass(WidgetImopenlinesPullCommandHandler, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'imopenlines';
	    }
	  }, {
	    key: "handleSessionStart",
	    value: function handleSessionStart(params, extra, command) {
	      this.store.commit('widget/dialog', {
	        sessionId: params.sessionId,
	        sessionClose: false,
	        userVote: VoteType.none
	      });
	      this.widget.sendEvent({
	        type: SubscriptionType$1.sessionStart,
	        data: {
	          sessionId: params.sessionId
	        }
	      });
	    }
	  }, {
	    key: "handleSessionOperatorChange",
	    value: function handleSessionOperatorChange(params, extra, command) {
	      this.store.commit('widget/dialog', {
	        operator: params.operator
	      });
	      this.widget.sendEvent({
	        type: SubscriptionType$1.sessionOperatorChange,
	        data: {
	          operator: params.operator
	        }
	      });
	    }
	  }, {
	    key: "handleSessionFinish",
	    value: function handleSessionFinish(params, extra, command) {
	      this.store.commit('widget/dialog', {
	        sessionId: params.sessionId,
	        sessionClose: true
	      });
	      this.widget.sendEvent({
	        type: SubscriptionType$1.sessionFinish,
	        data: {
	          sessionId: params.sessionId
	        }
	      });

	      if (!params.spam) {
	        this.store.commit('widget/dialog', {
	          operator: {
	            name: '',
	            firstName: '',
	            lastName: '',
	            workPosition: '',
	            avatar: '',
	            online: false
	          }
	        });
	      }
	    }
	  }]);
	  return WidgetImopenlinesPullCommandHandler;
	}();

	/**
	 * Bitrix OpenLines widget
	 * Widget private interface (base class)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */

	var Widget =
	/*#__PURE__*/
	function () {
	  /* region 01. Initialize and store data */
	  function Widget() {
	    var _this = this;

	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Widget);
	    this.ready = true;
	    this.widgetDataRequested = false;
	    this.offline = false;
	    this.code = params.code || '';
	    this.host = params.host || '';
	    this.language = params.language || 'en';
	    this.copyright = params.copyright !== false;
	    this.copyrightUrl = this.copyright && params.copyrightUrl ? params.copyrightUrl : '';
	    this.buttonInstance = babelHelpers.typeof(params.buttonInstance) === 'object' && params.buttonInstance !== null ? params.buttonInstance : null;
	    this.pageMode = babelHelpers.typeof(params.pageMode) === 'object' && params.pageMode;

	    if (this.pageMode) {
	      this.pageMode.useBitrixLocalize = params.pageMode.useBitrixLocalize === true;
	      this.pageMode.placeholder = document.getElementById(params.pageMode.placeholder);
	    }

	    if (typeof this.code === 'string') {
	      if (this.code.length <= 0) {
	        console.warn("%cLiveChatWidget.constructor: code is not correct (%c".concat(this.code, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        this.ready = false;
	      }
	    }

	    if (typeof this.host === 'string') {
	      if (this.host.length <= 0 || !this.host.startsWith('http')) {
	        console.warn("%cLiveChatWidget.constructor: host is not correct (%c".concat(this.host, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        this.ready = false;
	      }
	    }

	    this.inited = false;
	    this.initEventFired = false;
	    this.restClient = null;
	    this.userRegisterData = {};
	    this.customData = [];
	    this.localize = this.pageMode && this.pageMode.useBitrixLocalize ? window.BX.message : {};

	    if (babelHelpers.typeof(params.localize) === 'object') {
	      this.addLocalize(params.localize);
	    }

	    this.subscribers = {};
	    this.dateFormat = null;
	    this.messagesQueue = [];
	    this.filesQueue = [];
	    this.filesQueueIndex = 0;
	    this.configRequestXhr = null;

	    if (this.pageMode && this.pageMode.placeholder) {
	      this.rootNode = this.pageMode.placeholder;
	    } else {
	      this.rootNode = document.createElement('div');

	      if (document.body.firstChild) {
	        document.body.insertBefore(this.rootNode, document.body.firstChild);
	      } else {
	        document.body.appendChild(this.rootNode);
	      }
	    }

	    this.template = null;
	    window.addEventListener('orientationchange', function () {
	      if (!_this.store) {
	        return;
	      }

	      _this.store.commit('application/set', {
	        device: {
	          orientation: im_utils.Utils.device.getOrientation()
	        }
	      });

	      if (_this.store.state.widget.common.showed && _this.store.state.application.device.type == im_const.DeviceType.mobile && _this.store.state.application.device.orientation == im_const.DeviceOrientation.horizontal) {
	        document.activeElement.blur();
	      }
	    });
	    var serverVariables = im_tools_localstorage.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);

	    if (serverVariables) {
	      this.addLocalize(serverVariables);
	    }

	    var widgetVariables = {
	      common: {
	        host: this.getHost(),
	        pageMode: this.pageMode !== false,
	        copyright: this.copyright,
	        copyrightUrl: this.copyrightUrl
	      },
	      vote: {
	        messageText: this.getLocalize('BX_LIVECHAT_VOTE_TITLE'),
	        messageLike: this.getLocalize('BX_LIVECHAT_VOTE_PLUS_TITLE'),
	        messageDislike: this.getLocalize('BX_LIVECHAT_VOTE_MINUS_TITLE')
	      },
	      textMessages: {
	        bxLivechatOnlineLine1: this.getLocalize('BX_LIVECHAT_ONLINE_LINE_1'),
	        bxLivechatOnlineLine2: this.getLocalize('BX_LIVECHAT_ONLINE_LINE_2'),
	        bxLivechatOffline: this.getLocalize('BX_LIVECHAT_OFFLINE')
	      }
	    };

	    if (params.location && typeof LocationStyle[params.location] !== 'undefined') {
	      widgetVariables.common.location = params.location;
	    }

	    if (im_utils.Utils.types.isPlainObject(params.styles) && (params.styles.backgroundColor || params.styles.iconColor)) {
	      widgetVariables.styles = {};

	      if (params.styles.backgroundColor) {
	        widgetVariables.styles.backgroundColor = params.styles.backgroundColor;
	      }

	      if (params.styles.iconColor) {
	        widgetVariables.styles.iconColor = params.styles.iconColor;
	      }
	    }

	    this.controller = new im_controller.ApplicationController();
	    var applicationVariables = {
	      common: {
	        host: this.getHost(),
	        siteId: this.getSiteId(),
	        languageId: this.language
	      },
	      device: {
	        type: im_utils.Utils.device.isMobile() ? im_const.DeviceType.mobile : im_const.DeviceType.desktop,
	        orientation: im_utils.Utils.device.getOrientation()
	      },
	      dialog: {
	        messageLimit: this.controller.getDefaultMessageLimit()
	      }
	    };
	    new ui_vue_vuex.VuexBuilder().addModel(WidgetModel.create().setVariables(widgetVariables)).addModel(im_model.ApplicationModel.create().setVariables(applicationVariables)).addModel(im_model.MessagesModel.create()).addModel(im_model.DialoguesModel.create().setVariables({
	      host: this.host
	    }).useDatabase(false)).addModel(im_model.UsersModel.create().setVariables({
	      host: this.host,
	      defaultName: this.getLocalize('IM_MESSENGER_MESSAGE_USER_ANONYM')
	    }).useDatabase(false)).addModel(im_model.FilesModel.create().setVariables({
	      host: this.host
	    }).useDatabase(false)).setDatabaseConfig({
	      name: 'imol/widget',
	      type: ui_vue_vuex.VuexBuilder.DatabaseType.localStorage,
	      siteId: this.getSiteId()
	    }).build(function (result) {
	      _this.store = result.store;
	      _this.storeCollector = result.builder;

	      _this.initRestClient();

	      _this.controller.setVuexStore(_this.store);

	      _this.controller.setRestClient(_this.restClient);

	      _this.controller.setPrepareFilesBeforeSaveFunction(_this.prepareFileData.bind(_this));

	      _this.imRestAnswer = im_provider_rest.ImRestAnswerHandler.create({
	        store: _this.store,
	        controller: _this.controller
	      });
	      _this.widgetRestAnswer = WidgetRestAnswerHandler.create({
	        widget: _this,
	        store: _this.store,
	        controller: _this.controller
	      });
	      window.dispatchEvent(new CustomEvent('onBitrixLiveChat', {
	        detail: {
	          widget: _this,
	          widgetCode: _this.code,
	          widgetHost: _this.host
	        }
	      }));

	      if (_this.callStartFlag) {
	        _this.start();
	      }

	      if (_this.pageMode || _this.callOpenFlag) {
	        _this.open();
	      }
	    });
	  }

	  babelHelpers.createClass(Widget, [{
	    key: "initRestClient",
	    value: function initRestClient() {
	      this.restClient = new WidgetRestClient({
	        endpoint: this.host + '/rest'
	      });

	      if (this.isUserRegistered()) {
	        this.restClient.setAuthId(this.getUserHash());
	      } else {
	        this.restClient.setAuthId(RestAuth.guest);
	      }
	    }
	  }, {
	    key: "requestWidgetData",
	    value: function requestWidgetData() {
	      var _this2 = this;

	      if (!this.isReady()) {
	        console.error('LiveChatWidget.start: widget code or host is not specified');
	        return false;
	      }

	      this.widgetDataRequested = true;

	      if (!this.isUserRegistered() && (this.userRegisterData.hash || this.getUserHashCookie())) {
	        this.requestData();
	        this.inited = true;
	        this.fireInitEvent();
	      } else if (this.isConfigDataLoaded() && this.isUserRegistered()) {
	        this.requestData();
	        this.inited = true;
	        this.fireInitEvent();
	      } else {
	        this.restClient.callMethod(RestMethod.widgetConfigGet, {
	          code: this.code
	        }, function (xhr) {
	          _this2.configRequestXhr = xhr;
	        }).then(function (result) {
	          _this2.configRequestXhr = null;

	          _this2.clearError();

	          _this2.executeRestAnswer(RestMethod.widgetConfigGet, result);

	          if (!_this2.inited) {
	            _this2.inited = true;

	            _this2.fireInitEvent();
	          }
	        }).catch(function (result) {
	          _this2.configRequestXhr = null;

	          _this2.setError(result.error().ex.error, result.error().ex.error_description);
	        });

	        if (this.isConfigDataLoaded()) {
	          this.inited = true;
	          this.fireInitEvent();
	        }
	      }
	    }
	  }, {
	    key: "requestData",
	    value: function requestData() {
	      var _this3 = this;

	      if (this.requestDataSend) {
	        return true;
	      }

	      this.requestDataSend = true;

	      if (this.configRequestXhr) {
	        this.configRequestXhr.abort();
	      }

	      var query = babelHelpers.defineProperty({}, RestMethod.widgetConfigGet, [RestMethod.widgetConfigGet, {
	        code: this.code
	      }]);

	      if (this.isUserRegistered()) {
	        query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	          config_id: this.getConfigId(),
	          trace_data: this.getCrmTraceData(),
	          custom_data: this.getCustomData()
	        }];
	        query[im_const.RestMethod.imChatGet] = [im_const.RestMethod.imChatGet, {
	          dialog_id: '$result[' + RestMethod.widgetDialogGet + '][dialogId]'
	        }];
	        query[im_const.RestMethod.imDialogMessagesGet] = [im_const.RestMethod.imDialogMessagesGet, {
	          chat_id: '$result[' + RestMethod.widgetDialogGet + '][chatId]',
	          limit: this.controller.getRequestMessageLimit(),
	          convert_text: 'Y'
	        }];
	      } else {
	        query[RestMethod.widgetUserRegister] = [RestMethod.widgetUserRegister, babelHelpers.objectSpread({
	          config_id: '$result[' + RestMethod.widgetConfigGet + '][configId]'
	        }, this.getUserRegisterFields())];
	        query[im_const.RestMethod.imChatGet] = [im_const.RestMethod.imChatGet, {
	          dialog_id: '$result[' + RestMethod.widgetUserRegister + '][dialogId]'
	        }];

	        if (this.userRegisterData.hash || this.getUserHashCookie()) {
	          query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	            config_id: '$result[' + RestMethod.widgetConfigGet + '][configId]',
	            trace_data: this.getCrmTraceData(),
	            custom_data: this.getCustomData()
	          }];
	          query[im_const.RestMethod.imDialogMessagesGet] = [im_const.RestMethod.imDialogMessagesGet, {
	            chat_id: '$result[' + RestMethod.widgetDialogGet + '][chatId]',
	            limit: this.controller.getRequestMessageLimit(),
	            convert_text: 'Y'
	          }];
	        }

	        if (this.isUserAgreeConsent()) {
	          query[RestMethod.widgetUserConsentApply] = [RestMethod.widgetUserConsentApply, {
	            config_id: '$result[' + RestMethod.widgetConfigGet + '][configId]',
	            consent_url: location.href
	          }];
	        }
	      }

	      query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
	      query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {
	        'CACHE': 'N'
	      }];
	      query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];
	      this.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this3.requestDataSend = false;

	          _this3.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        var configGet = response[RestMethod.widgetConfigGet];

	        if (configGet && configGet.error()) {
	          _this3.requestDataSend = false;

	          _this3.setError(configGet.error().ex.error, configGet.error().ex.error_description);

	          return false;
	        }

	        _this3.executeRestAnswer(RestMethod.widgetConfigGet, configGet);

	        var userGetResult = response[RestMethod.widgetUserGet];

	        if (userGetResult.error()) {
	          _this3.requestDataSend = false;

	          _this3.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);

	          return false;
	        }

	        _this3.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

	        var chatGetResult = response[im_const.RestMethod.imChatGet];

	        if (chatGetResult.error()) {
	          _this3.requestDataSend = false;

	          _this3.setError(chatGetResult.error().ex.error, chatGetResult.error().ex.error_description);

	          return false;
	        }

	        _this3.executeRestAnswer(im_const.RestMethod.imChatGet, chatGetResult);

	        var dialogGetResult = response[RestMethod.widgetDialogGet];

	        if (dialogGetResult) {
	          if (dialogGetResult.error()) {
	            _this3.requestDataSend = false;

	            _this3.setError(dialogGetResult.error().ex.error, dialogGetResult.error().ex.error_description);

	            return false;
	          }

	          _this3.executeRestAnswer(RestMethod.widgetDialogGet, dialogGetResult);
	        }

	        var dialogMessagesGetResult = response[im_const.RestMethod.imDialogMessagesGet];

	        if (dialogMessagesGetResult) {
	          if (dialogMessagesGetResult.error()) {
	            _this3.requestDataSend = false;

	            _this3.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);

	            return false;
	          }

	          _this3.executeRestAnswer(im_const.RestMethod.imDialogMessagesGet, dialogMessagesGetResult);
	        }

	        var userRegisterResult = response[RestMethod.widgetUserRegister];

	        if (userRegisterResult) {
	          if (userRegisterResult.error()) {
	            _this3.requestDataSend = false;

	            _this3.setError(userRegisterResult.error().ex.error, userRegisterResult.error().ex.error_description);

	            return false;
	          }

	          _this3.executeRestAnswer(RestMethod.widgetUserRegister, userRegisterResult);
	        }

	        var timeShift = 0;
	        var serverTimeResult = response[RestMethod.pullServerTime];

	        if (serverTimeResult && !serverTimeResult.error()) {
	          timeShift = Math.floor((new Date().getTime() - new Date(serverTimeResult.data()).getTime()) / 1000);
	        }

	        var config = null;
	        var pullConfigResult = response[RestMethod.pullConfigGet];

	        if (pullConfigResult && !pullConfigResult.error()) {
	          config = pullConfigResult.data();
	          config.server.timeShift = timeShift;
	        }

	        _this3.startPullClient(config).then(function () {
	          _this3.processSendMessages();

	          _this3.processSendFiles();
	        }).catch(function (error) {
	          _this3.setError(error.ex.error, error.ex.error_description);
	        });

	        _this3.requestDataSend = false;
	      }, false, false, im_utils.Utils.getLogTrackingParams({
	        name: 'widget.init.config',
	        dialog: this.getDialogData()
	      }));
	    }
	  }, {
	    key: "executeRestAnswer",
	    value: function executeRestAnswer(command, result, extra) {
	      this.imRestAnswer.execute(command, result, extra);
	      this.widgetRestAnswer.execute(command, result, extra);
	    }
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      var _this4 = this;

	      if (Cookie.get(null, 'BITRIX_LIVECHAT_AUTH')) {
	        return files;
	      }

	      return files.map(function (file) {
	        var hash = (window.md5 || main_md5.md5)(_this4.getUserId() + '|' + file.id + '|' + _this4.getUserHash());

	        var urlParam = 'livechat_auth_id=' + hash + '&livechat_user_id=' + _this4.getUserId();

	        if (file.urlPreview) {
	          file.urlPreview = file.urlPreview + '&' + urlParam;
	        }

	        if (file.urlShow) {
	          file.urlShow = file.urlShow + '&' + urlParam;
	        }

	        if (file.urlDownload) {
	          file.urlDownload = file.urlDownload + '&' + urlParam;
	        }

	        return file;
	      });
	    }
	  }, {
	    key: "checkBrowserVersion",
	    value: function checkBrowserVersion() {
	      if (im_utils.Utils.platform.isIos()) {
	        var version = im_utils.Utils.platform.getIosVersion();

	        if (version && version <= 10) {
	          return false;
	        }
	      }

	      return true;
	    }
	    /* endregion 01. Initialize and store data */

	    /* region 02. Push & Pull */

	  }, {
	    key: "startPullClient",
	    value: function startPullClient(config) {
	      var _this5 = this;

	      var promise = new BX.Promise();

	      if (this.pullClient) {
	        if (!this.pullClient.isConnected()) {
	          this.pullClient.scheduleReconnect();
	        }

	        promise.resolve(true);
	        return promise;
	      }

	      if (!this.getUserId() || !this.getSiteId() || !this.restClient) {
	        promise.reject({
	          ex: {
	            error: 'WIDGET_NOT_LOADED',
	            error_description: 'Widget is not loaded.'
	          }
	        });
	        return promise;
	      }

	      this.pullClient = new pull_client.PullClient({
	        serverEnabled: true,
	        userId: this.getUserId(),
	        siteId: this.getSiteId(),
	        restClient: this.restClient,
	        configTimestamp: config ? config.server.config_timestamp : 0,
	        skipCheckRevision: true
	      });
	      this.pullClient.subscribe(new im_provider_pull.ImPullCommandHandler({
	        store: this.store,
	        controller: this.controller
	      }));
	      this.pullClient.subscribe(new WidgetImPullCommandHandler({
	        store: this.store,
	        controller: this.controller,
	        widget: this
	      }));
	      this.pullClient.subscribe(new WidgetImopenlinesPullCommandHandler({
	        store: this.store,
	        controller: this.controller,
	        widget: this
	      }));
	      this.pullClient.subscribe({
	        type: pull_client.PullClient.SubscriptionType.Status,
	        callback: this.eventStatusInteraction.bind(this)
	      });
	      this.pullConnectedFirstTime = this.pullClient.subscribe({
	        type: pull_client.PullClient.SubscriptionType.Status,
	        callback: function callback(result) {
	          if (result.status == pull_client.PullClient.PullStatus.Online) {
	            promise.resolve(true);

	            _this5.pullConnectedFirstTime();
	          }
	        }
	      });

	      if (this.template) {
	        this.template.$root.$bitrixPullClient = this.pullClient;
	        this.template.$root.$emit('onBitrixPullClientInited', this.pullClient);
	      }

	      this.pullClient.start(config).catch(function () {
	        promise.reject({
	          ex: {
	            error: 'PULL_CONNECTION_ERROR',
	            error_description: 'Pull is not connected.'
	          }
	        });
	      });
	      return promise;
	    }
	  }, {
	    key: "stopPullClient",
	    value: function stopPullClient() {
	      if (this.pullClient) {
	        this.pullClient.stop(pull_client.PullClient.CloseReasons.MANUAL, 'Closed manually');
	      }
	    }
	  }, {
	    key: "recoverPullConnection",
	    value: function recoverPullConnection() {
	      // this.pullClient.session.mid = 0; // TODO specially for disable pull history, remove after recode im
	      this.pullClient.restart(pull_client.PullClient.CloseReasons.MANUAL, 'Restart after click by connection status button.');
	    }
	  }, {
	    key: "eventStatusInteraction",
	    value: function eventStatusInteraction(data) {
	      var _this6 = this;

	      if (data.status === pull_client.PullClient.PullStatus.Online) {
	        this.offline = false;

	        if (this.pullRequestMessage) {
	          this.getDialogUnread().then(function () {
	            _this6.readMessage();

	            _this6.processSendMessages();

	            _this6.processSendFiles();
	          });
	          this.pullRequestMessage = false;
	        } else {
	          this.readMessage();
	          this.processSendMessages();
	          this.processSendFiles();
	        }
	      } else if (data.status === pull_client.PullClient.PullStatus.Offline) {
	        this.pullRequestMessage = true;
	        this.offline = true;
	      }
	    }
	    /* endregion 02. Push & Pull */

	    /* region 03. Template engine */

	  }, {
	    key: "attachTemplate",
	    value: function attachTemplate() {
	      if (this.template) {
	        this.store.commit('widget/common', {
	          showed: true
	        });
	        return true;
	      }

	      this.rootNode.innerHTML = '';
	      this.rootNode.appendChild(document.createElement('div'));
	      var widgetContext = this;
	      var controller = this.controller;
	      var restClient = this.restClient;
	      var pullClient = this.pullClient || null;
	      this.template = ui_vue.Vue.create({
	        el: this.rootNode.firstChild,
	        store: this.store,
	        template: '<bx-livechat/>',
	        beforeCreate: function beforeCreate() {
	          this.$bitrixWidget = widgetContext;
	          this.$bitrixController = controller;
	          this.$bitrixRestClient = restClient;
	          this.$bitrixPullClient = pullClient;
	          this.$bitrixMessages = widgetContext.localize;
	          widgetContext.sendEvent({
	            type: SubscriptionType$1.widgetOpen,
	            data: {}
	          });
	        },
	        destroyed: function destroyed() {
	          widgetContext.sendEvent({
	            type: SubscriptionType$1.widgetClose,
	            data: {}
	          });
	          this.$bitrixWidget.template = null;
	          this.$bitrixWidget.templateAttached = false;
	          this.$bitrixWidget.rootNode.innerHTML = '';
	          this.$bitrixWidget = null;
	          this.$bitrixRestClient = null;
	          this.$bitrixPullClient = null;
	          this.$bitrixMessages = null;
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "detachTemplate",
	    value: function detachTemplate() {
	      if (!this.template) {
	        return true;
	      }

	      this.template.$destroy();
	      return true;
	    }
	  }, {
	    key: "mutateTemplateComponent",
	    value: function mutateTemplateComponent(id, params) {
	      return ui_vue.Vue.mutateComponent(id, params);
	    }
	    /* endregion 03. Template engine */

	    /* region 04. Rest methods */

	  }, {
	    key: "addMessage",
	    value: function addMessage() {
	      var _this7 = this;

	      var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';

	      if (!text) {
	        return false;
	      }

	      im_tools_logger.Logger.warn('addMessage', text);

	      if (!this.controller.isUnreadMessagesLoaded()) {
	        this.sendMessage({
	          id: 0,
	          text: text
	        });
	        this.processSendMessages();
	        return true;
	      }

	      this.store.dispatch('messages/add', {
	        chatId: this.getChatId(),
	        authorId: this.getUserId(),
	        text: text
	      }).then(function (messageId) {
	        if (!_this7.isDialogStart()) {
	          _this7.store.commit('widget/common', {
	            dialogStart: true
	          });
	        }

	        _this7.messagesQueue.push({
	          id: messageId,
	          text: text,
	          sending: false
	        });

	        if (_this7.getChatId()) {
	          _this7.processSendMessages();
	        } else {
	          _this7.requestData();
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "addFile",
	    value: function addFile(fileInput) {
	      if (!fileInput) {
	        return false;
	      }

	      im_tools_logger.Logger.warn('addFile', fileInput.files[0].name, fileInput.files[0].size);

	      if (!this.isDialogStart()) {
	        this.store.commit('widget/common', {
	          dialogStart: true
	        });
	      }

	      this.filesQueue.push({
	        id: this.filesQueueIndex,
	        fileInput: fileInput
	      });
	      this.filesQueueIndex++;

	      if (this.getChatId()) {
	        this.processSendFiles();
	      } else {
	        this.requestData();
	      }

	      return true;
	    }
	  }, {
	    key: "processSendMessages",
	    value: function processSendMessages() {
	      var _this8 = this;

	      if (this.offline) {
	        return false;
	      }

	      this.messagesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;

	        _this8.sendMessage(element);
	      });
	      return true;
	    }
	  }, {
	    key: "processSendFiles",
	    value: function processSendFiles() {
	      var _this9 = this;

	      if (this.offline) {
	        return false;
	      }

	      this.filesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;

	        _this9.sendFile(element);
	      });
	      return true;
	    }
	  }, {
	    key: "sendMessage",
	    value: function sendMessage(message) {
	      var _this10 = this;

	      this.controller.stopWriting();
	      this.restClient.callMethod(im_const.RestMethod.imMessageAdd, {
	        'TEMP_ID': message.id,
	        'CHAT_ID': this.getChatId(),
	        'MESSAGE': message.text
	      }, null, null, im_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageAdd,
	        data: {
	          timMessageType: 'text'
	        },
	        dialog: this.getDialogData()
	      })).then(function (response) {
	        _this10.executeRestAnswer(im_const.RestMethod.imMessageAdd, response, message);
	      }).catch(function (error) {
	        _this10.executeRestAnswer(im_const.RestMethod.imMessageAdd, error, message);
	      });
	    }
	  }, {
	    key: "sendFile",
	    value: function sendFile(file) {
	      var _this11 = this;

	      var fileName = file.fileInput.files[0].name;
	      var fileType = 'file'; // TODO set type by fileInput type

	      var diskFolderId = this.getDiskFolderId();
	      var query = {};

	      if (diskFolderId) {
	        query[im_const.RestMethod.imDiskFileUpload] = [im_const.RestMethod.imDiskFileUpload, {
	          id: diskFolderId,
	          data: {
	            NAME: fileName
	          },
	          fileContent: file.fileInput,
	          generateUniqueName: true
	        }];
	      } else {
	        query[im_const.RestMethod.imDiskFolderGet] = [im_const.RestMethod.imDiskFolderGet, {
	          chat_id: this.getChatId()
	        }];
	        query[im_const.RestMethod.imDiskFileUpload] = [im_const.RestMethod.imDiskFileUpload, {
	          id: '$result[' + im_const.RestMethod.imDiskFolderGet + '][ID]',
	          data: {
	            NAME: fileName
	          },
	          fileContent: file.fileInput,
	          generateUniqueName: true
	        }];
	      }

	      query[im_const.RestMethod.imDiskFileCommit] = [im_const.RestMethod.imDiskFileCommit, {
	        chat_id: this.getChatId(),
	        upload_id: '$result[' + im_const.RestMethod.imDiskFileUpload + '][ID]'
	      }];
	      this.store.commit('widget/common', {
	        uploadFilePlus: true
	      }); // TODO remove this after create new file-loader

	      this.restClient.callBatch(query, function (response) {
	        _this11.store.commit('widget/common', {
	          uploadFileMinus: true
	        }); // TODO  remove this after create new file-loader


	        if (!response) {
	          _this11.requestDataSend = false;

	          _this11.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        if (!diskFolderId) {
	          var diskFolderGet = response[im_const.RestMethod.imDiskFolderGet];

	          if (diskFolderGet && diskFolderGet.error()) {
	            console.warn(diskFolderGet.error().ex.error, diskFolderGet.error().ex.error_description);
	            return false;
	          }

	          _this11.executeRestAnswer(im_const.RestMethod.imDiskFolderGet, diskFolderGet);
	        }

	        var diskFileUpload = response[im_const.RestMethod.imDiskFileUpload];

	        if (diskFileUpload && diskFileUpload.error()) {
	          console.warn(diskFileUpload.error().ex.error, diskFileUpload.error().ex.error_description);
	          return false;
	        } else {
	          im_tools_logger.Logger.log('upload success', diskFileUpload.data());
	        }

	        var diskFileCommit = response[im_const.RestMethod.imDiskFileCommit];

	        if (diskFileCommit && diskFileCommit.error()) {
	          console.warn(diskFileCommit.error().ex.error, diskFileCommit.error().ex.error_description);
	          return false;
	        } else {
	          im_tools_logger.Logger.log('commit success', diskFileCommit.data());
	        }
	      }, false, false, im_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imDiskFileCommit,
	        data: {
	          timMessageType: fileType
	        },
	        dialog: this.getDialogData()
	      }));
	      this.filesQueue = this.filesQueue.filter(function (el) {
	        return el.id != file.id;
	      });
	    }
	  }, {
	    key: "getDialogHistory",
	    value: function getDialogHistory(lastId) {
	      var _this12 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.getRequestMessageLimit();
	      this.restClient.callMethod(im_const.RestMethod.imDialogMessagesGet, {
	        'CHAT_ID': this.getChatId(),
	        'LAST_ID': lastId,
	        'LIMIT': limit,
	        'CONVERT_TEXT': 'Y'
	      }).then(function (result) {
	        _this12.executeRestAnswer(im_const.RestMethod.imDialogMessagesGet, result);

	        _this12.template.$emit('onDialogRequestHistoryResult', {
	          count: result.data().messages.length
	        });
	      }).catch(function (result) {
	        _this12.template.$emit('onDialogRequestHistoryResult', {
	          error: result.error().ex
	        });
	      });
	    }
	  }, {
	    key: "getDialogUnread",
	    value: function getDialogUnread(lastId) {
	      var _query2,
	          _this13 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.getRequestMessageLimit();
	      var promise = new BX.Promise();

	      if (!lastId) {
	        lastId = this.store.getters['messages/getLastId'](this.getChatId());
	      }

	      if (!lastId) {
	        this.template.$emit('onDialogRequestUnreadResult', {
	          error: {
	            error: 'LAST_ID_EMPTY',
	            error_description: 'LastId is empty.'
	          }
	        });
	        promise.reject();
	        return promise;
	      }

	      var query = (_query2 = {}, babelHelpers.defineProperty(_query2, im_const.RestMethod.imChatGet, [im_const.RestMethod.imChatGet, {
	        dialog_id: this.getDialogId()
	      }]), babelHelpers.defineProperty(_query2, im_const.RestMethod.imDialogMessagesUnread, [im_const.RestMethod.imDialogMessagesGet, {
	        chat_id: this.getChatId(),
	        first_id: lastId,
	        limit: limit,
	        convert_text: 'Y'
	      }]), _query2);
	      this.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this13.template.$emit('onDialogRequestUnreadResult', {
	            error: {
	              error: 'EMPTY_RESPONSE',
	              error_description: 'Server returned an empty response.'
	            }
	          });

	          promise.reject();
	          return false;
	        }

	        var chatGetResult = response[im_const.RestMethod.imChatGet];

	        if (!chatGetResult.error()) {
	          _this13.executeRestAnswer(im_const.RestMethod.imChatGet, chatGetResult);
	        }

	        var dialogMessageUnread = response[im_const.RestMethod.imDialogMessagesUnread];

	        if (dialogMessageUnread.error()) {
	          _this13.template.$emit('onDialogRequestUnreadResult', {
	            error: dialogMessageUnread.error().ex
	          });
	        } else {
	          _this13.executeRestAnswer(im_const.RestMethod.imDialogMessagesUnread, dialogMessageUnread);

	          _this13.template.$emit('onDialogRequestUnreadResult', {
	            count: dialogMessageUnread.data().messages.length
	          });
	        }

	        promise.fulfill(response);
	      }, false, false, im_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imDialogMessagesUnread,
	        dialog: this.getDialogData()
	      }));
	      return promise;
	    }
	  }, {
	    key: "retrySendMessage",
	    value: function retrySendMessage(message) {
	      if (this.messagesQueue.find(function (el) {
	        return el.id == message.id;
	      })) {
	        return false;
	      }

	      this.messagesQueue.push({
	        id: message.id,
	        text: message.text,
	        sending: false
	      });
	      this.controller.setSendingMessageFlag(message.id);
	      this.processSendMessages();
	    }
	  }, {
	    key: "readMessage",
	    value: function readMessage(messageId) {
	      if (this.offline) {
	        return false;
	      }

	      return this.controller.readMessage(messageId);
	    }
	  }, {
	    key: "sendDialogVote",
	    value: function sendDialogVote(result) {
	      var _this14 = this;

	      if (!this.getSessionId()) {
	        return false;
	      }

	      this.restClient.callMethod(RestMethod.widgetVoteSend, {
	        'SESSION_ID': this.getSessionId(),
	        'ACTION': result
	      }).catch(function (result) {
	        _this14.store.commit('widget/dialog', {
	          userVote: VoteType.none
	        });
	      });
	      this.sendEvent({
	        type: SubscriptionType$1.userVote,
	        data: {
	          vote: result
	        }
	      });
	    }
	  }, {
	    key: "sendForm",
	    value: function sendForm(type, fields) {
	      var _query3,
	          _this15 = this;

	      im_tools_logger.Logger.info('LiveChatWidgetPrivate.sendForm:', type, fields);
	      var query = (_query3 = {}, babelHelpers.defineProperty(_query3, RestMethod.widgetFormSend, [RestMethod.widgetFormSend, {
	        'CHAT_ID': this.getChatId(),
	        'FORM': type.toUpperCase(),
	        'FIELDS': fields
	      }]), babelHelpers.defineProperty(_query3, RestMethod.widgetUserGet, [RestMethod.widgetUserGet, {}]), _query3);
	      this.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this15.requestDataSend = false;

	          _this15.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        var userGetResult = response[RestMethod.widgetUserGet];

	        if (userGetResult.error()) {
	          _this15.requestDataSend = false;

	          _this15.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);

	          return false;
	        }

	        _this15.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

	        _this15.sendEvent({
	          type: SubscriptionType$1.userForm,
	          data: {
	            form: type,
	            fields: fields
	          }
	        });
	      }, false, false, im_utils.Utils.getLogTrackingParams({
	        name: RestMethod.widgetUserGet,
	        dialog: this.getDialogData()
	      }));
	    }
	  }, {
	    key: "sendConsentDecision",
	    value: function sendConsentDecision(result) {
	      result = result === true;
	      this.store.commit('widget/dialog', {
	        userConsent: result
	      });

	      if (result && this.isUserRegistered()) {
	        this.restClient.callMethod(RestMethod.widgetUserConsentApply, {
	          config_id: this.getConfigId(),
	          consent_url: location.href
	        });
	      }
	    }
	    /* endregion 05. Templates and template interaction */

	    /* region 05. Widget interaction and utils */

	  }, {
	    key: "start",
	    value: function start() {
	      if (!this.store) {
	        this.callStartFlag = true;
	        return true;
	      }

	      if (this.isSessionActive()) {
	        this.requestWidgetData();
	      }

	      return true;
	    }
	  }, {
	    key: "open",
	    value: function open() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      clearTimeout(this.openTimeout);

	      if (!this.store) {
	        this.callOpenFlag = true;
	        return true;
	      }

	      if (!params.openFromButton && this.buttonInstance) {
	        this.buttonInstance.wm.showById('openline_livechat');
	      }

	      if (!this.checkBrowserVersion()) {
	        this.setError('OLD_BROWSER_LOCALIZED', this.localize.BX_LIVECHAT_OLD_BROWSER);
	      } else if (im_utils.Utils.versionCompare(ui_vue.Vue.version(), '2.1') < 0) {
	        alert(this.localize.BX_LIVECHAT_OLD_VUE);
	        console.error("LiveChatWidget.error: OLD_VUE_VERSION (".concat(this.localize.BX_LIVECHAT_OLD_VUE_DEV.replace('#CURRENT_VERSION#', ui_vue.Vue.version()), ")"));
	        return false;
	      } else if (!this.isWidgetDataRequested()) {
	        this.requestWidgetData();
	      }

	      this.attachTemplate();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (this.pageMode) {
	        return false;
	      }

	      if (this.buttonInstance) {
	        this.buttonInstance.onWidgetClose();
	      }

	      this.detachTemplate();
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(params) {
	      if (!this.store) {
	        console.error('LiveChatWidget.showNotification: method can be called after fired event - onBitrixLiveChat');
	        return false;
	      } // TODO show popup notification and set badge on button
	      // operatorName
	      // notificationText
	      // counter

	    }
	  }, {
	    key: "fireInitEvent",
	    value: function fireInitEvent() {
	      if (this.initEventFired) {
	        return true;
	      }

	      this.sendEvent({
	        type: SubscriptionType$1.configLoaded,
	        data: {}
	      });

	      if (this.store.state.widget.common.reopen) {
	        this.open();
	      }

	      this.initEventFired = true;
	      return true;
	    }
	  }, {
	    key: "isReady",
	    value: function isReady() {
	      return this.ready;
	    }
	  }, {
	    key: "isInited",
	    value: function isInited() {
	      return this.inited;
	    }
	  }, {
	    key: "isUserRegistered",
	    value: function isUserRegistered() {
	      return !!this.getUserHash();
	    }
	  }, {
	    key: "isConfigDataLoaded",
	    value: function isConfigDataLoaded() {
	      return this.store.state.widget.common.configId;
	    }
	  }, {
	    key: "isWidgetDataRequested",
	    value: function isWidgetDataRequested() {
	      return this.widgetDataRequested;
	    }
	  }, {
	    key: "isChatLoaded",
	    value: function isChatLoaded() {
	      return this.store.state.application.dialog.chatId > 0;
	    }
	  }, {
	    key: "isSessionActive",
	    value: function isSessionActive() {
	      return !this.store.state.widget.dialog.sessionClose;
	    }
	  }, {
	    key: "isUserAgreeConsent",
	    value: function isUserAgreeConsent() {
	      return this.store.state.widget.dialog.userConsent;
	    }
	  }, {
	    key: "getCrmTraceData",
	    value: function getCrmTraceData() {
	      var traceData = '';

	      if (!this.buttonInstance) {
	        return traceData;
	      }

	      if (typeof this.buttonInstance.getTrace !== 'function') {
	        traceData = this.buttonInstance.getTrace();
	      } else if (typeof this.buttonInstance.b24Tracker !== 'undefined' && typeof this.buttonInstance.b24Tracker.guest !== 'undefined') {
	        traceData = this.buttonInstance.b24Tracker.guest.getTrace();
	      }

	      return traceData;
	    }
	  }, {
	    key: "getCustomData",
	    value: function getCustomData() {
	      var customData = [];

	      if (this.customData.length > 0) {
	        customData = this.customData;
	      } else {
	        customData = [{
	          MESSAGE: this.localize.BX_LIVECHAT_EXTRA_SITE + ': ' + location.href
	        }];
	      }

	      return JSON.stringify(customData);
	    }
	  }, {
	    key: "isUserLoaded",
	    value: function isUserLoaded() {
	      return this.store.state.widget.user.id > 0;
	    }
	  }, {
	    key: "getSiteId",
	    value: function getSiteId() {
	      return this.host.replace(/(http.?:\/\/)|([:.\\\/])/mg, "") + this.code;
	    }
	  }, {
	    key: "getHost",
	    value: function getHost() {
	      return this.host;
	    }
	  }, {
	    key: "getConfigId",
	    value: function getConfigId() {
	      return this.store.state.widget.common.configId;
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return this.store.state.application.dialog.chatId;
	    }
	  }, {
	    key: "isDialogStart",
	    value: function isDialogStart() {
	      return this.store.state.widget.common.dialogStart;
	    }
	  }, {
	    key: "getDialogId",
	    value: function getDialogId() {
	      return this.store.state.application.dialog.dialogId;
	    }
	  }, {
	    key: "getDialogData",
	    value: function getDialogData() {
	      var dialogId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.getDialogId();
	      return this.store.state.dialogues.collection[dialogId];
	    }
	  }, {
	    key: "getDiskFolderId",
	    value: function getDiskFolderId() {
	      return this.store.state.application.dialog.diskFolderId;
	    }
	  }, {
	    key: "getSessionId",
	    value: function getSessionId() {
	      return this.store.state.widget.dialog.sessionId;
	    }
	  }, {
	    key: "getUserHash",
	    value: function getUserHash() {
	      return this.store.state.widget.user.hash;
	    }
	  }, {
	    key: "getUserHashCookie",
	    value: function getUserHashCookie() {
	      var userHash = '';
	      var cookie = Cookie.get(null, 'LIVECHAT_HASH');

	      if (typeof cookie === 'string' && cookie.match(/^[a-f0-9]{32}$/)) {
	        userHash = cookie;
	      } else {
	        var _cookie = Cookie.get(this.getSiteId(), 'LIVECHAT_HASH');

	        if (typeof _cookie === 'string' && _cookie.match(/^[a-f0-9]{32}$/)) {
	          userHash = _cookie;
	        }
	      }

	      return userHash;
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      return this.store.state.widget.user.id;
	    }
	  }, {
	    key: "getUserData",
	    value: function getUserData() {
	      if (!this.store) {
	        console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
	        return false;
	      }

	      return this.store.state.widget.user;
	    }
	  }, {
	    key: "getUserRegisterFields",
	    value: function getUserRegisterFields() {
	      return {
	        'name': this.userRegisterData.name || '',
	        'last_name': this.userRegisterData.lastName || '',
	        'avatar': this.userRegisterData.avatar || '',
	        'email': this.userRegisterData.email || '',
	        'www': this.userRegisterData.www || '',
	        'gender': this.userRegisterData.gender || '',
	        'position': this.userRegisterData.position || '',
	        'user_hash': this.userRegisterData.hash || '',
	        'consent_url': this.store.state.widget.common.consentUrl ? location.href : '',
	        'trace_data': this.getCrmTraceData(),
	        'custom_data': this.getCustomData()
	      };
	    }
	  }, {
	    key: "getWidgetLocationCode",
	    value: function getWidgetLocationCode() {
	      return LocationStyle[this.store.state.widget.common.location];
	    }
	  }, {
	    key: "setUserRegisterData",
	    value: function setUserRegisterData(params) {
	      if (!this.store) {
	        console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
	        return false;
	      }

	      var validUserFields = ['hash', 'name', 'lastName', 'avatar', 'email', 'www', 'gender', 'position'];

	      if (!im_utils.Utils.types.isPlainObject(params)) {
	        console.error("%cLiveChatWidget.setUserData: params is not a object", "color: black;");
	        return false;
	      }

	      for (var field in this.userRegisterData) {
	        if (!this.userRegisterData.hasOwnProperty(field)) {
	          continue;
	        }

	        if (!params[field]) {
	          delete this.userRegisterData[field];
	        }
	      }

	      for (var _field in params) {
	        if (!params.hasOwnProperty(_field)) {
	          continue;
	        }

	        if (validUserFields.indexOf(_field) === -1) {
	          console.warn("%cLiveChatWidget.setUserData: user field is not set, because you are trying to set an unknown field (%c".concat(_field, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	          continue;
	        }

	        this.userRegisterData[_field] = params[_field];
	      }

	      if (this.userRegisterData.hash && this.getUserHash() && this.userRegisterData.hash != this.getUserHash()) {
	        this.setNewAuthToken(this.userRegisterData.hash);
	      }
	    }
	  }, {
	    key: "setNewAuthToken",
	    value: function setNewAuthToken() {
	      var authToken = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var siteId = this.getSiteId();
	      this.storeCollector.clearModelState();
	      Cookie.set(null, 'LIVECHAT_HASH', '', {
	        expires: 365 * 86400,
	        path: '/'
	      });
	      this.restClient.setAuthId(RestAuth.guest, authToken);
	    }
	  }, {
	    key: "setCustomData",
	    value: function setCustomData(params) {
	      if (!this.store) {
	        console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
	        return false;
	      }

	      var result = [];

	      if (params instanceof Array) {
	        params.forEach(function (element) {
	          if (element && babelHelpers.typeof(element) === 'object') {
	            result.push(element);
	          }
	        });

	        if (result.length <= 0) {
	          console.error('LiveChatWidget.setCustomData: params is empty');
	          return false;
	        }
	      } else {
	        if (!params) {
	          return false;
	        }

	        result = [{
	          'MESSAGE': params
	        }];
	      }

	      this.customData = this.customData.concat(result);
	      return true;
	    }
	  }, {
	    key: "setError",
	    value: function setError() {
	      var code = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var description = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      console.error("LiveChatWidget.error: ".concat(code, " (").concat(description, ")"));
	      var localizeDescription = '';

	      if (code == 'LIVECHAT_AUTH_FAILED') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_AUTH_FAILED').replace('#LINK_START#', '<a href="javascript:void();" onclick="location.reload()">').replace('#LINK_END#', '</a>');
	        this.setNewAuthToken();
	      } else if (code == 'LIVECHAT_AUTH_PORTAL_USER') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_PORTAL_USER_NEW').replace('#LINK_START#', '<a href="' + this.host + '">').replace('#LINK_END#', '</a>');
	      } else if (code.endsWith('LOCALIZED')) {
	        localizeDescription = description;
	      }

	      this.store.commit('application/set', {
	        error: {
	          active: true,
	          code: code,
	          description: localizeDescription
	        }
	      });
	    }
	  }, {
	    key: "clearError",
	    value: function clearError() {
	      this.store.commit('application/set', {
	        error: {
	          active: false,
	          code: '',
	          description: ''
	        }
	      });
	    }
	    /**
	     *
	     * @param params {Object}
	     * @returns {Function|Boolean} - Unsubscribe callback function or False
	     */

	  }, {
	    key: "subscribe",
	    value: function subscribe(params) {
	      if (!im_utils.Utils.types.isPlainObject(params)) {
	        console.error("%cLiveChatWidget.subscribe: params is not a object", "color: black;");
	        return false;
	      }

	      if (!SubscriptionTypeCheck.includes(params.type)) {
	        console.error("%cLiveChatWidget.subscribe: subscription type is not correct (%c".concat(params.type, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }

	      if (typeof params.callback !== 'function') {
	        console.error("%cLiveChatWidget.subscribe: callback is not a function (%c".concat(babelHelpers.typeof(params.callback), "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }

	      if (typeof this.subscribers[params.type] === 'undefined') {
	        this.subscribers[params.type] = [];
	      }

	      this.subscribers[params.type].push(params.callback);
	      return function () {
	        this.subscribers[params.type] = this.subscribers[params.type].filter(function (element) {
	          return element !== params.callback;
	        });
	      }.bind(this);
	    }
	    /**
	     *
	     * @param params {Object}
	     * @returns {boolean}
	     */

	  }, {
	    key: "sendEvent",
	    value: function sendEvent(params) {
	      params = params || {};

	      if (!params.type) {
	        return false;
	      }

	      if (babelHelpers.typeof(params.data) !== 'object' || !params.data) {
	        params.data = {};
	      }

	      if (this.subscribers[params.type] instanceof Array && this.subscribers[params.type].length > 0) {
	        this.subscribers[params.type].forEach(function (callback) {
	          return callback(params.data);
	        });
	      }

	      if (this.subscribers[SubscriptionType$1.every] instanceof Array && this.subscribers[SubscriptionType$1.every].length > 0) {
	        this.subscribers[SubscriptionType$1.every].forEach(function (callback) {
	          return callback({
	            type: params.type,
	            data: params.data
	          });
	        });
	      }

	      return true;
	    }
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      if (babelHelpers.typeof(phrases) !== "object" || !phrases) {
	        return false;
	      }

	      for (var name in phrases) {
	        if (phrases.hasOwnProperty(name)) {
	          this.localize[name] = phrases[name];
	        }
	      }

	      return true;
	    }
	  }, {
	    key: "getLocalize",
	    value: function getLocalize(name) {
	      var phrase = '';

	      if (typeof name === 'undefined') {
	        return this.localize;
	      } else if (typeof this.localize[name.toString()] === 'undefined') {
	        console.warn("LiveChatWidget.getLocalize: message with code '".concat(name.toString(), "' is undefined."));
	      } else {
	        phrase = this.localize[name];
	      }

	      return phrase;
	    }
	    /* endregion 05. Widget interaction and utils */

	  }]);
	  return Widget;
	}();

	/**
	 * Bitrix OpenLines widget
	 * Widget public interface
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	var WidgetPublicManager =
	/*#__PURE__*/
	function () {
	  function WidgetPublicManager(config) {
	    babelHelpers.classCallCheck(this, WidgetPublicManager);
	    this.developerInfo = 'Do not use private methods.';
	    this.__privateMethods__ = new Widget(config);

	    this.__createLegacyMethods();
	  }

	  babelHelpers.createClass(WidgetPublicManager, [{
	    key: "open",
	    value: function open(params) {
	      return this.__privateMethods__.open(params);
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      return this.__privateMethods__.close();
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(params) {
	      return this.__privateMethods__.showNotification(params);
	    }
	  }, {
	    key: "getUserData",
	    value: function getUserData() {
	      return this.__privateMethods__.getUserData();
	    }
	  }, {
	    key: "setUserRegisterData",
	    value: function setUserRegisterData(params) {
	      return this.__privateMethods__.setUserRegisterData(params);
	    }
	  }, {
	    key: "setCustomData",
	    value: function setCustomData(params) {
	      return this.__privateMethods__.setCustomData(params);
	    }
	  }, {
	    key: "mutateTemplateComponent",
	    value: function mutateTemplateComponent(id, params) {
	      return this.__privateMethods__.mutateTemplateComponent(id, params);
	    }
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      return this.__privateMethods__.addLocalize(phrases);
	    }
	    /**
	     *
	     * @param params {Object}
	     * @returns {Function|Boolean} - Unsubscribe callback function or False
	     */

	  }, {
	    key: "subscribe",
	    value: function subscribe(params) {
	      return this.__privateMethods__.subscribe(params);
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      return this.__privateMethods__.start();
	    }
	  }, {
	    key: "__createLegacyMethods",
	    value: function __createLegacyMethods() {
	      var _this = this;

	      if (typeof window.BX.LiveChat === 'undefined') {
	        var sourceHref = document.createElement('a');
	        sourceHref.href = this.__privateMethods__.host;
	        var sourceDomain = sourceHref.protocol + '//' + sourceHref.hostname + (sourceHref.port && sourceHref.port != '80' && sourceHref.port != '443' ? ":" + sourceHref.port : "");
	        window.BX.LiveChat = {
	          openLiveChat: function openLiveChat() {
	            _this.open({
	              openFromButton: true
	            });
	          },
	          closeLiveChat: function closeLiveChat() {
	            _this.close();
	          },
	          addEventListener: function addEventListener(el, eventName, handler) {
	            if (eventName === 'message') {
	              _this.subscribe({
	                type: SubscriptionType.userMessage,
	                callback: function callback(event) {
	                  handler({
	                    origin: sourceDomain,
	                    data: JSON.stringify({
	                      action: 'sendMessage'
	                    }),
	                    event: event
	                  });
	                }
	              });
	            } else {
	              console.warn('Method BX.LiveChat.addEventListener is not supported, user new format for subscribe.');
	            }
	          },
	          setCookie: function setCookie() {},
	          getCookie: function getCookie() {},
	          sourceDomain: sourceDomain
	        };
	      }

	      if (typeof window.BxLiveChatInit === 'function') {
	        var config = window.BxLiveChatInit();

	        if (config.user) {
	          this.__privateMethods__.setUserRegisterData(config.user);
	        }

	        if (config.firstMessage) {
	          this.__privateMethods__.setCustomData(config.firstMessage);
	        }
	      }

	      if (window.BxLiveChatLoader instanceof Array) {
	        window.BxLiveChatLoader.forEach(function (callback) {
	          return callback();
	        });
	      }

	      return true;
	    }
	  }]);
	  return WidgetPublicManager;
	}();

	/**
	 * Bitrix OpenLines widget
	 * LiveChat base component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	/**
	 * @notice Do not mutate or clone this component! It is under development.
	 */

	ui_vue.Vue.component('bx-livechat', {
	  data: function data() {
	    return {
	      viewPortMetaSiteNode: null,
	      viewPortMetaWidgetNode: null,
	      storedMessage: '',
	      storedFile: null,
	      textareaFocused: false,
	      textareaDrag: false,
	      textareaHeight: 100,
	      textareaMinimumHeight: 100,
	      textareaMaximumHeight: im_utils.Utils.device.isMobile() ? 200 : 300
	    };
	  },
	  created: function created() {
	    this.onCreated();
	    document.addEventListener('keydown', this.onWindowKeyDown);
	    this.$root.$on('requestShowForm', this.onRequestShowForm);
	  },
	  beforeDestroy: function beforeDestroy() {
	    document.removeEventListener('keydown', this.onWindowKeyDown);
	    this.$root.$off('requestShowForm', this.onRequestShowForm);
	    this.onTextareaDragEventRemove();
	  },
	  computed: babelHelpers.objectSpread({
	    FormType: function FormType$$1() {
	      return FormType;
	    },
	    VoteType: function VoteType$$1() {
	      return VoteType;
	    },
	    DeviceType: function DeviceType() {
	      return im_const.DeviceType;
	    },
	    textareaHeightStyle: function textareaHeightStyle(state) {
	      return 'flex: 0 0 ' + this.textareaHeight + 'px;';
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    },
	    widgetMobileDisabled: function widgetMobileDisabled(state) {
	      if (state.application.device.type == im_const.DeviceType.mobile) {
	        if (navigator.userAgent.toString().includes('iPad')) ; else if (state.application.device.orientation == im_const.DeviceOrientation.horizontal) {
	          if (navigator.userAgent.toString().includes('iPhone')) {
	            return true;
	          } else {
	            return !(babelHelpers.typeof(window.screen) === 'object' && window.screen.availHeight >= 800);
	          }
	        }
	      }

	      return false;
	    },
	    widgetClassName: function widgetClassName(state) {
	      var className = ['bx-livechat-wrapper'];
	      className.push('bx-livechat-show');

	      if (state.widget.common.pageMode) {
	        className.push('bx-livechat-page-mode');
	      } else {
	        className.push('bx-livechat-position-' + LocationStyle[state.widget.common.location]);
	      }

	      if (state.application.common.languageId == LanguageType.russian) {
	        className.push('bx-livechat-logo-ru');
	      } else if (state.application.common.languageId == LanguageType.ukraine) {
	        className.push('bx-livechat-logo-ua');
	      } else {
	        className.push('bx-livechat-logo-en');
	      }

	      if (!state.widget.common.online) {
	        className.push('bx-livechat-offline-state');
	      }

	      if (state.widget.common.dragged) {
	        className.push('bx-livechat-drag-n-drop');
	      }

	      if (state.widget.common.dialogStart) {
	        className.push('bx-livechat-chat-start');
	      }

	      if (state.widget.dialog.operator.name && !(state.application.device.type == im_const.DeviceType.mobile && state.application.device.orientation == im_const.DeviceOrientation.horizontal)) {
	        className.push('bx-livechat-has-operator');
	      }

	      if (im_utils.Utils.device.isMobile()) {
	        className.push('bx-livechat-mobile');
	      } else if (im_utils.Utils.browser.isSafari()) {
	        className.push('bx-livechat-browser-safari');
	      } else if (im_utils.Utils.browser.isIe()) {
	        className.push('bx-livechat-browser-ie');
	      }

	      if (im_utils.Utils.platform.isMac()) {
	        className.push('bx-livechat-mac');
	      } else {
	        className.push('bx-livechat-custom-scroll');
	      }

	      if (state.widget.common.styles.backgroundColor && im_utils.Utils.isDarkColor(state.widget.common.styles.iconColor)) {
	        className.push('bx-livechat-bright-header');
	      }

	      return className.join(' ');
	    },
	    showMessageDialog: function showMessageDialog() {
	      return this.messageCollection.length > 0;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    },
	    application: function application(state) {
	      return state.application;
	    },
	    messageCollection: function messageCollection(state) {
	      return state.messages.collection[state.application.dialog.chatId];
	    }
	  })),
	  watch: {
	    sessionClose: function sessionClose(value) {
	      im_tools_logger.Logger.log('sessionClose change', value);
	    }
	  },
	  methods: {
	    close: function close(event) {
	      if (this.widget.common.pageMode) {
	        return false;
	      }

	      this.onBeforeClose();
	      this.$store.commit('widget/common', {
	        showed: false
	      });
	    },
	    showLikeForm: function showLikeForm() {
	      if (this.offline) {
	        return false;
	      }

	      clearTimeout(this.showFormTimeout);

	      if (!this.widget.common.vote.enable) {
	        return false;
	      }

	      if (this.widget.dialog.sessionClose && this.widget.dialog.userVote != VoteType.none) {
	        return false;
	      }

	      this.$store.commit('widget/common', {
	        showForm: FormType.like
	      });
	    },
	    showWelcomeForm: function showWelcomeForm() {
	      clearTimeout(this.showFormTimeout);
	      this.$store.commit('widget/common', {
	        showForm: FormType.welcome
	      });
	    },
	    showOfflineForm: function showOfflineForm() {
	      clearTimeout(this.showFormTimeout);

	      if (this.widget.dialog.showForm !== FormType.welcome) {
	        this.$store.commit('widget/common', {
	          showForm: FormType.offline
	        });
	      }
	    },
	    showHistoryForm: function showHistoryForm() {
	      clearTimeout(this.showFormTimeout);
	      this.$store.commit('widget/common', {
	        showForm: FormType.history
	      });
	    },
	    hideForm: function hideForm() {
	      clearTimeout(this.showFormTimeout);
	      this.$store.commit('widget/common', {
	        showForm: FormType.none
	      });
	    },
	    showConsentWidow: function showConsentWidow() {
	      this.$store.commit('widget/common', {
	        showConsent: true
	      });
	    },
	    agreeConsentWidow: function agreeConsentWidow() {
	      this.$store.commit('widget/common', {
	        showConsent: false
	      });
	      this.$root.$bitrixWidget.sendConsentDecision(true);

	      if (this.storedMessage || this.storedFile) {
	        if (this.storedMessage) {
	          this.onTextareaSend({
	            focus: this.application.device.type != im_const.DeviceType.mobile
	          });
	          this.storedMessage = '';
	        }

	        if (this.storedFile) {
	          this.onTextareaFileSelected();
	          this.storedFile = '';
	        }
	      } else if (this.widget.common.showForm == FormType.none) {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    disagreeConsentWidow: function disagreeConsentWidow() {
	      this.$store.commit('widget/common', {
	        showForm: FormType.none
	      });
	      this.$store.commit('widget/common', {
	        showConsent: false
	      });
	      this.$root.$bitrixWidget.sendConsentDecision(false);

	      if (this.storedMessage) {
	        this.$root.$emit('onMessengerTextareaInsertText', {
	          text: this.storedMessage,
	          focus: this.application.device.type != im_const.DeviceType.mobile
	        });
	        this.storedMessage = '';
	      }

	      if (this.storedFile) {
	        this.storedFile = '';
	      }

	      if (this.application.device.type != im_const.DeviceType.mobile) {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    logEvent: function logEvent(name) {
	      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	        params[_key - 1] = arguments[_key];
	      }

	      im_tools_logger.Logger.info.apply(im_tools_logger.Logger, [name].concat(params));
	    },
	    onCreated: function onCreated() {
	      var _this = this;

	      if (im_utils.Utils.device.isMobile()) {
	        var viewPortMetaSiteNode = Array.from(document.head.getElementsByTagName('meta')).filter(function (element) {
	          return element.name == 'viewport';
	        })[0];

	        if (viewPortMetaSiteNode) {
	          this.viewPortMetaSiteNode = viewPortMetaSiteNode;
	          document.head.removeChild(this.viewPortMetaSiteNode);
	        } else {
	          var contentWidth = document.body.offsetWidth;

	          if (contentWidth < window.innerWidth) {
	            contentWidth = window.innerWidth;
	          }

	          if (contentWidth < 1024) {
	            contentWidth = 1024;
	          }

	          this.viewPortMetaSiteNode = document.createElement('meta');
	          this.viewPortMetaSiteNode.setAttribute('name', 'viewport');
	          this.viewPortMetaSiteNode.setAttribute('content', "width=".concat(contentWidth, ", initial-scale=1.0, user-scalable=1"));
	        }

	        if (!this.viewPortMetaWidgetNode) {
	          this.viewPortMetaWidgetNode = document.createElement('meta');
	          this.viewPortMetaWidgetNode.setAttribute('name', 'viewport');
	          this.viewPortMetaWidgetNode.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=0');
	          document.head.appendChild(this.viewPortMetaWidgetNode);
	        }

	        document.body.classList.add('bx-livechat-mobile-state');

	        if (im_utils.Utils.browser.isSafariBased()) {
	          document.body.classList.add('bx-livechat-mobile-safari-based');
	        }

	        setTimeout(function () {
	          _this.$store.commit('widget/common', {
	            showed: true
	          });
	        }, 50);
	      } else {
	        this.$store.commit('widget/common', {
	          showed: true
	        });
	      }

	      this.textareaHeight = this.widget.common.textareaHeight || this.textareaHeight;
	      this.$store.commit('files/initCollection', {
	        chatId: this.$root.$bitrixWidget.getChatId()
	      });
	      this.$store.commit('messages/initCollection', {
	        chatId: this.$root.$bitrixWidget.getChatId()
	      });
	      this.$store.commit('dialogues/initCollection', {
	        dialogId: this.$root.$bitrixWidget.getDialogId(),
	        fields: {
	          entityType: 'LIVECHAT',
	          type: 'livechat'
	        }
	      });
	    },
	    onBeforeClose: function onBeforeClose() {
	      if (im_utils.Utils.device.isMobile()) {
	        document.body.classList.remove('bx-livechat-mobile-state');

	        if (im_utils.Utils.browser.isSafariBased()) {
	          document.body.classList.remove('bx-livechat-mobile-safari-based');
	        }

	        if (this.viewPortMetaWidgetNode) {
	          document.head.removeChild(this.viewPortMetaWidgetNode);
	          this.viewPortMetaWidgetNode = null;
	        }

	        if (this.viewPortMetaSiteNode) {
	          document.head.appendChild(this.viewPortMetaSiteNode);
	          this.viewPortMetaSiteNode = null;
	        }
	      }
	    },
	    onAfterClose: function onAfterClose() {
	      this.$root.$bitrixWidget.close();
	    },
	    onRequestShowForm: function onRequestShowForm(event) {
	      var _this2 = this;

	      clearTimeout(this.showFormTimeout);

	      if (event.type == FormType.welcome) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this2.showWelcomeForm();
	          }, 5000);
	        } else {
	          this.showWelcomeForm();
	        }
	      } else if (event.type == FormType.offline) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this2.showOfflineForm();
	          }, 3000);
	        } else {
	          this.showOfflineForm();
	        }
	      } else if (event.type == FormType.like) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this2.showLikeForm();
	          }, 5000);
	        } else {
	          this.showLikeForm();
	        }
	      }
	    },
	    onDialogRequestHistory: function onDialogRequestHistory(event) {
	      this.$root.$bitrixWidget.getDialogHistory(event.lastId);
	    },
	    onDialogRequestUnread: function onDialogRequestUnread(event) {
	      this.$root.$bitrixWidget.getDialogUnread(event.lastId);
	    },
	    onDialogMessageClickByUserName: function onDialogMessageClickByUserName(event) {
	      // TODO name push to auto-replace mention holder - User Name -> [USER=274]User Name[/USER]
	      this.$root.$emit('onMessengerTextareaInsertText', {
	        text: event.user.name + ' '
	      });
	    },
	    onDialogMessageClickByCommand: function onDialogMessageClickByCommand(event) {
	      if (event.type === 'put') {
	        this.$root.$emit('onMessengerTextareaInsertText', {
	          text: event.value + ' '
	        });
	      } else if (event.type === 'send') {
	        this.$root.$bitrixWidget.addMessage(event.value);
	      } else {
	        im_tools_logger.Logger.warn('Unprocessed command', event);
	      }
	    },
	    onDialogMessageMenuClick: function onDialogMessageMenuClick(event) {
	      im_tools_logger.Logger.warn('Message menu:', event);
	    },
	    onDialogMessageRetryClick: function onDialogMessageRetryClick(event) {
	      im_tools_logger.Logger.warn('Message retry:', event);
	      this.$root.$bitrixWidget.retrySendMessage(event.message);
	    },
	    onDialogReadMessage: function onDialogReadMessage(event) {
	      this.$root.$bitrixWidget.readMessage(event.id);
	    },
	    onDialogClick: function onDialogClick(event) {
	      this.$store.commit('widget/common', {
	        showForm: FormType.none
	      });
	    },
	    onTextareaSend: function onTextareaSend(event) {
	      event.focus = event.focus !== false;

	      if (this.widget.common.showForm == FormType.smile) {
	        this.$store.commit('widget/common', {
	          showForm: FormType.none
	        });
	      }

	      if (!this.widget.dialog.userConsent && this.widget.common.consentUrl) {
	        if (event.text) {
	          this.storedMessage = event.text;
	        }

	        this.showConsentWidow();
	        return false;
	      }

	      event.text = event.text ? event.text : this.storedMessage;

	      if (!event.text) {
	        return false;
	      }

	      this.hideForm();
	      this.$root.$bitrixWidget.addMessage(event.text);

	      if (event.focus) {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }

	      return true;
	    },
	    onTextareaWrites: function onTextareaWrites(event) {
	      this.$root.$bitrixController.startWriting();
	    },
	    onTextareaFocus: function onTextareaFocus(event) {
	      var _this3 = this;

	      if (this.widget.common.copyright && this.application.device.type == im_const.DeviceType.mobile) {
	        this.widget.common.copyright = false;
	      }

	      if (im_utils.Utils.device.isMobile()) {
	        clearTimeout(this.onTextareaFocusScrollTimeout);
	        this.onTextareaFocusScrollTimeout = setTimeout(function () {
	          document.addEventListener('scroll', _this3.onWindowScroll);
	        }, 1000);
	      }

	      this.textareaFocused = true;
	    },
	    onTextareaBlur: function onTextareaBlur(event) {
	      var _this4 = this;

	      if (!this.widget.common.copyright && this.widget.common.copyright !== this.$root.$bitrixWidget.copyright) {
	        this.widget.common.copyright = this.$root.$bitrixWidget.copyright;
	        this.$nextTick(function () {
	          _this4.$root.$emit('onMessengerDialogScrollToBottom', {
	            force: true
	          });
	        });
	      }

	      if (im_utils.Utils.device.isMobile()) {
	        clearTimeout(this.onTextareaFocusScrollTimeout);
	        document.removeEventListener('scroll', this.onWindowScroll);
	      }

	      this.textareaFocused = false;
	    },
	    onTextareaStartDrag: function onTextareaStartDrag(event) {
	      if (this.textareaDrag) {
	        return;
	      }

	      im_tools_logger.Logger.log('Livechat: textarea drag started');
	      this.textareaDrag = true;
	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.textareaDragCursorStartPoint = event.clientY;
	      this.textareaDragHeightStartPoint = this.textareaHeight;
	      this.onTextareaDragEventAdd();
	      this.$root.$emit('onMessengerTextareaBlur', true);
	    },
	    onTextareaContinueDrag: function onTextareaContinueDrag(event) {
	      if (!this.textareaDrag) {
	        return;
	      }

	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.textareaDragCursorControlPoint = event.clientY;
	      var textareaHeight = Math.max(Math.min(this.textareaDragHeightStartPoint + this.textareaDragCursorStartPoint - this.textareaDragCursorControlPoint, this.textareaMaximumHeight), this.textareaMinimumHeight);
	      im_tools_logger.Logger.log('Livechat: textarea drag', 'new: ' + textareaHeight, 'curr: ' + this.textareaHeight);

	      if (this.textareaHeight != textareaHeight) {
	        this.textareaHeight = textareaHeight;
	      }
	    },
	    onTextareaStopDrag: function onTextareaStopDrag() {
	      if (!this.textareaDrag) {
	        return;
	      }

	      im_tools_logger.Logger.log('Livechat: textarea drag ended');
	      this.textareaDrag = false;
	      this.onTextareaDragEventRemove();
	      this.$store.commit('widget/common', {
	        textareaHeight: this.textareaHeight
	      });
	      this.$root.$emit('onMessengerDialogScrollToBottom', {
	        force: true
	      });
	    },
	    onTextareaDragEventAdd: function onTextareaDragEventAdd() {
	      document.addEventListener('mousemove', this.onTextareaContinueDrag);
	      document.addEventListener('touchmove', this.onTextareaContinueDrag);
	      document.addEventListener('touchend', this.onTextareaStopDrag);
	      document.addEventListener('mouseup', this.onTextareaStopDrag);
	      document.addEventListener('mouseleave', this.onTextareaStopDrag);
	    },
	    onTextareaDragEventRemove: function onTextareaDragEventRemove() {
	      document.removeEventListener('mousemove', this.onTextareaContinueDrag);
	      document.removeEventListener('touchmove', this.onTextareaContinueDrag);
	      document.removeEventListener('touchend', this.onTextareaStopDrag);
	      document.removeEventListener('mouseup', this.onTextareaStopDrag);
	      document.removeEventListener('mouseleave', this.onTextareaStopDrag);
	    },
	    onTextareaFileSelected: function onTextareaFileSelected(event) {
	      var fileInput = event && event.fileInput ? event.fileInput : this.storedFile;

	      if (!fileInput) {
	        return false;
	      }

	      if (fileInput.files[0].size > this.application.disk.maxFileSize) {
	        // TODO change alert to correct overlay window
	        alert(this.localize.BX_LIVECHAT_FILE_SIZE_EXCEEDED.replace('#LIMIT#', Math.round(this.application.disk.maxFileSize / 1024 / 1024)));
	        return false;
	      }

	      if (!this.widget.dialog.userConsent && this.widget.common.consentUrl) {
	        this.storedFile = event.fileInput;
	        this.showConsentWidow();
	        return false;
	      }

	      this.$root.$bitrixWidget.addFile(fileInput);
	    },
	    onTextareaAppButtonClick: function onTextareaAppButtonClick(event) {
	      if (event.appId == FormType.smile) {
	        if (this.widget.common.showForm == FormType.smile) {
	          this.$store.commit('widget/common', {
	            showForm: FormType.none
	          });
	        } else {
	          this.$store.commit('widget/common', {
	            showForm: FormType.smile
	          });
	        }
	      } else {
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    onPullRequestConfig: function onPullRequestConfig(event) {
	      this.$root.$bitrixWidget.recoverPullConnection();
	    },
	    onSmilesSelectSmile: function onSmilesSelectSmile(event) {
	      this.$root.$emit('onMessengerTextareaInsertText', {
	        text: event.text
	      });
	    },
	    onSmilesSelectSet: function onSmilesSelectSet() {
	      this.$root.$emit('onMessengerTextareaFocus');
	    },
	    onWindowKeyDown: function onWindowKeyDown(event) {
	      if (event.keyCode == 27) {
	        if (this.widget.common.showForm != FormType.none) {
	          this.$store.commit('widget/common', {
	            showForm: FormType.none
	          });
	        } else if (this.widget.common.showConsent) {
	          this.disagreeConsentWidow();
	        } else {
	          this.close();
	        }

	        event.preventDefault();
	        event.stopPropagation();
	        this.$root.$emit('onMessengerTextareaFocus');
	      }
	    },
	    onWindowScroll: function onWindowScroll(event) {
	      var _this5 = this;

	      clearTimeout(this.onWindowScrollTimeout);
	      this.onWindowScrollTimeout = setTimeout(function () {
	        _this5.$root.$emit('onMessengerTextareaBlur', true);
	      }, 50);
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-show\" leave-active-class=\"bx-livechat-close\" @after-leave=\"onAfterClose\">\n\t\t\t<div :class=\"widgetClassName\" v-if=\"widget.common.showed\">\n\t\t\t\t<div class=\"bx-livechat-box\">\n\t\t\t\t\t<bx-livechat-head :isWidgetDisabled=\"widgetMobileDisabled\" @like=\"showLikeForm\" @history=\"showHistoryForm\" @close=\"close\"/>\n\t\t\t\t\t<template v-if=\"widgetMobileDisabled\">\n\t\t\t\t\t\t<bx-livechat-body-orientation-disabled/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"application.error.active\">\n\t\t\t\t\t\t<bx-livechat-body-error/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"!widget.common.configId\">\n\t\t\t\t\t\t<div class=\"bx-livechat-body\" key=\"loading-body\">\n\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\t\t\t\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<template v-if=\"!widget.common.dialogStart\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-body\" key=\"welcome-body\">\n\t\t\t\t\t\t\t\t<bx-livechat-body-operators/>\n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widget.common.showForm == FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"widget.common.dialogStart\">\n\t\t\t\t\t\t\t<bx-pull-status :canReconnect=\"true\" @reconnect=\"onPullRequestConfig\"/>\n\t\t\t\t\t\t\t<div :class=\"['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]\" key=\"with-message\">\n\t\t\t\t\t\t\t\t<transition name=\"bx-livechat-animation-upload-file\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widget.common.uploadFile\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-file-upload\">\t\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-file-upload-sending\"></div>\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-file-upload-text\">{{localize.BX_LIVECHAT_FILE_UPLOAD}}</div>\n\t\t\t\t\t\t\t\t\t\t</div>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</transition>\t\n\t\t\t\t\t\t\t\t<template v-if=\"showMessageDialog\">\n\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-dialog\">\n\t\t\t\t\t\t\t\t\t\t<bx-messenger-dialog\n\t\t\t\t\t\t\t\t\t\t\t:userId=\"application.common.userId\" \n\t\t\t\t\t\t\t\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t\t\t\t\t\t\t\t:chatId=\"application.dialog.chatId\"\n\t\t\t\t\t\t\t\t\t\t\t:messageLimit=\"application.dialog.messageLimit\"\n\t\t\t\t\t\t\t\t\t\t\t:enableEmotions=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableDateActions=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableCreateContent=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageAvatar=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageMenu=\"false\"\n\t\t\t\t\t\t\t\t\t\t\tlistenEventScrollToBottom=\"onMessengerDialogScrollToBottom\"\n\t\t\t\t\t\t\t\t\t\t\tlistenEventRequestHistory=\"onDialogRequestHistoryResult\"\n\t\t\t\t\t\t\t\t\t\t\tlistenEventRequestUnread=\"onDialogRequestUnreadResult\"\n\t\t\t\t\t\t\t\t\t\t\t@readMessage=\"onDialogReadMessage\"\n\t\t\t\t\t\t\t\t\t\t\t@requestHistory=\"onDialogRequestHistory\"\n\t\t\t\t\t\t\t\t\t\t\t@requestUnread=\"onDialogRequestUnread\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByCommand=\"onDialogMessageClickByCommand\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByUserName=\"onDialogMessageClickByUserName\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByMessageMenu=\"onDialogMessageMenuClick\"\n\t\t\t\t\t\t\t\t\t\t\t@clickByMessageRetry=\"onDialogMessageRetryClick\"\n\t\t\t\t\t\t\t\t\t\t\t@click=\"onDialogClick\"\n\t\t\t\t\t\t\t\t\t\t />\n\t\t\t\t\t\t\t\t\t</div>\t \n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widget.common.showForm == FormType.like && widget.common.vote.enable\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-vote/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm == FormType.welcome\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-welcome/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm == FormType.offline\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-offline/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm == FormType.history\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-history/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm == FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\t\n\t\t\t\t\t\t<div class=\"bx-livechat-textarea\" :style=\"textareaHeightStyle\" ref=\"textarea\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-textarea-resize-handle\" @mousedown=\"onTextareaStartDrag\" @touchstart=\"onTextareaStartDrag\"></div>\n\t\t\t\t\t\t\t<bx-messenger-textarea\n\t\t\t\t\t\t\t\t:siteId=\"application.common.siteId\"\n\t\t\t\t\t\t\t\t:userId=\"application.common.userId\"\n\t\t\t\t\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t\t\t\t\t:writesEventLetter=\"3\"\n\t\t\t\t\t\t\t\t:enableEdit=\"true\"\n\t\t\t\t\t\t\t\t:enableCommand=\"false\"\n\t\t\t\t\t\t\t\t:enableMention=\"false\"\n\t\t\t\t\t\t\t\t:enableFile=\"application.disk.enabled\"\n\t\t\t\t\t\t\t\t:autoFocus=\"application.device.type !== DeviceType.mobile\"\n\t\t\t\t\t\t\t\t:styles=\"{button: {backgroundColor: widget.common.styles.backgroundColor, iconColor: widget.common.styles.iconColor}}\"\n\t\t\t\t\t\t\t\tlistenEventInsertText=\"onMessengerTextareaInsertText\"\n\t\t\t\t\t\t\t\tlistenEventFocus=\"onMessengerTextareaFocus\"\n\t\t\t\t\t\t\t\tlistenEventBlur=\"onMessengerTextareaBlur\"\n\t\t\t\t\t\t\t\t@writes=\"onTextareaWrites\" \n\t\t\t\t\t\t\t\t@send=\"onTextareaSend\" \n\t\t\t\t\t\t\t\t@focus=\"onTextareaFocus\" \n\t\t\t\t\t\t\t\t@blur=\"onTextareaBlur\" \n\t\t\t\t\t\t\t\t@edit=\"logEvent('edit message', $event)\"\n\t\t\t\t\t\t\t\t@fileSelected=\"onTextareaFileSelected\"\n\t\t\t\t\t\t\t\t@appButtonClick=\"onTextareaAppButtonClick\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<bx-livechat-form-consent @agree=\"agreeConsentWidow\" @disagree=\"disagreeConsentWidow\"/>\n\t\t\t\t\t\t<template v-if=\"widget.common.copyright\">\n\t\t\t\t\t\t\t<bx-livechat-footer/>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body error component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-body-error', {
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return Object.freeze({
	        BX_LIVECHAT_ERROR_TITLE: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_TITLE,
	        BX_LIVECHAT_ERROR_DESC: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_DESC
	      });
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"error-body\">\n\t\t\t<div class=\"bx-livechat-warning-window\">\n\t\t\t\t<div class=\"bx-livechat-warning-icon\"></div>\n\t\t\t\t<template v-if=\"application.error.description\"> \n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\" v-html=\"application.error.description\"></div>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-md bx-livechat-warning-msg\">{{localize.BX_LIVECHAT_ERROR_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\">{{localize.BX_LIVECHAT_ERROR_DESC}}</div>\n\t\t\t\t</template> \n\t\t\t</div>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Head component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-head', {
	  /**
	   * @emits 'close'
	   * @emits 'like'
	   * @emits 'history'
	   */
	  props: {
	    isWidgetDisabled: {
	      default: false
	    }
	  },
	  methods: {
	    close: function close(event) {
	      this.$emit('close');
	    },
	    like: function like(event) {
	      this.$emit('like');
	    },
	    history: function history(event) {
	      this.$emit('history');
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    VoteType: function VoteType$$1() {
	      return VoteType;
	    },
	    customBackgroundStyle: function customBackgroundStyle(state) {
	      return state.widget.common.styles.backgroundColor ? 'background-color: ' + state.widget.common.styles.backgroundColor + '!important;' : '';
	    },
	    customBackgroundOnlineStyle: function customBackgroundOnlineStyle(state) {
	      return state.widget.common.styles.backgroundColor ? 'border-color: ' + state.widget.common.styles.backgroundColor + '!important;' : '';
	    },
	    showName: function showName() {
	      return this.widget.dialog.operator.firstName || this.widget.dialog.operator.lastName;
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    },
	    application: function application(state) {
	      return state.application;
	    }
	  })),
	  watch: {
	    showName: function showName(value) {
	      var _this = this;

	      if (value) {
	        setTimeout(function () {
	          _this.$root.$emit('onMessengerDialogScrollToBottom');
	        }, 300);
	      }
	    }
	  },
	  template: "\n\t\t<div class=\"bx-livechat-head-wrap\">\n\t\t\t<template v-if=\"isWidgetDisabled\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\t\n\t\t\t</template>\n\t\t\t<template v-else-if=\"application.error.active\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<template v-else-if=\"!widget.common.configId\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-else>\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<template v-if=\"!showName\">\n\t\t\t\t\t\t<div class=\"bx-livechat-title\">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div class=\"bx-livechat-user bx-livechat-status-online\">\n\t\t\t\t\t\t\t<template v-if=\"widget.dialog.operator.avatar\">\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\" :style=\"'background-image: url('+encodeURI(widget.dialog.operator.avatar)+')'\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"widget.dialog.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"widget.dialog.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-user-info\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-name\">{{widget.dialog.operator.firstName? widget.dialog.operator.firstName: widget.dialog.operator.name}}</div>\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-position\">{{widget.dialog.operator.workPosition? widget.dialog.operator.workPosition: localize.BX_LIVECHAT_USER}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<span class=\"bx-livechat-control-box-active\" v-if=\"widget.common.dialogStart && widget.dialog.sessionId\">\n\t\t\t\t\t\t\t<button v-if=\"widget.common.vote.enable && (!widget.dialog.sessionClose || widget.dialog.sessionClose && widget.dialog.userVote == VoteType.none)\" :class=\"'bx-livechat-control-btn bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(widget.dialog.userVote)\" :title=\"localize.BX_LIVECHAT_VOTE_BUTTON\" @click=\"like\"></button>\n\t\t\t\t\t\t\t<button v-if=\"widget.common.vote.enable && widget.dialog.sessionClose && widget.dialog.userVote != VoteType.none\" :class=\"'bx-livechat-control-btn bx-livechat-control-btn-disabled bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(widget.dialog.userVote)\"></button>\n\t\t\t\t\t\t\t<button class=\"bx-livechat-control-btn bx-livechat-control-btn-mail\" :title=\"localize.BX_LIVECHAT_MAIL_BUTTON_NEW\" @click=\"history\"></button>\n\t\t\t\t\t\t</span>\t\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body loading component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-body-loading', {
	  computed: {
	    localize: function localize() {
	      return Object.freeze({
	        BX_LIVECHAT_LOADING: this.$root.$bitrixMessages.BX_LIVECHAT_LOADING
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"bx-livechat-loading-window\">\n\t\t\t<svg class=\"bx-livechat-loading-circular\" viewBox=\"25 25 50 50\">\n\t\t\t\t<circle class=\"bx-livechat-loading-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t\t<circle class=\"bx-livechat-loading-inner-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t</svg>\n\t\t\t<h3 class=\"bx-livechat-help-title bx-livechat-help-title-md bx-livechat-loading-msg\">{{localize.BX_LIVECHAT_LOADING}}</h3>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body operators component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-body-operators', {
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-help-container\">\n\t\t\t<transition name=\"bx-livechat-animation-fade\">\n\t\t\t\t<h2 v-if=\"widget.common.online\" key=\"online\" class=\"bx-livechat-help-title bx-livechat-help-title-lg\">{{widget.common.textMessages.bxLivechatOnlineLine1}}<div class=\"bx-livechat-help-subtitle\">{{widget.common.textMessages.bxLivechatOnlineLine2}}</div></h2>\n\t\t\t\t<h2 v-else key=\"offline\" class=\"bx-livechat-help-title bx-livechat-help-title-sm\">{{widget.common.textMessages.bxLivechatOffline}}</h2>\n\t\t\t</transition>\t\n\t\t\t<div class=\"bx-livechat-help-user\">\n\t\t\t\t<template v-for=\"operator in widget.common.operators\">\n\t\t\t\t\t<div class=\"bx-livechat-user\" :key=\"operator.id\">\n\t\t\t\t\t\t<template v-if=\"operator.avatar\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\" :style=\"'background-image: url('+encodeURI(operator.avatar)+')'\"></div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\"></div>\n\t\t\t\t\t\t</template>\t\n\t\t\t\t\t\t<div class=\"bx-livechat-user-info\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-name\">{{operator.firstName? operator.firstName: operator.name}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\t\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body orientation disabled component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-body-orientation-disabled', {
	  computed: {
	    localize: function localize() {
	      return Object.freeze({
	        BX_LIVECHAT_MOBILE_ROTATE: this.$root.$bitrixMessages.BX_LIVECHAT_MOBILE_ROTATE
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"orientation-head\">\n\t\t\t<div class=\"bx-livechat-mobile-orientation-box\">\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-icon\"></div>\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-text\">{{localize.BX_LIVECHAT_MOBILE_ROTATE}}</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Form consent component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-form-consent', {
	  /**
	   * @emits 'agree' {event: object} -- 'event' - click event
	   * @emits 'disagree' {event: object} -- 'event' - click event
	   */
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  methods: {
	    agree: function agree(event) {
	      this.$emit('agree', {
	        event: event
	      });
	    },
	    disagree: function disagree(event) {
	      this.$emit('disagree', {
	        event: event
	      });
	    },
	    onShow: function onShow(element, done) {
	      element.classList.add('bx-livechat-consent-window-show');
	      done();
	    },
	    onHide: function onHide(element, done) {
	      element.classList.remove('bx-livechat-consent-window-show');
	      element.classList.add('bx-livechat-consent-window-close');
	      setTimeout(function () {
	        done();
	      }, 400);
	    },
	    onKeyDown: function onKeyDown(event) {
	      if (event.keyCode == 9) {
	        if (event.target === this.$refs.iframe) {
	          if (event.shiftKey) {
	            this.$refs.cancel.focus();
	          } else {
	            this.$refs.success.focus();
	          }
	        } else if (event.target === this.$refs.success) {
	          if (event.shiftKey) {
	            this.$refs.iframe.focus();
	          } else {
	            this.$refs.cancel.focus();
	          }
	        } else if (event.target === this.$refs.cancel) {
	          if (event.shiftKey) {
	            this.$refs.success.focus();
	          } else {
	            this.$refs.iframe.focus();
	          }
	        }

	        event.preventDefault();
	      } else if (event.keyCode == 39 || event.keyCode == 37) {
	        if (event.target.nextElementSibling) {
	          event.target.nextElementSibling.focus();
	        } else if (event.target.previousElementSibling) {
	          event.target.previousElementSibling.focus();
	        }

	        event.preventDefault();
	      }
	    }
	  },
	  directives: {
	    focus: {
	      inserted: function inserted(element, params) {
	        element.focus();
	      }
	    }
	  },
	  template: "\n\t\t<transition @enter=\"onShow\" @leave=\"onHide\">\n\t\t\t<template v-if=\"widget.common.showConsent && widget.common.consentUrl\">\n\t\t\t\t<div class=\"bx-livechat-consent-window\">\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-title\">{{localize.BX_LIVECHAT_CONSENT_TITLE}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-content\">\n\t\t\t\t\t\t<iframe class=\"bx-livechat-consent-window-content-iframe\" ref=\"iframe\" frameborder=\"0\" marginheight=\"0\"  marginwidth=\"0\" allowtransparency=\"allow-same-origin\" seamless=\"true\" :src=\"widget.common.consentUrl\" @keydown=\"onKeyDown\"></iframe>\n\t\t\t\t\t</div>\t\t\t\t\t\t\t\t\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" ref=\"success\" @click=\"agree\" @keydown=\"onKeyDown\" v-focus>{{localize.BX_LIVECHAT_CONSENT_AGREE}}</button>\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-cancel\" ref=\"cancel\" @click=\"disagree\" @keydown=\"onKeyDown\">{{localize.BX_LIVECHAT_CONSENT_DISAGREE}}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</transition>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Form history component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-form-history', {
	  data: function data() {
	    return {
	      fieldEmail: ''
	    };
	  },
	  watch: {
	    fieldEmail: function fieldEmail(value) {
	      clearTimeout(this.fieldEmailTimeout);
	      this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  created: function created() {
	    this.fieldEmail = '' + this.widget.user.email;
	  },
	  methods: {
	    formShowed: function formShowed() {
	      if (!im_utils.Utils.platform.isMobile()) {
	        this.$refs.emailInput.focus();
	      }
	    },
	    sendForm: function sendForm() {
	      var email = this.checkEmailField() ? this.fieldEmail : '';

	      if (email) {
	        this.$root.$bitrixWidget.sendForm(FormType.history, {
	          email: email
	        });
	      }

	      this.hideForm();
	    },
	    hideForm: function hideForm(event) {
	      clearTimeout(this.fieldEmailTimeout);
	      this.$parent.hideForm();
	    },
	    onFieldEnterPress: function onFieldEnterPress(event) {
	      this.sendForm();
	      event.preventDefault();
	    },
	    checkEmailField: function checkEmailField() {
	      if (this.fieldEmail.match(/^(.*)@(.*)\.[a-zA-Z]{2,}$/)) {
	        if (this.$refs.email) {
	          this.$refs.email.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.emailInput) {
	          if (this.$refs.email) {
	            this.$refs.email.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\" @after-enter=\"formShowed\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-show\" key=\"welcome\">\t\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-form-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-sm\">{{localize.BX_LIVECHAT_MAIL_TITLE_NEW}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"email\">\n\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP\"></div>\n\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_MAIL\" v-model=\"fieldEmail\" ref=\"emailInput\" @blur=\"checkEmailField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" @click=\"sendForm\">{{localize.BX_LIVECHAT_MAIL_BUTTON_NEW}}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\n\t\t</transition>\t\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Form offline component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.cloneComponent('bx-livechat-form-offline', 'bx-livechat-form-welcome', {
	  methods: {
	    formShowed: function formShowed() {
	      if (!im_utils.Utils.platform.isMobile()) {
	        this.$refs.emailInput.focus();
	      }
	    },
	    sendForm: function sendForm() {
	      var name = this.fieldName;
	      var email = this.checkEmailField() ? this.fieldEmail : '';
	      var phone = this.checkPhoneField() ? this.fieldPhone : '';

	      if (name || email || phone) {
	        this.$root.$bitrixWidget.sendForm(FormType.offline, {
	          name: name,
	          email: email,
	          phone: phone
	        });
	      }

	      this.hideForm();
	    },
	    onFieldEnterPress: function onFieldEnterPress(event) {
	      if (event.target === this.$refs.emailInput) {
	        this.showFullForm();
	        this.$refs.phoneInput.focus();
	      } else if (event.target === this.$refs.phoneInput) {
	        this.$refs.nameInput.focus();
	      } else {
	        this.sendForm();
	      }

	      event.preventDefault();
	    }
	  },
	  watch: {
	    fieldName: function fieldName() {
	      clearTimeout(this.fieldNameTimeout);
	      this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
	    },
	    fieldEmail: function fieldEmail() {
	      clearTimeout(this.showFormTimeout);
	      this.showFormTimeout = setTimeout(this.showFullForm, 1000);
	      clearTimeout(this.fieldEmailTimeout);
	      this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\" @after-enter=\"formShowed\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-show\" key=\"welcome\">\t\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-form-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-sm\">{{localize.BX_LIVECHAT_OFFLINE_TITLE}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"email\">\n\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP\"></div>\n\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_MAIL\" v-model=\"fieldEmail\" ref=\"emailInput\" @blur=\"checkEmailField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div :class=\"['bx-livechat-form-short', {\n\t\t\t\t\t\t'bx-livechat-form-full': isFullForm,\n\t\t\t\t\t}]\">\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"phone\">\n\t\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP\"></div>\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_PHONE\" v-model=\"fieldPhone\" ref=\"phoneInput\" @blur=\"checkPhoneField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg\" ref=\"name\">\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_NAME\" v-model=\"fieldName\" ref=\"nameInput\" @blur=\"checkNameField\" @keydown.enter=\"onFieldEnterPress\"  @keydown.tab=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" @click=\"sendForm\">{{localize.BX_LIVECHAT_ABOUT_SEND}}</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\n\t\t</transition>\t\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Form vote component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-form-vote', {
	  computed: babelHelpers.objectSpread({
	    VoteType: function VoteType$$1() {
	      return VoteType;
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  methods: {
	    userVote: function userVote(vote) {
	      this.$store.commit('widget/common', {
	        showForm: FormType.none
	      });
	      this.$store.commit('widget/dialog', {
	        userVote: vote
	      });
	      this.$root.$bitrixWidget.sendDialogVote(vote);
	    },
	    hideForm: function hideForm(event) {
	      this.$parent.hideForm();
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-rate-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-rate-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-mdl\">{{widget.common.vote.messageText}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-like\" @click=\"userVote(VoteType.like)\" :title=\"widget.common.vote.messageLike\"></button>\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-dislike\" @click=\"userVote(VoteType.dislike)\" :title=\"widget.common.vote.messageDislike\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\t\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Form welcome component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-form-welcome', {
	  data: function data() {
	    return {
	      fieldName: '',
	      fieldEmail: '',
	      fieldPhone: '',
	      isFullForm: im_utils.Utils.platform.isMobile()
	    };
	  },
	  watch: {
	    fieldName: function fieldName() {
	      clearTimeout(this.showFormTimeout);
	      this.showFormTimeout = setTimeout(this.showFullForm, 1000);
	      clearTimeout(this.fieldNameTimeout);
	      this.fieldNameTimeout = setTimeout(this.checkNameField, 300);
	    },
	    fieldEmail: function fieldEmail(value) {
	      clearTimeout(this.fieldEmailTimeout);
	      this.fieldEmailTimeout = setTimeout(this.checkEmailField, 300);
	    },
	    fieldPhone: function fieldPhone(value) {
	      clearTimeout(this.fieldPhoneTimeout);
	      this.fieldPhoneTimeout = setTimeout(this.checkPhoneField, 300);
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  created: function created() {
	    this.fieldName = '' + this.widget.user.name;
	    this.fieldEmail = '' + this.widget.user.email;
	    this.fieldPhone = '' + this.widget.user.phone;
	  },
	  methods: {
	    formShowed: function formShowed() {
	      if (!im_utils.Utils.platform.isMobile()) {
	        this.$refs.nameInput.focus();
	      }
	    },
	    showFullForm: function showFullForm() {
	      clearTimeout(this.showFormTimeout);
	      this.isFullForm = true;
	    },
	    sendForm: function sendForm() {
	      var name = this.fieldName;
	      var email = this.checkEmailField() ? this.fieldEmail : '';
	      var phone = this.checkPhoneField() ? this.fieldPhone : '';

	      if (name || email || phone) {
	        this.$root.$bitrixWidget.sendForm(FormType.welcome, {
	          name: name,
	          email: email,
	          phone: phone
	        });
	      }

	      this.hideForm();
	    },
	    hideForm: function hideForm(event) {
	      clearTimeout(this.showFormTimeout);
	      clearTimeout(this.fieldNameTimeout);
	      clearTimeout(this.fieldEmailTimeout);
	      clearTimeout(this.fieldPhoneTimeout);
	      this.$parent.hideForm();
	    },
	    onFieldEnterPress: function onFieldEnterPress(event) {
	      if (event.target === this.$refs.nameInput) {
	        this.showFullForm();
	        this.$refs.emailInput.focus();
	      } else if (event.target === this.$refs.emailInput) {
	        this.$refs.phoneInput.focus();
	      } else {
	        this.sendForm();
	      }

	      event.preventDefault();
	    },
	    checkNameField: function checkNameField() {
	      if (this.fieldName.length > 0) {
	        if (this.$refs.name) {
	          this.$refs.name.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.nameInput) {
	          if (this.$refs.name) {
	            this.$refs.name.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    },
	    checkEmailField: function checkEmailField() {
	      if (this.fieldEmail.match(/^(.*)@(.*)\.[a-zA-Z]{2,}$/)) {
	        if (this.$refs.email) {
	          this.$refs.email.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.emailInput) {
	          if (this.$refs.email) {
	            this.$refs.email.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    },
	    checkPhoneField: function checkPhoneField() {
	      if (this.fieldPhone.match(/^(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){10,14}(\s*)?$/)) {
	        if (this.$refs.phone) {
	          this.$refs.phone.classList.remove('ui-ctl-danger');
	        }

	        return true;
	      } else {
	        if (document.activeElement !== this.$refs.phoneInput) {
	          if (this.$refs.phone) {
	            this.$refs.phone.classList.add('ui-ctl-danger');
	          }
	        }

	        return false;
	      }
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\" @after-enter=\"formShowed\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-show\" key=\"welcome\">\t\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-form-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-sm\">{{localize.BX_LIVECHAT_ABOUT_TITLE}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-w100 ui-ctl-lg\" ref=\"name\">\n\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_NAME\" v-model=\"fieldName\" ref=\"nameInput\" @blur=\"checkNameField\" @keydown.enter=\"onFieldEnterPress\"  @keydown.tab=\"onFieldEnterPress\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div :class=\"['bx-livechat-form-short', {\n\t\t\t\t\t\t'bx-livechat-form-full': isFullForm,\n\t\t\t\t\t}]\">\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"email\">\n\t\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_MAIL_TOOLTIP\"></div>\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_MAIL\" v-model=\"fieldEmail\" ref=\"emailInput\" @blur=\"checkEmailField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"phone\">\n\t\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-phone bx-livechat-form-icon\" :title=\"localize.BX_LIVECHAT_FIELD_PHONE_TOOLTIP\"></div>\n\t\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"localize.BX_LIVECHAT_FIELD_PHONE\" v-model=\"fieldPhone\" ref=\"phoneInput\" @blur=\"checkPhoneField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" @click=\"sendForm\">{{localize.BX_LIVECHAT_ABOUT_SEND}}</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\n\t\t</transition>\t\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Smiles component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.cloneComponent('bx-livechat-smiles', 'bx-smiles', {
	  methods: {
	    hideForm: function hideForm(event) {
	      this.$parent.hideForm();
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-smiles-box\">\n\t\t\t\t\t#PARENT_TEMPLATE#\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Footer component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.Vue.component('bx-livechat-footer', {
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_COPYRIGHT_', this.$root.$bitrixMessages);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-copyright\">\t\n\t\t\t<template v-if=\"widget.common.copyrightUrl\">\n\t\t\t\t<a :href=\"widget.common.copyrightUrl\" target=\"_blank\">\n\t\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t\t</a>\n\t\t\t</template>\n\t\t\t<template v-else>\n\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t</template>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Widget component & controller
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	BX.LiveChatWidget = WidgetPublicManager;
	BX.LiveChatWidget.VoteType = VoteType;
	BX.LiveChatWidget.SubscriptionType = SubscriptionType$1;
	BX.LiveChatWidget.LocationStyle = LocationStyle;
	BX.LiveChatWidget.Cookie = Cookie;
	window.dispatchEvent(new CustomEvent('onBitrixLiveChatSourceLoaded', {
	  detail: {}
	}));

}((this.window = this.window || {}),BX,window,window,window,window,window,BX,BX,BX,BX,BX.Messenger.Model,BX.Messenger.Controller,BX.Messenger,BX.Messenger.Provider.Pull,BX.Messenger.Provider.Pull,BX.Messenger,BX.Messenger.Const,BX,BX,BX.Messenger,BX,BX));
//# sourceMappingURL=widget.bundle.js.map
