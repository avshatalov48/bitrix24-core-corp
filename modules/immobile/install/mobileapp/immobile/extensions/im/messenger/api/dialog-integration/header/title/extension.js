/**
 * @module im/messenger/api/dialog-integration/header/title
 */
jn.define('im/messenger/api/dialog-integration/header/title', (require, exports, module) => {
	/**
	 * @abstract
	 * @class DialogHeaderTitle
	 * @implements {IDialogHeaderTitle}
	 */
	class DialogHeaderTitle
	{
		/**
		 * @param {() => DialoguesModelState} getDialog
		 * @param {RelatedEntityData} relatedEntity
		 */
		constructor({ getDialog, relatedEntity })
		{
			/**
			 * @protected
			 * @type {() => DialoguesModelState} */
			this.getDialog = getDialog;

			/**
			 * @protected
			 * @type {RelatedEntityData}
			 * */
			this.relatedEntity = relatedEntity;
		}

		/**
		 * @abstract
		 * @return JNWidgetTitleParams
		 */
		createTitleParams()
		{
			throw new Error(`${this.constructor.name}: createTitleParams() must be override in subclass.`);
		}
	}

	module.exports = {
		DialogHeaderTitle,
	};
});
