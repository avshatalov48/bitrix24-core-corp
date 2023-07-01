(function (exports,main_polyfill_customevent,pull_component_status,im_component_dialog,im_component_textarea,im_view_quotepanel,imopenlines_component_message,imopenlines_component_form,rest_client,im_provider_rest,main_date,pull_client,ui_vue_components_crm_form,im_controller,im_lib_cookie,im_lib_localstorage,im_lib_utils,main_md5,im_lib_uploader,main_core,im_lib_logger,im_eventHandler,im_const,main_core_minimal,ui_vue_vuex,ui_vue,main_core_events) {
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
	var WidgetBaseSize = Object.freeze({
	  width: 435,
	  height: 557
	});
	var WidgetMinimumSize = Object.freeze({
	  width: 340,
	  height: 435
	});
	var SubscriptionType = Object.freeze({
	  configLoaded: 'configLoaded',
	  widgetOpen: 'widgetOpen',
	  widgetClose: 'widgetClose',
	  sessionStart: 'sessionStart',
	  sessionOperatorChange: 'sessionOperatorChange',
	  sessionFinish: 'sessionFinish',
	  operatorMessage: 'operatorMessage',
	  userForm: 'userForm',
	  userMessage: 'userMessage',
	  userFile: 'userFile',
	  userVote: 'userVote',
	  every: 'every'
	});
	var SubscriptionTypeCheck = GetObjectValues(SubscriptionType);
	var RestMethod = Object.freeze({
	  widgetUserRegister: 'imopenlines.widget.user.register',
	  widgetChatCreate: 'imopenlines.widget.chat.create',
	  widgetConfigGet: 'imopenlines.widget.config.get',
	  widgetDialogGet: 'imopenlines.widget.dialog.get',
	  widgetDialogList: 'imopenlines.widget.dialog.list',
	  widgetUserGet: 'imopenlines.widget.user.get',
	  widgetUserConsentApply: 'imopenlines.widget.user.consent.apply',
	  widgetVoteSend: 'imopenlines.widget.vote.send',
	  widgetActionSend: 'imopenlines.widget.action.send',
	  pullServerTime: 'server.time',
	  pullConfigGet: 'pull.config.get'
	});
	var RestMethodCheck = GetObjectValues(RestMethod);
	var RestAuth = Object.freeze({
	  guest: 'guest'
	});
	var SessionStatus = Object.freeze({
	  "new": 0,
	  skip: 5,
	  answer: 10,
	  client: 20,
	  clientAfterOperator: 25,
	  operator: 40,
	  waitClient: 50,
	  close: 60,
	  spam: 65,
	  duplicate: 69,
	  silentlyClose: 75
	});
	var WidgetEventType = Object.freeze({
	  showForm: 'IMOL.Widget:showForm',
	  hideForm: 'IMOL.Widget:hideForm',
	  processMessagesToSendQueue: 'IMOL.Widget:processMessagesToSendQueue',
	  requestData: 'IMOL.Widget:requestData',
	  showConsent: 'IMOL.Widget:showConsent',
	  acceptConsent: 'IMOL.Widget:acceptConsent',
	  consentAccepted: 'IMOL.Widget:consentAccepted',
	  declineConsent: 'IMOL.Widget:declineConsent',
	  consentDeclined: 'IMOL.Widget:consentDeclined',
	  sendDialogVote: 'IMOL.Widget:sendDialogVote',
	  createSession: 'IMOL.Widget:createSession',
	  openSession: 'IMOL.Widget:openSession'
	});

	/**
	 * Bitrix OpenLines widget
	 * Widget model (Vuex Builder model)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	var WidgetModel = /*#__PURE__*/function (_VuexBuilderModel) {
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
	            beforeFinish: true,
	            messageText: this.getVariable('vote.messageText', ''),
	            messageLike: this.getVariable('vote.messageLike', ''),
	            messageDislike: this.getVariable('vote.messageDislike', '')
	          },
	          textMessages: {
	            bxLivechatOnlineLine1: this.getVariable('textMessages.bxLivechatOnlineLine1', ''),
	            bxLivechatOnlineLine2: this.getVariable('textMessages.bxLivechatOnlineLine2', ''),
	            bxLivechatOffline: this.getVariable('textMessages.bxLivechatOffline', ''),
	            bxLivechatTitle: ''
	          },
	          online: false,
	          operators: [],
	          connectors: [],
	          showForm: FormType.none,
	          showed: false,
	          reopen: false,
	          dragged: false,
	          textareaHeight: 0,
	          widgetHeight: 0,
	          widgetWidth: 0,
	          showConsent: false,
	          consentUrl: '',
	          dialogStart: false,
	          watchTyping: false,
	          showSessionId: false,
	          isCreateSessionMode: false,
	          crmFormsSettings: {
	            useWelcomeForm: false,
	            welcomeFormId: 0,
	            welcomeFormSec: '',
	            welcomeFormDelay: false,
	            welcomeFormFilled: false,
	            successText: '',
	            errorText: ''
	          }
	        },
	        dialog: {
	          sessionId: 0,
	          sessionClose: true,
	          sessionStatus: 0,
	          userVote: VoteType.none,
	          closeVote: false,
	          userConsent: false,
	          operatorChatId: 0,
	          operator: {
	            id: 0,
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
	          showForm: null
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
	          if (im_lib_utils.Utils.types.isPlainObject(payload.vote)) {
	            if (typeof payload.vote.enable === 'boolean') {
	              state.common.vote.enable = payload.vote.enable;
	            }
	            if (typeof payload.vote.beforeFinish === 'boolean') {
	              state.common.vote.beforeFinish = payload.vote.beforeFinish;
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
	          if (im_lib_utils.Utils.types.isPlainObject(payload.textMessages)) {
	            if (typeof payload.textMessages.bxLivechatOnlineLine1 === 'string' && payload.textMessages.bxLivechatOnlineLine1 !== '') {
	              state.common.textMessages.bxLivechatOnlineLine1 = payload.textMessages.bxLivechatOnlineLine1;
	            }
	            if (typeof payload.textMessages.bxLivechatOnlineLine2 === 'string' && payload.textMessages.bxLivechatOnlineLine2 !== '') {
	              state.common.textMessages.bxLivechatOnlineLine2 = payload.textMessages.bxLivechatOnlineLine2;
	            }
	            if (typeof payload.textMessages.bxLivechatOffline === 'string' && payload.textMessages.bxLivechatOffline !== '') {
	              state.common.textMessages.bxLivechatOffline = payload.textMessages.bxLivechatOffline;
	            }
	            if (typeof payload.textMessages.bxLivechatTitle === 'string' && payload.textMessages.bxLivechatTitle !== '') {
	              state.common.textMessages.bxLivechatTitle = payload.textMessages.bxLivechatTitle;
	            }
	          }
	          if (typeof payload.dragged === 'boolean') {
	            state.common.dragged = payload.dragged;
	          }
	          if (typeof payload.textareaHeight === 'number') {
	            state.common.textareaHeight = payload.textareaHeight;
	          }
	          if (typeof payload.widgetHeight === 'number') {
	            state.common.widgetHeight = payload.widgetHeight;
	          }
	          if (typeof payload.widgetWidth === 'number') {
	            state.common.widgetWidth = payload.widgetWidth;
	          }
	          if (typeof payload.showConsent === 'boolean') {
	            state.common.showConsent = payload.showConsent;
	          }
	          if (typeof payload.consentUrl === 'string') {
	            state.common.consentUrl = payload.consentUrl;
	          }
	          if (typeof payload.showed === 'boolean') {
	            state.common.showed = payload.showed;
	            payload.reopen = im_lib_utils.Utils.device.isMobile() ? false : payload.showed;
	          }
	          if (typeof payload.reopen === 'boolean') {
	            state.common.reopen = payload.reopen;
	          }
	          if (typeof payload.copyright === 'boolean') {
	            state.common.copyright = payload.copyright;
	          }
	          if (typeof payload.dialogStart === 'boolean') {
	            state.common.dialogStart = payload.dialogStart;
	          }
	          if (typeof payload.watchTyping === 'boolean') {
	            state.common.watchTyping = payload.watchTyping;
	          }
	          if (typeof payload.showSessionId === 'boolean') {
	            state.common.showSessionId = payload.showSessionId;
	          }
	          if (payload.operators instanceof Array) {
	            state.common.operators = payload.operators;
	          }
	          if (payload.connectors instanceof Array) {
	            state.common.connectors = payload.connectors;
	          }
	          if (typeof payload.showForm === 'string' && typeof FormType[payload.showForm] !== 'undefined') {
	            if (payload.showForm === FormType.like && !!state.dialog.closeVote) {
	              payload.showForm = FormType.none;
	            }
	            state.common.showForm = payload.showForm;
	          }
	          if (typeof payload.location === 'number' && typeof LocationStyle[payload.location] !== 'undefined') {
	            if (state.common.location !== payload.location) {
	              state.common.widgetHeight = 0;
	              state.common.widgetWidth = 0;
	              state.common.location = payload.location;
	            }
	          }
	          if (im_lib_utils.Utils.types.isPlainObject(payload.crmFormsSettings)) {
	            if (typeof payload.crmFormsSettings.useWelcomeForm === 'string') {
	              state.common.crmFormsSettings.useWelcomeForm = payload.crmFormsSettings.useWelcomeForm === 'Y';
	            }
	            if (typeof payload.crmFormsSettings.welcomeFormId === 'string') {
	              state.common.crmFormsSettings.welcomeFormId = payload.crmFormsSettings.welcomeFormId;
	            }
	            if (typeof payload.crmFormsSettings.welcomeFormSec === 'string') {
	              state.common.crmFormsSettings.welcomeFormSec = payload.crmFormsSettings.welcomeFormSec;
	            }
	            if (typeof payload.crmFormsSettings.welcomeFormDelay === 'string') {
	              state.common.crmFormsSettings.welcomeFormDelay = payload.crmFormsSettings.welcomeFormDelay === 'Y';
	            }
	            if (typeof payload.crmFormsSettings.successText === 'string' && payload.crmFormsSettings.successText !== '') {
	              state.common.crmFormsSettings.successText = payload.crmFormsSettings.successText;
	            }
	            if (typeof payload.crmFormsSettings.errorText === 'string' && payload.crmFormsSettings.errorText !== '') {
	              state.common.crmFormsSettings.errorText = payload.crmFormsSettings.errorText;
	            }
	          }
	          if (typeof payload.isCreateSessionMode === 'boolean') {
	            state.common.isCreateSessionMode = payload.isCreateSessionMode;
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
	          if (typeof payload.sessionStatus === 'number') {
	            state.dialog.sessionStatus = payload.sessionStatus;
	          }
	          if (typeof payload.userConsent === 'boolean') {
	            state.dialog.userConsent = payload.userConsent;
	          }
	          if (typeof payload.userVote === 'string' && typeof payload.userVote !== 'undefined') {
	            state.dialog.userVote = payload.userVote;
	          }
	          if (typeof payload.closeVote === 'boolean') {
	            state.dialog.closeVote = payload.closeVote;
	            if (!!payload.closeVote && state.common.showForm === FormType.like) {
	              state.common.showForm = FormType.none;
	            }
	          }
	          if (typeof payload.operatorChatId === 'number') {
	            state.dialog.operatorChatId = payload.operatorChatId;
	          }
	          if (im_lib_utils.Utils.types.isPlainObject(payload.operator)) {
	            if (typeof payload.operator.id === 'number') {
	              state.dialog.operator.id = payload.operator.id;
	            }
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
	            im_lib_cookie.Cookie.set(null, 'LIVECHAT_HASH', payload.hash, {
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
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this2 = this;
	      return {
	        show: function show(_ref) {
	          var commit = _ref.commit;
	          commit('common', {
	            showed: true
	          });
	        },
	        setVoteDateFinish: function setVoteDateFinish(_ref2, payload) {
	          var commit = _ref2.commit,
	            dispatch = _ref2.dispatch,
	            state = _ref2.state;
	          if (!payload) {
	            clearTimeout(_this2.setVoteDateTimeout);
	            commit('dialog', {
	              closeVote: false
	            });
	            return true;
	          }
	          var totalDelay = new Date(payload).getTime() - new Date().getTime();
	          var dayTimestamp = 10000;
	          clearTimeout(_this2.setVoteDateTimeout);
	          if (payload) {
	            if (totalDelay && !state.dialog.closeVote) {
	              commit('dialog', {
	                closeVote: false
	              });
	            }
	            var delay = totalDelay;
	            if (totalDelay > dayTimestamp) {
	              delay = dayTimestamp;
	            }
	            _this2.setVoteDateTimeout = setTimeout(function requestCloseVote() {
	              delay = new Date(payload).getTime() - new Date().getTime();
	              if (delay > 0) {
	                if (delay > dayTimestamp) {
	                  delay = dayTimestamp;
	                }
	                setTimeout(requestCloseVote, delay);
	              } else {
	                commit('dialog', {
	                  closeVote: true
	                });
	              }
	            }, delay);
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
	var WidgetRestClient = /*#__PURE__*/function () {
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
	      if (babelHelpers["typeof"](this.queryParams) !== 'object') {
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
	      if (babelHelpers["typeof"](this.queryParams) !== 'object') {
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
	        logTag = im_lib_utils.Utils.getLogTrackingParams({
	          name: method
	        });
	      }
	      var promise = new BX.Promise();

	      // TODO: Callbacks methods will not work!
	      this.restClient.callMethod(method, params, null, sendCallback, logTag).then(function (result) {
	        _this.queryAuthRestore = false;
	        promise.fulfill(result);
	      })["catch"](function (result) {
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
	            })["catch"](function (result) {
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
	var WidgetRestAnswerHandler = /*#__PURE__*/function (_BaseRestHandler) {
	  babelHelpers.inherits(WidgetRestAnswerHandler, _BaseRestHandler);
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
	        connectors: data.connectors || [],
	        watchTyping: data.watchTyping,
	        showSessionId: data.showSessionId,
	        crmFormsSettings: data.crmFormsSettings
	      });
	      this.store.commit('application/set', {
	        disk: data.disk
	      });
	      this.widget.addLocalize(data.serverVariables);
	      im_lib_localstorage.LocalStorage.set(this.widget.getSiteId(), 0, 'serverVariables', data.serverVariables || {});
	    }
	  }, {
	    key: "handleImopenlinesWidgetUserRegisterSuccess",
	    value: function handleImopenlinesWidgetUserRegisterSuccess(data) {
	      this.widget.restClient.setAuthId(data.hash);
	      var previousData = [];
	      if (typeof this.store.state.messages.collection[this.controller.application.getChatId()] !== 'undefined') {
	        previousData = this.store.state.messages.collection[this.controller.application.getChatId()];
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
	    key: "handleImopenlinesWidgetChatCreateSuccess",
	    value: function handleImopenlinesWidgetChatCreateSuccess(data) {
	      this.widget.restClient.setAuthId(data.hash);
	      this.store.commit('messages/initCollection', {
	        chatId: data.chatId,
	        messages: []
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
	      this.store.dispatch('users/set', [{
	        id: data.id,
	        name: data.name,
	        firstName: data.firstName,
	        lastName: data.lastName,
	        avatar: data.avatar,
	        gender: data.gender,
	        workPosition: data.position
	      }]);
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
	      this.store.commit('widget/dialog', data);
	      this.store.commit('application/set', {
	        dialog: {
	          chatId: data.chatId,
	          dialogId: 'chat' + data.chatId,
	          diskFolderId: data.diskFolderId
	        }
	      });
	      this.store.dispatch('widget/setVoteDateFinish', data.dateCloseVote);
	    }
	  }, {
	    key: "handleImDialogMessagesGetInitSuccess",
	    value: function handleImDialogMessagesGetInitSuccess(data) {
	      this.handleImDialogMessagesGetSuccess(data);
	    }
	  }, {
	    key: "handleImDialogMessagesGetSuccess",
	    value: function handleImDialogMessagesGetSuccess(data) {
	      if (data.messages && data.messages.length > 0 && !this.widget.isDialogStart()) {
	        this.store.commit('widget/common', {
	          dialogStart: true
	        });
	        this.store.commit('widget/dialog', {
	          userConsent: true
	        });
	      }
	    }
	  }, {
	    key: "handleImMessageAddSuccess",
	    value: function handleImMessageAddSuccess(messageId, message) {
	      this.widget.sendEvent({
	        type: SubscriptionType.userMessage,
	        data: {
	          id: messageId,
	          text: message.text
	        }
	      });
	    }
	  }, {
	    key: "handleImDiskFileCommitSuccess",
	    value: function handleImDiskFileCommitSuccess(result, message) {
	      this.widget.sendEvent({
	        type: SubscriptionType.userFile,
	        data: {}
	      });
	    }
	  }]);
	  return WidgetRestAnswerHandler;
	}(im_provider_rest.BaseRestHandler);

	/**
	 * Bitrix OpenLines widget
	 * Widget pull commands (Pull Command Handler)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	var WidgetImPullCommandHandler = /*#__PURE__*/function () {
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
	      if (params.message.senderId != this.controller.application.getUserId()) {
	        this.widget.sendEvent({
	          type: SubscriptionType.operatorMessage,
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
	var WidgetImopenlinesPullCommandHandler = /*#__PURE__*/function () {
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
	        sessionStatus: 0,
	        userVote: VoteType.none
	      });
	      this.store.dispatch('widget/setVoteDateFinish', '');
	      this.widget.sendEvent({
	        type: SubscriptionType.sessionStart,
	        data: {
	          sessionId: params.sessionId
	        }
	      });
	    }
	  }, {
	    key: "handleSessionOperatorChange",
	    value: function handleSessionOperatorChange(params, extra, command) {
	      this.store.commit('widget/dialog', {
	        operator: params.operator,
	        operatorChatId: params.operatorChatId
	      });
	      this.widget.sendEvent({
	        type: SubscriptionType.sessionOperatorChange,
	        data: {
	          operator: params.operator
	        }
	      });
	    }
	  }, {
	    key: "handleSessionStatus",
	    value: function handleSessionStatus(params, extra, command) {
	      this.store.commit('widget/dialog', {
	        sessionId: params.sessionId,
	        sessionStatus: params.sessionStatus,
	        sessionClose: params.sessionClose
	      });
	      this.widget.sendEvent({
	        type: SubscriptionType.sessionStatus,
	        data: {
	          sessionId: params.sessionId,
	          sessionStatus: params.sessionStatus
	        }
	      });
	      if (params.sessionClose) {
	        this.widget.sendEvent({
	          type: SubscriptionType.sessionFinish,
	          data: {
	            sessionId: params.sessionId,
	            sessionStatus: params.sessionStatus
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
	    }
	  }, {
	    key: "handleSessionDateCloseVote",
	    value: function handleSessionDateCloseVote(params, extra, command) {
	      this.store.dispatch('widget/setVoteDateFinish', params.dateCloseVote);
	    }
	  }]);
	  return WidgetImopenlinesPullCommandHandler;
	}();

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var Widget = /*#__PURE__*/function () {
	  /* region 01. Initialize and store data */

	  // Vue instance

	  // true if there are no initialization errors
	  // true if all preparations are done
	  // true if Pull-client is offline
	  // XHR-request from widget.config.get, can be aborted before completion

	  // this block can be set from public config
	  // user info
	  // additional info to send to server

	  // external event subscribers

	  // fields from params
	  // livechat code

	  // widget button

	  // fullscreen livechat mode options

	  function Widget() {
	    var _this = this;
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Widget);
	    babelHelpers.defineProperty(this, "params", null);
	    babelHelpers.defineProperty(this, "template", null);
	    babelHelpers.defineProperty(this, "rootNode", null);
	    babelHelpers.defineProperty(this, "restClient", null);
	    babelHelpers.defineProperty(this, "pullClient", null);
	    babelHelpers.defineProperty(this, "ready", true);
	    babelHelpers.defineProperty(this, "inited", false);
	    babelHelpers.defineProperty(this, "offline", false);
	    babelHelpers.defineProperty(this, "widgetConfigRequest", null);
	    babelHelpers.defineProperty(this, "userRegisterData", {});
	    babelHelpers.defineProperty(this, "customData", []);
	    babelHelpers.defineProperty(this, "options", {
	      checkSameDomain: true
	    });
	    babelHelpers.defineProperty(this, "subscribers", {});
	    babelHelpers.defineProperty(this, "code", '');
	    babelHelpers.defineProperty(this, "host", '');
	    babelHelpers.defineProperty(this, "language", '');
	    babelHelpers.defineProperty(this, "copyright", true);
	    babelHelpers.defineProperty(this, "copyrightUrl", '');
	    babelHelpers.defineProperty(this, "buttonInstance", null);
	    babelHelpers.defineProperty(this, "localize", null);
	    babelHelpers.defineProperty(this, "pageMode", null);
	    this.params = params;

	    //TODO: remove
	    this.messagesQueue = [];
	    main_core_events.EventEmitter.subscribe(WidgetEventType.requestData, this.requestData.bind(this));
	    main_core_events.EventEmitter.subscribe(WidgetEventType.createSession, this.createChat.bind(this));
	    main_core_events.EventEmitter.subscribe(WidgetEventType.openSession, this.openSession.bind(this));
	    this.initParams();
	    this.initRestClient();
	    this.initPullClient();
	    this.initCore().then(function () {
	      _this.initWidget();
	      _this.initComplete();
	    });
	  }
	  babelHelpers.createClass(Widget, [{
	    key: "initParams",
	    value: function initParams() {
	      this.rootNode = this.params.node || document.createElement('div');
	      this.code = this.params.code || '';
	      this.host = this.params.host || '';
	      this.language = this.params.language || 'en';
	      this.copyright = this.params.copyright !== false;
	      this.copyrightUrl = this.copyright && this.params.copyrightUrl ? this.params.copyrightUrl : '';
	      if (this.params.buttonInstance && babelHelpers["typeof"](this.params.buttonInstance) === 'object') {
	        this.buttonInstance = this.params.buttonInstance;
	      }
	      if (this.params.pageMode && babelHelpers["typeof"](this.params.pageMode) === 'object') {
	        this.pageMode = {
	          useBitrixLocalize: this.params.pageMode.useBitrixLocalize === true,
	          placeholder: document.querySelector("#".concat(this.params.pageMode.placeholder))
	        };
	      }
	      var errors = this.checkRequiredFields();
	      if (errors.length > 0) {
	        errors.forEach(function (error) {
	          return console.warn(error);
	        });
	        this.ready = false;
	      }
	      this.setRootNode();
	      this.localize = this.pageMode && this.pageMode.useBitrixLocalize ? window.BX.message : {};
	      this.setLocalize();
	    }
	  }, {
	    key: "initRestClient",
	    value: function initRestClient() {
	      this.restClient = new WidgetRestClient({
	        endpoint: "".concat(this.host, "/rest")
	      });
	    }
	  }, {
	    key: "initPullClient",
	    value: function initPullClient() {
	      this.pullClient = new pull_client.PullClient({
	        serverEnabled: true,
	        userId: 0,
	        siteId: this.getSiteId(),
	        restClient: this.restClient,
	        skipStorageInit: true,
	        configTimestamp: 0,
	        skipCheckRevision: true,
	        getPublicListMethod: 'imopenlines.widget.operator.get'
	      });
	    }
	  }, {
	    key: "initCore",
	    value: function initCore() {
	      this.controller = new im_controller.Controller({
	        host: this.getHost(),
	        siteId: this.getSiteId(),
	        userId: 0,
	        languageId: this.language,
	        pull: {
	          client: this.pullClient
	        },
	        rest: {
	          client: this.restClient
	        },
	        localize: this.localize,
	        vuexBuilder: {
	          database: !im_lib_utils.Utils.browser.isIe(),
	          databaseName: 'imol/widget',
	          databaseType: ui_vue_vuex.VuexBuilder.DatabaseType.localStorage,
	          models: [WidgetModel.create().setVariables(this.getWidgetVariables())]
	        }
	      });
	      return this.controller.ready();
	    }
	  }, {
	    key: "initWidget",
	    value: function initWidget() {
	      this.restClient.setAuthId(this.getRestAuthId());
	      this.setModelData();
	      // TODO: move from controller
	      this.controller.application.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));
	      this.controller.addRestAnswerHandler(WidgetRestAnswerHandler.create({
	        widget: this,
	        store: this.controller.getStore(),
	        controller: this.controller
	      }));
	    } // if start or open methods were called before core init - we will have appropriate flags
	    // for full-page livechat we always call open
	  }, {
	    key: "initComplete",
	    value: function initComplete() {
	      window.dispatchEvent(new CustomEvent('onBitrixLiveChat', {
	        detail: {
	          widget: this,
	          widgetCode: this.code,
	          widgetHost: this.host
	        }
	      }));
	      if (this.callStartFlag) {
	        this.start();
	      }
	      if (this.pageMode || this.callOpenFlag) {
	        this.open();
	      }
	    } // public method
	    // initially called from imopenlines/lib/livechatmanager.php:16
	    // if core is not ready yet - set flag and call start once again in this.initComplete()
	  }, {
	    key: "start",
	    value: function start() {
	      if (!this.controller || !this.controller.getStore()) {
	        this.callStartFlag = true;
	        return true;
	      }
	      if (this.isSessionActive()) {
	        this.requestWidgetData();
	      }
	      return true;
	    } // public method
	    // if core is not ready yet - set flag and call start once again in this.initComplete()
	    // if not inited yet - request widget data
	  }, {
	    key: "open",
	    value: function open() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      if (!this.controller.getStore()) {
	        this.callOpenFlag = true;
	        return true;
	      }
	      if (!params.openFromButton && this.buttonInstance) {
	        this.buttonInstance.wm.showById('openline_livechat');
	      }
	      var _this$checkForErrorsB = this.checkForErrorsBeforeOpen(),
	        error = _this$checkForErrorsB.error,
	        stop = _this$checkForErrorsB.stop;
	      if (stop) {
	        return false;
	      }
	      if (!error && !this.inited) {
	        this.requestWidgetData();
	      }
	      this.attachTemplate();
	    }
	  }, {
	    key: "requestWidgetData",
	    value: function requestWidgetData() {
	      var _this2 = this;
	      if (!this.ready) {
	        console.error('LiveChatWidget.start: widget code or host is not specified');
	        return false;
	      }

	      // if user is registered or we have its hash - proceed to getting chat and messages
	      if (this.isUserReady() || this.isHashAvailable()) {
	        this.requestData();
	        this.inited = true;
	        this.fireInitEvent();
	        return true;
	      }

	      // if there is no info about user - we need to get config and wait for first message
	      this.controller.restClient.callMethod(RestMethod.widgetConfigGet, {
	        code: this.code
	      }, function (xhr) {
	        _this2.widgetConfigRequest = xhr;
	      }).then(function (result) {
	        _this2.widgetConfigRequest = null;
	        _this2.clearError();
	        _this2.controller.executeRestAnswer(RestMethod.widgetConfigGet, result);
	        if (!_this2.inited) {
	          _this2.inited = true;
	          _this2.fireInitEvent();
	        }
	      })["catch"](function (error) {
	        _this2.widgetConfigRequest = null;
	        _this2.setError(error.error().ex.error, error.error().ex.error_description);
	      });
	      if (this.isConfigDataLoaded()) {
	        this.inited = true;
	        this.fireInitEvent();
	      }
	    } // get all other info (dialog, chat, messages etc)
	  }, {
	    key: "requestData",
	    value: function requestData() {
	      im_lib_logger.Logger.log('requesting data from widget');
	      if (this.requestDataSend) {
	        return true;
	      }
	      this.requestDataSend = true;

	      // if there is uncompleted widget.config.get request - abort it (because we will do it anyway)
	      if (this.widgetConfigRequest) {
	        this.widgetConfigRequest.abort();
	      }
	      var callback = this.handleBatchRequestResult.bind(this);
	      this.controller.restClient.callBatch(this.getDataRequestQuery(), callback, false, false, im_lib_utils.Utils.getLogTrackingParams({
	        name: 'widget.init.config',
	        dialog: this.controller.application.getDialogData()
	      }));
	    }
	  }, {
	    key: "createChat",
	    value: function createChat() {
	      var _this3 = this;
	      return new Promise(function (resolve, reject) {
	        _this3.controller.restClient.callBatch(_this3.getCreateChatRequestQuery(), function (result) {
	          _this3.handleBatchCreateChatRequestResult(result).then(function () {
	            resolve();
	          });
	        }, false, false);
	      });
	    }
	  }, {
	    key: "handleBatchRequestResult",
	    value: function handleBatchRequestResult(response) {
	      var _this4 = this;
	      if (!response) {
	        this.requestDataSend = false;
	        this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
	        return false;
	      }
	      this.handleConfigGet(response).then(function () {
	        return _this4.handleUserGet(response);
	      }).then(function () {
	        return _this4.handleChatGet(response);
	      }).then(function () {
	        return _this4.handleDialogGet(response);
	      }).then(function () {
	        return _this4.handleDialogMessagesGet(response);
	      }).then(function () {
	        return _this4.handleUserRegister(response);
	      }).then(function () {
	        return _this4.handlePullRequests(response);
	      })["catch"](function (_ref) {
	        var code = _ref.code,
	          description = _ref.description;
	        _this4.setError(code, description);
	      })["finally"](function () {
	        _this4.requestDataSend = false;
	      });
	    }
	  }, {
	    key: "handleBatchCreateChatRequestResult",
	    value: function handleBatchCreateChatRequestResult(response) {
	      var _this5 = this;
	      if (!response) {
	        this.requestDataSend = false;
	        this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
	        return false;
	      }
	      return this.handleChatCreate(response).then(function () {
	        return _this5.handleChatGet(response);
	      }).then(function () {
	        return _this5.handleDialogGet(response);
	      })["catch"](function (_ref2) {
	        var code = _ref2.code,
	          description = _ref2.description;
	        _this5.setError(code, description);
	      })["finally"](function () {
	        _this5.requestDataSend = false;
	      });
	    }
	  }, {
	    key: "handleBatchOpenSessionRequestResult",
	    value: function handleBatchOpenSessionRequestResult(response) {
	      var _this6 = this;
	      if (!response) {
	        this.requestDataSend = false;
	        this.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
	        return false;
	      }
	      return this.handleChatGet(response).then(function () {
	        return _this6.handleDialogGet(response);
	      }).then(function () {
	        return _this6.handleDialogMessagesGet(response);
	      })["catch"](function (_ref3) {
	        var code = _ref3.code,
	          description = _ref3.description;
	        _this6.setError(code, description);
	      })["finally"](function () {
	        _this6.requestDataSend = false;
	      });
	    }
	  }, {
	    key: "getDataRequestQuery",
	    value: function getDataRequestQuery() {
	      // always widget.config.get
	      var query = babelHelpers.defineProperty({}, RestMethod.widgetConfigGet, [RestMethod.widgetConfigGet, {
	        code: this.code
	      }]);
	      if (this.isUserRegistered()) {
	        // widget.dialog.get
	        query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	          config_id: this.getConfigId(),
	          trace_data: this.getCrmTraceData(),
	          custom_data: this.getCustomData()
	        }];

	        // im.chat.get
	        query[im_const.RestMethodHandler.imChatGet] = [im_const.RestMethod.imChatGet, {
	          dialog_id: "$result[".concat(RestMethod.widgetDialogGet, "][dialogId]")
	        }];

	        // im.dialog.messages.get
	        query[im_const.RestMethodHandler.imDialogMessagesGetInit] = [im_const.RestMethod.imDialogMessagesGet, {
	          chat_id: "$result[".concat(RestMethod.widgetDialogGet, "][chatId]"),
	          limit: this.controller.application.getRequestMessageLimit(),
	          convert_text: 'Y'
	        }];
	      } else {
	        // widget.user.register
	        query[RestMethod.widgetUserRegister] = [RestMethod.widgetUserRegister, _objectSpread({
	          config_id: "$result[".concat(RestMethod.widgetConfigGet, "][configId]")
	        }, this.getUserRegisterFields())];

	        // im.chat.get
	        query[im_const.RestMethodHandler.imChatGet] = [im_const.RestMethod.imChatGet, {
	          dialog_id: "$result[".concat(RestMethod.widgetUserRegister, "][dialogId]")
	        }];
	        if (this.userRegisterData.hash || this.getUserHashCookie()) {
	          // widget.dialog.get
	          query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	            config_id: "$result[".concat(RestMethod.widgetConfigGet, "][configId]"),
	            trace_data: this.getCrmTraceData(),
	            custom_data: this.getCustomData()
	          }];

	          // im.dialog.messages.get
	          query[im_const.RestMethodHandler.imDialogMessagesGetInit] = [im_const.RestMethod.imDialogMessagesGet, {
	            chat_id: "$result[".concat(RestMethod.widgetDialogGet, "][chatId]"),
	            limit: this.controller.application.getRequestMessageLimit(),
	            convert_text: 'Y'
	          }];
	        }
	        if (this.isUserAgreeConsent()) {
	          // widget.user.consent.apply
	          query[RestMethod.widgetUserConsentApply] = [RestMethod.widgetUserConsentApply, {
	            config_id: "$result[".concat(RestMethod.widgetConfigGet, "][configId]"),
	            consent_url: location.href
	          }];
	        }
	      }
	      query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
	      query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {
	        'CACHE': 'N'
	      }];
	      query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];
	      return query;
	    }
	  }, {
	    key: "getOpenSessionQuery",
	    value: function getOpenSessionQuery(chatId) {
	      // imopenlines.widget.dialog.get
	      var query = babelHelpers.defineProperty({}, RestMethod.widgetDialogGet, [RestMethod.widgetDialogGet, {
	        config_id: this.getConfigId(),
	        chat_id: chatId
	      }]);
	      query[im_const.RestMethodHandler.imChatGet] = [im_const.RestMethod.imChatGet, {
	        dialog_id: "chat".concat(chatId)
	      }];

	      // im.dialog.messages.get
	      query[im_const.RestMethodHandler.imDialogMessagesGetInit] = [im_const.RestMethod.imDialogMessagesGet, {
	        chat_id: chatId,
	        limit: 50,
	        convert_text: 'Y'
	      }];
	      return query;
	    }
	  }, {
	    key: "getCreateChatRequestQuery",
	    value: function getCreateChatRequestQuery() {
	      var query = {};

	      // widget.chat.register
	      query[RestMethod.widgetChatCreate] = [RestMethod.widgetChatCreate, _objectSpread({
	        config_id: this.getConfigId()
	      }, this.getUserRegisterFields())];

	      // im.chat.get
	      query[im_const.RestMethodHandler.imChatGet] = [im_const.RestMethod.imChatGet, {
	        dialog_id: "$result[".concat(RestMethod.widgetChatCreate, "][dialogId]")
	      }];

	      // widget.dialog.get
	      query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	        config_id: this.getConfigId(),
	        trace_data: this.getCrmTraceData(),
	        custom_data: this.getCustomData()
	      }];
	      if (this.isUserAgreeConsent()) {
	        // widget.user.consent.apply
	        query[RestMethod.widgetUserConsentApply] = [RestMethod.widgetUserConsentApply, {
	          config_id: this.getConfigId(),
	          consent_url: location.href
	        }];
	      }
	      query[RestMethod.pullServerTime] = [RestMethod.pullServerTime, {}];
	      query[RestMethod.pullConfigGet] = [RestMethod.pullConfigGet, {
	        'CACHE': 'N'
	      }];
	      query[RestMethod.widgetUserGet] = [RestMethod.widgetUserGet, {}];
	      return query;
	    }
	  }, {
	    key: "openSession",
	    value: function openSession(event) {
	      var _this7 = this;
	      var eventData = event.getData();
	      return new Promise(function (resolve, reject) {
	        var dialog = _this7.controller.getStore().getters['dialogues/get'](eventData.session.dialogId);
	        if (dialog) {
	          _this7.controller.getStore().commit('application/set', {
	            dialog: {
	              chatId: eventData.session.chatId,
	              dialogId: eventData.session.dialogId,
	              diskFolderId: 0
	            }
	          });
	          _this7.controller.getStore().commit('widget/common', {
	            isCreateSessionMode: false
	          });
	          resolve();
	          return;
	        }
	        _this7.controller.restClient.callBatch(_this7.getOpenSessionQuery(eventData.session.chatId), function (result) {
	          _this7.handleBatchOpenSessionRequestResult(result).then(function () {
	            _this7.controller.getStore().commit('widget/common', {
	              isCreateSessionMode: false
	            });
	            resolve();
	          });
	        }, false, false);
	      });
	    }
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      var _this8 = this;
	      if (!Array.isArray(files)) {
	        return files;
	      }
	      return files.map(function (file) {
	        var hash = (window.md5 || main_md5.md5)("".concat(_this8.getUserId(), "|").concat(file.id, "|").concat(_this8.getUserHash()));
	        var urlParam = "livechat_auth_id=".concat(hash, "&livechat_user_id=").concat(_this8.getUserId());
	        if (file.urlPreview) {
	          file.urlPreview = "".concat(file.urlPreview, "&").concat(urlParam);
	        }
	        if (file.urlShow) {
	          file.urlShow = "".concat(file.urlShow, "&").concat(urlParam);
	        }
	        if (file.urlDownload) {
	          file.urlDownload = "".concat(file.urlDownload, "&").concat(urlParam);
	        }
	        return file;
	      });
	    }
	  }, {
	    key: "checkRequiredFields",
	    value: function checkRequiredFields() {
	      var errors = [];
	      if (typeof this.code === 'string' && this.code.length <= 0) {
	        errors.push("LiveChatWidget.constructor: code is not correct (".concat(this.code, ")"));
	      }
	      if (typeof this.host === 'string' && (this.host.length <= 0 || !this.host.startsWith('http'))) {
	        errors.push("LiveChatWidget.constructor: host is not correct (".concat(this.host, ")"));
	      }
	      return errors;
	    }
	  }, {
	    key: "setRootNode",
	    value: function setRootNode() {
	      if (this.pageMode && this.pageMode.placeholder) {
	        this.rootNode = this.pageMode.placeholder;
	      } else if (document.body.firstChild) {
	        document.body.insertBefore(this.rootNode, document.body.firstChild);
	      } else {
	        document.body.append(this.rootNode);
	      }
	    }
	  }, {
	    key: "setLocalize",
	    value: function setLocalize() {
	      if (babelHelpers["typeof"](this.params.localize) === 'object') {
	        this.addLocalize(this.params.localize);
	      }
	      var serverVariables = im_lib_localstorage.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);
	      if (serverVariables) {
	        this.addLocalize(serverVariables);
	      }
	    }
	  }, {
	    key: "getWidgetVariables",
	    value: function getWidgetVariables() {
	      var variables = {
	        common: {
	          host: this.getHost(),
	          pageMode: !!this.pageMode,
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
	      if (this.params.styles) {
	        variables.styles = {};
	        if (this.params.styles.backgroundColor) {
	          variables.styles.backgroundColor = this.params.styles.backgroundColor;
	        }
	        if (this.params.styles.iconColor) {
	          variables.styles.iconColor = this.params.styles.iconColor;
	        }
	      }
	      return variables;
	    }
	  }, {
	    key: "getRestAuthId",
	    value: function getRestAuthId() {
	      return this.isUserRegistered() ? this.getUserHash() : RestAuth.guest;
	    }
	  }, {
	    key: "setModelData",
	    value: function setModelData() {
	      if (this.params.location && LocationStyle[this.params.location]) {
	        this.controller.getStore().commit('widget/common', {
	          location: this.params.location
	        });
	      }
	    }
	  }, {
	    key: "checkForErrorsBeforeOpen",
	    value: function checkForErrorsBeforeOpen() {
	      var result = {
	        error: false,
	        stop: false
	      };
	      if (!this.checkBrowserVersion()) {
	        this.setError('OLD_BROWSER_LOCALIZED', this.localize.BX_LIVECHAT_OLD_BROWSER);
	        result.error = true;
	      } else if (im_lib_utils.Utils.versionCompare(ui_vue.Vue.version(), '2.1') < 0) {
	        alert(this.localize.BX_LIVECHAT_OLD_VUE);
	        console.error("LiveChatWidget.error: OLD_VUE_VERSION (".concat(this.localize.BX_LIVECHAT_OLD_VUE_DEV.replace('#CURRENT_VERSION#', ui_vue.Vue.version()), ")"));
	        result.error = true;
	        result.stop = true;
	      } else if (this.isSameDomain()) {
	        this.setError('LIVECHAT_SAME_DOMAIN', this.localize.BX_LIVECHAT_SAME_DOMAIN);
	        result.error = true;
	      }
	      return result;
	    }
	  }, {
	    key: "isSameDomain",
	    value: function isSameDomain() {
	      if (typeof BX === 'undefined' || !BX.isReady) {
	        return false;
	      }
	      if (!this.options.checkSameDomain) {
	        return false;
	      }
	      return this.host.lastIndexOf(".".concat(location.hostname)) > -1;
	    }
	  }, {
	    key: "checkBrowserVersion",
	    value: function checkBrowserVersion() {
	      if (im_lib_utils.Utils.platform.isIos()) {
	        var version = im_lib_utils.Utils.platform.getIosVersion();
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
	      var _this9 = this;
	      return new Promise(function (resolve, reject) {
	        if (!_this9.getUserId() || !_this9.getSiteId() || !_this9.restClient) {
	          return reject({
	            ex: {
	              error: 'WIDGET_NOT_LOADED',
	              error_description: 'Widget is not loaded.'
	            }
	          });
	        }
	        if (_this9.pullClientInited) {
	          if (!_this9.pullClient.isConnected()) {
	            _this9.pullClient.scheduleReconnect();
	          }
	          return resolve(true);
	        }
	        _this9.controller.userId = _this9.getUserId();
	        _this9.pullClient.userId = _this9.getUserId();
	        _this9.pullClient.configTimestamp = config ? config.server.config_timestamp : 0;
	        _this9.pullClient.skipStorageInit = false;
	        _this9.pullClient.storage = new pull_client.PullClient.StorageManager({
	          userId: _this9.getUserId(),
	          siteId: _this9.getSiteId()
	        });
	        _this9.pullClient.subscribe(new WidgetImPullCommandHandler({
	          store: _this9.controller.getStore(),
	          controller: _this9.controller,
	          widget: _this9
	        }));
	        _this9.pullClient.subscribe(new WidgetImopenlinesPullCommandHandler({
	          store: _this9.controller.getStore(),
	          controller: _this9.controller,
	          widget: _this9
	        }));
	        _this9.pullClient.subscribe({
	          type: pull_client.PullClient.SubscriptionType.Status,
	          callback: _this9.eventStatusInteraction.bind(_this9)
	        });
	        _this9.pullConnectedFirstTime = _this9.pullClient.subscribe({
	          type: pull_client.PullClient.SubscriptionType.Status,
	          callback: function callback(result) {
	            if (result.status === pull_client.PullClient.PullStatus.Online) {
	              resolve(true);
	              _this9.pullConnectedFirstTime();
	            }
	          }
	        });
	        if (_this9.template) {
	          _this9.template.$Bitrix.PullClient.set(_this9.pullClient);
	        }
	        _this9.pullClient.start(_objectSpread(_objectSpread({}, config), {}, {
	          skipReconnectToLastSession: true
	        }))["catch"](function () {
	          reject({
	            ex: {
	              error: 'PULL_CONNECTION_ERROR',
	              error_description: 'Pull is not connected.'
	            }
	          });
	        });
	        _this9.pullClientInited = true;
	      });
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
	      if (data.status === pull_client.PullClient.PullStatus.Online) {
	        this.onPullOnlineStatus();
	      } else if (data.status === pull_client.PullClient.PullStatus.Offline) {
	        this.pullRequestMessage = true;
	        this.offline = true;
	      }
	    }
	  }, {
	    key: "onPullOnlineStatus",
	    value: function onPullOnlineStatus() {
	      var _this10 = this;
	      this.offline = false;

	      // if we go online after going offline - we need to request messages
	      if (this.pullRequestMessage) {
	        this.controller.pullBaseHandler.option.skip = true;
	        im_lib_logger.Logger.warn('Requesting getDialogUnread after going online');
	        main_core_events.EventEmitter.emitAsync(im_const.EventType.dialog.requestUnread, {
	          chatId: this.controller.application.getChatId()
	        }).then(function () {
	          main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollOnStart, {
	            chatId: _this10.controller.application.getChatId()
	          });
	          _this10.controller.pullBaseHandler.option.skip = false;
	          main_core_events.EventEmitter.emit(WidgetEventType.processMessagesToSendQueue);
	        })["catch"](function () {
	          _this10.controller.pullBaseHandler.option.skip = false;
	        });
	        this.pullRequestMessage = false;
	      } else {
	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.readMessage);
	        main_core_events.EventEmitter.emit(WidgetEventType.processMessagesToSendQueue);
	      }
	    }
	    /* endregion 02. Push & Pull */
	    /* region 03. Template engine */
	  }, {
	    key: "attachTemplate",
	    value: function attachTemplate() {
	      if (this.template) {
	        this.controller.getStore().commit('widget/common', {
	          showed: true
	        });
	        return true;
	      }
	      this.rootNode.innerHTML = '';
	      this.rootNode.append(document.createElement('div'));
	      var application = this;
	      return this.controller.createVue(application, {
	        el: this.rootNode.firstChild,
	        template: '<bx-livechat/>',
	        beforeCreate: function beforeCreate() {
	          application.sendEvent({
	            type: SubscriptionType.widgetOpen,
	            data: {}
	          });
	          application.template = this;
	          if (main_core_minimal.ZIndexManager !== undefined) {
	            var stack = main_core_minimal.ZIndexManager.getOrAddStack(document.body);
	            stack.setBaseIndex(1000000); // some big value
	            this.$bitrix.Data.set('zIndexStack', stack);
	          }
	        },
	        destroyed: function destroyed() {
	          application.sendEvent({
	            type: SubscriptionType.widgetClose,
	            data: {}
	          });
	          application.template = null;
	          application.templateAttached = false;
	          application.rootNode.innerHTML = '';
	        }
	      }).then(function () {
	        return new Promise(function (resolve, reject) {
	          return resolve();
	        });
	      });
	    }
	  }, {
	    key: "detachTemplate",
	    value: function detachTemplate() {
	      if (!this.template) {
	        return true;
	      }
	      this.template.$destroy();
	      return true;
	    } // public method
	  }, {
	    key: "mutateTemplateComponent",
	    value: function mutateTemplateComponent(id, params) {
	      return ui_vue.Vue.mutateComponent(id, params);
	    }
	    /* endregion 03. Template engine */
	    /* region 04. Widget interaction and utils */
	    // public method
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
	    key: "fireInitEvent",
	    value: function fireInitEvent() {
	      if (this.initEventFired) {
	        return true;
	      }
	      this.sendEvent({
	        type: SubscriptionType.configLoaded,
	        data: {}
	      });
	      if (this.controller.getStore().state.widget.common.reopen) {
	        this.open();
	      }
	      this.initEventFired = true;
	    }
	  }, {
	    key: "isUserRegistered",
	    value: function isUserRegistered() {
	      return !!this.getUserHash();
	    }
	  }, {
	    key: "isConfigDataLoaded",
	    value: function isConfigDataLoaded() {
	      return this.controller.getStore().state.widget.common.configId;
	    }
	  }, {
	    key: "isChatLoaded",
	    value: function isChatLoaded() {
	      return this.controller.getStore().state.application.dialog.chatId > 0;
	    }
	  }, {
	    key: "isSessionActive",
	    value: function isSessionActive() {
	      return !this.controller.getStore().state.widget.dialog.sessionClose;
	    }
	  }, {
	    key: "isUserAgreeConsent",
	    value: function isUserAgreeConsent() {
	      return this.controller.getStore().state.widget.dialog.userConsent;
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
	          MESSAGE: this.localize.BX_LIVECHAT_EXTRA_SITE + ': [URL]' + location.href + '[/URL]'
	        }];
	      }
	      return JSON.stringify(customData);
	    }
	  }, {
	    key: "isUserLoaded",
	    value: function isUserLoaded() {
	      return this.controller.getStore().state.widget.user.id > 0;
	    }
	  }, {
	    key: "isUserReady",
	    value: function isUserReady() {
	      return this.isConfigDataLoaded() && this.isUserRegistered();
	    }
	  }, {
	    key: "isHashAvailable",
	    value: function isHashAvailable() {
	      return !this.isUserRegistered() && (this.userRegisterData.hash || this.getUserHashCookie());
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
	      return this.controller.getStore().state.widget.common.configId;
	    }
	  }, {
	    key: "isDialogStart",
	    value: function isDialogStart() {
	      return this.controller.getStore().state.widget.common.dialogStart;
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return this.controller.getStore().state.application.dialog.chatId;
	    }
	  }, {
	    key: "getDialogId",
	    value: function getDialogId() {
	      return this.controller.getStore().state.application.dialog.dialogId;
	    }
	  }, {
	    key: "getDiskFolderId",
	    value: function getDiskFolderId() {
	      return this.controller.getStore().state.application.dialog.diskFolderId;
	    }
	  }, {
	    key: "getDialogData",
	    value: function getDialogData() {
	      var dialogId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.getDialogId();
	      return this.controller.getStore().state.dialogues.collection[dialogId];
	    }
	  }, {
	    key: "getSessionId",
	    value: function getSessionId() {
	      return this.controller.getStore().state.widget.dialog.sessionId;
	    }
	  }, {
	    key: "isSessionClose",
	    value: function isSessionClose() {
	      return this.controller.getStore().state.widget.dialog.sessionClose;
	    }
	  }, {
	    key: "getUserHash",
	    value: function getUserHash() {
	      return this.controller.getStore().state.widget.user.hash;
	    }
	  }, {
	    key: "getUserHashCookie",
	    value: function getUserHashCookie() {
	      var userHash = '';
	      var cookie = im_lib_cookie.Cookie.get(null, 'LIVECHAT_HASH');
	      if (typeof cookie === 'string' && cookie.match(/^[a-f0-9]{32}$/)) {
	        userHash = cookie;
	      } else {
	        var _cookie = im_lib_cookie.Cookie.get(this.getSiteId(), 'LIVECHAT_HASH');
	        if (typeof _cookie === 'string' && _cookie.match(/^[a-f0-9]{32}$/)) {
	          userHash = _cookie;
	        }
	      }
	      return userHash;
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      return this.controller.getStore().state.widget.user.id;
	    }
	  }, {
	    key: "getUserData",
	    value: function getUserData() {
	      if (!this.controller || !this.controller.getStore()) {
	        console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
	        return false;
	      }
	      return this.controller.getStore().state.widget.user;
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
	        'user_hash': this.userRegisterData.hash || this.getUserHashCookie() || '',
	        'consent_url': this.controller.getStore().state.widget.common.consentUrl ? location.href : '',
	        'trace_data': this.getCrmTraceData(),
	        'custom_data': this.getCustomData()
	      };
	    }
	  }, {
	    key: "getWidgetLocationCode",
	    value: function getWidgetLocationCode() {
	      return LocationStyle[this.controller.getStore().state.widget.common.location];
	    } // public method
	  }, {
	    key: "setUserRegisterData",
	    value: function setUserRegisterData(params) {
	      if (!this.controller || !this.controller.getStore()) {
	        console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
	        return false;
	      }
	      var validUserFields = ['hash', 'name', 'lastName', 'avatar', 'email', 'www', 'gender', 'position'];
	      if (!im_lib_utils.Utils.types.isPlainObject(params)) {
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
	      if (this.userRegisterData.hash && this.getUserHash() && this.userRegisterData.hash !== this.getUserHash()) {
	        this.setNewAuthToken(this.userRegisterData.hash);
	      }
	    }
	  }, {
	    key: "setNewAuthToken",
	    value: function setNewAuthToken() {
	      var authToken = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      this.controller.getStoreBuilder().clearModelState();
	      im_lib_cookie.Cookie.set(null, 'LIVECHAT_HASH', '', {
	        expires: 365 * 86400,
	        path: '/'
	      });
	      this.controller.restClient.setAuthId(RestAuth.guest, authToken);
	    } // public method
	  }, {
	    key: "setOption",
	    value: function setOption(name, value) {
	      this.options[name] = value;
	      return true;
	    } // public method
	  }, {
	    key: "setCustomData",
	    value: function setCustomData(params) {
	      if (!this.controller || !this.controller.getStore()) {
	        console.error('LiveChatWidget.getUserData: method can be called after fired event - onBitrixLiveChat');
	        return false;
	      }
	      var result = [];
	      if (params instanceof Array) {
	        params.forEach(function (element) {
	          if (element && babelHelpers["typeof"](element) === 'object') {
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
	      if (code === 'LIVECHAT_AUTH_FAILED') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_AUTH_FAILED').replace('#LINK_START#', '<a href="javascript:void();" onclick="location.reload()">').replace('#LINK_END#', '</a>');
	        this.setNewAuthToken();
	      } else if (code === 'LIVECHAT_AUTH_PORTAL_USER') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_PORTAL_USER_NEW').replace('#LINK_START#', '<a href="' + this.host + '">').replace('#LINK_END#', '</a>');
	      } else if (code === 'LIVECHAT_SAME_DOMAIN') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_SAME_DOMAIN');
	        var link = this.getLocalize('BX_LIVECHAT_SAME_DOMAIN_LINK');
	        if (link) {
	          localizeDescription += '<br><br><a href="' + link + '">' + this.getLocalize('BX_LIVECHAT_SAME_DOMAIN_MORE') + '</a>';
	        }
	      } else if (code.endsWith('LOCALIZED')) {
	        localizeDescription = description;
	      }
	      this.controller.getStore().commit('application/set', {
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
	      this.controller.getStore().commit('application/set', {
	        error: {
	          active: false,
	          code: '',
	          description: ''
	        }
	      });
	    } // public method
	  }, {
	    key: "subscribe",
	    value: function subscribe(params) {
	      if (!im_lib_utils.Utils.types.isPlainObject(params)) {
	        console.error("%cLiveChatWidget.subscribe: params is not a object", "color: black;");
	        return false;
	      }
	      if (!SubscriptionTypeCheck.includes(params.type)) {
	        console.error("%cLiveChatWidget.subscribe: subscription type is not correct (%c".concat(params.type, "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
	        return false;
	      }
	      if (typeof params.callback !== 'function') {
	        console.error("%cLiveChatWidget.subscribe: callback is not a function (%c".concat(babelHelpers["typeof"](params.callback), "%c)"), "color: black;", "font-weight: bold; color: red", "color: black");
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
	  }, {
	    key: "sendEvent",
	    value: function sendEvent(params) {
	      params = params || {};
	      if (!params.type) {
	        return false;
	      }
	      if (babelHelpers["typeof"](params.data) !== 'object' || !params.data) {
	        params.data = {};
	      }
	      if (this.subscribers[params.type] instanceof Array && this.subscribers[params.type].length > 0) {
	        this.subscribers[params.type].forEach(function (callback) {
	          return callback(params.data);
	        });
	      }
	      if (this.subscribers[SubscriptionType.every] instanceof Array && this.subscribers[SubscriptionType.every].length > 0) {
	        this.subscribers[SubscriptionType.every].forEach(function (callback) {
	          return callback({
	            type: params.type,
	            data: params.data
	          });
	        });
	      }
	      return true;
	    } // public method
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      if (babelHelpers["typeof"](phrases) !== "object" || !phrases) {
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
	    /* endregion 04. Widget interaction and utils */
	    /* region 05. Rest batch handlers */
	  }, {
	    key: "handleConfigGet",
	    value: function handleConfigGet(response) {
	      var _this11 = this;
	      return new Promise(function (resolve, reject) {
	        var configGet = response[RestMethod.widgetConfigGet];
	        if (configGet && configGet.error()) {
	          return reject({
	            code: configGet.error().ex.error,
	            description: configGet.error().ex.error_description
	          });
	        }
	        _this11.controller.executeRestAnswer(RestMethod.widgetConfigGet, configGet);
	        resolve();
	      });
	    }
	  }, {
	    key: "handleUserGet",
	    value: function handleUserGet(response) {
	      var _this12 = this;
	      return new Promise(function (resolve, reject) {
	        var userGetResult = response[RestMethod.widgetUserGet];
	        if (userGetResult.error()) {
	          return reject({
	            code: userGetResult.error().ex.error,
	            description: userGetResult.error().ex.error_description
	          });
	        }
	        _this12.controller.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);
	        resolve();
	      });
	    }
	  }, {
	    key: "handleChatGet",
	    value: function handleChatGet(response) {
	      var _this13 = this;
	      return new Promise(function (resolve, reject) {
	        var chatGetResult = response[im_const.RestMethodHandler.imChatGet];
	        if (chatGetResult.error()) {
	          return reject({
	            code: chatGetResult.error().ex.error,
	            description: chatGetResult.error().ex.error_description
	          });
	        }
	        _this13.controller.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);
	        resolve();
	      });
	    }
	  }, {
	    key: "handleDialogGet",
	    value: function handleDialogGet(response) {
	      var _this14 = this;
	      return new Promise(function (resolve, reject) {
	        var dialogGetResult = response[RestMethod.widgetDialogGet];
	        if (!dialogGetResult) {
	          return resolve();
	        }
	        if (dialogGetResult.error()) {
	          return reject({
	            code: dialogGetResult.error().ex.error,
	            description: dialogGetResult.error().ex.error_description
	          });
	        }
	        _this14.controller.executeRestAnswer(RestMethod.widgetDialogGet, dialogGetResult);
	        resolve();
	      });
	    }
	  }, {
	    key: "handleDialogMessagesGet",
	    value: function handleDialogMessagesGet(response) {
	      var _this15 = this;
	      return new Promise(function (resolve, reject) {
	        var dialogMessagesGetResult = response[im_const.RestMethodHandler.imDialogMessagesGetInit];
	        if (!dialogMessagesGetResult) {
	          return resolve();
	        }
	        if (dialogMessagesGetResult.error()) {
	          return reject({
	            code: dialogMessagesGetResult.error().ex.error,
	            description: dialogMessagesGetResult.error().ex.error_description
	          });
	        }
	        _this15.controller.getStore().dispatch('dialogues/saveDialog', {
	          dialogId: _this15.controller.application.getDialogId(),
	          chatId: _this15.controller.application.getChatId()
	        });
	        _this15.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
	        resolve();
	      });
	    }
	  }, {
	    key: "handleUserRegister",
	    value: function handleUserRegister(response) {
	      var _this16 = this;
	      return new Promise(function (resolve, reject) {
	        var userRegisterResult = response[RestMethod.widgetUserRegister];
	        if (!userRegisterResult) {
	          return resolve();
	        }
	        if (userRegisterResult.error()) {
	          return reject({
	            code: userRegisterResult.error().ex.error,
	            description: userRegisterResult.error().ex.error_description
	          });
	        }
	        _this16.controller.executeRestAnswer(RestMethod.widgetUserRegister, userRegisterResult);
	        resolve();
	      });
	    }
	  }, {
	    key: "handleChatCreate",
	    value: function handleChatCreate(response) {
	      var _this17 = this;
	      return new Promise(function (resolve, reject) {
	        var chatCreateResult = response[RestMethod.widgetChatCreate];
	        if (!chatCreateResult) {
	          return resolve();
	        }
	        if (chatCreateResult.error()) {
	          return reject({
	            code: chatCreateResult.error().ex.error,
	            description: chatCreateResult.error().ex.error_description
	          });
	        }
	        _this17.controller.executeRestAnswer(RestMethod.widgetChatCreate, chatCreateResult);
	        resolve();
	      });
	    }
	  }, {
	    key: "handlePullRequests",
	    value: function handlePullRequests(response) {
	      var _this18 = this;
	      return new Promise(function (resolve) {
	        var timeShift = 0;
	        var serverTimeResult = response[RestMethod.pullServerTime];
	        if (serverTimeResult && !serverTimeResult.error()) {
	          timeShift = Math.floor((Date.now() - new Date(serverTimeResult.data()).getTime()) / 1000);
	        }
	        var config = null;
	        var pullConfigResult = response[RestMethod.pullConfigGet];
	        if (pullConfigResult && !pullConfigResult.error()) {
	          config = pullConfigResult.data();
	          config.server.timeShift = timeShift;
	        }
	        _this18.startPullClient(config).then(function () {
	          main_core_events.EventEmitter.emit(WidgetEventType.processMessagesToSendQueue);
	        })["catch"](function (error) {
	          _this18.setError(error.ex.error, error.ex.error_description);
	        })["finally"](resolve);
	      });
	    } /* endregion 05. Rest batch handlers */
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
	var WidgetPublicManager = /*#__PURE__*/function () {
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

	var WidgetSendMessageHandler = /*#__PURE__*/function (_SendMessageHandler) {
	  babelHelpers.inherits(WidgetSendMessageHandler, _SendMessageHandler);
	  function WidgetSendMessageHandler($Bitrix) {
	    var _this;
	    babelHelpers.classCallCheck(this, WidgetSendMessageHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetSendMessageHandler).call(this, $Bitrix));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "application", null);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "storedMessage", null);
	    _this.application = $Bitrix.Application.get();
	    _this.onProcessQueueHandler = _this.processQueue.bind(babelHelpers.assertThisInitialized(_this));
	    _this.onConsentAcceptedHandler = _this.onConsentAccepted.bind(babelHelpers.assertThisInitialized(_this));
	    _this.onConsentDeclinedHandler = _this.onConsentDeclined.bind(babelHelpers.assertThisInitialized(_this));
	    main_core_events.EventEmitter.subscribe(WidgetEventType.processMessagesToSendQueue, _this.onProcessQueueHandler);
	    main_core_events.EventEmitter.subscribe(WidgetEventType.consentAccepted, _this.onConsentAcceptedHandler);
	    main_core_events.EventEmitter.subscribe(WidgetEventType.consentDeclined, _this.onConsentDeclinedHandler);
	    return _this;
	  }
	  babelHelpers.createClass(WidgetSendMessageHandler, [{
	    key: "destroy",
	    value: function destroy() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(WidgetSendMessageHandler.prototype), "destroy", this).call(this);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.processMessagesToSendQueue, this.onProcessQueueHandler);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.consentAccepted, this.onConsentAcceptedHandler);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.consentDeclined, this.onConsentDeclinedHandler);
	    }
	  }, {
	    key: "onSendMessage",
	    value: function onSendMessage(_ref) {
	      var _this2 = this;
	      var event = _ref.data;
	      event.focus = event.focus !== false;

	      //hide smiles
	      if (this.getWidgetData().common.showForm === FormType.smile) {
	        main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	      }

	      //show consent window if needed
	      if (!this.getWidgetData().dialog.userConsent && this.getWidgetData().common.consentUrl) {
	        if (event.text) {
	          this.storedMessage = event.text;
	        }
	        main_core_events.EventEmitter.emit(WidgetEventType.showConsent);
	        return false;
	      }
	      event.text = event.text ? event.text : this.storedMessage;
	      if (!event.text && !event.file) {
	        return false;
	      }
	      main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	      if (this.isCreateSessionMode()) {
	        main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.stopWriting);
	        main_core_events.EventEmitter.emitAsync(WidgetEventType.createSession).then(function () {
	          _this2.store.commit('widget/common', {
	            isCreateSessionMode: false
	          });
	          _this2.sendMessage(event.text, event.file);
	        });
	      } else {
	        this.sendMessage(event.text, event.file);
	      }
	      if (event.focus) {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	      return true;
	    }
	  }, {
	    key: "sendMessage",
	    value: function sendMessage() {
	      var _this3 = this;
	      var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var file = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      if (!text && !file) {
	        return false;
	      }
	      var quoteId = this.store.getters['dialogues/getQuoteId'](this.getDialogId());
	      if (quoteId) {
	        var quoteMessage = this.store.getters['messages/getMessage'](this.getChatId(), quoteId);
	        if (quoteMessage) {
	          text = this.getMessageTextWithQuote(quoteMessage, text);
	          main_core_events.EventEmitter.emit(im_const.EventType.dialog.quotePanelClose);
	        }
	      }
	      if (!this.controller.application.isUnreadMessagesLoaded()) {
	        this.sendMessageToServer({
	          id: 0,
	          chatId: this.getChatId(),
	          dialogId: this.getDialogId(),
	          text: text,
	          file: file
	        });
	        this.processQueue();
	        return true;
	      }
	      var params = {};
	      if (file) {
	        params.FILE_ID = [file.id];
	      }
	      this.addMessageToModel({
	        text: text,
	        params: params,
	        sending: !file
	      }).then(function (messageId) {
	        if (!_this3.isDialogStart()) {
	          _this3.store.commit('widget/common', {
	            dialogStart: true
	          });
	        }
	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	          chatId: _this3.getChatId(),
	          cancelIfScrollChange: true
	        });
	        _this3.addMessageToQueue({
	          messageId: messageId,
	          text: text,
	          file: file
	        });
	        if (_this3.getChatId()) {
	          _this3.processQueue();
	        } else {
	          main_core_events.EventEmitter.emit(WidgetEventType.requestData);
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "onClickOnKeyboard",
	    value: function onClickOnKeyboard(_ref2) {
	      var event = _ref2.data;
	      if (event.action === 'ACTION' && event.params.action === 'LIVECHAT') {
	        var _event$params = event.params,
	          dialogId = _event$params.dialogId,
	          messageId = _event$params.messageId,
	          value = _event$params.value;
	        var values = JSON.parse(value);
	        var sessionId = Number.parseInt(values.SESSION_ID, 10);
	        if (sessionId !== this.getSessionId() || this.isSessionClose()) {
	          console.error('WidgetSendMessageHandler', this.loc['BX_LIVECHAT_ACTION_EXPIRED']);
	          return false;
	        }
	        this.restClient.callMethod(RestMethod.widgetActionSend, {
	          'MESSAGE_ID': messageId,
	          'DIALOG_ID': dialogId,
	          'ACTION_VALUE': value
	        });
	      }
	      if (event.action === 'COMMAND') {
	        var _event$params2 = event.params,
	          _dialogId = _event$params2.dialogId,
	          _messageId = _event$params2.messageId,
	          botId = _event$params2.botId,
	          command = _event$params2.command,
	          params = _event$params2.params;
	        this.restClient.callMethod(im_const.RestMethod.imMessageCommand, {
	          'MESSAGE_ID': _messageId,
	          'DIALOG_ID': _dialogId,
	          'BOT_ID': botId,
	          'COMMAND': command,
	          'COMMAND_PARAMS': params
	        })["catch"](function (error) {
	          return console.error('WidgetSendMessageHandler: command processing error', error);
	        });
	      }
	    }
	  }, {
	    key: "getWidgetData",
	    value: function getWidgetData() {
	      return this.store.state.widget;
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return this.store.state.application.dialog.chatId;
	    }
	  }, {
	    key: "getDialogId",
	    value: function getDialogId() {
	      return this.store.state.application.dialog.dialogId;
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      return this.store.state.widget.user.id;
	    }
	  }, {
	    key: "getMessageTextWithQuote",
	    value: function getMessageTextWithQuote(quoteMessage, text) {
	      var user = null;
	      if (quoteMessage.authorId) {
	        user = this.store.getters['users/get'](quoteMessage.authorId);
	      }
	      var files = this.store.getters['files/getList'](this.getChatId());
	      var quoteDelimiter = '-'.repeat(54);
	      var quoteTitle = user && user.name ? user.name : this.loc['BX_LIVECHAT_SYSTEM_MESSAGE'];
	      var quoteDate = im_lib_utils.Utils.date.format(quoteMessage.date, null, this.loc);
	      var quoteContent = im_lib_utils.Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.loc);
	      var message = [];
	      message.push(quoteDelimiter);
	      message.push("".concat(quoteTitle, " [").concat(quoteDate, "]"));
	      message.push(quoteContent);
	      message.push(quoteDelimiter);
	      message.push(text);
	      return message.join("\n");
	    }
	  }, {
	    key: "addMessageToQueue",
	    value: function addMessageToQueue(_ref3) {
	      var messageId = _ref3.messageId,
	        text = _ref3.text,
	        file = _ref3.file;
	      this.messagesToSend.push({
	        id: messageId,
	        chatId: this.getChatId(),
	        dialogId: this.getDialogId(),
	        text: text,
	        file: file,
	        sending: false
	      });
	    }
	  }, {
	    key: "sendMessageToServer",
	    value: function sendMessageToServer(message) {
	      var _this4 = this;
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.stopWriting);

	      // first message, when we didn't have chat
	      if (message.chatId === 0) {
	        message.chatId = this.getChatId();
	      }
	      this.restClient.callMethod(im_const.RestMethod.imMessageAdd, {
	        'TEMPLATE_ID': message.id,
	        'CHAT_ID': message.chatId,
	        'MESSAGE': message.text
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageAdd,
	        data: {
	          timMessageType: 'text'
	        },
	        dialog: this.getDialogData()
	      })).then(function (response) {
	        _this4.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, response, message);
	      })["catch"](function (error) {
	        _this4.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, error, message);
	        im_lib_logger.Logger.warn('Error during sending message', error);
	      });
	      return true;
	    }
	  }, {
	    key: "isDialogStart",
	    value: function isDialogStart() {
	      return this.store.state.widget.common.dialogStart;
	    }
	  }, {
	    key: "isCreateSessionMode",
	    value: function isCreateSessionMode() {
	      return this.store.state.widget.common.isCreateSessionMode;
	    }
	  }, {
	    key: "getDialogData",
	    value: function getDialogData() {
	      var dialogId = this.getDialogId();
	      return this.store.state.dialogues.collection[dialogId];
	    }
	  }, {
	    key: "getApplicationModel",
	    value: function getApplicationModel() {
	      return this.store.state.application;
	    }
	  }, {
	    key: "getSessionId",
	    value: function getSessionId() {
	      return this.store.state.widget.dialog.sessionId;
	    }
	  }, {
	    key: "isSessionClose",
	    value: function isSessionClose() {
	      return this.store.state.widget.dialog.sessionClose;
	    }
	  }, {
	    key: "processQueue",
	    value: function processQueue() {
	      var _this5 = this;
	      if (this.application.offline) {
	        return false;
	      }
	      this.messagesToSend.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        _this5.deleteFromQueue(element.id);
	        element.sending = true;
	        if (element.file) {
	          main_core_events.EventEmitter.emit(im_const.EventType.textarea.stopWriting);
	          main_core_events.EventEmitter.emit(im_const.EventType.uploader.addMessageWithFile, element);
	        } else {
	          _this5.sendMessageToServer(element);
	        }
	      });
	    }
	  }, {
	    key: "onConsentAccepted",
	    value: function onConsentAccepted() {
	      if (!this.storedMessage) {
	        return;
	      }
	      var isFocusNeeded = this.getApplicationModel().device.type !== im_const.DeviceType.mobile;
	      this.onSendMessage({
	        data: {
	          focus: isFocusNeeded
	        }
	      });
	      this.storedMessage = '';
	    }
	  }, {
	    key: "onConsentDeclined",
	    value: function onConsentDeclined() {
	      if (!this.storedMessage) {
	        return;
	      }
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.insertText, {
	        text: this.storedMessage,
	        focus: this.getApplicationModel().device.type !== im_const.DeviceType.mobile
	      });
	      this.storedMessage = '';
	    }
	  }]);
	  return WidgetSendMessageHandler;
	}(im_eventHandler.SendMessageHandler);

	var WidgetTextareaHandler = /*#__PURE__*/function (_TextareaHandler) {
	  babelHelpers.inherits(WidgetTextareaHandler, _TextareaHandler);
	  function WidgetTextareaHandler($Bitrix) {
	    var _this;
	    babelHelpers.classCallCheck(this, WidgetTextareaHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetTextareaHandler).call(this, $Bitrix));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "application", null);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "pullClient", null);
	    _this.application = $Bitrix.Application.get();
	    _this.pullClient = $Bitrix.PullClient.get();
	    return _this;
	  }
	  babelHelpers.createClass(WidgetTextareaHandler, [{
	    key: "onAppButtonClick",
	    value: function onAppButtonClick(_ref) {
	      var event = _ref.data;
	      if (event.appId === FormType.smile) {
	        if (this.getWidgetModel().common.showForm === FormType.smile) {
	          main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	        } else {
	          main_core_events.EventEmitter.emit(WidgetEventType.showForm, {
	            type: FormType.smile
	          });
	        }
	      } else {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	    }
	  }, {
	    key: "onFocus",
	    value: function onFocus() {
	      var _this2 = this;
	      if (this.getWidgetModel().common.copyright && this.getApplicationModel().device.type === im_const.DeviceType.mobile) {
	        this.getWidgetModel().common.copyright = false;
	      }
	      if (im_lib_utils.Utils.device.isMobile()) {
	        clearTimeout(this.onFocusScrollTimeout);
	        this.onScrollHandler = this.onScroll.bind(this);
	        this.onFocusScrollTimeout = setTimeout(function () {
	          document.addEventListener('scroll', _this2.onScrollHandler);
	        }, 1000);
	      }
	    }
	  }, {
	    key: "onBlur",
	    value: function onBlur() {
	      var _this3 = this;
	      if (!this.getWidgetModel().common.copyright && this.getWidgetModel().common.copyright !== this.application.copyright) {
	        this.getWidgetModel().common.copyright = this.application.copyright;
	        setTimeout(function () {
	          main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	            chatId: _this3.getChatId(),
	            force: true
	          });
	        }, 100);
	      }
	      if (im_lib_utils.Utils.device.isMobile()) {
	        clearTimeout(this.onFocusScrollTimeout);
	        document.removeEventListener('scroll', this.onScrollHandler);
	      }
	    } // send typed client message to operator
	  }, {
	    key: "onKeyUp",
	    value: function onKeyUp(_ref2) {
	      var event = _ref2.data;
	      if (this.canSendTypedText()) {
	        var sessionId = this.getWidgetModel().dialog.sessionId;
	        var chatId = this.getChatId();
	        var userId = this.getWidgetModel().user.id;
	        var infoString = main_md5.md5("".concat(sessionId, "/").concat(chatId, "/").concat(userId));
	        var operatorId = this.getWidgetModel().dialog.operator.id;
	        var operatorChatId = this.getWidgetModel().dialog.operatorChatId;
	        this.pullClient.sendMessage([operatorId], 'imopenlines', 'linesMessageWrite', {
	          text: event.text,
	          infoString: infoString,
	          operatorChatId: operatorChatId
	        });
	      }
	    }
	  }, {
	    key: "canSendTypedText",
	    value: function canSendTypedText() {
	      return this.getWidgetModel().common.watchTyping && this.getWidgetModel().dialog.sessionId && !this.getWidgetModel().dialog.sessionClose && this.getWidgetModel().dialog.operator.id && this.getWidgetModel().dialog.operatorChatId && this.pullClient.isPublishingEnabled();
	    }
	  }, {
	    key: "onScroll",
	    value: function onScroll() {
	      clearTimeout(this.onScrollTimeout);
	      this.onScrollTimeout = setTimeout(function () {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setBlur, true);
	      }, 50);
	    }
	  }, {
	    key: "getWidgetModel",
	    value: function getWidgetModel() {
	      return this.store.state.widget;
	    }
	  }, {
	    key: "getApplicationModel",
	    value: function getApplicationModel() {
	      return this.store.state.application;
	    }
	  }]);
	  return WidgetTextareaHandler;
	}(im_eventHandler.TextareaHandler);

	var WidgetTextareaUploadHandler = /*#__PURE__*/function (_TextareaUploadHandle) {
	  babelHelpers.inherits(WidgetTextareaUploadHandler, _TextareaUploadHandle);
	  function WidgetTextareaUploadHandler($Bitrix) {
	    var _this;
	    babelHelpers.classCallCheck(this, WidgetTextareaUploadHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetTextareaUploadHandler).call(this, $Bitrix));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "storedFile", null);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "widgetApplication", null);
	    _this.widgetApplication = $Bitrix.Application.get();
	    _this.onConsentAcceptedHandler = _this.onConsentAccepted.bind(babelHelpers.assertThisInitialized(_this));
	    _this.onConsentDeclinedHandler = _this.onConsentDeclined.bind(babelHelpers.assertThisInitialized(_this));
	    main_core_events.EventEmitter.subscribe(WidgetEventType.consentAccepted, _this.onConsentAcceptedHandler);
	    main_core_events.EventEmitter.subscribe(WidgetEventType.consentDeclined, _this.onConsentDeclinedHandler);
	    return _this;
	  }
	  babelHelpers.createClass(WidgetTextareaUploadHandler, [{
	    key: "destroy",
	    value: function destroy() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(WidgetTextareaUploadHandler.prototype), "destroy", this).call(this);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.consentAccepted, this.onConsentAcceptedHandler);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.consentDeclined, this.onConsentDeclinedHandler);
	    }
	  }, {
	    key: "getUserId",
	    value: function getUserId() {
	      return this.controller.store.state.widget.user.id;
	    }
	  }, {
	    key: "getUserHash",
	    value: function getUserHash() {
	      return this.controller.store.state.widget.user.hash;
	    }
	  }, {
	    key: "getHost",
	    value: function getHost() {
	      return this.controller.store.state.widget.common.host;
	    }
	  }, {
	    key: "addMessageWithFile",
	    value: function addMessageWithFile(event) {
	      var _this2 = this;
	      var message = event.getData();
	      if (!this.getDiskFolderId()) {
	        this.requestDiskFolderId(message.chatId).then(function () {
	          _this2.addMessageWithFile(event);
	        })["catch"](function (error) {
	          im_lib_logger.Logger.error('addMessageWithFile error', error);
	          return false;
	        });
	        return false;
	      }
	      this.uploader.senderOptions.customHeaders['Livechat-Dialog-Id'] = this.getDialogId();
	      this.uploader.senderOptions.customHeaders['Livechat-Auth-Id'] = this.getUserHash();
	      this.uploader.addTask({
	        taskId: message.file.id,
	        fileData: message.file.source.file,
	        fileName: message.file.source.file.name,
	        generateUniqueName: true,
	        diskFolderId: this.getDiskFolderId(),
	        previewBlob: message.file.previewBlob,
	        chunkSize: this.widgetApplication.getLocalize('isCloud') ? im_lib_uploader.Uploader.CLOUD_MAX_CHUNK_SIZE : im_lib_uploader.Uploader.BOX_MIN_CHUNK_SIZE
	      });
	    }
	  }, {
	    key: "onTextareaFileSelected",
	    value: function onTextareaFileSelected() {
	      var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
	        event = _ref.data;
	      var fileInputEvent = null;
	      if (event && event.fileChangeEvent && event.fileChangeEvent.target.files.length > 0) {
	        fileInputEvent = event.fileChangeEvent;
	      } else {
	        fileInputEvent = this.storedFile;
	      }
	      if (!fileInputEvent) {
	        return false;
	      }
	      if (!this.controller.store.state.widget.dialog.userConsent && this.controller.store.state.widget.common.consentUrl) {
	        this.storedFile = event.fileChangeEvent;
	        main_core_events.EventEmitter.emit(WidgetEventType.showConsent);
	        return false;
	      }
	      this.uploadFile(fileInputEvent);
	    }
	  }, {
	    key: "uploadFile",
	    value: function uploadFile(event) {
	      if (!event) {
	        return false;
	      }
	      if (!this.getChatId()) {
	        main_core_events.EventEmitter.emit(WidgetEventType.requestData);
	      }
	      this.uploader.addFilesFromEvent(event);
	    }
	  }, {
	    key: "onConsentAccepted",
	    value: function onConsentAccepted() {
	      if (!this.storedFile) {
	        return;
	      }
	      this.onTextareaFileSelected();
	      this.storedFile = '';
	    }
	  }, {
	    key: "onConsentDeclined",
	    value: function onConsentDeclined() {
	      if (!this.storedFile) {
	        return;
	      }
	      this.storedFile = '';
	    }
	  }, {
	    key: "getUploaderSenderOptions",
	    value: function getUploaderSenderOptions() {
	      return {
	        host: this.getHost(),
	        customHeaders: {
	          'Livechat-Auth-Id': this.getUserHash()
	        },
	        actionUploadChunk: 'imopenlines.widget.disk.upload',
	        actionCommitFile: 'imopenlines.widget.disk.commit',
	        actionRollbackUpload: 'imopenlines.widget.disk.rollbackUpload'
	      };
	    }
	  }]);
	  return WidgetTextareaUploadHandler;
	}(im_eventHandler.TextareaUploadHandler);

	var WidgetReadingHandler = /*#__PURE__*/function (_ReadingHandler) {
	  babelHelpers.inherits(WidgetReadingHandler, _ReadingHandler);
	  function WidgetReadingHandler($Bitrix) {
	    var _this;
	    babelHelpers.classCallCheck(this, WidgetReadingHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetReadingHandler).call(this, $Bitrix));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "application", null);
	    _this.application = $Bitrix.Application.get();
	    return _this;
	  }
	  babelHelpers.createClass(WidgetReadingHandler, [{
	    key: "readMessage",
	    value: function readMessage(messageId) {
	      var skipTimer = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var skipAjax = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      if (this.application.offline) {
	        return false;
	      }
	      return babelHelpers.get(babelHelpers.getPrototypeOf(WidgetReadingHandler.prototype), "readMessage", this).call(this, messageId, skipTimer, skipAjax);
	    }
	  }]);
	  return WidgetReadingHandler;
	}(im_eventHandler.ReadingHandler);

	var WidgetResizeHandler = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(WidgetResizeHandler, _EventEmitter);
	  function WidgetResizeHandler(_ref) {
	    var _this;
	    var widgetLocation = _ref.widgetLocation,
	      availableWidth = _ref.availableWidth,
	      availableHeight = _ref.availableHeight,
	      events = _ref.events;
	    babelHelpers.classCallCheck(this, WidgetResizeHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetResizeHandler).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "isResizing", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "widgetLocation", null);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "availableWidth", null);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "availableHeight", null);
	    _this.setEventNamespace('BX.IMOL.WidgetResizeHandler');
	    _this.subscribeToEvents(events);
	    _this.widgetLocation = widgetLocation;
	    _this.availableWidth = availableWidth;
	    _this.availableHeight = availableHeight;
	    return _this;
	  }
	  babelHelpers.createClass(WidgetResizeHandler, [{
	    key: "subscribeToEvents",
	    value: function subscribeToEvents(configEvents) {
	      var _this2 = this;
	      var events = main_core.Type.isObject(configEvents) ? configEvents : {};
	      Object.entries(events).forEach(function (_ref2) {
	        var _ref3 = babelHelpers.slicedToArray(_ref2, 2),
	          name = _ref3[0],
	          callback = _ref3[1];
	        if (main_core.Type.isFunction(callback)) {
	          _this2.subscribe(name, callback);
	        }
	      });
	    }
	  }, {
	    key: "startResize",
	    value: function startResize(event, currentHeight, currentWidth) {
	      if (this.isResizing) {
	        return false;
	      }
	      this.isResizing = true;
	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.cursorStartPointY = event.clientY;
	      this.cursorStartPointX = event.clientX;
	      this.heightStartPoint = currentHeight;
	      this.widthStartPoint = currentWidth;
	      this.addWidgetResizeEvents();
	    }
	  }, {
	    key: "onContinueResize",
	    value: function onContinueResize(event) {
	      if (!this.isResizing) {
	        return false;
	      }
	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.cursorControlPointY = event.clientY;
	      this.cursorControlPointX = event.clientX;
	      var maxHeight = this.isBottomLocation() ? Math.min(this.heightStartPoint + this.cursorStartPointY - this.cursorControlPointY, this.availableHeight) : Math.min(this.heightStartPoint - this.cursorStartPointY + this.cursorControlPointY, this.availableHeight);
	      var height = Math.max(maxHeight, WidgetMinimumSize.height);
	      var maxWidth = this.isLeftLocation() ? Math.min(this.widthStartPoint - this.cursorStartPointX + this.cursorControlPointX, this.availableWidth) : Math.min(this.widthStartPoint + this.cursorStartPointX - this.cursorControlPointX, this.availableWidth);
	      var width = Math.max(maxWidth, WidgetMinimumSize.width);
	      this.emit(WidgetResizeHandler.events.onSizeChange, {
	        newHeight: height,
	        newWidth: width
	      });
	    }
	  }, {
	    key: "onStopResize",
	    value: function onStopResize() {
	      if (!this.isResizing) {
	        return false;
	      }
	      this.isResizing = false;
	      this.removeWidgetResizeEvents();
	      this.emit(WidgetResizeHandler.events.onStopResize);
	    }
	  }, {
	    key: "setAvailableWidth",
	    value: function setAvailableWidth(width) {
	      this.availableWidth = width;
	    }
	  }, {
	    key: "setAvailableHeight",
	    value: function setAvailableHeight(height) {
	      this.availableHeight = height;
	    }
	  }, {
	    key: "addWidgetResizeEvents",
	    value: function addWidgetResizeEvents() {
	      this.onContinueResizeHandler = this.onContinueResize.bind(this);
	      this.onStopResizeHandler = this.onStopResize.bind(this);
	      document.addEventListener('mousemove', this.onContinueResizeHandler);
	      document.addEventListener('mouseup', this.onStopResizeHandler);
	      document.addEventListener('mouseleave', this.onStopResizeHandler);
	    }
	  }, {
	    key: "removeWidgetResizeEvents",
	    value: function removeWidgetResizeEvents() {
	      document.removeEventListener('mousemove', this.onContinueResizeHandler);
	      document.removeEventListener('mouseup', this.onStopResizeHandler);
	      document.removeEventListener('mouseleave', this.onStopResizeHandler);
	    }
	  }, {
	    key: "isBottomLocation",
	    value: function isBottomLocation() {
	      return [LocationType.bottomLeft, LocationType.bottomMiddle, LocationType.bottomRight].includes(this.widgetLocation);
	    }
	  }, {
	    key: "isLeftLocation",
	    value: function isLeftLocation() {
	      return [LocationType.bottomLeft, LocationType.topLeft, LocationType.topMiddle].includes(this.widgetLocation);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      this.removeWidgetResizeEvents();
	    }
	  }]);
	  return WidgetResizeHandler;
	}(main_core_events.EventEmitter);
	babelHelpers.defineProperty(WidgetResizeHandler, "events", {
	  onSizeChange: 'onSizeChange',
	  onStopResize: 'onStopResize'
	});

	var WidgetConsentHandler = /*#__PURE__*/function () {
	  function WidgetConsentHandler($Bitrix) {
	    babelHelpers.classCallCheck(this, WidgetConsentHandler);
	    babelHelpers.defineProperty(this, "store", null);
	    babelHelpers.defineProperty(this, "restClient", null);
	    babelHelpers.defineProperty(this, "application", null);
	    this.store = $Bitrix.Data.get('controller').store;
	    this.restClient = $Bitrix.RestClient.get();
	    this.application = $Bitrix.Application.get();
	    this.subscribeToEvents();
	  }
	  babelHelpers.createClass(WidgetConsentHandler, [{
	    key: "subscribeToEvents",
	    value: function subscribeToEvents() {
	      this.showConsentHandler = this.onShowConsent.bind(this);
	      this.acceptConsentHandler = this.onAcceptConsent.bind(this);
	      this.declineConsentHandler = this.onDeclineConsent.bind(this);
	      main_core_events.EventEmitter.subscribe(WidgetEventType.showConsent, this.showConsentHandler);
	      main_core_events.EventEmitter.subscribe(WidgetEventType.acceptConsent, this.acceptConsentHandler);
	      main_core_events.EventEmitter.subscribe(WidgetEventType.declineConsent, this.declineConsentHandler);
	    }
	  }, {
	    key: "onShowConsent",
	    value: function onShowConsent() {
	      this.showConsent();
	    }
	  }, {
	    key: "onAcceptConsent",
	    value: function onAcceptConsent() {
	      this.acceptConsent();
	    }
	  }, {
	    key: "onDeclineConsent",
	    value: function onDeclineConsent() {
	      this.declineConsent();
	    }
	  }, {
	    key: "showConsent",
	    value: function showConsent() {
	      this.store.commit('widget/common', {
	        showConsent: true
	      });
	    }
	  }, {
	    key: "hideConsent",
	    value: function hideConsent() {
	      this.store.commit('widget/common', {
	        showConsent: false
	      });
	    }
	  }, {
	    key: "acceptConsent",
	    value: function acceptConsent() {
	      this.hideConsent();
	      this.sendConsentDecision(true);
	      main_core_events.EventEmitter.emit(WidgetEventType.consentAccepted);
	      if (this.getWidgetModel().common.showForm === FormType.none) {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	    }
	  }, {
	    key: "declineConsent",
	    value: function declineConsent() {
	      main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	      this.hideConsent();
	      this.sendConsentDecision(false);
	      main_core_events.EventEmitter.emit(WidgetEventType.consentDeclined);
	      if (this.getApplicationModel().device.type !== im_const.DeviceType.mobile) {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	    }
	  }, {
	    key: "sendConsentDecision",
	    value: function sendConsentDecision(result) {
	      this.store.commit('widget/dialog', {
	        userConsent: result
	      });
	      if (result && this.application.isUserRegistered()) {
	        this.restClient.callMethod(RestMethod.widgetUserConsentApply, {
	          config_id: this.getWidgetModel().common.configId,
	          consent_url: location.href
	        });
	      }
	    }
	  }, {
	    key: "getWidgetModel",
	    value: function getWidgetModel() {
	      return this.store.state.widget;
	    }
	  }, {
	    key: "getApplicationModel",
	    value: function getApplicationModel() {
	      return this.store.state.application;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.showConsent, this.showConsentHandler);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.acceptConsent, this.acceptConsentHandler);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.declineConsent, this.declineConsentHandler);
	    }
	  }]);
	  return WidgetConsentHandler;
	}();

	var WidgetFormHandler = /*#__PURE__*/function () {
	  function WidgetFormHandler($Bitrix) {
	    babelHelpers.classCallCheck(this, WidgetFormHandler);
	    babelHelpers.defineProperty(this, "store", null);
	    babelHelpers.defineProperty(this, "application", null);
	    babelHelpers.defineProperty(this, "restClient", null);
	    this.store = $Bitrix.Data.get('controller').store;
	    this.restClient = $Bitrix.RestClient.get();
	    this.application = $Bitrix.Application.get();
	    this.showFormHandler = this.onShowForm.bind(this);
	    this.hideFormHandler = this.onHideForm.bind(this);
	    this.sendVoteHandler = this.onSendVote.bind(this);
	    main_core_events.EventEmitter.subscribe(WidgetEventType.showForm, this.showFormHandler);
	    main_core_events.EventEmitter.subscribe(WidgetEventType.hideForm, this.hideFormHandler);
	    main_core_events.EventEmitter.subscribe(WidgetEventType.sendDialogVote, this.sendVoteHandler);
	  }
	  babelHelpers.createClass(WidgetFormHandler, [{
	    key: "onShowForm",
	    value: function onShowForm(_ref) {
	      var _this = this;
	      var event = _ref.data;
	      clearTimeout(this.showFormTimeout);
	      if (event.type === FormType.like) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this.showLikeForm();
	          }, 5000);
	        } else {
	          this.showLikeForm();
	        }
	      } else if (event.type === FormType.smile) {
	        this.showSmiles();
	      }
	    }
	  }, {
	    key: "onHideForm",
	    value: function onHideForm() {
	      this.hideForm();
	    }
	  }, {
	    key: "onSendVote",
	    value: function onSendVote(_ref2) {
	      var vote = _ref2.data.vote;
	      console.warn('VOTE', vote);
	      this.sendVote(vote);
	    }
	  }, {
	    key: "showLikeForm",
	    value: function showLikeForm() {
	      if (this.application.offline) {
	        return false;
	      }
	      clearTimeout(this.showFormTimeout);
	      if (!this.getWidgetModel().common.vote.enable) {
	        return false;
	      }
	      if (this.getWidgetModel().dialog.sessionClose && this.getWidgetModel().dialog.userVote !== VoteType.none) {
	        return false;
	      }
	      this.store.commit('widget/common', {
	        showForm: FormType.like
	      });
	    }
	  }, {
	    key: "showSmiles",
	    value: function showSmiles() {
	      this.store.commit('widget/common', {
	        showForm: FormType.smile
	      });
	    }
	  }, {
	    key: "sendVote",
	    value: function sendVote(vote) {
	      var _this2 = this;
	      var sessionId = this.getWidgetModel().dialog.sessionId;
	      if (!sessionId) {
	        return false;
	      }
	      this.restClient.callMethod(RestMethod.widgetVoteSend, {
	        'SESSION_ID': sessionId,
	        'ACTION': vote
	      })["catch"](function () {
	        _this2.store.commit('widget/dialog', {
	          userVote: VoteType.none
	        });
	      });
	      this.application.sendEvent({
	        type: SubscriptionType.userVote,
	        data: {
	          vote: vote
	        }
	      });
	    }
	  }, {
	    key: "hideForm",
	    value: function hideForm() {
	      clearTimeout(this.showFormTimeout);
	      if (this.getWidgetModel().common.showForm !== FormType.none) {
	        this.store.commit('widget/common', {
	          showForm: FormType.none
	        });
	      }
	    }
	  }, {
	    key: "getWidgetModel",
	    value: function getWidgetModel() {
	      return this.store.state.widget;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.showForm, this.showFormHandler);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.hideForm, this.hideFormHandler);
	      main_core_events.EventEmitter.unsubscribe(WidgetEventType.sendDialogVote, this.sendVoteHandler);
	    }
	  }]);
	  return WidgetFormHandler;
	}();

	var WidgetReactionHandler = /*#__PURE__*/function (_ReactionHandler) {
	  babelHelpers.inherits(WidgetReactionHandler, _ReactionHandler);
	  function WidgetReactionHandler() {
	    babelHelpers.classCallCheck(this, WidgetReactionHandler);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetReactionHandler).apply(this, arguments));
	  }
	  babelHelpers.createClass(WidgetReactionHandler, [{
	    key: "onOpenMessageReactionList",
	    value: function onOpenMessageReactionList(_ref) {
	      var data = _ref.data;
	      im_lib_logger.Logger.warn('Reactions list is blocked for the widget', data);
	    }
	  }]);
	  return WidgetReactionHandler;
	}(im_eventHandler.ReactionHandler);

	var WidgetHistoryHandler = /*#__PURE__*/function () {
	  function WidgetHistoryHandler($Bitrix) {
	    babelHelpers.classCallCheck(this, WidgetHistoryHandler);
	    babelHelpers.defineProperty(this, "store", null);
	    babelHelpers.defineProperty(this, "application", null);
	    this.store = $Bitrix.Data.get('controller').store;
	    this.application = $Bitrix.Application.get();
	  }
	  babelHelpers.createClass(WidgetHistoryHandler, [{
	    key: "getHtmlHistory",
	    value: function getHtmlHistory() {
	      var chatId = this.getChatId();
	      if (chatId <= 0) {
	        console.error('WidgetHistoryHandler: Incorrect chatId value');
	      }
	      var config = {
	        chatId: this.getChatId()
	      };
	      this.requestControllerAction('imopenlines.widget.history.download', config).then(this.handleRequest.bind(this)).then(this.downloadHistory.bind(this))["catch"](function (error) {
	        return console.error('WidgetHistoryHandler: fetch error.', error);
	      });
	    }
	  }, {
	    key: "requestControllerAction",
	    value: function requestControllerAction(action, config) {
	      var host = this.application.host ? this.application.host : '';
	      var ajaxEndpoint = '/bitrix/services/main/ajax.php';
	      var url = new URL(ajaxEndpoint, host);
	      url.searchParams.set('action', action);
	      var formData = new FormData();
	      for (var key in config) {
	        if (config.hasOwnProperty(key)) {
	          formData.append(key, config[key]);
	        }
	      }
	      return fetch(url, {
	        method: 'POST',
	        headers: {
	          'Livechat-Auth-Id': this.getUserHash()
	        },
	        body: formData
	      });
	    }
	  }, {
	    key: "handleRequest",
	    value: function handleRequest(response) {
	      var contentType = response.headers.get('Content-Type');
	      if (contentType.startsWith('application/json')) {
	        return response.json();
	      }
	      return response.blob();
	    }
	  }, {
	    key: "downloadHistory",
	    value: function downloadHistory(result) {
	      if (result instanceof Blob) {
	        var url = window.URL.createObjectURL(result);
	        var a = document.createElement('a');
	        a.href = url;
	        a.download = "".concat(this.getChatId(), ".html");
	        document.body.append(a);
	        a.click();
	        a.remove();
	      } else if (result.hasOwnProperty('errors')) {
	        console.error("WidgetHistoryHandler: ".concat(result.errors[0]));
	      } else {
	        console.error('WidgetHistoryHandler: unknown error.');
	      }
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return this.store.state.application.dialog.chatId;
	    }
	  }, {
	    key: "getUserHash",
	    value: function getUserHash() {
	      return this.store.state.widget.user.hash;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      //
	    }
	  }]);
	  return WidgetHistoryHandler;
	}();

	var WidgetDialogActionHandler = /*#__PURE__*/function (_DialogActionHandler) {
	  babelHelpers.inherits(WidgetDialogActionHandler, _DialogActionHandler);
	  function WidgetDialogActionHandler() {
	    babelHelpers.classCallCheck(this, WidgetDialogActionHandler);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(WidgetDialogActionHandler).apply(this, arguments));
	  }
	  babelHelpers.createClass(WidgetDialogActionHandler, [{
	    key: "onClickOnDialog",
	    value: function onClickOnDialog() {
	      main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	    }
	  }]);
	  return WidgetDialogActionHandler;
	}(im_eventHandler.DialogActionHandler);

	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	ui_vue.BitrixVue.component('bx-livechat', {
	  data: function data() {
	    return {
	      // sizes
	      widgetAvailableHeight: 0,
	      widgetAvailableWidth: 0,
	      widgetCurrentHeight: 0,
	      widgetCurrentWidth: 0,
	      widgetIsResizing: false,
	      textareaHeight: 100,
	      // welcome form
	      welcomeFormFilled: false,
	      // multi dialog
	      startNewChatMode: false
	    };
	  },
	  computed: _objectSpread$1({
	    FormType: function FormType$$1() {
	      return FormType;
	    },
	    VoteType: function VoteType$$1() {
	      return VoteType;
	    },
	    DeviceType: function DeviceType() {
	      return im_const.DeviceType;
	    },
	    EventType: function EventType() {
	      return im_const.EventType;
	    },
	    showTextarea: function showTextarea() {
	      if (this.widget.common.isCreateSessionMode) {
	        return this.startNewChatMode;
	      }
	      var crmFormsSettings = this.widget.common.crmFormsSettings;

	      // show if we dont use welcome form
	      if (!crmFormsSettings.useWelcomeForm || !crmFormsSettings.welcomeFormId) {
	        return true;
	      } else {
	        // show if we use welcome form with delay, otherwise check if it was filled
	        return crmFormsSettings.welcomeFormDelay ? true : this.welcomeFormFilled;
	      }
	    },
	    // for welcome CRM-form before dialog start
	    showWelcomeForm: function showWelcomeForm() {
	      //we are using welcome form, it doesnt have delay and it was not already filled
	      return this.widget.common.crmFormsSettings.useWelcomeForm && !this.widget.common.crmFormsSettings.welcomeFormDelay && this.widget.common.crmFormsSettings.welcomeFormId && !this.welcomeFormFilled;
	    },
	    textareaHeightStyle: function textareaHeightStyle() {
	      return {
	        flex: "0 0 ".concat(this.textareaHeight, "px")
	      };
	    },
	    textareaBottomMargin: function textareaBottomMargin() {
	      if (!this.widget.common.copyright && !this.isBottomLocation()) {
	        return {
	          marginBottom: '5px'
	        };
	      }
	      return '';
	    },
	    widgetHeightStyle: function widgetHeightStyle() {
	      if (im_lib_utils.Utils.device.isMobile() || this.widget.common.pageMode) {
	        return;
	      }
	      if (this.widgetAvailableHeight < WidgetBaseSize.height || this.widgetAvailableHeight < this.widgetCurrentHeight) {
	        this.widgetCurrentHeight = Math.max(this.widgetAvailableHeight, WidgetMinimumSize.height);
	      }
	      return "".concat(this.widgetCurrentHeight, "px");
	    },
	    widgetWidthStyle: function widgetWidthStyle() {
	      if (im_lib_utils.Utils.device.isMobile() || this.widget.common.pageMode) {
	        return;
	      }
	      if (this.widgetAvailableWidth < WidgetBaseSize.width || this.widgetAvailableWidth < this.widgetCurrentWidth) {
	        this.widgetCurrentWidth = Math.max(this.widgetAvailableWidth, WidgetMinimumSize.width);
	      }
	      return "".concat(this.widgetCurrentWidth, "px");
	    },
	    userSelectStyle: function userSelectStyle() {
	      return this.widgetIsResizing ? 'none' : 'auto';
	    },
	    widgetMobileDisabled: function widgetMobileDisabled() {
	      if (this.application.device.type !== im_const.DeviceType.mobile) {
	        return false;
	      }
	      if (this.application.device.orientation !== im_const.DeviceOrientation.horizontal) {
	        return false;
	      }
	      if (navigator.userAgent.toString().includes('iPhone')) {
	        return true;
	      } else {
	        return babelHelpers["typeof"](window.screen) !== 'object' || window.screen.availHeight < 800;
	      }
	    },
	    widgetPositionClass: function widgetPositionClass() {
	      var className = [];
	      if (this.widget.common.pageMode) {
	        className.push('bx-livechat-page-mode');
	      } else {
	        className.push("bx-livechat-position-".concat(LocationStyle[this.widget.common.location]));
	      }
	      return className;
	    },
	    widgetLanguageClass: function widgetLanguageClass() {
	      var className = [];
	      if (this.application.common.languageId === LanguageType.russian) {
	        className.push('bx-livechat-logo-ru');
	      } else if (this.application.common.languageId === LanguageType.ukraine) {
	        className.push('bx-livechat-logo-ua');
	      } else {
	        className.push('bx-livechat-logo-en');
	      }
	      return className;
	    },
	    widgetPlatformClass: function widgetPlatformClass() {
	      var className = [];
	      if (im_lib_utils.Utils.device.isMobile()) {
	        className.push('bx-livechat-mobile');
	      } else if (im_lib_utils.Utils.browser.isSafari()) {
	        className.push('bx-livechat-browser-safari');
	      } else if (im_lib_utils.Utils.browser.isIe()) {
	        className.push('bx-livechat-browser-ie');
	      }
	      if (im_lib_utils.Utils.platform.isMac()) {
	        className.push('bx-livechat-mac');
	      } else {
	        className.push('bx-livechat-custom-scroll');
	      }
	      return className;
	    },
	    widgetClassName: function widgetClassName() {
	      var className = [];
	      className.push.apply(className, babelHelpers.toConsumableArray(this.widgetPositionClass).concat(babelHelpers.toConsumableArray(this.widgetLanguageClass), babelHelpers.toConsumableArray(this.widgetPlatformClass)));
	      if (!this.widget.common.online) {
	        className.push('bx-livechat-offline-state');
	      }
	      if (this.widget.common.dragged) {
	        className.push('bx-livechat-drag-n-drop');
	      }
	      if (this.widget.common.dialogStart) {
	        className.push('bx-livechat-chat-start');
	      }
	      if (this.widget.dialog.operator.name && !(this.application.device.type === im_const.DeviceType.mobile && this.application.device.orientation === im_const.DeviceOrientation.horizontal)) {
	        className.push('bx-livechat-has-operator');
	      }
	      if (this.widget.common.styles.backgroundColor && im_lib_utils.Utils.isDarkColor(this.widget.common.styles.iconColor)) {
	        className.push('bx-livechat-bright-header');
	      }
	      return className;
	    },
	    showMessageDialog: function showMessageDialog() {
	      return this.messageCollection.length > 0;
	    },
	    localize: function localize() {
	      return ui_vue.BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    },
	    application: function application(state) {
	      return state.application;
	    },
	    dialog: function dialog(state) {
	      return state.dialogues.collection[state.application.dialog.dialogId];
	    },
	    messageCollection: function messageCollection(state) {
	      return state.messages.collection[state.application.dialog.chatId];
	    }
	  })),
	  created: function created() {
	    var _this = this;
	    im_lib_logger.Logger.warn('Livechat component created');
	    // we need to wait for initialization and widget opening to init logic handlers
	    this.onCreated().then(function () {
	      _this.subscribeToEvents();
	      _this.initEventHandlers();
	    });
	  },
	  mounted: function mounted() {
	    if (this.widget.user.id > 0) {
	      this.welcomeFormFilled = true;
	    }
	    this.registerZIndex();
	  },
	  beforeDestroy: function beforeDestroy() {
	    this.unsubscribeEvents();
	    this.destroyHandlers();
	    this.unregisterZIndex();
	  },
	  methods: {
	    // region initialization
	    initEventHandlers: function initEventHandlers() {
	      this.sendMessageHandler = new WidgetSendMessageHandler(this.$Bitrix);
	      this.textareaHandler = new WidgetTextareaHandler(this.$Bitrix);
	      this.textareaUploadHandler = new WidgetTextareaUploadHandler(this.$Bitrix);
	      this.readingHandler = new WidgetReadingHandler(this.$Bitrix);
	      this.consentHandler = new WidgetConsentHandler(this.$Bitrix);
	      this.formHandler = new WidgetFormHandler(this.$Bitrix);
	      this.textareaDragHandler = this.getTextareaDragHandler();
	      this.resizeHandler = this.getWidgetResizeHandler();
	      this.reactionHandler = new WidgetReactionHandler(this.$Bitrix);
	      this.historyHandler = new WidgetHistoryHandler(this.$Bitrix);
	      this.dialogActionHandler = new WidgetDialogActionHandler(this.$Bitrix);
	    },
	    destroyHandlers: function destroyHandlers() {
	      this.sendMessageHandler.destroy();
	      this.textareaHandler.destroy();
	      this.textareaUploadHandler.destroy();
	      this.readingHandler.destroy();
	      this.consentHandler.destroy();
	      this.formHandler.destroy();
	      this.textareaDragHandler.destroy();
	      this.resizeHandler.destroy();
	      this.reactionHandler.destroy();
	      this.historyHandler.destroy();
	      this.dialogActionHandler.destroy();
	    },
	    subscribeToEvents: function subscribeToEvents() {
	      document.addEventListener('keydown', this.onWindowKeyDown);
	      if (!im_lib_utils.Utils.device.isMobile() && !this.widget.common.pageMode) {
	        this.getAvailableSpaceFunc = im_lib_utils.Utils.throttle(this.getAvailableSpace, 50);
	        window.addEventListener('resize', this.getAvailableSpaceFunc);
	      }
	    },
	    unsubscribeEvents: function unsubscribeEvents() {
	      document.removeEventListener('keydown', this.onWindowKeyDown);
	      if (!im_lib_utils.Utils.device.isMobile() && !this.widget.common.pageMode) {
	        window.removeEventListener('resize', this.getAvailableSpaceFunc);
	      }
	    },
	    initMobileEnv: function initMobileEnv() {
	      var _this2 = this;
	      var metaTags = document.head.querySelectorAll('meta');
	      var viewPortMetaSiteNode = babelHelpers.toConsumableArray(metaTags).find(function (element) {
	        return element.name === 'viewport';
	      });
	      if (viewPortMetaSiteNode) {
	        // save tag and remove it from DOM
	        this.viewPortMetaSiteNode = viewPortMetaSiteNode;
	        this.viewPortMetaSiteNode.remove();
	      } else {
	        this.createViewportMeta();
	      }
	      if (!this.viewPortMetaWidgetNode) {
	        this.viewPortMetaWidgetNode = document.createElement('meta');
	        this.viewPortMetaWidgetNode.setAttribute('name', 'viewport');
	        this.viewPortMetaWidgetNode.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=0');
	        document.head.append(this.viewPortMetaWidgetNode);
	      }
	      document.body.classList.add('bx-livechat-mobile-state');
	      if (im_lib_utils.Utils.browser.isSafariBased()) {
	        document.body.classList.add('bx-livechat-mobile-safari-based');
	      }
	      return new Promise(function (resolve) {
	        setTimeout(function () {
	          _this2.$store.dispatch('widget/show').then(resolve);
	        }, 50);
	      });
	    },
	    createViewportMeta: function createViewportMeta() {
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
	    },
	    removeMobileEnv: function removeMobileEnv() {
	      document.body.classList.remove('bx-livechat-mobile-state');
	      if (im_lib_utils.Utils.browser.isSafariBased()) {
	        document.body.classList.remove('bx-livechat-mobile-safari-based');
	      }
	      if (this.viewPortMetaWidgetNode) {
	        this.viewPortMetaWidgetNode.remove();
	        this.viewPortMetaWidgetNode = null;
	      }
	      if (this.viewPortMetaSiteNode) {
	        document.head.append(this.viewPortMetaSiteNode);
	        this.viewPortMetaSiteNode = null;
	      }
	    },
	    onCreated: function onCreated() {
	      var _this3 = this;
	      return new Promise(function (resolve) {
	        if (im_lib_utils.Utils.device.isMobile()) {
	          _this3.initMobileEnv().then(resolve);
	        } else {
	          _this3.$store.dispatch('widget/show').then(function () {
	            _this3.widgetCurrentHeight = WidgetBaseSize.height;
	            _this3.widgetCurrentWidth = WidgetBaseSize.width;
	            _this3.getAvailableSpace();

	            // restore widget size from cache
	            _this3.widgetCurrentHeight = _this3.widget.common.widgetHeight || _this3.widgetCurrentHeight;
	            _this3.widgetCurrentWidth = _this3.widget.common.widgetWidth || _this3.widgetCurrentWidth;
	            resolve();
	          });
	        }

	        // restore textarea size from cache
	        _this3.textareaHeight = _this3.widget.common.textareaHeight || _this3.textareaHeight;
	        _this3.initCollections();
	      });
	    },
	    initCollections: function initCollections() {
	      this.$store.commit('files/initCollection', {
	        chatId: this.getApplication().getChatId()
	      });
	      this.$store.commit('messages/initCollection', {
	        chatId: this.getApplication().getChatId()
	      });
	      this.$store.commit('dialogues/initCollection', {
	        dialogId: this.getApplication().getDialogId(),
	        fields: {
	          entityType: 'LIVECHAT',
	          type: 'livechat'
	        }
	      });
	    },
	    // endregion initialization
	    // region events
	    onBeforeClose: function onBeforeClose() {
	      if (im_lib_utils.Utils.device.isMobile()) {
	        this.removeMobileEnv();
	      }
	    },
	    onAfterClose: function onAfterClose() {
	      this.getApplication().close();
	    },
	    onOpenMenu: function onOpenMenu() {
	      this.historyHandler.getHtmlHistory();
	    },
	    onPullRequestConfig: function onPullRequestConfig() {
	      this.getApplication().recoverPullConnection();
	    },
	    onSmilesSelectSmile: function onSmilesSelectSmile(event) {
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.insertText, {
	        text: event.text
	      });
	    },
	    onSmilesSelectSet: function onSmilesSelectSet() {
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	    },
	    onWidgetStartDrag: function onWidgetStartDrag(event) {
	      this.resizeHandler.startResize(event, this.widgetCurrentHeight, this.widgetCurrentWidth);
	      this.widgetIsResizing = true;
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.setBlur, true);
	    },
	    onWindowKeyDown: function onWindowKeyDown(event) {
	      // not escape
	      if (event.keyCode !== 27) {
	        return;
	      }

	      // hide form
	      if (this.widget.common.showForm !== FormType.none) {
	        this.$store.commit('widget/common', {
	          showForm: FormType.none
	        });
	      }
	      // decline consent
	      else if (this.widget.common.showConsent) {
	        main_core_events.EventEmitter.emit(WidgetEventType.declineConsent);
	      }
	      // close widget
	      else {
	        this.close();
	      }
	      event.preventDefault();
	      event.stopPropagation();
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	    },
	    onWelcomeFormSendSuccess: function onWelcomeFormSendSuccess() {
	      this.welcomeFormFilled = true;
	    },
	    onWelcomeFormSendError: function onWelcomeFormSendError(error) {
	      console.error('onWelcomeFormSendError', error);
	      this.welcomeFormFilled = true;
	    },
	    onTextareaStartDrag: function onTextareaStartDrag(event) {
	      this.textareaDragHandler.onStartDrag(event, this.textareaHeight);
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.setBlur, true);
	    },
	    openDialogList: function openDialogList() {
	      this.$store.commit('widget/common', {
	        isCreateSessionMode: !this.widget.common.isCreateSessionMode
	      });
	      this.startNewChatMode = false;
	    },
	    onStartNewChat: function onStartNewChat() {
	      this.startNewChatMode = true;
	    },
	    // endregion events
	    // region helpers
	    getApplication: function getApplication() {
	      return this.$Bitrix.Application.get();
	    },
	    close: function close() {
	      if (this.widget.common.pageMode) {
	        return false;
	      }
	      this.onBeforeClose();
	      this.$store.commit('widget/common', {
	        showed: false
	      });
	    },
	    // how much width and height we have for resizing
	    getAvailableSpace: function getAvailableSpace() {
	      var widgetMargin = 50;
	      if (this.isBottomLocation()) {
	        var bottomPosition = this.$refs.widgetWrapper.getBoundingClientRect().bottom;
	        var widgetBottomMargin = window.innerHeight - bottomPosition;
	        this.widgetAvailableHeight = window.innerHeight - widgetMargin - widgetBottomMargin;
	      } else {
	        var topPosition = this.$refs.widgetWrapper.getBoundingClientRect().top;
	        this.widgetAvailableHeight = window.innerHeight - widgetMargin - topPosition;
	      }
	      this.widgetAvailableWidth = window.innerWidth - widgetMargin * 2;
	      if (this.resizeHandler) {
	        this.resizeHandler.setAvailableWidth(this.widgetAvailableWidth);
	        this.resizeHandler.setAvailableHeight(this.widgetAvailableHeight);
	      }
	    },
	    getTextareaDragHandler: function getTextareaDragHandler() {
	      var _this4 = this,
	        _TextareaDragHandler;
	      return new im_eventHandler.TextareaDragHandler((_TextareaDragHandler = {}, babelHelpers.defineProperty(_TextareaDragHandler, im_eventHandler.TextareaDragHandler.events.onHeightChange, function (_ref) {
	        var data = _ref.data;
	        var newHeight = data.newHeight;
	        if (_this4.textareaHeight !== newHeight) {
	          _this4.textareaHeight = newHeight;
	        }
	      }), babelHelpers.defineProperty(_TextareaDragHandler, im_eventHandler.TextareaDragHandler.events.onStopDrag, function () {
	        _this4.$store.commit('widget/common', {
	          textareaHeight: _this4.textareaHeight
	        });
	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	          chatId: _this4.chatId,
	          force: true
	        });
	      }), _TextareaDragHandler));
	    },
	    getWidgetResizeHandler: function getWidgetResizeHandler() {
	      var _this5 = this,
	        _events;
	      return new WidgetResizeHandler({
	        widgetLocation: this.widget.common.location,
	        availableWidth: this.widgetAvailableWidth,
	        availableHeight: this.widgetAvailableHeight,
	        events: (_events = {}, babelHelpers.defineProperty(_events, WidgetResizeHandler.events.onSizeChange, function (_ref2) {
	          var data = _ref2.data;
	          var newHeight = data.newHeight,
	            newWidth = data.newWidth;
	          if (_this5.widgetCurrentHeight !== newHeight) {
	            _this5.widgetCurrentHeight = newHeight;
	          }
	          if (_this5.widgetCurrentWidth !== newWidth) {
	            _this5.widgetCurrentWidth = newWidth;
	          }
	        }), babelHelpers.defineProperty(_events, WidgetResizeHandler.events.onStopResize, function () {
	          _this5.widgetIsResizing = false;
	          _this5.$store.commit('widget/common', {
	            widgetHeight: _this5.widgetCurrentHeight,
	            widgetWidth: _this5.widgetCurrentWidth
	          });
	        }), _events)
	      });
	    },
	    isBottomLocation: function isBottomLocation() {
	      return [LocationType.bottomLeft, LocationType.bottomMiddle, LocationType.bottomRight].includes(this.widget.common.location);
	    },
	    isPageMode: function isPageMode() {
	      return this.widget.common.pageMode;
	    },
	    registerZIndex: function registerZIndex() {
	      this.zIndexStackInstance = this.$Bitrix.Data.get('zIndexStack');
	      if (this.zIndexStackInstance && !!this.$refs.widgetWrapper) {
	        this.zIndexStackInstance.register(this.$refs.widgetWrapper);
	      }
	    },
	    unregisterZIndex: function unregisterZIndex() {
	      if (this.zIndexStackInstance) {
	        this.zIndexStackInstance.unregister(this.$refs.widgetWrapper);
	      }
	    } // endregion helpers
	  },
	  // language=Vue
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-show\" leave-active-class=\"bx-livechat-close\" @after-leave=\"onAfterClose\">\n\t\t\t<div\n\t\t\t\t:class=\"widgetClassName\"\n\t\t\t\tv-if=\"widget.common.showed\"\n\t\t\t\t:style=\"{height: widgetHeightStyle, width: widgetWidthStyle, userSelect: userSelectStyle}\"\n\t\t\t\tclass=\"bx-livechat-wrapper bx-livechat-show\"\n\t\t\t\tref=\"widgetWrapper\"\n\t\t\t>\n\t\t\t\t<div class=\"bx-livechat-box\">\n\t\t\t\t\t<div v-if=\"isBottomLocation() && !isPageMode()\" class=\"bx-livechat-widget-resize-handle\" @mousedown=\"onWidgetStartDrag\"></div>\n\t\t\t\t\t<bx-livechat-head \n\t\t\t\t\t\t:isWidgetDisabled=\"widgetMobileDisabled\" \n\t\t\t\t\t\t@openMenu=\"onOpenMenu\" \n\t\t\t\t\t\t@close=\"close\"\n\t\t\t\t\t\t@openDialogList=\"openDialogList\"\n\t\t\t\t\t/>\n\t\t\t\t\t<template v-if=\"widgetMobileDisabled\">\n\t\t\t\t\t\t<bx-livechat-body-orientation-disabled/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"application.error.active\">\n\t\t\t\t\t\t<bx-livechat-body-error/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"!widget.common.configId\">\n\t\t\t\t\t\t<div class=\"bx-livechat-body\" key=\"loading-body\">\n\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div v-show=\"!widget.common.dialogStart\" class=\"bx-livechat-body\" :class=\"{'bx-livechat-body-with-scroll': showWelcomeForm}\" key=\"welcome-body\">\n\t\t\t\t\t\t\t<bx-imopenlines-form\n\t\t\t\t\t\t\t  v-show=\"showWelcomeForm\"\n\t\t\t\t\t\t\t  @formSendSuccess=\"onWelcomeFormSendSuccess\"\n\t\t\t\t\t\t\t  @formSendError=\"onWelcomeFormSendError\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t<template v-if=\"!showWelcomeForm\">\n\t\t\t\t\t\t\t\t<bx-livechat-body-operators/>\n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widget.common.showForm === FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<template v-if=\"widget.common.dialogStart\">\n\t\t\t\t\t\t\t<bx-pull-component-status :canReconnect=\"true\" @reconnect=\"onPullRequestConfig\"/>\n\t\t\t\t\t\t\t<div :class=\"['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]\" key=\"with-message\">\n\t\t\t\t\t\t\t\t<template v-if=\"widget.common.isCreateSessionMode\">\n\t\t\t\t\t\t\t\t\t<bx-livechat-dialogues-list @startNewChat=\"onStartNewChat\"/>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else-if=\"showMessageDialog\">\n\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-dialog\">\n\t\t\t\t\t\t\t\t\t\t<bx-im-component-dialog\n\t\t\t\t\t\t\t\t\t\t\t:userId=\"application.common.userId\"\n\t\t\t\t\t\t\t\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t\t\t\t\t\t\t\t:messageLimit=\"application.dialog.messageLimit\"\n\t\t\t\t\t\t\t\t\t\t\t:enableReactions=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:enableDateActions=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableCreateContent=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableGestureQuote=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:enableGestureMenu=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageAvatar=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageMenu=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:skipDataRequest=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:showLoadingState=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showEmptyState=\"false\"\n\t\t\t\t\t\t\t\t\t\t />\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t\t\t</template>\n\n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widget.common.showForm === FormType.like && widget.common.vote.enable\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-vote/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.welcome\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-welcome/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.offline\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-offline/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.history\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-history/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<div v-if=\"showTextarea || startNewChatMode\" class=\"bx-livechat-textarea\" :style=\"[textareaHeightStyle, textareaBottomMargin]\" ref=\"textarea\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-textarea-resize-handle\" @mousedown=\"onTextareaStartDrag\" @touchstart=\"onTextareaStartDrag\"></div>\n\t\t\t\t\t\t\t<bx-im-component-textarea\n\t\t\t\t\t\t\t\t:siteId=\"application.common.siteId\"\n\t\t\t\t\t\t\t\t:userId=\"application.common.userId\"\n\t\t\t\t\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t\t\t\t\t:writesEventLetter=\"3\"\n\t\t\t\t\t\t\t\t:enableEdit=\"true\"\n\t\t\t\t\t\t\t\t:enableCommand=\"false\"\n\t\t\t\t\t\t\t\t:enableMention=\"false\"\n\t\t\t\t\t\t\t\t:enableFile=\"application.disk.enabled\"\n\t\t\t\t\t\t\t\t:autoFocus=\"application.device.type !== DeviceType.mobile\"\n\t\t\t\t\t\t\t\t:styles=\"{button: {backgroundColor: widget.common.styles.backgroundColor, iconColor: widget.common.styles.iconColor}}\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"!widget.common.copyright && !isBottomLocation\" class=\"bx-livechat-nocopyright-resize-wrap\" style=\"position: relative;\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-widget-resize-handle\" @mousedown=\"onWidgetStartDrag\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<bx-livechat-form-consent />\n\t\t\t\t\t\t<template v-if=\"widget.common.copyright\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-copyright\">\n\t\t\t\t\t\t\t\t<template v-if=\"widget.common.copyrightUrl\">\n\t\t\t\t\t\t\t\t\t<a class=\"bx-livechat-copyright-link\" :href=\"widget.common.copyrightUrl\" target=\"_blank\">\n\t\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<div v-if=\"!isBottomLocation() && !isPageMode()\" class=\"bx-livechat-widget-resize-handle\" @mousedown=\"onWidgetStartDrag\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	});

	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	ui_vue.BitrixVue.component('bx-livechat-body-error', {
	  computed: _objectSpread$2({}, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"error-body\">\n\t\t\t<div class=\"bx-livechat-warning-window\">\n\t\t\t\t<div class=\"bx-livechat-warning-icon\"></div>\n\t\t\t\t<template v-if=\"application.error.description\"> \n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\" v-html=\"application.error.description\"></div>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-md bx-livechat-warning-msg\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_ERROR_TITLE')}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_ERROR_DESC')}}</div>\n\t\t\t\t</template> \n\t\t\t</div>\n\t\t</div>\n\t"
	});

	function ownKeys$3(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$3(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$3(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$3(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	ui_vue.BitrixVue.component('bx-livechat-head', {
	  /**
	   * @emits 'close'
	   * @emits 'like'
	   * @emits 'history'
	   */
	  props: {
	    isWidgetDisabled: {
	      "default": false
	    }
	  },
	  data: function data() {
	    return {
	      multiDialog: false // disabled because of beta status
	    };
	  },

	  methods: {
	    openDialogList: function openDialogList() {
	      main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	      this.$emit('openDialogList');
	    },
	    close: function close(event) {
	      this.$emit('close');
	    },
	    like: function like() {
	      main_core_events.EventEmitter.emit(WidgetEventType.showForm, {
	        type: FormType.like
	      });
	    },
	    openMenu: function openMenu(event) {
	      this.$emit('openMenu', event);
	    }
	  },
	  computed: _objectSpread$3({
	    VoteType: function VoteType$$1() {
	      return VoteType;
	    },
	    chatId: function chatId() {
	      if (this.application) {
	        return this.application.dialog.chatId;
	      }
	    },
	    customBackgroundStyle: function customBackgroundStyle(state) {
	      return state.widget.common.styles.backgroundColor ? 'background-color: ' + state.widget.common.styles.backgroundColor + '!important;' : '';
	    },
	    customBackgroundOnlineStyle: function customBackgroundOnlineStyle(state) {
	      return state.widget.common.styles.backgroundColor ? 'border-color: ' + state.widget.common.styles.backgroundColor + '!important;' : '';
	    },
	    showName: function showName(state) {
	      return state.widget.dialog.operator.firstName || state.widget.dialog.operator.lastName;
	    },
	    voteActive: function voteActive(state) {
	      if (!!state.widget.dialog.closeVote) {
	        return false;
	      }
	      if (!state.widget.common.vote.beforeFinish && state.widget.dialog.sessionStatus < SessionStatus.waitClient) {
	        return false;
	      }
	      if (!state.widget.dialog.sessionClose || state.widget.dialog.sessionClose && state.widget.dialog.userVote === VoteType.none) {
	        return true;
	      }
	      if (state.widget.dialog.sessionClose && state.widget.dialog.userVote !== VoteType.none) {
	        return true;
	      }
	      return false;
	    },
	    chatTitle: function chatTitle(state) {
	      return state.widget.common.textMessages.bxLivechatTitle || state.widget.common.configName || this.localize.BX_LIVECHAT_TITLE;
	    },
	    operatorName: function operatorName(state) {
	      if (!this.showName) return '';
	      return state.widget.dialog.operator.firstName ? state.widget.dialog.operator.firstName : state.widget.dialog.operator.name;
	    },
	    operatorDescription: function operatorDescription(state) {
	      if (!this.showName) {
	        return '';
	      }
	      var operatorPosition = state.widget.dialog.operator.workPosition ? state.widget.dialog.operator.workPosition : this.localize.BX_LIVECHAT_USER;
	      if (state.widget.common.showSessionId && state.widget.dialog.sessionId >= 0) {
	        return this.localize.BX_LIVECHAT_OPERATOR_POSITION_AND_SESSION_ID.replace("#POSITION#", operatorPosition).replace("#ID#", state.widget.dialog.sessionId);
	      }
	      return this.localize.BX_LIVECHAT_OPERATOR_POSITION_ONLY.replace("#POSITION#", operatorPosition);
	    },
	    localize: function localize() {
	      return ui_vue.BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
	    },
	    ie11: function ie11() {
	      return main_core_minimal.Browser.isIE11();
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
	          _this.$root.$emit(im_const.EventType.dialog.scrollToBottom, {
	            chatId: _this.chatId
	          });
	        }, 300);
	      }
	    }
	  },
	  //language=Vue
	  template: "\n\t\t<div class=\"bx-livechat-head-wrap\">\n\t\t\t<template v-if=\"isWidgetDisabled\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\t\n\t\t\t</template>\n\t\t\t<template v-else-if=\"application.error.active\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<template v-else-if=\"!widget.common.configId\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-else>\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<template v-if=\"!showName\">\n\t\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div class=\"bx-livechat-user bx-livechat-status-online\">\n\t\t\t\t\t\t\t<template v-if=\"widget.dialog.operator.avatar\">\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\" :style=\"'background-image: url('+encodeURI(widget.dialog.operator.avatar)+')'\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"widget.dialog.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"widget.dialog.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-user-info\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-name\">{{operatorName}}</div>\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-position\">{{operatorDescription}}</div>\t\t\t\t\t\t\t\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<span class=\"bx-livechat-control-box-active\" v-if=\"widget.common.dialogStart && widget.dialog.sessionId\">\n\t\t\t\t\t\t\t<button v-if=\"widget.common.vote.enable && voteActive\" :class=\"'bx-livechat-control-btn bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(widget.dialog.userVote)\" :title=\"localize.BX_LIVECHAT_VOTE_BUTTON\" @click=\"like\"></button>\n\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\tv-if=\"!ie11 && application.dialog.chatId > 0\"\n\t\t\t\t\t\t\t\tclass=\"bx-livechat-control-btn bx-livechat-control-btn-menu\"\n\t\t\t\t\t\t\t\t@click=\"openMenu\"\n\t\t\t\t\t\t\t\t:title=\"localize.BX_LIVECHAT_DOWNLOAD_HISTORY\"\n\t\t\t\t\t\t\t></button>\n\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\tv-if=\"multiDialog && application.dialog.chatId > 0\"\n\t\t\t\t\t\t\t\tclass=\"bx-livechat-control-btn bx-livechat-control-btn-list\"\n\t\t\t\t\t\t\t\t@click=\"openDialogList\"\n\t\t\t\t\t\t\t></button>\n\t\t\t\t\t\t</span>\t\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body loading component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-livechat-body-loading', {
	  template: "\n\t\t<div class=\"bx-livechat-loading-window\">\n\t\t\t<svg class=\"bx-livechat-loading-circular\" viewBox=\"25 25 50 50\">\n\t\t\t\t<circle class=\"bx-livechat-loading-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t\t<circle class=\"bx-livechat-loading-inner-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t</svg>\n\t\t\t<h3 class=\"bx-livechat-help-title bx-livechat-help-title-md bx-livechat-loading-msg\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_LOADING')}}</h3>\n\t\t</div>\n\t"
	});

	function ownKeys$4(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$4(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$4(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$4(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	ui_vue.BitrixVue.component('bx-livechat-body-operators', {
	  computed: _objectSpread$4({}, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-help-container\">\n\t\t\t<transition name=\"bx-livechat-animation-fade\">\n\t\t\t\t<h2 v-if=\"widget.common.online\" key=\"online\" class=\"bx-livechat-help-title bx-livechat-help-title-lg\">{{widget.common.textMessages.bxLivechatOnlineLine1}}<div class=\"bx-livechat-help-subtitle\">{{widget.common.textMessages.bxLivechatOnlineLine2}}</div></h2>\n\t\t\t\t<h2 v-else key=\"offline\" class=\"bx-livechat-help-title bx-livechat-help-title-sm\">{{widget.common.textMessages.bxLivechatOffline}}</h2>\n\t\t\t</transition>\t\n\t\t\t<div class=\"bx-livechat-help-user\">\n\t\t\t\t<template v-for=\"operator in widget.common.operators\">\n\t\t\t\t\t<div class=\"bx-livechat-user\" :key=\"operator.id\">\n\t\t\t\t\t\t<template v-if=\"operator.avatar\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\" :style=\"'background-image: url('+encodeURI(operator.avatar)+')'\"></div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\"></div>\n\t\t\t\t\t\t</template>\t\n\t\t\t\t\t\t<div class=\"bx-livechat-user-info\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-name\">{{operator.firstName? operator.firstName: operator.name}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\t\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	function ownKeys$5(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$5(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$5(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$5(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	ui_vue.BitrixVue.component('bx-livechat-dialogues-list', {
	  data: function data() {
	    return {
	      newChatMode: false,
	      sessionList: [],
	      isLoading: false,
	      pagesLoaded: 0,
	      hasMoreItemsToLoad: true,
	      itemsPerPage: 25
	    };
	  },
	  computed: _objectSpread$5({}, ui_vue_vuex.Vuex.mapState({
	    dialogues: function dialogues(state) {
	      return state.dialogues;
	    }
	  })),
	  mounted: function mounted() {
	    this.requestDialogList();
	  },
	  methods: {
	    requestDialogList: function requestDialogList() {
	      var _this = this;
	      var offset = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	      this.isLoading = true;
	      var requestParams = {
	        'CONFIG_ID': this.$Bitrix.Application.get().getConfigId()
	      };
	      if (offset > 0) {
	        requestParams['OFFSET'] = offset;
	      }
	      return this.$Bitrix.Application.get().controller.restClient.callMethod(RestMethod.widgetDialogList, requestParams).then(function (result) {
	        if (result.data().length === 0 || result.data().length < _this.itemsPerPage) {
	          _this.hasMoreItemsToLoad = false;
	        }
	        _this.pagesLoaded++;
	        _this.isLoading = false;
	        _this.sessionList = [].concat(babelHelpers.toConsumableArray(_this.sessionList), babelHelpers.toConsumableArray(_this.prepareSessionList(result.data())));
	      })["catch"](function (error) {
	        console.warn('error', error);
	      });
	    },
	    prepareSessionList: function prepareSessionList(sessionList) {
	      return Object.values(sessionList).map(function (dialog) {
	        return {
	          chatId: dialog.chatId,
	          dialogId: dialog.dialogId,
	          name: "Dialog #".concat(dialog.sessionId)
	        };
	      });
	    },
	    openSession: function openSession(event) {
	      main_core_events.EventEmitter.emit(WidgetEventType.openSession, event);
	    },
	    startNewChat: function startNewChat(event) {
	      this.newChatMode = true;
	      this.$emit('startNewChat', event);
	    },
	    isOneScreenRemaining: function isOneScreenRemaining(event) {
	      return event.target.scrollTop + event.target.clientHeight >= event.target.scrollHeight - event.target.clientHeight;
	    },
	    onScroll: function onScroll(event) {
	      if (this.isOneScreenRemaining(event)) {
	        if (this.isLoading || !this.hasMoreItemsToLoad) {
	          return;
	        }
	        var offset = this.itemsPerPage * this.pagesLoaded;
	        this.requestDialogList(offset);
	      }
	    }
	  },
	  // language=Vue
	  template: "\n\t<div class=\"bx-livechat-help-container\" style=\" height: 100%; display: flex; flex-direction: column; justify-content: space-between;\">\n\t\t<div \n\t\t\tstyle=\"margin-top: 25px;overflow-y: scroll;position:relative\"\n\t\t\t:style=\"{marginBottom: newChatMode ? 0 : '10px'}\"\n\t\t\t@scroll=\"onScroll\"\n\t\t>\n\t\t\t<div\n\t\t\t\tv-for=\"session in sessionList\"\n\t\t\t\t:key=\"session.chatId\"\n\t\t\t\tclass=\"bx-livechat-help-subtitle\"\n\t\t\t\t@click=\"openSession({event: $event, session: session})\"\n\t\t\t\tstyle=\"cursor: pointer; border: solid 1px black;border-radius: 10px;margin: 10px;padding: 5px;background-color: #0ae4ff\">\n\t\t\t\t{{ session.name }}\n\t\t\t</div>\n\t\t\t<div v-if=\"isLoading\" style=\"margin: 10px\">Loading</div>\n\t\t</div>\n\t\t\n\t\t<div v-if=\"!newChatMode\" style=\"margin-bottom: 10px;\">\n\t\t\t<button \n\t\t\t\tclass=\"bx-livechat-btn\" \n\t\t\t\tstyle=\"background-color: rgb(23, 163, 234); border-radius: 5px;width: 150px;\" \n\t\t\t\t@click=\"startNewChat\">\n\t\t\t\tStart new chat!\n\t\t\t</button>\n\t\t</div>\n\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body orientation disabled component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-livechat-body-orientation-disabled', {
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"orientation-head\">\n\t\t\t<div class=\"bx-livechat-mobile-orientation-box\">\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-icon\"></div>\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-text\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_MOBILE_ROTATE')}}</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	function ownKeys$6(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$6(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$6(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$6(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	ui_vue.BitrixVue.component('bx-livechat-form-consent', {
	  computed: _objectSpread$6({}, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  methods: {
	    agree: function agree() {
	      main_core_events.EventEmitter.emit(WidgetEventType.acceptConsent);
	    },
	    disagree: function disagree() {
	      main_core_events.EventEmitter.emit(WidgetEventType.declineConsent);
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
	  template: "\n\t\t<transition @enter=\"onShow\" @leave=\"onHide\">\n\t\t\t<template v-if=\"widget.common.showConsent && widget.common.consentUrl\">\n\t\t\t\t<div class=\"bx-livechat-consent-window\">\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-title\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_TITLE')}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-content\">\n\t\t\t\t\t\t<iframe class=\"bx-livechat-consent-window-content-iframe\" ref=\"iframe\" frameborder=\"0\" marginheight=\"0\"  marginwidth=\"0\" allowtransparency=\"allow-same-origin\" seamless=\"true\" :src=\"widget.common.consentUrl\" @keydown=\"onKeyDown\"></iframe>\n\t\t\t\t\t</div>\t\t\t\t\t\t\t\t\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" ref=\"success\" @click=\"agree\" @keydown=\"onKeyDown\" v-focus>{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_AGREE')}}</button>\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-cancel\" ref=\"cancel\" @click=\"disagree\" @keydown=\"onKeyDown\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_DISAGREE')}}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</transition>\n\t"
	});

	function ownKeys$7(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$7(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$7(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$7(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	ui_vue.BitrixVue.component('bx-livechat-form-vote', {
	  computed: _objectSpread$7({
	    VoteType: function VoteType$$1() {
	      return VoteType;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  methods: {
	    userVote: function userVote(vote) {
	      main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	      this.$store.commit('widget/dialog', {
	        userVote: vote
	      });
	      main_core_events.EventEmitter.emit(WidgetEventType.sendDialogVote, {
	        vote: vote
	      });
	    },
	    hideForm: function hideForm() {
	      main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-form-rate-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" :title=\"$Bitrix.Loc.getMessage('BX_LIVECHAT_VOTE_LATER')\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-rate-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-mdl\">{{widget.common.vote.messageText}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-like\" @click=\"userVote(VoteType.like)\" :title=\"widget.common.vote.messageLike\"></button>\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-dislike\" @click=\"userVote(VoteType.dislike)\" :title=\"widget.common.vote.messageDislike\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"bx-livechat-alert-later\"><span class=\"bx-livechat-alert-later-btn\" @click=\"hideForm\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_VOTE_LATER')}}</span></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\t\n\t"
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
	    hideForm: function hideForm() {
	      main_core_events.EventEmitter.emit(WidgetEventType.hideForm);
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-livechat-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-smiles-box\">\n\t\t\t\t\t#PARENT_TEMPLATE#\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
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
	BX.LiveChatWidget.SubscriptionType = SubscriptionType;
	BX.LiveChatWidget.LocationStyle = LocationStyle;
	BX.LiveChatWidget.Cookie = im_lib_cookie.Cookie;
	window.dispatchEvent(new CustomEvent('onBitrixLiveChatSourceLoaded', {
	  detail: {}
	}));

}((this.window = this.window || {}),BX,window,BX.Messenger,window,BX,window,window,BX,BX.Messenger.Provider.Rest,BX.Main,BX,BX.Ui.Vue.Components.Crm,BX.Messenger,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Lib,BX,BX.Messenger.Lib,BX,BX.Messenger.Lib,BX.Messenger.EventHandler,BX.Messenger.Const,BX,BX,BX,BX.Event));
//# sourceMappingURL=widget.bundle.js.map
