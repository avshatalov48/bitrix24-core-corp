this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_entitySelector,main_core) {
	'use strict';

	var _templateObject;
	var Footer = /*#__PURE__*/function (_DefaultFooter) {
	  babelHelpers.inherits(Footer, _DefaultFooter);
	  function Footer(dialog, options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Footer);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Footer).call(this, dialog, options));
	    _this.userId = options.userId ? options.userId.toString() : BX.message('USER_ID');
	    _this.taskId = options.taskId ? options.taskId.toString() : 0;
	    _this.groupId = options.groupId ? options.groupId.toString() : 0;
	    return _this;
	  }
	  babelHelpers.createClass(Footer, [{
	    key: "getContent",
	    value: function getContent() {
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
	  }]);
	  return Footer;
	}(ui_entitySelector.DefaultFooter);

	exports.Footer = Footer;

}((this.BX.Tasks.EntitySelector = this.BX.Tasks.EntitySelector || {}),BX.UI.EntitySelector,BX));
//# sourceMappingURL=tasks-entity-selector.bundle.js.map
