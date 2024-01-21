/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_notification,crm_ai_slider,crm_ai_textbox,crm_audioPlayer,main_core) {
	'use strict';

	var _templateObject;
	var Base = /*#__PURE__*/function () {
	  function Base(data) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Base);
	    this.initDefaultOptions();
	    this.activityId = data.activityId;
	    this.ownerTypeId = data.ownerTypeId;
	    this.ownerId = data.ownerId;
	    var audioPlayerNode = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div id=\"crm-textbox-audio-player\"></div>"])));
	    this.audioPlayerApp = new crm_audioPlayer.AudioPlayer({
	      rootNode: audioPlayerNode
	    });
	    this.textbox = new crm_ai_textbox.Textbox({
	      title: this.textboxTitle,
	      previousTextContent: audioPlayerNode
	    });
	    this.sliderId = "".concat(this.id, "-").concat(Math.floor(Math.random() * 1000));
	    this.wrapperSlider = new crm_ai_slider.Slider({
	      url: this.sliderId,
	      sliderTitle: this.sliderTitle,
	      width: this.sliderWidth,
	      extensions: ['crm.ai.textbox', 'crm.audio-player'],
	      events: {
	        onLoad: function onLoad() {
	          _this.audioPlayerApp.attachTemplate();
	        },
	        onClose: function onClose() {
	          _this.audioPlayerApp.detachTemplate();
	        }
	      }
	    });
	  }
	  babelHelpers.createClass(Base, [{
	    key: "open",
	    value: function open() {
	      var _this2 = this;
	      var content = new Promise(function (resolve, reject) {
	        _this2.getAiJobResultAndCallRecord().then(function (response) {
	          var audioProps = _this2.prepareAudioProps(response);
	          _this2.audioPlayerApp.setAudioProps(audioProps);
	          var aiJobResult = _this2.prepareAiJobResult(response);
	          _this2.textbox.setText(aiJobResult);
	          _this2.textbox.render();
	          resolve(_this2.textbox.get());
	        })["catch"](function (response) {
	          _this2.showError(response);
	          _this2.wrapperSlider.destroy();
	        });
	      });
	      this.wrapperSlider.setContent(content);
	      this.wrapperSlider.open();
	    }
	  }, {
	    key: "getAiJobResultAndCallRecord",
	    value: function getAiJobResultAndCallRecord() {
	      var actionData = {
	        data: {
	          activityId: this.activityId,
	          ownerTypeId: this.ownerTypeId,
	          ownerId: this.ownerId
	        }
	      };
	      return BX.ajax.runAction(this.aiJobResultAndCallRecordAction, actionData);
	    }
	  }, {
	    key: "showError",
	    value: function showError(response) {
	      ui_notification.UI.Notification.Center.notify({
	        content: response.errors[0].message,
	        autoHideDelay: 5000
	      });
	    }
	  }, {
	    key: "prepareAiJobResult",
	    value: function prepareAiJobResult(response) {
	      return '';
	    }
	  }, {
	    key: "prepareAudioProps",
	    value: function prepareAudioProps(response) {
	      var callRecord = response.data.callRecord;
	      return {
	        id: callRecord.id,
	        src: callRecord.src,
	        title: callRecord.title,
	        context: window.top
	      };
	    }
	  }, {
	    key: "getSliderTitle",
	    value: function getSliderTitle() {
	      return '';
	    }
	  }, {
	    key: "getTextboxTitle",
	    value: function getTextboxTitle() {
	      return '';
	    }
	  }, {
	    key: "initDefaultOptions",
	    value: function initDefaultOptions() {}
	  }]);
	  return Base;
	}();

	var Summary = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Summary, _Base);
	  function Summary() {
	    babelHelpers.classCallCheck(this, Summary);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Summary).apply(this, arguments));
	  }
	  babelHelpers.createClass(Summary, [{
	    key: "initDefaultOptions",
	    value: function initDefaultOptions() {
	      this.id = 'crm-copilot-summary';
	      this.aiJobResultAndCallRecordAction = 'crm.timeline.ai.getCopilotSummaryAndCallRecord';
	      this.sliderTitle = main_core.Loc.getMessage('CRM_COMMON_COPILOT');
	      this.sliderWidth = 520;
	      this.textboxTitle = main_core.Loc.getMessage('CRM_COPILOT_CALL_SUMMARY_TITLE');
	    }
	  }, {
	    key: "prepareAiJobResult",
	    value: function prepareAiJobResult(response) {
	      return response.data.aiJobResult.summary;
	    }
	  }]);
	  return Summary;
	}(Base);

	var Transcription = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Transcription, _Base);
	  function Transcription() {
	    babelHelpers.classCallCheck(this, Transcription);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Transcription).apply(this, arguments));
	  }
	  babelHelpers.createClass(Transcription, [{
	    key: "initDefaultOptions",
	    value: function initDefaultOptions() {
	      this.id = 'crm-copilot-transcript';
	      this.aiJobResultAndCallRecordAction = 'crm.timeline.ai.getCopilotTranscriptAndCallRecord';
	      this.sliderTitle = main_core.Loc.getMessage('CRM_COMMON_COPILOT');
	      this.sliderWidth = 730;
	      this.textboxTitle = main_core.Loc.getMessage('CRM_COPILOT_CALL_TRANSCRIPT_TITLE');
	    }
	  }, {
	    key: "prepareAiJobResult",
	    value: function prepareAiJobResult(response) {
	      return response.data.aiJobResult.transcription;
	    }
	  }]);
	  return Transcription;
	}(Base);

	var Call = {
	  Summary: Summary,
	  Transcription: Transcription
	};

	exports.Call = Call;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX,BX.Crm.AI,BX.Crm.AI,BX.Crm,BX));
//# sourceMappingURL=call.bundle.js.map
