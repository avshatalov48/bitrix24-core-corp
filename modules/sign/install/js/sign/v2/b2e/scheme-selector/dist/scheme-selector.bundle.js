/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_api) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	const SchemeType = Object.freeze({
	  Order: 'order',
	  Default: 'default',
	  Unset: 'unset'
	});
	var _ui = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ui");
	var _selectedType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedType");
	var _availableSchemes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("availableSchemes");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _renderSelectOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSelectOptions");
	var _getSchemeOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSchemeOptions");
	var _getAvailableSchemes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAvailableSchemes");
	var _isValidScheme = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isValidScheme");
	var _selectItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectItem");
	class SchemeSelector {
	  constructor() {
	    Object.defineProperty(this, _selectItem, {
	      value: _selectItem2
	    });
	    Object.defineProperty(this, _isValidScheme, {
	      value: _isValidScheme2
	    });
	    Object.defineProperty(this, _getAvailableSchemes, {
	      value: _getAvailableSchemes2
	    });
	    Object.defineProperty(this, _getSchemeOptions, {
	      value: _getSchemeOptions2
	    });
	    Object.defineProperty(this, _renderSelectOptions, {
	      value: _renderSelectOptions2
	    });
	    Object.defineProperty(this, _ui, {
	      writable: true,
	      value: {
	        container: HTMLElement = null,
	        select: HTMLSelectElement = null
	      }
	    });
	    Object.defineProperty(this, _selectedType, {
	      writable: true,
	      value: SchemeType.Unset
	    });
	    Object.defineProperty(this, _availableSchemes, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	  }
	  getLayout() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select = main_core.Tag.render(_t || (_t = _`
			<select
				class="ui-ctl-element"
				onchange="${0}"
			>
		`), ({
	      target: select
	    }) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _selectItem)[_selectItem](select.options[select.selectedIndex].value);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown sign-b2e-scheme-selector__dropdown">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select);
	    return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container;
	  }
	  validate() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container, '--invalid');
	    const result = babelHelpers.classPrivateFieldLooseBase(this, _selectedType)[_selectedType] !== SchemeType.Unset;
	    if (!result) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container, '--invalid');
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedType)[_selectedType] !== SchemeType.Unset;
	  }
	  async save(documentId) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyB2eDocumentScheme(documentId, babelHelpers.classPrivateFieldLooseBase(this, _selectedType)[_selectedType]);
	  }
	  async load(documentId) {
	    const {
	      schemes
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadB2eAvaialbleSchemes(documentId);
	    const filteredSchemes = schemes.filter(scheme => babelHelpers.classPrivateFieldLooseBase(this, _isValidScheme)[_isValidScheme](scheme));
	    if (!main_core.Type.isArrayFilled(filteredSchemes)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _selectItem)[_selectItem](SchemeType.Unset);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _availableSchemes)[_availableSchemes] = filteredSchemes;
	    babelHelpers.classPrivateFieldLooseBase(this, _renderSelectOptions)[_renderSelectOptions]();
	    babelHelpers.classPrivateFieldLooseBase(this, _selectItem)[_selectItem](babelHelpers.classPrivateFieldLooseBase(this, _availableSchemes)[_availableSchemes][0]);
	  }
	  getSelectedType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedType)[_selectedType];
	  }
	}
	function _renderSelectOptions2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.innerHTML = '';
	  const optionElements = babelHelpers.classPrivateFieldLooseBase(this, _getAvailableSchemes)[_getAvailableSchemes]().map(option => main_core.Tag.render(_t3 || (_t3 = _`
					<option value="${0}">
						${0}
					</option>
				`), option.value, main_core.Text.encode(option.text)));
	  optionElements.forEach(element => main_core.Dom.append(element, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select));
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.selectedIndex = optionElements.length > 0 ? 1 : 0;
	}
	function _getSchemeOptions2() {
	  return [{
	    value: SchemeType.Unset,
	    text: main_core.Loc.getMessage('SIGN_V2_B2E_SCHEME_SELECTOR_VALUE_TYPE_UNSET')
	  }, {
	    value: SchemeType.Default,
	    text: main_core.Loc.getMessage('SIGN_V2_B2E_SCHEME_SELECTOR_VALUE_TYPE_DEFAULT')
	  }, {
	    value: SchemeType.Order,
	    text: main_core.Loc.getMessage('SIGN_V2_B2E_SCHEME_SELECTOR_VALUE_TYPE_ORDER')
	  }];
	}
	function _getAvailableSchemes2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getSchemeOptions)[_getSchemeOptions]().filter(schemeOption => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _availableSchemes)[_availableSchemes].includes(schemeOption.value) || schemeOption.value === SchemeType.Unset;
	  });
	}
	function _isValidScheme2(scheme) {
	  return Object.values(SchemeType).includes(scheme);
	}
	function _selectItem2(value) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isValidScheme)[_isValidScheme](value)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedType)[_selectedType] = value;
	}

	exports.SchemeType = SchemeType;
	exports.SchemeSelector = SchemeSelector;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2));
//# sourceMappingURL=scheme-selector.bundle.js.map
