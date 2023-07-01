/**
 * @module layout/ui/entity-editor/editor-enum/control-options
 */
jn.define('layout/ui/entity-editor/editor-enum/control-options', (require, exports, module) => {
	/**
	 * @object EntityEditorControlOptions
	 */
	const EntityEditorControlOptions = {
		none: 0,
		showAlways: 1,

		check: (options, option) => {
			return ((options & option) === option);
		},
	};

	module.exports = { EntityEditorControlOptions };
});
