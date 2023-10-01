this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.PaymentPay = this.BX.Salescenter.PaymentPay || {};
(function (exports,sale_paymentPay_lib,sale_paymentPay_mixins_application,salescenter_paymentPay_userConsent,salescenter_paymentPay_backendProvider,sale_paymentPay_mixins_paymentSystem,main_core,main_core_events,sale_paymentPay_const,ui_vue) {
	'use strict';

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-application-pay_system', {
	  props: {
	    options: Object
	  },
	  mixins: [sale_paymentPay_mixins_application.MixinMethods],
	  data: function data() {
	    var settings = new sale_paymentPay_lib.Settings(this.options);
	    return {
	      stageType: sale_paymentPay_const.StageType,
	      stages: this.prepareParamsStages(),
	      stage: this.setStageType(),
	      loading: false,
	      paymentProcess: this.prepareParamsPaymentProcess(settings),
	      consent: this.prepareUserConsentSettings(settings)
	    };
	  },
	  created: function created() {
	    this.initPayment();
	    this.initUserConsent();
	    this.subscribeToGlobalEvents();
	  },
	  methods: {
	    initUserConsent: function initUserConsent() {
	      this.userConsentManager = new salescenter_paymentPay_userConsent.UserConsent({
	        containerId: this.consent.containerId,
	        accepted: this.consent.accepted,
	        eventName: this.consent.eventName
	      });
	    },
	    initBackendProvider: function initBackendProvider() {
	      this.backendProvider = new salescenter_paymentPay_backendProvider.BackendProvider({
	        returnUrl: this.paymentProcess.returnUrl,
	        orderId: this.paymentProcess.orderId,
	        paymentId: this.paymentProcess.paymentId,
	        accessCode: this.paymentProcess.accessCode
	      });
	    },
	    startPayment: function startPayment(paySystemId) {
	      var _this = this;
	      if (this.loading) {
	        return false;
	      }
	      this.userConsentManager.askUserToPerform(function () {
	        _this.loading = true;
	        _this.stages.paySystemList.selectedPaySystem = paySystemId;
	        _this.backendProvider.paySystemId = paySystemId;
	        _this.paymentProcess.start();
	      });
	    },
	    prepareParamsStages: function prepareParamsStages() {
	      var settings = new sale_paymentPay_lib.Settings(this.options);
	      return {
	        paySystemList: {
	          paySystems: settings.get('app.paySystems', []),
	          selectedPaySystem: null,
	          title: settings.get('app.title')
	        },
	        paySystemErrors: {
	          errors: []
	        },
	        paySystemResult: {
	          html: null,
	          fields: null
	        }
	      };
	    },
	    setStageType: function setStageType() {
	      return sale_paymentPay_const.StageType.list;
	    },
	    prepareUserConsentSettings: function prepareUserConsentSettings(settings) {
	      return {
	        id: settings.get('consent.id'),
	        title: settings.get('consent.title'),
	        eventName: settings.get('consent.eventName'),
	        accepted: settings.get('consent.accepted'),
	        containerId: settings.get('consent.containerId')
	      };
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"salescenter-payment-pay-app\">\n\t\t\t<salescenter-payment_pay-components-payment_system-pay_system_list\n\t\t\t\tv-if=\"stage === stageType.list\"\n\t\t\t\t:paySystems=\"stages.paySystemList.paySystems\"\n\t\t\t\t:selectedPaySystem=\"stages.paySystemList.selectedPaySystem\"\n\t\t\t\t:loading=\"loading\"\n                :title=\"stages.paySystemList.title\"\n\t\t\t\t@start-payment=\"startPayment($event)\">\n\t\t\t\t<template v-slot:user-consent>\n\t\t\t\t\t<salescenter-payment_pay-components-payment_system-user_consent\n\t\t\t\t\t\t:id=\"consent.id\"\n\t\t\t\t\t\t:title=\"consent.title\"\n\t\t\t\t\t\t:checked=\"consent.accepted\"\n\t\t\t\t\t\t:submitEventName=\"consent.eventName\"/>\n\t\t\t\t</template>\n\t\t\t</salescenter-payment_pay-components-payment_system-pay_system_list>\n\t\t\t<salescenter-payment_pay-components-payment_system-error_box\n\t\t\t\tv-if=\"stage === stageType.errors\"\n\t\t\t\t:errors=\"stages.paySystemErrors.errors\">\n\t\t\t\t<salescenter-payment_pay-components-payment_system-reset_panel @reset=\"resetView()\"/>\n\t\t\t</salescenter-payment_pay-components-payment_system-error_box>\n\t\t\t<salescenter-payment_pay-components-payment_system-pay_system_result\n\t\t\t\tv-if=\"stage === stageType.result\"\n\t\t\t\t:html=\"stages.paySystemResult.html\"\n\t\t\t\t:fields=\"stages.paySystemResult.fields\">\n\t\t\t\t<salescenter-payment_pay-components-payment_system-reset_panel @reset=\"resetView()\"/>\n\t\t\t</salescenter-payment_pay-components-payment_system-pay_system_result>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-application-pay_system_info', {
	  props: {
	    paySystems: {
	      type: Array,
	      "default": [],
	      required: false
	    }
	  },
	  data: function data() {
	    return {
	      selectedPaySystem: null
	    };
	  },
	  computed: {
	    localize: function localize() {
	      return Object.freeze(ui_vue.BitrixVue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_'));
	    },
	    selectedName: function selectedName() {
	      return this.selectedPaySystem ? this.selectedPaySystem.NAME : '';
	    },
	    selectedDescription: function selectedDescription() {
	      return this.selectedPaySystem ? BX.util.htmlspecialchars(this.selectedPaySystem.DESCRIPTION) : '';
	    }
	  },
	  methods: {
	    showInfo: function showInfo(paySystem) {
	      this.selectedPaySystem = paySystem;
	    },
	    logoStyle: function logoStyle(paySystem) {
	      var defaultLogo = '/bitrix/js/salescenter/payment-pay/payment-method/images/default_logo.png';
	      var src = paySystem.LOGOTIP || defaultLogo;
	      return "background-image: url(\"".concat(BX.util.htmlspecialchars(src), "\")");
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"order-payment-method-list\">\n\t\t\t\t<slot>\n\t\t\t\t\t<div class=\"order-pay-method-item-container info-mode\" v-for=\"paySystem in paySystems\">\n\t\t\t\t\t\t<div class=\"order-pay-method-item-logo-block\">\n\t\t\t\t\t\t\t<div class=\"order-pay-method-logo\" :style=\"logoStyle(paySystem)\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"order-pay-method-text-block\">\n\t\t\t\t\t\t\t<div class=\"order-pay-method-text\">{{ paySystem.NAME }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"btn info-mode\" @click=\"showInfo(paySystem)\">{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_10 }}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</slot>\n\t\t\t</div>\n\t\t\t<div class=\"order-payment-method-description\" v-if=\"selectedPaySystem\">\n\t\t\t\t<div class=\"order-payment-method-description-title\">{{ selectedName }}</div>\n\t\t\t\t<div class=\"order-payment-method-description-text\" v-html=\"selectedDescription\"></div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-application-payment', {
	  props: {
	    options: Object
	  },
	  mixins: [sale_paymentPay_mixins_application.MixinMethods],
	  data: function data() {
	    var settings = new sale_paymentPay_lib.Settings(this.options);
	    return {
	      stageType: sale_paymentPay_const.StageType,
	      stages: this.prepareParamsStages(),
	      stage: this.setStageType(),
	      loading: false,
	      paymentProcess: this.prepareParamsPaymentProcess(settings),
	      consent: this.prepareUserConsentSettings(settings)
	    };
	  },
	  created: function created() {
	    this.initPayment();
	    this.initUserConsent();
	    this.subscribeToGlobalEvents();
	  },
	  methods: {
	    initUserConsent: function initUserConsent() {
	      this.userConsentManager = new salescenter_paymentPay_userConsent.UserConsent({
	        containerId: this.consent.containerId,
	        accepted: this.consent.accepted,
	        eventName: this.consent.eventName
	      });
	    },
	    initBackendProvider: function initBackendProvider() {
	      this.backendProvider = new salescenter_paymentPay_backendProvider.BackendProvider({
	        returnUrl: this.paymentProcess.returnUrl,
	        orderId: this.paymentProcess.orderId,
	        paymentId: this.paymentProcess.paymentId,
	        accessCode: this.paymentProcess.accessCode
	      });
	    },
	    startPayment: function startPayment(paySystemId) {
	      var _this = this;
	      if (this.loading) {
	        return false;
	      }
	      this.userConsentManager.askUserToPerform(function () {
	        _this.loading = true;
	        _this.backendProvider.paySystemId = paySystemId;
	        _this.paymentProcess.start();
	      });
	    },
	    prepareParamsStages: function prepareParamsStages() {
	      var settings = new sale_paymentPay_lib.Settings(this.options);
	      return {
	        paymentInfo: {
	          paySystem: settings.get('app.paySystems', [])[0],
	          title: settings.get('app.title'),
	          sum: settings.get('payment.sumFormatted'),
	          paid: settings.get('payment.paid'),
	          checks: settings.get('payment.checks', [])
	        },
	        paySystemErrors: {
	          errors: []
	        },
	        paySystemResult: {
	          html: null,
	          fields: null
	        }
	      };
	    },
	    setStageType: function setStageType() {
	      return sale_paymentPay_const.StageType.paymentInfo;
	    },
	    prepareUserConsentSettings: function prepareUserConsentSettings(settings) {
	      return {
	        id: settings.get('consent.id'),
	        title: settings.get('consent.title'),
	        eventName: settings.get('consent.eventName'),
	        accepted: settings.get('consent.accepted'),
	        containerId: settings.get('consent.containerId')
	      };
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"salescenter-payment-pay-app\">\n\t\t\t<salescenter-payment_pay-components-payment_system-payment_info\n                v-if=\"stage === stageType.paymentInfo\"\n\t\t\t\t:paySystem=\"stages.paymentInfo.paySystem\"\n                :title=\"stages.paymentInfo.title\"\n\t\t\t\t:sum=\"stages.paymentInfo.sum\"\n\t\t\t\t:paid=\"stages.paymentInfo.paid\"\n\t\t\t\t:loading=\"loading\"\n\t\t\t\t:checks=\"stages.paymentInfo.checks\"\n                @start-payment=\"startPayment($event)\">\n\t\t\t\t<template v-slot:user-consent>\n\t\t\t\t\t<salescenter-payment_pay-components-payment_system-user_consent\n\t\t\t\t\t\t:id=\"consent.id\"\n\t\t\t\t\t\t:title=\"consent.title\"\n\t\t\t\t\t\t:checked=\"consent.accepted\"\n\t\t\t\t\t\t:submitEventName=\"consent.eventName\"/>\n\t\t\t\t</template>\n\t\t\t</salescenter-payment_pay-components-payment_system-payment_info>\n            <salescenter-payment_pay-components-payment_system-error_box\n                v-if=\"stage === stageType.errors\"\n                :errors=\"stages.paySystemErrors.errors\">\n            \t<salescenter-payment_pay-components-payment_system-reset_panel @reset=\"resetView()\"/>\n            </salescenter-payment_pay-components-payment_system-error_box>\n            <salescenter-payment_pay-components-payment_system-pay_system_result\n                v-if=\"stage === stageType.result\"\n                :html=\"stages.paySystemResult.html\"\n                :fields=\"stages.paySystemResult.fields\">\n            \t<salescenter-payment_pay-components-payment_system-reset_panel @reset=\"resetView()\"/>\n            </salescenter-payment_pay-components-payment_system-pay_system_result>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-payment_info-button', {
	  props: {
	    loading: {
	      type: Boolean,
	      "default": false,
	      required: false
	    }
	  },
	  mixins: [sale_paymentPay_mixins_paymentSystem.MixinPaymentInfoButton],
	  computed: {
	    classes: function classes() {
	      var classes = ['landing-block-node-button', 'text-uppercase', 'btn', 'btn-xl', 'pr-7', 'pl-7', 'u-btn-primary', 'g-font-weight-700', 'g-font-size-12', 'g-rounded-50'];
	      if (this.loading) {
	        classes.push('loading');
	      }
	      return classes;
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<button :class=\"classes\" @click=\"onClick($event)\">\n\t\t\t<slot></slot>\n\t\t</button>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-payment_info-pay_system_small_card', {
	  props: {
	    logo: String,
	    name: String
	  },
	  template: "\n\t\t<div class=\"order-payment-operator\">\n\t\t\t<img :src=\"logo\" :alt=\"name\" v-if=\"logo\">\n\t\t\t<div class=\"order-payment-pay-system-name\" v-else>{{ name }}</div>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-button', {
	  props: {
	    loading: {
	      type: Boolean,
	      "default": false,
	      required: false
	    }
	  },
	  mixins: [sale_paymentPay_mixins_paymentSystem.MixinButton],
	  computed: {
	    classes: function classes() {
	      return {
	        'order-payment-method-item-button': true,
	        'btn': true,
	        'btn-primary': true,
	        'rounded-pill': true,
	        'pay-mode': true,
	        'order-payment-loader': this.loading
	      };
	    },
	    buttonClasses: function buttonClasses() {
	      return {
	        'loading-button-text': this.loading
	      };
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div :class=\"classes\" @click=\"onClick($event)\">\n\t\t\t<span :class=\"buttonClasses\"><slot></slot></span>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-check', {
	  props: {
	    status: {
	      type: String,
	      "default": '',
	      required: false
	    },
	    link: {
	      type: String,
	      "default": '',
	      required: false
	    },
	    title: {
	      type: String,
	      required: true
	    }
	  },
	  mixins: [sale_paymentPay_mixins_paymentSystem.MixinCheck],
	  // language=Vue
	  template: "\n\t\t<div class=\"mb-2\" :class=\"{'check-print': processing}\">\n\t\t\t<a :href=\"link\" target=\"_blank\" class=\"check-link\" v-if=\"downloadable\">{{ title }}</a>\n\t\t\t<span v-else>{{ title }}</span>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-error_box', {
	  props: {
	    errors: Array
	  },
	  computed: {
	    localize: function localize() {
	      return Object.freeze(ui_vue.BitrixVue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_'));
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"alert alert-danger\">\n\t\t\t\t<slot name=\"errors-header\">\n\t\t\t\t\t<div>{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_8 }}</div>\n\t\t\t\t</slot>\n\t\t\t\t<slot name=\"errors-footer\">\n\t\t\t\t\t<div>{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_9 }}</div>\n\t\t\t\t</slot>\n\t\t\t</div>\n\t\t\t<slot></slot>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-pay_system_list', {
	  props: {
	    paySystems: {
	      type: Array,
	      "default": [],
	      required: false
	    },
	    selectedPaySystem: {
	      type: Number,
	      "default": null,
	      required: false
	    },
	    loading: {
	      type: Boolean,
	      "default": false,
	      required: false
	    },
	    title: {
	      type: String,
	      "default": null,
	      required: false
	    }
	  },
	  mixins: [sale_paymentPay_mixins_paymentSystem.MixinPaySystemList],
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"page-section-title\" v-if=\"title\">{{ title }}</div>\n\t\t\t<div class=\"order-payment-method-list\">\n\t\t\t\t<slot>\n\t\t\t\t\t<salescenter-payment_pay-components-payment_system-pay_system_row \n\t\t\t\t\t\tv-for=\"paySystem in paySystems\"\n\t\t\t\t\t\t:loading=\"isItemLoading(paySystem.ID)\"\n\t\t\t\t\t\t:name=\"paySystem.NAME\"\n\t\t\t\t\t\t:logo=\"paySystem.LOGOTIP\"\n\t\t\t\t\t\t:id=\"paySystem.ID\"\n\t\t\t\t\t\t@click=\"startPayment($event)\"\n\t\t\t\t\t/>\n\t\t\t\t</slot>\n\t\t\t</div>\n            <slot name=\"user-consent\"></slot>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-pay_system_result', {
	  props: {
	    html: {
	      type: String,
	      "default": null,
	      required: false
	    },
	    fields: {
	      type: Object,
	      "default": null,
	      required: false
	    }
	  },
	  computed: {
	    localize: function localize() {
	      return Object.freeze(ui_vue.BitrixVue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_'));
	    }
	  },
	  mounted: function mounted() {
	    if (this.html) {
	      BX.html(this.$refs.paySystemResultTemplate, this.html);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<template v-if=\"html\">\n\t\t\t\t<div ref=\"paySystemResultTemplate\"></div>\n\t\t\t\t<slot></slot>\n\t\t\t</template>\n\t\t\t<template v-else>\n\t\t\t\t<div class=\"checkout-basket-section\">\n\t\t\t\t\t<div class=\"page-section-title\">{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_1 }}</div>\n\t\t\t\t\t<div class=\"checkout-basket-personal-order-info\" v-if=\"fields\">\n\t\t\t\t\t\t<div class=\"checkout-basket-personal-order-info-item\" v-if=\"fields.SUM_WITH_CURRENCY\">\n\t\t\t\t\t\t\t<span>{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_2 }}</span> <strong v-html=\"fields.SUM_WITH_CURRENCY\"></strong>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"checkout-basket-personal-order-info-item\" v-if=\"fields.PAY_SYSTEM_NAME\">\n\t\t\t\t\t\t\t<span>{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_3 }}</span> <strong>{{ fields.PAY_SYSTEM_NAME }}</strong>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<slot></slot>\n\t\t\t</template>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-pay_system_row', {
	  props: {
	    loading: Boolean,
	    name: String,
	    logo: String,
	    id: String | Number
	  },
	  computed: {
	    localize: function localize() {
	      return Object.freeze(ui_vue.BitrixVue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_'));
	    }
	  },
	  mixins: [sale_paymentPay_mixins_paymentSystem.MixinPaySystemRow],
	  // language=Vue
	  template: "\n\t\t<div class=\"order-pay-method-item-container pay-mode\" @click=\"onClick()\">\n\t\t\t<div class=\"order-pay-method-item-logo-block\">\n\t\t\t\t<div class=\"order-pay-method-logo\" :style=\"logoStyle\"></div>\n\t\t\t</div>\n\t\t\t<div class=\"order-pay-method-text-block\">\n\t\t\t\t<div class=\"order-pay-method-text\">{{ name }}</div>\n\t\t\t</div>\n\t\t\t<salescenter-payment_pay-components-payment_system-button :loading=\"loading\">\n\t\t\t\t{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_4 }}\n\t\t\t</salescenter-payment_pay-components-payment_system-button>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-payment_info', {
	  props: {
	    paySystem: Object,
	    title: String,
	    sum: String,
	    loading: Boolean,
	    paid: Boolean,
	    checks: Array
	  },
	  computed: {
	    localize: function localize() {
	      return Object.freeze(ui_vue.BitrixVue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_'));
	    },
	    totalSum: function totalSum() {
	      return this.localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_11.replace('#SUM#', this.sum);
	    }
	  },
	  mixins: [sale_paymentPay_mixins_paymentSystem.MixinPaymentInfo],
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"order-payment-title\" v-if=\"title\">{{ title }}</div>\n\t\t\t<div class=\"order-payment-inner d-flex align-items-center justify-content-between\">\n\t\t\t\t<salescenter-payment_pay-components-payment_system-payment_info-pay_system_small_card :name=\"paySystem.NAME\" :logo=\"paySystem.LOGOTIP\"/>\n            \t<div class=\"order-payment-status d-flex align-items-center\" v-if=\"paid\">\n                \t<div class=\"order-payment-status-ok\"></div>\n                \t<div>{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_5 }}</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"order-payment-price\" v-html=\"totalSum\"></div>\n\t\t\t</div>\n\t\t\t<hr v-if=\"checks.length > 0\">\n\t\t\t<salescenter-payment_pay-components-payment_system-check \n\t\t\t\tv-for=\"check in checks\" \n\t\t\t\t:title=\"check.title\" \n\t\t\t\t:link=\"check.link\" \n\t\t\t\t:status=\"check.status\"/>\n\t\t\t<hr v-if=\"!paid\">\n            <slot name=\"user-consent\" v-if=\"!paid\"></slot>\n\t\t\t<div class=\"order-payment-buttons-container\" v-if=\"!paid\">\n\t\t\t\t<salescenter-payment_pay-components-payment_system-payment_info-button\n\t\t\t\t\t:loading=\"loading\"\n\t\t\t\t\t@click=\"onClick()\">\n\t\t\t\t\t{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_4 }}\n\t\t\t\t</salescenter-payment_pay-components-payment_system-payment_info-button>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-reset_panel', {
	  mixins: [sale_paymentPay_mixins_paymentSystem.MixinResetPanel],
	  computed: {
	    localize: function localize() {
	      return Object.freeze(ui_vue.BitrixVue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_'));
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"order-payment-buttons-container\">\n\t\t\t<div class=\"order-basket-section-description py-3\">\n\t\t\t\t{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_6 }}\n\t\t\t</div>\n\t\t\t<div class=\"order-basket-section-another-payment-button\">\n\t\t\t\t<salescenter-payment_pay-components-payment_system-button @click=\"reset()\">\n\t\t\t\t\t{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_7 }}\n\t\t\t\t</salescenter-payment_pay-components-payment_system-button>\n\t\t\t</div>\n\t\t</div>\t\n\t"
	});

	var _templateObject;
	ui_vue.BitrixVue.component('salescenter-payment_pay-components-payment_system-user_consent', {
	  props: {
	    id: {
	      type: Number | String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    submitEventName: {
	      type: String,
	      required: true
	    },
	    checked: {
	      type: Boolean,
	      "default": false,
	      required: false
	    }
	  },
	  methods: {
	    loadBlockHtml: function loadBlockHtml() {
	      var _this = this;
	      var data = {
	        fields: {
	          id: this.id,
	          title: this.title,
	          isChecked: this.checked ? 'Y' : 'N',
	          submitEventName: this.submitEventName
	        }
	      };
	      main_core.ajax.runComponentAction('bitrix:salescenter.payment.pay', 'userConsentRequest', {
	        mode: 'ajax',
	        data: data
	      }).then(function (response) {
	        if (!main_core.Type.isPlainObject(response.data) || !main_core.Type.isStringFilled(response.data.html) || !BX.UserConsent) {
	          return;
	        }
	        var html, wrapper, control;
	        html = response.data.html;
	        wrapper = _this.$refs.consentDiv;
	        wrapper.appendChild(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), html));
	        control = BX.UserConsent.load(wrapper);
	        main_core_events.EventEmitter.subscribe(control, BX.UserConsent.events.accepted, function (event) {
	          main_core_events.EventEmitter.emit(sale_paymentPay_const.EventType.consent.accepted);
	        });
	        main_core_events.EventEmitter.subscribe(control, BX.UserConsent.events.refused, function (event) {
	          main_core_events.EventEmitter.emit(sale_paymentPay_const.EventType.consent.refused);
	        });
	      });
	    }
	  },
	  mounted: function mounted() {
	    this.loadBlockHtml();
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n        \t<div ref=\"consentDiv\"/>\n\t\t</div>\n\t"
	});

}((this.BX.Salescenter.PaymentPay.Components = this.BX.Salescenter.PaymentPay.Components || {}),BX.Sale.PaymentPay.Lib,BX.Sale.PaymentPay.Mixins.Application,BX.Salescenter.PaymentPay,BX.Salescenter.PaymentPay,BX.Sale.PaymentPay.Mixins.PaymentSystem,BX,BX.Event,BX.Sale.PaymentPay.Const,BX));
//# sourceMappingURL=registry.bundle.js.map
