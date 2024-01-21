/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_notification,crm_ai_slider,crm_ai_textbox,main_core) {
	'use strict';

	var Base = /*#__PURE__*/function () {
	  function Base() {
	    babelHelpers.classCallCheck(this, Base);
	  }
	  babelHelpers.createClass(Base, null, [{
	    key: "open",
	    value: function open(data) {
	      var _this = this;
	      this.wrapperSlider = new crm_ai_slider.Slider({
	        url: this.sliderId,
	        title: this.sliderTitle,
	        extensions: ['crm.ai.textbox']
	      });
	      var content = new Promise(function (resolve, reject) {
	        _this.getText(data).then(function (response) {
	          var text = _this.getTextInResponse(response);
	          resolve(_this.getTextboxContent(text));
	        })["catch"](function (response) {
	          _this.wrapperSlider.destroy();
	          ui_notification.UI.Notification.Center.notify({
	            content: response.errors[0].message,
	            autoHideDelay: 5000
	          });
	        });
	      });
	      this.wrapperSlider.setContent(content);
	      this.wrapperSlider.open();
	    }
	  }, {
	    key: "getText",
	    value: function getText(data) {
	      var action = this.gettingTextActionName;
	      var actionData = {
	        data: {
	          activityId: data.activityId,
	          ownerTypeId: data.ownerTypeId,
	          ownerId: data.ownerId
	        }
	      };
	      return BX.ajax.runAction(action, actionData);
	    }
	  }, {
	    key: "getTextboxContent",
	    value: function getTextboxContent(text) {
	      var textbox = new crm_ai_textbox.Textbox({
	        title: this.textboxTitle,
	        text: text
	      });
	      return textbox.get();
	    }
	  }, {
	    key: "getTextInResponse",
	    value: function getTextInResponse(response) {
	      return null;
	    }
	  }]);
	  return Base;
	}();
	babelHelpers.defineProperty(Base, "sliderId", null);
	babelHelpers.defineProperty(Base, "sliderTitle", null);
	babelHelpers.defineProperty(Base, "gettingTextActionName", null);
	babelHelpers.defineProperty(Base, "textboxTitle", null);
	babelHelpers.defineProperty(Base, "wrapperSlider", null);

	var Summary = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Summary, _Base);
	  function Summary() {
	    babelHelpers.classCallCheck(this, Summary);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Summary).apply(this, arguments));
	  }
	  babelHelpers.createClass(Summary, null, [{
	    key: "getTextInResponse",
	    value: function getTextInResponse(response) {
	      return response.data.summary;
	    }
	  }]);
	  return Summary;
	}(Base);
	babelHelpers.defineProperty(Summary, "textboxTitle", main_core.Loc.getMessage('CRM_COPILOT_CALL_SUMMARY_TITLE'));
	babelHelpers.defineProperty(Summary, "sliderId", 'crm-copilot-summary-slider');
	babelHelpers.defineProperty(Summary, "sliderTitle", main_core.Loc.getMessage('CRM_COPILOT_CALL_SUMMARY_SLIDER_TITLE'));
	babelHelpers.defineProperty(Summary, "gettingTextActionName", 'crm.timeline.activity.getCopilotSummary');

	var Transcription = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Transcription, _Base);
	  function Transcription() {
	    babelHelpers.classCallCheck(this, Transcription);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Transcription).apply(this, arguments));
	  }
	  babelHelpers.createClass(Transcription, null, [{
	    key: "getTextInResponse",
	    value: function getTextInResponse(response) {
	      return response.data.transcription;
	    }
	  }]);
	  return Transcription;
	}(Base);
	babelHelpers.defineProperty(Transcription, "textboxTitle", main_core.Loc.getMessage('CRM_COPILOT_CALL_TRANSCRIPT_TITLE'));
	babelHelpers.defineProperty(Transcription, "sliderId", 'crm-copilot-transcript-slider');
	babelHelpers.defineProperty(Transcription, "sliderTitle", main_core.Loc.getMessage('CRM_COPILOT_CALL_TRANSCRIPT_SLIDER_TITLE'));
	babelHelpers.defineProperty(Transcription, "gettingTextActionName", 'crm.timeline.activity.getCopilotTranscript');

	var Call = {
	  Summary: Summary,
	  Transcription: Transcription
	};

	exports.Call = Call;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX,BX.Crm.AI,BX.Crm.AI,BX));
