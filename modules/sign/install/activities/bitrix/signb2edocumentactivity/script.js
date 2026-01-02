/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_core_events,sign_v2_api,ui_formElements_view) {
	'use strict';

	let _ = t => t,
	  _t;
	const namespace = main_core.Reflection.namespace('BX.Sign');
	const roles = {
	  HEAD: '1',
	  DEPUTY_HEAD: '3'
	};
	const responsibleSelector = '#id_responsible';
	const assigneeSelector = '#id_representative';
	const reviewerSelector = '#id_reviewer';
	const editorSelector = '#id_editor';
	var _buttonNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonNode");
	var _select = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("select");
	var _documentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentType");
	var _formName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formName");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _templateId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("templateId");
	var _previousData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("previousData");
	var _openSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openSlider");
	var _onSliderClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSliderClose");
	var _setTemplateList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setTemplateList");
	var _setSelectorValues = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSelectorValues");
	var _getUserSelectorsMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUserSelectorsMap");
	var _getDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDialog");
	var _getUserSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUserSelector");
	var _updateTemplateListSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTemplateListSelect");
	var _updateTagSelectors = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTagSelectors");
	var _selectMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectMembers");
	var _loadTemplatesList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadTemplatesList");
	class SignB2EDocumentActivity extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _loadTemplatesList, {
	      value: _loadTemplatesList2
	    });
	    Object.defineProperty(this, _selectMembers, {
	      value: _selectMembers2
	    });
	    Object.defineProperty(this, _updateTagSelectors, {
	      value: _updateTagSelectors2
	    });
	    Object.defineProperty(this, _updateTemplateListSelect, {
	      value: _updateTemplateListSelect2
	    });
	    Object.defineProperty(this, _getUserSelector, {
	      value: _getUserSelector2
	    });
	    Object.defineProperty(this, _getDialog, {
	      value: _getDialog2
	    });
	    Object.defineProperty(this, _getUserSelectorsMap, {
	      value: _getUserSelectorsMap2
	    });
	    Object.defineProperty(this, _setSelectorValues, {
	      value: _setSelectorValues2
	    });
	    Object.defineProperty(this, _setTemplateList, {
	      value: _setTemplateList2
	    });
	    Object.defineProperty(this, _onSliderClose, {
	      value: _onSliderClose2
	    });
	    Object.defineProperty(this, _openSlider, {
	      value: _openSlider2
	    });
	    Object.defineProperty(this, _buttonNode, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _select, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _documentType, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _formName, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _templateId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _previousData, {
	      writable: true,
	      value: {}
	    });
	    this.setEventNamespace('BX.Sign.SignB2EDocumentActivity');
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonNode)[_buttonNode] = options.buttonNode;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentType)[_documentType] = options.documentType;
	    babelHelpers.classPrivateFieldLooseBase(this, _select)[_select] = options.select;
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] = options.templateId;
	    if (!main_core.Type.isStringFilled(options.formName)) {
	      throw new Error('formName must be filled string');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _formName)[_formName] = options.formName;
	  }
	  init() {
	    babelHelpers.classPrivateFieldLooseBase(this, _setTemplateList)[_setTemplateList]();
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _buttonNode)[_buttonNode]) {
	      return;
	    }
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _buttonNode)[_buttonNode], 'click', babelHelpers.classPrivateFieldLooseBase(this, _openSlider)[_openSlider].bind(this));
	  }
	}
	function _openSlider2() {
	  BX.SidePanel.Instance.open('/sign/b2e/doc/0/?mode=template&IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER&FROM_ROBOT=1', {
	    width: 1250,
	    cacheable: false,
	    events: {
	      onClose: event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onSliderClose)[_onSliderClose](event);
	      }
	    }
	  });
	}
	async function _onSliderClose2(event) {
	  const slider = event.getSlider();
	  if (slider) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setTemplateList)[_setTemplateList](true, true);
	  }
	}
	function _setTemplateList2(needUpdateTagSelectors = false, isSliderClose = false) {
	  babelHelpers.classPrivateFieldLooseBase(this, _loadTemplatesList)[_loadTemplatesList]().then(({
	    data
	  }) => {
	    if (main_core.Type.isPlainObject(data)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _updateTemplateListSelect)[_updateTemplateListSelect](data, isSliderClose);
	      Object.entries(babelHelpers.classPrivateFieldLooseBase(this, _getUserSelectorsMap)[_getUserSelectorsMap]()).forEach(([key, selector]) => {
	        const dialog = babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog](selector);
	        if (dialog === null) {
	          return;
	        }
	        dialog.subscribe('onLoad', () => {
	          var _Object$values$templa;
	          const templateId = babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] === '' ? 0 : babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId];
	          const defaultUserId = (_Object$values$templa = Object.values(data)[templateId]) == null ? void 0 : _Object$values$templa[`${key}SelectorValue`];
	          if (defaultUserId) {
	            babelHelpers.classPrivateFieldLooseBase(this, _setSelectorValues)[_setSelectorValues]([defaultUserId], selector);
	          }
	        });
	        dialog.load();
	      });
	      if (needUpdateTagSelectors === true) {
	        babelHelpers.classPrivateFieldLooseBase(this, _updateTagSelectors)[_updateTagSelectors](data);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _select)[_select].onchange = () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _updateTagSelectors)[_updateTagSelectors](data);
	      };
	    }
	  }).catch(response => console.error(response.errors));
	}
	function _setSelectorValues2(selectorValues, selector) {
	  const dialog = babelHelpers.classPrivateFieldLooseBase(this, _getDialog)[_getDialog](selector);
	  if (dialog === null) {
	    return;
	  }
	  dialog.getSelectedItems().forEach(item => {
	    item.deselect();
	  });
	  selectorValues.forEach(value => {
	    let item = null;
	    const roleId = roles[value];
	    if (roleId !== undefined) {
	      item = dialog.getItem([roleId, value]);
	    } else if (main_core.Type.isInteger(Number(value))) {
	      item = dialog.getItem(['user', value]);
	    }
	    if (item) {
	      item.select();
	    }
	  });
	}
	function _getUserSelectorsMap2() {
	  return {
	    responsible: responsibleSelector,
	    assignee: assigneeSelector,
	    reviewer: reviewerSelector,
	    editor: editorSelector
	  };
	}
	function _getDialog2(selector) {
	  const userSelector = babelHelpers.classPrivateFieldLooseBase(this, _getUserSelector)[_getUserSelector](selector);
	  if (!userSelector) {
	    return null;
	  }
	  return userSelector.tagSelector.getDialog();
	}
	function _getUserSelector2(selector) {
	  return BX.Bizproc.UserSelector.getByNode(document.querySelector(selector));
	}
	function _updateTemplateListSelect2(data, isSliderClose) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _previousData)[_previousData] && isSliderClose) {
	    const previousFirstTemplateId = Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _previousData)[_previousData])[0];
	    const currentFirstTemplateId = Object.keys(data)[0];
	    if (previousFirstTemplateId !== currentFirstTemplateId) {
	      babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] = currentFirstTemplateId;
	    }
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _select)[_select]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _select)[_select].innerHTML = '';
	  }
	  Object.entries(data).forEach(([id, {
	    title
	  }]) => {
	    const selected = babelHelpers.classPrivateFieldLooseBase(this, _templateId)[_templateId] === id ? 'selected' : '';
	    const idValue = main_core.Text.encode(id);
	    const titleValue = main_core.Text.encode(title);
	    const option = main_core.Tag.render(_t || (_t = _`<option value="${0}" ${0}>${0}</option>`), idValue, selected, titleValue);
	    main_core.Dom.append(option, babelHelpers.classPrivateFieldLooseBase(this, _select)[_select]);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _previousData)[_previousData] = {
	    ...data
	  };
	}
	function _updateTagSelectors2(data) {
	  const selectedId = babelHelpers.classPrivateFieldLooseBase(this, _select)[_select].value;
	  if (!(selectedId in data)) {
	    return;
	  }
	  const selectedItem = data[selectedId];
	  if (selectedItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectMembers)[_selectMembers](selectedItem);
	  }
	}
	function _selectMembers2(selectedItem) {
	  if (selectedItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setSelectorValues)[_setSelectorValues]([selectedItem.responsibleSelectorValue], responsibleSelector);
	    babelHelpers.classPrivateFieldLooseBase(this, _setSelectorValues)[_setSelectorValues]([selectedItem.assigneeSelectorValue], assigneeSelector);
	    babelHelpers.classPrivateFieldLooseBase(this, _setSelectorValues)[_setSelectorValues](selectedItem.reviewerSelectorValue, reviewerSelector);
	    babelHelpers.classPrivateFieldLooseBase(this, _setSelectorValues)[_setSelectorValues]([selectedItem.editorSelectorValue], editorSelector);
	  }
	}
	function _loadTemplatesList2() {
	  return main_core.ajax.runAction('bizproc.activity.request', {
	    data: {
	      activity: 'SignB2EDocumentActivity',
	      documentType: babelHelpers.classPrivateFieldLooseBase(this, _documentType)[_documentType],
	      params: {
	        form_name: babelHelpers.classPrivateFieldLooseBase(this, _formName)[_formName]
	      }
	    }
	  });
	}
	namespace.SignB2EDocumentActivity = SignB2EDocumentActivity;

	exports.SignB2EDocumentActivity = SignB2EDocumentActivity;

}((this.BX.Sign = this.BX.Sign || {}),BX,BX.Event,BX.Sign.V2,BX.UI.FormElements));
//# sourceMappingURL=script.js.map
