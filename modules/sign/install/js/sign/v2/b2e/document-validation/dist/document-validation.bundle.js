/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_b2e_representativeSelector,sign_type,sign_v2_helper) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var HelpdeskCodes = Object.freeze({
	  EditorRoleDetails: '19740766'
	});
	var currentUserId = main_core.Extension.getSettings('sign.v2.b2e.document-validation').get('currentUserId');
	var maxReviewersCount = 20;
	var _reviewerRepresentativeSelectorList = /*#__PURE__*/new WeakMap();
	var _isTemplate = /*#__PURE__*/new WeakMap();
	var _onReviewerDelete = /*#__PURE__*/new WeakMap();
	var _onDeleteCallback = /*#__PURE__*/new WeakSet();
	var _setExcludedIdListForRepresentativeReviewerList = /*#__PURE__*/new WeakSet();
	var _getSelectedReviewerEntityList = /*#__PURE__*/new WeakSet();
	var DocumentValidation = /*#__PURE__*/function () {
	  function DocumentValidation() {
	    var isTemplate = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	    var onReviewerDelete = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	    babelHelpers.classCallCheck(this, DocumentValidation);
	    _classPrivateMethodInitSpec(this, _getSelectedReviewerEntityList);
	    _classPrivateMethodInitSpec(this, _setExcludedIdListForRepresentativeReviewerList);
	    _classPrivateMethodInitSpec(this, _onDeleteCallback);
	    _classPrivateFieldInitSpec(this, _reviewerRepresentativeSelectorList, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _isTemplate, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _onReviewerDelete, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _isTemplate, isTemplate);
	    babelHelpers.classPrivateFieldSet(this, _reviewerRepresentativeSelectorList, []);
	    babelHelpers.classPrivateFieldSet(this, _onReviewerDelete, onReviewerDelete);
	    this.addReviewerRepresentativeSelector();
	    this.editorRepresentativeSelector = new sign_v2_b2e_representativeSelector.RepresentativeSelector({
	      context: "sign_b2e_representative_selector_editor_".concat(currentUserId),
	      description: "\n\t\t\t\t<span>\n\t\t\t\t\t".concat(sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_B2E_DOCUMENT_VALIDATION_HINT_EDITOR'), HelpdeskCodes.EditorRoleDetails), "\n\t\t\t\t</span>\n\t\t\t"),
	      roleEnabled: isTemplate
	    });
	  }
	  babelHelpers.createClass(DocumentValidation, [{
	    key: "addReviewerRepresentativeSelector",
	    value: function addReviewerRepresentativeSelector() {
	      var _this = this;
	      if (this.getReviewerRepresentativeSelectorCount() >= maxReviewersCount) {
	        return null;
	      }
	      var index = main_core.Text.getRandom();
	      var excludedEntityList = _classPrivateMethodGet(this, _getSelectedReviewerEntityList, _getSelectedReviewerEntityList2).call(this);
	      var selector = new sign_v2_b2e_representativeSelector.RepresentativeSelector({
	        cacheable: false,
	        context: "sign_b2e_representative_selector_reviewer_".concat(currentUserId, "_").concat(index),
	        roleEnabled: babelHelpers.classPrivateFieldGet(this, _isTemplate),
	        isDescriptionVisible: false,
	        isMenuButtonVisible: Object.keys(babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)).length > 0,
	        onDelete: function onDelete(elementId) {
	          return _classPrivateMethodGet(_this, _onDeleteCallback, _onDeleteCallback2).call(_this, elementId);
	        },
	        onHide: function onHide() {
	          return _classPrivateMethodGet(_this, _setExcludedIdListForRepresentativeReviewerList, _setExcludedIdListForRepresentativeReviewerList2).call(_this);
	        },
	        excludedEntityList: excludedEntityList
	      });
	      selector.formatSelectButton('ui-btn-xs ui-btn-round ui-btn-light-border');
	      babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)[selector.getContainerId()] = selector;
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign_b2e_representative_selector_reviewer\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), selector.getLayout());
	    }
	  }, {
	    key: "getReviewerLayoutList",
	    value: function getReviewerLayoutList() {
	      var result = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<span></span>"])));
	      for (var _i = 0, _Object$values = Object.values(babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)); _i < _Object$values.length; _i++) {
	        var representativeSelector = _Object$values[_i];
	        var representativeLayout = representativeSelector.getLayout();
	        var block = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign_b2e_representative_selector_reviewer\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), representativeLayout);
	        main_core.Dom.append(block, result);
	      }
	      return result;
	    }
	  }, {
	    key: "getEditorLayout",
	    value: function getEditorLayout() {
	      var representativeLayout = this.editorRepresentativeSelector.getLayout();
	      this.editorRepresentativeSelector.formatSelectButton('ui-btn-xs ui-btn-round ui-btn-light-border');
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), representativeLayout);
	    }
	  }, {
	    key: "getValidationData",
	    value: function getValidationData() {
	      var validationData = _classPrivateMethodGet(this, _getSelectedReviewerEntityList, _getSelectedReviewerEntityList2).call(this);
	      var editorId = this.editorRepresentativeSelector.getRepresentativeId();
	      var editorType = this.editorRepresentativeSelector.getRepresentativeItemType();
	      if (editorId && editorType) {
	        validationData.push({
	          entityId: editorId,
	          entityType: editorType,
	          role: sign_type.MemberRole.editor
	        });
	      }
	      return validationData;
	    }
	  }, {
	    key: "loadReviewers",
	    value: function loadReviewers(reviewerList) {
	      if (reviewerList.length === 0) {
	        return;
	      }
	      if (reviewerList.length > this.getReviewerRepresentativeSelectorCount()) {
	        for (var i = 1; i < reviewerList.length; i++) {
	          this.addReviewerRepresentativeSelector();
	        }
	      }
	      var selectorIndex = 0;
	      for (var _i2 = 0, _Object$values2 = Object.values(babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)); _i2 < _Object$values2.length; _i2++) {
	        var _reviewerList$selecto;
	        var representativeSelector = _Object$values2[_i2];
	        var reviewer = (_reviewerList$selecto = reviewerList[selectorIndex]) !== null && _reviewerList$selecto !== void 0 ? _reviewerList$selecto : null;
	        if (reviewer === null) {
	          continue;
	        }
	        representativeSelector.load(reviewer.entityId, reviewer.entityType);
	        selectorIndex++;
	      }
	      _classPrivateMethodGet(this, _setExcludedIdListForRepresentativeReviewerList, _setExcludedIdListForRepresentativeReviewerList2).call(this);
	    }
	  }, {
	    key: "loadEditors",
	    value: function loadEditors(editorList) {
	      if (editorList.length === 0) {
	        return;
	      }
	      var editor = editorList[0];
	      this.editorRepresentativeSelector.load(editor.entityId, editor.entityType);
	    }
	  }, {
	    key: "getReviewerRepresentativeSelectorCount",
	    value: function getReviewerRepresentativeSelectorCount() {
	      return Object.keys(babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)).length;
	    }
	  }]);
	  return DocumentValidation;
	}();
	function _onDeleteCallback2(elementId) {
	  if (!Object.hasOwn(babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList), elementId)) {
	    return;
	  }
	  delete babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)[elementId];
	  _classPrivateMethodGet(this, _setExcludedIdListForRepresentativeReviewerList, _setExcludedIdListForRepresentativeReviewerList2).call(this);
	  babelHelpers.classPrivateFieldGet(this, _onReviewerDelete).call(this, elementId);
	}
	function _setExcludedIdListForRepresentativeReviewerList2() {
	  var excludedEntityList = _classPrivateMethodGet(this, _getSelectedReviewerEntityList, _getSelectedReviewerEntityList2).call(this);
	  var _loop = function _loop() {
	    var representativeSelector = _Object$values3[_i3];
	    var excludedEntityListWithoutSelected = excludedEntityList.filter(function (entity) {
	      return !(entity.entityId === representativeSelector.getRepresentativeId() && entity.entityType === representativeSelector.getRepresentativeItemType());
	    });
	    representativeSelector.setExcludedEntityList(excludedEntityListWithoutSelected);
	  };
	  for (var _i3 = 0, _Object$values3 = Object.values(babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)); _i3 < _Object$values3.length; _i3++) {
	    _loop();
	  }
	}
	function _getSelectedReviewerEntityList2() {
	  var result = [];
	  for (var _i4 = 0, _Object$values4 = Object.values(babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelectorList)); _i4 < _Object$values4.length; _i4++) {
	    var representativeSelector = _Object$values4[_i4];
	    var reviewerId = representativeSelector.getRepresentativeId();
	    if (!reviewerId) {
	      continue;
	    }
	    var reviewerType = representativeSelector.getRepresentativeItemType();
	    if (!reviewerType) {
	      continue;
	    }
	    result.push({
	      entityId: reviewerId,
	      entityType: reviewerType,
	      role: sign_type.MemberRole.reviewer
	    });
	  }
	  return result;
	}

	exports.maxReviewersCount = maxReviewersCount;
	exports.DocumentValidation = DocumentValidation;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2.B2e,BX.Sign,BX.Sign.V2));
