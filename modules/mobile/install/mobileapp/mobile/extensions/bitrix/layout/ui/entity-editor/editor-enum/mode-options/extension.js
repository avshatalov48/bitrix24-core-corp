(() => {
	BX.UI = BX.UI || {};

	/**
	 * @class BX.UI.EntityEditorModeOptions
	 */
	BX.UI.EntityEditorModeOptions = {
		none: 0x0,
		exclusive: 0x1,
		individual: 0x2,
		saveOnExit: 0x40,

		check: (options, option) => {
			return ((options & option) === option);
		},
	};
})();
