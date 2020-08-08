(function (exports) {
	'use strict';

	/**
	 * Bitrix Messenger
	 * Message Vue component
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */
	var FormType = Object.freeze({
	  none: 'none',
	  like: 'like',
	  welcome: 'welcome',
	  offline: 'offline',
	  history: 'history'
	});
	var VoteType = Object.freeze({
	  none: 'none',
	  like: 'like',
	  dislike: 'dislike'
	});
	BX.Vue.cloneComponent('bx-imopenlines-message', 'bx-im-view-message', {
	  methods: {
	    checkFormShow: function checkFormShow() {
	      if (!this.message.params || !this.message.params.IMOL_FORM) {
	        return true;
	      }

	      if (this.message.params.IMOL_FORM === 'welcome') {
	        if (!this.widget.dialog.sessionClose && !this.widget.user.name && !this.widget.user.lastName && !this.widget.user.email && !this.widget.user.phone) {
	          this.$root.$emit('requestShowForm', {
	            type: FormType.welcome,
	            delayed: true
	          });
	        }
	      } else if (this.message.params.IMOL_FORM === 'offline') {
	        if (!this.widget.dialog.sessionClose && !this.widget.user.email) {
	          this.$root.$emit('requestShowForm', {
	            type: FormType.offline,
	            delayed: true
	          });
	        }
	      } else if (this.message.params.IMOL_FORM === 'history-delay') {
	        if (parseInt(this.message.params.IMOL_VOTE) === this.widget.dialog.sessionId && this.widget.dialog.userVote === VoteType.none) {
	          this.$root.$emit('requestShowForm', {
	            type: FormType.like,
	            delayed: true
	          });
	        }
	      }
	    }
	  },
	  created: function created() {
	    this.checkFormShow();
	  },
	  computed: babelHelpers.objectSpread({
	    dialogNumber: function dialogNumber() {
	      if (!this.message.params) {
	        return false;
	      }

	      if (!this.message.params.IMOL_SID) {
	        return false;
	      }

	      return this.localize.IMOL_MESSAGE_DIALOG_ID.replace('#ID#', this.message.params.IMOL_SID);
	    },
	    localize: function localize() {
	      return Object.freeze(Object.assign({}, this.parentLocalize, BX.Vue.getFilteredPhrases('IMOL_MESSAGE_', this.$root.$bitrixMessages)));
	    },
	    showMessage: function showMessage() {
	      if (!this.message.params) {
	        return true;
	      }

	      if (this.message.params.IMOL_FORM && this.message.params.IMOL_FORM === 'history-delay' // TODO change after release to vote
	      ) {
	          return false;
	        }

	      return true;
	    }
	  }, BX.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  template: "\n\t\t<div v-if=\"showMessage\" class=\"bx-imopenlines-message\">\n\t\t\t<div v-if=\"dialogNumber\" class=\"bx-imopenlines-message-dialog-number\">{{dialogNumber}}</div>\n\t\t\t#PARENT_TEMPLATE#\n\t\t</div>\n\t"
	});

}((this.window = this.window || {})));
//# sourceMappingURL=message.bundle.js.map
