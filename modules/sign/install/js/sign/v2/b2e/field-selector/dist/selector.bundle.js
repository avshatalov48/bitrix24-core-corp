/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,ui_sidepanel_layout,ui_userfieldfactory,ui_buttons,main_core_events,main_core) {
	'use strict';

	const DefaultUri = 'sign.api_v1.b2e.fields.load';
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _request = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("request");
	var _getUri = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUri");
	class Backend extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _getUri, {
	      value: _getUri2
	    });
	    Object.defineProperty(this, _request, {
	      value: _request2
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    this.setEventNamespace('BX.Sign.B2E.FieldsSelector.Backend');
	    this.subscribeFromOptions(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].events);
	  }
	  setCustomSettings(customSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _options)[_options],
	      customSettings
	    };
	  }
	  getData(requestOptions = {}) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    return babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]({
	      data: {
	        ...requestOptions,
	        ...((_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].customSettings) == null ? void 0 : _babelHelpers$classPr2.requestOptions) != null ? _babelHelpers$classPr : {})
	      }
	    });
	  }
	}
	function _request2(requestOptions) {
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction(babelHelpers.classPrivateFieldLooseBase(this, _getUri)[_getUri](), {
	      json: requestOptions.data
	    }).then(resolve).catch(reject);
	  });
	}
	function _getUri2() {
	  var _babelHelpers$classPr3;
	  if (main_core.Type.isStringFilled((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].customSettings) == null ? void 0 : _babelHelpers$classPr3.uri)) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].customSettings.uri;
	  }
	  return DefaultUri;
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _setOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");
	var _getOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");
	var _onInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onInput");
	var _getDebounceWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDebounceWrapper");
	var _getInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInput");
	var _onClearClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClearClick");
	var _getClearButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getClearButton");
	class Search extends main_core_events.EventEmitter {
	  constructor(_options = {}) {
	    super();
	    Object.defineProperty(this, _getClearButton, {
	      value: _getClearButton2
	    });
	    Object.defineProperty(this, _onClearClick, {
	      value: _onClearClick2
	    });
	    Object.defineProperty(this, _getInput, {
	      value: _getInput2
	    });
	    Object.defineProperty(this, _getDebounceWrapper, {
	      value: _getDebounceWrapper2
	    });
	    Object.defineProperty(this, _onInput, {
	      value: _onInput2
	    });
	    Object.defineProperty(this, _getOptions, {
	      value: _getOptions2
	    });
	    Object.defineProperty(this, _setOptions, {
	      value: _setOptions2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.B2E.Fields.Selector.Search');
	    this.subscribeFromOptions(_options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions)[_setOptions](_options);
	  }
	  getValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getInput)[_getInput]().value;
	  }
	  setValue(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getInput)[_getInput]().value = value;
	    babelHelpers.classPrivateFieldLooseBase(this, _onInput)[_onInput]();
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('layout', () => {
	      return main_core.Tag.render(_t || (_t = _`
				<div class="sign-b2e-fields-selector-search">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100 ui-ctl-before-icon ui-ctl-after-icon">
						<div class="ui-ctl-before ui-ctl-icon-search"></div>
						${0}
						${0}
					</div>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getClearButton)[_getClearButton](), babelHelpers.classPrivateFieldLooseBase(this, _getInput)[_getInput]());
	    });
	  }
	}
	function _setOptions2(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('options', {
	    ...options
	  });
	}
	function _getOptions2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('options', {});
	}
	function _onInput2() {
	  this.emit('onChange', {
	    value: babelHelpers.classPrivateFieldLooseBase(this, _getInput)[_getInput]().value
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _getDebounceWrapper)[_getDebounceWrapper]()();
	}
	function _getDebounceWrapper2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('debounceWrapper', () => {
	    return main_core.Runtime.debounce(() => {
	      this.emit('onDebouncedChange', {
	        value: babelHelpers.classPrivateFieldLooseBase(this, _getInput)[_getInput]().value
	      });
	    }, 50);
	  });
	}
	function _getInput2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('input', () => {
	    const initialValue = (() => {
	      if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().initialValue)) {
	        return babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().initialValue;
	      }
	      return '';
	    })();
	    return main_core.Tag.render(_t2 || (_t2 = _`
				<input 
					type="text" 
					class="ui-ctl-element" 
					oninput="${0}"
					value="${0}"
					placeholder="${0}"
				>
			`), babelHelpers.classPrivateFieldLooseBase(this, _onInput)[_onInput].bind(this), main_core.Text.encode(initialValue), main_core.Loc.getMessage('SIGN_B2E_FIELDS_SELECTOR_SEARCH_PLACEHOLDER'));
	  });
	}
	function _onClearClick2(event) {
	  event.preventDefault();
	  babelHelpers.classPrivateFieldLooseBase(this, _getInput)[_getInput]().value = '';
	  babelHelpers.classPrivateFieldLooseBase(this, _onInput)[_onInput]();
	}
	function _getClearButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('clearButton', () => {
	    return main_core.Tag.render(_t3 || (_t3 = _`
				<button 
					class="ui-ctl-after ui-ctl-icon-clear" 
					onclick="${0}"
				></button>
			`), babelHelpers.classPrivateFieldLooseBase(this, _onClearClick)[_onClearClick].bind(this));
	  });
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1;
	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _setOptions$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");
	var _getOptions$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");
	var _onChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChange");
	var _getCheckbox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCheckbox");
	var _isDisabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDisabled");
	class ListItem extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _isDisabled, {
	      value: _isDisabled2
	    });
	    Object.defineProperty(this, _getCheckbox, {
	      value: _getCheckbox2
	    });
	    Object.defineProperty(this, _onChange, {
	      value: _onChange2
	    });
	    Object.defineProperty(this, _getOptions$1, {
	      value: _getOptions2$1
	    });
	    Object.defineProperty(this, _setOptions$1, {
	      value: _setOptions2$1
	    });
	    Object.defineProperty(this, _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.B2E.FieldSelector.ListItem');
	    this.subscribeFromOptions(_options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions$1)[_setOptions$1](_options);
	    const {
	      targetContainer
	    } = _options;
	    if (main_core.Type.isDomNode(targetContainer)) {
	      this.renderTo(targetContainer);
	    }
	  }
	  getField() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getOptions$1)[_getOptions$1]().field;
	  }
	  isSelected() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getCheckbox)[_getCheckbox]().checked;
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember(`layout`, () => {
	      const fieldDisabledClassName = 'sign-b2e-fields-selector-field--disabled';
	      return main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<div class="sign-b2e-fields-selector-field${0}">
					<label class="ui-ctl ui-ctl-checkbox sign-b2e-fields-selector-field-checkbox">
						${0}
						<div class="ui-ctl-label-text">${0}</div>
					</label>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _isDisabled)[_isDisabled]() ? " " + fieldDisabledClassName : '', babelHelpers.classPrivateFieldLooseBase(this, _getCheckbox)[_getCheckbox](), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _getOptions$1)[_getOptions$1]().field.caption));
	    });
	  }
	  renderTo(targetContainer) {
	    if (main_core.Type.isDomNode(targetContainer)) {
	      main_core.Dom.append(this.getLayout(), targetContainer);
	    }
	  }
	}
	function _setOptions2$1(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].set('options', {
	    type: ListItem.Type.CHECKBOX,
	    ...options
	  });
	}
	function _getOptions2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].get('options', {});
	}
	function _onChange2() {
	  this.emit('onChange');
	}
	function _getCheckbox2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('checkbox', () => {
	    return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
				<input 
					type="${0}" 
					class="ui-ctl-element"
					onchange="${0}"
					name="SIGN_B2E_SELECTOR_ITEM"
					${0}
				>
			`), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _getOptions$1)[_getOptions$1]().type), babelHelpers.classPrivateFieldLooseBase(this, _onChange)[_onChange].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _getOptions$1)[_getOptions$1]().selected ? 'checked' : '');
	  });
	}
	function _isDisabled2() {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$1)[_getOptions$1]().disabled) != null ? _babelHelpers$classPr : false;
	}
	ListItem.Type = {
	  CHECKBOX: 'checkbox',
	  RADIO: 'radio'
	};

	let _$2 = t => t,
	  _t$2,
	  _t2$2,
	  _t3$1;
	const DEFAULT_FIELD_MODULE_ID = 'crm';
	var _cache$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _defaultFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultFilter");
	var _defaultFieldsFactoryFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultFieldsFactoryFilter");
	var _permissionAddByCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("permissionAddByCategory");
	var _setOptions$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");
	var _getOptions$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");
	var _getBackend = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBackend");
	var _setFieldsList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setFieldsList");
	var _applyCategoriesFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyCategoriesFilter");
	var _applyFieldsFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyFieldsFilter");
	var _applySearchFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applySearchFilter");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	var _setIsLeadEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setIsLeadEnabled");
	var _isLeadEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLeadEnabled");
	var _setIsAllowedCreateField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setIsAllowedCreateField");
	var _isAllowedCreateField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAllowedCreateField");
	var _getSidebarItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSidebarItems");
	var _getFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFilter");
	var _cleanFieldsList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cleanFieldsList");
	var _getSelectedFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectedFields");
	var _addSelectedField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addSelectedField");
	var _removeSelectedField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeSelectedField");
	var _setSelectedFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSelectedFields");
	var _isMultiple = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMultiple");
	var _renderCategoryFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCategoryFields");
	var _getDisabledFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDisabledFields");
	var _isFieldDisabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFieldDisabled");
	var _onListItemChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onListItemChange");
	var _onSidebarItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSidebarItemClick");
	var _onBackendError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBackendError");
	var _getLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLayout");
	var _onSearchChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSearchChange");
	var _getSearch = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSearch");
	var _getFieldsListLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsListLayout");
	var _getCreateFieldButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateFieldButton");
	var _onCreateFieldClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCreateFieldClick");
	var _getPreparedCategoryId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPreparedCategoryId");
	var _getFieldsFactoryTypesFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsFactoryTypesFilter");
	var _applyFieldsFactoryTypesFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyFieldsFactoryTypesFilter");
	var _getUserFieldFactory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUserFieldFactory");
	var _getTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTitle");
	var _getSliderLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSliderLayout");
	var _getRenderedSliderLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRenderedSliderLayout");
	var _onSaveClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSaveClick");
	var _setPromiseResolver = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setPromiseResolver");
	var _getPromiseResolver = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPromiseResolver");
	var _selectFirstCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectFirstCategory");
	var _getSliderId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSliderId");
	var _onSliderCloseComplete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSliderCloseComplete");
	var _setAddButtonEnabledByCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setAddButtonEnabledByCategory");
	class FieldSelector extends main_core_events.EventEmitter {
	  constructor(_options = {}) {
	    super();
	    Object.defineProperty(this, _setAddButtonEnabledByCategory, {
	      value: _setAddButtonEnabledByCategory2
	    });
	    Object.defineProperty(this, _onSliderCloseComplete, {
	      value: _onSliderCloseComplete2
	    });
	    Object.defineProperty(this, _getSliderId, {
	      value: _getSliderId2
	    });
	    Object.defineProperty(this, _selectFirstCategory, {
	      value: _selectFirstCategory2
	    });
	    Object.defineProperty(this, _getPromiseResolver, {
	      value: _getPromiseResolver2
	    });
	    Object.defineProperty(this, _setPromiseResolver, {
	      value: _setPromiseResolver2
	    });
	    Object.defineProperty(this, _onSaveClick, {
	      value: _onSaveClick2
	    });
	    Object.defineProperty(this, _getRenderedSliderLayout, {
	      value: _getRenderedSliderLayout2
	    });
	    Object.defineProperty(this, _getSliderLayout, {
	      value: _getSliderLayout2
	    });
	    Object.defineProperty(this, _getTitle, {
	      value: _getTitle2
	    });
	    Object.defineProperty(this, _getUserFieldFactory, {
	      value: _getUserFieldFactory2
	    });
	    Object.defineProperty(this, _applyFieldsFactoryTypesFilter, {
	      value: _applyFieldsFactoryTypesFilter2
	    });
	    Object.defineProperty(this, _getFieldsFactoryTypesFilter, {
	      value: _getFieldsFactoryTypesFilter2
	    });
	    Object.defineProperty(this, _getPreparedCategoryId, {
	      value: _getPreparedCategoryId2
	    });
	    Object.defineProperty(this, _onCreateFieldClick, {
	      value: _onCreateFieldClick2
	    });
	    Object.defineProperty(this, _getCreateFieldButton, {
	      value: _getCreateFieldButton2
	    });
	    Object.defineProperty(this, _getFieldsListLayout, {
	      value: _getFieldsListLayout2
	    });
	    Object.defineProperty(this, _getSearch, {
	      value: _getSearch2
	    });
	    Object.defineProperty(this, _onSearchChange, {
	      value: _onSearchChange2
	    });
	    Object.defineProperty(this, _getLayout, {
	      value: _getLayout2
	    });
	    Object.defineProperty(this, _onBackendError, {
	      value: _onBackendError2
	    });
	    Object.defineProperty(this, _onSidebarItemClick, {
	      value: _onSidebarItemClick2
	    });
	    Object.defineProperty(this, _onListItemChange, {
	      value: _onListItemChange2
	    });
	    Object.defineProperty(this, _isFieldDisabled, {
	      value: _isFieldDisabled2
	    });
	    Object.defineProperty(this, _getDisabledFields, {
	      value: _getDisabledFields2
	    });
	    Object.defineProperty(this, _renderCategoryFields, {
	      value: _renderCategoryFields2
	    });
	    Object.defineProperty(this, _isMultiple, {
	      value: _isMultiple2
	    });
	    Object.defineProperty(this, _setSelectedFields, {
	      value: _setSelectedFields2
	    });
	    Object.defineProperty(this, _removeSelectedField, {
	      value: _removeSelectedField2
	    });
	    Object.defineProperty(this, _addSelectedField, {
	      value: _addSelectedField2
	    });
	    Object.defineProperty(this, _getSelectedFields, {
	      value: _getSelectedFields2
	    });
	    Object.defineProperty(this, _cleanFieldsList, {
	      value: _cleanFieldsList2
	    });
	    Object.defineProperty(this, _getFilter, {
	      value: _getFilter2
	    });
	    Object.defineProperty(this, _getSidebarItems, {
	      value: _getSidebarItems2
	    });
	    Object.defineProperty(this, _isAllowedCreateField, {
	      value: _isAllowedCreateField2
	    });
	    Object.defineProperty(this, _setIsAllowedCreateField, {
	      value: _setIsAllowedCreateField2
	    });
	    Object.defineProperty(this, _isLeadEnabled, {
	      value: _isLeadEnabled2
	    });
	    Object.defineProperty(this, _setIsLeadEnabled, {
	      value: _setIsLeadEnabled2
	    });
	    Object.defineProperty(this, _load, {
	      value: _load2
	    });
	    Object.defineProperty(this, _applySearchFilter, {
	      value: _applySearchFilter2
	    });
	    Object.defineProperty(this, _applyFieldsFilter, {
	      value: _applyFieldsFilter2
	    });
	    Object.defineProperty(this, _applyCategoriesFilter, {
	      value: _applyCategoriesFilter2
	    });
	    Object.defineProperty(this, _setFieldsList, {
	      value: _setFieldsList2
	    });
	    Object.defineProperty(this, _getBackend, {
	      value: _getBackend2
	    });
	    Object.defineProperty(this, _getOptions$2, {
	      value: _getOptions2$2
	    });
	    Object.defineProperty(this, _setOptions$2, {
	      value: _setOptions2$2
	    });
	    Object.defineProperty(this, _cache$2, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _permissionAddByCategory, {
	      writable: true,
	      value: {}
	    });
	    this.setEventNamespace('BX.Sign.B2e.FieldsSelector');
	    this.subscribeFromOptions(_options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions$2)[_setOptions$2](_options);
	  }
	  setCustomBackendSettings(customBackendSettings) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions$2)[_setOptions$2]({
	      ...babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2](),
	      customBackendSettings
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _getBackend)[_getBackend]().setCustomSettings(babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]().customBackendSettings);
	  }
	  getFieldsList(filtering = true) {
	    const fieldsList = babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('fieldsList', {});
	    const filter = babelHelpers.classPrivateFieldLooseBase(this, _getFilter)[_getFilter]();
	    if (main_core.Type.isPlainObject(filter)) {
	      const query = filtering ? babelHelpers.classPrivateFieldLooseBase(this, _getSearch)[_getSearch]().getValue() : '';
	      return babelHelpers.classPrivateFieldLooseBase(this, _applySearchFilter)[_applySearchFilter](babelHelpers.classPrivateFieldLooseBase(this, _applyFieldsFilter)[_applyFieldsFilter](babelHelpers.classPrivateFieldLooseBase(this, _applyCategoriesFilter)[_applyCategoriesFilter](fieldsList, filter), filter), query);
	    }
	    if (main_core.Type.isFunction(filter)) {
	      const defaultFilter = main_core.Runtime.clone(babelHelpers.classPrivateFieldLooseBase(FieldSelector, _defaultFilter)[_defaultFilter]);
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _isLeadEnabled)[_isLeadEnabled]()) {
	        defaultFilter['-categories'].push('LEAD');
	      }
	      const prefilteredFieldsList = babelHelpers.classPrivateFieldLooseBase(this, _applyFieldsFilter)[_applyFieldsFilter](babelHelpers.classPrivateFieldLooseBase(this, _applyCategoriesFilter)[_applyCategoriesFilter](fieldsList, defaultFilter), filter);
	      return filter(main_core.Runtime.clone(prefilteredFieldsList));
	    }
	    return fieldsList;
	  }
	  static loadFieldList(options, customBackendSettings = null) {
	    return new Backend({
	      customSettings: customBackendSettings,
	      events: {
	        onError: () => {}
	      }
	    }).getData({
	      options
	    }).then(({
	      data
	    }) => {
	      return data == null ? void 0 : data.fields;
	    });
	  }
	  hide() {
	    const SidePanel = main_core.Reflection.getClass('BX.SidePanel');
	    if (SidePanel.Instance) {
	      SidePanel.Instance.close();
	    }
	  }
	  show() {
	    const SidePanel = main_core.Reflection.getClass('BX.SidePanel');
	    if (SidePanel.Instance) {
	      const createFieldButton = babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldButton)[_getCreateFieldButton]();
	      createFieldButton.setDisabled(true);
	      SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _getSliderId)[_getSliderId](), {
	        width: 740,
	        contentCallback: () => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]().then(() => {
	            createFieldButton.setDisabled(!babelHelpers.classPrivateFieldLooseBase(this, _isAllowedCreateField)[_isAllowedCreateField]());
	            babelHelpers.classPrivateFieldLooseBase(this, _selectFirstCategory)[_selectFirstCategory]();
	            return babelHelpers.classPrivateFieldLooseBase(this, _getRenderedSliderLayout)[_getRenderedSliderLayout]();
	          }).catch(({
	            errors
	          }) => {
	            return main_core.Tag.render(_t$2 || (_t$2 = _$2`
									<div class="ui-alert ui-alert-danger">
										<span class="ui-alert-message">${0}</span>
									</div>
								`), errors.map(item => main_core.Text.encode(item.message)).join('\n'));
	          });
	        },
	        events: {
	          onCloseComplete: () => babelHelpers.classPrivateFieldLooseBase(this, _onSliderCloseComplete)[_onSliderCloseComplete]()
	        }
	      });
	    }
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _setPromiseResolver)[_setPromiseResolver](resolve);
	    });
	  }
	}
	function _setOptions2$2(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('options', {
	    filter: {},
	    multiple: true,
	    ...options
	  });
	}
	function _getOptions2$2() {
	  return main_core.Runtime.clone(babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('options', {
	    filter: {}
	  }));
	}
	function _getBackend2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('backend', () => {
	    return new Backend({
	      customSettings: babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]().customBackendSettings,
	      events: {
	        onError: babelHelpers.classPrivateFieldLooseBase(this, _onBackendError)[_onBackendError].bind(this)
	      }
	    });
	  });
	}
	function _setFieldsList2(fieldsList) {
	  if (main_core.Type.isObject(fieldsList.SMART_B2E_DOC)) {
	    fieldsList.SMART_B2E_DOC.CAPTION = main_core.Loc.getMessage('SIGN_B2E_FIELDS_SELECTOR_SMART_B2E_DOC_CATEGORY_CAPTION');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('fieldsList', {
	    ...fieldsList
	  });
	}
	function _applyCategoriesFilter2(fieldsList, filter) {
	  const fieldsEntries = Object.entries(fieldsList);
	  return fieldsEntries.reduce((acc, [categoryId, category]) => {
	    if ((!main_core.Type.isArrayFilled(filter['+categories']) || filter['+categories'].includes(categoryId)) && (!main_core.Type.isArrayFilled(filter['-categories']) || !filter['-categories'].includes(categoryId))) {
	      acc[categoryId] = category;
	    }
	    return acc;
	  }, {});
	}
	function _applyFieldsFilter2(fieldsList, filter) {
	  const fieldsEntries = Object.entries(fieldsList);
	  return fieldsEntries.reduce((acc, [categoryId, category]) => {
	    const filteredFields = category.FIELDS.filter(field => {
	      const allowed = !main_core.Type.isArrayFilled(filter['+fields']) || filter['+fields'].some(condition => {
	        if (main_core.Type.isStringFilled(condition)) {
	          return field.type === condition;
	        }
	        if (main_core.Type.isFunction(condition)) {
	          return condition(field);
	        }
	        if (main_core.Type.isPlainObject(condition)) {
	          return Object.entries(condition).every(([key, value]) => {
	            return field[key] === value;
	          });
	        }
	        return false;
	      });
	      const disallowed = main_core.Type.isArrayFilled(filter['-fields']) && filter['-fields'].some(condition => {
	        if (main_core.Type.isStringFilled(condition)) {
	          return field.type === condition;
	        }
	        if (main_core.Type.isFunction(condition)) {
	          return condition(field);
	        }
	        if (main_core.Type.isPlainObject(condition)) {
	          return Object.entries(condition).every(([key, value]) => {
	            return field[key] === value;
	          });
	        }
	        return false;
	      });
	      return allowed && !disallowed;
	    });
	    if (filter.allowEmptyFieldList ? main_core.Type.isArray(filteredFields) : main_core.Type.isArrayFilled(filteredFields)) {
	      acc[categoryId] = {
	        ...category,
	        FIELDS: filteredFields
	      };
	    }
	    return acc;
	  }, {});
	}
	function _applySearchFilter2(fieldsList, query) {
	  const fieldsEntries = Object.entries(fieldsList);
	  if (main_core.Type.isStringFilled(query)) {
	    const preparedQuery = String(query).toLowerCase();
	    return fieldsEntries.reduce((acc, [categoryId, category]) => {
	      const filteredFields = category.FIELDS.filter(field => {
	        return main_core.Type.isStringFilled(field.caption) && String(field.caption).toLowerCase().includes(preparedQuery);
	      });
	      if (main_core.Type.isArrayFilled(filteredFields)) {
	        acc[categoryId] = {
	          ...category,
	          FIELDS: filteredFields
	        };
	      }
	      return acc;
	    }, {});
	  }
	  return fieldsList;
	}
	function _load2() {
	  const {
	    controllerOptions = {}
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]();
	  return babelHelpers.classPrivateFieldLooseBase(this, _getBackend)[_getBackend]().getData({
	    options: controllerOptions
	  }).then(({
	    data
	  }) => {
	    var _data$options$isLeadE, _data$options, _data$options$permiss, _data$options2, _data$options2$permis, _data$options2$permis2, _data$options$permiss2, _data$options3, _data$options3$permis, _data$options3$permis2;
	    babelHelpers.classPrivateFieldLooseBase(this, _setFieldsList)[_setFieldsList](data.fields);
	    babelHelpers.classPrivateFieldLooseBase(this, _setIsLeadEnabled)[_setIsLeadEnabled]((_data$options$isLeadE = (_data$options = data.options) == null ? void 0 : _data$options.isLeadEnabled) != null ? _data$options$isLeadE : false);
	    babelHelpers.classPrivateFieldLooseBase(this, _setIsAllowedCreateField)[_setIsAllowedCreateField]((_data$options$permiss = (_data$options2 = data.options) == null ? void 0 : (_data$options2$permis = _data$options2.permissions) == null ? void 0 : (_data$options2$permis2 = _data$options2$permis.userField) == null ? void 0 : _data$options2$permis2.add) != null ? _data$options$permiss : false);
	    babelHelpers.classPrivateFieldLooseBase(this, _permissionAddByCategory)[_permissionAddByCategory] = (_data$options$permiss2 = (_data$options3 = data.options) == null ? void 0 : (_data$options3$permis = _data$options3.permissions) == null ? void 0 : (_data$options3$permis2 = _data$options3$permis.userField) == null ? void 0 : _data$options3$permis2.addByCategory) != null ? _data$options$permiss2 : {};
	  });
	}
	function _setIsLeadEnabled2(value) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('isLeadEnabled', value);
	}
	function _isLeadEnabled2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('isLeadEnabled');
	}
	function _setIsAllowedCreateField2(value) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('isAllowedCreateField', value);
	}
	function _isAllowedCreateField2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('isAllowedCreateField', false);
	}
	function _getSidebarItems2() {
	  return Object.entries(this.getFieldsList()).map(([categoryId, category]) => {
	    var _babelHelpers$classPr;
	    let label = category.CAPTION;
	    Object.entries((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]()) == null ? void 0 : _babelHelpers$classPr.categoryCaptions).forEach(([category, caption]) => {
	      if (category === categoryId) {
	        label = caption;
	      }
	    });
	    return {
	      label,
	      id: categoryId,
	      moduleId: category == null ? void 0 : category.MODULE_ID,
	      onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSidebarItemClick)[_onSidebarItemClick].bind(this, categoryId)
	    };
	  });
	}
	function _getFilter2() {
	  const customFilter = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]().filter;
	  if (main_core.Type.isPlainObject(customFilter)) {
	    const defaultFilter = babelHelpers.classPrivateFieldLooseBase(FieldSelector, _defaultFilter)[_defaultFilter];
	    if (main_core.Type.isArray(customFilter['-categories'])) {
	      customFilter['-categories'] = [...customFilter['-categories'], ...defaultFilter['-categories']];
	    } else {
	      customFilter['-categories'] = [...defaultFilter['-categories']];
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isLeadEnabled)[_isLeadEnabled]()) {
	      customFilter['-categories'].push('LEAD');
	    }
	    if (main_core.Type.isArray(customFilter['-fields'])) {
	      customFilter['-fields'] = [...customFilter['-fields'], ...defaultFilter['-fields']];
	    } else {
	      customFilter['-fields'] = [...defaultFilter['-fields']];
	    }
	  }
	  return customFilter;
	}
	function _cleanFieldsList2() {
	  main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _getFieldsListLayout)[_getFieldsListLayout]());
	}
	function _getSelectedFields2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('selectedFields', []);
	}
	function _addSelectedField2(field) {
	  const selectedFields = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedFields)[_getSelectedFields]();
	  const hasField = selectedFields.some(currentField => {
	    return currentField.name === field.name;
	  });
	  if (!hasField) {
	    selectedFields.push(field);
	    babelHelpers.classPrivateFieldLooseBase(this, _setSelectedFields)[_setSelectedFields](selectedFields);
	  }
	}
	function _removeSelectedField2(field) {
	  const selectedFields = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedFields)[_getSelectedFields]().filter(currentField => {
	    return currentField.name !== field.name;
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _setSelectedFields)[_setSelectedFields](selectedFields);
	}
	function _setSelectedFields2(fields) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('selectedFields', fields);
	}
	function _isMultiple2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]().multiple;
	}
	function _renderCategoryFields2(categoryId) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cleanFieldsList)[_cleanFieldsList]();
	  const fields = this.getFieldsList()[categoryId].FIELDS;
	  if (main_core.Type.isArrayFilled(fields)) {
	    fields.forEach(field => {
	      void new ListItem({
	        field,
	        targetContainer: babelHelpers.classPrivateFieldLooseBase(this, _getFieldsListLayout)[_getFieldsListLayout](),
	        events: {
	          onChange: babelHelpers.classPrivateFieldLooseBase(this, _onListItemChange)[_onListItemChange].bind(this)
	        },
	        selected: babelHelpers.classPrivateFieldLooseBase(this, _getSelectedFields)[_getSelectedFields]().some(selectedField => {
	          return selectedField.name === field.name;
	        }),
	        type: babelHelpers.classPrivateFieldLooseBase(this, _isMultiple)[_isMultiple]() ? ListItem.Type.CHECKBOX : ListItem.Type.RADIO,
	        disabled: babelHelpers.classPrivateFieldLooseBase(this, _isFieldDisabled)[_isFieldDisabled](field)
	      });
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setAddButtonEnabledByCategory)[_setAddButtonEnabledByCategory](categoryId);
	}
	function _getDisabledFields2() {
	  var _babelHelpers$classPr2;
	  return (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]().disabledFields) != null ? _babelHelpers$classPr2 : null;
	}
	function _isFieldDisabled2(field) {
	  const disabledFields = babelHelpers.classPrivateFieldLooseBase(this, _getDisabledFields)[_getDisabledFields]();
	  if (main_core.Type.isNull(disabledFields)) {
	    return false;
	  }
	  return disabledFields.some(fieldRule => main_core.Type.isString(fieldRule) && field.name === fieldRule || main_core.Type.isFunction(fieldRule) && fieldRule(field));
	}
	function _onListItemChange2(event) {
	  const listItem = event.getTarget();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isMultiple)[_isMultiple]()) {
	    if (listItem.isSelected()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _addSelectedField)[_addSelectedField](listItem.getField());
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _removeSelectedField)[_removeSelectedField](listItem.getField());
	    }
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _setSelectedFields)[_setSelectedFields]([listItem.getField()]);
	  }
	}
	function _onSidebarItemClick2(categoryId) {
	  babelHelpers.classPrivateFieldLooseBase(this, _renderCategoryFields)[_renderCategoryFields](categoryId);
	}
	function _onBackendError2(error) {
	  console.error(error);
	  this.emit('onError', {
	    error
	  });
	}
	function _getLayout2() {
	  return main_core.Tag.render(_t2$2 || (_t2$2 = _$2`
			<div class="sign-b2e-fields-selector">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getFieldsListLayout)[_getFieldsListLayout]());
	}
	async function _onSearchChange2() {
	  const sliderLayout = await babelHelpers.classPrivateFieldLooseBase(this, _getSliderLayout)[_getSliderLayout]();
	  const sidebarItems = babelHelpers.classPrivateFieldLooseBase(this, _getSidebarItems)[_getSidebarItems]();
	  sliderLayout.getMenu().setItems(sidebarItems);
	  babelHelpers.classPrivateFieldLooseBase(this, _cleanFieldsList)[_cleanFieldsList]();
	  const [firstSidebarItem] = sidebarItems;
	  if (firstSidebarItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onSidebarItemClick)[_onSidebarItemClick](firstSidebarItem.id);
	    sliderLayout.getMenu().setActiveFirstItem();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isAllowedCreateField)[_isAllowedCreateField]()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldButton)[_getCreateFieldButton]().setDisabled(false);
	    }
	  } else {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isAllowedCreateField)[_isAllowedCreateField]()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldButton)[_getCreateFieldButton]().setDisabled(true);
	    }
	  }
	}
	function _getSearch2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('search', () => {
	    return new Search({
	      events: {
	        onChange: babelHelpers.classPrivateFieldLooseBase(this, _onSearchChange)[_onSearchChange].bind(this)
	      }
	    });
	  });
	}
	function _getFieldsListLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('fieldsListLayout', () => {
	    return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
				<div class="sign-b2e-fields-selector-fields-list"></div>
			`));
	  });
	}
	function _getCreateFieldButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('createFieldButton', () => {
	    return new ui_buttons.Button({
	      text: main_core.Loc.getMessage('SIGN_B2E_FIELDS_SELECTOR_CREATE_BUTTON_LABEL'),
	      color: ui_buttons.Button.Color.SUCCESS,
	      onclick: babelHelpers.classPrivateFieldLooseBase(this, _onCreateFieldClick)[_onCreateFieldClick].bind(this)
	    });
	  });
	}
	async function _onCreateFieldClick2() {
	  const sliderLayout = await babelHelpers.classPrivateFieldLooseBase(this, _getSliderLayout)[_getSliderLayout]();
	  const sliderMenu = sliderLayout.getMenu();
	  if (sliderMenu.hasActive()) {
	    const currentCategoryId = sliderMenu.getActiveItem().getId();
	    const moduleId = sliderMenu.getActiveItem().getModuleId() || DEFAULT_FIELD_MODULE_ID;
	    const factory = babelHelpers.classPrivateFieldLooseBase(this, _getUserFieldFactory)[_getUserFieldFactory](currentCategoryId, moduleId);
	    const menu = factory.getMenu();
	    menu.open(selectedType => {
	      const configurator = factory.getConfigurator({
	        userField: factory.createUserField(selectedType),
	        canMultipleFields: false,
	        canRequiredFields: false,
	        onSave: userField => {
	          main_core.Dom.addClass(configurator.saveButton, 'ui-btn-wait');
	          const languages = new Set(Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]().languages));
	          const currentLanguage = main_core.Loc.getMessage('LANGUAGE_ID');
	          const currentValue = userField.data.editFormLabel[currentLanguage];
	          languages.forEach(lang => {
	            userField.data.editFormLabel[lang] = currentValue;
	          });
	          return userField.save().then(() => {
	            return babelHelpers.classPrivateFieldLooseBase(this, _load)[_load]();
	          }).then(() => {
	            main_core.Dom.removeClass(configurator.saveButton, 'ui-btn-wait');
	            babelHelpers.classPrivateFieldLooseBase(this, _onSidebarItemClick)[_onSidebarItemClick](currentCategoryId);
	            babelHelpers.classPrivateFieldLooseBase(this, _getSearch)[_getSearch]().setValue(userField.getData().editFormLabel[currentLanguage]);
	          });
	        },
	        onCancel: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _onSidebarItemClick)[_onSidebarItemClick](currentCategoryId);
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _cleanFieldsList)[_cleanFieldsList]();
	      main_core.Dom.append(configurator.render(), babelHelpers.classPrivateFieldLooseBase(this, _getFieldsListLayout)[_getFieldsListLayout]());
	    });
	  }
	}
	function _getPreparedCategoryId2(categoryId) {
	  if (categoryId.startsWith('PROFILE')) {
	    return 'USER_LEGAL';
	  }
	  if (categoryId.startsWith('DYNAMIC_')) {
	    const fieldsList = this.getFieldsList();
	    if (main_core.Type.isPlainObject(fieldsList[categoryId])) {
	      return fieldsList[categoryId].DYNAMIC_ID;
	    }
	  }
	  return `CRM_${categoryId}`;
	}
	function _getFieldsFactoryTypesFilter2() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  const defaultFilter = main_core.Runtime.clone(babelHelpers.classPrivateFieldLooseBase(FieldSelector, _defaultFieldsFactoryFilter)[_defaultFieldsFactoryFilter]);
	  const customFilter = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]()) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.fieldsFactory) == null ? void 0 : _babelHelpers$classPr4.filter;
	  if (main_core.Type.isPlainObject(customFilter)) {
	    if (main_core.Type.isArrayFilled(customFilter['-types'])) {
	      customFilter['-types'] = [...defaultFilter['-types'], ...customFilter['-types']];
	    } else {
	      customFilter['-types'] = [...defaultFilter['-types']];
	    }
	    return customFilter;
	  }
	  if (main_core.Type.isFunction(customFilter)) {
	    return customFilter;
	  }
	  return defaultFilter;
	}
	function _applyFieldsFactoryTypesFilter2(types, filter) {
	  if (main_core.Type.isPlainObject(filter)) {
	    return types.filter(type => {
	      const allowed = !main_core.Type.isArrayFilled(filter['+types']) || filter['+types'].some(condition => {
	        if (main_core.Type.isStringFilled(condition)) {
	          return type.name === condition;
	        }
	        if (main_core.Type.isFunction(condition)) {
	          return condition(type);
	        }
	        return false;
	      });
	      const disallowed = main_core.Type.isArrayFilled(filter['-types']) && filter['-types'].some(condition => {
	        if (main_core.Type.isStringFilled(condition)) {
	          return type.name === condition;
	        }
	        if (main_core.Type.isFunction(condition)) {
	          return condition(type);
	        }
	        return false;
	      });
	      return allowed && !disallowed;
	    });
	  }
	  return types;
	}
	function _getUserFieldFactory2(categoryId, moduleId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember(`factory_${categoryId}`, () => {
	    const rootWindow = window.top;
	    const Factory = (() => {
	      if (rootWindow.BX.UI.UserFieldFactory) {
	        return rootWindow.BX.UI.UserFieldFactory.Factory;
	      }
	      return BX.UI.UserFieldFactory.Factory;
	    })();
	    const factory = new Factory(babelHelpers.classPrivateFieldLooseBase(this, _getPreparedCategoryId)[_getPreparedCategoryId](categoryId), {
	      moduleId: moduleId || DEFAULT_FIELD_MODULE_ID,
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldButton)[_getCreateFieldButton]().render()
	    });
	    const filter = babelHelpers.classPrivateFieldLooseBase(this, _getFieldsFactoryTypesFilter)[_getFieldsFactoryTypesFilter]();
	    if (main_core.Type.isFunction(filter)) {
	      factory.types = babelHelpers.classPrivateFieldLooseBase(this, _applyFieldsFactoryTypesFilter)[_applyFieldsFactoryTypesFilter](factory.types, FieldSelector.defaultFieldsFactoryFilter);
	      factory.types = filter(factory.types);
	    }
	    if (main_core.Type.isPlainObject(filter)) {
	      factory.types = babelHelpers.classPrivateFieldLooseBase(this, _applyFieldsFactoryTypesFilter)[_applyFieldsFactoryTypesFilter](factory.types, filter);
	    }
	    return factory;
	  });
	}
	function _getTitle2() {
	  const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]();
	  if (!main_core.Type.isStringFilled(options.title)) {
	    return main_core.Loc.getMessage('SIGN_B2E_FIELDS_SELECTOR_SLIDER_FIRST_PARTY_TITLE');
	  }
	  return options.title;
	}
	function _getSliderLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('sliderLayout', () => {
	    return new Promise(resolve => {
	      ui_sidepanel_layout.Layout.createLayout({
	        extensions: ['sign.b2e.field-selector'],
	        title: babelHelpers.classPrivateFieldLooseBase(this, _getTitle)[_getTitle](),
	        content: () => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _getLayout)[_getLayout]();
	        },
	        menu: {
	          items: babelHelpers.classPrivateFieldLooseBase(this, _getSidebarItems)[_getSidebarItems]()
	        },
	        toolbar: () => {
	          const toolbarItems = [babelHelpers.classPrivateFieldLooseBase(this, _getSearch)[_getSearch]().getLayout()];
	          if (babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]().alwaysHideCreateFieldButton !== true) {
	            toolbarItems.push(babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldButton)[_getCreateFieldButton]());
	          }
	          return toolbarItems;
	        },
	        buttons: ({
	          SaveButton,
	          closeButton
	        }) => {
	          return [new SaveButton({
	            text: main_core.Loc.getMessage('SIGN_B2E_FIELDS_SELECTOR_APPLY_BUTTON_LABEL'),
	            onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSaveClick)[_onSaveClick].bind(this)
	          }), closeButton];
	        }
	      }).then(result => {
	        resolve(result);
	      });
	    });
	  });
	}
	function _getRenderedSliderLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('renderedSliderLayout', () => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getSliderLayout)[_getSliderLayout]().then(layout => {
	      return layout.render();
	    });
	  });
	}
	function _onSaveClick2() {
	  const selectedFields = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedFields)[_getSelectedFields]();
	  const result = (() => {
	    const {
	      resultModifier
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getOptions$2)[_getOptions$2]();
	    if (main_core.Type.isFunction(resultModifier)) {
	      return resultModifier(selectedFields);
	    }
	    return selectedFields.map(field => {
	      return field.name;
	    });
	  })();
	  babelHelpers.classPrivateFieldLooseBase(this, _getPromiseResolver)[_getPromiseResolver]()(result);
	  this.hide();
	}
	function _setPromiseResolver2(resolver) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('promiseResolver', resolver);
	}
	function _getPromiseResolver2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('promiseResolver', () => {});
	}
	function _selectFirstCategory2() {
	  const [firstSidebarItem] = babelHelpers.classPrivateFieldLooseBase(this, _getSidebarItems)[_getSidebarItems]();
	  if (main_core.Type.isPlainObject(firstSidebarItem)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onSidebarItemClick)[_onSidebarItemClick](firstSidebarItem.id);
	  }
	}
	function _getSliderId2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('sliderId', () => {
	    return `sign.b2e.fields.selector-${main_core.Text.getRandom()}`;
	  });
	}
	function _onSliderCloseComplete2() {
	  this.emit('onSliderCloseComplete');
	  babelHelpers.classPrivateFieldLooseBase(this, _setSelectedFields)[_setSelectedFields]([]);
	}
	function _setAddButtonEnabledByCategory2(categoryId) {
	  if (Object.hasOwn(babelHelpers.classPrivateFieldLooseBase(this, _permissionAddByCategory)[_permissionAddByCategory], categoryId)) {
	    const isAllowed = babelHelpers.classPrivateFieldLooseBase(this, _permissionAddByCategory)[_permissionAddByCategory][categoryId];
	    babelHelpers.classPrivateFieldLooseBase(this, _setIsAllowedCreateField)[_setIsAllowedCreateField](isAllowed);
	    babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldButton)[_getCreateFieldButton]().setDisabled(!isAllowed);
	  }
	}
	Object.defineProperty(FieldSelector, _defaultFilter, {
	  writable: true,
	  value: {
	    '-categories': ['CATALOG', 'ACTIVITY', 'INVOICE'],
	    '-fields': [{
	      name: 'CONTACT_ORIGIN_VERSION'
	    }, {
	      name: 'CONTACT_LINK'
	    }]
	  }
	});
	Object.defineProperty(FieldSelector, _defaultFieldsFactoryFilter, {
	  writable: true,
	  value: {
	    '-types': ['employee', 'money', 'double', 'boolean', 'file', 'datetime']
	  }
	});

	exports.FieldSelector = FieldSelector;

}((this.BX.Sign.B2e = this.BX.Sign.B2e || {}),BX.UI.SidePanel,BX.UI.UserFieldFactory,BX.UI,BX.Event,BX));
//# sourceMappingURL=selector.bundle.js.map
