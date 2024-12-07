this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_signSettings,sign_v2_documentSetup,sign_v2_b2b_documentSend,sign_v2_b2b_requisites) {
	'use strict';

	var _requisites = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisites");
	class B2BSignSettings extends sign_v2_signSettings.SignSettings {
	  constructor(containerId, signOptions) {
	    super(containerId, signOptions);
	    Object.defineProperty(this, _requisites, {
	      writable: true,
	      value: void 0
	    });
	    const {
	      config
	    } = signOptions;
	    const {
	      blankSelectorConfig,
	      documentSendConfig
	    } = config;
	    this.documentSetup = new sign_v2_documentSetup.DocumentSetup(blankSelectorConfig);
	    this.documentSend = new sign_v2_b2b_documentSend.DocumentSend(documentSendConfig);
	    babelHelpers.classPrivateFieldLooseBase(this, _requisites)[_requisites] = new sign_v2_b2b_requisites.Requisites();
	    this.subscribeOnEvents();
	  }
	  async applyDocumentData(uid) {
	    const applied = Boolean(await this.setupDocument(uid));
	    if (!applied) {
	      return false;
	    }
	    const {
	      setupData
	    } = this.documentSetup;
	    babelHelpers.classPrivateFieldLooseBase(this, _requisites)[_requisites].documentData = setupData;
	    this.documentSend.documentData = setupData;
	    this.editor.documentData = setupData;
	    return true;
	  }
	  getStepsMetadata(signSettings) {
	    return {
	      setup: {
	        get content() {
	          return signSettings.documentSetup.layout;
	        },
	        title: main_core.Loc.getMessage('SIGN_SETTINGS_B2B_LOAD_DOCUMENT'),
	        beforeCompletion: async () => {
	          const setupData = await this.setupDocument();
	          if (!setupData) {
	            return false;
	          }
	          const {
	            uid,
	            entityId,
	            initiator
	          } = setupData;
	          babelHelpers.classPrivateFieldLooseBase(this, _requisites)[_requisites].documentData = {
	            uid,
	            entityId,
	            initiator
	          };
	          return true;
	        }
	      },
	      requisites: {
	        get content() {
	          return babelHelpers.classPrivateFieldLooseBase(signSettings, _requisites)[_requisites].getLayout();
	        },
	        title: main_core.Loc.getMessage('SIGN_SETTINGS_B2B_PREPARING_DOCUMENT'),
	        beforeCompletion: async () => {
	          const {
	            uid,
	            isTemplate,
	            title,
	            initiator
	          } = this.documentSetup.setupData;
	          const valid = babelHelpers.classPrivateFieldLooseBase(this, _requisites)[_requisites].checkInitiator(initiator);
	          if (!valid) {
	            return false;
	          }
	          const entityData = await babelHelpers.classPrivateFieldLooseBase(this, _requisites)[_requisites].processMembers();
	          if (!entityData) {
	            return false;
	          }
	          const blocks = await this.documentSetup.loadBlocks(uid);
	          this.editor.documentData = {
	            isTemplate,
	            uid,
	            blocks
	          };
	          this.editor.entityData = entityData;
	          this.documentSend.documentData = {
	            uid,
	            title,
	            blocks,
	            initiator
	          };
	          this.documentSend.entityData = entityData;
	          await this.editor.waitForPagesUrls();
	          await this.editor.renderDocument();
	          this.wizard.toggleBtnLoadingState('next', false);
	          await this.editor.show();
	          return true;
	        }
	      },
	      send: {
	        get content() {
	          return signSettings.documentSend.getLayout();
	        },
	        title: main_core.Loc.getMessage('SIGN_SETTINGS_SEND_DOCUMENT'),
	        beforeCompletion: () => {
	          return this.documentSend.sendForSign();
	        }
	      }
	    };
	  }
	  subscribeOnEvents() {
	    super.subscribeOnEvents();
	    babelHelpers.classPrivateFieldLooseBase(this, _requisites)[_requisites].subscribe('changeInitiator', ({
	      data
	    }) => {
	      this.documentSetup.setupData = {
	        ...this.documentSetup.setupData,
	        initiator: data.initiator
	      };
	    });
	  }
	}

	exports.B2BSignSettings = B2BSignSettings;

}((this.BX.Sign.V2.B2b = this.BX.Sign.V2.B2b || {}),BX,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2));
//# sourceMappingURL=sign-settings.bundle.js.map
