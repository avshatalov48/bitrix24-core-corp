(function (exports,main_core,main_popup,ui_buttons,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2;
	var namespaceCrmWhatsNew = main_core.Reflection.namespace('BX.Crm.WhatsNew');
	var ActionViewMode = /*#__PURE__*/function () {
	  function ActionViewMode(_ref) {
	    var slides = _ref.slides,
	      steps = _ref.steps,
	      options = _ref.options,
	      closeOptionCategory = _ref.closeOptionCategory,
	      closeOptionName = _ref.closeOptionName;
	    babelHelpers.classCallCheck(this, ActionViewMode);
	    this.popup = null;
	    this.slides = [];
	    this.steps = [];
	    this.options = options;
	    this.slideClassName = 'crm-whats-new-slides-wrapper';
	    this.closeOptionCategory = main_core.Type.isString(closeOptionCategory) ? closeOptionCategory : '';
	    this.closeOptionName = main_core.Type.isString(closeOptionName) ? closeOptionName : '';
	    this.onClickClose = this.onClickCloseHandler.bind(this);
	    this.whatNewPromise = null;
	    this.tourPromise = null;
	    this.prepareSlides(slides);
	    this.prepareSteps(steps);
	  }
	  babelHelpers.createClass(ActionViewMode, [{
	    key: "prepareSlides",
	    value: function prepareSlides(slideConfigs) {
	      var _this = this;
	      if (slideConfigs.length) {
	        this.whatNewPromise = main_core.Runtime.loadExtension('ui.dialogs.whats-new');
	      }
	      this.slides = slideConfigs.map(function (slideConfig) {
	        return {
	          className: _this.slideClassName,
	          title: slideConfig.title,
	          html: _this.getPreparedSlideHtml(slideConfig)
	        };
	      }, this);
	    }
	  }, {
	    key: "getPreparedSlideHtml",
	    value: function getPreparedSlideHtml(slideConfig) {
	      var slide = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-whats-new-slide\">\n\t\t\t\t<img src=\"", "\" alt=\"\">\n\t\t\t\t<div class=\"crm-whats-new-slide-inner-title\"> ", " </div>\n\t\t\t\t<p>", "</p>\n\t\t\t</div>\n\t\t"])), slideConfig.innerImage, slideConfig.innerTitle, slideConfig.innerDescription);
	      var buttons = this.getPrepareSlideButtons(slideConfig);
	      if (buttons.length) {
	        var buttonsContainer = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-whats-new-slide-buttons\"></div>"])));
	        main_core.Dom.append(buttonsContainer, slide);
	        buttons.forEach(function (button) {
	          main_core.Dom.append(button.getContainer(), buttonsContainer);
	        });
	      }
	      return slide;
	    }
	  }, {
	    key: "getPrepareSlideButtons",
	    value: function getPrepareSlideButtons(slideConfig) {
	      var _this2 = this;
	      var buttons = [];
	      if (slideConfig.buttons) {
	        var className = 'ui-btn ui-btn-primary ui-btn-hover ui-btn-round ';
	        buttons = slideConfig.buttons.map(function (buttonConfig) {
	          var _buttonConfig$classNa;
	          var config = {
	            className: className + ((_buttonConfig$classNa = buttonConfig.className) !== null && _buttonConfig$classNa !== void 0 ? _buttonConfig$classNa : ''),
	            text: buttonConfig.text
	          };
	          if (buttonConfig.onClickClose) {
	            config.onclick = function () {
	              return _this2.onClickClose();
	            };
	          } else if (buttonConfig.helpDeskCode) {
	            config.onclick = function () {
	              return _this2.showHelpDesk(buttonConfig.helpDeskCode);
	            };
	          }
	          return new ui_buttons.Button(config);
	        }, this);
	      }
	      return buttons;
	    }
	  }, {
	    key: "prepareSteps",
	    value: function prepareSteps(stepsConfig) {
	      var _this3 = this;
	      if (stepsConfig.length) {
	        this.tourPromise = main_core.Runtime.loadExtension('ui.tour');
	      }
	      this.steps = stepsConfig.map(function (stepConfig) {
	        var step = {
	          id: stepConfig.id,
	          title: stepConfig.title,
	          text: stepConfig.text,
	          position: stepConfig.position,
	          article: stepConfig.article
	        };
	        if (stepConfig.useDynamicTarget) {
	          var _stepConfig$eventName;
	          var eventName = (_stepConfig$eventName = stepConfig.eventName) !== null && _stepConfig$eventName !== void 0 ? _stepConfig$eventName : _this3.getDefaultStepEventName(step.id);
	          main_core_events.EventEmitter.subscribeOnce(eventName, _this3.showStepByEvent.bind(_this3));
	        } else {
	          step.target = stepConfig.target;
	        }
	        return step;
	      }, this);
	    }
	  }, {
	    key: "showStepByEvent",
	    value: function showStepByEvent(event) {
	      var _this4 = this;
	      this.tourPromise.then(function (exports) {
	        var _event$data = event.data,
	          stepId = _event$data.stepId,
	          target = _event$data.target,
	          delay = _event$data.delay;
	        var step = _this4.steps.find(function (step) {
	          return step.id === stepId;
	        });
	        if (!step) {
	          console.error('step not found');
	          return;
	        }
	        setTimeout(function () {
	          step.target = target;
	          var Guide = exports.Guide;
	          var guide = _this4.createGuideInstance(Guide, [step], true);
	          _this4.setStepPopupOptions(guide.getPopup());
	          guide.showNextStep();
	          _this4.save();
	        }, delay || 0);
	      });
	    }
	  }, {
	    key: "getDefaultStepEventName",
	    value: function getDefaultStepEventName(stepId) {
	      return "Crm.WhatsNew::onTargetSetted::".concat(stepId);
	    }
	  }, {
	    key: "onClickCloseHandler",
	    value: function onClickCloseHandler() {
	      var lastPosition = this.popup.getLastPosition();
	      var currentPosition = this.popup.getPositionBySlide(this.popup.getCurrentSlide());
	      if (currentPosition >= lastPosition) {
	        this.popup.destroy();
	      } else {
	        this.popup.selectNextSlide();
	      }
	    }
	  }, {
	    key: "showHelpDesk",
	    value: function showHelpDesk(code) {
	      if (top.BX.Helper) {
	        top.BX.Helper.show("redirect=detail&code=".concat(code));
	        event.preventDefault();
	      }
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (this.slides.length) {
	        this.executeWhatsNew();
	      } else if (this.steps.length) {
	        this.executeGuide();
	      }
	    }
	  }, {
	    key: "executeWhatsNew",
	    value: function executeWhatsNew() {
	      var _this5 = this;
	      if (main_popup.PopupManager && main_popup.PopupManager.isAnyPopupShown()) {
	        return;
	      }
	      this.whatNewPromise.then(function (exports) {
	        var WhatsNew = exports.WhatsNew;
	        _this5.popup = new WhatsNew({
	          slides: _this5.slides,
	          popupOptions: {
	            height: 440
	          },
	          events: {
	            onDestroy: function onDestroy() {
	              _this5.save();
	              _this5.executeGuide();
	            }
	          }
	        });
	        _this5.popup.show();
	        ActionViewMode.whatsNewInstances.push(_this5.popup);
	      }, this);
	    }
	  }, {
	    key: "executeGuide",
	    value: function executeGuide() {
	      var _this6 = this;
	      var steps = main_core.clone(this.steps);
	      steps = steps.filter(function (step) {
	        return Boolean(step.target);
	      });
	      if (!steps.length) {
	        return;
	      }
	      this.tourPromise.then(function (exports) {
	        var Guide = exports.Guide;
	        var guide = _this6.createGuideInstance(Guide, steps, _this6.steps.length <= 1);
	        if (ActionViewMode.tourInstances.find(function (existedGuide) {
	          var _existedGuide$getPopu;
	          return (_existedGuide$getPopu = existedGuide.getPopup()) === null || _existedGuide$getPopu === void 0 ? void 0 : _existedGuide$getPopu.isShown();
	        })) {
	          return; // do not allow many guides at the same time
	        }

	        ActionViewMode.tourInstances.push(guide);
	        _this6.setStepPopupOptions(guide.getPopup());
	        if (guide.steps.length > 1 || _this6.options.showOverlayFromFirstStep) {
	          guide.start();
	        } else {
	          guide.showNextStep();
	        }
	        _this6.save();
	      });
	    }
	  }, {
	    key: "createGuideInstance",
	    value: function createGuideInstance(guide, steps, onEvents) {
	      var _this7 = this;
	      return new guide({
	        onEvents: onEvents,
	        steps: steps,
	        events: {
	          onFinish: function onFinish() {
	            if (!_this7.slides.length) {
	              _this7.save();
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "setStepPopupOptions",
	    value: function setStepPopupOptions(popup) {
	      var _this$options = this.options,
	        steps = _this$options.steps,
	        _this$options$hideTou = _this$options.hideTourOnMissClick,
	        hideTourOnMissClick = _this$options$hideTou === void 0 ? false : _this$options$hideTou;
	      popup.setAutoHide(hideTourOnMissClick);
	      if (steps && steps.popup) {
	        if (steps.popup.width) {
	          popup.setWidth(steps.popup.width);
	        }
	      }
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      BX.userOptions.save(this.closeOptionCategory, this.closeOptionName, 'closed', 'Y');
	    }
	  }]);
	  return ActionViewMode;
	}();
	babelHelpers.defineProperty(ActionViewMode, "tourInstances", []);
	babelHelpers.defineProperty(ActionViewMode, "whatsNewInstances", []);
	namespaceCrmWhatsNew.ActionViewMode = ActionViewMode;

}((this.window = this.window || {}),BX,BX.Main,BX.UI,BX.Event));
//# sourceMappingURL=script.js.map
