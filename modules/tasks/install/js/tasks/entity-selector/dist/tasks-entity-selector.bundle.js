this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,ui_entitySelector,main_core) {
	'use strict';

	var _templateObject;

	var Footer = /*#__PURE__*/function (_DefaultFooter) {
	  babelHelpers.inherits(Footer, _DefaultFooter);

	  function Footer(dialog, options) {
	    babelHelpers.classCallCheck(this, Footer);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Footer).call(this, dialog, options));
	  }

	  babelHelpers.createClass(Footer, [{
	    key: "getContent",
	    value: function getContent() {
	      return this.cache.remember('content', function () {
	        return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n                <div class=\"my-module-footer-class\">\n                    ", "\n                </div>\n            "])), main_core.Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_CREATE_NEW'));
	      });
	    }
	  }]);
	  return Footer;
	}(ui_entitySelector.DefaultFooter);

	exports.Footer = Footer;

}((this.BX.Tasks.EntitySelector = this.BX.Tasks.EntitySelector || {}),BX.UI.EntitySelector,BX));
//# sourceMappingURL=tasks-entity-selector.bundle.js.map
