(function (exports,ui_vue,ui_vue_vuex,im_view_message,ui_fonts_opensans,main_core_events) {
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

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var FormType$1 = Object.freeze({
	  none: 'none',
	  like: 'like',
	  welcome: 'welcome',
	  offline: 'offline',
	  history: 'history'
	});
	var VoteType$1 = Object.freeze({
	  none: 'none',
	  like: 'like',
	  dislike: 'dislike'
	});
	ui_vue.BitrixVue.cloneComponent('bx-imopenlines-message', 'bx-im-view-message', {
	  methods: {
	    checkMessageParamsForForm: function checkMessageParamsForForm() {
	      if (!this.message.params || !this.message.params.IMOL_FORM) {
	        return true;
	      }
	      if (this.message.params.IMOL_FORM === FormType$1.like) {
	        if (parseInt(this.message.params.IMOL_VOTE) === this.widget.dialog.sessionId && this.widget.dialog.userVote === VoteType$1.none) {
	          main_core_events.EventEmitter.emit(WidgetEventType.showForm, {
	            type: FormType$1.like,
	            delayed: true
	          });
	        }
	      }
	    }
	  },
	  created: function created() {
	    this.checkMessageParamsForForm();
	  },
	  computed: _objectSpread({
	    dialogNumber: function dialogNumber() {
	      if (!this.message.params) {
	        return false;
	      }
	      if (!this.message.params.IMOL_SID) {
	        return false;
	      }
	      return this.$Bitrix.Loc.getMessage('IMOL_MESSAGE_DIALOG_ID').replace('#ID#', this.message.params.IMOL_SID);
	    },
	    showMessage: function showMessage() {
	      if (!this.message.params) {
	        return true;
	      }
	      if (this.message.params.IMOL_FORM && this.message.params.IMOL_FORM === 'like') {
	        return false;
	      }
	      return true;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  template: "\n\t\t<div v-if=\"showMessage\" class=\"bx-imopenlines-message\">\n\t\t\t<div v-if=\"dialogNumber\" class=\"bx-imopenlines-message-dialog-number\">{{dialogNumber}}</div>\n\t\t\t#PARENT_TEMPLATE#\n\t\t</div>\n\t"
	});

}((this.window = this.window || {}),BX,BX,window,BX,BX.Event));
//# sourceMappingURL=message.bundle.js.map
