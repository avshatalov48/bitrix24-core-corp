/**
 * @module im/messenger/api/dialog-integration/header/buttons
 */
jn.define('im/messenger/api/dialog-integration/header/buttons', (require, exports, module) => {
	/**
	 * @abstract
	 * @class DialogHeaderButtons
	 * @implements {IDialogHeaderButtons}
	 */
	class DialogHeaderButtons
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
		 * @protected
		 * @return {Array<DialogHeaderButton>}
		 */
		getButtons()
		{
			throw new Error(`${this.constructor.name}: getButtons() must be override in subclass.`);
		}

		/**
		 * @abstract
		 * @protected
		 * @param {string} buttonId
		 * @return void
		 */
		tapHandler(buttonId)
		{
			throw new Error(`${this.constructor.name}: tapHandler() must be override in subclass.`);
		}
	}

	module.exports = {
		DialogHeaderButtons,
	};
});
