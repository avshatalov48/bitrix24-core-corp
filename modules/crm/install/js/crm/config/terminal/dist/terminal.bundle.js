this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Config = this.BX.Crm.Config || {};
(function (exports,ui_vue3,ui_switcher,main_core,main_popup,ui_vue3_vuex) {
	'use strict';

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
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div class=\"ui-slider-heading-4 settings-section-header\">\n\t\t\t\t<div v-if=\"switchable\">\n\t\t\t\t\t<span ref=\"switcher\" class=\"ui-switcher\"></span>\n\t\t\t\t</div>\n\t\t\t\t<div ref=\"title\">\n\t\t\t\t\t{{ title }}\n\t\t\t\t\t<span\n\t\t\t\t\t\tv-if=\"hint !== ''\"\n\t\t\t\t\t\tclass=\"ui-hint\"\n\t\t\t\t\t\tdata-hint-html\n\t\t\t\t\t\tdata-hint-interactivity\n\t\t\t\t\t\t:data-hint=\"hint\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<span class=\"ui-hint-icon\"></span>\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t</div>\n\t\t\t<div v-if=\"isEnabled\">\n\t\t\t\t<slot></slot>\n\t\t\t</div>\n\t\t</div>\n\t"
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
	    document.addEventListener('click', function () {
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
	        requestMethod: "get",
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
	      } else {
	        return 'sms-provider-selector-list';
	      }
	    }
	  }),
	  // language=Vue
	  template: "\n\t\t<div style=\"display: inline-block; vertical-align: top\">\n\t\t\t<span class=\"sms-provider-selector\" @click.stop=\"switchVisibility\">{{getSelectedService['NAME']}}</span>\n\t\t\t<ul :class=\"getListClassname\" @click.stop>\n\t\t\t\t<li v-for=\"provider in getActiveSmsServices()\" @click=\"switchService(provider['ID'])\" v-show=\"provider['ID'] !== getSelectedService['ID']\">{{ provider['NAME'] }}</li>\n\t\t\t\t<li @click=\"openSmsServicesSlider\">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_MORE') }}</li>\n\t\t\t</ul>\n\t\t</div>\n\t"
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
	    'SettingsSection': SettingsSection,
	    'Sms': Sms,
	    'SmsProviderSelect': SmsProviderSelect,
	    'Notification': Notification
	  },
	  computed: _objectSpread$1(_objectSpread$1({}, ui_vue3_vuex.mapGetters(['isSmsSendingActive', 'isAnyServiceEnabled', 'isNotificationsEnabled'])), {}, {
	    getSectionTitle: function getSectionTitle() {
	      return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_TITLE');
	    },
	    getSectionHint: function getSectionHint() {
	      var replacements = {
	        '#LINK_START#': "<a onclick=\"top.BX.Helper.show('redirect=detail&code=17399056')\" style=\"cursor: pointer\">",
	        '#LINK_END#': "</a>"
	      };
	      return this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_HINT_TEXT', replacements);
	    },
	    getNotificationConnectHint: function getNotificationConnectHint() {
	      var replacements = {
	        '#LINK_START#': "<span onclick=\"top.BX.Helper.show('redirect=detail&code=17399068')\" class=\"sms-provider-selector\">",
	        '#LINK_END#': "</span>"
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
	      });
	    },
	    onSectionToggled: function onSectionToggled() {
	      this.setSmsSendingActive(!this.isSmsSendingActive);
	    },
	    getSmsMessage: function getSmsMessage() {
	      var link = "<span class=\"sms-link-path\">".concat(this.getPaymentSlipLinkScheme(), "</span><span class=\"sms-link-plug\">xxxxx</span>") + " ";
	      var text = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_MESSAGE_TEMPLATE');
	      return main_core.Text.encode(text).replace(/#PAYMENT_SLIP_LINK#/g, link);
	    },
	    getSmsProviderMessage: function getSmsProviderMessage() {
	      var providerSelect = "<span>Dummy SMS</span>";
	      var text = this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_SELECT');
	      return main_core.Text.encode(text).replace(/#SMS_SERVICE#/g, providerSelect);
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
	      /** @var {NotificationLink} */
	      var notificationLink = this.getNotificationsLink();
	      if (notificationLink === null) {
	        return;
	      }
	      if (notificationLink.type === 'connect_link') {
	        var options = {
	          cacheable: false,
	          allowChangeHistory: false,
	          requestMethod: "get",
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
	        requestMethod: "get",
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
	  template: "\n\t\t<SettingsSection \n\t\t\t:title=\"getSectionTitle\"\n\t\t\t:switchable=\"true\"\n\t\t\tv-on:toggle=\"onSectionToggled\"\n\t\t\t:active=\"isSmsSendingActive\"\n\t\t\t:hint=\"getSectionHint\"\n\t\t>\n\t\t\t<div v-if=\"isAnyServiceEnabled\">\n\t\t\t\t<div class=\"sms-message-info\">\n\t\t\t\t\t<span class=\"sms-message-info-text\">{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_INFO') }}</span>\n\t\t\t\t\t<Sms>\n\t\t\t\t\t\t<span \n\t\t\t\t\t\t\tv-html=\"getSmsMessage()\"\n\t\t\t\t\t\t\tv-on:mouseenter=\"onSmsMouseenter($event)\"\n\t\t\t\t\t\t\tv-on:mouseleave=\"onSmsMouseleave\"\n\t\t\t\t\t\t></span>\n\t\t\t\t\t</Sms>\n\t\t\t\t\t<div class=\"sms-provider-selector-block\">\n\t\t\t\t\t\t<div \n\t\t\t\t\t\t\tv-html=\"getNotificationConnectHint\"\n\t\t\t\t\t\t\tv-if=\"isNotificationsEnabled\"\n\t\t\t\t\t\t></div>\n\t\t\t\t\t\t<div v-else>\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_SELECT') }}\n\t\t\t\t\t\t\t<SmsProviderSelect v-on:onConnectSliderClosed=\"onServiceConnectSliderClosed\"/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-else>\n\t\t\t\t<span class=\"sms-provider-empty-provider-list-text\" v-if=\"(getNotificationsLink() !== null) && (getServiceLink() !== '')\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_ONBOARDING_TEXT') }}\n\t\t\t\t</span>\n\t\t\t\t<span class=\"sms-provider-empty-provider-list-text\" v-else-if=\"(getNotificationsLink() === null) && (getServiceLink() !== '')\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_ONBOARDING_ONLY_SMS_SERVICES_TEXT') }}\n\t\t\t\t</span>\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-primary\" @click=\"onNotificationsConnectLinkClick\" v-if=\"getNotificationsLink() !== null\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_UNC_CONNECT_BTN') }}\n\t\t\t\t</button>\n\t\t\t\t\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-light-border\" @click=\"onProviderSmsNotificationClick\" v-if=\"(getNotificationsLink() !== null) && (getServiceLink() !== '')\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_BTN') }}\n\t\t\t\t</button>\n\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-primary\" @click=\"onProviderSmsNotificationClick\" v-else-if=\"(getNotificationsLink() === null) && (getServiceLink() !== '')\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SECTION_SMS_SERVICE_PROVIDER_CONNECT_BTN') }}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t</SettingsSection>\n\t"
	};

	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var TerminalSettings = {
	  components: {
	    'SmsSettingsSection': SmsSettingsSection
	  },
	  computed: _objectSpread$2(_objectSpread$2({}, ui_vue3_vuex.mapGetters({
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
	  methods: {
	    save: function save() {
	      this.$emit('onSave');
	    },
	    cancel: function cancel() {
	      this.$emit('onCancel');
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"settings-container\">\n\t\t\t<!-- Settings title -->\n\t\t\t<div class=\"ui-slider-heading-4\" style=\"margin-bottom: 40px\">\n\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SUBTITLE_NOTIFICATION') }}\n\t\t\t</div>\n\t\t\t\n\t\t\t<!-- Settings content -->\n\t\t\t<div class=\"settings-section-list\">\n\t\t\t\t<SmsSettingsSection />\n\t\t\t</div>\n\t\t\t\n\t\t\t\n\t\t\t<!-- Save panel -->\n\t\t\t<div\n\t\t\t\t:class=\"buttonsPanelClass\"\n\t\t\t>\n\t\t\t\t<div class=\"ui-button-panel ui-button-panel-align-center\">\n\t\t\t\t\t<button\n\t\t\t\t\t\t@click=\"save\"\n\t\t\t\t\t\t:class=\"saveButtonClasses\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVE_BTN') }}\n\t\t\t\t\t</button>\n\t\t\t\t\t<a\n\t\t\t\t\t\t@click=\"cancel\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-link\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_CANCEL_BTN') }}\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function ownKeys$3(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$3(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$3(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$3(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
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
	          'TerminalSettings': TerminalSettings
	        },
	        computed: _objectSpread$3({}, ui_vue3_vuex.mapGetters(['isChanged', 'isSaving', 'getChangedValues'])),
	        methods: _objectSpread$3(_objectSpread$3({}, ui_vue3_vuex.mapMutations(['setSaving'])), {}, {
	          onSettingsSave: function onSettingsSave() {
	            var _this = this;
	            if (!this.isChanged || this.isSaving) {
	              return;
	            }
	            this.setSaving(true);
	            main_core.ajax.runComponentAction('bitrix:crm.config.terminal.settings', 'saveSettings', {
	              data: {
	                changedValues: this.getChangedValues
	              }
	            }).then(function () {
	              _this.setSaving(false);
	              BX.SidePanel.Instance.close();
	            }, function () {
	              _this.setSaving(false);
	              BX.UI.Notification.Center.notify({
	                content: _this.$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_ON_SAVE_ERROR'),
	                width: 350,
	                autoHideDelay: 4000
	              });
	            });
	          },
	          onSettingsCancel: function onSettingsCancel() {
	            BX.SidePanel.Instance.close();
	          }
	        }),
	        //language=Vue
	        template: "\n\t\t\t\t<TerminalSettings @onSave=\"onSettingsSave\" @onCancel=\"onSettingsCancel\"/>\n\t\t\t"
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
	      return _objectSpread$3(_objectSpread$3({}, terminalSettings), {}, {
	        isSaving: false,
	        changedValues: {}
	      });
	    },
	    getters: {
	      isChanged: function isChanged(state) {
	        return Object.keys(state.changedValues).length !== 0;
	      },
	      isSmsSendingActive: function isSmsSendingActive(state) {
	        if (state.changedValues.isSmsSendingEnabled !== undefined) {
	          return state.changedValues.isSmsSendingEnabled;
	        } else {
	          return state.isSmsSendingEnabled;
	        }
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
	            return element['ID'] === state.changedValues.selectedServiceId;
	          });
	        }
	        return state.activeSmsServices.find(function (element) {
	          return element['SELECTED'];
	        });
	      },
	      getActiveSmsServices: function getActiveSmsServices(state) {
	        return state.activeSmsServices;
	      },
	      getChangedValues: function getChangedValues(state) {
	        return state.changedValues;
	      },
	      isSaving: function isSaving(state) {
	        return state.isSaving;
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
	          return element['SELECTED'];
	        })['ID'] === value) {
	          delete state.changedValues.selectedServiceId;
	        } else {
	          state.changedValues.selectedServiceId = value;
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
	      }
	    }
	  };
	  return ui_vue3_vuex.createStore(terminalSettingsStore);
	}

	exports.App = App;

}((this.BX.Crm.Config.Terminal = this.BX.Crm.Config.Terminal || {}),BX.Vue3,BX,BX,BX.Main,BX.Vue3.Vuex));
//# sourceMappingURL=terminal.bundle.js.map
