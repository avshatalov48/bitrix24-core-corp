/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core_events,ui_vue3,sign_v2_b2e_signSettingsTemplates,sign_v2_b2e_vueUtil,ui_progressbar,ui_vue3_pinia,ui_vue3_components_popup) {
	'use strict';

	// @vue/component
	const TemplateSendApp = {
	  name: 'TemplateSendApp',
	  components: {
	    Popup: ui_vue3_components_popup.Popup
	  },
	  mixins: [sign_v2_b2e_vueUtil.LocMixin],
	  computed: {
	    progress() {
	      return ui_vue3_pinia.mapState(sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore, ['sendProgress']).sendProgress();
	    },
	    configured() {
	      return ui_vue3_pinia.mapState(sign_v2_b2e_signSettingsTemplates.useDocumentTemplateFillingStore, ['configured']).configured();
	    },
	    title() {
	      return this.loc('SIGN_DOCUMENT_TEMPLATE_SEND_TITLE');
	    },
	    description() {
	      return this.configured ? this.loc('SIGN_DOCUMENT_TEMPLATE_SEND_DESCRIPTION_ALLOW_CLOSE') : this.loc('SIGN_DOCUMENT_TEMPLATE_SEND_DESCRIPTION');
	    }
	  },
	  watch: {
	    progress: {
	      handler(newValue) {
	        this.progressBar.update(newValue);
	      }
	    }
	  },
	  created() {
	    this.progressBar = new ui_progressbar.ProgressBar({
	      maxValue: 100,
	      value: 0,
	      colorTrack: '#dfe3e6'
	    });
	  },
	  mounted() {
	    this.progressBar.renderTo(this.$refs.progressContainer);
	  },
	  methods: {
	    onClose() {
	      this.$Bitrix.eventEmitter.emit('sign:document-template-send:close');
	    }
	  },
	  template: `
		<div class="send-b2e-overlay">
			<div class="sign-b2e-overlay-content">
				<div class="sign-b2e-overlay__animate-layout">
					<div class="sign-b2e-overlay__overlap-docs">
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
					</div>
					<div class="sign-b2e-overlay__overlap-docs">
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
						<div class="sign-b2e-overlay__overlap-doc"></div>
					</div>
				</div>
				<div class="sign-b2e-overlay-progress-title">
					{{ title }}
				</div>
				<div class="sign-b2e-overlay-close-description">
					{{ description }}
				</div>
				<button v-if="configured" class="ui-btn ui-btn-md ui-btn-light-border ui-btn-round" @click="onClose">
					{{ loc('SIGN_DOCUMENT_TEMPLATE_SEND_CLOSE') }}
				</button>
			</div>
			<div class="send-b2e-progress-container" ref="progressContainer"></div>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _vueApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("vueApp");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _store = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("store");
	var _createApp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createApp");
	class DocumentTemplateSend extends main_core_events.EventEmitter {
	  constructor(store) {
	    super();
	    Object.defineProperty(this, _createApp, {
	      value: _createApp2
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _vueApp, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _store, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.V2.B2e.DocumentTemplateSend');
	    babelHelpers.classPrivateFieldLooseBase(this, _store)[_store] = store;
	  }
	  getLayout() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = BX.Tag.render(_t || (_t = _`<div class="sign-settings_templates_send"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _createApp)[_createApp](babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  unmount() {
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].unmount();
	  }
	}
	function _createApp2(container) {
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(TemplateSendApp, {});
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].use(babelHelpers.classPrivateFieldLooseBase(this, _store)[_store]);
	  babelHelpers.classPrivateFieldLooseBase(this, _vueApp)[_vueApp] = babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(container);
	  babelHelpers.classPrivateFieldLooseBase(this, _vueApp)[_vueApp].$Bitrix.eventEmitter.subscribe('sign:document-template-send:close', () => this.emit('close'));
	}

	exports.DocumentTemplateSend = DocumentTemplateSend;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Event,BX.Vue3,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.UI,BX.Vue3.Pinia,BX.UI.Vue3.Components));
//# sourceMappingURL=document-template-send.bundle.js.map
