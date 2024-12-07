/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Config = this.BX.Crm.Config || {};
(function (exports,ui_vue3,ui_switcher,main_popup,rest_client,bitrix24_phoneverify,ui_dialogs_messagebox,ui_label,main_core,landing_backend,landing_pageobject,ui_vue3_vuex) {
	'use strict';

	var SettingsContainer = {
	  props: {
	    title: String,
	    iconStyle: String,
	    collapsed: Boolean
	  },
	  methods: {
	    onTitleClicked: function onTitleClicked() {
	      this.$emit('titleClick');
	    }
	  },
	  template: "\n\t<div class=\"settings-container\">\n\t\t<div\n\t\t\tclass=\"ui-slider-heading-4 settings-container-title\"\n\t\t\tv-bind:class=\"{ 'settings-container-title-collapsed': collapsed }\"\n\t\t\tv-on:click=\"onTitleClicked\"\n\t\t>\n\t\t\t<div :class=\"iconStyle\"></div>\n\t\t\t{{ title }}\n\t\t</div>\n\n\t\t<div class=\"settings-section-list\" v-bind:class=\"{ 'settings-section-list-collapsed': collapsed }\">\n\t\t\t<slot></slot>\n\t\t</div>\n\t</div>\n\t"
	};

	var SettingsSection = {
	  data: function data() {
	    return {
	      isEnabled: this.active,
	      switcher: null
	    };
	  },
	  mounted: function mounted() {
	    new BX.UI.Switcher({
	      node: this.$refs.switcher,
	      size: 'small',
	      checked: this.isEnabled,
	      handlers: {
	        toggled: this.onSwitcherToggle.bind(this)
	      }
	    });
	    BX.UI.Hint.init(this.$refs.title);
	  },
	  methods: {
	    onSwitcherToggle: function onSwitcherToggle() {
	      this.isEnabled = !this.isEnabled;
	      this.$emit('toggle', this.isEnabled);
	    },
	    onTitleClick: function onTitleClick() {
	      this.$emit('titleClick');
	    }
	  },
	  props: {
	    title: String,
	    switchable: {
	      type: Boolean,
	      "default": false
	    },
	    active: {
	      type: Boolean,
	      "default": false
	    },
	    hint: {
	      type: String,
	      "default": ''
	    },
	    leftIconClass: {
	      type: String,
	      "default": ''
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"ui-slider-heading-4 settings-section-header\">\n\t\t\t\t<div v-if=\"switchable\" class=\"settings-setction-switcher-container\">\n\t\t\t\t\t<span ref=\"switcher\" class=\"ui-switcher\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div v-if=\"leftIconClass\" :class=\"leftIconClass\"></div>\n\t\t\t\t<div\n\t\t\t\t\tstyle=\"font-size: 16px; margin-right: 0px;\"\n\t\t\t\t>\n\t\t\t\t\t{{ title }}\n\t\t\t\t</div>\n\t\t\t\t<span\n\t\t\t\t\tv-if=\"hint !== ''\"\n\t\t\t\t\tclass=\"ui-hint\"\n\t\t\t\t\tdata-hint-html\n\t\t\t\t\tdata-hint-interactivity\n\t\t\t\t\t:data-hint=\"hint\"\n\t\t\t\t>\n\t\t\t\t\t<span class=\"ui-hint-icon\"></span>\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var Sms = {
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"sms-container\">\n\t\t\t\t<slot></slot>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var SmsProviderSelect = {
	  data: function data() {
	    return {
	      isListShowed: false
	    };
	  },
	  mounted: function mounted() {
	    var _this = this;
	    main_core.Event.bind(document, 'click', function () {
	      _this.isListShowed = false;
	    });
	  },
	  methods: _objectSpread(_objectSpread(_objectSpread({}, ui_vue3_vuex.mapGetters(['getServiceLink', 'getActiveSmsServices'])), ui_vue3_vuex.mapMutations(['selectSMSService'])), {}, {
	    switchService: function switchService(serviceId) {
	      this.isListShowed = false;
	      this.selectSMSService(serviceId);
	    },
	    switchVisibility: function switchVisibility() {
	      this.isListShowed = !this.isListShowed;
	    },
	    openSmsServicesSlider: function openSmsServicesSlider() {
	      var _this2 = this;
	      var options = {
	        cacheable: false,
	        allowChangeHistory: false,
	        requestMethod: 'get',
	        width: 700,
	        events: {
	          onClose: function onClose() {
	            _this2.$emit('onConnectSliderClosed');
	          }
	        }
	      };
	      BX.SidePanel.Instance.open(this.getServiceLink(), options);
	    }
	  }),
	  computed: _objectSpread(_objectSpread({}, ui_vue3_vuex.mapGetters(['getSelectedService'])), {}, {
	    getListClassname: function getListClassname() {
	      if (!this.isListShowed) {
	        return 'sms-provider-selector-hided-list';
	      }
	      return 'sms-provider-selector-list';
	    }
	  }),
	  // language=Vue
	  template: "\n\t\t<div style=\"display: inline-block; vertical-align: top; position: relative;\">\n\t\t\t<span class=\"sms-provider-selector\" @click.stop=\"switchVisibility\">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_CHANGE_MSGVER_1') }}</span>\n\t\t\t<ul :class=\"getListClassname\" @click.stop>\n\t\t\t\t<li v-for=\"provider in getActiveSmsServices()\" @click=\"switchService(provider['ID'])\" v-show=\"provider['ID'] !== getSelectedService['ID']\">{{ provider['NAME'] }}</li>\n\t\t\t\t<li @click=\"openSmsServicesSlider\">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_MORE') }}</li>\n\t\t\t</ul>\n\t\t</div>\n\t"
	};

	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var SmsSettingsSection = {
	  data: function data() {
	    return {
	      popup: null
	    };
	  },
	  components: {
	    SettingsSection: SettingsSection,
	    Sms: Sms,
	    SmsProviderSelect: SmsProviderSelect
	  },
	  computed: _objectSpread$1(_objectSpread$1({}, ui_vue3_vuex.mapGetters(['isSmsSendingActive', 'isAnyServiceEnabled', 'isNotificationsEnabled', 'getSelectedService'])), {}, {
	    getSectionTitle: function getSectionTitle() {
	      return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_TITLE');
	    },
	    getSectionHint: function getSectionHint() {
	      var replacements = {
	        '#LINK_START#': '<a onclick="top.BX.Helper.show(\'redirect=detail&code=17399056\')" style="cursor: pointer">',
	        '#LINK_END#': '</a>'
	      };
	      return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_HINT_TEXT', replacements);
	    },
	    getNotificationConnectHint: function getNotificationConnectHint() {
	      var replacements = {
	        '#LINK_START#': '<span onclick="top.BX.Helper.show(\'redirect=detail&code=17399068\')" class="sms-provider-selector">',
	        '#LINK_END#': '</span>'
	      };
	      return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_UNC_CONNECTED', replacements);
	    }
	  }),
	  methods: _objectSpread$1(_objectSpread$1(_objectSpread$1({}, ui_vue3_vuex.mapMutations(['setSmsSendingActive', 'updateServicesList', 'updateNotificationsEnabled'])), ui_vue3_vuex.mapGetters(['getPaymentSlipLinkScheme', 'getNotificationsLink', 'getServiceLink'])), {}, {
	    onServiceConnectSliderClosed: function onServiceConnectSliderClosed() {
	      var _this = this;
	      main_core.ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'updateServicesList').then(function (response) {
	        if (response.status === 'success') {
	          var data = response.data;
	          if (data !== null && data !== void 0 && data.isUCNEnabled) {
	            _this.updateNotificationsEnabled(data.isUCNEnabled);
	          }
	          _this.updateServicesList(data.activeSmsServices);
	        }
	      })["catch"](function (error) {
	        console.error(error);
	      });
	    },
	    onSectionToggled: function onSectionToggled() {
	      this.setSmsSendingActive(!this.isSmsSendingActive);
	    },
	    getSmsMessage: function getSmsMessage() {
	      var link = "<br /><span class=\"sms-link-path\">".concat(this.getPaymentSlipLinkScheme(), "</span><span class=\"sms-link-plug\">xxxxx</span> ");
	      var text = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_TEMPLATE');
	      return main_core.Text.encode(text).replaceAll('#PAYMENT_SLIP_LINK#', link);
	    },
	    onSmsMouseenter: function onSmsMouseenter(event) {
	      var _this2 = this;
	      var target = event.target;
	      var message = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_HINT');
	      if (this.popup) {
	        this.popup.destroy();
	        this.popup = null;
	      }
	      this.popup = new main_popup.Popup(null, target, {
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this2.popup.destroy();
	            _this2.popup = null;
	          }
	        },
	        darkMode: true,
	        content: message,
	        offsetLeft: target.offsetWidth
	      });
	      this.popup.show();
	    },
	    onSmsMouseleave: function onSmsMouseleave() {
	      this.popup.destroy();
	    },
	    onNotificationsConnectLinkClick: function onNotificationsConnectLinkClick() {
	      var _this3 = this;
	      var notificationLink = this.getNotificationsLink();
	      if (notificationLink === null) {
	        return;
	      }
	      if (notificationLink.type === 'connect_link') {
	        var options = {
	          cacheable: false,
	          allowChangeHistory: false,
	          requestMethod: 'get',
	          width: 700,
	          events: {
	            onClose: function onClose() {
	              _this3.onServiceConnectSliderClosed();
	            }
	          }
	        };
	        BX.SidePanel.Instance.open(notificationLink.value, options);
	      } else if (notificationLink.type === 'ui_helper') {
	        top.BX.UI.InfoHelper.show(notificationLink.value);
	      }
	    },
	    onProviderSmsNotificationClick: function onProviderSmsNotificationClick() {
	      var _this4 = this;
	      var options = {
	        cacheable: false,
	        allowChangeHistory: false,
	        requestMethod: 'get',
	        width: 700,
	        events: {
	          onClose: function onClose() {
	            _this4.onServiceConnectSliderClosed();
	          }
	        }
	      };
	      BX.SidePanel.Instance.open(this.getServiceLink(), options);
	    }
	  }),
	  // language=Vue
	  template: "\n\t\t<div v-if=\"isAnyServiceEnabled\" style=\"display: flex; justify-content: space-between; margin-bottom: 24px;\">\n\t\t\t<div>\n\t\t\t\t<SettingsSection\n\t\t\t\t\t:title=\"getSectionTitle\"\n\t\t\t\t\t:switchable=\"true\"\n\t\t\t\t\tv-on:toggle=\"onSectionToggled\"\n\t\t\t\t\t:active=\"isSmsSendingActive\"\n\t\t\t\t\t:hint=\"getSectionHint\"\n\t\t\t\t/>\n\t\t\t\t<div style=\"margin-left: 53px;\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-html=\"getNotificationConnectHint\"\n\t\t\t\t\t\tv-if=\"isNotificationsEnabled\"\n\t\t\t\t\t\tclass=\"sms-provider-name\"\n\t\t\t\t\t></div>\n\t\t\t\t\t<div v-else>\n\t\t\t\t\t\t<div class=\"sms-provider-name\" style=\"padding: 3px 0;\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_SELECT_MSGVER_2', {'%PROVIDER_NAME%': getSelectedService['NAME']}) }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<SmsProviderSelect v-on:onConnectSliderClosed=\"onServiceConnectSliderClosed\"/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div>\n\t\t\t\t<div class=\"sms-provider-message-title\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_TITLE') }}\n\t\t\t\t</div>\n\t\t\t\t<div>\n\t\t\t\t\t<Sms>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-html=\"getSmsMessage()\"\n\t\t\t\t\t\t\tv-on:mouseenter=\"onSmsMouseenter($event)\"\n\t\t\t\t\t\t\tv-on:mouseleave=\"onSmsMouseleave\"\n\t\t\t\t\t\t></span>\n\t\t\t\t\t</Sms>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t\t<div style=\"margin-bottom: 24px;\" v-else>\n\t\t\t<span class=\"sms-provider-empty-provider-list-text\" v-if=\"(getNotificationsLink() !== null) && (getServiceLink() !== '')\">\n\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_ONBOARDING_TEXT') }}\n\t\t\t</span>\n\t\t\t<span class=\"sms-provider-empty-provider-list-text\" v-else-if=\"(getNotificationsLink() === null) && (getServiceLink() !== '')\">\n\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_ONBOARDING_ONLY_SMS_SERVICES_TEXT') }}\n\t\t\t</span>\n\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-primary\" @click=\"onNotificationsConnectLinkClick\" v-if=\"getNotificationsLink() !== null\">\n\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_UNC_CONNECT_BTN') }}\n\t\t\t</button>\n\n\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-light-border\" @click=\"onProviderSmsNotificationClick\" v-if=\"(getNotificationsLink() !== null) && (getServiceLink() !== '')\">\n\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_BTN') }}\n\t\t\t</button>\n\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-primary\" @click=\"onProviderSmsNotificationClick\" v-else-if=\"(getNotificationsLink() === null) && (getServiceLink() !== '')\">\n\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_BTN') }}\n\t\t\t</button>\n\t\t</div>\n\t"
	};

	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var SmsSettings = {
	  components: {
	    SettingsContainer: SettingsContainer,
	    SmsSettingsSection: SmsSettingsSection
	  },
	  computed: _objectSpread$2({}, ui_vue3_vuex.mapGetters({
	    isSettingsChanged: 'isChanged',
	    isSaving: 'isSaving',
	    getIsSmsCollapsed: 'getIsSmsCollapsed'
	  })),
	  methods: _objectSpread$2(_objectSpread$2({}, ui_vue3_vuex.mapMutations(['updateIsSmsCollapsed'])), {}, {
	    onTitleClick: function onTitleClick() {
	      this.updateIsSmsCollapsed(!this.getIsSmsCollapsed);
	      main_core.ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'updateSmsCollapsed', {
	        data: {
	          collapsed: this.getIsSmsCollapsed
	        }
	      });
	    }
	  }),
	  // language=Vue
	  template: "\n\t\t<SettingsContainer\n\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SUBTITLE_NOTIFICATION')\"\n\t\t\ticonStyle=\"settings-section-icon-sms\"\n\t\t\t:collapsed=\"getIsSmsCollapsed\"\n\t\t\tv-on:titleClick=\"onTitleClick\"\n\t\t>\n\t\t\t<SmsSettingsSection />\n\t\t</SettingsContainer>\n\t"
	};

	var RequiredPaysystemCodes = Object.freeze({
	  sbp: 'sbp',
	  sberQr: 'sberQr',
	  rest: 'rest',
	  paysystemPanel: 'paysystemPanel'
	});

	function ownKeys$3(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$3(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$3(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$3(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var PaymentMethodsSettings = {
	  components: {
	    SettingsSection: SettingsSection,
	    SettingsContainer: SettingsContainer
	  },
	  computed: _objectSpread$3(_objectSpread$3({}, ui_vue3_vuex.mapGetters({
	    getAvailablePaysystems: 'getAvailablePaysystems',
	    getTerminalDisabledPaysystems: 'getTerminalDisabledPaysystems',
	    getIsLinkPaymentEnabled: 'getIsLinkPaymentEnabled',
	    getIsSbpEnabled: 'getIsSbpEnabled',
	    getIsSberQrEnabled: 'getIsSberQrEnabled',
	    getIsSbpConnected: 'getIsSbpConnected',
	    getIsSberQrConnected: 'getIsSberQrConnected',
	    getIsRuZone: 'getIsRuZone',
	    getSbpConnectPath: 'getSbpConnectPath',
	    getSberQrConnectPath: 'getSberQrConnectPath',
	    getIsAnyPaysystemActive: 'getIsAnyPaysystemActive',
	    getPaysystemPanelPath: 'getPaysystemPanelPath',
	    getIsPaysystemsCollapsed: 'getIsPaysystemsCollapsed',
	    getPaysystemsArticleUrl: 'getPaysystemsArticleUrl',
	    getIsPhoneConfirmed: 'getIsPhoneConfirmed',
	    getConnectedSiteId: 'getConnectedSiteId',
	    getIsConnectedSitePublished: 'getIsConnectedSitePublished',
	    getIsConnectedSiteExists: 'getIsConnectedSiteExists'
	  })), {}, {
	    getRequiredPaysystemCodes: function getRequiredPaysystemCodes() {
	      return RequiredPaysystemCodes;
	    },
	    getLinkPaymentHint: function getLinkPaymentHint() {
	      return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_LINK_PAYMENT_HINT');
	    }
	  }),
	  methods: _objectSpread$3(_objectSpread$3({}, ui_vue3_vuex.mapMutations(['setTerminalPaysystemDisabled', 'setLinkPaymentEnabled', 'setRequiredPaysystemDisabled', 'updateSbpConnectPath', 'updateSberQrConnectPath', 'updateIsSbpConnected', 'updateIsSberQrConnected', 'updateIsPaysystemsCollapsed', 'updateIsAnyPaysystemActive', 'updateAvailablePaysystems', 'updateIsPhoneConfirmed', 'updateConnectedSiteId', 'updateIsConnectedSitePublished', 'updateIsConnectedSiteExists'])), {}, {
	    onPaysystemToggled: function onPaysystemToggled(paysystemId) {
	      this.setTerminalPaysystemDisabled(paysystemId);
	    },
	    onRequiredPaysystemToggled: function onRequiredPaysystemToggled(paysystemCode) {
	      this.setRequiredPaysystemDisabled(paysystemCode);
	    },
	    onLinkPaymentToggled: function onLinkPaymentToggled() {
	      this.setLinkPaymentEnabled(!this.getIsLinkPaymentEnabled);
	    },
	    openPaysystemSlider: function openPaysystemSlider(psMode) {
	      var _this = this;
	      var link = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      var options = {
	        cacheable: false,
	        allowChangeHistory: false,
	        requestMethod: 'get',
	        width: psMode === RequiredPaysystemCodes.paysystemPanel ? null : 1000,
	        events: {
	          onClose: function onClose() {
	            _this.onPaysystemSliderClosed();
	          }
	        }
	      };
	      var url = psMode === RequiredPaysystemCodes.rest ? link : this.getPaysystemUrl(psMode);
	      BX.SidePanel.Instance.open(url, options);
	    },
	    getPaysystemUrl: function getPaysystemUrl(psMode) {
	      switch (psMode) {
	        case RequiredPaysystemCodes.sbp:
	          return this.getSbpConnectPath;
	        case RequiredPaysystemCodes.sberQr:
	          return this.getSberQrConnectPath;
	        case RequiredPaysystemCodes.paysystemPanel:
	          return this.getPaysystemPanelPath;
	        default:
	          return '';
	      }
	    },
	    onPaysystemSliderClosed: function onPaysystemSliderClosed() {
	      var _this2 = this;
	      main_core.ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'updatePaysystemPaths').then(function (response) {
	        _this2.updateSbpConnectPath(response.data.sbp);
	        _this2.updateSberQrConnectPath(response.data.sberbankQr);
	        _this2.updateIsSbpConnected(response.data.isSbpConnected);
	        _this2.updateIsSberQrConnected(response.data.isSberQrConnected);
	        _this2.updateIsAnyPaysystemActive(response.data.isAnyPaysystemActive);
	        _this2.updateAvailablePaysystems(response.data.availablePaysystems);
	      })["catch"](function () {});
	    },
	    onSiteSliderClosed: function onSiteSliderClosed() {
	      var _this3 = this;
	      this.loader.show(document.body);
	      main_core.ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'updateConnectedSiteParams').then(function (response) {
	        _this3.loader.hide();
	        _this3.updateIsConnectedSiteExists(response.data.isConnectedSiteExists);
	        _this3.updateConnectedSiteId(response.data.connectedSiteId);
	        _this3.updateIsPhoneConfirmed(response.data.isPhoneConfirmed);
	        _this3.updateIsConnectedSitePublished(response.data.isConnectedSitePublished);
	        _this3.connectSite();
	      })["catch"](function () {});
	    },
	    getStatusLabel: function getStatusLabel() {
	      var connected = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      var text = this.$Bitrix.Loc.getMessage(connected ? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECTED' : 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_NOT_CONNECTED').toUpperCase();
	      var label = new ui_label.Label({
	        text: text,
	        color: connected ? ui_label.LabelColor.LIGHT_GREEN : ui_label.LabelColor.LIGHT,
	        size: ui_label.LabelSize.LG,
	        fill: true
	      });
	      return label.render().outerHTML;
	    },
	    onTitleClick: function onTitleClick() {
	      this.updateIsPaysystemsCollapsed(!this.getIsPaysystemsCollapsed);
	      main_core.ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'updatePaysystemsCollapsed', {
	        data: {
	          collapsed: this.getIsPaysystemsCollapsed
	        }
	      });
	    },
	    connectSite: function connectSite() {
	      if (!this.loader) {
	        this.loader = new BX.Loader({
	          size: 200
	        });
	      }
	      if (!this.getIsConnectedSiteExists) {
	        this.createSite();
	        return;
	      }
	      if (!this.getIsConnectedSitePublished) {
	        this.publishSite();
	        return;
	      }
	      if (!this.getIsPhoneConfirmed) {
	        this.showPhoneConfirmationPopup();
	      }
	    },
	    createSite: function createSite() {
	      var _this4 = this;
	      this.loader.show(document.body);
	      rest_client.rest.callMethod('salescenter.manager.getConfig').then(function (result) {
	        var _result$answer$result = result.answer.result,
	          connectedSiteId = _result$answer$result.connectedSiteId,
	          isSiteExists = _result$answer$result.isSiteExists,
	          isPhoneConfirmed = _result$answer$result.isPhoneConfirmed,
	          siteTemplateCode = _result$answer$result.siteTemplateCode;
	        _this4.loader.hide();
	        if (isSiteExists && connectedSiteId > 0) {
	          _this4.updateIsConnectedSiteExists(isSiteExists);
	          _this4.updateConnectedSiteId(connectedSiteId);
	          _this4.updateIsPhoneConfirmed(isPhoneConfirmed);
	          if (isPhoneConfirmed) {
	            _this4.publishSite();
	            return;
	          }
	          _this4.showPhoneConfirmationPopup();
	        } else {
	          var url = new main_core.Uri('/shop/stores/site/edit/0/');
	          var params = {
	            context: 'terminal',
	            tpl: siteTemplateCode,
	            no_redirect: 'Y'
	          };
	          url.setQueryParams(params);
	          var options = {
	            events: {
	              onClose: function onClose() {
	                _this4.onSiteSliderClosed();
	              }
	            }
	          };
	          BX.SidePanel.Instance.open(url.toString(), options);
	        }
	      })["catch"](function () {
	        return _this4.loader.hide();
	      });
	    },
	    publishSite: function publishSite() {
	      var _this5 = this;
	      this.loader.show(document.body);
	      landing_backend.Backend.getInstance().action('Site::publication', {
	        id: this.getConnectedSiteId
	      }).then(function (publishedSiteId) {
	        _this5.loader.hide();
	        if (publishedSiteId) {
	          _this5.updateIsConnectedSitePublished(true);
	        }
	      })["catch"](function (data) {
	        _this5.loader.hide();
	        if (data.type === 'error' && !main_core.Type.isUndefined(data.result[0])) {
	          var errorCode = data.result[0].error;
	          if (errorCode === 'PHONE_NOT_CONFIRMED') {
	            _this5.showPhoneConfirmationPopup();
	          } else if (errorCode === 'EMAIL_NOT_CONFIRMED') {
	            BX.UI.InfoHelper.show('limit_sites_confirm_email');
	          } else {
	            ui_dialogs_messagebox.MessageBox.alert(data.result[0].error_description);
	          }
	        }
	      });
	    },
	    confirmPhoneNumber: function confirmPhoneNumber() {
	      var _this6 = this;
	      this.loader.show(document.body);
	      bitrix24_phoneverify.PhoneVerify.getInstance().setEntityType('landing_site').setEntityId(this.getConnectedSiteId).startVerify({
	        mandatory: false,
	        callback: function callback(verified) {
	          _this6.loader.hide();
	          if (!verified) {
	            return;
	          }
	          _this6.updateIsPhoneConfirmed(verified);
	          if (!_this6.getIsConnectedSitePublished) {
	            _this6.publishSite();
	          }
	        }
	      });
	    },
	    showPhoneConfirmationPopup: function showPhoneConfirmationPopup() {
	      var _this7 = this;
	      ui_dialogs_messagebox.MessageBox.confirm(this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_MESSAGE'), this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_TITLE'), function (messageBox) {
	        messageBox.close();
	        _this7.confirmPhoneNumber();
	      }, this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_OK_CAPTION'), function (messageBox) {
	        return messageBox.close();
	      }, this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PHONE_CONFIRMATION_POPUP_CANCEL_CAPTION'));
	    }
	  }),
	  // language=Vue
	  template: "\n\t\t<SettingsContainer\n\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_TITLE_MSGVER_1')\"\n\t\t\ticonStyle=\"settings-section-icon-payment-methods\"\n\t\t\t:collapsed=\"getIsPaysystemsCollapsed\"\n\t\t\tv-on:titleClick=\"onTitleClick\"\n\t\t\tv-bind:style=\"{ 'padding-bottom: 0px;' : !getIsPaysystemsCollapsed }\"\n\t\t>\n\n\t\t\t<div\n\t\t\t\tclass=\"payment-systems-subtitle\"\n\t\t\t\tv-html=\"$Bitrix.Loc.getMessage(\n\t\t\t\t\t'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SUBTITLE_MSGVER_1',\n\t\t\t\t\t{'#MORE_INFO_LINK#': getPaysystemsArticleUrl})\"\n\t\t\t></div>\n\n\t\t\t<div class=\"payment-systems-section-container\">\n\t\t\t\t<div class=\"payment-systems-container\">\n\t\t\t\t\t<div v-if=\"getIsRuZone\" class=\"payment-system-wrapper\">\n\t\t\t\t\t\t<SettingsSection\n\t\t\t\t\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBP')\"\n\t\t\t\t\t\t\t:switchable=\"true\"\n\t\t\t\t\t\t\t:active=\"getIsSbpEnabled\"\n\t\t\t\t\t\t\tleftIconClass=\"payment-method-icon-sbp\"\n\t\t\t\t\t\t\tv-on:toggle=\"onRequiredPaysystemToggled(getRequiredPaysystemCodes.sbp)\"\n\t\t\t\t\t\t\tv-on:titleClick=\"openPaysystemSlider(getRequiredPaysystemCodes.sbp)\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<div class=\"payment-system-status-container\">\n\t\t\t\t\t\t\t<span v-html=\"getStatusLabel(this.getIsSbpConnected)\"></span>\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"payment-system-set\"\n\t\t\t\t\t\t\t\t:class=\"getIsSbpConnected ? 'payment-system-set-connected' : 'payment-system-set-not-connected'\"\n\t\t\t\t\t\t\t\tv-on:click=\"openPaysystemSlider(getRequiredPaysystemCodes.sbp)\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage(this.getIsSbpConnected\n\t\t\t\t\t\t\t\t? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SET'\n\t\t\t\t\t\t\t\t: 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div v-if=\"getIsRuZone\" class=\"payment-system-wrapper\">\n\t\t\t\t\t\t<SettingsSection\n\t\t\t\t\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBER_QR_MSGVER_1')\"\n\t\t\t\t\t\t\t:switchable=\"true\"\n\t\t\t\t\t\t\t:active=\"getIsSberQrEnabled\"\n\t\t\t\t\t\t\tleftIconClass=\"payment-method-icon-sber\"\n\t\t\t\t\t\t\tv-on:toggle=\"onRequiredPaysystemToggled(getRequiredPaysystemCodes.sberQr)\"\n\t\t\t\t\t\t\tv-on:titleClick=\"openPaysystemSlider(getRequiredPaysystemCodes.sberQr)\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<div class=\"payment-system-status-container\">\n\t\t\t\t\t\t\t<span v-html=\"getStatusLabel(this.getIsSberQrConnected)\"></span>\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"payment-system-set\"\n\t\t\t\t\t\t\t\t:class=\"getIsSberQrConnected ? 'payment-system-set-connected' : 'payment-system-set-not-connected'\"\n\t\t\t\t\t\t\t\tv-on:click=\"openPaysystemSlider(getRequiredPaysystemCodes.sberQr)\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage(this.getIsSberQrConnected\n\t\t\t\t\t\t\t\t? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SET'\n\t\t\t\t\t\t\t\t: 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-if=\"getAvailablePaysystems.length > 0\"\n\t\t\t\t\t\tv-for=\"paysystem in getAvailablePaysystems\"\n\t\t\t\t\t\tclass=\"payment-system-wrapper\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<SettingsSection\n\t\t\t\t\t\t\t:key=\"paysystem.type\"\n\t\t\t\t\t\t\t:title=\"paysystem.title\"\n\t\t\t\t\t\t\t:switchable=\"paysystem.id > 0\"\n\t\t\t\t\t\t\t:active=\"paysystem.id > 0 && !getTerminalDisabledPaysystems.includes(paysystem.id)\"\n\t\t\t\t\t\t\tv-on:toggle=\"onPaysystemToggled(paysystem.id)\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<div class=\"payment-system-status-container\">\n\t\t\t\t\t\t\t<span v-html=\"getStatusLabel(paysystem.isConnected)\"></span>\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"payment-system-set\"\n\t\t\t\t\t\t\t\t:class=\"paysystem.isConnected ? 'payment-system-set-connected' : 'payment-system-set-not-connected'\"\n\t\t\t\t\t\t\t\tv-on:click=\"openPaysystemSlider(getRequiredPaysystemCodes.rest, paysystem.path)\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage(paysystem.isConnected\n\t\t\t\t\t\t\t\t? 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SET'\n\t\t\t\t\t\t\t\t: 'CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"payment-system-wrapper\">\n\t\t\t\t\t\t<SettingsSection\n\t\t\t\t\t\t\t:title=\"$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_LINK_PAYMENT')\"\n\t\t\t\t\t\t\t:switchable=\"true\"\n\t\t\t\t\t\t\t:active=\"getIsLinkPaymentEnabled\"\n\t\t\t\t\t\t\tv-on:toggle=\"onLinkPaymentToggled()\"\n\t\t\t\t\t\t\t:hint=\"getLinkPaymentHint\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<div class=\"payment-system-status-container\">\n\t\t\t\t\t\t\t<span v-html=\"getStatusLabel(this.getIsAnyPaysystemActive && this.getIsConnectedSitePublished && getIsPhoneConfirmed)\"></span>\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"payment-system-set payment-system-set-connected\"\n\t\t\t\t\t\t\t\tv-on:click=\"openPaysystemSlider(getRequiredPaysystemCodes.paysystemPanel)\"\n\t\t\t\t\t\t\t\tv-if=\"getIsConnectedSitePublished && getIsPhoneConfirmed\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_PAYMENT_METHOD') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tclass=\"payment-system-set payment-system-set-not-connected\"\n\t\t\t\t\t\t\t\tv-on:click=\"connectSite()\"\n\t\t\t\t\t\t\t\tv-else\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_CONNECT') }}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"terminal-image-wrapper\"></div>\n\t\t\t</div>\n\n\t\t</SettingsContainer>\n\t"
	};

	function ownKeys$4(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$4(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$4(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$4(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var ButtonsPanel = {
	  methods: {
	    save: function save() {
	      this.$Bitrix.eventEmitter.emit('crm:terminal:onSettingsSave');
	    },
	    cancel: function cancel() {
	      this.$Bitrix.eventEmitter.emit('crm:terminal:onSettingsCancel');
	    }
	  },
	  computed: _objectSpread$4(_objectSpread$4({}, ui_vue3_vuex.mapGetters({
	    isSettingsChanged: 'isChanged',
	    isSaving: 'isSaving'
	  })), {}, {
	    buttonsPanelClass: function buttonsPanelClass() {
	      return {
	        'ui-button-panel-wrapper': true,
	        'ui-pinner': true,
	        'ui-pinner-bottom': true,
	        'ui-pinner-full-width': true,
	        'ui-button-panel-wrapper-hide': !this.isSettingsChanged
	      };
	    },
	    saveButtonClasses: function saveButtonClasses() {
	      return {
	        'ui-btn': true,
	        'ui-btn-success': true,
	        'ui-btn-wait': this.isSaving
	      };
	    }
	  }),
	  template: "\n\t\t<div :class=\"buttonsPanelClass\">\n\t\t\t<div class=\"ui-button-panel ui-button-panel-align-center\">\n\t\t\t\t<button\n\t\t\t\t\t@click=\"save\"\n\t\t\t\t\t:class=\"saveButtonClasses\"\n\t\t\t\t>\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVE_BTN') }}\n\t\t\t\t</button>\n\t\t\t\t<a\n\t\t\t\t\t@click=\"cancel\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-link\"\n\t\t\t\t>\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_CANCEL_BTN') }}\n\t\t\t\t</a>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function ownKeys$5(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$5(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$5(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$5(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _application = /*#__PURE__*/new WeakMap();
	var _initStore = /*#__PURE__*/new WeakSet();
	var App = /*#__PURE__*/function () {
	  function App(props) {
	    babelHelpers.classCallCheck(this, App);
	    _classPrivateMethodInitSpec(this, _initStore);
	    _classPrivateFieldInitSpec(this, _application, {
	      writable: true,
	      value: void 0
	    });
	    this.rootNode = document.getElementById(props.rootNodeId);
	    this.terminalSettings = props.terminalSettings;
	    this.store = _classPrivateMethodGet(this, _initStore, _initStore2).call(this, props.terminalSettings);
	  }
	  babelHelpers.createClass(App, [{
	    key: "attachTemplate",
	    value: function attachTemplate() {
	      babelHelpers.classPrivateFieldSet(this, _application, ui_vue3.BitrixVue.createApp({
	        components: {
	          SmsSettings: SmsSettings,
	          PaymentMethodsSettings: PaymentMethodsSettings,
	          ButtonsPanel: ButtonsPanel
	        },
	        computed: _objectSpread$5({}, ui_vue3_vuex.mapGetters(['isChanged', 'isSaving', 'getChangedValues', 'getIsPaysystemsCollapsed', 'getIsSbpEnabled', 'getIsSberQrEnabled', 'getIsLinkPaymentEnabled', 'getAvailablePaysystems', 'getTerminalDisabledPaysystems', 'getIsAnyPaysystemEnabled'])),
	        mounted: function mounted() {
	          this.$Bitrix.eventEmitter.subscribe('crm:terminal:onSettingsSave', this.onSettingsSave);
	          this.$Bitrix.eventEmitter.subscribe('crm:terminal:onSettingsCancel', this.onSettingsCancel);
	        },
	        beforeUnmount: function beforeUnmount() {
	          this.$Bitrix.eventEmitter.unsubscribe('crm:terminal:onSettingsSave');
	          this.$Bitrix.eventEmitter.unsubscribe('crm:terminal:onSettingsCancel');
	        },
	        methods: _objectSpread$5(_objectSpread$5({}, ui_vue3_vuex.mapMutations(['setSaving'])), {}, {
	          validateChangedValues: function validateChangedValues() {
	            var values = this.getChangedValues;
	            if (values && Object.keys(values).length > 0) {
	              return values;
	            }
	            return {};
	          },
	          onSettingsSave: function onSettingsSave() {
	            var _this = this;
	            if (this.getIsAnyPaysystemEnabled) {
	              this.save();
	            } else {
	              ui_dialogs_messagebox.MessageBox.confirm(this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVING_CONFIRM_TITLE'), function (messageBox) {
	                return messageBox.close();
	              }, this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVING_CONFIRM_OK'), function (messageBox) {
	                messageBox.close();
	                _this.save();
	              }, this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVING_CONFIRM_SAVE_AND_CLOSE'));
	            }
	          },
	          save: function save() {
	            var _this2 = this;
	            if (!this.isChanged || this.isSaving) {
	              return;
	            }
	            this.setSaving(true);
	            main_core.ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'saveSettings', {
	              data: {
	                changedValues: this.validateChangedValues()
	              }
	            }).then(function () {
	              _this2.setSaving(false);
	              BX.SidePanel.Instance.close();
	              top.BX.UI.Notification.Center.notify({
	                content: _this2.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_ON_SAVE_SUCCESS')
	              });
	            }, function () {
	              _this2.setSaving(false);
	              BX.UI.Notification.Center.notify({
	                content: _this2.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_ON_SAVE_ERROR'),
	                width: 350,
	                autoHideDelay: 4000
	              });
	            });
	          },
	          onSettingsCancel: function onSettingsCancel() {
	            BX.SidePanel.Instance.close();
	          }
	        }),
	        // language=Vue
	        template: "\n\t\t\t\t<div style=\"position: relative; overflow: hidden;\">\n\t\t\t\t\t<div class=\"ui-side-panel-wrap-workarea payment-methods-settings-wrapper\">\n\t\t\t\t\t\t<PaymentMethodsSettings/>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div\n\t\t\t\t\t\tclass=\"terminal-image\"\n\t\t\t\t\t\tv-bind:class=\"{ 'terminal-image-collapsed': this.getIsPaysystemsCollapsed }\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"terminal-image-title\">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_IMAGE_TITLE') }}</div>\n\t\t\t\t\t\t<div class=\"terminal-image-paysystems-container\">\n\t\t\t\t\t\t\t<div v-if=\"getIsSbpEnabled\" class=\"terminal-image-paysystem terminal-image-paysystem-sbp\">\n\t\t\t\t\t\t\t\t<span>{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBP') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div v-if=\"getIsSberQrEnabled\" class=\"terminal-image-paysystem terminal-image-paysystem-sber-qr\">\n\t\t\t\t\t\t\t\t<span>{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_SBER_QR_MSGVER_1') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<template v-if=\"getAvailablePaysystems.length > 0\" v-for=\"paysystem in getAvailablePaysystems\">\n\t\t\t\t\t\t\t\t<div class=\"terminal-image-paysystem terminal-image-paysystem-wallet\" v-if=\"getTerminalDisabledPaysystems.indexOf(paysystem.id) === -1\">\n\t\t\t\t\t\t\t\t\t<span>{{ paysystem.title }}</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<div v-if=\"getIsLinkPaymentEnabled\" class=\"terminal-image-paysystem terminal-image-paysystem-link\">\n\t\t\t\t\t\t\t\t<span>{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_LINK_PAYMENT') }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"terminal-image-no-paysystems-stub\" v-if=\"!getIsAnyPaysystemEnabled\">\n\t\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_PS_NO_PAY_METHODS') }}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"ui-side-panel-wrap-workarea\" style=\"margin-bottom: 70px;\">\n\t\t\t\t\t<SmsSettings/>\n\t\t\t\t</div>\n\n\t\t\t\t<ButtonsPanel/>\n\t\t\t"
	      }));
	      babelHelpers.classPrivateFieldGet(this, _application).use(this.store);
	      babelHelpers.classPrivateFieldGet(this, _application).mount(this.rootNode);
	    }
	  }]);
	  return App;
	}();
	function _initStore2(terminalSettings) {
	  var terminalSettingsStore = {
	    state: function state() {
	      return _objectSpread$5(_objectSpread$5({}, terminalSettings), {}, {
	        isSaving: false,
	        changedValues: {}
	      });
	    },
	    getters: {
	      isChanged: function isChanged(state) {
	        return Object.keys(state.changedValues).length > 0;
	      },
	      isSmsSendingActive: function isSmsSendingActive(state) {
	        if (state.changedValues.isSmsSendingEnabled !== undefined) {
	          return state.changedValues.isSmsSendingEnabled;
	        }
	        return state.isSmsSendingEnabled;
	      },
	      isAnyServiceEnabled: function isAnyServiceEnabled(state) {
	        return state.activeSmsServices.length > 0 || state.isNotificationsEnabled;
	      },
	      isNotificationsEnabled: function isNotificationsEnabled(state) {
	        return state.isNotificationsEnabled;
	      },
	      getPaymentSlipLinkScheme: function getPaymentSlipLinkScheme(state) {
	        return state.paymentSlipLinkScheme;
	      },
	      getNotificationsLink: function getNotificationsLink(state) {
	        if (main_core.Type.isObject(state.connectNotificationsLink)) {
	          return state.connectNotificationsLink;
	        }
	        return null;
	      },
	      isNotificationAvailableToConnect: function isNotificationAvailableToConnect(state) {
	        return this.getNotificationsLink(state) !== null;
	      },
	      getServiceLink: function getServiceLink(state) {
	        if (main_core.Type.isString(state.connectServiceLink)) {
	          return state.connectServiceLink;
	        }
	        return '';
	      },
	      getSelectedService: function getSelectedService(state) {
	        if (main_core.Type.isString(state.changedValues.selectedServiceId)) {
	          return state.activeSmsServices.find(function (element) {
	            return element.ID === state.changedValues.selectedServiceId;
	          });
	        }
	        return state.activeSmsServices.find(function (element) {
	          return element.SELECTED;
	        });
	      },
	      getIsAnyPaysystemEnabled: function getIsAnyPaysystemEnabled(state, getters) {
	        var hasEnabledPaysystem = false;
	        var _iterator = _createForOfIteratorHelper(getters.getAvailablePaysystems),
	          _step;
	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var paysystem = _step.value;
	            if (!getters.getTerminalDisabledPaysystems.includes(paysystem.id)) {
	              hasEnabledPaysystem = true;
	              break;
	            }
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	        return getters.getIsSbpEnabled || getters.getIsSberQrEnabled || getters.getIsLinkPaymentEnabled || hasEnabledPaysystem;
	      },
	      getActiveSmsServices: function getActiveSmsServices(state) {
	        return state.activeSmsServices;
	      },
	      getChangedValues: function getChangedValues(state) {
	        return state.changedValues;
	      },
	      isSaving: function isSaving(state) {
	        return state.isSaving;
	      },
	      getAvailablePaysystems: function getAvailablePaysystems(state) {
	        return state.availablePaysystems;
	      },
	      getTerminalDisabledPaysystems: function getTerminalDisabledPaysystems(state) {
	        return state.terminalDisabledPaysystems;
	      },
	      getIsLinkPaymentEnabled: function getIsLinkPaymentEnabled(state) {
	        return state.isLinkPaymentEnabled;
	      },
	      getPaysystemPanelPath: function getPaysystemPanelPath(state) {
	        return state.paysystemPanelPath;
	      },
	      getIsAnyPaysystemActive: function getIsAnyPaysystemActive(state) {
	        return state.isAnyPaysystemActive;
	      },
	      getIsSbpEnabled: function getIsSbpEnabled(state) {
	        return state.isSbpEnabled;
	      },
	      getSbpConnectPath: function getSbpConnectPath(state) {
	        return state.sbpConnectPath;
	      },
	      getIsSbpConnected: function getIsSbpConnected(state) {
	        return state.isSbpConnected;
	      },
	      getIsSberQrEnabled: function getIsSberQrEnabled(state) {
	        return state.isSberQrEnabled;
	      },
	      getSberQrConnectPath: function getSberQrConnectPath(state) {
	        return state.sberQrConnectPath;
	      },
	      getIsSberQrConnected: function getIsSberQrConnected(state) {
	        return state.isSberQrConnected;
	      },
	      getIsPaysystemsCollapsed: function getIsPaysystemsCollapsed(state) {
	        return state.isPaysystemsCollapsed;
	      },
	      getPaysystemsArticleUrl: function getPaysystemsArticleUrl(state) {
	        return state.paysystemsArticleUrl;
	      },
	      getIsSmsCollapsed: function getIsSmsCollapsed(state) {
	        return state.isSmsCollapsed;
	      },
	      getIsRuZone: function getIsRuZone(state) {
	        return state.isRuZone;
	      },
	      getIsPhoneConfirmed: function getIsPhoneConfirmed(state) {
	        return state.isPhoneConfirmed;
	      },
	      getConnectedSiteId: function getConnectedSiteId(state) {
	        return state.connectedSiteId;
	      },
	      getIsConnectedSitePublished: function getIsConnectedSitePublished(state) {
	        return state.isConnectedSitePublished;
	      },
	      getIsConnectedSiteExists: function getIsConnectedSiteExists(state) {
	        return state.isConnectedSiteExists;
	      }
	    },
	    mutations: {
	      setSmsSendingActive: function setSmsSendingActive(state, value) {
	        if (state.changedValues.isSmsSendingEnabled !== undefined && value === state.isSmsSendingEnabled) {
	          delete state.changedValues.isSmsSendingEnabled;
	        } else {
	          state.isChanged = true;
	          state.changedValues.isSmsSendingEnabled = value;
	        }
	      },
	      selectSMSService: function selectSMSService(state, value) {
	        if (state.activeSmsServices.find(function (element) {
	          return element.SELECTED;
	        }).ID === value) {
	          delete state.changedValues.selectedServiceId;
	        } else {
	          state.changedValues.selectedServiceId = value;
	        }
	      },
	      setTerminalPaysystemDisabled: function setTerminalPaysystemDisabled(state, paysystemId) {
	        if (!Array.isArray(state.changedValues.terminalDisabledPaysystems)) {
	          state.changedValues.terminalDisabledPaysystems = state.terminalDisabledPaysystems;
	        }
	        if (state.changedValues.terminalDisabledPaysystems.includes(paysystemId)) {
	          var _state$changedValues$;
	          var currentDisabledPs = (_state$changedValues$ = state.changedValues.terminalDisabledPaysystems) !== null && _state$changedValues$ !== void 0 ? _state$changedValues$ : [];
	          state.changedValues.terminalDisabledPaysystems = currentDisabledPs.filter(function (ps) {
	            return ps !== paysystemId;
	          });
	          state.terminalDisabledPaysystems = state.changedValues.terminalDisabledPaysystems;
	          if (state.changedValues.terminalDisabledPaysystems.length === 0) {
	            state.changedValues.terminalPaysystemsAllEnabled = true;
	            state.terminalPaysystemsAllEnabled = true;
	          }
	          state.isChanged = true;
	        } else {
	          state.isChanged = true;
	          if (state.changedValues.terminalDisabledPaysystems) {
	            state.changedValues.terminalDisabledPaysystems.push(paysystemId);
	            state.terminalDisabledPaysystems.push(paysystemId);
	          } else {
	            state.changedValues.terminalDisabledPaysystems = [paysystemId];
	            state.terminalDisabledPaysystems = [paysystemId];
	          }
	          delete state.changedValues.terminalPaysystemsAllEnabled;
	          delete state.terminalPaysystemsAllEnabled;
	        }
	      },
	      setRequiredPaysystemDisabled: function setRequiredPaysystemDisabled(state, paysystemCode) {
	        var paysystemKey;
	        switch (paysystemCode) {
	          case RequiredPaysystemCodes.sbp:
	            paysystemKey = 'isSbpEnabled';
	            break;
	          case RequiredPaysystemCodes.sberQr:
	            paysystemKey = 'isSberQrEnabled';
	            break;
	          default:
	            paysystemKey = null;
	        }
	        if (!paysystemKey) {
	          return;
	        }
	        state[paysystemKey] = !state[paysystemKey];
	        state.changedValues[paysystemKey] = state[paysystemKey];
	      },
	      setLinkPaymentEnabled: function setLinkPaymentEnabled(state, value) {
	        if (state.changedValues.isLinkPaymentEnabled !== undefined && value === state.isLinkPaymentEnabled) {
	          delete state.changedValues.isLinkPaymentEnabled;
	        } else {
	          state.isChanged = true;
	          state.changedValues.isLinkPaymentEnabled = value;
	          state.isLinkPaymentEnabled = value;
	        }
	      },
	      setSaving: function setSaving(state, value) {
	        if (main_core.Type.isBoolean(value)) {
	          state.isSaving = value;
	        }
	      },
	      updateServicesList: function updateServicesList(state, value) {
	        state.activeSmsServices = value;
	      },
	      updateNotificationsEnabled: function updateNotificationsEnabled(state, value) {
	        state.isNotificationsEnabled = value;
	      },
	      updateSbpConnectPath: function updateSbpConnectPath(state, value) {
	        state.sbpConnectPath = value;
	      },
	      updateSberQrConnectPath: function updateSberQrConnectPath(state, value) {
	        state.sberQrConnectPath = value;
	      },
	      updateIsSbpConnected: function updateIsSbpConnected(state, value) {
	        state.isSbpConnected = value;
	      },
	      updateIsSberQrConnected: function updateIsSberQrConnected(state, value) {
	        state.isSberQrConnected = value;
	      },
	      updateIsPaysystemsCollapsed: function updateIsPaysystemsCollapsed(state, value) {
	        state.isPaysystemsCollapsed = value;
	      },
	      updateIsSmsCollapsed: function updateIsSmsCollapsed(state, value) {
	        state.isSmsCollapsed = value;
	      },
	      updateIsAnyPaysystemActive: function updateIsAnyPaysystemActive(state, value) {
	        state.isAnyPaysystemActive = value;
	      },
	      updateAvailablePaysystems: function updateAvailablePaysystems(state, value) {
	        state.availablePaysystems = value;
	      },
	      updateIsPhoneConfirmed: function updateIsPhoneConfirmed(state, value) {
	        state.isPhoneConfirmed = value;
	      },
	      updateConnectedSiteId: function updateConnectedSiteId(state, value) {
	        state.connectedSiteId = value;
	      },
	      updateIsConnectedSitePublished: function updateIsConnectedSitePublished(state, value) {
	        state.isConnectedSitePublished = value;
	      },
	      updateIsConnectedSiteExists: function updateIsConnectedSiteExists(state, value) {
	        state.isConnectedSiteExists = value;
	      }
	    }
	  };
	  return ui_vue3_vuex.createStore(terminalSettingsStore);
	}

	exports.App = App;

}((this.BX.Crm.Config.Terminal = this.BX.Crm.Config.Terminal || {}),BX.Vue3,BX.UI,BX.Main,BX,BX.Bitrix24,BX.UI.Dialogs,BX.UI,BX,BX.Landing,BX.Landing,BX.Vue3.Vuex));
//# sourceMappingURL=terminal.bundle.js.map
