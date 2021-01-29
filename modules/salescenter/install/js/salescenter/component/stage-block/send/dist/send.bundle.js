this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.Component = this.BX.Salescenter.Component || {};
this.BX.Salescenter.Component.StageBlock = this.BX.Salescenter.Component.StageBlock || {};
(function (exports,main_core) {
	'use strict';

	var SendMode = {
	  props: {
	    resend: {
	      type: Boolean,
	      required: true
	    }
	  },
	  methods: {
	    onClick: function onClick(event) {
	      this.$emit('stage-block-send-mode', event);
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-container\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-inline\">\n\t\t\t\t\t<div v-if=\"resend\"\n\t\t\t\t\t\tv-on:click=\"onClick($event)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-lg ui-btn-success ui-btn-round\">".concat(main_core.Loc.getMessage('SALESCENTER_RESEND'), "</div>\n\t\t\t\t\t<div v-else\n\t\t\t\t\t\tv-on:click=\"onClick($event)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-lg ui-btn-success ui-btn-round\">").concat(main_core.Loc.getMessage('SALESCENTER_SEND'), "</div>\n\t\t\t\t\t<slot name=\"stage-block-send-mode-slot\"/>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
	};

	var SendModeEnabled = {
	  props: {
	    resend: {
	      type: Boolean,
	      required: true
	    }
	  },
	  components: {
	    'send-mode-block': SendMode
	  },
	  methods: {
	    openWhatClientSee: function openWhatClientSee(event) {
	      this.$emit('stage-block-send-mode-enabled-see-client', event);
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-mode-enabled-send', event);
	    }
	  },
	  template: "\n\t\t<send-mode-block v-on:stage-block-send-mode=\"onSend($event)\" :resend=\"resend\">\n\t\t\t<template v-slot:stage-block-send-mode-slot>\n\t\t\t\t<div v-on:click=\"openWhatClientSee($event)\"\n\t\t\t\t\tclass=\"salescenter-app-add-item-link\">".concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_WHAT_DOES_CLIENT_SEE'), "</div>\n\t\t\t</template>\n\t\t</send-mode-block>\n\t")
	};

	var SendModeDisabled = {
	  props: {
	    resend: {
	      type: Boolean,
	      required: true
	    }
	  },
	  components: {
	    'send-mode-block': SendMode
	  },
	  methods: {
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-mode-disabled-send', event);
	    }
	  },
	  template: "\n\t\t<send-mode-block v-on:stage-block-send-mode=\"onClick($event)\" :resend=\"resend\"/>\n\t"
	};

	exports.SendMode = SendMode;
	exports.SendModeEnabled = SendModeEnabled;
	exports.SendModeDisabled = SendModeDisabled;

}((this.BX.Salescenter.Component.StageBlock.Send = this.BX.Salescenter.Component.StageBlock.Send || {}),BX));
//# sourceMappingURL=send.bundle.js.map
