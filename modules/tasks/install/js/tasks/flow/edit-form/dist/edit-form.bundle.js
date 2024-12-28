/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_popup,ui_buttons,tasks_wizard,tasks_intervalSelector,main_polyfill_intersectionobserver,pull_client,ui_entitySelector,main_core_events,main_core,ui_formElements_view,ui_lottie) {
	'use strict';

	class FormPage {
	  getId() {}
	  getTitle() {}
	  setFlow(flow) {}
	  render() {}
	  getFields(flowData) {}
	  getRequiredData() {
	    return [];
	  }
	  update() {
	    this.cleanErrors();
	  }
	  cleanErrors() {}
	  showErrors(incorrectData) {}
	  onContinueClick(flowData = {}) {
	    return Promise.resolve(true);
	  }
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5;
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _renderChecker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderChecker");
	var _setHint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setHint");
	var _renderValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderValue");
	var _renderInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderInput");
	var _renderEntitySelectorValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderEntitySelectorValue");
	var _onEntitySelectorItemSelectedHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEntitySelectorItemSelectedHandler");
	var _getSelectedItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectedItem");
	class ValueChecker extends main_core_events.EventEmitter {
	  constructor(params) {
	    super(params);
	    Object.defineProperty(this, _getSelectedItem, {
	      value: _getSelectedItem2
	    });
	    Object.defineProperty(this, _onEntitySelectorItemSelectedHandler, {
	      value: _onEntitySelectorItemSelectedHandler2
	    });
	    Object.defineProperty(this, _renderEntitySelectorValue, {
	      value: _renderEntitySelectorValue2
	    });
	    Object.defineProperty(this, _renderInput, {
	      value: _renderInput2
	    });
	    Object.defineProperty(this, _renderValue, {
	      value: _renderValue2
	    });
	    Object.defineProperty(this, _setHint, {
	      value: _setHint2
	    });
	    Object.defineProperty(this, _renderChecker, {
	      value: _renderChecker2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Tasks.Flow.EditForm.ValueChecker');
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = {};
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector) {
	      var _babelHelpers$classPr;
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].value = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector.getPreselectedItems()[0]) == null ? void 0 : _babelHelpers$classPr[1];
	    }
	  }
	  isChecked() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker.isChecked();
	  }
	  getValue() {
	    var _babelHelpers$classPr2, _babelHelpers$classPr3;
	    const entitySelectorValue = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedItem)[_getSelectedItem]()) == null ? void 0 : _babelHelpers$classPr2.id;
	    return ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue) == null ? void 0 : _babelHelpers$classPr3.value) || entitySelectorValue || babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].placeholder;
	  }
	  setErrors(errors) {
	    var _babelHelpers$classPr4;
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker) == null ? void 0 : _babelHelpers$classPr4.setErrors(errors);
	  }
	  cleanError() {
	    var _babelHelpers$classPr5;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker) == null ? void 0 : _babelHelpers$classPr5.cleanError();
	  }
	  getInputNode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _renderInput)[_renderInput]();
	  }
	  getChecker() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker;
	  }
	  disable(disabled) {
	    if (disabled) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker.switcher.check(true);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker.switcher.disable(disabled);
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap = main_core.Tag.render(_t || (_t = _`
			<div
				class="tasks-flow__create-value-checker ${0}"
				data-id="tasks-flow-value-checker-${0}"
			>
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].value ? '' : '--off', babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].id, babelHelpers.classPrivateFieldLooseBase(this, _renderChecker)[_renderChecker](), babelHelpers.classPrivateFieldLooseBase(this, _renderValue)[_renderValue](), babelHelpers.classPrivateFieldLooseBase(this, _renderEntitySelectorValue)[_renderEntitySelectorValue]());
	    this.update();
	    const observer = new IntersectionObserver(() => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap.offsetWidth > 0) {
	        this.update();
	        observer.disconnect();
	      }
	    });
	    observer.observe(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap);
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap;
	  }
	  update() {
	    var _babelHelpers$classPr6;
	    (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap.closest('form')) == null ? void 0 : _babelHelpers$classPr6.dispatchEvent(new window.Event('change'));
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap, '--off');
	    if (this.isChecked()) {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap, '--off');
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector) {
	      var _babelHelpers$classPr7, _babelHelpers$classPr8;
	      babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].entitySelector.innerText = (_babelHelpers$classPr7 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedItem)[_getSelectedItem]()) == null ? void 0 : _babelHelpers$classPr8.title.text) != null ? _babelHelpers$classPr7 : main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_SELECT');
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].placeholder) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].disabledValue.innerText = this.getValue();
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].disabledValue, 'display', '');
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue.style.width = `${babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].disabledValue.offsetWidth + 7}px`;
	    const checkerField = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].wrap.querySelector('.ui-section__field');
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue.style.height = `${checkerField.offsetHeight}px`;
	  }
	}
	function _renderChecker2() {
	  var _babelHelpers$classPr9, _babelHelpers$classPr10, _babelHelpers$classPr11;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker = new ui_formElements_view.Checker({
	    checked: Boolean(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].value),
	    title: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].title,
	    hideSeparator: true,
	    size: (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].size) != null ? _babelHelpers$classPr9 : 'small',
	    isFieldDisabled: (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isFieldDisabled) != null ? _babelHelpers$classPr10 : false
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker.subscribe('change', baseEvent => {
	    const isChecked = baseEvent.getData();
	    this.update();
	    if (isChecked && babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue) {
	      const length = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue.value.length;
	      babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue.focus();
	      babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue.setSelectionRange(length, length);
	    }
	  });
	  main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker.switcher, 'lock', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _setHint)[_setHint](true);
	    this.emit('lock', babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField);
	  });
	  main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker.switcher, 'unlock', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _setHint)[_setHint](false);
	    this.emit('unlock', babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checker.render();
	  babelHelpers.classPrivateFieldLooseBase(this, _setHint)[_setHint]((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].isFieldDisabled) != null ? _babelHelpers$classPr11 : false);
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField;
	}
	function _setHint2(isDisabled) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].hintText) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].hintOnDisabled === true && isDisabled === false) {
	    main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField, 'data-hint', null);
	    main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField, 'data-hint-no-icon', null);
	  } else {
	    main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField, 'data-hint', babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].hintText);
	    main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerContentField, 'data-hint-no-icon', true);
	  }
	}
	function _renderValue2() {
	  var _babelHelpers$classPr12;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].placeholder) {
	    return '';
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].disabledValue = main_core.Tag.render(_t2 || (_t2 = _`
			<span class="tasks-flow__create-value-checker_text">${0}</span>
		`), this.getValue());
	  return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="tasks-flow__create-value-checker_input">
				${0}
				<span>${0}</span>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderInput)[_renderInput](), (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].unit) != null ? _babelHelpers$classPr12 : '', babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].disabledValue);
	}
	function _renderInput2() {
	  var _babelHelpers$classPr13;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue = main_core.Tag.render(_t4 || (_t4 = _`
			<input class="ui-ctl-element" placeholder="${0}" value="${0}">
		`), babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].placeholder, (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].value) != null ? _babelHelpers$classPr13 : babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].placeholder);
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue, 'input', () => this.update());
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].checkerValue;
	}
	function _renderEntitySelectorValue2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector) {
	    return '';
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].entitySelector = main_core.Tag.render(_t5 || (_t5 = _`
			<div class="tasks-flow-template-selector">
				${0}
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_SELECT'));
	  babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector.subscribe('Item:onSelect', babelHelpers.classPrivateFieldLooseBase(this, _onEntitySelectorItemSelectedHandler)[_onEntitySelectorItemSelectedHandler].bind(this));
	  babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector.subscribe('Item:onDeselect', () => this.update());
	  babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector.subscribe('onLoad', () => this.update());
	  babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector.setTargetNode(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].entitySelector);
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].entitySelector, 'click', () => {
	    if (this.isChecked()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector.show();
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].entitySelector;
	}
	function _onEntitySelectorItemSelectedHandler2() {
	  this.update();
	  babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector.hide();
	}
	function _getSelectedItem2() {
	  var _babelHelpers$classPr14;
	  return (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].entitySelector) == null ? void 0 : _babelHelpers$classPr14.getSelectedItems()[0];
	}

	var _params$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _bind = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bind");
	var _getTextBeforeCursor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextBeforeCursor");
	var _normalizeNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("normalizeNumber");
	var _setCursorToFormattedPosition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setCursorToFormattedPosition");
	class BindFilterNumberInput {
	  constructor(params) {
	    Object.defineProperty(this, _setCursorToFormattedPosition, {
	      value: _setCursorToFormattedPosition2
	    });
	    Object.defineProperty(this, _normalizeNumber, {
	      value: _normalizeNumber2
	    });
	    Object.defineProperty(this, _getTextBeforeCursor, {
	      value: _getTextBeforeCursor2
	    });
	    Object.defineProperty(this, _bind, {
	      value: _bind2
	    });
	    Object.defineProperty(this, _params$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _bind)[_bind](params.input);
	  }
	}
	function _bind2(input) {
	  let dispatchedProgrammatically = false;
	  input.addEventListener('input', () => {
	    if (dispatchedProgrammatically) {
	      dispatchedProgrammatically = false;
	      return;
	    }
	    const textBeforeCursor = babelHelpers.classPrivateFieldLooseBase(this, _getTextBeforeCursor)[_getTextBeforeCursor](input);
	    input.value = babelHelpers.classPrivateFieldLooseBase(this, _normalizeNumber)[_normalizeNumber](input.value) || '';
	    babelHelpers.classPrivateFieldLooseBase(this, _setCursorToFormattedPosition)[_setCursorToFormattedPosition](input, textBeforeCursor);
	    dispatchedProgrammatically = true;
	    input.dispatchEvent(new Event('input'));
	  });
	}
	function _getTextBeforeCursor2(input) {
	  const selectionStart = input.selectionStart;
	  const text = input.value.slice(0, selectionStart);
	  return babelHelpers.classPrivateFieldLooseBase(this, _normalizeNumber)[_normalizeNumber](text);
	}
	function _normalizeNumber2(value) {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  let normalizedValue = value.replace(/\D/g, '');
	  normalizedValue = parseInt(normalizedValue, 10);
	  normalizedValue = Math.max((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1].min) != null ? _babelHelpers$classPr : 1, normalizedValue);
	  normalizedValue = Math.min((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _params$1)[_params$1].max) != null ? _babelHelpers$classPr2 : 9999, normalizedValue);
	  return `${normalizedValue || ''}`;
	}
	function _setCursorToFormattedPosition2(input, textBeforeCursor) {
	  const firstPart = textBeforeCursor.slice(0, -1);
	  const lastCharacter = textBeforeCursor.slice(-1);
	  const matches = input.value.match(`${firstPart}.*?${lastCharacter}`);
	  if (!matches) {
	    return;
	  }
	  const match = matches[0];
	  const formattedPosition = input.value.indexOf(match) + match.length;
	  input.setSelectionRange(formattedPosition, formattedPosition);
	}
	const bindFilterNumberInput = params => new BindFilterNumberInput(params);

	let _$1 = t => t,
	  _t$1,
	  _t2$1;
	var _params$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _flow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flow");
	var _getTasksCreatorsFromSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTasksCreatorsFromSelector");
	var _isShortInterval = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isShortInterval");
	var _renderPlannedCompletionTime = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPlannedCompletionTime");
	var _needUseSchedule = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needUseSchedule");
	class AboutPage extends FormPage {
	  constructor(params) {
	    super();
	    Object.defineProperty(this, _needUseSchedule, {
	      get: _get_needUseSchedule,
	      set: void 0
	    });
	    Object.defineProperty(this, _renderPlannedCompletionTime, {
	      value: _renderPlannedCompletionTime2
	    });
	    Object.defineProperty(this, _isShortInterval, {
	      value: _isShortInterval2
	    });
	    Object.defineProperty(this, _getTasksCreatorsFromSelector, {
	      value: _getTasksCreatorsFromSelector2
	    });
	    Object.defineProperty(this, _params$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _flow, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params$2)[_params$2] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow] = {};
	  }
	  setFlow(flow) {
	    babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow] = flow;
	  }
	  getId() {
	    return 'about-flow';
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_ABOUT_FLOW');
	  }
	  getRequiredData() {
	    return ['name', 'plannedCompletionTime', 'taskCreators'];
	  }
	  showErrors(incorrectData) {
	    if (incorrectData.includes('name')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_NAME_ERROR')]);
	    }
	    if (incorrectData.includes('plannedCompletionTime')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_PLANNED_COMPLETION_TIME_ERROR')]);
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime.emit('onSetErrors');
	    }
	    if (incorrectData.includes('taskCreators')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].taskCreatorsSelector.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_CREATORS_ERROR')]);
	    }
	  }
	  cleanErrors() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].taskCreatorsSelector.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime.emit('onCleanErrors');
	  }
	  getFields(flowData = {}) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _flowData$name, _babelHelpers$classPr4, _flowData$description, _babelHelpers$classPr5, _flowData$taskCreator, _flowData$plannedComp, _flowData$matchSchedu, _flowData$matchWorkTi, _babelHelpers$classPr6, _babelHelpers$classPr7;
	    const plannedCompletionTimeValue = parseInt((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime) == null ? void 0 : _babelHelpers$classPr.getValue(), 10);
	    const intervalDuration = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTimeIntervalSelector) == null ? void 0 : _babelHelpers$classPr2.getDuration();
	    const interval = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTimeIntervalSelector) == null ? void 0 : _babelHelpers$classPr3.getInterval();
	    return {
	      name: (_flowData$name = flowData.name) != null ? _flowData$name : (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName) == null ? void 0 : _babelHelpers$classPr4.getValue().trim(),
	      description: (_flowData$description = flowData.description) != null ? _flowData$description : (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowDescription) == null ? void 0 : _babelHelpers$classPr5.getValue(),
	      taskCreators: (_flowData$taskCreator = flowData.taskCreators) != null ? _flowData$taskCreator : babelHelpers.classPrivateFieldLooseBase(this, _getTasksCreatorsFromSelector)[_getTasksCreatorsFromSelector](),
	      plannedCompletionTime: (_flowData$plannedComp = flowData.plannedCompletionTime) != null ? _flowData$plannedComp : plannedCompletionTimeValue * intervalDuration || 0,
	      matchSchedule: (_flowData$matchSchedu = flowData.matchSchedule) != null ? _flowData$matchSchedu : babelHelpers.classPrivateFieldLooseBase(this, _isShortInterval)[_isShortInterval](interval),
	      matchWorkTime: (_flowData$matchWorkTi = flowData.matchWorkTime) != null ? _flowData$matchWorkTi : (_babelHelpers$classPr6 = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].skipWeekends) == null ? void 0 : _babelHelpers$classPr7.isChecked()) != null ? _babelHelpers$classPr6 : true
	    };
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName = new ui_formElements_view.TextInput({
	      id: 'tasks-flow-edit-form-field-name',
	      label: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_NAME'),
	      placeholder: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_NAME_EXAMPLE'),
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow].name
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowDescription = new ui_formElements_view.TextArea({
	      id: 'tasks-flow-edit-form-field-description',
	      label: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DESCRIPTION'),
	      placeholder: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DESCRIPTION_EXAMPLE'),
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow].description,
	      resizeOnlyY: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].taskCreatorsSelector = new ui_formElements_view.UserSelector({
	      id: 'tasks-flow-edit-form-field-creators',
	      label: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_WHO_CAN_ADD_TASKS'),
	      enableDepartments: true,
	      values: babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow].taskCreators
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].aboutPageForm = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<form class="tasks-flow__create-about">
				${0}
				${0}
				<div class="tasks-flow__create-separator --empty"></div>
				${0}
				<div class="tasks-flow__create-separator --empty"></div>
				${0}
			</form>
		`), babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowDescription.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].taskCreatorsSelector.render(), babelHelpers.classPrivateFieldLooseBase(this, _renderPlannedCompletionTime)[_renderPlannedCompletionTime]());
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].aboutPageForm, 'change', babelHelpers.classPrivateFieldLooseBase(this, _params$2)[_params$2].onChangeHandler);
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].aboutPageForm;
	  }
	  focusToEmptyName() {
	    const isEmpty = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName.getValue().trim().length === 0;
	    if (isEmpty) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName.getInputNode().focus();
	    }
	  }
	  async onContinueClick(flowData = {}) {
	    const {
	      data: response
	    } = await main_core.ajax.runAction('tasks.flow.Flow.isExists', {
	      data: {
	        flowData: flowData
	      }
	    });
	    if (response.exists) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].flowName.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_DUPLICATE_ERROR')]);
	      return false;
	    }
	    return true;
	  }
	}
	function _getTasksCreatorsFromSelector2() {
	  var _babelHelpers$classPr8;
	  let taskCreators = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].taskCreatorsSelector) == null ? void 0 : _babelHelpers$classPr8.getSelector().getTags().map(tag => [tag.entityId, tag.id]);
	  if (main_core.Type.isUndefined(taskCreators) || taskCreators.length === 0) {
	    var _babelHelpers$classPr9, _babelHelpers$classPr10;
	    taskCreators = (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].taskCreatorsSelector) == null ? void 0 : (_babelHelpers$classPr10 = _babelHelpers$classPr9.getSelector()) == null ? void 0 : _babelHelpers$classPr10.getDialog().getPreselectedItems();
	  }
	  return taskCreators;
	}
	function _isShortInterval2(interval) {
	  const shortIntervals = ['minutes', 'hours'];
	  return shortIntervals.includes(interval);
	}
	function _renderPlannedCompletionTime2() {
	  const value = babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow].plannedCompletionTime;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTimeIntervalSelector = new tasks_intervalSelector.IntervalSelector({
	    value
	  });
	  const needUseSchedule = babelHelpers.classPrivateFieldLooseBase(this, _needUseSchedule)[_needUseSchedule];
	  if (needUseSchedule) {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTimeIntervalSelector.subscribe('intervalChanged', event => {
	      const interval = event.getData().interval;
	      const isSkipWeekendsDisabled = babelHelpers.classPrivateFieldLooseBase(this, _isShortInterval)[_isShortInterval](interval);
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].skipWeekends.disable(isSkipWeekendsDisabled);
	    });
	  }
	  const duration = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTimeIntervalSelector.getDuration();
	  const plannedCompletionTimeLabel = `
			<div class="tasks-flow__create-title-with-hint">
				${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_PLANNED_COMPLETION_TIME')}
				<span
					data-id="plannedCompletionTimeHint"
					class="ui-hint"
					data-hint="${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_PLANNED_COMPLETION_TIME_HINT')}" 
					data-hint-no-icon
				>
					<span class="ui-hint-icon"></span>
				</span>
			</div>
		`;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime = new ui_formElements_view.TextInput({
	    label: plannedCompletionTimeLabel,
	    placeholder: '0',
	    inputDefaultWidth: true,
	    value: String(value / duration || '')
	  });
	  const maxInt = 2 ** 32 / 2 - 1;
	  const monthDuration = 60 * 60 * 24 * 31;
	  bindFilterNumberInput({
	    input: babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime.getInputNode(),
	    max: Math.floor(maxInt / monthDuration)
	  });
	  const interval = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTimeIntervalSelector.getInterval();
	  const isSkipWeekendsDisabled = needUseSchedule && babelHelpers.classPrivateFieldLooseBase(this, _isShortInterval)[_isShortInterval](interval);
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].skipWeekends = new ValueChecker({
	    id: 'planned-completion-time-skip-weekends',
	    title: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_SKIP_WEEKENDS'),
	    value: isSkipWeekendsDisabled ? true : babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow].id === 0 || babelHelpers.classPrivateFieldLooseBase(this, _flow)[_flow].matchWorkTime,
	    size: 'extra-small',
	    isFieldDisabled: isSkipWeekendsDisabled,
	    hintText: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DISABLED_SKIP_WEEKENDS_HINT'),
	    hintOnDisabled: true
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].skipWeekends.subscribe('lock', baseEvent => {
	    requestAnimationFrame(() => {
	      const hintManager = top.BX.UI.Hint.createInstance({
	        id: `tasks-flow-edit-form-about-page-${main_core.Text.getRandom()}`,
	        className: 'skipInitByClassName',
	        popupParameters: {
	          targetContainer: babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].aboutPageForm.closest('.tasks-wizard__step')
	        }
	      });
	      hintManager.initNode(baseEvent.getData());
	    });
	  });
	  const root = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div data-id="tasks-flow-edit-form-field-planned-time">
				<div class="tasks-flow__create-planned_completion-time">
					${0}
					<div class="tasks-flow__create-planned_completion-time-interval">
						${0}
					</div>
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTime.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].plannedCompletionTimeIntervalSelector.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].skipWeekends.render());
	  return root;
	}
	function _get_needUseSchedule() {
	  const settings = main_core.Extension.getSettings('tasks.flow.edit-form');
	  return settings.get('needUseSchedule');
	}

	var _userId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userId");
	var _onTemplateAdded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onTemplateAdded");
	var _onTemplateUpdated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onTemplateUpdated");
	class PullRequests extends main_core_events.EventEmitter {
	  constructor(userId) {
	    super();
	    Object.defineProperty(this, _onTemplateUpdated, {
	      value: _onTemplateUpdated2
	    });
	    Object.defineProperty(this, _onTemplateAdded, {
	      value: _onTemplateAdded2
	    });
	    Object.defineProperty(this, _userId, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Tasks.Flow.EditForm.PullRequests');
	    babelHelpers.classPrivateFieldLooseBase(this, _userId)[_userId] = parseInt(userId, 10);
	  }
	  getModuleId() {
	    return 'tasks';
	  }
	  getMap() {
	    return {
	      template_add: babelHelpers.classPrivateFieldLooseBase(this, _onTemplateAdded)[_onTemplateAdded].bind(this),
	      template_update: babelHelpers.classPrivateFieldLooseBase(this, _onTemplateUpdated)[_onTemplateUpdated].bind(this)
	    };
	  }
	}
	function _onTemplateAdded2(data) {
	  this.emit('templateAdded', {
	    template: {
	      id: data.TEMPLATE_ID,
	      title: data.TEMPLATE_TITLE
	    }
	  });
	}
	function _onTemplateUpdated2(data) {
	  this.emit('templateUpdated', {
	    template: {
	      id: data.TEMPLATE_ID,
	      title: data.TEMPLATE_TITLE
	    }
	  });
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$2,
	  _t3$1;
	const BIG_DEPARTMENT_USER_COUNT = 30;
	const HINT_MESSAGES_BY_COUNT = [{
	  condition: count => count === 0,
	  message: 'TASKS_FLOW_EDIT_FORM_THIS_IS_EMPTY_DEPARTMENT_HINT'
	}, {
	  condition: count => count > BIG_DEPARTMENT_USER_COUNT,
	  message: 'TASKS_FLOW_EDIT_FORM_THIS_IS_BIG_DEPARTMENT_HINT'
	}];
	var _params$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _flow$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flow");
	var _currentUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentUser");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _subscribeToPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToPull");
	var _onTemplateAddedHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onTemplateAddedHandler");
	var _onTemplateUpdatedHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onTemplateUpdatedHandler");
	var _showResponsibleListError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showResponsibleListError");
	var _getResponsiblesByDistributionType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResponsiblesByDistributionType");
	var _getResponsiblesFromSelectorByDistributionType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResponsiblesFromSelectorByDistributionType");
	var _getResponsiblesFromSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResponsiblesFromSelector");
	var _renderDistribution = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderDistribution");
	var _getResponsiblesSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResponsiblesSelector");
	var _renderDistributionType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderDistributionType");
	var _getTaskTemplateDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTaskTemplateDialog");
	var _getProjectLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProjectLabel");
	var _getHintMessageCodeByCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHintMessageCodeByCount");
	var _showDepartmentHint = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showDepartmentHint");
	var _getDepartmentsUsersCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDepartmentsUsersCount");
	class SettingsPage extends FormPage {
	  constructor(params) {
	    super();
	    Object.defineProperty(this, _getDepartmentsUsersCount, {
	      value: _getDepartmentsUsersCount2
	    });
	    Object.defineProperty(this, _showDepartmentHint, {
	      value: _showDepartmentHint2
	    });
	    Object.defineProperty(this, _getHintMessageCodeByCount, {
	      value: _getHintMessageCodeByCount2
	    });
	    Object.defineProperty(this, _getProjectLabel, {
	      value: _getProjectLabel2
	    });
	    Object.defineProperty(this, _getTaskTemplateDialog, {
	      value: _getTaskTemplateDialog2
	    });
	    Object.defineProperty(this, _renderDistributionType, {
	      value: _renderDistributionType2
	    });
	    Object.defineProperty(this, _getResponsiblesSelector, {
	      value: _getResponsiblesSelector2
	    });
	    Object.defineProperty(this, _renderDistribution, {
	      value: _renderDistribution2
	    });
	    Object.defineProperty(this, _getResponsiblesFromSelector, {
	      value: _getResponsiblesFromSelector2
	    });
	    Object.defineProperty(this, _getResponsiblesFromSelectorByDistributionType, {
	      value: _getResponsiblesFromSelectorByDistributionType2
	    });
	    Object.defineProperty(this, _getResponsiblesByDistributionType, {
	      value: _getResponsiblesByDistributionType2
	    });
	    Object.defineProperty(this, _showResponsibleListError, {
	      value: _showResponsibleListError2
	    });
	    Object.defineProperty(this, _onTemplateUpdatedHandler, {
	      value: _onTemplateUpdatedHandler2
	    });
	    Object.defineProperty(this, _onTemplateAddedHandler, {
	      value: _onTemplateAddedHandler2
	    });
	    Object.defineProperty(this, _subscribeToPull, {
	      value: _subscribeToPull2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _currentUser, {
	      get: _get_currentUser,
	      set: void 0
	    });
	    Object.defineProperty(this, _params$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _flow$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params$3)[_params$3] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  setFlow(flow) {
	    babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1] = flow;
	  }
	  getId() {
	    return 'settings';
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_SETTINGS');
	  }
	  getRequiredData() {
	    const requiredData = ['groupId', 'responsibleList'];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate.isChecked()) {
	      requiredData.push('templateId');
	    }
	    return requiredData;
	  }
	  update() {
	    super.update();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].queueDistribution, '--active');
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].manuallyDistribution, '--active');
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].himselfDistribution, '--active');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].queueRadio.checked) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].queueDistribution, '--active');
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].manuallyRadio.checked) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].manuallyDistribution, '--active');
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].himselfRadio.checked) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].himselfDistribution, '--active');
	    }
	  }
	  showErrors(incorrectData) {
	    if (incorrectData.includes('responsibleList')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showResponsibleListError)[_showResponsibleListError]();
	    }
	    if (incorrectData.includes('groupId')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].projectSelector.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_PROJECT_FOR_TASKS_ERROR')]);
	    }
	    if (incorrectData.includes('templateId')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_TEMPLATE_FOR_TASKS_ERROR')]);
	    }
	  }
	  cleanErrors() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].projectSelector.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesQueueSelector.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesHimselfSelector.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].moderatorSelector.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate.cleanError();
	  }
	  getFields(flowData = {}) {
	    var _flowData$distributio, _babelHelpers$classPr, _flowData$responsible, _babelHelpers$classPr3, _babelHelpers$classPr4, _flowData$notifyAtHal, _babelHelpers$classPr5, _babelHelpers$classPr6, _flowData$taskControl, _babelHelpers$classPr7, _babelHelpers$classPr8, _flowData$groupId, _flowData$templateId, _babelHelpers$classPr9;
	    const selectedDistributionType = new FormData(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].settingsPageForm).get('distribution');
	    const distributionType = (_flowData$distributio = flowData.distributionType) != null ? _flowData$distributio : selectedDistributionType || 'queue';
	    const responsibleList = babelHelpers.classPrivateFieldLooseBase(this, _getResponsiblesByDistributionType)[_getResponsiblesByDistributionType](distributionType, flowData);
	    let groupId = babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].groupId;
	    if ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].projectSelector) != null && _babelHelpers$classPr.getSelector().getDialog().isLoaded()) {
	      var _babelHelpers$classPr2;
	      groupId = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].projectSelector.getSelector().getTags()[0]) == null ? void 0 : _babelHelpers$classPr2.id;
	    }
	    return {
	      distributionType,
	      responsibleList,
	      responsibleCanChangeDeadline: (_flowData$responsible = flowData.responsibleCanChangeDeadline) != null ? _flowData$responsible : (_babelHelpers$classPr3 = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsibleCanChangeDeadline) == null ? void 0 : _babelHelpers$classPr4.isChecked()) != null ? _babelHelpers$classPr3 : false,
	      notifyAtHalfTime: (_flowData$notifyAtHal = flowData.notifyAtHalfTime) != null ? _flowData$notifyAtHal : (_babelHelpers$classPr5 = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].notifyAtHalfTime) == null ? void 0 : _babelHelpers$classPr6.isChecked()) != null ? _babelHelpers$classPr5 : false,
	      taskControl: (_flowData$taskControl = flowData.taskControl) != null ? _flowData$taskControl : (_babelHelpers$classPr7 = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskControl) == null ? void 0 : _babelHelpers$classPr8.isChecked()) != null ? _babelHelpers$classPr7 : false,
	      groupId: (_flowData$groupId = flowData.groupId) != null ? _flowData$groupId : groupId || 0,
	      templateId: (_flowData$templateId = flowData.templateId) != null ? _flowData$templateId : (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate) != null && _babelHelpers$classPr9.isChecked() ? babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate.getValue() : 0
	    };
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsibleCanChangeDeadline = new ValueChecker({
	      id: 'responsible-can-change-deadline',
	      title: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_RESPONSIBLE_CAN_CHANGE_DEADLINE'),
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].responsibleCanChangeDeadline
	    });
	    const notifyAtHalfTimeTitle = `
			<div class="tasks-flow__create-title-with-hint">
				<span>${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_AT_HALF_TIME')}</span>
				<span
					data-id="notifyAtHalfTimeHint"
					class="ui-hint ui-hint-flow-value-checker"
					data-hint="${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_AT_HALF_TIME_HINT')}" 
					data-hint-no-icon
				>
					<span class="ui-hint-icon ui-hint-icon-flow-value-checker"></span>
				</span>
			</div>
		`;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].notifyAtHalfTime = new ValueChecker({
	      id: 'notify-at-half-time',
	      title: notifyAtHalfTimeTitle,
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].notifyAtHalfTime
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskControl = new ValueChecker({
	      id: 'task-control',
	      title: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASK_CONTROL'),
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].taskControl
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].projectSelector = new ui_formElements_view.UserSelector({
	      label: babelHelpers.classPrivateFieldLooseBase(this, _getProjectLabel)[_getProjectLabel](),
	      enableUsers: false,
	      enableDepartments: false,
	      multiple: false,
	      entities: [{
	        id: 'project',
	        options: {
	          features: {
	            tasks: []
	          },
	          checkFeatureForCreate: true,
	          '!type': ['collab'],
	          isFromFlowCreationForm: true
	        }
	      }],
	      values: babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].groupId ? [['project', babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].groupId]] : []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate = new ValueChecker({
	      id: 'task-template',
	      title: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_ACCEPT_TASKS_BY_TEMPLATE_TITLE'),
	      entitySelector: babelHelpers.classPrivateFieldLooseBase(this, _getTaskTemplateDialog)[_getTaskTemplateDialog]()
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].settingsPageForm = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<form class="tasks-flow__create-settings">
				${0}
				<div class="tasks-flow__create-separator --empty"></div>
				${0}
				${0}
				${0}
				<div class="tasks-flow__create-separator"></div>
				${0}
				<div class="tasks-flow__create-separator --empty"></div>
				${0}
			</form>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderDistribution)[_renderDistribution](), babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsibleCanChangeDeadline.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].notifyAtHalfTime.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskControl.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].projectSelector.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate.render());
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].settingsPageForm, 'change', babelHelpers.classPrivateFieldLooseBase(this, _params$3)[_params$3].onChangeHandler);
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].settingsPageForm;
	  }
	  checkDepartmentUsersCount(selector) {
	    const selectedTags = selector.getTags();
	    const selectedDepartments = selectedTags.filter(tag => tag.getEntityId() === 'department');
	    let addedDepartments = [];
	    if (main_core.Type.isUndefined(this.selectedDepartments) || this.selectedDepartments.length === 0) {
	      addedDepartments = selectedDepartments;
	    } else {
	      addedDepartments = selectedDepartments.filter(departmentTag => !this.selectedDepartments.includes(departmentTag));
	    }
	    this.selectedDepartments = selectedDepartments;
	    if (addedDepartments.length === 0) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _getDepartmentsUsersCount)[_getDepartmentsUsersCount](addedDepartments).then(countArray => {
	      const departmentForHint = countArray.find(departmentData => departmentData.count > BIG_DEPARTMENT_USER_COUNT || departmentData.count === 0);
	      if (departmentForHint) {
	        const tag = addedDepartments.find(item => item.getId().toString() === departmentForHint.departmentId);
	        if (tag) {
	          babelHelpers.classPrivateFieldLooseBase(this, _showDepartmentHint)[_showDepartmentHint](tag, babelHelpers.classPrivateFieldLooseBase(this, _getHintMessageCodeByCount)[_getHintMessageCodeByCount](departmentForHint.count));
	        }
	      }
	    }).catch(error => {
	      console.error(error);
	    });
	  }
	}
	function _get_currentUser() {
	  const settings = main_core.Extension.getSettings('tasks.flow.edit-form');
	  return settings.currentUser;
	}
	function _init2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _subscribeToPull)[_subscribeToPull]();
	}
	function _subscribeToPull2() {
	  const pullRequests = new PullRequests(babelHelpers.classPrivateFieldLooseBase(this, _currentUser)[_currentUser]);
	  pullRequests.subscribe('templateAdded', babelHelpers.classPrivateFieldLooseBase(this, _onTemplateAddedHandler)[_onTemplateAddedHandler].bind(this));
	  pullRequests.subscribe('templateUpdated', babelHelpers.classPrivateFieldLooseBase(this, _onTemplateUpdatedHandler)[_onTemplateUpdatedHandler].bind(this));
	  pull_client.PULL.subscribe(pullRequests);
	}
	function _onTemplateAddedHandler2({
	  data
	}) {
	  const template = data.template;
	  const templateItem = {
	    id: template.id,
	    entityId: 'task-template',
	    title: template.title,
	    tabs: 'recents'
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplateDialog.addItem(templateItem);
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplateDialog.getItems().find(item => item.id === templateItem.id).select();
	}
	function _onTemplateUpdatedHandler2({
	  data
	}) {
	  const template = data.template;
	  const templateItem = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplateDialog.getItem({
	    id: template.id,
	    entityId: 'task-template'
	  });
	  if (main_core.Type.isStringFilled(template.title)) {
	    var _babelHelpers$classPr10;
	    templateItem == null ? void 0 : templateItem.setTitle(template.title);
	    (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplate) == null ? void 0 : _babelHelpers$classPr10.update();
	  }
	  if (!main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplateDialog.getSelectedItems())) {
	    templateItem == null ? void 0 : templateItem.select();
	  }
	}
	function _showResponsibleListError2() {
	  const distributionType = new FormData(babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].settingsPageForm).get('distribution');

	  // eslint-disable-next-line default-case
	  switch (distributionType) {
	    case 'manually':
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].moderatorSelector.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_MODERATOR_ERROR')]);
	      break;
	    case 'queue':
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesQueueSelector.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_RESPONSIBLES_ERROR')]);
	      break;
	    case 'himself':
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesHimselfSelector.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_TASKS_RESPONSIBLES_ERROR')]);
	      break;
	  }
	}
	function _getResponsiblesByDistributionType2(distributionType, flowData = {}) {
	  let responsibleList = [];
	  const isConsiderFlowResponsible = babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].distributionType === distributionType;
	  if (isConsiderFlowResponsible) {
	    responsibleList = babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].responsibleList;
	  }
	  const responsibleListFromSelector = babelHelpers.classPrivateFieldLooseBase(this, _getResponsiblesFromSelectorByDistributionType)[_getResponsiblesFromSelectorByDistributionType](distributionType);
	  if (!main_core.Type.isNull(responsibleListFromSelector)) {
	    responsibleList = responsibleListFromSelector;
	  }
	  const isConsiderFlowDataResponsible = flowData.distributionType === distributionType;
	  if (isConsiderFlowDataResponsible) {
	    var _flowData$responsible2;
	    return (_flowData$responsible2 = flowData.responsibleList) != null ? _flowData$responsible2 : responsibleList;
	  }
	  return responsibleList;
	}
	function _getResponsiblesFromSelectorByDistributionType2(distributionType) {
	  switch (distributionType) {
	    case 'manually':
	      return babelHelpers.classPrivateFieldLooseBase(this, _getResponsiblesFromSelector)[_getResponsiblesFromSelector](babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].moderatorSelector);
	    case 'queue':
	      return babelHelpers.classPrivateFieldLooseBase(this, _getResponsiblesFromSelector)[_getResponsiblesFromSelector](babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesQueueSelector);
	    case 'himself':
	      return babelHelpers.classPrivateFieldLooseBase(this, _getResponsiblesFromSelector)[_getResponsiblesFromSelector](babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesHimselfSelector);
	    default:
	      return null;
	  }
	}
	function _getResponsiblesFromSelector2(selector) {
	  if (selector != null && selector.getSelector().getDialog().isLoaded()) {
	    return selector == null ? void 0 : selector.getSelector().getTags().map(tag => [tag.entityId, tag.id]);
	  }
	  return null;
	}
	function _renderDistribution2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesQueueSelector = babelHelpers.classPrivateFieldLooseBase(this, _getResponsiblesSelector)[_getResponsiblesSelector](babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].distributionType === 'queue' ? babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].responsibleList : [], false);
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesHimselfSelector = babelHelpers.classPrivateFieldLooseBase(this, _getResponsiblesSelector)[_getResponsiblesSelector](babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].distributionType === 'himself' ? babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].responsibleList : []);
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].moderatorSelector = new ui_formElements_view.UserSelector({
	    enableAll: false,
	    enableDepartments: false,
	    multiple: false,
	    values: [['user', babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].distributionType === 'manually' && main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].responsibleList) ? babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].responsibleList[0][1] : babelHelpers.classPrivateFieldLooseBase(this, _currentUser)[_currentUser]]]
	  });
	  const {
	    root: queueDistribution,
	    radio: queueRadio
	  } = babelHelpers.classPrivateFieldLooseBase(this, _renderDistributionType)[_renderDistributionType]({
	    type: 'queue',
	    selector: babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesQueueSelector
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].queueDistribution = queueDistribution;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].queueRadio = queueRadio;
	  const {
	    root: manuallyDistribution,
	    radio: manuallyRadio
	  } = babelHelpers.classPrivateFieldLooseBase(this, _renderDistributionType)[_renderDistributionType]({
	    type: 'manually',
	    selector: babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].moderatorSelector
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].manuallyDistribution = manuallyDistribution;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].manuallyRadio = manuallyRadio;
	  const {
	    root: himselfDistribution,
	    radio: himselfRadio
	  } = babelHelpers.classPrivateFieldLooseBase(this, _renderDistributionType)[_renderDistributionType]({
	    type: 'himself',
	    selector: babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesHimselfSelector
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].himselfDistribution = himselfDistribution;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].himselfRadio = himselfRadio;
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].queueRadio.checked = babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].distributionType === 'queue';
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].manuallyRadio.checked = babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].distributionType === 'manually';
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].himselfRadio.checked = babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].distributionType === 'himself';
	  this.update();
	  const selector = babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].responsiblesHimselfSelector.getSelector();
	  selector.getDialog().subscribe('onHide', this.checkDepartmentUsersCount.bind(this, selector));
	  return main_core.Tag.render(_t2$2 || (_t2$2 = _$2`
			<div class="ui-section__field-container">
				<div class="ui-section__field-label_box">
					<label class="ui-section__field-label">
						${0}
					</label>
				</div>
				${0}
				${0}
				${0}
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DISTRIBUTION_TYPE'), babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].queueDistribution, babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].manuallyDistribution, babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].himselfDistribution);
	}
	function _getResponsiblesSelector2(responsibleValues, enableDepartments = true, multiple = true) {
	  return new ui_formElements_view.UserSelector({
	    enableAll: false,
	    multiple,
	    enableDepartments,
	    values: responsibleValues,
	    label: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DISTRIBUTION_QUEUE_SELECTOR_LABEL')
	  });
	}
	function _renderDistributionType2({
	  type,
	  selector
	}) {
	  return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
			<div class="tasks-flow__create-distribution-type --${0}" data-id="tasks-flow-distribution-${0}">
				<label class="ui-ctl ui-ctl-radio ui-ctl-wa">
					<input type="radio" name="distribution" value="${0}" class="ui-ctl-element" ref="radio">
					<div class="tasks-flow__create-distribution-type_title-container">
						<div class="tasks-flow__create-distribution-type_content">
						<div class="tasks-flow__create-distribution-type_title">
								<div class="tasks-flow__create-distribution-type_title-text">
									${0}
								</div>
								<span class="tasks-flow__create-distribution-type_label ui-label ui-label-primary ui-label-fill">
									<span class="ui-label-inner">
										${0}
									</span>
								</span>
							</div>
							<div class="tasks-flow__create-distribution-type_hint">
								${0}
							</div>
						</div>
						<div class="tasks-flow__create-distribution-type_icon --${0}"></div>
					</div>
				</label>
				<div class="tasks-flow__create-distribution-type_selector">
					${0}
				</div>
			</div>
		`), type, type, type, main_core.Loc.getMessage(`TASKS_FLOW_EDIT_FORM_DISTRIBUTION_${type.toUpperCase()}`), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_STUB_BUTTON'), main_core.Loc.getMessage(`TASKS_FLOW_EDIT_FORM_DISTRIBUTION_${type.toUpperCase()}_HINT`), type, selector == null ? void 0 : selector.render());
	}
	function _getTaskTemplateDialog2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplateDialog = new ui_entitySelector.Dialog({
	    width: 500,
	    context: 'flow',
	    preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].templateId ? [['task-template', babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].templateId]] : '',
	    enableSearch: true,
	    multiple: false,
	    entities: [{
	      id: 'task-template'
	    }]
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _layout$2)[_layout$2].taskTemplateDialog;
	}
	function _getProjectLabel2() {
	  const notifyEmptyProject = `
			<span
				data-id="notifyEmptyProjectHint"
				class="ui-hint ui-hint-flow-value-checker"
				data-hint="${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_EMPTY_PROJECT_HINT')}" 
				data-hint-no-icon
			>
				<span class="ui-hint-icon ui-hint-icon-flow-value-checker"></span>
			</span>
		`;
	  return `
			<div class="tasks-flow-field-label-container">
				<span>${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_PROJECT_FOR_TASKS')}</span>
				${babelHelpers.classPrivateFieldLooseBase(this, _flow$1)[_flow$1].id ? '' : notifyEmptyProject}
			</div>
		`;
	}
	function _getHintMessageCodeByCount2(count) {
	  const hintMessage = HINT_MESSAGES_BY_COUNT.find(hint => hint.condition(count));
	  return hintMessage ? hintMessage.message : '';
	}
	function _showDepartmentHint2(tag, code) {
	  const popup = new BX.PopupWindow({
	    content: BX.Loc.getMessage(code),
	    darkMode: true,
	    bindElement: tag.getContainer(),
	    angle: true,
	    contentPadding: 5,
	    maxWidth: 400,
	    offsetLeft: tag.getContainer().offsetWidth / 2,
	    autoHide: true,
	    closeByEsc: true
	  });
	  popup.show();
	}
	function _getDepartmentsUsersCount2(departments) {
	  const departmentsToBackend = departments.map(department => [department.getEntityId(), department.getId()]);
	  return main_core.ajax.runAction('tasks.flow.Flow.getDepartmentsMemberCount', {
	    data: {
	      departments: departmentsToBackend
	    }
	  }).then(result => {
	    return main_core.Type.isArrayFilled(result.errors) ? null : result.data;
	  }).catch(errors => {
	    console.error(errors);
	  });
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$3;
	var _params$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _flow$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flow");
	var _currentUser$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentUser");
	var _getCheckerValues = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCheckerValues");
	var _getCheckerNumericValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCheckerNumericValue");
	var _getInteger = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInteger");
	var _renderAnalyticsStub = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderAnalyticsStub");
	class ControlPage extends FormPage {
	  constructor(params) {
	    super();
	    Object.defineProperty(this, _renderAnalyticsStub, {
	      value: _renderAnalyticsStub2
	    });
	    Object.defineProperty(this, _getInteger, {
	      value: _getInteger2
	    });
	    Object.defineProperty(this, _getCheckerNumericValue, {
	      value: _getCheckerNumericValue2
	    });
	    Object.defineProperty(this, _getCheckerValues, {
	      value: _getCheckerValues2
	    });
	    Object.defineProperty(this, _currentUser$1, {
	      get: _get_currentUser$1,
	      set: void 0
	    });
	    Object.defineProperty(this, _params$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _flow$2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params$4)[_params$4] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _flow$2)[_flow$2] = {};
	  }
	  setFlow(flow) {
	    babelHelpers.classPrivateFieldLooseBase(this, _flow$2)[_flow$2] = flow;
	  }
	  getId() {
	    return 'control';
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_CONTROL');
	  }
	  getRequiredData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckerValues)[_getCheckerValues]().filter(checker => babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3][checker].isChecked());
	  }
	  showErrors(incorrectData) {
	    if (incorrectData.includes('ownerId')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].flowOwnerSelector.setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_OWNER_ERROR')]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _getCheckerValues)[_getCheckerValues]().forEach(checker => {
	      if (incorrectData.includes(checker)) {
	        babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3][checker].setErrors([main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_VALUE_ERROR')]);
	      }
	    });
	  }
	  cleanErrors() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].flowOwnerSelector.cleanError();
	    babelHelpers.classPrivateFieldLooseBase(this, _getCheckerValues)[_getCheckerValues]().forEach(checker => babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3][checker].cleanError());
	  }
	  getFields(flowData = {}) {
	    var _babelHelpers$classPr, _flowData$notifyOnQue, _flowData$notifyOnTas, _flowData$notifyWhenE;
	    let ownerId = babelHelpers.classPrivateFieldLooseBase(this, _currentUser$1)[_currentUser$1];
	    if ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].flowOwnerSelector) != null && _babelHelpers$classPr.getSelector().getDialog().isLoaded()) {
	      var _babelHelpers$classPr2;
	      ownerId = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].flowOwnerSelector.getSelector().getTags()[0]) == null ? void 0 : _babelHelpers$classPr2.id;
	    }
	    return {
	      ownerId: flowData.ownerId || ownerId || 0,
	      notifyOnQueueOverflow: (_flowData$notifyOnQue = flowData.notifyOnQueueOverflow) != null ? _flowData$notifyOnQue : babelHelpers.classPrivateFieldLooseBase(this, _getCheckerNumericValue)[_getCheckerNumericValue](babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnQueueOverflow),
	      notifyOnTasksInProgressOverflow: (_flowData$notifyOnTas = flowData.notifyOnTasksInProgressOverflow) != null ? _flowData$notifyOnTas : babelHelpers.classPrivateFieldLooseBase(this, _getCheckerNumericValue)[_getCheckerNumericValue](babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnTasksInProgressOverflow),
	      notifyWhenEfficiencyDecreases: (_flowData$notifyWhenE = flowData.notifyWhenEfficiencyDecreases) != null ? _flowData$notifyWhenE : babelHelpers.classPrivateFieldLooseBase(this, _getCheckerNumericValue)[_getCheckerNumericValue](babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyWhenEfficiencyDecreases)
	    };
	  }
	  render() {
	    const flowOwnerLabel = `
			<div class="tasks-flow__create-title-with-hint">
				${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_OWNER')}
				<span
					data-id="flowOwnerHint"
					class="ui-hint"
					data-hint="${main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_FLOW_OWNER_HINT')}" 
					data-hint-no-icon
				><span class="ui-hint-icon"></span></span>
			</div>
		`;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].flowOwnerSelector = new ui_formElements_view.UserSelector({
	      id: 'tasks-flow-edit-form-field-owner',
	      label: flowOwnerLabel,
	      enableAll: false,
	      enableDepartments: false,
	      multiple: false,
	      values: [['user', babelHelpers.classPrivateFieldLooseBase(this, _flow$2)[_flow$2].ownerId]]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnQueueOverflow = new ValueChecker({
	      id: 'notify-on-queue-overflow',
	      title: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_ON_QUEUE_OVERFLOW'),
	      placeholder: 50,
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow$2)[_flow$2].notifyOnQueueOverflow,
	      size: 'extra-small'
	    });
	    bindFilterNumberInput({
	      input: babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnQueueOverflow.getInputNode(),
	      max: 99999
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnTasksInProgressOverflow = new ValueChecker({
	      id: 'notify-on-tasks-in-progress-overflow',
	      title: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW'),
	      placeholder: 50,
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow$2)[_flow$2].notifyOnTasksInProgressOverflow,
	      size: 'extra-small'
	    });
	    bindFilterNumberInput({
	      input: babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnTasksInProgressOverflow.getInputNode(),
	      max: 99999
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyWhenEfficiencyDecreases = new ValueChecker({
	      id: 'notify-when-efficiency-decreases',
	      title: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_NOTIFY_WHEN_EFFICIENCY_DECREASES'),
	      placeholder: 70,
	      unit: '%',
	      value: babelHelpers.classPrivateFieldLooseBase(this, _flow$2)[_flow$2].notifyWhenEfficiencyDecreases,
	      size: 'extra-small'
	    });
	    bindFilterNumberInput({
	      input: babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyWhenEfficiencyDecreases.getInputNode(),
	      max: 100
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].analyticsPermissionsSelector = new ui_formElements_view.UserSelector({
	      id: 'tasks-flow-edit-form-field-analytics',
	      label: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_PERMISSIONS'),
	      enableAll: true,
	      enableDepartments: true,
	      className: ''
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].controlPageForm = main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<form class="tasks-flow__create-control">
				${0}
				${0}
				${0}
				${0}
				<div class="tasks-flow__create-separator"></div>
				${0}
			</form>
		`), babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].flowOwnerSelector.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnQueueOverflow.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyOnTasksInProgressOverflow.render(), babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].notifyWhenEfficiencyDecreases.render(), babelHelpers.classPrivateFieldLooseBase(this, _renderAnalyticsStub)[_renderAnalyticsStub]());
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].controlPageForm, 'change', babelHelpers.classPrivateFieldLooseBase(this, _params$4)[_params$4].onChangeHandler);
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout$3)[_layout$3].controlPageForm;
	  }
	}
	function _get_currentUser$1() {
	  const settings = main_core.Extension.getSettings('tasks.flow.edit-form');
	  return settings.currentUser;
	}
	function _getCheckerValues2() {
	  return ['notifyOnQueueOverflow', 'notifyOnTasksInProgressOverflow', 'notifyWhenEfficiencyDecreases'];
	}
	function _getCheckerNumericValue2(checker) {
	  return checker != null && checker.isChecked() ? babelHelpers.classPrivateFieldLooseBase(this, _getInteger)[_getInteger](checker.getValue()) : null;
	}
	function _getInteger2(value) {
	  return /^\d+$/.test(value) ? parseInt(value, 10) : 0;
	}
	function _renderAnalyticsStub2() {
	  return main_core.Tag.render(_t2$3 || (_t2$3 = _$3`
			<div class="tasks-flow__create_analytics-stub">
				<span>${0}</span>
				<span class="tasks-flow__create_analytics-stub-label ui-label ui-label-primary ui-label-fill">
					<span class="ui-label-inner">
						${0}
					</span>
				</span>
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_STUB_LABEL'), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_ANALYTICS_STUB_BUTTON'));
	}

	var nm = "Atom/Torrent animation 6";
	var v = "5.9.6";
	var fr = 60;
	var ip = 0;
	var op = 239;
	var w = 220;
	var h = 220;
	var ddd = 0;
	var markers = [];
	var assets = [{
	  nm: "[FRAME] Atom/Torrent animation 6 - Null / Vector - Null / Vector / Vector - Null / Vector / body - Null / body / Star 4 - Null / Star 4 / Star 2 - Null / Star 2 / Star 1 - Null / Star 1 / Star 3 - Null / Star 3",
	  fr: 60,
	  id: "lvc7ui4o20a2e3j8",
	  layers: [{
	    ty: 3,
	    ddd: 0,
	    ind: 4,
	    hd: false,
	    nm: "Atom/Torrent animation 6 - Null",
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 5,
	    hd: false,
	    nm: "Vector - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [51.5, 51.5]
	      },
	      o: {
	        a: 1,
	        k: [{
	          t: 51.00000000000006,
	          s: [1],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 80.00000000000001,
	          s: [0],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 88.99999999999997,
	          s: [0],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 104.32713754646757,
	          s: [0],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 202.1338289962817,
	          s: [100]
	        }]
	      },
	      p: {
	        a: 1,
	        k: [{
	          t: 104.32713754646757,
	          s: [-18.499899999999997, 109.5],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          },
	          ti: [0, 0],
	          to: [0, 0]
	        }, {
	          t: 202.1338289962817,
	          s: [101.5001, 109.5]
	        }]
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 6,
	    hd: false,
	    nm: "Vector",
	    parent: 5,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 1,
	        k: [{
	          t: 51.00000000000006,
	          s: [1],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 80.00000000000001,
	          s: [0],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 88.99999999999997,
	          s: [0],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 104.32713754646757,
	          s: [0],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 202.1338289962817,
	          s: [100]
	        }]
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 5,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[23.8604, 92.5209], [23.8604, 101.2021], [32.4925, 101.2021], [49.1681, 84.4316], [49.2122, 84.3877], [51, 80.0469], [49.2122, 75.7061], [49.1681, 75.6622], [32.3992, 58.7981], [23.7671, 58.7981], [23.7671, 67.4793], [30.6598, 74.4112], [6.1038, 74.4112], [-0.0001, 80.5497], [6.1038, 86.6882], [29.66, 86.6882], [23.8604, 92.5209]],
	            i: [[2.3836900000000014, -2.397260000000003], [-2.3837, -2.3972], [-2.3837, 2.3973], [0, 0], [0, 0], [0.0001, 1.5711], [1.1919, 1.1987], [0, 0], [0, 0], [2.3837, -2.3972], [-2.3837, -2.3972], [0, 0], [0, 0], [0, -3.3902], [-3.3711, 0], [0, 0], [0, 0]],
	            o: [[-2.3836900000000014, 2.397260000000003], [2.3837200000000003, 2.3972299999999933], [0, 0], [0, 0], [1.19191, -1.198679999999996], [0.00005000000000165983, -1.571070000000006], [0, 0], [0, 0], [-2.383700000000001, -2.3972499999999997], [-2.3837100000000007, 2.3972499999999997], [0, 0], [0, 0], [-3.37107, 0], [0, 3.3902300000000025], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[23.8604, 35.5209], [23.8604, 44.2021], [32.4925, 44.2021], [49.1681, 27.4316], [49.2122, 27.3877], [51, 23.0469], [49.2122, 18.7061], [49.1681, 18.6622], [32.3992, 1.7981], [23.7671, 1.7981], [23.7671, 10.4793], [30.6598, 17.4112], [6.1038, 17.4112], [-0.0001, 23.5497], [6.1038, 29.6882], [29.66, 29.6882], [23.8604, 35.5209]],
	            i: [[2.3836900000000014, -2.397260000000003], [-2.3837, -2.3972], [-2.3837, 2.3973], [0, 0], [0, 0], [0.0001, 1.5711], [1.1919, 1.1987], [0, 0], [0, 0], [2.3837, -2.3972], [-2.3837, -2.3972], [0, 0], [0, 0], [0, -3.3902], [-3.3711, 0], [0, 0], [0, 0]],
	            o: [[-2.3836900000000014, 2.397260000000003], [2.3837100000000007, 2.3972399999999965], [0, 0], [0, 0], [1.19191, -1.1986799999999995], [0.00005000000000165983, -1.5710699999999989], [0, 0], [0, 0], [-2.383700000000001, -2.39725], [-2.3837100000000007, 2.3972500000000005], [0, 0], [0, 0], [-3.37107, 0], [0, 3.390229999999999], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[75.8604, 62.5209], [75.8604, 71.2021], [84.4926, 71.2021], [101.1682, 54.4316], [101.2123, 54.3877], [103.0001, 50.0469], [101.2123, 45.7061], [101.1682, 45.6622], [84.3993, 28.7981], [75.7671, 28.7981], [75.7671, 37.4793], [82.6598, 44.4112], [58.1038, 44.4112], [51.9999, 50.5498], [58.1038, 56.6883], [81.66, 56.6883], [75.8604, 62.5209]],
	            i: [[2.3836900000000014, -2.397260000000003], [-2.3837, -2.3972], [-2.3837, 2.3973], [0, 0], [0, 0], [0.0001, 1.5711], [1.1919, 1.1987], [0, 0], [0, 0], [2.3837, -2.3972], [-2.3837, -2.3972], [0, 0], [0, 0], [0, -3.3902], [-3.3711, 0], [0, 0], [0, 0]],
	            o: [[-2.3836900000000014, 2.397260000000003], [2.3837199999999967, 2.3972299999999933], [0, 0], [0, 0], [1.1919100000000071, -1.198680000000003], [0.00005000000000165983, -1.5710699999999989], [0, 0], [0, 0], [-2.3837000000000046, -2.3972499999999997], [-2.3837099999999936, 2.3972499999999997], [0, 0], [0, 0], [-3.371070000000003, 0], [0, 3.3902300000000025], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [1, 1, 1, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 1
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 7,
	    hd: false,
	    nm: "Vector - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [51.5, 51.5]
	      },
	      o: {
	        a: 1,
	        k: [{
	          t: 104.57357324157103,
	          s: [100],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 202.3802646913852,
	          s: [0]
	        }]
	      },
	      p: {
	        a: 1,
	        k: [{
	          t: 104.57357324157103,
	          s: [101.5, 109.5],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          },
	          ti: [0, 0],
	          to: [0, 0]
	        }, {
	          t: 202.3802646913852,
	          s: [204.5, 109.5]
	        }]
	      },
	      r: {
	        a: 1,
	        k: [{
	          t: 7.29014597838927,
	          s: [0],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [1],
	            y: [1]
	          }
	        }, {
	          t: 21.050521638831196,
	          s: [-10],
	          o: {
	            x: [0],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 44.731168124242885,
	          s: [0]
	        }]
	      },
	      s: {
	        a: 1,
	        k: [{
	          t: 2.8996427101595734,
	          s: [-0.1, -0.1],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 41.90876574164156,
	          s: [100, 100],
	          o: {
	            x: [0],
	            y: [0]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 53.96477032050418,
	          s: [100, 100],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 61.36616958252654,
	          s: [110.00000000000001, 110.00000000000001],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 76.16907992324106,
	          s: [100, 100]
	        }]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 8,
	    hd: false,
	    nm: "Vector",
	    parent: 7,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 1,
	        k: [{
	          t: 104.57357324157103,
	          s: [100],
	          o: {
	            x: [0.5],
	            y: [0.35]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 202.3802646913852,
	          s: [0]
	        }]
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 240,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 5,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[23.8604, 92.5209], [23.8604, 101.2021], [32.4925, 101.2021], [49.1681, 84.4316], [49.2122, 84.3877], [51, 80.0469], [49.2122, 75.7061], [49.1681, 75.6622], [32.3992, 58.7981], [23.7671, 58.7981], [23.7671, 67.4793], [30.6598, 74.4112], [6.1038, 74.4112], [-0.0001, 80.5497], [6.1038, 86.6882], [29.66, 86.6882], [23.8604, 92.5209]],
	            i: [[2.3836900000000014, -2.397260000000003], [-2.3837, -2.3972], [-2.3837, 2.3973], [0, 0], [0, 0], [0.0001, 1.5711], [1.1919, 1.1987], [0, 0], [0, 0], [2.3837, -2.3972], [-2.3837, -2.3972], [0, 0], [0, 0], [0, -3.3902], [-3.3711, 0], [0, 0], [0, 0]],
	            o: [[-2.3836900000000014, 2.397260000000003], [2.3837200000000003, 2.3972299999999933], [0, 0], [0, 0], [1.19191, -1.198679999999996], [0.00005000000000165983, -1.571070000000006], [0, 0], [0, 0], [-2.383700000000001, -2.3972499999999997], [-2.3837100000000007, 2.3972499999999997], [0, 0], [0, 0], [-3.37107, 0], [0, 3.3902300000000025], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[23.8604, 35.5209], [23.8604, 44.2021], [32.4925, 44.2021], [49.1681, 27.4316], [49.2122, 27.3877], [51, 23.0469], [49.2122, 18.7061], [49.1681, 18.6622], [32.3992, 1.7981], [23.7671, 1.7981], [23.7671, 10.4793], [30.6598, 17.4112], [6.1038, 17.4112], [-0.0001, 23.5497], [6.1038, 29.6882], [29.66, 29.6882], [23.8604, 35.5209]],
	            i: [[2.3836900000000014, -2.397260000000003], [-2.3837, -2.3972], [-2.3837, 2.3973], [0, 0], [0, 0], [0.0001, 1.5711], [1.1919, 1.1987], [0, 0], [0, 0], [2.3837, -2.3972], [-2.3837, -2.3972], [0, 0], [0, 0], [0, -3.3902], [-3.3711, 0], [0, 0], [0, 0]],
	            o: [[-2.3836900000000014, 2.397260000000003], [2.3837100000000007, 2.3972399999999965], [0, 0], [0, 0], [1.19191, -1.1986799999999995], [0.00005000000000165983, -1.5710699999999989], [0, 0], [0, 0], [-2.383700000000001, -2.39725], [-2.3837100000000007, 2.3972500000000005], [0, 0], [0, 0], [-3.37107, 0], [0, 3.390229999999999], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[75.8604, 62.5209], [75.8604, 71.2021], [84.4926, 71.2021], [101.1682, 54.4316], [101.2123, 54.3877], [103.0001, 50.0469], [101.2123, 45.7061], [101.1682, 45.6622], [84.3993, 28.7981], [75.7671, 28.7981], [75.7671, 37.4793], [82.6598, 44.4112], [58.1038, 44.4112], [51.9999, 50.5498], [58.1038, 56.6883], [81.66, 56.6883], [75.8604, 62.5209]],
	            i: [[2.3836900000000014, -2.397260000000003], [-2.3837, -2.3972], [-2.3837, 2.3973], [0, 0], [0, 0], [0.0001, 1.5711], [1.1919, 1.1987], [0, 0], [0, 0], [2.3837, -2.3972], [-2.3837, -2.3972], [0, 0], [0, 0], [0, -3.3902], [-3.3711, 0], [0, 0], [0, 0]],
	            o: [[-2.3836900000000014, 2.397260000000003], [2.3837199999999967, 2.3972299999999933], [0, 0], [0, 0], [1.1919100000000071, -1.198680000000003], [0.00005000000000165983, -1.5710699999999989], [0, 0], [0, 0], [-2.3837000000000046, -2.3972499999999997], [-2.3837099999999936, 2.3972499999999997], [0, 0], [0, 0], [-3.371070000000003, 0], [0, 3.3902300000000025], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [1, 1, 1, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 1
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 9,
	    hd: false,
	    nm: "body - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [69.5, 88]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 0,
	        k: [109.5, 110]
	      },
	      r: {
	        a: 1,
	        k: [{
	          t: 5.6945823033230925,
	          s: [0],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [1],
	            y: [1]
	          }
	        }, {
	          t: 29.809357570137365,
	          s: [-10],
	          o: {
	            x: [0],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 50.37784235653768,
	          s: [0]
	        }]
	      },
	      s: {
	        a: 1,
	        k: [{
	          t: 5.297397769516729,
	          s: [-0.1, -0.1],
	          o: {
	            x: [0],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 29.981583898275588,
	          s: [120, 120],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [1],
	            y: [1]
	          }
	        }, {
	          t: 45.48678969930097,
	          s: [98.3, 98.3],
	          o: {
	            x: [0],
	            y: [0]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 53.23169097805378,
	          s: [100, 100],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 61.11602962079405,
	          s: [110.00000000000001, 110.00000000000001],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 76.64631529194739,
	          s: [100, 100]
	        }]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 10,
	    hd: false,
	    nm: "body",
	    parent: 9,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 240,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[10.8665, 175.8877], [11.4452, 175.8977], [36.6261, 174.0583], [52.7435, 161.0347], [98.8385, 125.9712], [129.1684, 122.522], [138.9999, 112.1049], [138.9999, 59.0294], [129.3847, 48.6865], [96.7109, 45.6381], [44.5985, 7.1959], [39.1445, 2.4422], [10.3445, 0.217], [0, 10.8502], [0, 165.5001], [10.8665, 175.8878], [10.8665, 175.8877]],
	            i: [[0, 0], [0, 0], [-3.7034, 2.2119], [-8.0917, 6.7356], [-5.9864, 1.6766], [-9.9496, 0.6611], [0, 5.5087], [0, 0], [5.4197, 0.4171], [7.8681, 1.0572], [12.6448, 11.1886], [0.8537, 0.6831], [11.0574, -0.3576], [-0.0128, -5.7145], [0, 0], [-5.7736, -0.0997], [0, 0]],
	            o: [[0, 0], [10.528860000000002, 0.1826199999999858], [1.6698200000000014, -0.9973099999999988], [16.462159999999997, -13.703329999999994], [5.679019999999994, -1.5904699999999963], [5.508029999999991, -0.36597000000000435], [0, 0], [0, -5.424399999999999], [-9.851460000000003, -0.7580999999999989], [-10.218859999999992, -1.3730899999999977], [-2.6704599999999985, -2.3629300000000004], [-3.5646599999999964, -2.85253], [-5.72348, 0.18510000000000001], [0, 0], [0.01287, 5.762349999999998], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 1,
	          k: [{
	            t: 18.645635137794528,
	            s: [100],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 25.028958002539536,
	            s: [100],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 33.894684203574144,
	            s: [100],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 57.65483042234692,
	            s: [100],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 77.86868616070589,
	            s: [100],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 83.54275092936804,
	            s: [100]
	          }]
	        },
	        c: {
	          a: 1,
	          k: [{
	            t: 18.645635137794528,
	            s: [0.1450980392156863, 0.6862745098039216, 0.9607843137254902, 1],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 25.028958002539536,
	            s: [0.13333333333333333, 0.7411764705882353, 0.9607843137254902, 1],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 33.894684203574144,
	            s: [0.1450980392156863, 0.6862745098039216, 0.9607843137254902, 1],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 57.65483042234692,
	            s: [0.1450980392156863, 0.6862745098039216, 0.9607843137254902, 1],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 77.86868616070589,
	            s: [0.13333333333333333, 0.7411764705882353, 0.9607843137254902, 1],
	            o: {
	              x: [0.5],
	              y: [0.35]
	            },
	            i: {
	              x: [0.15],
	              y: [1]
	            }
	          }, {
	            t: 83.54275092936804,
	            s: [0.1450980392156863, 0.6862745098039216, 0.9607843137254902, 1]
	          }]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 2
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 11,
	    hd: false,
	    nm: "Star 4 - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [20, 20]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 1,
	        k: [{
	          t: 63.00000000000006,
	          s: [109, 135],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          },
	          ti: [0, 0],
	          to: [0, 0]
	        }, {
	          t: 86.87219933688343,
	          s: [164, 186]
	        }]
	      },
	      r: {
	        a: 1,
	        k: [{
	          t: 104.54275092936804,
	          s: [0],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 151.13943138384246,
	          s: [-45],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 201.84758364312282,
	          s: [0]
	        }]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 12,
	    hd: false,
	    nm: "Star 4",
	    parent: 11,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[20, 0], [16.606, 16.606], [0, 20], [16.606, 23.394], [20, 40], [23.394, 23.394], [40, 20], [23.394, 16.606], [20, 0]],
	            i: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]],
	            o: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [0.18823529411764706, 0.7843137254901961, 0.9725490196078431, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 1
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 13,
	    hd: false,
	    nm: "Star 2 - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [11, 11]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 1,
	        k: [{
	          t: 63.00000000000006,
	          s: [94, 65],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          },
	          ti: [0, 0],
	          to: [0, 0]
	        }, {
	          t: 87.00000000000006,
	          s: [133, 22]
	        }]
	      },
	      r: {
	        a: 1,
	        k: [{
	          t: 104.54275092936804,
	          s: [0],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 151.13943138384246,
	          s: [-45],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 201.84758364312282,
	          s: [0]
	        }]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 14,
	    hd: false,
	    nm: "Star 2",
	    parent: 13,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[11, 0], [9.1333, 9.1333], [0, 11], [9.1333, 12.8667], [11, 22], [12.8667, 12.8667], [22, 11], [12.8667, 9.1333], [11, 0]],
	            i: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]],
	            o: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [0.1568627450980392, 0.6549019607843137, 0.9882352941176471, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 1
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 15,
	    hd: false,
	    nm: "Star 1 - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [10, 10]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 1,
	        k: [{
	          t: 63.00000000000006,
	          s: [58, 183],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          },
	          ti: [0, 0],
	          to: [0, 0]
	        }, {
	          t: 87.00000000000006,
	          s: [19, 147]
	        }]
	      },
	      r: {
	        a: 1,
	        k: [{
	          t: 104.54275092936804,
	          s: [0],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 149.76894078224015,
	          s: [45],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 201.84758364312282,
	          s: [0]
	        }]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 16,
	    hd: false,
	    nm: "Star 1",
	    parent: 15,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[10, 0], [8.303, 8.303], [0, 10], [8.303, 11.697], [10, 20], [11.697, 11.697], [20, 10], [11.697, 8.303], [10, 0]],
	            i: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]],
	            o: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [0.16862745098039217, 0.7098039215686275, 0.9803921568627451, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 1
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 17,
	    hd: false,
	    nm: "Star 3 - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [16, 16]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 1,
	        k: [{
	          t: 63.00000000000006,
	          s: [130, 87],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          },
	          ti: [0, 0],
	          to: [0, 0]
	        }, {
	          t: 87.74540339596109,
	          s: [188, 42]
	        }]
	      },
	      r: {
	        a: 1,
	        k: [{
	          t: 104.54275092936804,
	          s: [0],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 149.76894078224015,
	          s: [45],
	          o: {
	            x: [0.42],
	            y: [0]
	          },
	          i: {
	            x: [0.58],
	            y: [1]
	          }
	        }, {
	          t: 201.84758364312282,
	          s: [0]
	        }]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 18,
	    hd: false,
	    nm: "Star 3",
	    parent: 17,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 60,
	    op: 240,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[16, 0], [13.2848, 13.2848], [0, 16], [13.2848, 18.7152], [16, 32], [18.7152, 18.7152], [32, 16], [18.7152, 13.2848], [16, 0]],
	            i: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]],
	            o: [[0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [0.18823529411764706, 0.7843137254901961, 0.9725490196078431, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 1
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }]
	}];
	var layers = [{
	  ddd: 0,
	  ind: 1,
	  ty: 0,
	  nm: "Atom/Torrent animation 6",
	  refId: "lvc7ui4o20a2e3j8",
	  sr: 1,
	  ks: {
	    a: {
	      a: 0,
	      k: [0, 0]
	    },
	    p: {
	      a: 0,
	      k: [0, 0]
	    },
	    s: {
	      a: 0,
	      k: [100, 100]
	    },
	    sk: {
	      a: 0,
	      k: 0
	    },
	    sa: {
	      a: 0,
	      k: 0
	    },
	    r: {
	      a: 0,
	      k: 0
	    },
	    o: {
	      a: 0,
	      k: 100
	    }
	  },
	  ao: 0,
	  w: 220,
	  h: 220,
	  ip: 0,
	  op: 240,
	  st: 0,
	  hd: false,
	  bm: 0
	}];
	var meta = {
	  a: "",
	  d: "",
	  tc: "",
	  g: "Aninix"
	};
	var flowfLottieIconInfo = {
	  nm: nm,
	  v: v,
	  fr: fr,
	  ip: ip,
	  op: op,
	  w: w,
	  h: h,
	  ddd: ddd,
	  markers: markers,
	  assets: assets,
	  layers: layers,
	  meta: meta
	};

	let _$4 = t => t,
	  _t$4,
	  _t2$4,
	  _t3$2,
	  _t4$1,
	  _t5$1;
	const SLIDER_WIDTH = 692;
	const HELPDESK_ARTICLE = 21272066;
	var _params$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _layout$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _wizard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wizard");
	var _pages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pages");
	var _finishButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("finishButton");
	var _saveChangesButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveChangesButton");
	var _flow$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flow");
	var _flowLottieAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowLottieAnimation");
	var _lottieIconContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lottieIconContainer");
	var _pageChanging = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageChanging");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _renderHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHeader");
	var _renderWizard = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderWizard");
	var _saveChangesAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveChangesAction");
	var _getFinishButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFinishButton");
	var _getSaveChangesButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSaveChangesButton");
	var _onChangeHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeHandler");
	var _saveFlowAction = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveFlowAction");
	var _showErrors = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showErrors");
	var _showDemoInfo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showDemoInfo");
	var _renderDemoInfoContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderDemoInfoContent");
	var _getLottieIconContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLottieIconContainer");
	var _bindStartWorkBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindStartWorkBtn");
	var _hasIncorrectData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hasIncorrectData");
	var _getIncorrectData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIncorrectData");
	var _getFlow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFlow");
	var _onContinueHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onContinueHandler");
	class EditForm extends main_core_events.EventEmitter {
	  constructor(params = {}) {
	    super(params);
	    Object.defineProperty(this, _onContinueHandler, {
	      value: _onContinueHandler2
	    });
	    Object.defineProperty(this, _getFlow, {
	      value: _getFlow2
	    });
	    Object.defineProperty(this, _getIncorrectData, {
	      value: _getIncorrectData2
	    });
	    Object.defineProperty(this, _hasIncorrectData, {
	      value: _hasIncorrectData2
	    });
	    Object.defineProperty(this, _bindStartWorkBtn, {
	      value: _bindStartWorkBtn2
	    });
	    Object.defineProperty(this, _getLottieIconContainer, {
	      value: _getLottieIconContainer2
	    });
	    Object.defineProperty(this, _renderDemoInfoContent, {
	      value: _renderDemoInfoContent2
	    });
	    Object.defineProperty(this, _showDemoInfo, {
	      value: _showDemoInfo2
	    });
	    Object.defineProperty(this, _showErrors, {
	      value: _showErrors2
	    });
	    Object.defineProperty(this, _saveFlowAction, {
	      value: _saveFlowAction2
	    });
	    Object.defineProperty(this, _onChangeHandler, {
	      value: _onChangeHandler2
	    });
	    Object.defineProperty(this, _getSaveChangesButton, {
	      value: _getSaveChangesButton2
	    });
	    Object.defineProperty(this, _getFinishButton, {
	      value: _getFinishButton2
	    });
	    Object.defineProperty(this, _saveChangesAction, {
	      value: _saveChangesAction2
	    });
	    Object.defineProperty(this, _renderWizard, {
	      value: _renderWizard2
	    });
	    Object.defineProperty(this, _renderHeader, {
	      value: _renderHeader2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _params$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _wizard, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pages, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _finishButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _saveChangesButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _flow$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _flowLottieAnimation, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _lottieIconContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pageChanging, {
	      writable: true,
	      value: false
	    });
	    this.setEventNamespace('BX.Tasks.Flow.EditForm');
	    babelHelpers.classPrivateFieldLooseBase(this, _params$5)[_params$5] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$4)[_layout$4] = {};
	    babelHelpers.classPrivateFieldLooseBase(this, _flowLottieAnimation)[_flowLottieAnimation] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer] = null;
	    const onChangeHandler = babelHelpers.classPrivateFieldLooseBase(this, _onChangeHandler)[_onChangeHandler].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages] = [new AboutPage({
	      onChangeHandler
	    }), new SettingsPage({
	      onChangeHandler
	    }), new ControlPage({
	      onChangeHandler
	    })];
	    const initFlowData = {
	      notifyAtHalfTime: true,
	      responsibleCanChangeDeadline: false,
	      taskControl: true,
	      notifyOnQueueOverflow: 50,
	      notifyOnTasksInProgressOverflow: 50,
	      notifyWhenEfficiencyDecreases: 70,
	      taskCreators: [['meta-user', 'all-users']]
	    };
	    if (babelHelpers.classPrivateFieldLooseBase(this, _params$5)[_params$5].flowName) {
	      initFlowData.name = babelHelpers.classPrivateFieldLooseBase(this, _params$5)[_params$5].flowName;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3] = babelHelpers.classPrivateFieldLooseBase(this, _getFlow)[_getFlow](initFlowData);
	  }
	  static async createInstance(params = {}) {
	    const {
	      EditForm
	    } = await top.BX.Runtime.loadExtension('tasks.flow.edit-form');
	    const instance = new EditForm(params);
	    instance.openInSlider();
	    return instance;
	  }
	  openInSlider() {
	    const sidePanelId = `tasks-flow-create-slider-${main_core.Text.getRandom()}`;
	    BX.SidePanel.Instance.open(sidePanelId, {
	      cacheable: true,
	      contentCallback: async slider => {
	        this.slider = slider;
	        const {
	          data: noAccess
	        } = await main_core.ajax.runAction('tasks.flow.View.Access.check', {
	          data: {
	            flowId: babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id > 0 ? babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id : 0,
	            context: 'edit-form',
	            demoFlow: babelHelpers.classPrivateFieldLooseBase(this, _params$5)[_params$5].demoFlow,
	            guideFlow: babelHelpers.classPrivateFieldLooseBase(this, _params$5)[_params$5].guideFlow
	          }
	        });
	        if (noAccess !== null) {
	          return main_core.Tag.render(_t$4 || (_t$4 = _$4`${0}`), noAccess.html);
	        }
	        if (babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id > 0) {
	          const {
	            data: flowData
	          } = await main_core.ajax.runAction('tasks.flow.Flow.get', {
	            data: {
	              flowId: babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id
	            }
	          });
	          babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3] = babelHelpers.classPrivateFieldLooseBase(this, _getFlow)[_getFlow](flowData);
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].forEach(page => page.setFlow(babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3]));
	        return babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]();
	      },
	      width: SLIDER_WIDTH,
	      events: {
	        onLoad: event => {
	          const aboutPage = babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].find(page => page.getId() === 'about-flow');
	          aboutPage.focusToEmptyName();
	          requestAnimationFrame(() => {
	            babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].initHints();
	          });
	        },
	        onClose: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].hideHints();
	          this.emit('afterClose');
	        }
	      }
	    });
	  }
	}
	function _render2() {
	  return main_core.Tag.render(_t2$4 || (_t2$4 = _$4`
			<div class="tasks-flow__create">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderHeader)[_renderHeader](), babelHelpers.classPrivateFieldLooseBase(this, _renderWizard)[_renderWizard]());
	}
	function _renderHeader2() {
	  const title = main_core.Loc.getMessage(babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id ? 'TASKS_FLOW_EDIT_FORM_HEADER_TITLE_EDIT' : 'TASKS_FLOW_EDIT_FORM_HEADER_TITLE');
	  const subTitle = main_core.Loc.getMessage(babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id ? 'TASKS_FLOW_EDIT_FORM_HEADER_SUBTITLE' : 'TASKS_FLOW_EDIT_FORM_HEADER_SUBTITLE_CREATE');
	  return main_core.Tag.render(_t3$2 || (_t3$2 = _$4`
			<div class="ui-slider-section ui-slider-section-icon-center --rounding --icon-sm">
				<span class="tasks-flow__create-header_icon ui-icon ui-slider-icon"></span>
				<div class="ui-slider-content-box">
					<div class="ui-slider-heading-2">${0}</div>
					<div class="ui-slider-inner-box">
						<p class="ui-slider-paragraph-2">
							${0}
						</p>
					</div>
				</div>
			</div>
		`), title, subTitle);
	}
	function _renderWizard2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard] = new tasks_wizard.Wizard({
	    steps: babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].map(page => ({
	      id: page.getId(),
	      title: page.getTitle(),
	      content: page.render(),
	      isFilled: () => !babelHelpers.classPrivateFieldLooseBase(this, _hasIncorrectData)[_hasIncorrectData](page.getRequiredData())
	    })),
	    onCancel: () => this.slider.close(false, () => this.slider.destroy()),
	    onDisabledContinueButtonClick: babelHelpers.classPrivateFieldLooseBase(this, _showErrors)[_showErrors].bind(this),
	    onContinueHandler: babelHelpers.classPrivateFieldLooseBase(this, _onContinueHandler)[_onContinueHandler].bind(this),
	    finishButton: babelHelpers.classPrivateFieldLooseBase(this, _getFinishButton)[_getFinishButton](),
	    saveChangesButton: babelHelpers.classPrivateFieldLooseBase(this, _getSaveChangesButton)[_getSaveChangesButton](),
	    article: HELPDESK_ARTICLE
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].render();
	}
	function _saveChangesAction2() {
	  const isEdit = babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id > 0;
	  if (!isEdit || babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].demo) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _saveFlowAction)[_saveFlowAction]();
	}
	function _getFinishButton2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _finishButton))[_finishButton]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_finishButton] = new ui_buttons.Button({
	    text: main_core.Loc.getMessage(babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id ? 'TASKS_FLOW_EDIT_FORM_SAVE_FLOW' : 'TASKS_FLOW_EDIT_FORM_CREATE_FLOW'),
	    color: ui_buttons.Button.Color.PRIMARY,
	    round: true,
	    size: ui_buttons.Button.Size.LARGE,
	    onclick: () => babelHelpers.classPrivateFieldLooseBase(this, _saveFlowAction)[_saveFlowAction]()
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _finishButton)[_finishButton];
	}
	function _getSaveChangesButton2() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].id || babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3].demo) {
	    return null;
	  }
	  (_babelHelpers$classPr4 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _saveChangesButton))[_saveChangesButton]) != null ? _babelHelpers$classPr4 : _babelHelpers$classPr3[_saveChangesButton] = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_SAVE_CHANGES'),
	    color: ui_buttons.Button.Color.SUCCESS,
	    round: true,
	    size: BX.UI.Button.Size.LARGE,
	    onclick: () => babelHelpers.classPrivateFieldLooseBase(this, _saveChangesAction)[_saveChangesAction]()
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _saveChangesButton)[_saveChangesButton];
	}
	function _onChangeHandler2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3] = babelHelpers.classPrivateFieldLooseBase(this, _getFlow)[_getFlow]();
	  babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].forEach(page => page.update());
	  babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].update();
	}
	function _saveFlowAction2() {
	  var _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _hasIncorrectData)[_hasIncorrectData]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showErrors)[_showErrors]();
	    return;
	  }
	  if ((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _saveChangesButton)[_saveChangesButton]) != null && _babelHelpers$classPr5.isDisabled() || (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _finishButton)[_finishButton]) != null && _babelHelpers$classPr6.isDisabled()) {
	    return;
	  }
	  (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _saveChangesButton)[_saveChangesButton]) == null ? void 0 : _babelHelpers$classPr7.setState(ui_buttons.ButtonState.DISABLED);
	  (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _finishButton)[_finishButton]) == null ? void 0 : _babelHelpers$classPr8.setState(ui_buttons.ButtonState.DISABLED);
	  const flowData = Object.fromEntries(Object.entries(babelHelpers.classPrivateFieldLooseBase(this, _getFlow)[_getFlow]()).map(([key, value]) => [key, main_core.Type.isBoolean(value) ? value ? 1 : 0 : value]));
	  const action = flowData.id ? 'update' : 'create';
	  const textNotification = flowData.id ? 'TASKS_FLOW_EDIT_FORM_FLOW_UPDATE' : 'TASKS_FLOW_EDIT_FORM_FLOW_CREATE';
	  main_core.ajax.runAction(`tasks.flow.Flow.${flowData.demo ? 'activateDemo' : action}`, {
	    data: {
	      flowData,
	      guideFlow: babelHelpers.classPrivateFieldLooseBase(this, _params$5)[_params$5].guideFlow
	    }
	  }).then(response => {
	    if (response.status === 'success') {
	      const flowData = response.data;
	      this.emit('afterSave', flowData);
	      this.slider.close(false, () => {
	        if (flowData.trialFeatureEnabled) {
	          babelHelpers.classPrivateFieldLooseBase(this, _showDemoInfo)[_showDemoInfo]();
	        }
	        this.slider.destroy();
	      });
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage(textNotification),
	        width: 'auto'
	      });
	    }
	  }, error => {
	    var _babelHelpers$classPr9, _babelHelpers$classPr10;
	    (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _saveChangesButton)[_saveChangesButton]) == null ? void 0 : _babelHelpers$classPr9.setState(null);
	    (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _finishButton)[_finishButton]) == null ? void 0 : _babelHelpers$classPr10.setState(null);
	    alert(error.errors.map(e => e.message).join('\n'));
	  });
	}
	function _showErrors2() {
	  const incorrectData = babelHelpers.classPrivateFieldLooseBase(this, _getIncorrectData)[_getIncorrectData]();
	  babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].forEach(page => page.showErrors(incorrectData));
	}
	function _showDemoInfo2() {
	  const popup = new main_popup.Popup({
	    id: 'tasks-flow-task-demo-info',
	    className: 'tasks-flow__task-demo-info',
	    width: 620,
	    overlay: true,
	    padding: 48,
	    closeIcon: true,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderDemoInfoContent)[_renderDemoInfoContent](),
	    events: {
	      onFirstShow: baseEvent => {
	        babelHelpers.classPrivateFieldLooseBase(this, _bindStartWorkBtn)[_bindStartWorkBtn](baseEvent.getTarget());
	      }
	    }
	  });
	  popup.show();
	}
	function _renderDemoInfoContent2() {
	  return main_core.Tag.render(_t4$1 || (_t4$1 = _$4`
			<div class="tasks-flow__task-demo-info_wrapper">
				<div class="tasks-flow__task-demo-info_content">
					<div class="tasks-flow__task-demo-info_title">
						${0}
					</div>
					<div class="tasks-flow__task-demo-info_text">
						${0}
					</div>
					<div class="tasks-flow__task-demo-info_text-trial">
						${0}
					</div>
					<div class="ui-btn ui-btn-sm ui-btn-primary ui-btn-round ui-btn-no-caps">
						${0}
					</div>
				</div>
				${0}
			</div>
		`), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_TITLE_1'), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_TEXT_1'), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_TEXT_TRIAL_1'), main_core.Loc.getMessage('TASKS_FLOW_EDIT_FORM_DEMO_INFO_BTN_1'), babelHelpers.classPrivateFieldLooseBase(this, _getLottieIconContainer)[_getLottieIconContainer]());
	}
	function _getLottieIconContainer2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer] = main_core.Tag.render(_t5$1 || (_t5$1 = _$4`
				<div class="tasks-flow__task-demo-info_image"></div>
			`));
	    babelHelpers.classPrivateFieldLooseBase(this, _flowLottieAnimation)[_flowLottieAnimation] = ui_lottie.Lottie.loadAnimation({
	      container: babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer],
	      renderer: 'svg',
	      loop: false,
	      animationData: flowfLottieIconInfo
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer];
	}
	function _bindStartWorkBtn2(popup) {
	  const popupContainer = popup.getContentContainer();
	  if (main_core.Type.isDomNode(popupContainer)) {
	    const btnNode = popup.getContentContainer().querySelector('.ui-btn');
	    if (main_core.Type.isDomNode(popupContainer)) {
	      main_core.Event.bind(btnNode, 'click', () => popup.close());
	    }
	  }
	}
	function _hasIncorrectData2(fields = []) {
	  const incorrectData = babelHelpers.classPrivateFieldLooseBase(this, _getIncorrectData)[_getIncorrectData]();
	  for (const field of fields) {
	    if (incorrectData.includes(field)) {
	      return true;
	    }
	  }
	  return !main_core.Type.isArrayFilled(fields) && main_core.Type.isArrayFilled(incorrectData);
	}
	function _getIncorrectData2() {
	  const flowData = babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3];
	  const incorrectData = [];
	  if (!main_core.Type.isStringFilled(flowData.name)) {
	    incorrectData.push('name');
	  }
	  if (flowData.plannedCompletionTime <= 0) {
	    incorrectData.push('plannedCompletionTime');
	  }
	  if (!main_core.Type.isArrayFilled(flowData.taskCreators)) {
	    incorrectData.push('taskCreators');
	  }
	  if (!main_core.Type.isArrayFilled(flowData.responsibleList) || flowData.distributionType === 'manually' && flowData.responsibleList[0] <= 0) {
	    incorrectData.push('responsibleList');
	  }
	  if (flowData.id > 0 && flowData.groupId <= 0 && flowData.demo === false) {
	    incorrectData.push('groupId');
	  }
	  if (!main_core.Type.isNumber(flowData.templateId)) {
	    incorrectData.push('templateId');
	  }
	  if (flowData.id > 0 && flowData.ownerId <= 0 && flowData.demo === false) {
	    incorrectData.push('ownerId');
	  }
	  if (main_core.Type.isNumber(flowData.notifyOnQueueOverflow) && flowData.notifyOnQueueOverflow <= 0) {
	    incorrectData.push('notifyOnQueueOverflow');
	  }
	  if (main_core.Type.isNumber(flowData.notifyOnTasksInProgressOverflow) && flowData.notifyOnTasksInProgressOverflow <= 0) {
	    incorrectData.push('notifyOnTasksInProgressOverflow');
	  }
	  if (main_core.Type.isNumber(flowData.notifyWhenEfficiencyDecreases) && (flowData.notifyWhenEfficiencyDecreases <= 0 || flowData.notifyWhenEfficiencyDecreases > 100)) {
	    incorrectData.push('notifyWhenEfficiencyDecreases');
	  }
	  return incorrectData;
	}
	function _getFlow2(flowData = {}) {
	  var _babelHelpers$classPr11;
	  return {
	    id: babelHelpers.classPrivateFieldLooseBase(this, _params$5)[_params$5].flowId,
	    demo: 'demo' in flowData ? flowData.demo === true : ((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3]) == null ? void 0 : _babelHelpers$classPr11.demo) === true,
	    ...babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].reduce((fields, page) => ({
	      ...fields,
	      ...page.getFields(flowData)
	    }), {})
	  };
	}
	function _onContinueHandler2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _pageChanging)[_pageChanging] === true) {
	    return Promise.resolve(false);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _pageChanging)[_pageChanging] = true;
	  const stepId = babelHelpers.classPrivateFieldLooseBase(this, _wizard)[_wizard].getCurrentStep().id;
	  const currentPage = babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].find(page => page.getId() === stepId);
	  return currentPage == null ? void 0 : currentPage.onContinueClick(babelHelpers.classPrivateFieldLooseBase(this, _flow$3)[_flow$3]).then(canContinue => {
	    babelHelpers.classPrivateFieldLooseBase(this, _pageChanging)[_pageChanging] = false;
	    return canContinue;
	  });
	}

	exports.EditForm = EditForm;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX.Main,BX.UI,BX.Tasks,BX.Tasks,BX,BX,BX.UI.EntitySelector,BX.Event,BX,BX.UI.FormElements,BX.UI));
//# sourceMappingURL=edit-form.bundle.js.map
