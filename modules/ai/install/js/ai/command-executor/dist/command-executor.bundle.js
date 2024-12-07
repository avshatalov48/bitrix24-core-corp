/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ai_engine,ai_payload_textpayload,main_core) {
	'use strict';

	const CommandCodes = Object.freeze({
	  createChecklist: 'create_checklist'
	});
	var _engine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _isAgreementAccepted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAgreementAccepted");
	var _checkOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkOptions");
	var _initEngine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initEngine");
	var _checkAgreement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkAgreement");
	var _showNotification = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showNotification");
	class CommandExecutor {
	  constructor(_options) {
	    Object.defineProperty(this, _showNotification, {
	      value: _showNotification2
	    });
	    Object.defineProperty(this, _checkAgreement, {
	      value: _checkAgreement2
	    });
	    Object.defineProperty(this, _initEngine, {
	      value: _initEngine2
	    });
	    Object.defineProperty(this, _checkOptions, {
	      value: _checkOptions2
	    });
	    Object.defineProperty(this, _engine, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isAgreementAccepted, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _checkOptions)[_checkOptions](_options);
	    babelHelpers.classPrivateFieldLooseBase(this, _initEngine)[_initEngine]({
	      moduleId: _options.moduleId,
	      contextId: _options.contextId,
	      contextParameters: _options.contextParameters || {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isAgreementAccepted)[_isAgreementAccepted] = main_core.Extension.getSettings('ai.command-executor').isAgreementAccepted === true;
	  }
	  async makeChecklistFromText(text) {
	    return new Promise((resolve, reject) => {
	      if (!text) {
	        throw new Error('AI.CommandExecutor.makeChecklistFromText: text is required parameter');
	      }
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isAgreementAccepted)[_isAgreementAccepted] === false) {
	        babelHelpers.classPrivateFieldLooseBase(this, _checkAgreement)[_checkAgreement](() => {
	          babelHelpers.classPrivateFieldLooseBase(this, _isAgreementAccepted)[_isAgreementAccepted] = true;
	          this.makeChecklistFromText(text).then(result => {
	            resolve(result);
	          }).catch(err => {
	            reject(err);
	          });
	        }, () => {
	          reject(new Error('Agreement is not accepted'));
	        });
	      } else {
	        const payload = new ai_payload_textpayload.Text({
	          prompt: {
	            code: CommandCodes.createChecklist
	          }
	        });
	        payload.setMarkers({
	          original_message: text
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setPayload(payload);
	        babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setAnalyticParameters({
	          c_section: 'tasks'
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].textCompletions(payload).then(result => {
	          resolve(result.data.result);
	        }).catch(err => {
	          var _err$errors;
	          const errorFromServer = err == null ? void 0 : (_err$errors = err.errors) == null ? void 0 : _err$errors[0];
	          if ((errorFromServer == null ? void 0 : errorFromServer.code) === 'CLOUD_REGISTRATION_DATA_NOT_FOUND') {
	            babelHelpers.classPrivateFieldLooseBase(this, _showNotification)[_showNotification](errorFromServer.message);
	          }
	          reject(err);
	        });
	      }
	    });
	  }
	}
	function _checkOptions2(options) {
	  if (!options.moduleId) {
	    throw new Error('BX.AI.CommandExecutor: moduleId is required option');
	  }
	  if (!options.contextId) {
	    throw new Error('BX.AI.CommandExecutor: contextId is required option');
	  }
	}
	function _initEngine2(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine] = new ai_engine.Engine();
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setContextId(options.contextId).setModuleId(options.moduleId).setContextParameters(options.contextParameters);
	}
	async function _checkAgreement2(onAccept, onCancel) {
	  const {
	    CopilotAgreement
	  } = await main_core.Runtime.loadExtension('ai.copilot-agreement');
	  const options = {
	    moduleId: babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getModuleId(),
	    contextId: babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getContextId(),
	    events: {
	      onAccept,
	      onCancel
	    }
	  };
	  const agreement = new CopilotAgreement(options);
	  return agreement.checkAgreement();
	}
	async function _showNotification2(message) {
	  await main_core.Runtime.loadExtension('ui.notification');
	  const notificationCenter = main_core.Reflection.getClass('BX.UI.Notification.Center');
	  notificationCenter.notify({
	    id: 'command-executor-notification',
	    content: message
	  });
	}

	exports.CommandExecutor = CommandExecutor;

}((this.BX.AI = this.BX.AI || {}),BX.AI,BX.AI.Payload,BX));
//# sourceMappingURL=command-executor.bundle.js.map
