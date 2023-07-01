/**
 * @module layout/ui/entity-editor/editor-enum/mode
 */
jn.define('layout/ui/entity-editor/editor-enum/mode', (require, exports, module) => {

	/**
	 * @object EntityEditorMode
	 */
	const EntityEditorMode = {
		intermediate: 0,
		edit: 1,
		view: 2,

		names: {
			view: 'view',
			edit: 'edit',
		},

		getName: function(id) {
			if (id === this.edit)
			{
				return this.names.edit;
			}
			else if (id === this.view)
			{
				return this.names.view;
			}
			return '';
		},

		parse: function(str) {
			str = str.toLowerCase();
			if (str === this.names.edit)
			{
				return this.edit;
			}
			else if (str === this.names.view)
			{
				return this.view;
			}
			return this.intermediate;
		},
	};

	module.exports = { EntityEditorMode };
});
