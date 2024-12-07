/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.AI = this.BX.Crm.AI || {};
(function (exports,crm_integration_analytics,main_core,ui_analytics,ui_dialogs_messagebox) {
	'use strict';

	/**
	 * @memberof BX.Crm.AI.Feedback
	 */
	function showSendFeedbackPopupIfFeedbackWasNeverSent(mergeUuid, ownerType, activityId, activityDirection) {
	  return wasFeedbackSent(mergeUuid).then(wasSent => {
	    if (!wasSent) {
	      return showSendFeedbackPopup(mergeUuid, ownerType, activityId, activityDirection);
	    }

	    // eslint-disable-next-line promise/no-return-wrap
	    return Promise.resolve();
	  });
	}

	/**
	 * @memberof BX.Crm.AI.Feedback
	 */
	function wasFeedbackSent(mergeUuid) {
	  // Ajax.runAction returns BX.Promise. I think it's not okay to return it from an exported function
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('crm.timeline.ai.wasFeedbackSent', {
	      data: {
	        mergeUuid
	      }
	    }).then(({
	      data
	    }) => {
	      if (main_core.Type.isBoolean(data)) {
	        resolve(data);
	      } else {
	        resolve(false);
	      }
	    })
	    // eslint-disable-next-line prefer-promise-reject-errors
	    .catch((...args) => reject(...args));
	  });
	}

	/**
	 * @memberof BX.Crm.AI.Feedback
	 */
	function sendFeedback(mergeUuid, ownerType, activityId, activityDirection) {
	  main_core.ajax.runAction('crm.timeline.ai.sendFeedback', {
	    data: {
	      mergeUuid
	    }
	  }).then(() => {
	    ui_analytics.sendData(crm_integration_analytics.Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, crm_integration_analytics.Dictionary.STATUS_SUCCESS).setElement(crm_integration_analytics.Dictionary.ELEMENT_FEEDBACK_SEND).setActivityDirection(activityDirection).buildData());
	    ui_analytics.sendData(crm_integration_analytics.Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, crm_integration_analytics.Dictionary.STATUS_SUCCESS).setTool(crm_integration_analytics.Dictionary.TOOL_CRM).setCategory(crm_integration_analytics.Dictionary.CATEGORY_AI_OPERATIONS).setElement(crm_integration_analytics.Dictionary.ELEMENT_FEEDBACK_SEND).setActivityDirection(activityDirection).buildData());
	  }).catch(({
	    errors
	  }) => console.error('Error sending feedback', errors));
	}

	/**
	 * @memberof BX.Crm.AI.Feedback
	 */
	function showSendFeedbackPopup(mergeUuid, ownerType, activityId, activityDirection) {
	  return new Promise(resolve => {
	    const messageBox = createFeedbackMessageBox({
	      onOk: () => {
	        sendFeedback(mergeUuid, ownerType, activityId, activityDirection);
	        messageBox.close();
	        resolve();
	      },
	      onCancel: () => {
	        messageBox.close();
	        ui_analytics.sendData(crm_integration_analytics.Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, crm_integration_analytics.Dictionary.STATUS_SUCCESS).setElement(crm_integration_analytics.Dictionary.ELEMENT_FEEDBACK_REFUSED).setActivityDirection(activityDirection).buildData());
	        ui_analytics.sendData(crm_integration_analytics.Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, crm_integration_analytics.Dictionary.STATUS_SUCCESS).setTool(crm_integration_analytics.Dictionary.TOOL_CRM).setCategory(crm_integration_analytics.Dictionary.CATEGORY_AI_OPERATIONS).setElement(crm_integration_analytics.Dictionary.ELEMENT_FEEDBACK_REFUSED).setActivityDirection(activityDirection).buildData());
	        resolve();
	      }
	    });
	    messageBox.show();
	  });
	}
	/**
	 * @memberof BX.Crm.AI.Feedback
	 */
	function createFeedbackMessageBox(options) {
	  const message = `
		<div class="bx-crm-ai-feedback-popup-content">
			<div class="bx-crm-ai-feedback-popup-content__icon"></div>
			<div class="bx-crm-ai-feedback-popup-content__text">
				${main_core.Loc.getMessage('CRM_AI_FEEDBACK_POPUP_TEXT')}
			</div>
		</div>
	`;
	  return ui_dialogs_messagebox.MessageBox.create({
	    title: main_core.Loc.getMessage('CRM_AI_FEEDBACK_POPUP_TITLE'),
	    message,
	    okCaption: main_core.Loc.getMessage('CRM_AI_FEEDBACK_POPUP_BUTTON_SHARE'),
	    cancelCaption: main_core.Loc.getMessage('CRM_AI_FEEDBACK_POPUP_BUTTON_ANOTHER_TIME'),
	    buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
	    ...options
	  });
	}

	exports.showSendFeedbackPopupIfFeedbackWasNeverSent = showSendFeedbackPopupIfFeedbackWasNeverSent;
	exports.wasFeedbackSent = wasFeedbackSent;
	exports.sendFeedback = sendFeedback;
	exports.showSendFeedbackPopup = showSendFeedbackPopup;
	exports.createFeedbackMessageBox = createFeedbackMessageBox;

}((this.BX.Crm.AI.Feedback = this.BX.Crm.AI.Feedback || {}),BX.Crm.Integration.Analytics,BX,BX.UI.Analytics,BX.UI.Dialogs));
//# sourceMappingURL=feedback.bundle.js.map
