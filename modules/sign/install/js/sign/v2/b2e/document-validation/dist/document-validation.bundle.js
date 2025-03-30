/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_b2e_representativeSelector,sign_type,sign_v2_helper) {
	'use strict';

	var _templateObject;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var HelpdeskCodes = Object.freeze({
	  EditorRoleDetails: '19740766',
	  ReviewerRoleDetails: '20801214'
	});
	var currentUserId = main_core.Extension.getSettings('sign.v2.b2e.document-validation').get('currentUserId');
	var _reviewerRepresentativeSelector = /*#__PURE__*/new WeakMap();
	var _getRepresentativeLayout = /*#__PURE__*/new WeakSet();
	var DocumentValidation = /*#__PURE__*/function () {
	  function DocumentValidation() {
	    babelHelpers.classCallCheck(this, DocumentValidation);
	    _classPrivateMethodInitSpec(this, _getRepresentativeLayout);
	    _classPrivateFieldInitSpec(this, _reviewerRepresentativeSelector, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _reviewerRepresentativeSelector, new sign_v2_b2e_representativeSelector.RepresentativeSelector({
	      context: "sign_b2e_representative_selector_reviewer_".concat(currentUserId),
	      description: "\n\t\t\t\t<span>\n\t\t\t\t\t".concat(sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_B2E_DOCUMENT_VALIDATION_HINT_REVIEWER'), HelpdeskCodes.ReviewerRoleDetails), "\n\t\t\t\t</span>\n\t\t\t")
	    }));
	    this.editorRepresentativeSelector = new sign_v2_b2e_representativeSelector.RepresentativeSelector({
	      context: "sign_b2e_representative_selector_editor_".concat(currentUserId),
	      description: "\n\t\t\t\t<span>\n\t\t\t\t\t".concat(sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_B2E_DOCUMENT_VALIDATION_HINT_EDITOR'), HelpdeskCodes.EditorRoleDetails), "\n\t\t\t\t</span>\n\t\t\t")
	    });
	  }
	  babelHelpers.createClass(DocumentValidation, [{
	    key: "getReviewerLayout",
	    value: function getReviewerLayout() {
	      return _classPrivateMethodGet(this, _getRepresentativeLayout, _getRepresentativeLayout2).call(this, sign_type.MemberRole.reviewer);
	    }
	  }, {
	    key: "getEditorLayout",
	    value: function getEditorLayout() {
	      return _classPrivateMethodGet(this, _getRepresentativeLayout, _getRepresentativeLayout2).call(this, sign_type.MemberRole.editor);
	    }
	  }, {
	    key: "getValidationData",
	    value: function getValidationData() {
	      var validationData = {};
	      var reviewerId = babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelector).getRepresentativeId();
	      var editorId = this.editorRepresentativeSelector.getRepresentativeId();
	      if (reviewerId) {
	        validationData.reviewer = reviewerId;
	      }
	      if (editorId) {
	        validationData.editor = editorId;
	      }
	      return validationData;
	    }
	  }, {
	    key: "load",
	    value: function load(memberId, role) {
	      var representativeSelector = role === sign_type.MemberRole.reviewer ? babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelector) : this.editorRepresentativeSelector;
	      representativeSelector.load(memberId);
	    }
	  }]);
	  return DocumentValidation;
	}();
	function _getRepresentativeLayout2(role) {
	  var representativeSelector = role === sign_type.MemberRole.reviewer ? babelHelpers.classPrivateFieldGet(this, _reviewerRepresentativeSelector) : this.editorRepresentativeSelector;
	  var representativeLayout = representativeSelector.getLayout();
	  representativeSelector.formatSelectButton('ui-btn-xs ui-btn-round ui-btn-light-border');
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), representativeLayout);
	}

	exports.DocumentValidation = DocumentValidation;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2.B2e,BX.Sign,BX.Sign.V2));
