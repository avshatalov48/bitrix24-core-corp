/* eslint-disable */
this.BX = this.BX || {};
this.BX.CRM = this.BX.CRM || {};
(function (exports,main_core) {
	'use strict';

	let instance = null;
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _isUniversalActivityScenarioEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUniversalActivityScenarioEnabled");
	var _isLastActivityEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLastActivityEnabled");
	/**
	 * @memberOf BX.CRM.Kanban
	 */
	class Restriction {
	  static get Instance() {
	    if (window.top !== window && main_core.Reflection.getClass('top.BX.CRM.Kanban.Restriction')) {
	      return window.top.BX.CRM.Kanban.Restriction;
	    }
	    if (!instance) {
	      throw new Error('Restriction must be inited before use');
	    }
	    return instance;
	  }
	  static init(options) {
	    if (instance) {
	      console.warn('Attempt to re-init Restriction');
	      return;
	    }
	    instance = new Restriction(options);
	  }
	  constructor(options) {
	    Object.defineProperty(this, _isLastActivityEnabled, {
	      value: _isLastActivityEnabled2
	    });
	    Object.defineProperty(this, _isUniversalActivityScenarioEnabled, {
	      value: _isUniversalActivityScenarioEnabled2
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    if (instance) {
	      throw new Error('Restriction is a singleton, another instance exists already. Use Instance to access it');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = main_core.Type.isPlainObject(options) ? options : {};
	  }
	  isSortTypeChangeAvailable() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isUniversalActivityScenarioEnabled)[_isUniversalActivityScenarioEnabled]() && babelHelpers.classPrivateFieldLooseBase(this, _isLastActivityEnabled)[_isLastActivityEnabled]();
	  }
	  isLastActivityInfoInKanbanItemAvailable() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isUniversalActivityScenarioEnabled)[_isUniversalActivityScenarioEnabled]() && babelHelpers.classPrivateFieldLooseBase(this, _isLastActivityEnabled)[_isLastActivityEnabled]();
	  }
	  isTodoActivityCreateAvailable() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isUniversalActivityScenarioEnabled)[_isUniversalActivityScenarioEnabled]();
	  }
	}
	function _isUniversalActivityScenarioEnabled2() {
	  if (main_core.Type.isBoolean(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].isUniversalActivityScenarioEnabled)) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].isUniversalActivityScenarioEnabled;
	  }
	  return true;
	}
	function _isLastActivityEnabled2() {
	  if (main_core.Type.isBoolean(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].isLastActivityEnabled)) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].isLastActivityEnabled;
	  }
	  return true;
	}

	const namespace = main_core.Reflection.namespace('BX.CRM.Kanban');
	namespace.Restriction = Restriction;

	exports.Restriction = Restriction;

}((this.BX.CRM.Kanban = this.BX.CRM.Kanban || {}),BX));
//# sourceMappingURL=restriction.bundle.js.map
