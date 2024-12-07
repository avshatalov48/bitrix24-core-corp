/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_b2e_startProcess,sign_v2_b2e_submitDocumentInfo,ui_wizard,sign_v2_helper) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _containerId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("containerId");
	var _wizard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wizard");
	var _startProcess = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startProcess");
	var _stepsContext = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stepsContext");
	var _createHead = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createHead");
	var _getStartProcessStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStartProcessStep");
	var _getSubmitDocumentInfoStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSubmitDocumentInfoStep");
	var _getStepsMetadata = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStepsMetadata");
	var _getLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLayout");
	class B2EEmployeeSignSettings {
	  constructor(containerId) {
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
	    babelHelpers.classPrivateFieldLooseBase(this, _containerId)[_containerId] = containerId;
	    const currentSlider = BX.SidePanel.Instance.getTopSlider();
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
	    babelHelpers.classPrivateFieldLooseBase(this, _startProcess)[_startProcess] = new sign_v2_b2e_startProcess.StartProcess();
	  }
	  render() {
	    const container = document.getElementById(babelHelpers.classPrivateFieldLooseBase(this, _containerId)[_containerId]);
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getLayout)[_getLayout](), container);
	    babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].moveOnStep(0);
	  }
	}
	function _createHead2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="sign-settings__head">
				<div>
					<p class="sign-settings__head_title">
						${0}
					</p>
					<p class="sign-settings__head_title --sub">
						<span>${0}</span>
						<a class="sign-settings__head_title-help">
							${0}
						</a>
					</p>
				</div>
			</div>
		`), main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE'), main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB'), main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_TITLE_SUB_HELP'));
	}
	function _getStartProcessStep2(signSettings) {
	  return {
	    get content() {
	      const layout = babelHelpers.classPrivateFieldLooseBase(signSettings, _startProcess)[_startProcess].getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_START_PROCESS'),
	    beforeCompletion: async () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _stepsContext)[_stepsContext].selectedTemplateUid = babelHelpers.classPrivateFieldLooseBase(signSettings, _startProcess)[_startProcess].getSelectedTemplateUid();
	      babelHelpers.classPrivateFieldLooseBase(this, _stepsContext)[_stepsContext].templatesList = await babelHelpers.classPrivateFieldLooseBase(signSettings, _startProcess)[_startProcess].getTemplates();
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
	          uid: currentTemplateSelected.uid
	        },
	        company: currentTemplateSelected.company,
	        fields: currentTemplateSelected.fields
	      });
	      const layout = submitDocumentInfo.getLayout();
	      sign_v2_helper.SignSettingsItemCounter.numerate(layout);
	      return layout;
	    },
	    title: main_core.Loc.getMessage('SIGN_SETTINGS_EMPLOYEE_SUBMIT_INFO'),
	    beforeCompletion: () => {
	      return submitDocumentInfo.sendForSign();
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
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-settings__scope sign-settings --b2e --employee">
				<div class="sign-settings__sidebar">
					${0}
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _createHead)[_createHead](), babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].getLayout());
	}

	exports.B2EEmployeeSignSettings = B2EEmployeeSignSettings;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Ui,BX.Sign.V2));
//# sourceMappingURL=sign-settings-employee.bundle.js.map
