this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Delivery = this.BX.Crm.Delivery || {};
(function (exports,ui_vue) {
	'use strict';

	var UseLocalize = {
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('TIMELINE_DELIVERY_TAXI_');
	    }
	  }
	};

	var UseAuthor = {
	  computed: {
	    author: function author() {
	      return this.data.AUTHOR ? this.data.AUTHOR : null;
	    }
	  }
	};

	var UseHistoryItem = {
	  mixins: [UseLocalize, UseAuthor],
	  props: {
	    self: {
	      required: true,
	      type: Object
	    }
	  },
	  computed: {
	    data: function data() {
	      return this.self._data;
	    },
	    fields: function fields() {
	      return this.data.FIELDS ? this.data.FIELDS : null;
	    },
	    createdAt: function createdAt() {
	      return this.self instanceof BX.CrmHistoryItem ? this.self.formatTime(this.self.getCreatedTime()) : '';
	    }
	  }
	};

	var AuthorComponent = {
	  props: {
	    author: {
	      required: true,
	      type: Object
	    }
	  },
	  computed: {
	    linkStyle: function linkStyle() {
	      if (!this.author.IMAGE_URL) {
	        return {};
	      }

	      return {
	        'background-image': 'url(' + encodeURI(this.author.IMAGE_URL) + ')',
	        'background-size': '21px'
	      };
	    }
	  },
	  template: "\n\t\t<a\n\t\t\tv-if=\"author.SHOW_URL\"\n\t\t\t:href=\"author.SHOW_URL\"\n\t\t\t:style=\"linkStyle\"\n\t\t\tclass=\"crm-entity-stream-content-detail-employee\">\t\n\t\t</a>\n\t"
	};

	var HistoryItem = {
	  components: {
	    'author': AuthorComponent
	  },
	  props: {
	    author: {
	      required: false,
	      type: Object
	    },
	    createdAt: {
	      required: false,
	      type: String
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-new\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi\"></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-title\">\n\t\t\t\t\t\t\t<slot name=\"title\"></slot>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<slot name=\"status\"></slot>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">\n\t\t\t\t\t\t\t<span v-html=\"createdAt\"></span>\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail\">\n\t\t\t\t\t\t<slot></slot>\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\"></author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var LogoComponent = {
	  props: {
	    logo: {
	      required: true,
	      type: String
	    }
	  },
	  template: "\n\t\t<div\n\t\t\tclass=\"crm-entity-stream-content-delivery-title-logo\"\n\t\t\t:style=\"'background: url(' + logo +') center no-repeat;'\"\n\t\t></div>\n\t"
	};

	var InfoComponent = {
	  props: {
	    name: {
	      required: false,
	      type: String
	    },
	    method: {
	      required: false,
	      type: String
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-title-info\">\n\t\t\t<div v-if=\"name\" class=\"crm-entity-stream-content-delivery-title-name\">\n\t\t\t\t{{name}}\n\t\t\t</div>\n\t\t\t<div v-if=\"method\" class=\"crm-entity-stream-content-delivery-title-param\">\n\t\t\t\t{{method}}\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var CarLogoInfo = {
	  props: {
	    logo: {
	      required: false
	    },
	    serviceName: {
	      required: false
	    },
	    methodName: {
	      required: false
	    }
	  },
	  components: {
	    'logo': LogoComponent,
	    'info': InfoComponent
	  },
	  mixins: [UseLocalize],
	  template: "\n\t\t<div class=\"crm-entity-stream-content-delivery-row\">\n\t\t\t<div class=\"crm-entity-stream-content-delivery-title\">\n\t\t\t\t<div class=\"crm-entity-stream-content-delivery-icon crm-entity-stream-content-delivery-icon--car\"></div>\n\t\t\t\t<div class=\"crm-entity-stream-content-delivery-title-contnet\">\n\t\t\t\t\t<logo v-if=\"logo\" :logo=\"logo\"></logo>\n\t\t\t\t\t<info\n\t\t\t\t\t\tv-if=\"serviceName || methodName\"\n\t\t\t\t\t\t:name=\"serviceName\"\n\t\t\t\t\t\t:method=\"methodName\"\n\t\t\t\t\t></info>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<slot name=\"bottom\"></slot>\n\t\t</div>\n\t"
	};

	var callrequest = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  computed: {
	    isExpectedPriceReceived: function isExpectedPriceReceived() {
	      return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
	    }
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_SEND_REQUEST_HISTORY_TITLE}}\t\t\t\t\n\t\t\t</template>\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t>\n\t\t\t\t\t<template v-slot:bottom>\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-description\">\n\t\t\t\t\t\t\t<template v-if=\"isExpectedPriceReceived\">\n\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_ESTIMATED_DELIVERY_PRICE_RECEIVED}}:\n\t\t\t\t\t\t\t\t<span v-html=\"fields.EXPECTED_PRICE_DELIVERY\"></span>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED_FULL}}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t</car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var estimationrequest = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem
	  },
	  computed: {
	    isExpectedPriceReceived: function isExpectedPriceReceived() {
	      return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
	    }
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_PRICE_CALCULATION}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span v-if=\"!isExpectedPriceReceived\" class=\"crm-entity-stream-content-event-missing\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description crm-delivery-taxi-caption\">\n\t\t\t\t\t<template v-if=\"isExpectedPriceReceived\">\n\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_ESTIMATED_DELIVERY_PRICE_RECEIVED}}:\n\t\t\t\t\t\t<span v-html=\"fields.EXPECTED_PRICE_DELIVERY\"></span>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED_FULL}}\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-description\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box-label\">{{localize.TIMELINE_DELIVERY_TAXI_ADDRESS_FROM}}</div>\n\t\t\t\t\t\t<span v-html=\"fields.ADDRESS_FROM\"></span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-delivery-order-box-label\">{{localize.TIMELINE_DELIVERY_TAXI_ADDRESS_TO}}</div>\n\t\t\t\t\t\t<span v-html=\"fields.ADDRESS_TO\"></span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var cancelledbymanager = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLED_BY_MANAGER}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-process\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLATION}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t\t<div v-if=\"fields.IS_PAID\" class=\"crm-entity-stream-content-detail-notice\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_PAID_CANCELLATION}}\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var cancelledbydriver = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLED_BY_DRIVER}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-process\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCELLATION}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var performernotfound = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_PERFORMER_NOT_FOUND}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-missing\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var returnedfinish = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_RETURNED_FINISH_TITLE}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-process\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_RETURN}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<car-logo-info\n\t\t\t\t\t:logo=\"fields.DELIVERY_SYSTEM_LOGO\"\n\t\t\t\t\t:service-name=\"fields.DELIVERY_SYSTEM_NAME\"\n\t\t\t\t\t:method-name=\"fields.DELIVERY_METHOD\"\n\t\t\t\t></car-logo-info>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	var smsproviderissue = ui_vue.Vue.extend({
	  mixins: [UseHistoryItem],
	  components: {
	    'history-item': HistoryItem,
	    'car-logo-info': CarLogoInfo
	  },
	  methods: {
	    setupSmsProvider: function setupSmsProvider() {
	      if (!this.fields.SMS_PROVIDER_SETUP_LINK) {
	        return;
	      }

	      BX.SidePanel.Instance.open(this.fields.SMS_PROVIDER_SETUP_LINK, {
	        cacheable: false
	      });
	    }
	  },
	  template: "\n\t\t<history-item :author=\"author\" :createdAt=\"createdAt\">\n\t\t\t<template v-slot:title>\n\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_ISSUE_TITLE}}\n\t\t\t</template>\n\t\t\t<template v-slot:status>\n\t\t\t\t<span class=\"crm-entity-stream-content-event-missing\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_ERROR}}\n\t\t\t\t</span>\n\t\t\t</template>\t\t\t\n\t\t\t<template v-slot:default>\n\t\t\t\t<div class=\"crm-entity-stream-content-delivery-description\">\n\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_ISSUE_DETAIL}}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-content-detail-notice\">\n\t\t\t\t\t<a v-if=\"fields.SMS_PROVIDER_SETUP_LINK\" @click=\"setupSmsProvider\" href=\"#\" class=\"crm-entity-stream-content-detail-target\">\n\t\t\t\t\t\t{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_SMS_PROVIDER_SETUP}}\t\t\t\t\t\t\t\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</history-item>\n\t"
	});

	exports.CallRequest = callrequest;
	exports.EstimationRequest = estimationrequest;
	exports.CancelledByManager = cancelledbymanager;
	exports.CancelledByDriver = cancelledbydriver;
	exports.PerformerNotFound = performernotfound;
	exports.ReturnedFinish = returnedfinish;
	exports.SmsProviderIssue = smsproviderissue;

}((this.BX.Crm.Delivery.Taxi = this.BX.Crm.Delivery.Taxi || {}),BX));
//# sourceMappingURL=taxi.bundle.js.map
