(() => {
	BX.UI = BX.UI || {};
	/**
	 * @class BX.UI.EntityEditorControlOptions
	 */
	BX.UI.EntityEditorControlOptions = {
		none: 0,
		showAlways: 1,

		check: (options, option) => {
			return ((options & option) === option);
		},
	};
})();