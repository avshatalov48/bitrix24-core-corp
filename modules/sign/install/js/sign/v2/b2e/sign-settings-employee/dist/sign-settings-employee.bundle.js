/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_cache,sign_v2_analytics,sign_v2_b2e_startProcess,sign_v2_b2e_submitDocumentInfo,sign_v2_helper,ui_wizard,main_loader,main_core_events) {
	'use strict';

	var noTemplatesStateImage = "/bitrix/js/sign/v2/b2e/sign-settings-employee/dist/images/no-templates-state-image.svg";

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	const emptyStateHelpdeskCode = '23174934';
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _containerId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("containerId");
	var _wizard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wizard");
	var _startProcess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startProcess");
	var _stepsContext = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stepsContext");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _createHead = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createHead");
	var _getStartProcessStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStartProcessStep");
	var _getSubmitDocumentInfoStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSubmitDocumentInfoStep");
	var _getStepsMetadata = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStepsMetadata");
	var _getLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLayout");
	var _getZeroTemplatesEmptyState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getZeroTemplatesEmptyState");
	var _enableNoStepMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableNoStepMode");
	var _disableNoStepMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableNoStepMode");
	var _subscribeOnEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEvents");
	class B2EEmployeeSignSettings {
	  constructor(containerId = '', analyticsContext = {}) {
	    Object.defineProperty(this, _subscribeOnEvents, {
	      value: _subscribeOnEvents2
	    });
	    Object.defineProperty(this, _disableNoStepMode, {
	      value: _disableNoStepMode2
	    });
	    Object.defineProperty(this, _enableNoStepMode, {
	      value: _enableNoStepMode2
	    });
	    Object.defineProperty(this, _getZeroTemplatesEmptyState, {
	      value: _getZeroTemplatesEmptyState2
	    });
	    Object.defineProperty(this, _getLayout, {
	      value: _getLayout2
	    });
	    Object.defineProperty(this, _getStepsMetadata, {
	      value: _getStepsMetadata2
	    });
	    Object.defineProperty(this, _getSubmitDocumentInfoStep, {
	      value: _getSubmitDocumentInfoStep2
	    });
	    Object.defineProperty(this, _getStartProcessStep, {
	      value: _getStartProcessStep2
	    });
	    Object.defineProperty(this, _createHead, {
	      value: _createHead2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _containerId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _wizard, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _startProcess, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _stepsContext, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _containerId)[_containerId] = containerId;
	    const currentSlider = BX.SidePanel.Instance.getTopSlider();
	    babelHelpers.classPrivateFieldLooseBase(this, _startProcess)[_startProcess] = new sign_v2_b2e_startProcess.StartProcess();
	    babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard] = new ui_wizard.Wizard(babelHelpers.classPrivateFieldLooseBase(this, _getStepsMetadata)[_getStepsMetadata](this), {
	      back: {
	        className: 'ui-btn-light-border'
	      },
	      next: {
	        className: 'ui-btn-success'
	      },
	      complete: {
	        className: 'ui-btn-success',
	        title: main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_COMPLETE_TITLE'),
	        onComplete: () => currentSlider == null ? void 0 : currentSlider.close()
	      },
	      swapButtons: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = new sign_v2_analytics.Analytics({
	      contextOptions: analyticsContext
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeOnEvents)[_subscribeOnEvents]();
	  }
	  async render() {
	    const container = document.getElementById(babelHelpers.classPrivateFieldLooseBase(this, _containerId)[_containerId]);
	    if (container === null) {
	      return;
	    }
	    this.renderToContainer(container);
	  }
	  async renderToContainer(container) {
	    if (main_core.Type.isNull(container)) {
	      return;
	    }
	    const loader = new main_loader.Loader({
	      target: container
	    });
	    void loader.show();
	    const templates = await babelHelpers.classPrivateFieldLooseBase(this, _startProcess)[_startProcess].getTemplates();
	    if (templates.length === 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	        event: 'show_empty_state',
	        c_element: 'create_button'
	      });
	      main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getZeroTemplatesEmptyState)[_getZeroTemplatesEmptyState](), container);
	      void loader.hide();
	      return;
	    }
	    void loader.hide();
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getLayout)[_getLayout](), container);
	    babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].moveOnStep(0);
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	      event: 'click_create_document',
	      c_element: 'create_button'
	    });
	  }
	  clearCache() {
	    babelHelpers.classPrivateFieldLooseBase(this, _startProcess)[_startProcess].resetCache();
	    babelHelpers.classPrivateFieldLooseBase(this, _stepsContext)[_stepsContext] = {};
	  }
	}
	function _createHead2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('headLayout', () => {
	    const {
	      root,
	      titleHelp
	    } = main_core.Tag.render(_t || (_t = _`
				<div class="sign-settings__head">
					<div>
						<p class="sign-settings__head_title">
							${0}
						</p>
						<p class="sign-settings__head_title --sub">
							<span>${0}</span>
							<a ref="titleHelp" class="sign-settings__head_title-help">
								${0}
							</a>
						</p>
					</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE'), main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB'), main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB_HELP'));
	    sign_v2_helper.Helpdesk.bindHandler(titleHelp, '23052076');
	    return root;
	  });
	}
	function _getStartProcessStep2(signSettings) {
	  const startProcess = babelHelpers.classPrivateFieldLooseBase(signSettings, _startProcess)[_startProcess];
	  return {
	    get content() {
	      if (main_core.Type.isUndefined(babelHelpers.classPrivateFieldLooseBase(signSettings, _stepsContext)[_stepsContext].selectedTemplateUid)) {
	        babelHelpers.classPrivateFieldLooseBase(signSettings, _wizard)[_wizard].toggleBtnActiveState('next', true);
	        if (main_core.Type.isStringFilled(startProcess.getSelectedTemplateUid())) {
	          babelHelpers.classPrivateFieldLooseBase(signSettings, _wizard)[_wizard].toggleBtnActiveState('next', false);
	        } else {
	          startProcess.subscribe(startProcess.events.onProcessTypeSelect, () => babelHelpers.classPrivateFieldLooseBase(signSettings, _wizard)[_wizard].toggleBtnActiveState('next', false));
	        }
	      }
	      const layout = startProcess.getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      babelHelpers.classPrivateFieldLooseBase(signSettings, _disableNoStepMode)[_disableNoStepMode]();
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_START_PROCESS'),
	    beforeCompletion: async () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _stepsContext)[_stepsContext].selectedTemplateUid = startProcess.getSelectedTemplateUid();
	      babelHelpers.classPrivateFieldLooseBase(this, _stepsContext)[_stepsContext].templatesList = await startProcess.getTemplates();
	      babelHelpers.classPrivateFieldLooseBase(this, _stepsContext)[_stepsContext].fields = (await startProcess.getFields(babelHelpers.classPrivateFieldLooseBase(this, _stepsContext)[_stepsContext].selectedTemplateUid)).fields;
	    }
	  };
	}
	function _getSubmitDocumentInfoStep2(signSettings) {
	  let submitDocumentInfo = null;
	  return {
	    get content() {
	      const currentTemplateSelected = babelHelpers.classPrivateFieldLooseBase(signSettings, _stepsContext)[_stepsContext].templatesList.find(template => template.uid === babelHelpers.classPrivateFieldLooseBase(signSettings, _stepsContext)[_stepsContext].selectedTemplateUid);
	      submitDocumentInfo = new sign_v2_b2e_submitDocumentInfo.SubmitDocumentInfo({
	        template: {
	          uid: currentTemplateSelected.uid,
	          title: currentTemplateSelected.title
	        },
	        company: currentTemplateSelected.company,
	        fields: babelHelpers.classPrivateFieldLooseBase(signSettings, _stepsContext)[_stepsContext].fields
	      });
	      const layout = submitDocumentInfo.getLayout();
	      if (babelHelpers.classPrivateFieldLooseBase(signSettings, _stepsContext)[_stepsContext].fields.length > 0) {
	        sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(signSettings, _enableNoStepMode)[_enableNoStepMode]();
	      }
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_SUBMIT_INFO'),
	    beforeCompletion: async () => {
	      submitDocumentInfo.subscribeOnce(submitDocumentInfo.events.documentSendedSuccessFully, event => {
	        const document = event.getData().document;
	        babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].sendWithProviderTypeAndDocId({
	          event: 'sent_document_to_sign',
	          c_element: 'create_button',
	          status: 'success'
	        }, document.id, document.providerCode);
	      });
	      let result = false;
	      try {
	        result = await submitDocumentInfo.sendForSign();
	      } catch (error) {
	        console.error(error);
	        result = false;
	        babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	          event: 'sent_document_to_sign',
	          c_element: 'create_button',
	          status: 'error'
	        });
	      }
	      return result;
	    }
	  };
	}
	function _getStepsMetadata2(signSettings) {
	  return {
	    startProcess: babelHelpers.classPrivateFieldLooseBase(this, _getStartProcessStep)[_getStartProcessStep](signSettings),
	    submitDocumentInfo: babelHelpers.classPrivateFieldLooseBase(this, _getSubmitDocumentInfoStep)[_getSubmitDocumentInfoStep](signSettings)
	  };
	}
	function _getLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('headLayout', () => {
	    return main_core.Tag.render(_t2 || (_t2 = _`
				<div class="sign-settings__scope sign-settings --b2e --employee">
					<div class="sign-settings__sidebar">
						${0}
						${0}
					</div>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _createHead)[_createHead](), babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].getLayout());
	  });
	}
	function _getZeroTemplatesEmptyState2() {
	  return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="sign-settings__scope sign-settings --b2e --employee">
				<div class="sign-settings__sidebar">
					<div class="sign-settings__empty-state">
						<div class="sign-settings__empty-state_icon">
							<img src="${0}" alt="${0}">
						</div>
						<p class="sign-settings__empty-state_title">
							${0}
						</p>
						<p class="sign-settings__empty-state_text">
							${0}
						</p>
					</div>
				</div>
			</div>
		`), noTemplatesStateImage, main_core.Loc.getMessage('SIGN_SETTINGS_EMPTY_STATE_ICON_ALT'), main_core.Loc.getMessage('SIGN_SETTINGS_EMPTY_STATE_TITLE'), sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_SETTINGS_EMPTY_STATE_DESCRIPTION'), emptyStateHelpdeskCode, sign_v2_helper.Helpdesk.defaultRedirectValue, ['sign-settings__empty-state_link']));
	}
	function _enableNoStepMode2() {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getLayout)[_getLayout](), 'no-step-mode');
	}
	function _disableNoStepMode2() {
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getLayout)[_getLayout](), 'no-step-mode');
	}
	function _subscribeOnEvents2() {
	  main_core_events.EventEmitter.subscribe('BX.Sign.SignSettingsEmployee:onBeforeTemplateSend', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].toggleBtnActiveState('back', true);
	  });
	  main_core_events.EventEmitter.subscribe('BX.Sign.SignSettingsEmployee:onAfterTemplateSend', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].toggleBtnActiveState('back', false);
	  });
	}

	exports.B2EEmployeeSignSettings = B2EEmployeeSignSettings;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Cache,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2,BX.Ui,BX,BX.Event));
//# sourceMappingURL=sign-settings-employee.bundle.js.map
