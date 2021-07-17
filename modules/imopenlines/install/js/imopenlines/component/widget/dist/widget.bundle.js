(function (exports,main_polyfill_customevent,pull_component_status,ui_vue_components_smiles,im_component_dialog,im_component_textarea,im_view_quotepanel,imopenlines_component_message,imopenlines_component_form,rest_client,im_provider_rest,main_date,pull_client,im_controller,im_lib_cookie,im_lib_localstorage,im_lib_uploader,im_lib_logger,im_mixin,main_md5,main_core_events,im_const,main_core_minimal,ui_icons,ui_forms,ui_vue_vuex,im_lib_utils,ui_vue) {
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
	  widgetConfigGet: 'imopenlines.widget.config.get',
	  widgetDialogGet: 'imopenlines.widget.dialog.get',
	  widgetUserGet: 'imopenlines.widget.user.get',
	  widgetUserConsentApply: 'imopenlines.widget.user.consent.apply',
	  widgetVoteSend: 'imopenlines.widget.vote.send',
	  widgetFormSend: 'imopenlines.widget.form.send',
	  widgetActionSend: 'imopenlines.widget.action.send',
	  pullServerTime: 'server.time',
	  pullConfigGet: 'pull.config.get'
	});
	var RestMethodCheck = GetObjectValues(RestMethod);
	var RestAuth = Object.freeze({
	  guest: 'guest'
	});
	var SessionStatus = Object.freeze({
	  new: 0,
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
	var EventType = Object.freeze({
	  requestShowForm: 'IMOL.Widget:requestShowForm'
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
	          showSessionId: false
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
	        logTag = im_lib_utils.Utils.getLogTrackingParams({
	          name: method
	        });
	      }

	      var promise = new BX.Promise(); // TODO: Callbacks methods will not work!

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
	        showSessionId: data.showSessionId
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
	      this.widget.messagesQueue = this.widget.messagesQueue.filter(function (el) {
	        return el.id != message.id;
	      });
	      this.widget.sendEvent({
	        type: SubscriptionType.userMessage,
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
	  }, {
	    key: "handleImDiskFileCommitSuccess",
	    value: function handleImDiskFileCommitSuccess(result, message) {
	      this.widget.messagesQueue = this.widget.messagesQueue.filter(function (el) {
	        return el.id != message.id;
	      });
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

	/**
	 * Bitrix OpenLines widget
	 * Widget private interface (base class)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2020 Bitrix
	 */

	var Widget = /*#__PURE__*/function () {
	  /* region 01. Initialize and store data */
	  function Widget() {
	    var _this = this;

	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Widget);
	    this.params = params;
	    this.template = null;
	    this.rootNode = this.params.node || document.createElement('div');
	    this.messagesQueue = [];
	    this.ready = true;
	    this.widgetDataRequested = false;
	    this.offline = false;
	    this.inited = false;
	    this.initEventFired = false;
	    this.restClient = null;
	    this.userRegisterData = {};
	    this.customData = [];
	    this.subscribers = {};
	    this.configRequestXhr = null;
	    this.initParams().then(function () {
	      return _this.initRestClient();
	    }).then(function () {
	      return _this.initPullClient();
	    }).then(function () {
	      return _this.initCore();
	    }).then(function () {
	      return _this.initWidget();
	    }).then(function () {
	      return _this.initUploader();
	    }).then(function () {
	      return _this.initComplete();
	    });
	  }

	  babelHelpers.createClass(Widget, [{
	    key: "initParams",
	    value: function initParams() {
	      this.code = this.params.code || '';
	      this.host = this.params.host || '';
	      this.language = this.params.language || 'en';
	      this.copyright = this.params.copyright !== false;
	      this.copyrightUrl = this.copyright && this.params.copyrightUrl ? this.params.copyrightUrl : '';
	      this.buttonInstance = babelHelpers.typeof(this.params.buttonInstance) === 'object' && this.params.buttonInstance !== null ? this.params.buttonInstance : null;
	      this.pageMode = babelHelpers.typeof(this.params.pageMode) === 'object' && this.params.pageMode;

	      if (this.pageMode) {
	        this.pageMode.useBitrixLocalize = this.params.pageMode.useBitrixLocalize === true;
	        this.pageMode.placeholder = document.getElementById(this.params.pageMode.placeholder);
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

	      if (this.pageMode && this.pageMode.placeholder) {
	        this.rootNode = this.pageMode.placeholder;
	      } else {
	        if (document.body.firstChild) {
	          document.body.insertBefore(this.rootNode, document.body.firstChild);
	        } else {
	          document.body.appendChild(this.rootNode);
	        }
	      }

	      this.localize = this.pageMode && this.pageMode.useBitrixLocalize ? window.BX.message : {};

	      if (babelHelpers.typeof(this.params.localize) === 'object') {
	        this.addLocalize(this.params.localize);
	      }

	      var serverVariables = im_lib_localstorage.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);

	      if (serverVariables) {
	        this.addLocalize(serverVariables);
	      }

	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initRestClient",
	    value: function initRestClient() {
	      this.restClient = new WidgetRestClient({
	        endpoint: this.host + '/rest'
	      });
	      return new Promise(function (resolve, reject) {
	        return resolve();
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
	      this.pullClientInited = false;
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initCore",
	    value: function initCore() {
	      var _this2 = this;

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

	      if (im_lib_utils.Utils.types.isPlainObject(this.params.styles) && (this.params.styles.backgroundColor || this.params.styles.iconColor)) {
	        widgetVariables.styles = {};

	        if (this.params.styles.backgroundColor) {
	          widgetVariables.styles.backgroundColor = this.params.styles.backgroundColor;
	        }

	        if (this.params.styles.iconColor) {
	          widgetVariables.styles.iconColor = this.params.styles.iconColor;
	        }
	      }

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
	          models: [WidgetModel.create().setVariables(widgetVariables)]
	        }
	      });
	      return new Promise(function (resolve, reject) {
	        _this2.controller.ready().then(function () {
	          return resolve();
	        });
	      });
	    }
	  }, {
	    key: "initWidget",
	    value: function initWidget() {
	      if (this.isUserRegistered()) {
	        this.restClient.setAuthId(this.getUserHash());
	      } else {
	        this.restClient.setAuthId(RestAuth.guest);
	      }

	      if (this.params.location && typeof LocationStyle[this.params.location] !== 'undefined') {
	        this.controller.getStore().commit('widget/common', {
	          location: this.params.location
	        });
	      }

	      this.controller.application.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));
	      this.controller.addRestAnswerHandler(WidgetRestAnswerHandler.create({
	        widget: this,
	        store: this.controller.getStore(),
	        controller: this.controller
	      }));
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initUploader",
	    value: function initUploader() {
	      var _this3 = this;

	      this.uploader = new im_lib_uploader.Uploader({
	        generatePreview: true,
	        sender: {
	          host: this.host,
	          customHeaders: {
	            'Livechat-Auth-Id': this.getUserHash()
	          },
	          actionUploadChunk: 'imopenlines.widget.disk.upload',
	          actionCommitFile: 'imopenlines.widget.disk.commit',
	          actionRollbackUpload: 'imopenlines.widget.disk.rollbackUpload'
	        }
	      });
	      this.uploader.subscribe('onStartUpload', function (event) {
	        var eventData = event.getData();
	        im_lib_logger.Logger.log('Uploader: onStartUpload', eventData);

	        _this3.controller.getStore().dispatch('files/update', {
	          chatId: _this3.getChatId(),
	          id: eventData.id,
	          fields: {
	            status: im_const.FileStatus.upload,
	            progress: 0
	          }
	        });
	      });
	      this.uploader.subscribe('onProgress', function (event) {
	        var eventData = event.getData();
	        im_lib_logger.Logger.log('Uploader: onProgress', eventData);

	        _this3.controller.getStore().dispatch('files/update', {
	          chatId: _this3.getChatId(),
	          id: eventData.id,
	          fields: {
	            status: im_const.FileStatus.upload,
	            progress: eventData.progress === 100 ? 99 : eventData.progress
	          }
	        });
	      });
	      this.uploader.subscribe('onSelectFile', function (event) {
	        var eventData = event.getData();
	        var file = eventData.file;
	        im_lib_logger.Logger.log('Uploader: onSelectFile', eventData);
	        var fileType = 'file';

	        if (file.type.toString().startsWith('image')) {
	          fileType = 'image';
	        } else if (file.type.toString().startsWith('video')) {
	          fileType = 'video';
	        }

	        _this3.controller.getStore().dispatch('files/add', {
	          chatId: _this3.getChatId(),
	          authorId: _this3.getUserId(),
	          name: eventData.file.name,
	          type: fileType,
	          extension: file.name.split('.').splice(-1)[0],
	          size: eventData.file.size,
	          image: !eventData.previewData ? false : {
	            width: eventData.previewDataWidth,
	            height: eventData.previewDataHeight
	          },
	          status: im_const.FileStatus.upload,
	          progress: 0,
	          authorName: _this3.controller.application.getCurrentUser().name,
	          urlPreview: eventData.previewData ? URL.createObjectURL(eventData.previewData) : ""
	        }).then(function (fileId) {
	          _this3.addMessage('', {
	            id: fileId,
	            source: eventData,
	            previewBlob: eventData.previewData
	          });
	        });
	      });
	      this.uploader.subscribe('onComplete', function (event) {
	        var eventData = event.getData();
	        im_lib_logger.Logger.log('Uploader: onComplete', eventData);

	        _this3.controller.getStore().dispatch('files/update', {
	          chatId: _this3.getChatId(),
	          id: eventData.id,
	          fields: {
	            status: im_const.FileStatus.wait,
	            progress: 100
	          }
	        });

	        var message = _this3.messagesQueue.find(function (message) {
	          return message.file.id === eventData.id;
	        });

	        var fileType = _this3.controller.getStore().getters['files/get'](_this3.getChatId(), message.file.id, true).type;

	        _this3.fileCommit({
	          chatId: _this3.getChatId(),
	          uploadId: eventData.result.data.file.id,
	          messageText: message.text,
	          messageId: message.id,
	          fileId: message.file.id,
	          fileType: fileType
	        }, message);
	      });
	      this.uploader.subscribe('onUploadFileError', function (event) {
	        var eventData = event.getData();
	        im_lib_logger.Logger.log('Uploader: onUploadFileError', eventData);

	        var message = _this3.messagesQueue.find(function (message) {
	          return message.file.id === eventData.id;
	        });

	        if (typeof message === 'undefined') {
	          return;
	        }

	        _this3.fileError(_this3.getChatId(), message.file.id, message.id);
	      });
	      this.uploader.subscribe('onCreateFileError', function (event) {
	        var eventData = event.getData();
	        im_lib_logger.Logger.log('Uploader: onCreateFileError', eventData);

	        var message = _this3.messagesQueue.find(function (message) {
	          return message.file.id === eventData.id;
	        });

	        _this3.fileError(_this3.getChatId(), message.file.id, message.id);
	      });
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
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

	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "requestWidgetData",
	    value: function requestWidgetData() {
	      var _this4 = this;

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
	        this.controller.restClient.callMethod(RestMethod.widgetConfigGet, {
	          code: this.code
	        }, function (xhr) {
	          _this4.configRequestXhr = xhr;
	        }).then(function (result) {
	          _this4.configRequestXhr = null;

	          _this4.clearError();

	          _this4.controller.executeRestAnswer(RestMethod.widgetConfigGet, result);

	          if (!_this4.inited) {
	            _this4.inited = true;

	            _this4.fireInitEvent();
	          }
	        }).catch(function (result) {
	          _this4.configRequestXhr = null;

	          _this4.setError(result.error().ex.error, result.error().ex.error_description);
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
	      var _this5 = this;

	      im_lib_logger.Logger.log('requesting data from widget');

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
	        query[im_const.RestMethodHandler.imChatGet] = [im_const.RestMethod.imChatGet, {
	          dialog_id: '$result[' + RestMethod.widgetDialogGet + '][dialogId]'
	        }];
	        query[im_const.RestMethodHandler.imDialogMessagesGetInit] = [im_const.RestMethod.imDialogMessagesGet, {
	          chat_id: '$result[' + RestMethod.widgetDialogGet + '][chatId]',
	          limit: this.controller.application.getRequestMessageLimit(),
	          convert_text: 'Y'
	        }];
	      } else {
	        query[RestMethod.widgetUserRegister] = [RestMethod.widgetUserRegister, babelHelpers.objectSpread({
	          config_id: '$result[' + RestMethod.widgetConfigGet + '][configId]'
	        }, this.getUserRegisterFields())];
	        query[im_const.RestMethodHandler.imChatGet] = [im_const.RestMethod.imChatGet, {
	          dialog_id: '$result[' + RestMethod.widgetUserRegister + '][dialogId]'
	        }];

	        if (this.userRegisterData.hash || this.getUserHashCookie()) {
	          query[RestMethod.widgetDialogGet] = [RestMethod.widgetDialogGet, {
	            config_id: '$result[' + RestMethod.widgetConfigGet + '][configId]',
	            trace_data: this.getCrmTraceData(),
	            custom_data: this.getCustomData()
	          }];
	          query[im_const.RestMethodHandler.imDialogMessagesGetInit] = [im_const.RestMethod.imDialogMessagesGet, {
	            chat_id: '$result[' + RestMethod.widgetDialogGet + '][chatId]',
	            limit: this.controller.application.getRequestMessageLimit(),
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
	      this.controller.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this5.requestDataSend = false;

	          _this5.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        var configGet = response[RestMethod.widgetConfigGet];

	        if (configGet && configGet.error()) {
	          _this5.requestDataSend = false;

	          _this5.setError(configGet.error().ex.error, configGet.error().ex.error_description);

	          return false;
	        }

	        _this5.controller.executeRestAnswer(RestMethod.widgetConfigGet, configGet);

	        var userGetResult = response[RestMethod.widgetUserGet];

	        if (userGetResult.error()) {
	          _this5.requestDataSend = false;

	          _this5.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);

	          return false;
	        }

	        _this5.controller.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

	        var chatGetResult = response[im_const.RestMethodHandler.imChatGet];

	        if (chatGetResult.error()) {
	          _this5.requestDataSend = false;

	          _this5.setError(chatGetResult.error().ex.error, chatGetResult.error().ex.error_description);

	          return false;
	        }

	        _this5.controller.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);

	        var dialogGetResult = response[RestMethod.widgetDialogGet];

	        if (dialogGetResult) {
	          if (dialogGetResult.error()) {
	            _this5.requestDataSend = false;

	            _this5.setError(dialogGetResult.error().ex.error, dialogGetResult.error().ex.error_description);

	            return false;
	          }

	          _this5.controller.executeRestAnswer(RestMethod.widgetDialogGet, dialogGetResult);
	        }

	        var dialogMessagesGetResult = response[im_const.RestMethodHandler.imDialogMessagesGetInit];

	        if (dialogMessagesGetResult) {
	          if (dialogMessagesGetResult.error()) {
	            _this5.requestDataSend = false;

	            _this5.setError(dialogMessagesGetResult.error().ex.error, dialogMessagesGetResult.error().ex.error_description);

	            return false;
	          }

	          _this5.controller.getStore().dispatch('dialogues/saveDialog', {
	            dialogId: _this5.controller.application.getDialogId(),
	            chatId: _this5.controller.application.getChatId()
	          });

	          _this5.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
	        }

	        var userRegisterResult = response[RestMethod.widgetUserRegister];

	        if (userRegisterResult) {
	          if (userRegisterResult.error()) {
	            _this5.requestDataSend = false;

	            _this5.setError(userRegisterResult.error().ex.error, userRegisterResult.error().ex.error_description);

	            return false;
	          }

	          _this5.controller.executeRestAnswer(RestMethod.widgetUserRegister, userRegisterResult);
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

	        _this5.startPullClient(config).then(function () {
	          _this5.processSendMessages();
	        }).catch(function (error) {
	          _this5.setError(error.ex.error, error.ex.error_description);
	        });

	        _this5.requestDataSend = false;
	      }, false, false, im_lib_utils.Utils.getLogTrackingParams({
	        name: 'widget.init.config',
	        dialog: this.controller.application.getDialogData()
	      }));
	    }
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      var _this6 = this;

	      if (!im_lib_utils.Utils.types.isArray(files)) {
	        return files;
	      }

	      return files.map(function (file) {
	        var hash = (window.md5 || main_md5.md5)(_this6.getUserId() + '|' + file.id + '|' + _this6.getUserHash());

	        var urlParam = 'livechat_auth_id=' + hash + '&livechat_user_id=' + _this6.getUserId();

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
	      var _this7 = this;

	      var promise = new BX.Promise();

	      if (!this.getUserId() || !this.getSiteId() || !this.restClient) {
	        promise.reject({
	          ex: {
	            error: 'WIDGET_NOT_LOADED',
	            error_description: 'Widget is not loaded.'
	          }
	        });
	        return promise;
	      }

	      if (this.pullClientInited) {
	        if (!this.pullClient.isConnected()) {
	          this.pullClient.scheduleReconnect();
	        }

	        promise.resolve(true);
	        return promise;
	      }

	      this.controller.userId = this.getUserId();
	      this.pullClient.userId = this.getUserId();
	      this.pullClient.configTimestamp = config ? config.server.config_timestamp : 0;
	      this.pullClient.skipStorageInit = false;
	      this.pullClient.storage = pull_client.PullClient.StorageManager({
	        userId: this.getUserId(),
	        siteId: this.getSiteId()
	      });
	      this.pullClient.subscribe(new WidgetImPullCommandHandler({
	        store: this.controller.getStore(),
	        controller: this.controller,
	        widget: this
	      }));
	      this.pullClient.subscribe(new WidgetImopenlinesPullCommandHandler({
	        store: this.controller.getStore(),
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
	          if (result.status === pull_client.PullClient.PullStatus.Online) {
	            promise.resolve(true);

	            _this7.pullConnectedFirstTime();
	          }
	        }
	      });

	      if (this.template) {
	        this.template.$Bitrix.PullClient.set(this.pullClient);
	      }

	      this.pullClient.start(babelHelpers.objectSpread({}, config, {
	        skipReconnectToLastSession: true
	      })).catch(function () {
	        promise.reject({
	          ex: {
	            error: 'PULL_CONNECTION_ERROR',
	            error_description: 'Pull is not connected.'
	          }
	        });
	      });
	      this.pullClientInited = true;
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
	      var _this8 = this;

	      if (data.status === pull_client.PullClient.PullStatus.Online) {
	        this.offline = false;

	        if (this.pullRequestMessage) {
	          this.controller.pullBaseHandler.option.skip = true;
	          im_lib_logger.Logger.warn('Requesting getDialogUnread after going online');
	          main_core_events.EventEmitter.emitAsync(im_const.EventType.dialog.requestUnread, {
	            chatId: this.controller.application.getChatId()
	          }).then(function () {
	            main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollOnStart, {
	              chatId: _this8.controller.application.getChatId()
	            });
	            _this8.controller.pullBaseHandler.option.skip = false;

	            _this8.processSendMessages();
	          }).catch(function () {
	            _this8.controller.pullBaseHandler.option.skip = false;
	          });
	          this.pullRequestMessage = false;
	        } else {
	          this.readMessage();
	          this.processSendMessages();
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
	        this.controller.getStore().commit('widget/common', {
	          showed: true
	        });
	        return true;
	      }

	      this.rootNode.innerHTML = '';
	      this.rootNode.appendChild(document.createElement('div'));
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
	      var _this9 = this;

	      var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var file = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (!text && !file) {
	        return false;
	      }

	      var quoteId = this.controller.getStore().getters['dialogues/getQuoteId'](this.controller.application.getDialogId());

	      if (quoteId) {
	        var quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), quoteId);

	        if (quoteMessage) {
	          var user = null;

	          if (quoteMessage.authorId) {
	            user = this.controller.getStore().getters['users/get'](quoteMessage.authorId);
	          }

	          var files = this.controller.getStore().getters['files/getList'](this.controller.application.getChatId());
	          var message = [];
	          message.push('-'.repeat(54));
	          message.push((user && user.name ? user.name : this.getLocalize('BX_LIVECHAT_SYSTEM_MESSAGE')) + ' [' + im_lib_utils.Utils.date.format(quoteMessage.date, null, this.getLocalize()) + ']');
	          message.push(im_lib_utils.Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.getLocalize()));
	          message.push('-'.repeat(54));
	          message.push(text);
	          text = message.join("\n");
	          this.quoteMessageClear();
	        }
	      }

	      im_lib_logger.Logger.warn('addMessage', text, file);

	      if (!this.controller.application.isUnreadMessagesLoaded()) {
	        this.sendMessage({
	          id: 0,
	          text: text,
	          file: file
	        });
	        this.processSendMessages();
	        return true;
	      }

	      var params = {};

	      if (file) {
	        params.FILE_ID = [file.id];
	      }

	      this.controller.getStore().dispatch('messages/add', {
	        chatId: this.getChatId(),
	        authorId: this.getUserId(),
	        text: text,
	        params: params,
	        sending: !file
	      }).then(function (messageId) {
	        if (!_this9.isDialogStart()) {
	          _this9.controller.getStore().commit('widget/common', {
	            dialogStart: true
	          });
	        }

	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	          chatId: _this9.getChatId(),
	          cancelIfScrollChange: true
	        });

	        _this9.messagesQueue.push({
	          id: messageId,
	          text: text,
	          file: file,
	          sending: false
	        });

	        if (_this9.getChatId()) {
	          _this9.processSendMessages();
	        } else {
	          _this9.requestData();
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "uploadFile",
	    value: function uploadFile(event) {
	      if (!event) {
	        return false;
	      }

	      if (!this.getChatId()) {
	        this.requestData();
	      }

	      this.uploader.addFilesFromEvent(event);
	    }
	  }, {
	    key: "cancelUploadFile",
	    value: function cancelUploadFile(fileId) {
	      var _this10 = this;

	      var element = this.messagesQueue.find(function (element) {
	        return element.file && element.file.id === fileId;
	      });

	      if (element) {
	        this.uploader.deleteTask(fileId);

	        if (element.xhr) {
	          element.xhr.abort();
	        }

	        this.controller.getStore().dispatch('messages/delete', {
	          chatId: this.getChatId(),
	          id: element.id
	        }).then(function () {
	          _this10.controller.getStore().dispatch('files/delete', {
	            chatId: _this10.getChatId(),
	            id: element.file.id
	          });

	          _this10.messagesQueue = _this10.messagesQueue.filter(function (el) {
	            return el.id !== element.id;
	          });
	        });
	      }
	    }
	  }, {
	    key: "processSendMessages",
	    value: function processSendMessages() {
	      var _this11 = this;

	      if (!this.getDiskFolderId()) {
	        this.requestDiskFolderId().then(function () {
	          _this11.processSendMessages();
	        }).catch(function () {
	          im_lib_logger.Logger.warn('uploadFile', 'Error get disk folder id');
	          return false;
	        });
	        return false;
	      }

	      if (this.offline) {
	        return false;
	      }

	      this.messagesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;

	        if (element.file) {
	          _this11.sendMessageWithFile(element);
	        } else {
	          _this11.sendMessage(element);
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "sendMessage",
	    value: function sendMessage(message) {
	      var _this12 = this;

	      this.controller.application.stopWriting();
	      var quiteId = this.controller.getStore().getters['dialogues/getQuoteId'](this.getDialogId());

	      if (quiteId) {
	        var quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.getChatId(), quiteId);

	        if (quoteMessage) {
	          var user = this.controller.getStore().getters['users/get'](quoteMessage.authorId);
	          var newMessage = [];
	          newMessage.push("------------------------------------------------------");
	          newMessage.push(user.name ? user.name : this.getLocalize('BX_LIVECHAT_SYSTEM_MESSAGE'));
	          newMessage.push(quoteMessage.text);
	          newMessage.push('------------------------------------------------------');
	          newMessage.push(message.text);
	          message.text = newMessage.join("\n");
	          this.quoteMessageClear();
	        }
	      }

	      message.chatId = this.getChatId();
	      this.controller.restClient.callMethod(im_const.RestMethod.imMessageAdd, {
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
	        _this12.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, response, message);
	      }).catch(function (error) {
	        _this12.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, error, message);
	      });
	      return true;
	    }
	  }, {
	    key: "sendMessageWithFile",
	    value: function sendMessageWithFile(message) {
	      this.controller.application.stopWriting();
	      var diskFolderId = this.getDiskFolderId();
	      message.chatId = this.getChatId();
	      this.uploader.senderOptions.customHeaders['Livechat-Dialog-Id'] = message.chatId;
	      this.uploader.senderOptions.customHeaders['Livechat-Auth-Id'] = this.getUserHash();
	      this.uploader.addTask({
	        taskId: message.file.id,
	        fileData: message.file.source.file,
	        fileName: message.file.source.file.name,
	        generateUniqueName: true,
	        diskFolderId: diskFolderId,
	        previewBlob: message.file.previewBlob,
	        chunkSize: this.localize.isCloud ? im_lib_uploader.Uploader.CLOUD_MAX_CHUNK_SIZE : im_lib_uploader.Uploader.BOX_MIN_CHUNK_SIZE
	      });
	    }
	  }, {
	    key: "fileError",
	    value: function fileError(chatId, fileId) {
	      var messageId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
	      this.controller.getStore().dispatch('files/update', {
	        chatId: chatId,
	        id: fileId,
	        fields: {
	          status: im_const.FileStatus.error,
	          progress: 0
	        }
	      });

	      if (messageId) {
	        this.controller.getStore().dispatch('messages/actionError', {
	          chatId: chatId,
	          id: messageId,
	          retry: false
	        });
	      }
	    }
	  }, {
	    key: "requestDiskFolderId",
	    value: function requestDiskFolderId() {
	      var _this13 = this;

	      if (this.requestDiskFolderPromise) {
	        return this.requestDiskFolderPromise;
	      }

	      this.requestDiskFolderPromise = new Promise(function (resolve, reject) {
	        if (_this13.flagRequestDiskFolderIdSended || _this13.getDiskFolderId()) {
	          _this13.flagRequestDiskFolderIdSended = false;
	          resolve();
	          return true;
	        }

	        _this13.flagRequestDiskFolderIdSended = true;

	        _this13.controller.restClient.callMethod(im_const.RestMethod.imDiskFolderGet, {
	          chat_id: _this13.controller.application.getChatId()
	        }).then(function (response) {
	          _this13.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, response);

	          _this13.flagRequestDiskFolderIdSended = false;
	          resolve();
	        }).catch(function (error) {
	          _this13.flagRequestDiskFolderIdSended = false;

	          _this13.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, error);

	          reject();
	        });
	      });
	      return this.requestDiskFolderPromise;
	    }
	  }, {
	    key: "fileCommit",
	    value: function fileCommit(params, message) {
	      var _this14 = this;

	      this.controller.restClient.callMethod(im_const.RestMethod.imDiskFileCommit, {
	        chat_id: params.chatId,
	        upload_id: params.uploadId,
	        message: params.messageText,
	        template_id: params.messageId,
	        file_template_id: params.fileId
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imDiskFileCommit,
	        data: {
	          timMessageType: params.fileType
	        },
	        dialog: this.getDialogData()
	      })).then(function (response) {
	        _this14.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFileCommit, response, message);
	      }).catch(function (error) {
	        _this14.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFileCommit, error, message);
	      });
	      return true;
	    }
	  }, {
	    key: "getDialogHistory",
	    value: function getDialogHistory(lastId) {
	      var _this15 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.application.getRequestMessageLimit();
	      this.controller.restClient.callMethod(im_const.RestMethod.imDialogMessagesGet, {
	        'CHAT_ID': this.getChatId(),
	        'LAST_ID': lastId,
	        'LIMIT': limit,
	        'CONVERT_TEXT': 'Y'
	      }).then(function (result) {
	        _this15.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGet, result);

	        _this15.template.$emit(im_const.EventType.dialog.requestHistoryResult, {
	          count: result.data().messages.length
	        });
	      }).catch(function (result) {
	        _this15.template.$emit(im_const.EventType.dialog.requestHistoryResult, {
	          error: result.error().ex
	        });
	      });
	    }
	  }, {
	    key: "getDialogUnread",
	    value: function getDialogUnread(lastId) {
	      var _this16 = this;

	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.application.getRequestMessageLimit();
	      var promise = new BX.Promise();

	      if (!lastId) {
	        lastId = this.controller.getStore().getters['messages/getLastId'](this.controller.application.getChatId());
	      }

	      if (!lastId) {
	        this.template.$emit(im_const.EventType.dialog.requestUnreadResult, {
	          error: {
	            error: 'LAST_ID_EMPTY',
	            error_description: 'LastId is empty.'
	          }
	        });
	        promise.reject();
	        return promise;
	      }

	      this.controller.application.readMessage(lastId, true, true).then(function () {
	        var _query2;

	        var query = (_query2 = {}, babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogRead, [im_const.RestMethod.imDialogRead, {
	          dialog_id: _this16.getDialogId(),
	          message_id: lastId
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imChatGet, [im_const.RestMethod.imChatGet, {
	          dialog_id: _this16.getDialogId()
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogMessagesGetUnread, [im_const.RestMethod.imDialogMessagesGet, {
	          chat_id: _this16.getChatId(),
	          first_id: lastId,
	          limit: limit,
	          convert_text: 'Y'
	        }]), _query2);

	        _this16.controller.restClient.callBatch(query, function (response) {
	          if (!response) {
	            _this16.template.$emit(im_const.EventType.dialog.requestUnreadResult, {
	              error: {
	                error: 'EMPTY_RESPONSE',
	                error_description: 'Server returned an empty response.'
	              }
	            });

	            promise.reject();
	            return false;
	          }

	          var chatGetResult = response[im_const.RestMethodHandler.imChatGet];

	          if (!chatGetResult.error()) {
	            _this16.controller.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);
	          }

	          var dialogMessageUnread = response[im_const.RestMethodHandler.imDialogMessagesGetUnread];

	          if (dialogMessageUnread.error()) {
	            _this16.template.$emit(im_const.EventType.dialog.requestUnreadResult, {
	              error: dialogMessageUnread.error().ex
	            });
	          } else {
	            _this16.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGetUnread, dialogMessageUnread);

	            _this16.template.$emit(im_const.EventType.dialog.requestUnreadResult, {
	              firstMessageId: dialogMessageUnread.data().messages.length > 0 ? dialogMessageUnread.data().messages[0].id : 0,
	              count: dialogMessageUnread.data().messages.length
	            });
	          }

	          promise.fulfill(response);
	        }, false, false, im_lib_utils.Utils.getLogTrackingParams({
	          name: im_const.RestMethodHandler.imDialogMessagesGetUnread,
	          dialog: _this16.getDialogData()
	        }));
	      });
	      return promise;
	    }
	  }, {
	    key: "retrySendMessage",
	    value: function retrySendMessage(message) {
	      if (this.messagesQueue.find(function (el) {
	        return el.id === message.id;
	      })) {
	        return false;
	      }

	      this.messagesQueue.push({
	        id: message.id,
	        text: message.text,
	        sending: false
	      });
	      this.controller.application.setSendingMessageFlag(message.id);
	      this.processSendMessages();
	    }
	  }, {
	    key: "readMessage",
	    value: function readMessage(messageId) {
	      if (this.offline) {
	        return false;
	      }

	      return this.controller.application.readMessage(messageId);
	    }
	  }, {
	    key: "reactMessage",
	    value: function reactMessage(id, reaction) {
	      this.controller.application.reactMessage(id, reaction.type, reaction.action);
	    }
	  }, {
	    key: "execMessageKeyboardCommand",
	    value: function execMessageKeyboardCommand(data) {
	      if (data.action === 'ACTION' && data.params.action === 'LIVECHAT') {
	        var _data$params = data.params,
	            _dialogId = _data$params.dialogId,
	            _messageId = _data$params.messageId;
	        var values = JSON.parse(data.params.value);
	        var sessionId = parseInt(values.SESSION_ID);

	        if (sessionId !== this.getSessionId() || this.isSessionClose()) {
	          alert(this.localize.BX_LIVECHAT_ACTION_EXPIRED);
	          return false;
	        }

	        this.controller.restClient.callMethod(RestMethod.widgetActionSend, {
	          'MESSAGE_ID': _messageId,
	          'DIALOG_ID': _dialogId,
	          'ACTION_VALUE': data.params.value
	        });
	        return true;
	      }

	      if (data.action !== 'COMMAND') {
	        return false;
	      }

	      var _data$params2 = data.params,
	          dialogId = _data$params2.dialogId,
	          messageId = _data$params2.messageId,
	          botId = _data$params2.botId,
	          command = _data$params2.command,
	          params = _data$params2.params;
	      this.controller.restClient.callMethod(im_const.RestMethod.imMessageCommand, {
	        'MESSAGE_ID': messageId,
	        'DIALOG_ID': dialogId,
	        'BOT_ID': botId,
	        'COMMAND': command,
	        'COMMAND_PARAMS': params
	      });
	      return true;
	    }
	  }, {
	    key: "quoteMessageClear",
	    value: function quoteMessageClear() {
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          quoteId: 0
	        }
	      });
	    }
	  }, {
	    key: "sendDialogVote",
	    value: function sendDialogVote(result) {
	      var _this17 = this;

	      if (!this.getSessionId()) {
	        return false;
	      }

	      this.controller.restClient.callMethod(RestMethod.widgetVoteSend, {
	        'SESSION_ID': this.getSessionId(),
	        'ACTION': result
	      }).catch(function (result) {
	        _this17.controller.getStore().commit('widget/dialog', {
	          userVote: VoteType.none
	        });
	      });
	      this.sendEvent({
	        type: SubscriptionType.userVote,
	        data: {
	          vote: result
	        }
	      });
	    }
	  }, {
	    key: "sendForm",
	    value: function sendForm(type, fields) {
	      var _query3,
	          _this18 = this;

	      im_lib_logger.Logger.info('LiveChatWidgetPrivate.sendForm:', type, fields);
	      var query = (_query3 = {}, babelHelpers.defineProperty(_query3, RestMethod.widgetFormSend, [RestMethod.widgetFormSend, {
	        'CHAT_ID': this.getChatId(),
	        'FORM': type.toUpperCase(),
	        'FIELDS': fields
	      }]), babelHelpers.defineProperty(_query3, RestMethod.widgetUserGet, [RestMethod.widgetUserGet, {}]), _query3);
	      this.controller.restClient.callBatch(query, function (response) {
	        if (!response) {
	          _this18.requestDataSend = false;

	          _this18.setError('EMPTY_RESPONSE', 'Server returned an empty response.');

	          return false;
	        }

	        var userGetResult = response[RestMethod.widgetUserGet];

	        if (userGetResult.error()) {
	          _this18.requestDataSend = false;

	          _this18.setError(userGetResult.error().ex.error, userGetResult.error().ex.error_description);

	          return false;
	        }

	        _this18.controller.executeRestAnswer(RestMethod.widgetUserGet, userGetResult);

	        _this18.sendEvent({
	          type: SubscriptionType.userForm,
	          data: {
	            form: type,
	            fields: fields
	          }
	        });
	      }, false, false, im_lib_utils.Utils.getLogTrackingParams({
	        name: RestMethod.widgetUserGet,
	        dialog: this.getDialogData()
	      }));
	    }
	  }, {
	    key: "getHtmlHistory",
	    value: function getHtmlHistory() {
	      var chatId = this.getChatId();

	      if (chatId <= 0) {
	        console.error('Incorrect chatId value');
	      }

	      var config = {
	        chatId: this.getChatId()
	      };
	      this.requestControllerAction('imopenlines.widget.history.download', config).then(function (response) {
	        var contentType = response.headers.get('Content-Type');

	        if (contentType.startsWith('application/json')) {
	          return response.json();
	        }

	        return response.blob();
	      }).then(function (result) {
	        if (result instanceof Blob) {
	          var url = window.URL.createObjectURL(result);
	          var a = document.createElement('a');
	          a.href = url;
	          a.download = chatId + '.html';
	          document.body.appendChild(a);
	          a.click();
	          a.remove();
	        } else if (result.hasOwnProperty('errors')) {
	          console.error(result.errors[0]);
	        } else {
	          console.error('Unknown error.');
	        }
	      }).catch(function () {
	        return console.error('Fetch error.');
	      });
	    }
	    /**
	     * Basic method to run actions.
	     * If you need to extend it, check BX.ajax.runAction to extend this method.
	     */

	  }, {
	    key: "requestControllerAction",
	    value: function requestControllerAction(action, config) {
	      var host = this.host ? this.host : '';
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
	    key: "sendConsentDecision",
	    value: function sendConsentDecision(result) {
	      result = result === true;
	      this.controller.getStore().commit('widget/dialog', {
	        userConsent: result
	      });

	      if (result && this.isUserRegistered()) {
	        this.controller.restClient.callMethod(RestMethod.widgetUserConsentApply, {
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
	      if (!this.controller || !this.controller.getStore()) {
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

	      if (!this.controller.getStore()) {
	        this.callOpenFlag = true;
	        return true;
	      }

	      if (!params.openFromButton && this.buttonInstance) {
	        this.buttonInstance.wm.showById('openline_livechat');
	      }

	      if (!this.checkBrowserVersion()) {
	        this.setError('OLD_BROWSER_LOCALIZED', this.localize.BX_LIVECHAT_OLD_BROWSER);
	      } else if (im_lib_utils.Utils.versionCompare(ui_vue.Vue.version(), '2.1') < 0) {
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
	      if (!this.controller || !this.controller.getStore()) {
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
	        type: SubscriptionType.configLoaded,
	        data: {}
	      });

	      if (this.controller.getStore().state.widget.common.reopen) {
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
	      return this.controller.getStore().state.widget.common.configId;
	    }
	  }, {
	    key: "isWidgetDataRequested",
	    value: function isWidgetDataRequested() {
	      return this.widgetDataRequested;
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
	    }
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
	    }
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

	      if (code === 'LIVECHAT_AUTH_FAILED') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_AUTH_FAILED').replace('#LINK_START#', '<a href="javascript:void();" onclick="location.reload()">').replace('#LINK_END#', '</a>');
	        this.setNewAuthToken();
	      } else if (code === 'LIVECHAT_AUTH_PORTAL_USER') {
	        localizeDescription = this.getLocalize('BX_LIVECHAT_PORTAL_USER_NEW').replace('#LINK_START#', '<a href="' + this.host + '">').replace('#LINK_END#', '</a>');
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
	    }
	    /**
	     *
	     * @param params {Object}
	     * @returns {Function|Boolean} - Unsubscribe callback function or False
	     */

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

	      if (this.subscribers[SubscriptionType.every] instanceof Array && this.subscribers[SubscriptionType.every].length > 0) {
	        this.subscribers[SubscriptionType.every].forEach(function (callback) {
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

	ui_vue.BitrixVue.component('bx-livechat', {
	  mixins: [im_mixin.DialogCore, im_mixin.TextareaCore, im_mixin.TextareaUploadFile, im_mixin.DialogReadMessages, im_mixin.DialogClickOnCommand, im_mixin.DialogClickOnUserName, im_mixin.DialogClickOnKeyboardButton, im_mixin.DialogClickOnMessageMenu, im_mixin.DialogClickOnMessageRetry, im_mixin.DialogSetMessageReaction, im_mixin.DialogClickOnUploadCancel],
	  data: function data() {
	    return {
	      viewPortMetaSiteNode: null,
	      viewPortMetaWidgetNode: null,
	      storedMessage: '',
	      storedFile: null,
	      widgetMinimumHeight: 435,
	      widgetMinimumWidth: 340,
	      widgetBaseHeight: 557,
	      widgetBaseWidth: 435,
	      widgetMargin: 50,
	      widgetAvailableHeight: 0,
	      widgetAvailableWidth: 0,
	      widgetCurrentHeight: 0,
	      widgetCurrentWidth: 0,
	      widgetDrag: false,
	      textareaFocused: false,
	      textareaDrag: false,
	      textareaHeight: 100,
	      textareaMinimumHeight: 100,
	      textareaMaximumHeight: im_lib_utils.Utils.device.isMobile() ? 200 : 300,
	      zIndexStackInstance: null
	    };
	  },
	  created: function created() {
	    im_lib_logger.Logger.warn('bx-livechat created');
	    this.onCreated();
	    document.addEventListener('keydown', this.onWindowKeyDown);

	    if (!im_lib_utils.Utils.device.isMobile() && !this.widget.common.pageMode) {
	      window.addEventListener('resize', this.getAvailableSpaceFunc = im_lib_utils.Utils.throttle(this.getAvailableSpace, 50));
	    }

	    main_core_events.EventEmitter.subscribe(EventType.requestShowForm, this.onRequestShowForm);
	  },
	  mounted: function mounted() {
	    this.zIndexStackInstance = this.$Bitrix.Data.get('zIndexStack');

	    if (this.zIndexStackInstance && !!this.$refs.widgetWrapper) {
	      this.zIndexStackInstance.register(this.$refs.widgetWrapper);
	    }
	  },
	  beforeDestroy: function beforeDestroy() {
	    if (this.zIndexStackInstance) {
	      this.zIndexStackInstance.unregister(this.$refs.widgetWrapper);
	    }

	    document.removeEventListener('keydown', this.onWindowKeyDown);

	    if (!im_lib_utils.Utils.device.isMobile() && !this.widget.common.pageMode) {
	      window.removeEventListener('resize', this.getAvailableSpaceFunc);
	    }

	    main_core_events.EventEmitter.unsubscribe(EventType.requestShowForm, this.onRequestShowForm);
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
	    EventType: function EventType$$1() {
	      return im_const.EventType;
	    },
	    textareaHeightStyle: function textareaHeightStyle(state) {
	      return {
	        flex: '0 0 ' + this.textareaHeight + 'px'
	      };
	    },
	    textareaBottomMargin: function textareaBottomMargin() {
	      if (!this.widget.common.copyright && !this.isBottomLocation) {
	        return {
	          marginBottom: '5px'
	        };
	      }

	      return '';
	    },
	    widgetBaseSizes: function widgetBaseSizes() {
	      return {
	        width: this.widgetBaseWidth,
	        height: this.widgetBaseHeight
	      };
	    },
	    widgetHeightStyle: function widgetHeightStyle() {
	      if (im_lib_utils.Utils.device.isMobile() || this.widget.common.pageMode) {
	        return;
	      }

	      if (this.widgetAvailableHeight < this.widgetBaseSizes.height || this.widgetAvailableHeight < this.widgetCurrentHeight) {
	        this.widgetCurrentHeight = Math.max(this.widgetAvailableHeight, this.widgetMinimumHeight);
	      }

	      return this.widgetCurrentHeight + 'px';
	    },
	    widgetWidthStyle: function widgetWidthStyle() {
	      if (im_lib_utils.Utils.device.isMobile() || this.widget.common.pageMode) {
	        return;
	      }

	      if (this.widgetAvailableWidth < this.widgetBaseSizes.width || this.widgetAvailableWidth < this.widgetCurrentWidth) {
	        this.widgetCurrentWidth = Math.max(this.widgetAvailableWidth, this.widgetMinimumWidth);
	      }

	      return this.widgetCurrentWidth + 'px';
	    },
	    userSelectStyle: function userSelectStyle() {
	      return this.widgetDrag ? 'none' : 'auto';
	    },
	    isBottomLocation: function isBottomLocation() {
	      return [LocationType.bottomLeft, LocationType.bottomMiddle, LocationType.bottomRight].includes(this.widget.common.location);
	    },
	    isLeftLocation: function isLeftLocation() {
	      return [LocationType.bottomLeft, LocationType.topLeft, LocationType.topMiddle].includes(this.widget.common.location);
	    },
	    localize: function localize() {
	      return ui_vue.BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
	    },
	    widgetMobileDisabled: function widgetMobileDisabled(state) {
	      if (state.application.device.type === im_const.DeviceType.mobile) {
	        if (navigator.userAgent.toString().includes('iPad')) ; else if (state.application.device.orientation === im_const.DeviceOrientation.horizontal) {
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

	      if (state.application.common.languageId === LanguageType.russian) {
	        className.push('bx-livechat-logo-ru');
	      } else if (state.application.common.languageId === LanguageType.ukraine) {
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

	      if (state.widget.dialog.operator.name && !(state.application.device.type === im_const.DeviceType.mobile && state.application.device.orientation === im_const.DeviceOrientation.horizontal)) {
	        className.push('bx-livechat-has-operator');
	      }

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

	      if (state.widget.common.styles.backgroundColor && im_lib_utils.Utils.isDarkColor(state.widget.common.styles.iconColor)) {
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
	    dialog: function dialog(state) {
	      return state.dialogues.collection[state.application.dialog.dialogId];
	    },
	    messageCollection: function messageCollection(state) {
	      return state.messages.collection[state.application.dialog.chatId];
	    }
	  })),
	  watch: {
	    sessionClose: function sessionClose(value) {
	      im_lib_logger.Logger.log('sessionClose change', value);
	    },
	    //Redefined for uploadFile mixin
	    dialogInited: function dialogInited(newValue) {
	      return false;
	    }
	  },
	  methods: {
	    getRestClient: function getRestClient() {
	      return this.$Bitrix.RestClient.get();
	    },
	    getApplication: function getApplication() {
	      return this.$Bitrix.Application.get();
	    },
	    onSendMessage: function onSendMessage(_ref) {
	      var event = _ref.data;
	      event.focus = event.focus !== false;

	      if (this.widget.common.showForm === FormType.smile) {
	        this.$store.commit('widget/common', {
	          showForm: FormType.none
	        });
	      } //show consent window if needed


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
	      this.getApplication().addMessage(event.text);

	      if (event.focus) {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }

	      return true;
	    },
	    close: function close(event) {
	      if (this.widget.common.pageMode) {
	        return false;
	      }

	      this.onBeforeClose();
	      this.$store.commit('widget/common', {
	        showed: false
	      });
	    },
	    getAvailableSpace: function getAvailableSpace() {
	      if (this.isBottomLocation) {
	        var bottomPosition = this.$refs.widgetWrapper.getBoundingClientRect().bottom;
	        var widgetBottomMargin = window.innerHeight - bottomPosition;
	        this.widgetAvailableHeight = window.innerHeight - this.widgetMargin - widgetBottomMargin;
	      } else {
	        var topPosition = this.$refs.widgetWrapper.getBoundingClientRect().top;
	        this.widgetAvailableHeight = window.innerHeight - this.widgetMargin - topPosition;
	      }

	      this.widgetAvailableWidth = window.innerWidth - this.widgetMargin * 2;
	    },
	    showLikeForm: function showLikeForm() {
	      if (this.offline) {
	        return false;
	      }

	      clearTimeout(this.showFormTimeout);

	      if (!this.widget.common.vote.enable) {
	        return false;
	      }

	      if (this.widget.dialog.sessionClose && this.widget.dialog.userVote !== VoteType.none) {
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
	    onOpenMenu: function onOpenMenu(event) {
	      this.getApplication().getHtmlHistory();
	    },
	    hideForm: function hideForm() {
	      clearTimeout(this.showFormTimeout);

	      if (this.widget.common.showForm !== FormType.none) {
	        this.$store.commit('widget/common', {
	          showForm: FormType.none
	        });
	      }
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
	      this.getApplication().sendConsentDecision(true);

	      if (this.storedMessage || this.storedFile) {
	        if (this.storedMessage) {
	          this.onSendMessage({
	            data: {
	              focus: this.application.device.type !== im_const.DeviceType.mobile
	            }
	          });
	          this.storedMessage = '';
	        }

	        if (this.storedFile) {
	          this.onTextareaFileSelected();
	          this.storedFile = '';
	        }
	      } else if (this.widget.common.showForm === FormType.none) {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	    },
	    disagreeConsentWidow: function disagreeConsentWidow() {
	      this.$store.commit('widget/common', {
	        showForm: FormType.none
	      });
	      this.$store.commit('widget/common', {
	        showConsent: false
	      });
	      this.getApplication().sendConsentDecision(false);

	      if (this.storedMessage) {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.insertText, {
	          text: this.storedMessage,
	          focus: this.application.device.type !== im_const.DeviceType.mobile
	        });
	        this.storedMessage = '';
	      }

	      if (this.storedFile) {
	        this.storedFile = '';
	      }

	      if (this.application.device.type !== im_const.DeviceType.mobile) {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	    },
	    logEvent: function logEvent(name) {
	      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	        params[_key - 1] = arguments[_key];
	      }

	      im_lib_logger.Logger.info.apply(im_lib_logger.Logger, [name].concat(params));
	    },
	    onCreated: function onCreated() {
	      var _this = this;

	      if (im_lib_utils.Utils.device.isMobile()) {
	        var viewPortMetaSiteNode = Array.from(document.head.getElementsByTagName('meta')).filter(function (element) {
	          return element.name === 'viewport';
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

	        if (im_lib_utils.Utils.browser.isSafariBased()) {
	          document.body.classList.add('bx-livechat-mobile-safari-based');
	        }

	        setTimeout(function () {
	          _this.$store.dispatch('widget/show');
	        }, 50);
	      } else {
	        this.$store.dispatch('widget/show').then(function () {
	          _this.widgetCurrentHeight = _this.widgetBaseSizes.height;
	          _this.widgetCurrentWidth = _this.widgetBaseSizes.width;

	          _this.getAvailableSpace();

	          _this.widgetCurrentHeight = _this.widget.common.widgetHeight || _this.widgetCurrentHeight;
	          _this.widgetCurrentWidth = _this.widget.common.widgetWidth || _this.widgetCurrentWidth;
	        });
	      }

	      this.textareaHeight = this.widget.common.textareaHeight || this.textareaHeight;
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
	    onBeforeClose: function onBeforeClose() {
	      if (im_lib_utils.Utils.device.isMobile()) {
	        document.body.classList.remove('bx-livechat-mobile-state');

	        if (im_lib_utils.Utils.browser.isSafariBased()) {
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
	      this.getApplication().close();
	    },
	    onRequestShowForm: function onRequestShowForm(_ref2) {
	      var _this2 = this;

	      var event = _ref2.data;
	      clearTimeout(this.showFormTimeout);

	      if (event.type === FormType.welcome) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this2.showWelcomeForm();
	          }, 5000);
	        } else {
	          this.showWelcomeForm();
	        }
	      } else if (event.type === FormType.offline) {
	        if (event.delayed) {
	          this.showFormTimeout = setTimeout(function () {
	            _this2.showOfflineForm();
	          }, 3000);
	        } else {
	          this.showOfflineForm();
	        }
	      } else if (event.type === FormType.like) {
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
	      this.getApplication().getDialogHistory(event.lastId);
	    },
	    onDialogRequestUnread: function onDialogRequestUnread(event) {
	      this.getApplication().getDialogUnread(event.lastId);
	    },
	    onClickOnUserName: function onClickOnUserName(_ref3) {
	      var event = _ref3.data;
	      // TODO name push to auto-replace mention holder - User Name -> [USER=274]User Name[/USER]
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.insertText, {
	        text: event.user.name + ', '
	      });
	    },
	    onClickOnUploadCancel: function onClickOnUploadCancel(_ref4) {
	      var event = _ref4.data;
	      this.getApplication().cancelUploadFile(event.file.id);
	    },
	    onClickOnKeyboardButton: function onClickOnKeyboardButton(_ref5) {
	      var event = _ref5.data;
	      this.getApplication().execMessageKeyboardCommand(event);
	    },
	    onClickOnCommand: function onClickOnCommand(_ref6) {
	      var event = _ref6.data;

	      if (event.type === 'put') {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.insertText, {
	          text: event.value + ' '
	        });
	      } else if (event.type === 'send') {
	        this.getApplication().addMessage(event.value);
	      } else {
	        im_lib_logger.Logger.warn('Unprocessed command', event);
	      }
	    },
	    onClickOnMessageMenu: function onClickOnMessageMenu(_ref7) {
	      var event = _ref7.data;
	      im_lib_logger.Logger.warn('Message menu:', event);
	    },
	    onClickOnMessageRetry: function onClickOnMessageRetry(_ref8) {
	      var event = _ref8.data;
	      im_lib_logger.Logger.warn('Message retry:', event);
	      this.getApplication().retrySendMessage(event.message);
	    },
	    onReadMessage: function onReadMessage(_ref9) {
	      var event = _ref9.data;
	      this.getApplication().readMessage(event.id);
	    },
	    onSetMessageReaction: function onSetMessageReaction(_ref10) {
	      var event = _ref10.data;
	      this.getApplication().reactMessage(event.message.id, event.reaction);
	    },
	    onClickOnDialog: function onClickOnDialog(_ref11) {
	      var event = _ref11.data;

	      if (this.widget.common.showForm !== FormType.none) {
	        this.$store.commit('widget/common', {
	          showForm: FormType.none
	        });
	      }
	    },
	    onTextareaKeyUp: function onTextareaKeyUp(_ref12) {
	      var event = _ref12.data;

	      if (this.widget.common.watchTyping && this.widget.dialog.sessionId && !this.widget.dialog.sessionClose && this.widget.dialog.operator.id && this.widget.dialog.operatorChatId && this.$Bitrix.PullClient.get().isPublishingEnabled()) {
	        var infoString = main_md5.md5(this.widget.dialog.sessionId + '/' + this.application.dialog.chatId + '/' + this.widget.user.id);
	        this.$Bitrix.PullClient.get().sendMessage([this.widget.dialog.operator.id], 'imopenlines', 'linesMessageWrite', {
	          text: event.text,
	          infoString: infoString,
	          operatorChatId: this.widget.dialog.operatorChatId
	        });
	      }
	    },
	    onTextareaFocus: function onTextareaFocus(_ref13) {
	      var _this3 = this;

	      var event = _ref13.data;

	      if (this.widget.common.copyright && this.application.device.type === im_const.DeviceType.mobile) {
	        this.widget.common.copyright = false;
	      }

	      if (im_lib_utils.Utils.device.isMobile()) {
	        clearTimeout(this.onTextareaFocusScrollTimeout);
	        this.onTextareaFocusScrollTimeout = setTimeout(function () {
	          document.addEventListener('scroll', _this3.onWindowScroll);
	        }, 1000);
	      }

	      this.textareaFocused = true;
	    },
	    onTextareaBlur: function onTextareaBlur(_ref14) {
	      var _this4 = this;

	      var event = _ref14.data;

	      if (!this.widget.common.copyright && this.widget.common.copyright !== this.getApplication().copyright) {
	        this.widget.common.copyright = this.getApplication().copyright;
	        this.$nextTick(function () {
	          main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	            chatId: _this4.chatId,
	            force: true
	          });
	        });
	      }

	      if (im_lib_utils.Utils.device.isMobile()) {
	        clearTimeout(this.onTextareaFocusScrollTimeout);
	        document.removeEventListener('scroll', this.onWindowScroll);
	      }

	      this.textareaFocused = false;
	    },
	    onTextareaStartDrag: function onTextareaStartDrag(event) {
	      if (this.textareaDrag) {
	        return;
	      }

	      im_lib_logger.Logger.log('Livechat: textarea drag started');
	      this.textareaDrag = true;
	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.textareaDragCursorStartPoint = event.clientY;
	      this.textareaDragHeightStartPoint = this.textareaHeight;
	      this.onTextareaDragEventAdd();
	      main_core_events.EventEmitter.emit(im_const.EventType.textarea.setBlur, true);
	    },
	    onTextareaContinueDrag: function onTextareaContinueDrag(event) {
	      if (!this.textareaDrag) {
	        return;
	      }

	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.textareaDragCursorControlPoint = event.clientY;
	      var textareaHeight = Math.max(Math.min(this.textareaDragHeightStartPoint + this.textareaDragCursorStartPoint - this.textareaDragCursorControlPoint, this.textareaMaximumHeight), this.textareaMinimumHeight);
	      im_lib_logger.Logger.log('Livechat: textarea drag', 'new: ' + textareaHeight, 'curr: ' + this.textareaHeight);

	      if (this.textareaHeight !== textareaHeight) {
	        this.textareaHeight = textareaHeight;
	      }
	    },
	    onTextareaStopDrag: function onTextareaStopDrag() {
	      if (!this.textareaDrag) {
	        return;
	      }

	      im_lib_logger.Logger.log('Livechat: textarea drag ended');
	      this.textareaDrag = false;
	      this.onTextareaDragEventRemove();
	      this.$store.commit('widget/common', {
	        textareaHeight: this.textareaHeight
	      });
	      main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	        chatId: this.chatId,
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
	    onTextareaFileSelected: function onTextareaFileSelected() {
	      var _ref15 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
	          event = _ref15.data;

	      var fileInputEvent = null;

	      if (event && event.fileChangeEvent && event.fileChangeEvent.target.files.length > 0) {
	        fileInputEvent = event.fileChangeEvent;
	      } else {
	        fileInputEvent = this.storedFile;
	      }

	      if (!fileInputEvent) {
	        return false;
	      }

	      if (!this.widget.dialog.userConsent && this.widget.common.consentUrl) {
	        this.storedFile = event.fileChangeEvent;
	        this.showConsentWidow();
	        return false;
	      }

	      this.getApplication().uploadFile(fileInputEvent);
	    },
	    onTextareaAppButtonClick: function onTextareaAppButtonClick(_ref16) {
	      var event = _ref16.data;

	      if (event.appId === FormType.smile) {
	        if (this.widget.common.showForm === FormType.smile) {
	          this.$store.commit('widget/common', {
	            showForm: FormType.none
	          });
	        } else {
	          this.$store.commit('widget/common', {
	            showForm: FormType.smile
	          });
	        }
	      } else {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	    },
	    onTextareaEdit: function onTextareaEdit(_ref17) {
	      var event = _ref17.data;
	      this.logEvent('edit message', event);
	    },
	    onPullRequestConfig: function onPullRequestConfig(event) {
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
	      if (this.widgetDrag) {
	        return;
	      }

	      this.widgetDrag = true;
	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.widgetDragCursorStartPointY = event.clientY;
	      this.widgetDragCursorStartPointX = event.clientX;
	      this.widgetDragHeightStartPoint = this.widgetCurrentHeight;
	      this.widgetDragWidthStartPoint = this.widgetCurrentWidth;
	      this.onWidgetDragEventAdd();
	    },
	    onWidgetContinueDrag: function onWidgetContinueDrag(event) {
	      if (!this.widgetDrag) {
	        return;
	      }

	      event = event.changedTouches ? event.changedTouches[0] : event;
	      this.widgetDragCursorControlPointY = event.clientY;
	      this.widgetDragCursorControlPointX = event.clientX;
	      var widgetHeight = 0;

	      if (this.isBottomLocation) {
	        widgetHeight = Math.max(Math.min(this.widgetDragHeightStartPoint + this.widgetDragCursorStartPointY - this.widgetDragCursorControlPointY, this.widgetAvailableHeight), this.widgetMinimumHeight);
	      } else {
	        widgetHeight = Math.max(Math.min(this.widgetDragHeightStartPoint - this.widgetDragCursorStartPointY + this.widgetDragCursorControlPointY, this.widgetAvailableHeight), this.widgetMinimumHeight);
	      }

	      var widgetWidth = 0;

	      if (this.isLeftLocation) {
	        widgetWidth = Math.max(Math.min(this.widgetDragWidthStartPoint - this.widgetDragCursorStartPointX + this.widgetDragCursorControlPointX, this.widgetAvailableWidth), this.widgetMinimumWidth);
	      } else {
	        widgetWidth = Math.max(Math.min(this.widgetDragWidthStartPoint + this.widgetDragCursorStartPointX - this.widgetDragCursorControlPointX, this.widgetAvailableWidth), this.widgetMinimumWidth);
	      }

	      if (this.widgetCurrentHeight !== widgetHeight) {
	        this.widgetCurrentHeight = widgetHeight;
	      }

	      if (this.widgetCurrentWidth !== widgetWidth) {
	        this.widgetCurrentWidth = widgetWidth;
	      }
	    },
	    onWidgetStopDrag: function onWidgetStopDrag() {
	      if (!this.widgetDrag) {
	        return;
	      }

	      this.widgetDrag = false;
	      this.onWidgetDragEventRemove();
	      this.$store.commit('widget/common', {
	        widgetHeight: this.widgetCurrentHeight,
	        widgetWidth: this.widgetCurrentWidth
	      });
	    },
	    onWidgetDragEventAdd: function onWidgetDragEventAdd() {
	      document.addEventListener('mousemove', this.onWidgetContinueDrag);
	      document.addEventListener('mouseup', this.onWidgetStopDrag);
	      document.addEventListener('mouseleave', this.onWidgetStopDrag);
	    },
	    onWidgetDragEventRemove: function onWidgetDragEventRemove() {
	      document.removeEventListener('mousemove', this.onWidgetContinueDrag);
	      document.removeEventListener('mouseup', this.onWidgetStopDrag);
	      document.removeEventListener('mouseleave', this.onWidgetStopDrag);
	    },
	    onWindowKeyDown: function onWindowKeyDown(event) {
	      if (event.keyCode === 27) {
	        if (this.widget.common.showForm !== FormType.none) {
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
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setFocus);
	      }
	    },
	    onWindowScroll: function onWindowScroll(event) {
	      clearTimeout(this.onWindowScrollTimeout);
	      this.onWindowScrollTimeout = setTimeout(function () {
	        main_core_events.EventEmitter.emit(im_const.EventType.textarea.setBlur, true);
	      }, 50);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-show\" leave-active-class=\"bx-livechat-close\" @after-leave=\"onAfterClose\">\n\t\t\t<div :class=\"widgetClassName\" v-if=\"widget.common.showed\" :style=\"{height: widgetHeightStyle, width: widgetWidthStyle, userSelect: userSelectStyle}\" ref=\"widgetWrapper\">\n\t\t\t\t<div class=\"bx-livechat-box\">\n\t\t\t\t\t<div v-if=\"isBottomLocation\" class=\"bx-livechat-widget-resize-handle\" @mousedown=\"onWidgetStartDrag\"></div>\n\t\t\t\t\t<bx-livechat-head :isWidgetDisabled=\"widgetMobileDisabled\" @like=\"showLikeForm\" @openMenu=\"onOpenMenu\" @close=\"close\"/>\n\t\t\t\t\t<template v-if=\"widgetMobileDisabled\">\n\t\t\t\t\t\t<bx-livechat-body-orientation-disabled/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"application.error.active\">\n\t\t\t\t\t\t<bx-livechat-body-error/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else-if=\"!widget.common.configId\">\n\t\t\t\t\t\t<div class=\"bx-livechat-body\" key=\"loading-body\">\n\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\t\t\t\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<template v-if=\"!widget.common.dialogStart\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-body\" key=\"welcome-body\">\n\t\t\t\t\t\t\t\t<bx-livechat-body-operators/>\n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widget.common.showForm === FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"widget.common.dialogStart\">\n\t\t\t\t\t\t\t<bx-pull-component-status :canReconnect=\"true\" @reconnect=\"onPullRequestConfig\"/>\n\t\t\t\t\t\t\t<div :class=\"['bx-livechat-body', {'bx-livechat-body-with-message': showMessageDialog}]\" key=\"with-message\">\n\t\t\t\t\t\t\t\t<template v-if=\"showMessageDialog\">\n\t\t\t\t\t\t\t\t\t<div class=\"bx-livechat-dialog\">\n\t\t\t\t\t\t\t\t\t\t<bx-im-component-dialog\n\t\t\t\t\t\t\t\t\t\t\t:userId=\"application.common.userId\" \n\t\t\t\t\t\t\t\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t\t\t\t\t\t\t\t:messageLimit=\"application.dialog.messageLimit\"\n\t\t\t\t\t\t\t\t\t\t\t:enableReactions=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:enableDateActions=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableCreateContent=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:enableGestureQuote=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:enableGestureMenu=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageAvatar=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showMessageMenu=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:skipDataRequest=\"true\"\n\t\t\t\t\t\t\t\t\t\t\t:showLoadingState=\"false\"\n\t\t\t\t\t\t\t\t\t\t\t:showEmptyState=\"false\"\n\t\t\t\t\t\t\t\t\t\t />\n\t\t\t\t\t\t\t\t\t</div>\t \n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<bx-livechat-body-loading/>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t  \n\t\t\t\t\t\t\t\t<keep-alive include=\"bx-livechat-smiles\">\n\t\t\t\t\t\t\t\t\t<template v-if=\"widget.common.showForm === FormType.like && widget.common.vote.enable\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-vote/>\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.welcome\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-welcome/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.offline\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-offline/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.history\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-form-history/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t\t<template v-else-if=\"widget.common.showForm === FormType.smile\">\n\t\t\t\t\t\t\t\t\t\t<bx-livechat-smiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\"/>\t\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</keep-alive>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\t\n\t\t\t\t\t\t<div class=\"bx-livechat-textarea\" :style=\"[textareaHeightStyle, textareaBottomMargin]\" ref=\"textarea\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-textarea-resize-handle\" @mousedown=\"onTextareaStartDrag\" @touchstart=\"onTextareaStartDrag\"></div>\n\t\t\t\t\t\t\t<bx-im-component-textarea\n\t\t\t\t\t\t\t\t:siteId=\"application.common.siteId\"\n\t\t\t\t\t\t\t\t:userId=\"application.common.userId\"\n\t\t\t\t\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t\t\t\t\t:writesEventLetter=\"3\"\n\t\t\t\t\t\t\t\t:enableEdit=\"true\"\n\t\t\t\t\t\t\t\t:enableCommand=\"false\"\n\t\t\t\t\t\t\t\t:enableMention=\"false\"\n\t\t\t\t\t\t\t\t:enableFile=\"application.disk.enabled\"\n\t\t\t\t\t\t\t\t:autoFocus=\"application.device.type !== DeviceType.mobile\"\n\t\t\t\t\t\t\t\t:styles=\"{button: {backgroundColor: widget.common.styles.backgroundColor, iconColor: widget.common.styles.iconColor}}\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"!widget.common.copyright && !isBottomLocation\" class=\"bx-livechat-nocopyright-resize-wrap\" style=\"position: relative;\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-widget-resize-handle\" @mousedown=\"onWidgetStartDrag\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<bx-livechat-form-consent @agree=\"agreeConsentWidow\" @disagree=\"disagreeConsentWidow\"/>\n\t\t\t\t\t\t<template v-if=\"widget.common.copyright\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-copyright\">\t\n\t\t\t\t\t\t\t\t<template v-if=\"widget.common.copyrightUrl\">\n\t\t\t\t\t\t\t\t\t<a class=\"bx-livechat-copyright-link\" :href=\"widget.common.copyrightUrl\" target=\"_blank\">\n\t\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-name\">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>\n\t\t\t\t\t\t\t\t\t<span class=\"bx-livechat-logo-icon\"></span>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t<div v-if=\"!isBottomLocation\" class=\"bx-livechat-widget-resize-handle\" @mousedown=\"onWidgetStartDrag\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Body error component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-livechat-body-error', {
	  computed: babelHelpers.objectSpread({}, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"error-body\">\n\t\t\t<div class=\"bx-livechat-warning-window\">\n\t\t\t\t<div class=\"bx-livechat-warning-icon\"></div>\n\t\t\t\t<template v-if=\"application.error.description\"> \n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\" v-html=\"application.error.description\"></div>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-md bx-livechat-warning-msg\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_ERROR_TITLE')}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_ERROR_DESC')}}</div>\n\t\t\t\t</template> \n\t\t\t</div>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Head component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-livechat-head', {
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
	    openMenu: function openMenu(event) {
	      this.$emit('openMenu', event);
	    }
	  },
	  computed: babelHelpers.objectSpread({
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
	  template: "\n\t\t<div class=\"bx-livechat-head-wrap\">\n\t\t\t<template v-if=\"isWidgetDisabled\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\t\n\t\t\t</template>\n\t\t\t<template v-else-if=\"application.error.active\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<template v-else-if=\"!widget.common.configId\">\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-else>\n\t\t\t\t<div class=\"bx-livechat-head\" :style=\"customBackgroundStyle\">\n\t\t\t\t\t<template v-if=\"!showName\">\n\t\t\t\t\t\t<div class=\"bx-livechat-title\">{{chatTitle}}</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div class=\"bx-livechat-user bx-livechat-status-online\">\n\t\t\t\t\t\t\t<template v-if=\"widget.dialog.operator.avatar\">\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\" :style=\"'background-image: url('+encodeURI(widget.dialog.operator.avatar)+')'\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"widget.dialog.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t<div class=\"bx-livechat-user-icon\">\n\t\t\t\t\t\t\t\t\t<div v-if=\"widget.dialog.operator.online\" class=\"bx-livechat-user-status\" :style=\"customBackgroundOnlineStyle\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"bx-livechat-user-info\">\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-name\">{{operatorName}}</div>\n\t\t\t\t\t\t\t<div class=\"bx-livechat-user-position\">{{operatorDescription}}</div>\t\t\t\t\t\t\t\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"bx-livechat-control-box\">\n\t\t\t\t\t\t<span class=\"bx-livechat-control-box-active\" v-if=\"widget.common.dialogStart && widget.dialog.sessionId\">\n\t\t\t\t\t\t\t<button v-if=\"widget.common.vote.enable && voteActive\" :class=\"'bx-livechat-control-btn bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(widget.dialog.userVote)\" :title=\"localize.BX_LIVECHAT_VOTE_BUTTON\" @click=\"like\"></button>\n\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\tv-if=\"!ie11 && application.dialog.chatId > 0\"\n\t\t\t\t\t\t\t\tclass=\"bx-livechat-control-btn bx-livechat-control-btn-menu\"\n\t\t\t\t\t\t\t\t@click=\"openMenu\"\n\t\t\t\t\t\t\t\t:title=\"localize.BX_LIVECHAT_DOWNLOAD_HISTORY\"\n\t\t\t\t\t\t\t></button>\n\t\t\t\t\t\t</span>\t\n\t\t\t\t\t\t<button v-if=\"!widget.common.pageMode\" class=\"bx-livechat-control-btn bx-livechat-control-btn-close\" :title=\"localize.BX_LIVECHAT_CLOSE_BUTTON\" @click=\"close\"></button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t"
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

	/**
	 * Bitrix OpenLines widget
	 * Body operators component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-livechat-body-operators', {
	  computed: babelHelpers.objectSpread({}, ui_vue_vuex.Vuex.mapState({
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
	ui_vue.BitrixVue.component('bx-livechat-body-orientation-disabled', {
	  template: "\n\t\t<div class=\"bx-livechat-body\" key=\"orientation-head\">\n\t\t\t<div class=\"bx-livechat-mobile-orientation-box\">\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-icon\"></div>\n\t\t\t\t<div class=\"bx-livechat-mobile-orientation-text\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_MOBILE_ROTATE')}}</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Form consent component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-livechat-form-consent', {
	  /**
	   * @emits 'agree' {event: object} -- 'event' - click event
	   * @emits 'disagree' {event: object} -- 'event' - click event
	   */
	  computed: babelHelpers.objectSpread({}, ui_vue_vuex.Vuex.mapState({
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
	  template: "\n\t\t<transition @enter=\"onShow\" @leave=\"onHide\">\n\t\t\t<template v-if=\"widget.common.showConsent && widget.common.consentUrl\">\n\t\t\t\t<div class=\"bx-livechat-consent-window\">\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-title\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_TITLE')}}</div>\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-content\">\n\t\t\t\t\t\t<iframe class=\"bx-livechat-consent-window-content-iframe\" ref=\"iframe\" frameborder=\"0\" marginheight=\"0\"  marginwidth=\"0\" allowtransparency=\"allow-same-origin\" seamless=\"true\" :src=\"widget.common.consentUrl\" @keydown=\"onKeyDown\"></iframe>\n\t\t\t\t\t</div>\t\t\t\t\t\t\t\t\n\t\t\t\t\t<div class=\"bx-livechat-consent-window-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" ref=\"success\" @click=\"agree\" @keydown=\"onKeyDown\" v-focus>{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_AGREE')}}</button>\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-cancel\" ref=\"cancel\" @click=\"disagree\" @keydown=\"onKeyDown\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_CONSENT_DISAGREE')}}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</transition>\n\t"
	});

	/**
	 * Bitrix OpenLines widget
	 * Form history component (Vue component)
	 *
	 * @package bitrix
	 * @subpackage imopenlines
	 * @copyright 2001-2019 Bitrix
	 */
	ui_vue.BitrixVue.component('bx-livechat-form-history', {
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
	  computed: babelHelpers.objectSpread({}, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  created: function created() {
	    this.fieldEmail = '' + this.widget.user.email;
	  },
	  methods: {
	    formShowed: function formShowed() {
	      if (!im_lib_utils.Utils.platform.isMobile()) {
	        this.$refs.emailInput.focus();
	      }
	    },
	    sendForm: function sendForm() {
	      var email = this.checkEmailField() ? this.fieldEmail : '';

	      if (email) {
	        this.$Bitrix.Application.get().sendForm(FormType.history, {
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
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\" @after-enter=\"formShowed\">\n\t\t\t<div v-if=\"false\" class=\"bx-livechat-alert-box bx-livechat-form-show\" key=\"welcome\">\t\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideForm\"></div>\n\t\t\t\t<div class=\"bx-livechat-alert-form-box\">\n\t\t\t\t\t<h4 class=\"bx-livechat-alert-title bx-livechat-alert-title-sm\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_MAIL_TITLE_NEW')}}</h4>\n\t\t\t\t\t<div class=\"bx-livechat-form-item ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-lg\" ref=\"email\">\n\t\t\t\t\t   <div class=\"ui-ctl-after ui-ctl-icon-mail bx-livechat-form-icon\" :title=\"$Bitrix.Loc.getMessage('BX_LIVECHAT_FIELD_MAIL_TOOLTIP')\"></div>\n\t\t\t\t\t   <input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :placeholder=\"$Bitrix.Loc.getMessage('BX_LIVECHAT_FIELD_MAIL')\" v-model=\"fieldEmail\" ref=\"emailInput\" @blur=\"checkEmailField\" @keydown.enter=\"onFieldEnterPress\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"bx-livechat-btn-box\">\n\t\t\t\t\t\t<button class=\"bx-livechat-btn bx-livechat-btn-success\" @click=\"sendForm\">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_MAIL_BUTTON_NEW')}}</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\n\t\t</transition>\t\n\t"
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
	      if (!im_lib_utils.Utils.platform.isMobile()) {
	        this.$refs.emailInput.focus();
	      }
	    },
	    sendForm: function sendForm() {
	      var name = this.fieldName;
	      var email = this.checkEmailField() ? this.fieldEmail : '';
	      var phone = this.checkPhoneField() ? this.fieldPhone : '';

	      if (name || email || phone) {
	        this.$Bitrix.Application.get().sendForm(FormType.offline, {
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
	ui_vue.BitrixVue.component('bx-livechat-form-vote', {
	  computed: babelHelpers.objectSpread({
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
	      this.$store.commit('widget/common', {
	        showForm: FormType.none
	      });
	      this.$store.commit('widget/dialog', {
	        userVote: vote
	      });
	      this.$Bitrix.Application.get().sendDialogVote(vote);
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
	ui_vue.BitrixVue.component('bx-livechat-form-welcome', {
	  data: function data() {
	    return {
	      fieldName: '',
	      fieldEmail: '',
	      fieldPhone: '',
	      isFullForm: im_lib_utils.Utils.platform.isMobile()
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
	      return ui_vue.BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
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
	      if (!im_lib_utils.Utils.platform.isMobile()) {
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
	        this.$Bitrix.Application.get().sendForm(FormType.welcome, {
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

}((this.window = this.window || {}),BX,window,window,BX.Messenger,window,BX,window,window,BX,BX.Messenger.Provider.Rest,BX,BX,BX.Messenger,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Lib,BX.Messenger.Mixin,BX,BX.Event,BX.Messenger.Const,BX,BX,BX,BX,BX.Messenger.Lib,BX));
//# sourceMappingURL=widget.bundle.js.map
