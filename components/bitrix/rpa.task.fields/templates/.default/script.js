(function (exports,main_core,rpa_manager) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var TaskFieldsComponent =
	/*#__PURE__*/
	function () {
	  function TaskFieldsComponent(editorId, options) {
	    babelHelpers.classCallCheck(this, TaskFieldsComponent);
	    babelHelpers.defineProperty(this, "editor", null);

	    if (main_core.Type.isString(editorId)) {
	      this.editorId = editorId;
	      this.editor = BX.UI.EntityEditor.get(this.editorId);

	      if (main_core.Type.isPlainObject(options)) {
	        if (options.id) {
	          this.id = parseInt(options.id);
	        }

	        if (options.typeId) {
	          this.typeId = parseInt(options.typeId);

	          if (!this.id) {
	            this.id = 0;
	          }
	        }
	      }
	    }
	  }

	  babelHelpers.createClass(TaskFieldsComponent, [{
	    key: "init",
	    value: function init() {
	      rpa_manager.Manager.addEditor(this.typeId, this.id, this.editor);
	    }
	  }]);
	  return TaskFieldsComponent;
	}();

	namespace.TaskFieldsComponent = TaskFieldsComponent;

}((this.window = this.window || {}),BX,BX.Rpa));
//# sourceMappingURL=script.js.map
