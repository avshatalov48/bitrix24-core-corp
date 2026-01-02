/**
 * @module im/messenger-v2/application/lib/dialog-manager/normalizer
 */
jn.define('im/messenger-v2/application/lib/dialog-manager/normalizer', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');

	/**
	 * @param {DialogOpenOptions} options
	 * @return {DialogOpenOptions}
	 */
	function normalizeOpenDialogOptions(options)
	{
		/**
		 * @type {DialogOpenOptions}
		 */
		const normalizedOptions = clone(options);
		if (options.dialogId)
		{
			normalizedOptions.dialogId = String(options.dialogId);
		}

		if (!Type.isFunction(options.onClose))
		{
			normalizedOptions.onClose = () => {};
		}

		if (Type.isUndefined(options.actionsAfterOpen))
		{
			normalizedOptions.actionsAfterOpen = [];
		}

		if (!Type.isBoolean(options.makeTabActive))
		{
			normalizedOptions.makeTabActive = true;
		}

		return normalizedOptions;
	}

	module.exports = { normalizeOpenDialogOptions };
});
