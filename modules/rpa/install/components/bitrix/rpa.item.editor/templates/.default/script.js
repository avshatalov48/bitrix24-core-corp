(function (exports,main_core,rpa_manager) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var ItemEditorComponent =
	/*#__PURE__*/
	function () {
	  function ItemEditorComponent(editorId, options) {
	    babelHelpers.classCallCheck(this, ItemEditorComponent);
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

	  babelHelpers.createClass(ItemEditorComponent, [{
	    key: "init",
	    value: function init() {
	      rpa_manager.Manager.addEditor(this.typeId, this.id, this.editor);
	    }
	  }]);
	  return ItemEditorComponent;
	}();

	namespace.ItemEditorComponent = ItemEditorComponent;

}((this.window = this.window || {}),BX,BX.Rpa));
//# sourceMappingURL=script.js.map
