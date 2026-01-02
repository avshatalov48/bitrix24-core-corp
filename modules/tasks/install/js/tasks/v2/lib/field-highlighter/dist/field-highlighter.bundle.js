/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core) {
	'use strict';

	const fieldAttribute = 'data-task-field-id';
	const chipAttribute = 'data-task-chip-id';
	const fieldSelector = '[data-field-container]';
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _highlightTimeouts = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightTimeouts");
	var _startAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startAnimation");
	var _stopAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stopAnimation");
	var _scrollToField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrollToField");
	var _nextTick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("nextTick");
	class FieldHighlighter {
	  constructor() {
	    Object.defineProperty(this, _nextTick, {
	      value: _nextTick2
	    });
	    Object.defineProperty(this, _scrollToField, {
	      value: _scrollToField2
	    });
	    Object.defineProperty(this, _stopAnimation, {
	      value: _stopAnimation2
	    });
	    Object.defineProperty(this, _startAnimation, {
	      value: _startAnimation2
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: document.body
	    });
	    Object.defineProperty(this, _highlightTimeouts, {
	      writable: true,
	      value: {}
	    });
	  }
	  setContainer(container) {
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = container;
	    return this;
	  }
	  addHighlight(fieldId) {
	    this.highlightContainer(this.getFieldContainer(fieldId));
	    return this;
	  }
	  addChipHighlight(fieldId) {
	    this.highlightContainer(this.getChipContainer(fieldId));
	    return this;
	  }
	  highlightContainer(container) {
	    if (!container) {
	      return;
	    }
	    main_core.Dom.addClass(container, 'tasks-field-highlight');
	    const removeHighlight = () => {
	      main_core.Dom.removeClass(container, 'tasks-field-highlight');
	      main_core.Event.unbind(window, 'click', removeHighlight);
	      main_core.Event.unbind(window, 'keydown', removeHighlight);
	    };
	    main_core.Event.bind(window, 'click', removeHighlight);
	    main_core.Event.bind(window, 'keydown', removeHighlight);
	  }
	  async highlight(fieldId) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _nextTick)[_nextTick]();
	    const fieldContainer = this.getFieldContainer(fieldId);
	    if (!fieldContainer) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _stopAnimation)[_stopAnimation](fieldContainer);
	    setTimeout(() => babelHelpers.classPrivateFieldLooseBase(this, _startAnimation)[_startAnimation](fieldContainer));
	    clearTimeout(babelHelpers.classPrivateFieldLooseBase(this, _highlightTimeouts)[_highlightTimeouts][fieldId]);
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightTimeouts)[_highlightTimeouts][fieldId] = setTimeout(() => babelHelpers.classPrivateFieldLooseBase(this, _stopAnimation)[_stopAnimation](fieldContainer), 1500);
	    babelHelpers.classPrivateFieldLooseBase(this, _scrollToField)[_scrollToField](fieldId);
	  }
	  getFieldContainer(fieldId) {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].querySelector(`[${fieldAttribute}="${fieldId}"]`)) == null ? void 0 : _babelHelpers$classPr.closest(fieldSelector);
	  }
	  getChipContainer(fieldId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].querySelector(`[${chipAttribute}="${fieldId}"]`);
	  }
	}
	function _startAnimation2(fieldContainer) {
	  main_core.Dom.addClass(fieldContainer, ['tasks-field-highlight', '--animate']);
	}
	function _stopAnimation2(fieldContainer) {
	  main_core.Dom.removeClass(fieldContainer, ['tasks-field-highlight', '--animate']);
	}
	function _scrollToField2(fieldId) {
	  const fieldContainer = this.getFieldContainer(fieldId);
	  if (!fieldContainer) {
	    return;
	  }
	  main_core.Dom.style(fieldContainer, 'scrollMarginTop', '100px');
	  fieldContainer.scrollIntoView({
	    block: 'start',
	    behavior: 'smooth'
	  });
	  setTimeout(() => {
	    main_core.Dom.style(fieldContainer, 'scrollMarginTop', null);
	  }, 1000);
	}
	function _nextTick2() {
	  return new Promise(resolve => {
	    setTimeout(resolve, 0);
	  });
	}
	const fieldHighlighter = new FieldHighlighter();

	exports.fieldHighlighter = fieldHighlighter;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX));
//# sourceMappingURL=field-highlighter.bundle.js.map
