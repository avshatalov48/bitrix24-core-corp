/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_entitySelector,main_core) {
	'use strict';

	var _templateObject, _templateObject2;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _isTaskTemplateFooter = /*#__PURE__*/new WeakSet();
	var _renderTasksTagFooter = /*#__PURE__*/new WeakSet();
	var _renderTasksTemplateFooter = /*#__PURE__*/new WeakSet();
	var Footer = /*#__PURE__*/function (_DefaultFooter) {
	  babelHelpers.inherits(Footer, _DefaultFooter);
	  function Footer(dialog, options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Footer);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Footer).call(this, dialog, options));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderTasksTemplateFooter);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _renderTasksTagFooter);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _isTaskTemplateFooter);
	    _this.userId = options.userId ? options.userId.toString() : BX.message('USER_ID');
	    _this.taskId = options.taskId ? options.taskId.toString() : 0;
	    _this.groupId = options.groupId ? options.groupId.toString() : 0;
	    return _this;
	  }
	  babelHelpers.createClass(Footer, [{
	    key: "getContent",
	    value: function getContent() {
	      if (_classPrivateMethodGet(this, _isTaskTemplateFooter, _isTaskTemplateFooter2).call(this)) {
	        return _classPrivateMethodGet(this, _renderTasksTemplateFooter, _renderTasksTemplateFooter2).call(this);
	      }
	      return _classPrivateMethodGet(this, _renderTasksTagFooter, _renderTasksTagFooter2).call(this);
	    }
	  }]);
	  return Footer;
	}(ui_entitySelector.DefaultFooter);
	function _isTaskTemplateFooter2() {
	  return babelHelpers.toConsumableArray(this.dialog.entities.keys())[0] === 'task-template';
	}
	function _renderTasksTagFooter2() {
	  var url = '/company/personal/user/' + this.userId + '/tasks/tags/';
	  var task = this.taskId;
	  var group = this.groupId;
	  if (group !== 0) {
	    url = '/company/personal/user/' + this.userId + '/tasks/tags/?GROUP_ID=' + group;
	  }
	  return this.cache.remember('content', function () {
	    return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tags-widget-custom-footer\">\n\t\t\t\t\t<a class=\"ui-selector-footer-link ui-selector-footer-link-add\"  \n\t\t\t\t\t\tid=\"tags-widget-custom-footer-add-new\" hidden=\"true\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t\t<span class=\"ui-selector-footer-conjunction\" \n\t\t\t\t\t\tid=\"tags-widget-custom-footer-conjunction\" hidden=\"true\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<a class=\"ui-selector-footer-link\" \n\t\t\t\t\t\tonclick=\"BX.SidePanel.Instance.open('", "', {\n\t\t\t\t\t\t\t\t\twidth: 1000,\n\t\t\t\t\t\t\t\t\trequestMethod: 'post',\n\t\t\t\t\t\t\t\t\trequestParams: {\n\t\t\t\t\t\t\t\t\t\ttaskId: ", ",\n\t\t\t\t\t\t\t\t\t},\n\t\t\t\t\t\t\t\t})\n\t\t\t\t\t\t\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t"], ["\n\t\t\t\t<div class=\"tags-widget-custom-footer\">\n\t\t\t\t\t<a class=\"ui-selector-footer-link ui-selector-footer-link-add\"  \n\t\t\t\t\t\tid=\"tags-widget-custom-footer-add-new\" hidden=\"true\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t\t<span class=\"ui-selector-footer-conjunction\" \n\t\t\t\t\t\tid=\"tags-widget-custom-footer-conjunction\" hidden=\"true\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<a class=\"ui-selector-footer-link\" \n\t\t\t\t\t\tonclick=\"BX.SidePanel.Instance.open(\\'", "\\', {\n\t\t\t\t\t\t\t\t\twidth: 1000,\n\t\t\t\t\t\t\t\t\trequestMethod: 'post',\n\t\t\t\t\t\t\t\t\trequestParams: {\n\t\t\t\t\t\t\t\t\t\ttaskId: ", ",\n\t\t\t\t\t\t\t\t\t},\n\t\t\t\t\t\t\t\t})\n\t\t\t\t\t\t\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_CREATE'), main_core.Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_OR'), url, task, main_core.Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_GET_TAG_SLIDER'));
	  });
	}
	function _renderTasksTemplateFooter2() {
	  if (!this.options.canCreateTemplate) {
	    return null;
	  }
	  return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<a class=\"ui-selector-footer-link ui-selector-footer-link-add\" href=\"", "\">\n\t\t\t\t", "\n\t\t\t</a>\n\t\t"])), this.options.templateAddUrl, main_core.Loc.getMessage('TASKS_ENTITY_SELECTOR_TEMPLATE_FOOTER_CREATE_TEMPLATE'));
	}

	exports.Footer = Footer;

}((this.BX.Tasks.EntitySelector = this.BX.Tasks.EntitySelector || {}),BX.UI.EntitySelector,BX));
//# sourceMappingURL=tasks-entity-selector.bundle.js.map
