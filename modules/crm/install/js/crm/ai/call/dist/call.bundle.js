/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_vue3,crm_ai_slider,crm_ai_textbox,ui_notification,crm_audioPlayer,pull_client,pull_queuemanager,ui_lottie,crm_copilot_callAssessmentSelector,crm_router,crm_timeline_tools,main_core_events,ui_bbcode_formatter_htmlFormatter,ui_sidepanel,ui_designTokens,main_core) {
	'use strict';

	var _templateObject;
	var Base = /*#__PURE__*/function () {
	  function Base(data) {
	    var _data$languageTitle;
	    babelHelpers.classCallCheck(this, Base);
	    babelHelpers.defineProperty(this, "languageTitle", null);
	    this.initDefaultOptions();
	    this.activityId = data.activityId;
	    this.ownerTypeId = data.ownerTypeId;
	    this.ownerId = data.ownerId;
	    this.languageTitle = (_data$languageTitle = data.languageTitle) !== null && _data$languageTitle !== void 0 ? _data$languageTitle : null;
	    this.audioPlayerNode = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div id=\"crm-textbox-audio-player\"></div>"])));
	    this.audioPlayerApp = new crm_audioPlayer.AudioPlayer({
	      rootNode: this.audioPlayerNode
	    });
	    this.textbox = new crm_ai_textbox.Textbox({
	      title: this.textboxTitle,
	      previousTextContent: this.audioPlayerNode,
	      attentions: this.getTextboxAttentions()
	    });
	    this.sliderId = "".concat(this.id, "-").concat(this.activityId);
	    this.wrapperSlider = new crm_ai_slider.Slider({
	      url: this.sliderId,
	      sliderTitle: this.sliderTitle,
	      sliderContentClass: this.getSliderContentClass(),
	      width: this.sliderWidth,
	      extensions: this.getExtensions(),
	      design: this.getSliderDesign(),
	      events: this.getSliderEvents(),
	      toolbar: this.getSliderToolbar()
	    });
	  }
	  babelHelpers.createClass(Base, [{
	    key: "getExtensions",
	    value: function getExtensions() {
	      return ['crm.ai.textbox', 'crm.audio-player'];
	    }
	  }, {
	    key: "getSliderContentClass",
	    value: function getSliderContentClass() {
	      return null;
	    }
	  }, {
	    key: "getSliderDesign",
	    value: function getSliderDesign() {
	      return null;
	    }
	  }, {
	    key: "getSliderToolbar",
	    value: function getSliderToolbar() {
	      return null;
	    }
	  }, {
	    key: "getSliderEvents",
	    value: function getSliderEvents() {
	      var _this = this;
	      return {
	        onLoad: function onLoad() {
	          _this.audioPlayerApp.attachTemplate();
	        },
	        onClose: function onClose() {
	          _this.audioPlayerApp.detachTemplate();
	        }
	      };
	    }
	  }, {
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
	    key: "getTextboxAttentions",
	    value: function getTextboxAttentions() {
	      var attentions = [this.getNotAccurateAttention()];
	      var jobLanguageAttention = this.getJobLanguageAttention();
	      if (jobLanguageAttention !== null) {
	        attentions.push(jobLanguageAttention);
	      }
	      return attentions;
	    }
	  }, {
	    key: "getNotAccurateAttention",
	    value: function getNotAccurateAttention() {
	      var helpdeskCode = '20412666';
	      var content = main_core.Loc.getMessage(this.getNotAccuratePhraseCode(), {
	        '[helpdesklink]': "<a href=\"##\" onclick=\"top.BX.Helper.show('redirect=detail&code=".concat(helpdeskCode, "');\">"),
	        '[/helpdesklink]': '</a>'
	      });
	      return new crm_ai_textbox.Attention({
	        content: content
	      });
	    }
	  }, {
	    key: "getJobLanguageAttention",
	    value: function getJobLanguageAttention() {
	      if (!main_core.Type.isStringFilled(this.languageTitle)) {
	        return null;
	      }
	      var helpdeskCode = '20423978';
	      var content = main_core.Loc.getMessage('CRM_COPILOT_CALL_JOB_LANGUAGE_ATTENTION', {
	        '#LANGUAGE_TITLE#': "<span style=\"text-transform: lowercase\">".concat(main_core.Text.encode(this.languageTitle), "</span>"),
	        '[helpdesklink]': "<a href=\"##\" onclick=\"top.BX.Helper.show('redirect=detail&code=".concat(helpdeskCode, "');\">"),
	        '[/helpdesklink]': '</a>'
	      });
	      return new crm_ai_textbox.Attention({
	        preset: crm_ai_textbox.AttentionPresets.COPILOT,
	        content: content
	      });
	    }
	  }, {
	    key: "getNotAccuratePhraseCode",
	    value: function getNotAccuratePhraseCode() {
	      return '';
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

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var CALL_SCORING_ADD_COMMAND = 'call_scoring_add';
	var CALL_ASSESSMENT_UPDATE_COMMAND = 'call_assessment_update';
	var _callScoringCallback = /*#__PURE__*/new WeakMap();
	var _callAssessmentCallback = /*#__PURE__*/new WeakMap();
	var _unsubscribeFromCallScoring = /*#__PURE__*/new WeakMap();
	var _unsubscribeFromCallAssessment = /*#__PURE__*/new WeakMap();
	var Pull = /*#__PURE__*/function () {
	  function Pull(callScoringCallback, callAssessmentCallback) {
	    babelHelpers.classCallCheck(this, Pull);
	    _classPrivateFieldInitSpec(this, _callScoringCallback, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _callAssessmentCallback, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _unsubscribeFromCallScoring, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _unsubscribeFromCallAssessment, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _callScoringCallback, callScoringCallback);
	    babelHelpers.classPrivateFieldSet(this, _callAssessmentCallback, callAssessmentCallback);
	  }
	  babelHelpers.createClass(Pull, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      if (!pull_client.PULL) {
	        console.error('pull is not initialized');
	        return;
	      }

	      // @todo use only one subscribe with many actions in callback
	      babelHelpers.classPrivateFieldSet(this, _unsubscribeFromCallScoring, pull_client.PULL.subscribe({
	        moduleId: 'crm',
	        command: CALL_SCORING_ADD_COMMAND,
	        callback: function callback(params) {
	          if (main_core.Type.isStringFilled(params.eventId) && pull_queuemanager.QueueManager.eventIds.has(params.eventId)) {
	            return;
	          }
	          babelHelpers.classPrivateFieldGet(_this, _callScoringCallback).call(_this, params);
	        }
	      }));
	      babelHelpers.classPrivateFieldSet(this, _unsubscribeFromCallAssessment, pull_client.PULL.subscribe({
	        moduleId: 'crm',
	        command: CALL_ASSESSMENT_UPDATE_COMMAND,
	        callback: function callback(params) {
	          if (main_core.Type.isStringFilled(params.eventId) && pull_queuemanager.QueueManager.eventIds.has(params.eventId)) {
	            return;
	          }
	          babelHelpers.classPrivateFieldGet(_this, _callAssessmentCallback).call(_this, params);
	        }
	      }));
	      pull_client.PULL.extendWatch(CALL_SCORING_ADD_COMMAND);
	      pull_client.PULL.extendWatch(CALL_ASSESSMENT_UPDATE_COMMAND);
	    }
	  }, {
	    key: "unsubscribe",
	    value: function unsubscribe() {
	      babelHelpers.classPrivateFieldGet(this, _unsubscribeFromCallScoring).call(this);
	      babelHelpers.classPrivateFieldGet(this, _unsubscribeFromCallAssessment).call(this);
	    }
	  }]);
	  return Pull;
	}();

	/*
	* @readonly
	* @enum {string}
	*/
	var ViewMode = Object.freeze({
	  usedNotAssessmentScript: 'usedNotAssessmentScript',
	  usedCurrentVersionOfScript: 'usedCurrentVersionOfScript',
	  usedOtherVersionOfScript: 'usedOtherVersionOfScript',
	  emptyScriptList: 'emptyScriptList',
	  assessmentSettingsPending: 'assessmentSettingsPending',
	  pending: 'pending',
	  error: 'error'
	});

	var Compliance = {
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    assessment: {
	      type: Number,
	      "default": null
	    },
	    lowBorder: {
	      type: Number,
	      "default": 30
	    },
	    highBorder: {
	      type: Number,
	      "default": 70
	    },
	    viewMode: {
	      type: String,
	      "default": null
	    }
	  },
	  mounted: function mounted() {
	    this.startAnimate();
	  },
	  methods: {
	    startAnimate: function startAnimate() {
	      if (this.$refs.assessment) {
	        this.animateCounter(this.$refs.assessment, this.assessment);
	      }
	    },
	    animateCounter: function animateCounter(counterElement, targetNumber) {
	      var startNumber = 0;
	      var duration = 1500;
	      var increment = targetNumber / (duration / 50);
	      var interval = setInterval(function () {
	        startNumber += increment;
	        if (startNumber >= targetNumber) {
	          startNumber = targetNumber;
	          clearInterval(interval);
	        }

	        // eslint-disable-next-line no-param-reassign
	        counterElement.textContent = Math.floor(startNumber);
	      }, 50);
	    }
	  },
	  computed: {
	    classList: function classList() {
	      return {
	        'call-quality__compliance__container': true,
	        '--empty-state': !this.isUsedCurrentVersionOfScript,
	        '--low': this.assessment <= this.lowBorder,
	        '--high': this.assessment >= this.highBorder
	      };
	    },
	    isUsedCurrentVersionOfScript: function isUsedCurrentVersionOfScript() {
	      return this.viewMode === ViewMode.usedCurrentVersionOfScript;
	    },
	    infoTitle: function infoTitle() {
	      return this.viewMode === ViewMode.emptyScriptList ? main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_COMPLIANCE_EMPTY_SCRIPT_LIST_TITLE') : main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_COMPLIANCE_TITLE');
	    },
	    valueTitle: function valueTitle() {
	      return this.viewMode === ViewMode.emptyScriptList ? main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_COMPLIANCE_EMPTY_SCRIPT_LIST_VALUE') : this.title;
	    }
	  },
	  template: "\n\t\t<div :class=\"classList\">\n\t\t\t<div class=\"call-quality__compliance\">\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"isUsedCurrentVersionOfScript\"\n\t\t\t\t\tclass=\"call-quality__compliance__assessment\"\n\t\t\t\t>\n\t\t\t\t\t<span ref=\"assessment\" class=\"call-quality__compliance__assessment-value\">\n\t\t\t\t\t\t{{ assessment }}\n\t\t\t\t\t</span>\n\t\t\t\t\t<div class=\"call-quality__compliance__assessment-measure\">\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__compliance__info\">\n\t\t\t\t\t<span class=\"call-quality__compliance__info-title\">\n\t\t\t\t\t\t{{ infoTitle }}\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"call-quality__compliance__info-value\">\n\t\t\t\t\t\t{{ valueTitle }}\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var Loader = {
	  mounted: function mounted() {
	    this.renderLottieAnimation();
	  },
	  methods: {
	    renderLottieAnimation: function renderLottieAnimation() {
	      var mainAnimation = ui_lottie.Lottie.loadAnimation({
	        path: this.getAnimationPath(),
	        container: this.$refs.lottie,
	        renderer: 'svg',
	        loop: true,
	        autoplay: true
	      });
	      mainAnimation.setSpeed(0.75);
	      return this.$refs.lottie.root;
	    },
	    getAnimationPath: function getAnimationPath() {
	      return '/bitrix/js/crm/ai/call/src/call-quality/lottie/loader.json';
	    }
	  },
	  template: "\n\t\t<div ref=\"lottie\" class=\"call-quality__explanation-loader__lottie\"></div>\n\t"
	};

	var AssessmentSettingsPendingBlock = {
	  components: {
	    Loader: Loader
	  },
	  template: "\n\t\t<div class=\"call-quality__explanation\">\n\t\t\t<div class=\"call-quality__explanation__container\">\n\t\t\t\t<div class=\"call-quality__explanation-title\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_ASSESSMENT_SETTINGS_PENDING_TITLE') }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-text\">\n\t\t\t\t\t<div class=\"call-quality__explanation-loader__container\">\n\t\t\t\t\t\t<Loader />\n\t\t\t\t\t\t<div class=\"call-quality__explanation-loader__lottie-text\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_ASSESSMENT_SETTINGS_PENDING_TEXT') }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var EmptyScriptListBlock = {
	  computed: {
	    title: function title() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_TITLE');
	    },
	    text: function text() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_TEXT');
	    }
	  },
	  template: "\n\t\t<div class=\"call-quality__explanation\">\n\t\t\t<div class=\"call-quality__explanation__container\">\n\t\t\t\t<div class=\"call-quality__explanation-title\">\n\t\t\t\t\t{{ title }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-text\" v-html=\"text\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var ErrorBlock = {
	  data: function data() {
	    return {
	      errorText: null
	    };
	  },
	  methods: {
	    setErrorMessage: function setErrorMessage(message) {
	      this.errorText = message;
	    }
	  },
	  computed: {
	    explanationText: function explanationText() {
	      return main_core.Type.isStringFilled(this.errorText) ? this.errorText : main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_ERROR_TEXT');
	    }
	  },
	  template: "\n\t\t<div class=\"call-quality__explanation\">\n\t\t\t<div class=\"call-quality__explanation__container --error\">\n\t\t\t\t<div class=\"call-quality__explanation-title\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_ERROR_TITLE') }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-text\">\n\t\t\t\t\t{{ explanationText }}\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var NotAssessmentScriptBlock = {
	  methods: {
	    doAssessment: function doAssessment() {
	      this.$emit('doAssessment');
	    }
	  },
	  computed: {
	    title: function title() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_NO_EXPLANATION_TITLE');
	    },
	    text: function text() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_NO_EXPLANATION_TEXT');
	    },
	    buttonText: function buttonText() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_NO_EXPLANATION_ASSESSMENT');
	    }
	  },
	  template: "\n\t\t<div class=\"call-quality__explanation\">\n\t\t\t<div class=\"call-quality__explanation__container \">\n\t\t\t\t<div class=\"call-quality__explanation-title\">\n\t\t\t\t\t{{ title }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-text\" v-html=\"text\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"call-quality__explanation__buttons-container\">\n\t\t\t\t<button\n\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-no-caps ui-btn-color-ai ui-btn-round ui-btn-active\"\n\t\t\t\t\t@click=\"doAssessment\"\n\t\t\t\t>\n\t\t\t\t\t{{ buttonText }}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var OtherScriptBlock = {
	  methods: {
	    showAssessment: function showAssessment() {
	      this.$emit('showAssessment');
	    },
	    doAssessment: function doAssessment() {
	      this.$emit('doAssessment');
	    }
	  },
	  computed: {
	    title: function title() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_TITLE');
	    },
	    text: function text() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_TEXT');
	    },
	    buttonShowText: function buttonShowText() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_SHOW_ASSESSMENT');
	    },
	    buttonDoText: function buttonDoText() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_OLD_EXPLANATION_ASSESSMENT');
	    }
	  },
	  template: "\n\t\t<div class=\"call-quality__explanation\">\n\t\t\t<div class=\"call-quality__explanation__container \">\n\t\t\t\t<div class=\"call-quality__explanation-title\" v-html=\"title\">\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-text\" v-html=\"text\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"call-quality__explanation__buttons-container\">\n\t\t\t\t<button\n\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-no-caps ui-btn-color-ai ui-btn-round ui-btn-active\"\n\t\t\t\t\t@click=\"doAssessment\"\n\t\t\t\t>\n\t\t\t\t\t{{ buttonDoText }}\n\t\t\t\t</button>\n\t\t\t\t<button\n\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-no-caps ui-btn-light-border ui-btn-round\"\n\t\t\t\t\t@click=\"showAssessment\"\n\t\t\t\t>\n\t\t\t\t\t{{ buttonShowText }}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var PendingBlock = {
	  components: {
	    Loader: Loader
	  },
	  template: "\n\t\t<div class=\"call-quality__explanation\">\n\t\t\t<div class=\"call-quality__explanation__container\">\n\t\t\t\t<div class=\"call-quality__explanation-title\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_PENDING_TITLE') }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-text\">\n\t\t\t\t\t<div class=\"call-quality__explanation-loader__container\">\n\t\t\t\t\t\t<Loader />\n\t\t\t\t\t\t<div class=\"call-quality__explanation-loader__lottie-text\">\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_PENDING_TEXT') }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var ARTICLE_CODE = '23240682';
	var DISCLAIMER_ARTICLE_CODE = '20412666';
	var RecommendationBlock = {
	  props: {
	    recommendations: {
	      type: String,
	      "default": null
	    },
	    summary: {
	      type: String,
	      "default": null
	    },
	    useInRating: {
	      type: Boolean,
	      "default": false
	    }
	  },
	  methods: {
	    showArticle: function showArticle() {
	      var _window$top$BX, _window$top$BX$Helper;
	      (_window$top$BX = window.top.BX) === null || _window$top$BX === void 0 ? void 0 : (_window$top$BX$Helper = _window$top$BX.Helper) === null || _window$top$BX$Helper === void 0 ? void 0 : _window$top$BX$Helper.show("redirect=detail&code=".concat(ARTICLE_CODE));
	    }
	  },
	  computed: {
	    disclaimer: function disclaimer() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EXPLANATION_DISCLAIMER', {
	        '#LINK_START#': "<a onclick='window.top.BX?.Helper?.show(`redirect=detail&code=".concat(DISCLAIMER_ARTICLE_CODE, "`)' href=\"#\">"),
	        '#LINK_END#': '</a>'
	      });
	    }
	  },
	  template: "\n\t\t<div class=\"call-quality__explanation --copilot-content\">\n\t\t\t<div class=\"call-quality__explanation__container \">\n\t\t\t\t<div class=\"call-quality__explanation-title\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EXPLANATION_TITLE') }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-text\">\n\t\t\t\t\t<div \n\t\t\t\t\t\tv-if=\"!useInRating\"\n\t\t\t\t\t\tclass=\"call-quality__explanation-badge\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div>\n\t\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EXPLANATION_NOT_IN_RATING') }}\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"call-quality__explanation-badge-article ui-icon-set --help\"\n\t\t\t\t\t\t\t\t@click=\"showArticle\"\n\t\t\t\t\t\t\t></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<p>\n\t\t\t\t\t\t{{ summary }}\n\t\t\t\t\t</p>\n\t\t\t\t\t<p>\n\t\t\t\t\t\t{{ recommendations }}\n\t\t\t\t\t</p>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__explanation-disclaimer\" v-html=\"disclaimer\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var _templateObject$1, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _container = /*#__PURE__*/new WeakMap();
	var _isLoading = /*#__PURE__*/new WeakMap();
	var _createContainer = /*#__PURE__*/new WeakSet();
	var ScriptSelectorDisplayStrategy = /*#__PURE__*/function () {
	  function ScriptSelectorDisplayStrategy() {
	    babelHelpers.classCallCheck(this, ScriptSelectorDisplayStrategy);
	    _classPrivateMethodInitSpec(this, _createContainer);
	    _classPrivateFieldInitSpec$1(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _isLoading, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldSet(this, _container, _classPrivateMethodGet(this, _createContainer, _createContainer2).call(this));
	  }
	  babelHelpers.createClass(ScriptSelectorDisplayStrategy, [{
	    key: "getTargetNode",
	    value: function getTargetNode() {
	      return this.titleNode;
	    }
	  }, {
	    key: "updateTitle",
	    value: function updateTitle(title) {
	      this.innerTitleNode.innerText = title;
	      this.innerTitleNode.title = title;
	    }
	  }, {
	    key: "setLoading",
	    value: function setLoading(isLoading) {
	      if (babelHelpers.classPrivateFieldGet(this, _isLoading) === isLoading) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _isLoading, isLoading);
	      main_core.Dom.toggleClass(babelHelpers.classPrivateFieldGet(this, _container), '--loading');
	    }
	  }]);
	  return ScriptSelectorDisplayStrategy;
	}();
	function _createContainer2() {
	  this.innerTitleNode = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	  this.titleNode = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"call-quality__script-selector\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.innerTitleNode);
	  return this.titleNode;
	}

	var ARTICLE_CODE$1 = '23240682';
	var SUCCESS_STATUS = 'SUCCESS';
	var PENDING_STATUS = 'PENDING';
	var ScriptSelector = {
	  props: {
	    assessmentSettingsId: {
	      type: Number,
	      required: true
	    },
	    assessmentSettingsStatus: {
	      type: String,
	      "default": SUCCESS_STATUS
	    },
	    assessmentSettingsTitle: {
	      type: String,
	      required: true
	    },
	    isPromptChanged: {
	      type: Boolean,
	      required: true
	    },
	    promptUpdatedAt: {
	      type: String,
	      required: true
	    },
	    prompt: {
	      type: String,
	      required: true
	    },
	    viewMode: {
	      type: String,
	      "default": ''
	    }
	  },
	  data: function data() {
	    return {
	      callAssessmentSelector: this.getCallAssessmentSelector(),
	      htmlFormatter: new ui_bbcode_formatter_htmlFormatter.HtmlFormatter()
	    };
	  },
	  methods: {
	    getCallAssessmentSelector: function getCallAssessmentSelector() {
	      return new crm_copilot_callAssessmentSelector.CallAssessmentSelector({
	        currentCallAssessment: {
	          id: this.assessmentSettingsId,
	          title: this.assessmentSettingsId > 0 ? this.assessmentSettingsTitle : main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_SCRIPT_TITLE')
	        },
	        emptyScriptListTitle: main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_SCRIPT_TITLE'),
	        displayStrategy: new ScriptSelectorDisplayStrategy(),
	        additionalSelectorOptions: {
	          dialog: {
	            events: {
	              'Item:onBeforeSelect': this.onBeforeSelect.bind(this)
	            }
	          },
	          popup: {
	            events: {
	              onShow: function onShow(event) {
	                // @todo maybe there is a better solution?
	                var zIndex = ui_sidepanel.SidePanel.Instance.getTopSlider().getZindex() + 1;
	                event.target.getZIndexComponent().setZIndex(zIndex);
	              }
	            }
	          }
	        }
	      });
	    },
	    onBeforeSelect: function onBeforeSelect(event) {
	      var _this$callAssessmentS;
	      this.$emit('onBeforeSelect', (_this$callAssessmentS = this.callAssessmentSelector.getCurrentCallAssessmentItem()) === null || _this$callAssessmentS === void 0 ? void 0 : _this$callAssessmentS.id);
	    },
	    onEditCallAssessmentSettings: function onEditCallAssessmentSettings(_ref) {
	      var target = _ref.target;
	      if (this.assessmentSettingsStatus === PENDING_STATUS) {
	        this.showDisabledButtonHint(target);
	        return;
	      }
	      crm_router.Router.openSlider("/crm/copilot-call-assessment/details/".concat(this.assessmentSettingsId, "/"), {
	        width: 700,
	        cacheable: false
	      });
	    },
	    onShowActualPrompt: function onShowActualPrompt() {
	      this.$emit('onShowActualPrompt');
	    },
	    showArticle: function showArticle() {
	      var _window$top$BX, _window$top$BX$Helper;
	      (_window$top$BX = window.top.BX) === null || _window$top$BX === void 0 ? void 0 : (_window$top$BX$Helper = _window$top$BX.Helper) === null || _window$top$BX$Helper === void 0 ? void 0 : _window$top$BX$Helper.show("redirect=detail&code=".concat(ARTICLE_CODE$1));
	    },
	    formatHtml: function formatHtml(source) {
	      return this.htmlFormatter.format({
	        source: source
	      });
	    },
	    close: function close() {
	      var _this$callAssessmentS2;
	      (_this$callAssessmentS2 = this.callAssessmentSelector) === null || _this$callAssessmentS2 === void 0 ? void 0 : _this$callAssessmentS2.close();
	    },
	    disable: function disable() {
	      var _this$callAssessmentS3;
	      (_this$callAssessmentS3 = this.callAssessmentSelector) === null || _this$callAssessmentS3 === void 0 ? void 0 : _this$callAssessmentS3.disable();
	    },
	    enable: function enable() {
	      var _this$callAssessmentS4;
	      (_this$callAssessmentS4 = this.callAssessmentSelector) === null || _this$callAssessmentS4 === void 0 ? void 0 : _this$callAssessmentS4.enable();
	    },
	    doAssessment: function doAssessment(_ref2) {
	      var target = _ref2.target;
	      if (this.assessmentSettingsStatus === PENDING_STATUS) {
	        this.showDisabledButtonHint(target);
	        return;
	      }
	      this.$emit('doAssessment');
	    },
	    showDisabledButtonHint: function showDisabledButtonHint(target) {
	      top.BX.UI.Hint.popupParameters = {
	        closeByEsc: true,
	        autoHide: true,
	        angle: null,
	        events: {}
	      };
	      main_core.Runtime.debounce(function () {
	        top.BX.UI.Hint.show(target, main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_DISABLED_DO_ASSESSMENT_HINT'), true);
	      }, 150, this)();
	    },
	    isDisabledAssessmentButton: function isDisabledAssessmentButton() {
	      return this.assessmentSettingsStatus !== SUCCESS_STATUS;
	    },
	    isDisabledEditButton: function isDisabledEditButton() {
	      return this.assessmentSettingsStatus === PENDING_STATUS;
	    }
	  },
	  mounted: function mounted() {
	    main_core.Dom.append(this.callAssessmentSelector.getContainer(), this.$refs.container);
	    if (this.$refs.prompt) {
	      main_core.Dom.append(this.formattedPrompt, this.$refs.prompt);
	    }
	  },
	  computed: {
	    scriptUpdatedAt: function scriptUpdatedAt() {
	      var date = new Date(this.promptUpdatedAt);
	      var datetimeConverter = new crm_timeline_tools.DatetimeConverter(date);
	      var dateString = datetimeConverter.toDatetimeString({
	        withDayOfWeek: false,
	        delimiter: ', '
	      });
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_INFO_UPDATED', {
	        '#UPDATED_AT#': dateString
	      });
	    },
	    formattedPrompt: function formattedPrompt() {
	      return this.formatHtml(this.prompt);
	    },
	    isShowFooterButtons: function isShowFooterButtons() {
	      return this.viewMode !== ViewMode.usedOtherVersionOfScript && this.viewMode !== ViewMode.usedNotAssessmentScript && this.viewMode !== ViewMode.pending && this.assessmentSettingsId > 0;
	    },
	    footerButtonClassList: function footerButtonClassList() {
	      return ['ui-btn', 'ui-btn-xs', 'ui-btn-no-caps', 'ui-btn-light-border', 'ui-btn-round', {
	        'ui-btn-disabled': this.isDisabledAssessmentButton()
	      }];
	    },
	    footerEditButtonClassList: function footerEditButtonClassList() {
	      return ['ui-btn', 'ui-btn-xs', 'ui-btn-no-caps', 'ui-btn-round', 'ui-btn-light', 'edit-button', {
	        'ui-btn-disabled': this.isDisabledEditButton()
	      }];
	    },
	    isEmptyScriptListViewMode: function isEmptyScriptListViewMode() {
	      return this.viewMode === ViewMode.emptyScriptList;
	    }
	  },
	  watch: {
	    prompt: function prompt() {
	      main_core.Dom.clean(this.$refs.prompt);
	      main_core.Dom.append(this.formattedPrompt, this.$refs.prompt);
	    }
	  },
	  template: "\n\t\t<div>\n\t\t\t<div class=\"call-quality__script-selector__container\">\n\t\t\t\t<div class=\"call-quality__script-selector__title\">\n\t\t\t\t\t<div>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_SELECTOR_TITLE') }}\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"call-quality__script-selector__selector-container\" ref=\"container\"></div>\n\t\t\t\t\t<div \n\t\t\t\t\t\tclass=\"call-quality__script-selector__article ui-icon-set --help\"\n\t\t\t\t\t\t@click=\"showArticle\"\n\t\t\t\t\t></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div\n\t\t\t\tv-if=\"this.isPromptChanged && isShowFooterButtons\"\n\t\t\t\tclass=\"call-quality__script-info__container\"\n\t\t\t>\n\t\t\t\t<span>{{scriptUpdatedAt}}</span>\n\t\t\t\t<button\n\t\t\t\t\tclass=\"ui-btn ui-btn-xs ui-btn-no-caps ui-btn-round ui-btn-link ui-btn-active\"\n\t\t\t\t\t@click=\"onShowActualPrompt\"\n\t\t\t\t>\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_INFO_SHOW_NEW_PROMPT') }}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t\t<div class=\"call-quality__script-container\">\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"isEmptyScriptListViewMode\"\n\t\t\t\t\tclass=\"call-quality__script-text\"\n\t\t\t\t>\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_PROMPT_TEXT') }}\n\t\t\t\t</div>\n\t\t\t\t<div v-else class=\"call-quality__script-text\" ref=\"prompt\">\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"isShowFooterButtons\"\n\t\t\t\t\tclass=\"call-quality__script-footer\"\n\t\t\t\t>\n\t\t\t\t\t<button \n\t\t\t\t\t\t:class=\"footerButtonClassList\"\n\t\t\t\t\t\t@click=\"doAssessment\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_ASSESSMENT_REPLY') }}\n\t\t\t\t\t</button>\n\t\t\t\t\t<button \n\t\t\t\t\t\t:class=\"footerEditButtonClassList\"\n\t\t\t\t\t\t@click=\"onEditCallAssessmentSettings\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_EDIT') }}\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var CallQuality = {
	  components: {
	    AudioPlayerComponent: crm_audioPlayer.AudioPlayerComponent,
	    ScriptSelectorComponent: ScriptSelector,
	    RecommendationBlock: RecommendationBlock,
	    OtherScriptBlock: OtherScriptBlock,
	    NotAssessmentScriptBlock: NotAssessmentScriptBlock,
	    AssessmentSettingsPendingBlock: AssessmentSettingsPendingBlock,
	    PendingBlock: PendingBlock,
	    ErrorBlock: ErrorBlock,
	    EmptyScriptListBlock: EmptyScriptListBlock,
	    ComplianceComponent: Compliance
	  },
	  props: {
	    client: {
	      type: Object,
	      required: true
	    },
	    data: {
	      type: Object,
	      required: true
	    },
	    audioProps: {
	      type: Object,
	      required: true
	    },
	    context: {
	      type: Object,
	      required: true
	    }
	  },
	  data: function data() {
	    var _quality$id;
	    var quality = this.getPreparedQualityProps(this.data);
	    var prompt = quality.prompt;
	    var currentQualityAssessmentId = (_quality$id = quality.id) !== null && _quality$id !== void 0 ? _quality$id : null;
	    var viewMode = null;
	    if (this.data.viewMode === ViewMode.usedNotAssessmentScript) {
	      viewMode = ViewMode.usedNotAssessmentScript;
	    } else if (this.data.viewMode === ViewMode.pending) {
	      viewMode = ViewMode.pending;
	    } else if (this.data.viewMode === ViewMode.emptyScriptList) {
	      viewMode = ViewMode.emptyScriptList;
	    } else if (quality.id) {
	      var _this$data$viewMode;
	      viewMode = (_this$data$viewMode = this.data.viewMode) !== null && _this$data$viewMode !== void 0 ? _this$data$viewMode : ViewMode.usedCurrentVersionOfScript;
	    } else {
	      viewMode = ViewMode.error;
	    }
	    return {
	      quality: quality,
	      currentQualityAssessmentId: currentQualityAssessmentId,
	      viewMode: viewMode,
	      prompt: prompt,
	      isShowAudioPlayer: false,
	      direction: this.data.callDirection
	    };
	  },
	  mounted: function mounted() {
	    top.BX.Event.EventEmitter.subscribe('crm:copilot:callAssessment:beforeSave', this.onBeforeAssessmentSettingsChange);
	    top.BX.Event.EventEmitter.subscribe('crm:copilot:callAssessment:save', this.onAssessmentSettingsChange);
	    this.pull = new Pull(this.onPullChangeScript, this.onPullChangeAssessment);
	    this.pull.init();
	  },
	  methods: {
	    onBeforeAssessmentSettingsChange: function onBeforeAssessmentSettingsChange(event) {
	      var _event$getData = event.getData(),
	        data = _event$getData.data;
	      if (!this.isPromptChanged(data.prompt)) {
	        return;
	      }
	      this.quality.assessmentSettingsStatus = 'PENDING';
	    },
	    onAssessmentSettingsChange: function onAssessmentSettingsChange(event) {
	      var _event$getData2 = event.getData(),
	        id = _event$getData2.id,
	        data = _event$getData2.data;
	      if (!this.isPromptChanged(data.prompt)) {
	        return;
	      }
	      this.onChangeScript(id);
	    },
	    isPromptChanged: function isPromptChanged(newPrompt) {
	      return this.quality.actualPrompt !== newPrompt;
	    },
	    showAudioPlayer: function showAudioPlayer() {
	      this.isShowAudioPlayer = true;
	    },
	    onShowActualPrompt: function onShowActualPrompt() {
	      this.viewMode = ViewMode.usedOtherVersionOfScript;
	      this.prompt = this.quality.actualPrompt;
	    },
	    onShowCurrentAssessment: function onShowCurrentAssessment() {
	      this.viewMode = ViewMode.usedCurrentVersionOfScript;
	      this.prompt = this.quality.prompt;
	    },
	    onDoAssessment: function onDoAssessment() {
	      var _this$$refs$scriptSel,
	        _this = this;
	      this.viewMode = ViewMode.pending;
	      (_this$$refs$scriptSel = this.$refs.scriptSelector) === null || _this$$refs$scriptSel === void 0 ? void 0 : _this$$refs$scriptSel.disable();
	      var config = {
	        data: _objectSpread(_objectSpread({}, this.context), {}, {
	          assessmentSettingsId: this.quality.assessmentSettingsId
	        })
	      };
	      main_core.ajax.runAction('crm.copilot.callqualityassessment.doAssessment', config).then(function (response) {
	        var _this$$refs$scriptSel2;
	        var status = response.status,
	          data = response.data;
	        (_this$$refs$scriptSel2 = _this.$refs.scriptSelector) === null || _this$$refs$scriptSel2 === void 0 ? void 0 : _this$$refs$scriptSel2.enable();
	        if (status !== 'success') {
	          _this.showError(response);
	          return;
	        }
	        main_core_events.EventEmitter.emit('crm.ai.callQuality:doAssessment', {
	          data: data
	        });
	      })["catch"](function (response) {
	        var _this$$refs$scriptSel3;
	        _this.showError(response);
	        (_this$$refs$scriptSel3 = _this.$refs.scriptSelector) === null || _this$$refs$scriptSel3 === void 0 ? void 0 : _this$$refs$scriptSel3.enable();
	      });
	    },
	    onChangeScript: function onChangeScript(assessmentSettingsId) {
	      var _this2 = this;
	      var config = {
	        data: _objectSpread(_objectSpread({}, this.context), {}, {
	          assessmentSettingsId: assessmentSettingsId
	        })
	      };
	      main_core.ajax.runAction('crm.copilot.callqualityassessment.get', config).then(function (response) {
	        var _this2$$refs$scriptSe;
	        (_this2$$refs$scriptSe = _this2.$refs.scriptSelector) === null || _this2$$refs$scriptSe === void 0 ? void 0 : _this2$$refs$scriptSe.enable();
	        var status = response.status,
	          data = response.data;
	        if (status !== 'success') {
	          _this2.showError(response);
	          return;
	        }
	        if (main_core.Type.isObject(data)) {
	          _this2.quality = _this2.getPreparedQualityProps(data);
	          if (!(_this2.quality.isPromptChanged && data.viewMode === ViewMode.assessmentSettingsPending
	          //&& this.viewMode === ViewMode.usedCurrentVersionOfScript
	          )) {
	            _this2.viewMode = data.viewMode;
	          }
	        }
	      })["catch"](function (response) {
	        var _this2$$refs$scriptSe2;
	        (_this2$$refs$scriptSe2 = _this2.$refs.scriptSelector) === null || _this2$$refs$scriptSe2 === void 0 ? void 0 : _this2$$refs$scriptSe2.enable();
	        top.BX.UI.Notification.Center.notify({
	          content: response.errors[0].message,
	          autoHideDelay: 5000
	        });
	      });
	    },
	    showError: function showError(response) {
	      var _this3 = this;
	      this.viewMode = ViewMode.error;
	      this.$nextTick(function () {
	        var _this3$$refs$errorBlo, _response$errors$;
	        (_this3$$refs$errorBlo = _this3.$refs.errorBlock) === null || _this3$$refs$errorBlo === void 0 ? void 0 : _this3$$refs$errorBlo.setErrorMessage((_response$errors$ = response.errors[0]) === null || _response$errors$ === void 0 ? void 0 : _response$errors$.message);
	      });
	    },
	    getPreparedQualityProps: function getPreparedQualityProps(_ref) {
	      var _quality$ID, _quality$CREATED_AT, _quality$ASSESSMENT_S, _quality$ASSESSMENT_S2, _quality$ASSESSMENT, _quality$ASSESSMENT_A, _quality$PREV_ASSESSM, _quality$IS_PROMPT_CH, _quality$USE_IN_RATIN, _quality$PROMPT, _quality$ACTUAL_PROMP, _quality$PROMPT_UPDAT, _quality$TITLE, _quality$RECOMMENDATI, _quality$SUMMARY, _quality$LOW_BORDER, _quality$HIGH_BORDER;
	      var quality = _ref.callQuality;
	      if (!main_core.Type.isPlainObject(quality)) {
	        // eslint-disable-next-line no-param-reassign
	        quality = {};
	      }
	      return {
	        id: Number((_quality$ID = quality.ID) !== null && _quality$ID !== void 0 ? _quality$ID : 0),
	        createdAt: (_quality$CREATED_AT = quality.CREATED_AT) !== null && _quality$CREATED_AT !== void 0 ? _quality$CREATED_AT : null,
	        assessmentSettingsId: Number((_quality$ASSESSMENT_S = quality.ASSESSMENT_SETTING_ID) !== null && _quality$ASSESSMENT_S !== void 0 ? _quality$ASSESSMENT_S : 0),
	        assessmentSettingsStatus: (_quality$ASSESSMENT_S2 = quality.ASSESSMENT_SETTINGS_STATUS) !== null && _quality$ASSESSMENT_S2 !== void 0 ? _quality$ASSESSMENT_S2 : null,
	        assessment: Number((_quality$ASSESSMENT = quality.ASSESSMENT) !== null && _quality$ASSESSMENT !== void 0 ? _quality$ASSESSMENT : 0),
	        assessmentAvg: Number((_quality$ASSESSMENT_A = quality.ASSESSMENT_AVG) !== null && _quality$ASSESSMENT_A !== void 0 ? _quality$ASSESSMENT_A : 0),
	        prevAssessmentAvg: Number((_quality$PREV_ASSESSM = quality.PREV_ASSESSMENT_AVG) !== null && _quality$PREV_ASSESSM !== void 0 ? _quality$PREV_ASSESSM : 0),
	        isPromptChanged: Boolean((_quality$IS_PROMPT_CH = quality.IS_PROMPT_CHANGED) !== null && _quality$IS_PROMPT_CH !== void 0 ? _quality$IS_PROMPT_CH : false),
	        useInRating: Boolean((_quality$USE_IN_RATIN = quality.USE_IN_RATING) !== null && _quality$USE_IN_RATIN !== void 0 ? _quality$USE_IN_RATIN : false),
	        prompt: (_quality$PROMPT = quality.PROMPT) !== null && _quality$PROMPT !== void 0 ? _quality$PROMPT : '',
	        actualPrompt: (_quality$ACTUAL_PROMP = quality.ACTUAL_PROMPT) !== null && _quality$ACTUAL_PROMP !== void 0 ? _quality$ACTUAL_PROMP : '',
	        promptUpdatedAt: (_quality$PROMPT_UPDAT = quality.PROMPT_UPDATED_AT) !== null && _quality$PROMPT_UPDAT !== void 0 ? _quality$PROMPT_UPDAT : '',
	        title: (_quality$TITLE = quality.TITLE) !== null && _quality$TITLE !== void 0 ? _quality$TITLE : '',
	        recommendations: (_quality$RECOMMENDATI = quality.RECOMMENDATIONS) !== null && _quality$RECOMMENDATI !== void 0 ? _quality$RECOMMENDATI : '',
	        summary: (_quality$SUMMARY = quality.SUMMARY) !== null && _quality$SUMMARY !== void 0 ? _quality$SUMMARY : '',
	        lowBorder: Number((_quality$LOW_BORDER = quality.LOW_BORDER) !== null && _quality$LOW_BORDER !== void 0 ? _quality$LOW_BORDER : 30),
	        highBorder: Number((_quality$HIGH_BORDER = quality.HIGH_BORDER) !== null && _quality$HIGH_BORDER !== void 0 ? _quality$HIGH_BORDER : 70)
	      };
	    },
	    close: function close() {
	      var _this$$refs$scriptSel4;
	      (_this$$refs$scriptSel4 = this.$refs.scriptSelector) === null || _this$$refs$scriptSel4 === void 0 ? void 0 : _this$$refs$scriptSel4.close();
	      this.pull.unsubscribe();
	      top.BX.Event.EventEmitter.unsubscribe('crm:copilot:callAssessment:beforeSave', this.onBeforeAssessmentSettingsChange);
	      top.BX.Event.EventEmitter.unsubscribe('crm:copilot:callAssessment:save', this.onAssessmentSettingsChange);
	    },
	    onPullChangeScript: function onPullChangeScript(params) {
	      if (this.context.activityId !== params.activityId) {
	        return;
	      }
	      if (params.status === 'error' || !main_core.Type.isNumber(params.assessmentSettingsId)) {
	        this.viewMode = ViewMode.error;
	      } else {
	        this.onChangeScript(params.assessmentSettingsId);
	      }
	    },
	    onPullChangeAssessment: function onPullChangeAssessment(params) {
	      var _params$assessmentSet;
	      var assessmentSettingsId = (_params$assessmentSet = params.assessmentSettingsId) !== null && _params$assessmentSet !== void 0 ? _params$assessmentSet : null;
	      var currentAssessmentSettingsId = this.quality.assessmentSettingsId;
	      if (assessmentSettingsId !== currentAssessmentSettingsId) {
	        return;
	      }
	      this.onChangeScript(assessmentSettingsId);
	    }
	  },
	  watch: {
	    quality: {
	      handler: function handler(quality) {
	        this.prompt = quality.prompt;
	      },
	      deep: true
	    }
	  },
	  computed: {
	    clientNameClassList: function clientNameClassList() {
	      return {
	        'call-quality__call-client-name': true,
	        '--incoming': Number(this.direction) === 1,
	        '--outgoing': Number(this.direction) === 2
	      };
	    },
	    clientName: function clientName() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_AI_CALL_TITLE', {
	        '[clientname]': "<a href=\"".concat(this.client.detailUrl, "\">"),
	        '[/clientname]': '</a>',
	        '#CLIENT_NAME#': main_core.Text.encode(this.client.fullName)
	      });
	    },
	    formattedDate: function formattedDate() {
	      var datetimeConverter = crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.client.activityCreated);
	      return datetimeConverter.toDatetimeString({
	        withDayOfWeek: false,
	        delimiter: ', '
	      });
	    },
	    isUsedCurrentVersionOfScriptViewMode: function isUsedCurrentVersionOfScriptViewMode() {
	      return this.viewMode === ViewMode.usedCurrentVersionOfScript;
	    },
	    isUsedOtherVersionOfScriptViewMode: function isUsedOtherVersionOfScriptViewMode() {
	      return this.viewMode === ViewMode.usedOtherVersionOfScript;
	    },
	    isUsedNotAssessmentScriptViewMode: function isUsedNotAssessmentScriptViewMode() {
	      return this.viewMode === ViewMode.usedNotAssessmentScript;
	    },
	    isAssessmentSettingsPendingViewMode: function isAssessmentSettingsPendingViewMode() {
	      return this.viewMode === ViewMode.assessmentSettingsPending;
	    },
	    isPendingViewMode: function isPendingViewMode() {
	      return this.viewMode === ViewMode.pending;
	    },
	    isErrorViewMode: function isErrorViewMode() {
	      return this.viewMode === ViewMode.error;
	    },
	    isEmptyScriptListViewMode: function isEmptyScriptListViewMode() {
	      return this.viewMode === ViewMode.emptyScriptList;
	    }
	  },
	  template: "\n\t\t<div class=\"call-quality__column --info\">\n\t\t\t<div>\n\t\t\t\t<div class=\"call-quality__header\">\n\t\t\t\t\t<div class=\"call-quality__header-row --flex\">\n\t\t\t\t\t\t<div :class=\"clientNameClassList\" v-html=\"clientName\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"call-quality__call-date\">\n\t\t\t\t\t\t\t{{ formattedDate }}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"call-quality__header-row\">\n\t\t\t\t\t\t<div id=\"crm-textbox-audio-player\" ref=\"audioPlayer\">\n\t\t\t\t\t\t\t<AudioPlayerComponent v-if=\"isShowAudioPlayer\" v-bind=\"audioProps\" />\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<ComplianceComponent \n\t\t\t\t\t:assessment=\"quality.assessment\"\n\t\t\t\t\t:title=\"quality.title\"\n\t\t\t\t\t:viewMode=\"viewMode\"\n\t\t\t\t\t:lowBorder=\"quality.lowBorder\"\n\t\t\t\t\t:highBorder=\"quality.highBorder\"\n\t\t\t\t/>\n\t\t\t\t<RecommendationBlock\n\t\t\t\t\tv-if=\"isUsedCurrentVersionOfScriptViewMode\"\n\t\t\t\t\t:recommendations=\"quality.recommendations\"\n\t\t\t\t\t:summary=\"quality.summary\"\n\t\t\t\t\t:use-in-rating=\"quality.useInRating\"\n\t\t\t\t/>\n\t\t\t\t<OtherScriptBlock\n\t\t\t\t\tv-if=\"isUsedOtherVersionOfScriptViewMode\"\n\t\t\t\t\t@showAssessment=\"onShowCurrentAssessment\"\n\t\t\t\t\t@doAssessment=\"onDoAssessment\"\n\t\t\t\t/>\n\t\t\t\t<NotAssessmentScriptBlock\n\t\t\t\t\tv-if=\"isUsedNotAssessmentScriptViewMode\"\n\t\t\t\t\t@doAssessment=\"onDoAssessment\"\n\t\t\t\t/>\n\t\t\t\t<AssessmentSettingsPendingBlock v-if=\"isAssessmentSettingsPendingViewMode\"/>\n\t\t\t\t<PendingBlock v-if=\"isPendingViewMode\"/>\n\t\t\t\t<ErrorBlock v-if=\"isErrorViewMode\" ref=\"errorBlock\"/>\n\t\t\t\t<EmptyScriptListBlock v-if=\"isEmptyScriptListViewMode\"/>\n\t\t\t</div>\n\t\t</div>\n\t\t<div class=\"call-quality__column --prompt\">\n\t\t\t<ScriptSelectorComponent\n\t\t\t\tref=\"scriptSelector\"\n\t\t\t\t:assessmentSettingsId=\"quality.assessmentSettingsId\"\n\t\t\t\t:assessmentSettingsStatus=\"quality.assessmentSettingsStatus\"\n\t\t\t\t:assessmentSettingsTitle=\"quality.title\"\n\t\t\t\t:isPromptChanged=\"quality.isPromptChanged\"\n\t\t\t\t:promptUpdatedAt=\"quality.promptUpdatedAt\"\n\t\t\t\t:prompt=\"prompt\"\n\t\t\t\t:viewMode=\"viewMode\"\n\t\t\t\t@onBeforeSelect=\"onChangeScript\"\n\t\t\t\t@onShowActualPrompt=\"onShowActualPrompt\"\n\t\t\t\t@doAssessment=\"onDoAssessment\"\n\t\t\t/>\n\t\t</div>\n\t"
	};

	var _templateObject$2, _templateObject2$1, _templateObject3, _templateObject4, _templateObject5;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var Trend = Object.freeze({
	  up: 1,
	  down: -1,
	  noChanges: 0
	});
	var ARTICLE_CODE$2 = '23240682';
	var ARTICLE_ANCHOR = 'rate';
	var _id = /*#__PURE__*/new WeakMap();
	var _rating = /*#__PURE__*/new WeakMap();
	var _prevRating = /*#__PURE__*/new WeakMap();
	var _userPhotoUrl = /*#__PURE__*/new WeakMap();
	var _articleCode = /*#__PURE__*/new WeakMap();
	var _articleAnchor = /*#__PURE__*/new WeakMap();
	var _useSkeletonMode = /*#__PURE__*/new WeakMap();
	var _getSkeleton = /*#__PURE__*/new WeakSet();
	var _getContent = /*#__PURE__*/new WeakSet();
	var _getAvatar = /*#__PURE__*/new WeakSet();
	var _getTrendClass = /*#__PURE__*/new WeakSet();
	var _getTrend = /*#__PURE__*/new WeakSet();
	var _showArticle = /*#__PURE__*/new WeakSet();
	var _layout = /*#__PURE__*/new WeakSet();
	var Rating = /*#__PURE__*/function () {
	  function Rating() {
	    babelHelpers.classCallCheck(this, Rating);
	    _classPrivateMethodInitSpec$1(this, _layout);
	    _classPrivateMethodInitSpec$1(this, _showArticle);
	    _classPrivateMethodInitSpec$1(this, _getTrend);
	    _classPrivateMethodInitSpec$1(this, _getTrendClass);
	    _classPrivateMethodInitSpec$1(this, _getAvatar);
	    _classPrivateMethodInitSpec$1(this, _getContent);
	    _classPrivateMethodInitSpec$1(this, _getSkeleton);
	    _classPrivateFieldInitSpec$2(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _rating, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _prevRating, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _userPhotoUrl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$2(this, _articleCode, {
	      writable: true,
	      value: ARTICLE_CODE$2
	    });
	    _classPrivateFieldInitSpec$2(this, _articleAnchor, {
	      writable: true,
	      value: ARTICLE_ANCHOR
	    });
	    _classPrivateFieldInitSpec$2(this, _useSkeletonMode, {
	      writable: true,
	      value: true
	    });
	    babelHelpers.classPrivateFieldSet(this, _id, "crm.ai.call.quality-rating-".concat(main_core.Text.getRandom()));
	  }
	  babelHelpers.createClass(Rating, [{
	    key: "render",
	    value: function render() {
	      var content = babelHelpers.classPrivateFieldGet(this, _useSkeletonMode) ? _classPrivateMethodGet$1(this, _getSkeleton, _getSkeleton2).call(this) : _classPrivateMethodGet$1(this, _getContent, _getContent2).call(this);
	      return main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"call-quality__rating__container\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _id), content);
	    } // @todo
	  }, {
	    key: "setRating",
	    value: function setRating(rating) {
	      babelHelpers.classPrivateFieldSet(this, _rating, rating);
	    }
	  }, {
	    key: "setPrevRating",
	    value: function setPrevRating(rating) {
	      babelHelpers.classPrivateFieldSet(this, _prevRating, rating);
	    }
	  }, {
	    key: "setUserPhotoUrl",
	    value: function setUserPhotoUrl(userPhotoUrl) {
	      babelHelpers.classPrivateFieldSet(this, _userPhotoUrl, userPhotoUrl);
	    }
	  }, {
	    key: "setSkeletonMode",
	    value: function setSkeletonMode() {
	      var useSkeletonMode = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      if (babelHelpers.classPrivateFieldGet(this, _useSkeletonMode) !== useSkeletonMode) {
	        babelHelpers.classPrivateFieldSet(this, _useSkeletonMode, useSkeletonMode);
	        _classPrivateMethodGet$1(this, _layout, _layout2).call(this);
	      }
	    }
	  }]);
	  return Rating;
	}();
	function _getSkeleton2() {
	  return main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	}
	function _getContent2() {
	  return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"call-quality__rating__text-container\">\n\t\t\t\t", "\n\t\t\t\t<div \n\t\t\t\t\tclass=\"call-quality__rating_article ui-icon-set --help\"\n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t></div>\n\t\t\t</div>\n\t\t\t<div class=\"call-quality__rating__value-container\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"call-quality__rating__value\">\n\t\t\t\t\t", "\n\t\t\t\t\t<span class=\"call-quality__rating__measure\">%</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"call-quality__rating__trend ", "\"></div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_RATING'), _classPrivateMethodGet$1(this, _showArticle, _showArticle2).bind(this), _classPrivateMethodGet$1(this, _getAvatar, _getAvatar2).call(this), babelHelpers.classPrivateFieldGet(this, _rating), _classPrivateMethodGet$1(this, _getTrendClass, _getTrendClass2).call(this));
	}
	function _getAvatar2() {
	  if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _userPhotoUrl))) {
	    return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div\n\t\t\t\t\tclass=\"call-quality__rating__avatar\"\n\t\t\t\t\tstyle=\"background-image: url(", ")\"\n\t\t\t\t></div>\n\t\t\t"])), encodeURI(babelHelpers.classPrivateFieldGet(this, _userPhotoUrl)));
	  }
	  return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"call-quality__rating__avatar ui-icon ui-icon-common-user\">\n\t\t\t\t<i style=\"\"></i>\n\t\t\t</div>\n\t\t"])));
	}
	function _getTrendClass2() {
	  var trend = _classPrivateMethodGet$1(this, _getTrend, _getTrend2).call(this);
	  if (trend === Trend.up) {
	    return '--up';
	  }
	  if (trend === Trend.down) {
	    return '--down';
	  }
	  return '--no-changes';
	}
	function _getTrend2() {
	  if (babelHelpers.classPrivateFieldGet(this, _rating) > babelHelpers.classPrivateFieldGet(this, _prevRating)) {
	    return Trend.up;
	  }
	  if (babelHelpers.classPrivateFieldGet(this, _rating) < babelHelpers.classPrivateFieldGet(this, _prevRating)) {
	    return Trend.down;
	  }
	  return Trend.noChanges;
	}
	function _showArticle2() {
	  var _window$top$BX, _window$top$BX$Helper;
	  (_window$top$BX = window.top.BX) === null || _window$top$BX === void 0 ? void 0 : (_window$top$BX$Helper = _window$top$BX.Helper) === null || _window$top$BX$Helper === void 0 ? void 0 : _window$top$BX$Helper.show("redirect=detail&code=".concat(babelHelpers.classPrivateFieldGet(this, _articleCode), "&anchor=").concat(babelHelpers.classPrivateFieldGet(this, _articleAnchor)));
	}
	function _layout2() {
	  var currentContent = document.getElementById(babelHelpers.classPrivateFieldGet(this, _id));
	  if (currentContent === null) {
	    return;
	  }
	  main_core.Dom.replace(currentContent, this.render());
	}

	var _templateObject$3;
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	/**
	 * @memberOf BX.Crm.AI.Call
	 *
	 */
	var _layoutComponent = /*#__PURE__*/new WeakMap();
	var _app = /*#__PURE__*/new WeakMap();
	var _jobId = /*#__PURE__*/new WeakMap();
	var _clientDetailUrl = /*#__PURE__*/new WeakMap();
	var _clientFullName = /*#__PURE__*/new WeakMap();
	var _activityCreated = /*#__PURE__*/new WeakMap();
	var _userPhotoUrl$1 = /*#__PURE__*/new WeakMap();
	var _assessmentSettingsId = /*#__PURE__*/new WeakMap();
	var _prepareRating = /*#__PURE__*/new WeakSet();
	var CallQuality$1 = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(CallQuality$$1, _Base);
	  function CallQuality$$1(data) {
	    var _babelHelpers$classPr;
	    var _this;
	    babelHelpers.classCallCheck(this, CallQuality$$1);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CallQuality$$1).call(this, data));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _prepareRating);
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _layoutComponent, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _app, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _jobId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _clientDetailUrl, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _clientFullName, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _activityCreated, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _userPhotoUrl$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(babelHelpers.assertThisInitialized(_this), _assessmentSettingsId, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _jobId, main_core.Type.isNumber(data.jobId) ? data.jobId : null);
	    _this.sliderId = "".concat(_this.id, "-").concat((_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _jobId)) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : _this.activityId);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _clientDetailUrl, main_core.Type.isStringFilled(data.clientDetailUrl) ? data.clientDetailUrl : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _clientFullName, main_core.Type.isStringFilled(data.clientFullName) ? data.clientFullName : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _userPhotoUrl$1, main_core.Type.isStringFilled(data.userPhotoUrl) ? data.userPhotoUrl : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _activityCreated, main_core.Type.isNumber(data.activityCreated) ? data.activityCreated : null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _assessmentSettingsId, main_core.Type.isNumber(data.assessmentSettingsId) ? data.assessmentSettingsId : null);
	    _this.rating = new Rating();
	    return _this;
	  }
	  babelHelpers.createClass(CallQuality$$1, [{
	    key: "initDefaultOptions",
	    value: function initDefaultOptions() {
	      this.id = 'crm-copilot-call-quality';
	      this.sliderTitle = main_core.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SLIDER_TITLE');
	      var width = Math.round(BX.SidePanel.Instance.getTopSlider().getWidth() * 0.75);
	      this.sliderWidth = width > 0 ? width : Math.round(window.screen.width * 0.75);
	      this.textboxTitle = main_core.Loc.getMessage('CRM_COPILOT_CALL_TRANSCRIPT_TITLE');
	      this.aiJobResultAndCallRecordAction = 'crm.timeline.ai.getCopilotCallQuality';
	    }
	  }, {
	    key: "getExtensions",
	    value: function getExtensions() {
	      var extensions = babelHelpers.get(babelHelpers.getPrototypeOf(CallQuality$$1.prototype), "getExtensions", this).call(this);
	      extensions.push('crm.ai.call');
	      return extensions;
	    }
	  }, {
	    key: "getSliderContentClass",
	    value: function getSliderContentClass() {
	      return 'crm-copilot-call-quality-wrapper';
	    }
	  }, {
	    key: "getSliderDesign",
	    value: function getSliderDesign() {
	      return {
	        margin: 0
	      };
	    }
	  }, {
	    key: "getSliderToolbar",
	    value: function getSliderToolbar() {
	      var _this2 = this;
	      return function () {
	        return [_this2.rating.render()];
	      };
	    }
	  }, {
	    key: "getSliderEvents",
	    value: function getSliderEvents() {
	      var _this3 = this;
	      var events = babelHelpers.get(babelHelpers.getPrototypeOf(CallQuality$$1.prototype), "getSliderEvents", this).call(this);
	      events.onLoad = function () {
	        babelHelpers.classPrivateFieldGet(_this3, _layoutComponent).showAudioPlayer();
	      };
	      events.onClose = function () {
	        babelHelpers.classPrivateFieldGet(_this3, _layoutComponent).close();
	      };
	      return events;
	    }
	    /**
	     * @override
	     */
	  }, {
	    key: "open",
	    value: function open() {
	      var _this4 = this;
	      var content = new Promise(function (resolve, reject) {
	        _this4.getAiJobResultAndCallRecord().then(function (response) {
	          var audioProps = _this4.prepareAudioProps(response);
	          _classPrivateMethodGet$2(_this4, _prepareRating, _prepareRating2).call(_this4, response.data);
	          var context = {
	            activityId: _this4.activityId,
	            ownerTypeId: _this4.ownerTypeId,
	            ownerId: _this4.ownerId,
	            jobId: babelHelpers.classPrivateFieldGet(_this4, _jobId)
	          };
	          babelHelpers.classPrivateFieldSet(_this4, _app, ui_vue3.BitrixVue.createApp(CallQuality, {
	            client: {
	              detailUrl: babelHelpers.classPrivateFieldGet(_this4, _clientDetailUrl),
	              fullName: babelHelpers.classPrivateFieldGet(_this4, _clientFullName),
	              activityCreated: babelHelpers.classPrivateFieldGet(_this4, _activityCreated)
	            },
	            data: response.data,
	            audioProps: audioProps,
	            context: context
	          }));
	          var container = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"call-quality__container\"></div>"])));
	          babelHelpers.classPrivateFieldSet(_this4, _layoutComponent, babelHelpers.classPrivateFieldGet(_this4, _app).mount(container));
	          main_core_events.EventEmitter.subscribe('crm.ai.callQuality:doAssessment', function () {
	            // @todo will the slider close?
	            //this.wrapperSlider?.close();
	          });
	          resolve(container);
	        })["catch"](function (response) {
	          _this4.showError(response);
	          _this4.wrapperSlider.destroy();
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
	          ownerId: this.ownerId,
	          jobId: babelHelpers.classPrivateFieldGet(this, _jobId),
	          assessmentSettingsId: babelHelpers.classPrivateFieldGet(this, _assessmentSettingsId)
	        }
	      };
	      return BX.ajax.runAction(this.aiJobResultAndCallRecordAction, actionData);
	    }
	  }, {
	    key: "getNotAccuratePhraseCode",
	    value: function getNotAccuratePhraseCode() {
	      return 'CRM_COPILOT_CALL_TRANSCRIPT_NOT_BE_ACCURATE';
	    }
	  }]);
	  return CallQuality$$1;
	}(Base);
	function _prepareRating2(_ref) {
	  var callQuality = _ref.callQuality;
	  if (!main_core.Type.isPlainObject(callQuality)) {
	    return;
	  }
	  var rating = this.rating;
	  if (callQuality) {
	    rating.setRating(callQuality === null || callQuality === void 0 ? void 0 : callQuality.ASSESSMENT_AVG);
	    rating.setPrevRating(callQuality === null || callQuality === void 0 ? void 0 : callQuality.PREV_ASSESSMENT_AVG);
	  }
	  if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldGet(this, _userPhotoUrl$1))) {
	    rating.setUserPhotoUrl(babelHelpers.classPrivateFieldGet(this, _userPhotoUrl$1));
	  }
	  rating.setSkeletonMode(false);
	}

	/**
	 * @memberOf BX.Crm.AI.Call
	 */
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
	    key: "getNotAccuratePhraseCode",
	    value: function getNotAccuratePhraseCode() {
	      return 'CRM_COPILOT_CALL_SUMMARY_NOT_BE_ACCURATE';
	    }
	  }, {
	    key: "prepareAiJobResult",
	    value: function prepareAiJobResult(response) {
	      return response.data.aiJobResult.summary;
	    }
	  }]);
	  return Summary;
	}(Base);

	/**
	 * @memberOf BX.Crm.AI.Call
	 */
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
	    key: "getNotAccuratePhraseCode",
	    value: function getNotAccuratePhraseCode() {
	      return 'CRM_COPILOT_CALL_TRANSCRIPT_NOT_BE_ACCURATE';
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
	  Transcription: Transcription,
	  CallQuality: CallQuality$1
	};

	exports.Call = Call;

}((this.BX.Crm.AI = this.BX.Crm.AI || {}),BX.Vue3,BX.Crm.AI,BX.Crm.AI,BX,BX.Crm,BX,BX.Pull,BX.UI,BX.Crm.Copilot,BX.Crm,BX.Crm.Timeline,BX.Event,BX.UI.BBCode.Formatter,BX,BX,BX));
//# sourceMappingURL=call.bundle.js.map
