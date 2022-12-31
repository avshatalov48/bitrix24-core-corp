(() => {
	BX.UI = BX.UI || {};

	/**
	 * @class BX.UI.EntityEditorVisibilityPolicy
	 */
	BX.UI.EntityEditorVisibilityPolicy = {
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
				return mode === BX.UI.EntityEditorMode.view;
			}
			else if (policy === this.edit)
			{
				return mode === BX.UI.EntityEditorMode.edit;
			}

			return true;
		},
	};
})();
