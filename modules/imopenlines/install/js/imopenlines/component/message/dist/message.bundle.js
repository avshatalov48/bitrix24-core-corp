(function (exports,ui_vue,ui_vue_vuex,im_view_message,main_core_events) {
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
	 * Bitrix Messenger
	 * Message Vue component
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */
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
	          main_core_events.EventEmitter.emit(EventType.requestShowForm, {
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
	  computed: babelHelpers.objectSpread({
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

}((this.window = this.window || {}),BX,BX,window,BX.Event));
//# sourceMappingURL=message.bundle.js.map
