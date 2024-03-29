/* eslint-disable */
this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.Component = this.BX.Salescenter.Component || {};
this.BX.Salescenter.Component.StageBlock = this.BX.Salescenter.Component.StageBlock || {};
(function (exports,ui_vue) {
	'use strict';

	var TimeLineItemContentBlock = {
	  props: ['item'],
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_TIMELINE_ITEM_CONTENT_');
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline-content\">\n\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-text\">\n\t\t\t\t<slot name=\"timeline-content-text\"></slot>\t\t\t\t\n\t\t\t\t<a :href=\"item.url\" v-if=\"item.url\" target=\"_blank\">\n\t\t\t\t\t{{localize.SALESCENTER_TIMELINE_ITEM_CONTENT_VIEW}}\n\t\t\t\t</a>\n\t\t\t</span>\n\t\t</div>\n\t"
	};

	var TimeLineItemBlock = {
	  props: ['item'],
	  components: {
	    'timeline-item-content-block': TimeLineItemContentBlock
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline-item\"\n\t\t\t:class=\"{\n\t\t\t\t'salescenter-app-payment-by-sms-timeline-item-disabled' : item.disabled\n\t\t\t}\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-icon \" \n\t\t\t\t\t:class=\"'salescenter-app-payment-by-sms-item-counter-icon-'+item.icon\"></div>\n\t\t\t</div>\n\t\t\t<component :is=\"'timeline-item-content-block'\" \n\t\t\t\t:item=\"item\">\n\t\t\t\t<template v-slot:timeline-content-text>{{item.content}}</template>\n\t\t\t</component>\n\t\t</div>\n\t"
	};

	var TimeLineItemPaymentBlock = {
	  props: ['item'],
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline-item salescenter-app-payment-by-sms-timeline-item-payment\"\n\t\t\t:class=\"{\n\t\t\t\t'salescenter-app-payment-by-sms-timeline-item-disabled' : item.disabled\n\t\t\t}\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-icon \" \n\t\t\t\t\t:class=\"'salescenter-app-payment-by-sms-item-counter-icon-'+item.icon\"></div>\n\t\t\t</div>\n\t\n\t\t\t<div class=\"salescenter-app-payment-by-sms-timeline-content\">\n\t\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-price\">\n\t\t\t\t\t<span v-html=\"item.sum\"></span>\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"salescenter-app-payment-by-sms-timeline-content-price-cur\"\n\t\t\t\t\t\t:class=\"{ 'salescenter-app-payment-by-sms-timeline-content-price-cur-ruble': item.currencyCode === 'RUB' }\"\n\t\t\t\t\t\tv-html=\"item.currency\">\n\t\t\t\t\t</span>\n\t\t\t\t</span>\n\t\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-text-strong\">\n\t\t\t\t\t{{item.title}}\n\t\t\t\t</span>\n\t\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-text\">\n\t\t\t\t\t{{item.content}}\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var TimeLineItemCustomBlock = {
	  props: ['item'],
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline-item\"\n\t\t\t:class=\"{\n\t\t\t\t'salescenter-app-payment-by-sms-timeline-item-disabled' : item.disabled\n\t\t\t}\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-icon \" \n\t\t\t\t\t:class=\"'salescenter-app-payment-by-sms-item-counter-icon-'+item.icon\"></div>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-timeline-content\" v-html=\"item.content\">\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	exports.TimeLineItemBlock = TimeLineItemBlock;
	exports.TimeLineItemPaymentBlock = TimeLineItemPaymentBlock;
	exports.TimeLineItemContentBlock = TimeLineItemContentBlock;
	exports.TimeLineItemCustomBlock = TimeLineItemCustomBlock;

}((this.BX.Salescenter.Component.StageBlock.TimeLine = this.BX.Salescenter.Component.StageBlock.TimeLine || {}),BX));
//# sourceMappingURL=timeline.bundle.js.map
