/**
 * @module layout/ui/product-grid/services/focus-context
 */
jn.define('layout/ui/product-grid/services/focus-context', (require, exports, module) => {

	const fields = new Set();

	/**
	 * @class FocusContext
	 */
	class FocusContext
	{
		static registerField({ref})
		{
			fields.add(ref);
		}

		static blur()
		{
			fields.forEach(ref => {
				if (ref && ref.isFocused())
				{
					ref.blur();
					Keyboard.dismiss();
				}
			});
		}
	}

	module.exports = { FocusContext };
});