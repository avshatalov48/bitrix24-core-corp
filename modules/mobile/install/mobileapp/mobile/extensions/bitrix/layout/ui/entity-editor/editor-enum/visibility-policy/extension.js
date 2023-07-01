/**
 * @module layout/ui/entity-editor/editor-enum/visibility-policy
 */
jn.define('layout/ui/entity-editor/editor-enum/visibility-policy', (require, exports, module) => {

	const { EntityEditorMode } = require('layout/ui/entity-editor/editor-enum/mode');

	/**
	 * @object EntityEditorVisibilityPolicy
	 */
	const EntityEditorVisibilityPolicy = {
		always: 0,
		view: 1,
		edit: 2,

		parse: function(str) {
			str = str.toLowerCase();
			if (str === 'view')
			{
				return this.view;
			}
			else if (str === 'edit')
			{
				return this.edit;
			}

			return this.always;
		},

		/**
		 * @param {EntityEditorBaseControl} control
		 * @returns {boolean}
		 */
		checkVisibility: function(control) {
			const mode = control.getMode();
			const policy = control.getVisibilityPolicy();

			if (policy === this.view)
			{
				return mode === EntityEditorMode.view;
			}
			else if (policy === this.edit)
			{
				return mode === EntityEditorMode.edit;
			}

			return true;
		},
	};

	module.exports = { EntityEditorVisibilityPolicy };
});
