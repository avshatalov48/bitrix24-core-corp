/**
 * @module im/messenger/controller/dialog/lib/header/buttons/controller
 */
jn.define('im/messenger/controller/dialog/lib/header/buttons/controller', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { debounce } = require('utils/function');

	const { EventType } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const {
		CancelMultipleSelectButton,
		HeaderButtons,
	} = require('im/messenger/controller/dialog/lib/header/buttons/buttons');

	/**
	 * @class HeaderButtonsController
	 */
	class HeaderButtonsController
	{
		/**
		 * @type {IDialogHeaderButtons|null}
		 */
		#buttonsController = null;

		/**
		 * @param {() => DialoguesModelState} getDialog
		 * @param {DialogLocator} dialogLocator
		 */
		constructor({ getDialog, dialogLocator })
		{
			/**
			 * @protected
			 * @type {() => DialoguesModelState}
			 * */
			this.getDialog = getDialog;

			/**
			 * @protected
			 * @type {DialogLocator}
			 * */
			this.dialogLocator = dialogLocator;

			/** @protected */
			this.renderedButtons = [];

			this.bindMethods();
		}

		/**
		 * @protected
		 * @return {DialogConfigurator}
		 */
		get configurator()
		{
			return this.dialogLocator.get('configurator');
		}

		bindMethods()
		{
			/** @type {function} */
			this.tapHandler = this.#createButtonTapHandler();
		}

		subscribeViewEvents()
		{
			this.dialogLocator.get('view').on(EventType.view.barButtonTap, this.tapHandler);
		}

		/**
		 * @return {Promise<IDialogHeaderButtons>}
		 */
		async getButtonsController()
		{
			if (!this.#buttonsController)
			{
				this.#buttonsController = await this.#initButtonsController();
			}

			return this.#buttonsController;
		}

		/**
		 * @return {Promise<IDialogHeaderButtons>}
		 */
		async #initButtonsController()
		{
			const ButtonsController = await this.configurator.getHeaderButtonsControllerClass();
			const buttonsController = new ButtonsController({
				getDialog: this.getDialog,
				relatedEntity: this.configurator.getRelatedEntity(),
			});

			if (buttonsController instanceof HeaderButtons)
			{
				buttonsController.setDialogLocator(this.dialogLocator);
			}

			return buttonsController;
		}

		/**
		 * @return {Array<DialogHeaderButton>}
		 */
		async getButtons()
		{
			const controller = await this.getButtonsController();

			return controller.getButtons();
		}

		/**
		 * @protected
		 * @param {string} buttonId
		 * @return {Promise}
		 */
		async tapHandler(buttonId)
		{
			const controller = await this.getButtonsController();

			return controller.tapHandler(buttonId);
		}

		/**
		 * @protected
		 * @return {function}
		 */
		#createButtonTapHandler()
		{
			return debounce(this.tapHandler, 300, this, true);
		}

		/**
		 * @return {Promise<Array<DialogHeaderButton>>}
		 */
		async getButtonsByCurrentDialogState()
		{
			if (this.checkSelectMessagesModeEnabled())
			{
				return [this.getCancelMultipleSelectButton()];
			}

			return this.getButtons();
		}

		/**
		 * @return {boolean}
		 */
		checkSelectMessagesModeEnabled()
		{
			return this.dialogLocator.get('select-manager')?.isSelectMessagesModeEnabled() ?? false;
		}

		/**
		 * @param {() => any} callback
		 * @void
		 */
		renderCancelMultipleButton(callback)
		{
			this.#forceRenderButtons([this.getCancelMultipleSelectButton(callback)]);
		}

		/**
		 * @param {() => any} callback
		 * @return {DialogHeaderButton}
		 */
		getCancelMultipleSelectButton(callback)
		{
			return {
				...CancelMultipleSelectButton,
				callback,
			};
		}

		checkShouldRenderButtons(buttons)
		{
			const getButtonWithoutCallback = (button) => {
				const { callback, ...stateWithoutCallback } = button;

				return stateWithoutCallback;
			};

			const prevStateWithoutCallback = this.renderedButtons.map((button) => {
				return getButtonWithoutCallback(button);
			});
			const newStateWithoutCallback = buttons.map((button) => {
				return getButtonWithoutCallback(button);
			});

			return !isEqual(prevStateWithoutCallback, newStateWithoutCallback);
		}

		async render()
		{
			const buttons = await this.getButtonsByCurrentDialogState();
			if (!this.checkShouldRenderButtons(buttons))
			{
				return;
			}

			Logger.info(`${this.constructor.name}.render before:`, this.renderedButtons, ' after: ', buttons);
			this.renderedButtons = buttons;

			this.dialogLocator.get('view').setRightButtons(buttons);
		}

		/**
		 * @param {Array<DialogHeaderButton>} buttons
		 */
		#forceRenderButtons(buttons)
		{
			this.renderedButtons = buttons;
			this.dialogLocator.get('view').setRightButtons(buttons);
		}
	}

	module.exports = {
		HeaderButtonsController,
	};
});
