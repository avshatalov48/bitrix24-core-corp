/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_core_events,ui_buttons) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11;
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _steps = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("steps");
	var _getPreviousStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPreviousStep");
	var _updateStepsAvailability = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateStepsAvailability");
	var _getCurrentStepIndex = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentStepIndex");
	var _renderStepHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStepHeader");
	var _renderStepTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStepTitle");
	var _renderStepContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStepContainer");
	var _renderButtonsContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderButtonsContainer");
	var _renderArticle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderArticle");
	var _openHelpDesk = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openHelpDesk");
	var _renderStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStep");
	var _subscribeToPopupInit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToPopupInit");
	var _updateFade = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFade");
	var _renderBackButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderBackButton");
	var _renderCancelButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCancelButton");
	var _renderContinueButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContinueButton");
	var _onContinueButtonClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onContinueButtonClickHandler");
	var _renderFinishButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFinishButton");
	var _renderSaveChangesButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSaveChangesButton");
	var _openStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openStep");
	class Wizard {
	  constructor(_params2) {
	    Object.defineProperty(this, _openStep, {
	      value: _openStep2
	    });
	    Object.defineProperty(this, _renderSaveChangesButton, {
	      value: _renderSaveChangesButton2
	    });
	    Object.defineProperty(this, _renderFinishButton, {
	      value: _renderFinishButton2
	    });
	    Object.defineProperty(this, _onContinueButtonClickHandler, {
	      value: _onContinueButtonClickHandler2
	    });
	    Object.defineProperty(this, _renderContinueButton, {
	      value: _renderContinueButton2
	    });
	    Object.defineProperty(this, _renderCancelButton, {
	      value: _renderCancelButton2
	    });
	    Object.defineProperty(this, _renderBackButton, {
	      value: _renderBackButton2
	    });
	    Object.defineProperty(this, _updateFade, {
	      value: _updateFade2
	    });
	    Object.defineProperty(this, _subscribeToPopupInit, {
	      value: _subscribeToPopupInit2
	    });
	    Object.defineProperty(this, _renderStep, {
	      value: _renderStep2
	    });
	    Object.defineProperty(this, _openHelpDesk, {
	      value: _openHelpDesk2
	    });
	    Object.defineProperty(this, _renderArticle, {
	      value: _renderArticle2
	    });
	    Object.defineProperty(this, _renderButtonsContainer, {
	      value: _renderButtonsContainer2
	    });
	    Object.defineProperty(this, _renderStepContainer, {
	      value: _renderStepContainer2
	    });
	    Object.defineProperty(this, _renderStepTitle, {
	      value: _renderStepTitle2
	    });
	    Object.defineProperty(this, _renderStepHeader, {
	      value: _renderStepHeader2
	    });
	    Object.defineProperty(this, _getCurrentStepIndex, {
	      value: _getCurrentStepIndex2
	    });
	    Object.defineProperty(this, _updateStepsAvailability, {
	      value: _updateStepsAvailability2
	    });
	    Object.defineProperty(this, _getPreviousStep, {
	      value: _getPreviousStep2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _steps, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = _params2;
	    babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps] = _params2.steps;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	  }
	  render() {
	    const wrap = main_core.Tag.render(_t || (_t = _`
			<div class="tasks-wizard__container tasks-wizard__scope">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderStepHeader)[_renderStepHeader](babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps]), babelHelpers.classPrivateFieldLooseBase(this, _renderStepContainer)[_renderStepContainer](babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps]));
	    babelHelpers.classPrivateFieldLooseBase(this, _openStep)[_openStep](babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps][0]);
	    return wrap;
	  }
	  getCurrentStep() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps][babelHelpers.classPrivateFieldLooseBase(this, _getCurrentStepIndex)[_getCurrentStepIndex]()];
	  }
	  update() {
	    babelHelpers.classPrivateFieldLooseBase(this, _updateStepsAvailability)[_updateStepsAvailability]();
	  }
	  initHints() {
	    babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps].forEach(step => {
	      step.hintManager = top.BX.UI.Hint.createInstance({
	        id: `tasks-flow-edit-form-${step.id}-${main_core.Text.getRandom()}`,
	        className: 'skipInitByClassName',
	        popupParameters: {
	          targetContainer: step.node
	        }
	      });
	      step.hintManager.init(step.node);
	    });
	  }
	  hideHints() {
	    babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps].forEach(step => {
	      var _step$hintManager;
	      (_step$hintManager = step.hintManager) == null ? void 0 : _step$hintManager.hide();
	    });
	  }
	}
	function _getPreviousStep2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps][babelHelpers.classPrivateFieldLooseBase(this, _getCurrentStepIndex)[_getCurrentStepIndex]() - 1];
	}
	function _updateStepsAvailability2() {
	  let isAvailable = true;
	  for (const step of babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps]) {
	    if (isAvailable) {
	      main_core.Dom.removeClass(step.titleNode, '--unavailable');
	    } else {
	      main_core.Dom.addClass(step.titleNode, '--unavailable');
	    }
	    const isStepUnavailable = main_core.Type.isFunction(step.isFilled) && !step.isFilled();
	    isAvailable = isAvailable && !isStepUnavailable;
	    if (step.selected) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton.setState(isAvailable ? null : ui_buttons.ButtonState.DISABLED);
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].finishButton.setState(isAvailable ? null : ui_buttons.ButtonState.DISABLED);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton.setState(isAvailable ? null : ui_buttons.ButtonState.DISABLED);
	  }
	}
	function _getCurrentStepIndex2() {
	  for (const [index, step] of babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps].entries()) {
	    if (step.selected) {
	      return index;
	    }
	  }
	  return 0;
	}
	function _renderStepHeader2(steps) {
	  const firstSteps = steps.slice(0, -1);
	  const lastStep = steps.slice(-1)[0];
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="tasks-wizard__step_header">
				${0}
				${0}
			</div>
		`), firstSteps.map(step => babelHelpers.classPrivateFieldLooseBase(this, _renderStepTitle)[_renderStepTitle](step, false)), babelHelpers.classPrivateFieldLooseBase(this, _renderStepTitle)[_renderStepTitle](lastStep, true));
	}
	function _renderStepTitle2(step, isLast) {
	  const arrow = isLast ? null : main_core.Tag.render(_t3 || (_t3 = _`
			<div class="ui-icon-set --chevron-right" style="--ui-icon-set__icon-size: 15px;"></div>
		`));
	  step.titleNode = main_core.Tag.render(_t4 || (_t4 = _`
			<span
				class="tasks-wizard__step_name ${0}"
				data-id="tasks-wizard-step-${0}"
			>${0}</span>
		`), step.selected ? '--selected' : '', step.id, step.title);
	  main_core.Event.bind(step.titleNode, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _openStep)[_openStep](step));
	  return main_core.Tag.render(_t5 || (_t5 = _`
			<span class="tasks-wizard__step_name-container">
				${0}
				${0}
			</span>
		`), step.titleNode, arrow);
	}
	function _renderStepContainer2(steps) {
	  return main_core.Tag.render(_t6 || (_t6 = _`
			<div class="tasks-wizard__step_container">
				${0}
				${0}
			</div>
		`), steps.map(step => babelHelpers.classPrivateFieldLooseBase(this, _renderStep)[_renderStep](step)), babelHelpers.classPrivateFieldLooseBase(this, _renderButtonsContainer)[_renderButtonsContainer]());
	}
	function _renderButtonsContainer2() {
	  return main_core.Tag.render(_t7 || (_t7 = _`
			<div class="tasks-wizard__step_buttons-container">
				${0}
				${0}
				${0}
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderBackButton)[_renderBackButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderCancelButton)[_renderCancelButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderContinueButton)[_renderContinueButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderFinishButton)[_renderFinishButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderSaveChangesButton)[_renderSaveChangesButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderArticle)[_renderArticle]());
	}
	function _renderArticle2() {
	  if (!main_core.Type.isNumber(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].article)) {
	    return '';
	  }
	  const article = main_core.Tag.render(_t8 || (_t8 = _`
			<div class="tasks-wizard__article">
				<span class="ui-icon-set --help"></span>
				${0}
			</div>
		`), main_core.Loc.getMessage('TASKS_WIZARD_HELP'));
	  main_core.Event.bind(article, 'click', babelHelpers.classPrivateFieldLooseBase(this, _openHelpDesk)[_openHelpDesk].bind(this));
	  return article;
	}
	function _openHelpDesk2() {
	  top.BX.Helper.show(`redirect=detail&code=${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].article}`);
	}
	function _renderStep2(step) {
	  const fadeTop = main_core.Tag.render(_t9 || (_t9 = _`
			<div class="tasks-wizard__step_fade --top"></div>
		`));
	  const fadeBottom = main_core.Tag.render(_t10 || (_t10 = _`
			<div class="tasks-wizard__step_fade"></div>
		`));
	  step.node = main_core.Tag.render(_t11 || (_t11 = _`
			<div class="tasks-wizard__step ${0}">
				${0}
				${0}
				${0}
			</div>
		`), step.selected ? '--selected' : '', fadeTop, step.content, fadeBottom);
	  const observer = new IntersectionObserver(() => {
	    if (step.node.offsetWidth > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _updateFade)[_updateFade](step.node, fadeTop, fadeBottom);
	      observer.disconnect();
	    }
	  });
	  observer.observe(step.node);
	  main_core.Event.bind(step.node, 'scroll', () => babelHelpers.classPrivateFieldLooseBase(this, _updateFade)[_updateFade](step.node, fadeTop, fadeBottom));
	  babelHelpers.classPrivateFieldLooseBase(this, _subscribeToPopupInit)[_subscribeToPopupInit](step.node);
	  return step.node;
	}
	function _subscribeToPopupInit2(stepContainer) {
	  main_core_events.EventEmitter.subscribe('BX.Main.Popup:onInit', event => {
	    const data = event.getCompatData();
	    const bindElement = data[1];
	    const params = data[2];
	    if (main_core.Type.isDomNode(bindElement) && stepContainer.contains(bindElement)) {
	      params.targetContainer = stepContainer;
	    }
	  });
	}
	function _updateFade2(container, fadeTop, fadeBottom) {
	  const scrollTop = container.scrollTop;
	  const maxScroll = container.scrollHeight - container.offsetHeight;
	  const scrolledToBottom = Math.abs(scrollTop - maxScroll) < 1;
	  if (scrollTop === 0) {
	    main_core.Dom.removeClass(fadeTop, '--show');
	  } else {
	    main_core.Dom.addClass(fadeTop, '--show');
	  }
	  if (scrolledToBottom) {
	    main_core.Dom.removeClass(fadeBottom, '--show');
	  } else {
	    main_core.Dom.addClass(fadeBottom, '--show');
	  }
	}
	function _renderBackButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].backButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_BACK'),
	    color: ui_buttons.Button.Color.LIGHT_BORDER,
	    round: true,
	    size: BX.UI.Button.Size.LARGE,
	    onclick: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _openStep)[_openStep](babelHelpers.classPrivateFieldLooseBase(this, _getPreviousStep)[_getPreviousStep]());
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].backButton.setDataSet({
	    id: 'tasks-wizard-flow-back'
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].backButton.render();
	}
	function _renderCancelButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].cancelButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_CANCEL'),
	    color: ui_buttons.Button.Color.LIGHT_BORDER,
	    round: true,
	    size: BX.UI.Button.Size.LARGE,
	    onclick: () => babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].onCancel()
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].cancelButton.setDataSet({
	    id: 'tasks-wizard-flow-cancel'
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].cancelButton.render();
	}
	function _renderContinueButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_CONTINUE'),
	    color: ui_buttons.Button.Color.LIGHT_BORDER,
	    round: true,
	    size: BX.UI.Button.Size.LARGE,
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onContinueButtonClickHandler)[_onContinueButtonClickHandler].bind(this)
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton.setDataSet({
	    id: 'tasks-wizard-flow-continue'
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton.render();
	}
	async function _onContinueButtonClickHandler2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton.getState() === ui_buttons.ButtonState.DISABLED) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params]).onDisabledContinueButtonClick) == null ? void 0 : _babelHelpers$classPr.call(_babelHelpers$classPr2);
	    return;
	  }
	  if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].onContinueHandler) && babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton.getState() === null) {
	    const canContinue = await babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].onContinueHandler();
	    if (!canContinue) {
	      return;
	    }
	  }
	  const currentStep = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentStepIndex)[_getCurrentStepIndex]();
	  const nextStep = currentStep + 1;
	  babelHelpers.classPrivateFieldLooseBase(this, _openStep)[_openStep](babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps][nextStep]);
	}
	function _renderFinishButton2() {
	  var _babelHelpers$classPr3;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].finishButton = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].finishButton;
	  (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].finishButton) == null ? void 0 : _babelHelpers$classPr3.setDataSet({
	    id: 'tasks-wizard-flow-finish'
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].finishButton.render();
	}
	function _renderSaveChangesButton2() {
	  var _babelHelpers$classPr4, _babelHelpers$classPr5;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].saveChangesButton;
	  (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton) == null ? void 0 : _babelHelpers$classPr4.setDataSet({
	    id: 'tasks-wizard-flow-save'
	  });
	  return (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton) == null ? void 0 : _babelHelpers$classPr5.render();
	}
	function _openStep2(currentStep) {
	  const index = babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps].findIndex(step => step.id === currentStep.id);
	  babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps].forEach(step => {
	    step.selected = false;
	    main_core.Dom.removeClass(step.titleNode, '--selected');
	    main_core.Dom.removeClass(step.node, '--selected');
	  });
	  currentStep.selected = true;
	  main_core.Dom.addClass(currentStep.titleNode, '--selected');
	  main_core.Dom.addClass(currentStep.node, '--selected');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].finishButton.getContainer(), 'display', 'none');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton.getContainer(), 'display', '');
	  if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton.getContainer(), 'display', '');
	  }
	  if (index === babelHelpers.classPrivateFieldLooseBase(this, _steps)[_steps].length - 1) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].finishButton.getContainer(), 'display', '');
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].continueButton.getContainer(), 'display', 'none');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton) {
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].saveChangesButton.getContainer(), 'display', 'none');
	    }
	  }
	  if (index > 0) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].backButton.getContainer(), 'display', '');
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].cancelButton.getContainer(), 'display', 'none');
	  } else {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].backButton.getContainer(), 'display', 'none');
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].cancelButton.getContainer(), 'display', '');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _updateStepsAvailability)[_updateStepsAvailability]();
	}

	exports.Wizard = Wizard;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX.Event,BX.UI));
//# sourceMappingURL=wizard.bundle.js.map
