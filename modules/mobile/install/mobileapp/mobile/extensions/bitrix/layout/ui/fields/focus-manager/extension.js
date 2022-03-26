(() => {
	/** @type{?Fields.BaseField} */
	let focusedField = null;

	/**
	 * @class Fields.FocusManager
	 */
	class FocusManager
	{
		static hasFocusedField()
		{
			return focusedField !== null && focusedField.state.focus;
		}

		static setFocusedField(field)
		{
			focusedField = field;
		}

		/**
		 * @param {Fields.BaseField} field
		 * @param {?Function} callback
		 */
		static blurFocusedFieldIfHas(field, callback = null)
		{
			if (this.hasFocusedField())
			{
				focusedField.removeFocus(callback);

				if (focusedField.hasKeyboard() && !field.hasKeyboard())
				{
					Keyboard.dismiss();
				}
			}
			else
			{
				callback && callback();
			}
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.FocusManager = FocusManager;
})();