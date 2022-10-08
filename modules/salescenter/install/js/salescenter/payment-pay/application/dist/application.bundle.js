this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
(function (exports,sale_paymentPay_lib,salescenter_paymentPay_userConsent,salescenter_paymentPay_mixins,salescenter_paymentPay_backendProvider,ui_vue,main_core,main_core_events,sale_paymentPay_const) {
	'use strict';

	var ErrorBox = {
	  props: {
	    errors: Array
	  },
	  computed: {
	    flattenErrorsList: function flattenErrorsList() {
	      var list = [];
	      this.errors.map(function (errorType) {
	        errorType.map(function (error) {
	          return list.push(error);
	        });
	      });
	      return list;
	    },
	    loc: function loc() {
	      return ui_vue.BitrixVue.getFilteredPhrases('SPP_');
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"alert alert-danger\">\n\t\t\t\t<slot name=\"errors-header\">\n\t\t\t\t\t<div>{{ loc.SPP_INITIATE_PAY_ERROR_TEXT_HEADER }}</div>\n\t\t\t\t</slot>\n\t\t\t\t<div v-for=\"error in flattenErrorsList\">{{ error }}</div>\n\t\t\t\t<slot name=\"errors-footer\">\n\t\t\t\t\t<div>{{ loc.SPP_INITIATE_PAY_ERROR_TEXT_FOOTER }}</div>\n\t\t\t\t</slot>\n\t\t\t</div>\n\t\t\t<slot></slot>\n\t\t</div>\n\t"
	};

	var Button = {
	  props: {
	    loading: {
	      type: Boolean,
	      "default": false,
	      required: false
	    }
	  },
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
	    }
	  },
	  methods: {
	    onClick: function onClick(event) {
	      this.$emit('click', event);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div :class=\"classes\" @click=\"onClick($event)\">\n\t\t\t<slot></slot>\n\t\t</div>\n\t"
	};

	var ResetPanel = {
	  components: {
	    Button: Button
	  },
	  methods: {
	    reset: function reset() {
	      this.$emit('reset');
	    }
	  },
	  computed: {
	    loc: function loc() {
	      return ui_vue.BitrixVue.getFilteredPhrases('SPP_');
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"order-payment-buttons-container\">\n\t\t\t<div class=\"order-basket-section-description py-3\">\n\t\t\t\t{{ loc.SPP_EMPTY_TEMPLATE_FOOTER }}\n\t\t\t</div>\n\t\t\t<Button @click=\"reset()\">\n\t\t\t\t{{ loc.SPP_PAY_RELOAD_BUTTON_NEW }}\n\t\t\t</Button>\n\t\t</div>\t\n\t"
	};

	var PaySystemResult = {
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
	    loc: function loc() {
	      return ui_vue.BitrixVue.getFilteredPhrases('SPP_');
	    }
	  },
	  mounted: function mounted() {
	    if (this.html) {
	      BX.html(this.$refs.paySystemResultTemplate, this.html);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<template v-if=\"html\">\n\t\t\t\t<div ref=\"paySystemResultTemplate\"></div>\n\t\t\t\t<slot></slot>\n\t\t\t</template>\n\t\t\t<template v-else>\n\t\t\t\t<div class=\"checkout-basket-section\">\n\t\t\t\t\t<div class=\"page-section-title\">{{ loc.SPP_EMPTY_TEMPLATE_TITLE }}</div>\n\t\t\t\t\t<div class=\"checkout-basket-personal-order-info\" v-if=\"fields\">\n\t\t\t\t\t\t<div class=\"checkout-basket-personal-order-info-item\" v-if=\"fields.SUM_WITH_CURRENCY\">\n\t\t\t\t\t\t\t<span>{{ loc.SPP_EMPTY_TEMPLATE_SUM_WITH_CURRENCY_FIELD }}</span> <strong v-html=\"fields.SUM_WITH_CURRENCY\"></strong>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"checkout-basket-personal-order-info-item\" v-if=\"fields.PAY_SYSTEM_NAME\">\n\t\t\t\t\t\t\t<span>{{ loc.SPP_EMPTY_TEMPLATE_PAY_SYSTEM_NAME_FIELD }}</span> <strong>{{ fields.PAY_SYSTEM_NAME }}</strong>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t"
	};

	var PaySystemRow = {
	  props: {
	    loading: Boolean,
	    name: String,
	    logo: String,
	    id: Number
	  },
	  components: {
	    Button: Button
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('click', this.id);
	    }
	  },
	  computed: {
	    logoStyle: function logoStyle() {
	      var defaultLogo = '/bitrix/js/salescenter/payment-pay/payment-method/images/default_logo.png';
	      var src = this.logo || defaultLogo;
	      return "background-image: url(\"".concat(BX.util.htmlspecialchars(src), "\")");
	    },
	    loc: function loc() {
	      return ui_vue.BitrixVue.getFilteredPhrases('SPP_');
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"order-pay-method-item-container pay-mode\" @click=\"onClick()\">\n\t\t\t<div class=\"order-pay-method-item-logo-block\">\n\t\t\t\t<div class=\"order-pay-method-logo\" :style=\"logoStyle\"></div>\n\t\t\t</div>\n\t\t\t<div class=\"order-pay-method-text-block\">\n\t\t\t\t<div class=\"order-pay-method-text\">{{ name }}</div>\n\t\t\t</div>\n\t\t\t<Button :loading=\"loading\">{{ loc.SPP_PAY_BUTTON }}</Button>\n\t\t</div>\n\t"
	};

	var PaySystemList = {
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
	  components: {
	    PaySystemRow: PaySystemRow
	  },
	  methods: {
	    isItemLoading: function isItemLoading(paySystemId) {
	      return this.selectedPaySystem === paySystemId && this.loading;
	    },
	    startPayment: function startPayment(paySystemId) {
	      this.$emit('start-payment', paySystemId);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"page-section-title\" v-if=\"title\">{{ title }}</div>\n\t\t\t<div class=\"order-payment-method-list\">\n\t\t\t\t<slot>\n\t\t\t\t\t<PaySystemRow \n\t\t\t\t\t\tv-for=\"paySystem in paySystems\"\n\t\t\t\t\t\t:loading=\"isItemLoading(paySystem.ID)\"\n\t\t\t\t\t\t:name=\"paySystem.NAME\"\n\t\t\t\t\t\t:logo=\"paySystem.LOGOTIP\"\n\t\t\t\t\t\t:id=\"paySystem.ID\"\n\t\t\t\t\t\t@click=\"startPayment($event)\"\n\t\t\t\t\t/>\n\t\t\t\t</slot>\n\t\t\t</div>\n            <slot name=\"user-consent\"></slot>\n\t\t</div>\n\t"
	};

	var PaySystemInfo = {
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
	    loc: function loc() {
	      return ui_vue.BitrixVue.getFilteredPhrases('SPP_');
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
	  template: "\n\t\t<div>\n\t\t\t<div class=\"order-payment-method-list\">\n\t\t\t\t<slot>\n\t\t\t\t\t<div class=\"order-pay-method-item-container info-mode\" v-for=\"paySystem in paySystems\">\n\t\t\t\t\t\t<div class=\"order-pay-method-item-logo-block\">\n\t\t\t\t\t\t\t<div class=\"order-pay-method-logo\" :style=\"logoStyle(paySystem)\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"order-pay-method-text-block\">\n\t\t\t\t\t\t\t<div class=\"order-pay-method-text\">{{ paySystem.NAME }}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"btn info-mode\" @click=\"showInfo(paySystem)\">{{ loc.SPP_INFO_BUTTON }}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</slot>\n\t\t\t</div>\n\t\t\t<div class=\"order-payment-method-description\" v-if=\"selectedPaySystem\">\n\t\t\t\t<div class=\"order-payment-method-description-title\">{{ selectedName }}</div>\n\t\t\t\t<div class=\"order-payment-method-description-text\" v-html=\"selectedDescription\"></div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var Check = {
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
	  computed: {
	    processing: function processing() {
	      return this.status === 'P';
	    },
	    downloadable: function downloadable() {
	      return this.status === 'Y' && this.link !== '';
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"mb-2\" :class=\"{'check-print': processing}\">\n\t\t\t<a :href=\"link\" target=\"_blank\" class=\"check-link\" v-if=\"downloadable\">{{ title }}</a>\n\t\t\t<span v-else>{{ title }}</span>\n\t\t</div>\n\t"
	};

	var PaySystemCard = {
	  props: {
	    logo: String,
	    name: String
	  },
	  template: "\n\t\t<div class=\"order-payment-operator\">\n\t\t\t<img :src=\"logo\" :alt=\"name\" v-if=\"logo\">\n\t\t\t<div class=\"order-payment-pay-system-name\" v-else>{{ name }}</div>\n\t\t</div>\n\t"
	};

	var Button$1 = {
	  props: {
	    loading: {
	      type: Boolean,
	      "default": false,
	      required: false
	    }
	  },
	  computed: {
	    classes: function classes() {
	      var classes = ['landing-block-node-button', 'text-uppercase', 'btn', 'btn-xl', 'pr-7', 'pl-7', 'u-btn-primary', 'g-font-weight-700', 'g-font-size-12', 'g-rounded-50'];

	      if (this.loading) {
	        classes.push('loading');
	      }

	      return classes;
	    }
	  },
	  methods: {
	    onClick: function onClick(event) {
	      this.$emit('click', event);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<button :class=\"classes\" @click=\"onClick($event)\">\n\t\t\t<slot></slot>\n\t\t</button>\n\t"
	};

	var PaymentInfo = {
	  props: {
	    paySystem: Object,
	    title: String,
	    sum: String,
	    loading: Boolean,
	    paid: Boolean,
	    checks: Array
	  },
	  components: {
	    Check: Check,
	    PaySystemCard: PaySystemCard,
	    Button: Button$1
	  },
	  methods: {
	    onClick: function onClick() {
	      this.$emit('start-payment', this.paySystem.ID);
	    }
	  },
	  computed: {
	    loc: function loc() {
	      return ui_vue.BitrixVue.getFilteredPhrases('SPP_');
	    },
	    totalSum: function totalSum() {
	      return this.loc.SPP_SUM.replace('#SUM#', this.sum);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"order-payment-title\" v-if=\"title\">{{ title }}</div>\n\t\t\t<div class=\"order-payment-inner d-flex align-items-center justify-content-between\">\n\t\t\t\t<PaySystemCard :name=\"paySystem.NAME\" :logo=\"paySystem.LOGOTIP\"/>\n            \t<div class=\"order-payment-status d-flex align-items-center\" v-if=\"paid\">\n                \t<div class=\"order-payment-status-ok\"></div>\n                \t<div>{{ loc.SPP_PAID }}</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"order-payment-price\" v-html=\"totalSum\"></div>\n\t\t\t</div>\n\t\t\t<hr v-if=\"checks.length > 0\">\n\t\t\t<Check \n\t\t\t\tv-for=\"check in checks\" \n\t\t\t\t:title=\"check.title\" \n\t\t\t\t:link=\"check.link\" \n\t\t\t\t:status=\"check.status\"/>\n\t\t\t<hr v-if=\"!paid\">\n            <slot name=\"user-consent\" v-if=\"!paid\"></slot>\n\t\t\t<div class=\"order-payment-buttons-container\" v-if=\"!paid\">\n\t\t\t\t<Button\n\t\t\t\t\t:loading=\"loading\"\n\t\t\t\t\t@click=\"onClick()\">\n\t\t\t\t\t{{ loc.SPP_PAY_BUTTON }}\n\t\t\t\t</Button>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var _templateObject;
	var UserConsentComponent = {
	  props: {
	    id: {
	      type: Number,
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
	};

	ui_vue.BitrixVue.component('salescenter-payment-pay-app', {
	  props: {
	    options: Object
	  },
	  mixins: [salescenter_paymentPay_mixins.OptionMixin],
	  components: {
	    ErrorBox: ErrorBox,
	    ResetPanel: ResetPanel,
	    PaySystemResult: PaySystemResult,
	    PaySystemList: PaySystemList,
	    PaySystemInfo: PaySystemInfo,
	    PaymentInfo: PaymentInfo,
	    UserConsentComponent: UserConsentComponent
	  },
	  data: function data() {
	    return {
	      stages: this.getStageDefaults(),
	      stage: this.getDefaultStage(),
	      loading: false,
	      consent: {
	        id: this.option('consent.id'),
	        title: this.option('consent.title'),
	        eventName: this.option('consent.eventName'),
	        accepted: this.option('consent.accepted')
	      }
	    };
	  },
	  created: function created() {
	    this.initPaymentProcess();
	    this.initUserConsent();
	    this.subscribeToGlobalEvents();
	  },
	  methods: {
	    initPaymentProcess: function initPaymentProcess() {
	      this.backendProvider = new salescenter_paymentPay_backendProvider.BackendProvider({
	        returnUrl: this.option('paymentProcess.returnUrl'),
	        orderId: this.option('paymentProcess.orderId'),
	        paymentId: this.option('paymentProcess.paymentId'),
	        accessCode: this.option('paymentProcess.accessCode')
	      });
	      this.paymentProcess = new sale_paymentPay_lib.PaymentProcess({
	        backendProvider: this.backendProvider,
	        allowPaymentRedirect: this.option('paymentProcess.allowPaymentRedirect')
	      });
	    },
	    initUserConsent: function initUserConsent() {
	      this.userConsentManager = new salescenter_paymentPay_userConsent.UserConsent({
	        containerId: this.option('consent.containerId'),
	        accepted: this.option('consent.accepted', false),
	        eventName: this.option('consent.eventName', false)
	      });
	    },
	    subscribeToGlobalEvents: function subscribeToGlobalEvents() {
	      var _this = this;

	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.payment.error, function (e) {
	        _this.handlePaymentError(e.getData());
	      });
	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.payment.success, function (e) {
	        _this.handlePaymentSuccess(e.getData());
	      });
	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.global.paySystemAjaxError, function (e) {
	        _this.handlePaySystemAjaxError(e.getData());
	      });
	      main_core_events.EventEmitter.subscribe(sale_paymentPay_const.EventType.global.paySystemUpdateTemplate, function (e) {
	        _this.handlePaySystemUpdateTemplate(e.getData());
	      });
	    },
	    startPayment: function startPayment(paySystemId) {
	      var _this2 = this;

	      if (this.loading) {
	        return false;
	      }

	      this.userConsentManager.askUserToPerform(function () {
	        _this2.loading = true;
	        _this2.stages.paySystemList.selectedPaySystem = paySystemId;
	        _this2.backendProvider.paySystemId = paySystemId;

	        _this2.paymentProcess.start();
	      });
	    },
	    handlePaymentError: function handlePaymentError(response) {
	      this.stages.paySystemErrors.errors = response.data.errors || [];
	      this.stage = 'paySystemErrors';
	    },
	    handlePaymentSuccess: function handlePaymentSuccess(response) {
	      this.stages.paySystemResult.html = response.data.html || null;
	      this.stages.paySystemResult.fields = response.data.fields || null;
	      this.stage = 'paySystemResult';
	    },
	    handlePaySystemAjaxError: function handlePaySystemAjaxError(data) {
	      this.stages.paySystemErrors.errors = data || [];
	      this.stage = 'paySystemErrors';
	    },
	    handlePaySystemUpdateTemplate: function handlePaySystemUpdateTemplate(data) {
	      sale_paymentPay_lib.VirtualForm.createFromNode(this.$el).submit();
	    },
	    resetView: function resetView() {
	      this.stages = this.getStageDefaults();
	      this.stage = this.getDefaultStage();
	      this.loading = false;
	    },
	    getStageDefaults: function getStageDefaults() {
	      return {
	        paySystemList: {
	          paySystems: this.option('app.paySystems', []),
	          selectedPaySystem: null,
	          title: this.option('app.title')
	        },
	        paySystemInfo: {
	          paySystems: this.option('app.paySystems', [])
	        },
	        paymentInfo: {
	          paySystem: this.option('app.paySystems', [])[0],
	          title: this.option('app.title'),
	          sum: this.option('payment.sumFormatted'),
	          paid: this.option('payment.paid'),
	          checks: this.option('payment.checks', [])
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
	    getDefaultStage: function getDefaultStage() {
	      return this.option('app.template', 'paySystemList');
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"salescenter-payment-pay-app\">\n\t\t\t<PaySystemList \n\t\t\t\tv-if=\"stage === 'paySystemList'\"\n\t\t\t\t:paySystems=\"stages.paySystemList.paySystems\"\n\t\t\t\t:selectedPaySystem=\"stages.paySystemList.selectedPaySystem\"\n\t\t\t\t:loading=\"loading\"\n\t\t\t\t:title=\"stages.paySystemList.title\"\n\t\t\t\t@start-payment=\"startPayment($event)\">\n\t\t\t\t<template v-slot:user-consent>\n\t\t\t\t\t<UserConsentComponent \n\t\t\t\t\t\t:id=\"consent.id\"\n\t\t\t\t\t\t:title=\"consent.title\"\n\t\t\t\t\t\t:checked=\"consent.accepted\"\n\t\t\t\t\t\t:submitEventName=\"consent.eventName\"/>\n                </template>\n\t\t\t</PaySystemList>\n\t\t\t<PaySystemInfo\n\t\t\t\tv-if=\"stage === 'paySystemInfo'\"\n\t\t\t\t:paySystems=\"stages.paySystemInfo.paySystems\">\n\t\t\t</PaySystemInfo>\n\t\t\t<PaymentInfo \n\t\t\t\tv-if=\"stage === 'paymentInfo'\"\n\t\t\t\t:paySystem=\"stages.paymentInfo.paySystem\"\n                :title=\"stages.paymentInfo.title\"\n\t\t\t\t:sum=\"stages.paymentInfo.sum\"\n\t\t\t\t:paid=\"stages.paymentInfo.paid\"\n\t\t\t\t:loading=\"loading\"\n\t\t\t\t:checks=\"stages.paymentInfo.checks\"\n                @start-payment=\"startPayment($event)\">\n              \t<template v-slot:user-consent>\n                \t<UserConsentComponent\n                    \t:id=\"consent.id\"\n                    \t:title=\"consent.title\"\n                    \t:checked=\"consent.accepted\"\n                    \t:submitEventName=\"consent.eventName\"/>\n              \t</template>\n\t\t\t</PaymentInfo>\n\t\t\t<ErrorBox\n\t\t\t\tv-if=\"stage === 'paySystemErrors'\" \n\t\t\t\t:errors=\"stages.paySystemErrors.errors\">\n\t\t\t\t<ResetPanel @reset=\"resetView()\"/>\n\t\t\t</ErrorBox>\n\t\t\t<PaySystemResult\n\t\t\t\tv-if=\"stage === 'paySystemResult'\" \n\t\t\t\t:html=\"stages.paySystemResult.html\" \n\t\t\t\t:fields=\"stages.paySystemResult.fields\">\n\t\t\t\t<ResetPanel @reset=\"resetView()\"/>\n\t\t\t</PaySystemResult>\n\t\t</div>\n\t"
	});

}((this.BX.Salescenter.PaymentPay = this.BX.Salescenter.PaymentPay || {}),BX.Sale.PaymentPay.Lib,BX.Salescenter.PaymentPay,BX.Salescenter.PaymentPay.Mixins,BX.Salescenter.PaymentPay,BX,BX,BX.Event,BX.Sale.PaymentPay.Const));
//# sourceMappingURL=application.bundle.js.map
