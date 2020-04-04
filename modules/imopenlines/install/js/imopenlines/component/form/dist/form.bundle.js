(function (exports,ui_vue,ui_vue_vuex) {
	'use strict';

	/**
	 * Bitrix Messenger
	 * Form Vue component
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */
	BX.Vue.cloneComponent('bx-test-form', 'bx-messenger-message', {
	  data: function data() {
	    return {
	      formValue: ''
	    };
	  },
	  created: function created() {},
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
	    },
	    wasFilled: function wasFilled() {
	      return !!this.message.params.CRM_FORM_VALUE;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    widget: function widget(state) {
	      return state.widget;
	    }
	  })),
	  methods: {
	    onFillForm: function onFillForm() {
	      this.$root.$bitrixRestClient.callMethod('imopenlines.widget.form.fill', {
	        'CRM_FORM_VALUE': this.formValue,
	        'MESSAGE_ID': this.message.id
	      }).then(function (response) {
	        console.log(response);
	      }).catch(function (error) {
	        console.log(error);
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"bx-im-message bx-im-message-without-menu bx-im-message-without-avatar\">\n\t\t\t<div v-if=\"!wasFilled\" class=\"bx-im-message-content\">\n\t\t\t\t<div style=\"margin-bottom: 10px;\">Form component with id {{message.params.CRM_FORM_ID}}</div>\n\t\t\t\t<div style=\"margin-bottom: 10px; display: flex;\">\n\t\t\t\t\t<input type=\"text\" v-model=\"formValue\" style=\"margin-right: 15px;\" />\n\t\t\t\t\t<button @click=\"onFillForm\" class=\"bx-im-textarea-send-button bx-im-textarea-send-button-bright-arrow\" style=\"background-color: rgb(23, 163, 234);\"></button>\t\n\t\t\t\t</div>\n\t\t\t\t<!--#PARENT_TEMPLATE#-->\n\t\t\t</div>\n\t\t\t<div v-else class=\"bx-im-message-content\">\n\t\t\t\tForm was already filled with the value - \"{{message.params.CRM_FORM_VALUE[0]}}\"!\n\t\t\t</div>\n\t\t</div>\n\t"
	});

}((this.window = this.window || {}),BX,BX));
//# sourceMappingURL=form.bundle.js.map
