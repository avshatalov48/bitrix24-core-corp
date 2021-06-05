(function (exports,main_core) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Crm.Component');
	/**
	 * @memberOf BX.Crm.Component
	 */

	var Router = /*#__PURE__*/function () {
	  function Router() {
	    babelHelpers.classCallCheck(this, Router);
	  }

	  babelHelpers.createClass(Router, null, [{
	    key: "bindAnchors",

	    /**
	     * @public
	     * @param roots
	     * @param rules
	     */
	    value: function bindAnchors(roots, rules) {
	      var _this = this;

	      var preparedRules = [];
	      rules.forEach(function (rule) {
	        preparedRules.push(_this.prependRootsToRuleConditions(roots, rule));
	      });
	      BX.SidePanel.Instance.bindAnchors({
	        rules: preparedRules
	      });
	    }
	    /**
	     * @protected
	     * @param roots
	     * @param rule
	     * @return {BX.SidePanel.Rule}
	     */

	  }, {
	    key: "prependRootsToRuleConditions",
	    value: function prependRootsToRuleConditions(roots, rule) {
	      // Don't change the received object to avoid problems
	      var localRule = main_core.Runtime.clone(rule);

	      if (!main_core.Type.isArrayFilled(localRule.condition)) {
	        return localRule;
	      }

	      var modifiedConditions = [];
	      localRule.condition.forEach(function (condition) {
	        if (main_core.Type.isRegExp(condition)) {
	          condition = condition.toString();
	        }

	        roots.forEach(function (root) {
	          modifiedConditions.push(root + condition);
	        });
	      });
	      localRule.condition = modifiedConditions;
	      return localRule;
	    }
	  }]);
	  return Router;
	}();

	namespace.Router = Router;

}((this.window = this.window || {}),BX));
//# sourceMappingURL=script.js.map
