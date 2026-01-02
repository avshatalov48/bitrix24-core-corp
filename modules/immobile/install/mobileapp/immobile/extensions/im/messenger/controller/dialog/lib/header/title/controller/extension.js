/**
 * @module im/messenger/controller/dialog/lib/header/title/controller
 */
jn.define('im/messenger/controller/dialog/lib/header/title/controller', (require, exports, module) => {
	const { isEqual } = require('utils/object');

	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class HeaderTitleController
	 */
	class HeaderTitleController
	{
		/**
		 * @type {IDialogHeaderTitle|null}
		 */
		#titleController = null;

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
			this.renderTimerId = null;

			/** @protected */
			this.titleParams = {};
		}

		/**
		 * @protected
		 * @return {DialogConfigurator}
		 */
		get configurator()
		{
			return this.dialogLocator.get('configurator');
		}

		/**
		 * @return {DialogView|null}
		 */
		get view()
		{
			const view = this.dialogLocator.get('view');
			if (view)
			{
				return view;
			}

			return null;
		}

		/**
		 * @return {Promise<IDialogHeaderTitle>}
		 */
		async getTitleController()
		{
			if (!this.#titleController)
			{
				this.#titleController = await this.#initTitleController();
			}

			return this.#titleController;
		}

		/**
		 * @return {Promise<IDialogHeaderTitle>}
		 */
		async #initTitleController()
		{
			const TitleControllerClass = await this.configurator.getHeaderTitleControllerClass();

			return new TitleControllerClass({
				getDialog: this.getDialog,
				relatedEntity: this.configurator.getRelatedEntity(),
			});
		}

		/**
		 * @return {Promise<JNWidgetTitleParams>}
		 */
		async createTitleParams()
		{
			const controller = await this.getTitleController();

			return controller.createTitleParams();
		}

		async renderTitle()
		{
			const titleParams = await this.createTitleParams();
			if (!isEqual(this.titleParams, titleParams))
			{
				Logger.info('Dialog.renderTitle: before: ', this.titleParams, ' after: ', titleParams);

				this.view?.setTitle(titleParams);
				this.titleParams = titleParams;

				return this;
			}
			Logger.info('Dialog.renderTitle: header is up-to-date, redrawing is cancelled.');

			return this;
		}

		startRender()
		{
			void this.renderTitle();

			this.renderTimerId = setInterval(this.renderTitle.bind(this), 60000);

			return this;
		}

		stopRender()
		{
			clearInterval(this.renderTimerId);

			return this;
		}

		/**
		 * @param {JNWidgetTitleParams} params
		 */
		setTitleParams(params)
		{
			this.titleParams = params;
		}
	}

	module.exports = {
		HeaderTitleController,
	};
});
