/**
 * @module im/messenger/controller/dialog/lib/message-create-menu
 */
jn.define('im/messenger/controller/dialog/lib/message-create-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { Icon } = require('assets/icons');

	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const/rest');

	let openTaskCreateForm = null;
	try
	{
		openTaskCreateForm = require('tasks/layout/task/create/opener')?.openTaskCreateForm;
	}
	catch (error)
	{
		console.warn('Cannot get openTaskCreateForm', error);
	}

	/**
	 * @class MessageAvatarMenu
	 */
	class MessageCreateMenu
	{
		/**
		 * @param {MessagesModelState} messageData
		 * @param {DialogLocator} serviceLocator
		 */
		constructor(messageData, serviceLocator) {
			this.actionsName = this.getActionNames();
			this.actionsData = [];
			this.messageData = messageData;
			this.serviceLocator = serviceLocator;
		}

		/**
		 * @param {MessagesModelState} messageData
		 * @param {DialogLocator} serviceLocator
		 */
		static open(messageData, serviceLocator)
		{
			const instanceClass = new MessageCreateMenu(messageData, serviceLocator);
			instanceClass.show();
		}

		static hasActions()
		{
			const instanceClass = new MessageCreateMenu();

			return instanceClass.getActionNames().length > 0;
		}

		getActionNames()
		{
			const actionsName = [];

			if (openTaskCreateForm)
			{
				actionsName.push('task');
			}

			return actionsName;
		}

		show()
		{
			this.setCloseMenuPromise();
			this.createMenu();
			this.menu.show().catch((err) => Logger.error('MessageCreateMenu.open.catch:', err));
		}

		createMenu()
		{
			this.prepareActionsData();
			this.menu = new ContextMenu({
				actions: this.actionsData,
				params: {
					title: Loc.getMessage('IMMOBILE_DIALOG_MESSAGE_CREATE_MENU_TITLE'),
					showActionLoader: true,
				},
				onClose: () => this.resolveClosePromise(),
			});
		}

		prepareActionsData()
		{
			this.actionsName.forEach((actionName) => {
				this.actionsData.push({
					id: actionName,
					title: Loc.getMessage(`IMMOBILE_DIALOG_MESSAGE_CREATE_MENU_${actionName.toUpperCase()}`),
					icon: Icon.TASK,
					onClickCallback: this.getCallbackByAction(actionName),
					testId: `IMMOBILE_DIALOG_MESSAGE_CREATE_MENU_${actionName.toUpperCase()}`,
				});
			});
		}

		/**
		 * @param {string} actionName
		 * @return {Function}
		 */
		getCallbackByAction(actionName)
		{
			const method = Object.getOwnPropertyNames(Object.getPrototypeOf(this))
				.filter((prop) => prop.toLowerCase().includes(actionName));

			if (method.length === 0 || method.length > 2)
			{
				return () => Logger.error(`${this.constructor.name}.getCallbackByAction error find method`, method);
			}

			return this[method].bind(this);
		}

		setCloseMenuPromise()
		{
			this.closePromise = new Promise((resolve) => {
				this.resolveClosePromise = resolve;
			});
		}

		async onClickActionTask()
		{
			Logger.log(`${this.constructor.name}.onClickActionTask`);
			const taskData = await this.getPrepareDataFromRest();
			if (!taskData.params || !openTaskCreateForm)
			{
				return;
			}

			let auditors = [];
			try
			{
				if (taskData.params?.AUDITORS)
				{
					const auditorsIds = taskData.params.AUDITORS.split(',');
					this.store = this.serviceLocator.get('store');
					auditors = auditorsIds.map((id) => {
						const user = this.store.getters['usersModel/getById'](id);
						if (user && user.name)
						{
							return Object.create({ id, name: user.name });
						}

						return Object.create({ id, name: '' });
					});
				}
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.onClickActionTask get auditors error find ${error}`);
			}

			const files = taskData.params?.UF_TASK_WEBDAV_FILES_DATA || [];

			this.closePromise.then(() => {
				openTaskCreateForm({
					initialTaskData: {
						title: this.messageData.text,
						description: taskData.params.DESCRIPTION,
						auditors,
						files,
						IM_CHAT_ID: taskData.params.IM_CHAT_ID,
						IM_MESSAGE_ID: taskData.params.IM_MESSAGE_ID,
					},
					closeAfterSave: true,
					analyticsLabel: {
						c_section: 'chat',
						c_element: 'create_button',
					},
				});
			})
				.catch((error) => Logger.log(`${this.constructor.name}.onClickActionTask.closePromise.catch:`, error));
		}

		/**
		 * @return {Promise}
		 */
		getPrepareDataFromRest()
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(RestMethod.imChatTaskPrepare, { MESSAGE_ID: this.messageData.id })
					.then((result) => {
						Logger.log(`${this.constructor.name}.getPrepareDataFromRest result`, result.data());
						resolve(result.data());
					})
					.catch((result) => {
						Logger.error(`${this.constructor.name}.getPrepareDataFromRest catch:`, result.error());
						reject(result.error());
					});
			});
		}
	}

	module.exports = {
		MessageCreateMenu,
	};
});
