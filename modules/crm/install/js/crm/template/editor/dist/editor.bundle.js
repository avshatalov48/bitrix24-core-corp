/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_core_events,crm_entitySelector,ui_entitySelector) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var SELECTOR_TARGET_NAME = 'crm-template-editor-element-pill';
	var _id = /*#__PURE__*/new WeakMap();
	var _target = /*#__PURE__*/new WeakMap();
	var _entityTypeId = /*#__PURE__*/new WeakMap();
	var _entityId = /*#__PURE__*/new WeakMap();
	var _categoryId = /*#__PURE__*/new WeakMap();
	var _placeHolderMaskRe = /*#__PURE__*/new WeakMap();
	var _headerContainerEl = /*#__PURE__*/new WeakMap();
	var _bodyContainerEl = /*#__PURE__*/new WeakMap();
	var _footerContainerEl = /*#__PURE__*/new WeakMap();
	var _placeHoldersDialogDefaultOptions = /*#__PURE__*/new WeakMap();
	var _headerRaw = /*#__PURE__*/new WeakMap();
	var _bodyRaw = /*#__PURE__*/new WeakMap();
	var _footerRaw = /*#__PURE__*/new WeakMap();
	var _createContainer = /*#__PURE__*/new WeakSet();
	var _createContainerWithSelectors = /*#__PURE__*/new WeakSet();
	var _getPlainText = /*#__PURE__*/new WeakSet();
	var _bindEvents = /*#__PURE__*/new WeakSet();
	var _assertValidParams = /*#__PURE__*/new WeakSet();
	var Editor = /*#__PURE__*/function () {
	  function Editor(_params) {
	    babelHelpers.classCallCheck(this, Editor);
	    _classPrivateMethodInitSpec(this, _assertValidParams);
	    _classPrivateMethodInitSpec(this, _bindEvents);
	    _classPrivateMethodInitSpec(this, _getPlainText);
	    _classPrivateMethodInitSpec(this, _createContainerWithSelectors);
	    _classPrivateMethodInitSpec(this, _createContainer);
	    _classPrivateFieldInitSpec(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _target, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _entityId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _categoryId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _placeHolderMaskRe, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _headerContainerEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _bodyContainerEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _footerContainerEl, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _placeHoldersDialogDefaultOptions, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _headerRaw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _bodyRaw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _footerRaw, {
	      writable: true,
	      value: null
	    });
	    _classPrivateMethodGet(this, _assertValidParams, _assertValidParams2).call(this, _params);
	    babelHelpers.classPrivateFieldSet(this, _id, _params.id || "crm-template-editor-".concat(main_core.Text.getRandom()));
	    babelHelpers.classPrivateFieldSet(this, _target, _params.target);
	    babelHelpers.classPrivateFieldSet(this, _entityTypeId, _params.entityTypeId);
	    babelHelpers.classPrivateFieldSet(this, _entityId, _params.entityId);
	    babelHelpers.classPrivateFieldSet(this, _categoryId, main_core.Type.isNumber(_params.entityId) ? _params.entityId : null);
	    babelHelpers.classPrivateFieldSet(this, _placeHolderMaskRe, main_core.Type.isStringFilled(_params.placeHolderMaskRe) ? _params.placeHolderMaskRe : /{{\s+(\d+)\s+}}/g); // {{ 1 }}, {{ 2 }}, {{ 3 }} ... default regex

	    babelHelpers.classPrivateFieldSet(this, _placeHoldersDialogDefaultOptions, {
	      multiple: false,
	      showAvatars: false,
	      dropdownMode: true,
	      compactView: true,
	      enableSearch: true,
	      tagSelectorOptions: {
	        textBoxWidth: '100%'
	      },
	      entities: [{
	        id: 'placeholder',
	        options: {
	          entityTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeId),
	          entityId: babelHelpers.classPrivateFieldGet(this, _entityId),
	          categoryId: babelHelpers.classPrivateFieldGet(this, _categoryId)
	        }
	      }]
	    });
	    _classPrivateMethodGet(this, _createContainer, _createContainer2).call(this);
	    _classPrivateMethodGet(this, _bindEvents, _bindEvents2).call(this);
	  }

	  // region Public methods
	  babelHelpers.createClass(Editor, [{
	    key: "setHeader",
	    value: function setHeader(input) {
	      if (!main_core.Type.isStringFilled(input)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _headerRaw, input);
	      main_core.Dom.append(_classPrivateMethodGet(this, _createContainerWithSelectors, _createContainerWithSelectors2).call(this, input), babelHelpers.classPrivateFieldGet(this, _headerContainerEl));
	    }
	  }, {
	    key: "setBody",
	    value: function setBody(input) {
	      if (!main_core.Type.isStringFilled(input)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _bodyRaw, input);
	      main_core.Dom.append(_classPrivateMethodGet(this, _createContainerWithSelectors, _createContainerWithSelectors2).call(this, input), babelHelpers.classPrivateFieldGet(this, _bodyContainerEl));
	    }
	  }, {
	    key: "setFooter",
	    value: function setFooter(input) {
	      if (!main_core.Type.isStringFilled(input)) {
	        return;
	      }
	      babelHelpers.classPrivateFieldSet(this, _footerRaw, input);
	      main_core.Dom.append(_classPrivateMethodGet(this, _createContainerWithSelectors, _createContainerWithSelectors2).call(this, input), babelHelpers.classPrivateFieldGet(this, _footerContainerEl));
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      return {
	        header: _classPrivateMethodGet(this, _getPlainText, _getPlainText2).call(this, babelHelpers.classPrivateFieldGet(this, _headerContainerEl)),
	        body: _classPrivateMethodGet(this, _getPlainText, _getPlainText2).call(this, babelHelpers.classPrivateFieldGet(this, _bodyContainerEl)),
	        footer: _classPrivateMethodGet(this, _getPlainText, _getPlainText2).call(this, babelHelpers.classPrivateFieldGet(this, _footerContainerEl))
	      };
	    }
	  }, {
	    key: "getRawData",
	    value: function getRawData() {
	      return {
	        header: babelHelpers.classPrivateFieldGet(this, _headerRaw),
	        body: babelHelpers.classPrivateFieldGet(this, _bodyRaw),
	        footer: babelHelpers.classPrivateFieldGet(this, _footerRaw)
	      };
	    } // endregion
	  }]);
	  return Editor;
	}();
	function _createContainer2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _target)) {
	    return;
	  }
	  var containerEl = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div id=\"", "\" class=\"crm-template-editor crm-template-editor__scope\"></div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _id));
	  babelHelpers.classPrivateFieldSet(this, _headerContainerEl, main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-template-editor-header\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _headerContainerEl), containerEl);
	  babelHelpers.classPrivateFieldSet(this, _bodyContainerEl, main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-template-editor-body\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _bodyContainerEl), containerEl);
	  babelHelpers.classPrivateFieldSet(this, _footerContainerEl, main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-template-editor-footer\"></div>"]))));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _footerContainerEl), containerEl);
	  main_core.Dom.append(containerEl, babelHelpers.classPrivateFieldGet(this, _target));
	}
	function _createContainerWithSelectors2(input) {
	  var placeHolders = input.match(babelHelpers.classPrivateFieldGet(this, _placeHolderMaskRe));
	  if (!placeHolders) {
	    return input;
	  }
	  var result = input.replace(babelHelpers.classPrivateFieldGet(this, _placeHolderMaskRe), "<span class=\"".concat(SELECTOR_TARGET_NAME, "\">").concat(main_core.Loc.getMessage('CRM_TEMPLATE_EDITOR_EMPTY_PLACEHOLDER_LABEL'), "</span>"));
	  var container = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), result);
	  var dlgOptions = babelHelpers.classPrivateFieldGet(this, _placeHoldersDialogDefaultOptions);
	  var elements = container.querySelectorAll(".".concat(SELECTOR_TARGET_NAME));
	  elements.forEach(function (element) {
	    dlgOptions.events = {
	      'Item:onSelect': function ItemOnSelect(event) {
	        var item = event.getData().item;

	        // eslint-disable-next-line no-param-reassign
	        element.textContent = item.getTitle();
	        main_core.Dom.attr(element, 'placeholder-id', item.id);
	        main_core.Dom.addClass(element, '--selected');
	      },
	      'Item:onDeselect': function ItemOnDeselect(event) {
	        // eslint-disable-next-line no-param-reassign
	        element.textContent = main_core.Loc.getMessage('CRM_TEMPLATE_EDITOR_EMPTY_PLACEHOLDER_LABEL');
	        main_core.Dom.attr(element, 'placeholder-id', '');
	        main_core.Dom.removeClass(element, '--selected');
	      }
	    };
	    dlgOptions.targetNode = element;
	    var dlg = new crm_entitySelector.Dialog(dlgOptions);
	    main_core.Event.bind(element, 'click', function () {
	      return dlg.show();
	    });
	  });
	  return container;
	}
	function _getPlainText2(container) {
	  if (!main_core.Type.isDomNode(container)) {
	    return null;
	  }
	  var containerCopy = main_core.Runtime.clone(container);
	  var elements = containerCopy.querySelectorAll(".".concat(SELECTOR_TARGET_NAME));
	  elements.forEach(function (element) {
	    // eslint-disable-next-line no-param-reassign
	    element.textContent = element !== null && element !== void 0 && element.hasAttribute('placeholder-id') ? "{".concat(element.getAttribute('placeholder-id'), "}") : '';
	  });
	  return containerCopy.textContent;
	}
	function _bindEvents2() {
	  // TODO: not implemented yet
	}
	function _assertValidParams2(params) {
	  if (!main_core.Type.isPlainObject(params)) {
	    throw new TypeError('BX.Crm.Template.Editor: The "params" argument must be object');
	  }
	  if (!main_core.Type.isDomNode(params.target)) {
	    throw new Error('BX.Crm.Template.Editor: The "target" argument must be DOM node');
	  }
	  if (!BX.CrmEntityType.isDefined(params.entityTypeId)) {
	    throw new TypeError('BX.Crm.Template.Editor: The "entityTypeId" argument is not correct');
	  }
	  if (!main_core.Type.isNumber(params.entityId) || params.entityId <= 0) {
	    throw new TypeError('BX.Crm.Template.Editor: The "entityId" argument is not correct');
	  }
	}

	exports.Editor = Editor;

}((this.BX.Crm.Template = this.BX.Crm.Template || {}),BX,BX.Event,BX.Crm.EntitySelectorEx,BX.UI.EntitySelector));
//# sourceMappingURL=editor.bundle.js.map
