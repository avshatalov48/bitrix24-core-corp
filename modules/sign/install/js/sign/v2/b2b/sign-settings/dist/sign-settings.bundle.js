/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_b2b_documentSend,sign_v2_b2b_requisites,sign_v2_documentSetup,sign_v2_signSettings) {
	'use strict';

	var _requisites = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requisites");
	var _decorateStepsBeforeCompletionWithAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("decorateStepsBeforeCompletionWithAnalytics");
	var _sendAnalyticsOnStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnStart");
	var _sendAnalyticsOnDocumentApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticsOnDocumentApply");
	class B2BSignSettings extends sign_v2_signSettings.SignSettings {
	  constructor(containerId, signOptions) {
	    super(containerId, signOptions);
	    Object.defineProperty(this, _sendAnalyticsOnDocumentApply, {
	      value: _sendAnalyticsOnDocumentApply2
	    });
	    Object.defineProperty(this, _sendAnalyticsOnStart, {
	      value: _sendAnalyticsOnStart2
	    });
	    Object.defineProperty(this, _decorateStepsBeforeCompletionWithAnalytics, {
	      value: _decorateStepsBeforeCompletionWithAnalytics2
	    });
	    Object.defineProperty(this, _requisites, {
	      writable: true,
	      value: void 0
	    });
	    const {
	      config,
	      chatId = 0
	    } = signOptions;
	    const {
	      blankSelectorConfig,
	      documentSendConfig
	    } = config;
	    blankSelectorConfig.chatId = chatId;
	    this.documentSetup = new sign_v2_documentSetup.DocumentSetup(blankSelectorConfig);
	    this.documentSend = new sign_v2_b2b_documentSend.DocumentSend(documentSendConfig);
	    babelHelpers.classPrivateFieldLooseBase(this, _requisites)[_requisites] = new sign_v2_b2b_requisites.Requisites();
	    this.isB2bSignMaster = true;
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
	    this.editor.documentData = setupData;
	    babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnDocumentApply)[_sendAnalyticsOnDocumentApply](setupData.id);
	    this.documentsGroup.set(setupData.uid, setupData);
	    return true;
	  }
	  getStepsMetadata(signSettings, documentUid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticsOnStart)[_sendAnalyticsOnStart](documentUid);
	    const steps = {
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
	          this.setSingleDocument(setupData);
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
	            initiator,
	            initiatedByType
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
	          this.editor.setSenderType(initiatedByType);
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
	    babelHelpers.classPrivateFieldLooseBase(this, _decorateStepsBeforeCompletionWithAnalytics)[_decorateStepsBeforeCompletionWithAnalytics](steps);
	    return steps;
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
	function _decorateStepsBeforeCompletionWithAnalytics2(steps) {
	  const analytics = this.getAnalytics();
	  steps.send.beforeCompletion = sign_v2_signSettings.decorateResultBeforeCompletion(steps.send.beforeCompletion, () => {
	    analytics.sendWithDocId({
	      event: 'sent_document_to_sign',
	      status: 'success'
	    }, this.documentSend.documentData.uid);
	  }, () => {
	    analytics.send({
	      event: 'sent_document_to_sign',
	      status: 'error'
	    });
	  });
	}
	function _sendAnalyticsOnStart2() {
	  const analytics = this.getAnalytics();
	  if (!this.isEditMode()) {
	    analytics.send({
	      event: 'click_create_document'
	    });
	  }
	}
	function _sendAnalyticsOnDocumentApply2(documentId) {
	  this.getAnalytics().sendWithDocId({
	    event: 'click_create_document'
	  }, documentId);
	}

	exports.B2BSignSettings = B2BSignSettings;

}((this.BX.Sign.V2.B2b = this.BX.Sign.V2.B2b || {}),BX,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2));
//# sourceMappingURL=sign-settings.bundle.js.map
