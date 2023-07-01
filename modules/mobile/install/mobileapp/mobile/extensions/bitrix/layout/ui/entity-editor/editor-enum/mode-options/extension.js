/**
 * @module layout/ui/entity-editor/editor-enum/mode-options
 */
jn.define('layout/ui/entity-editor/editor-enum/mode-options', (require, exports, module) => {

	/**
	 * @object EntityEditorModeOptions
	 */
	const EntityEditorModeOptions = {
		none: 0x0,
		exclusive: 0x1,
		individual: 0x2,
		saveOnExit: 0x40,

		check: (options, option) => {
			return ((options & option) === option);
		},
	};

	module.exports = { EntityEditorModeOptions };
});
