/**
 * @module im/messenger/controller/dialog/lib/entity-manager
 */

jn.define('im/messenger/controller/dialog/lib/entity-manager', (require, exports, module) => {
	const { isModuleInstalled } = require('module');

	const { MessengerParams } = require('im/messenger/lib/params');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('dialog--entity-manager');
	const { Uuid } = require('utils/uuid');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const {
		RestMethod,
		EventType,
	} = require('im/messenger/const');
	const { AnalyticsService } = require('im/messenger/provider/service/analytics');

	/**
	 * @class EntityManager
	 */
	class EntityManager
	{
		#onCalendarEntrySaveHandler;

		/**
		 * @param {DialogId} dialogId
		 * @param {MessengerCoreStore} store
		 */
		constructor(dialogId, store)
		{
			this.dialogId = dialogId;
			this.store = store;
		}

		/**
		 * @param {number} messageId
		 * @return {Promise<PrepareTaskData>}
		 */
		#getCreateTaskData(messageId)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(RestMethod.imChatTaskPrepare, { MESSAGE_ID: messageId })
					.then((result) => {
						logger.log(`${this.constructor.name}.getCreateTaskData result`, result.data());
						resolve(result.data());
					})
					.catch((result) => {
						logger.error(`${this.constructor.name}.getCreateTaskData catch:`, result.error());
						reject(result.error());
					});
			});
		}

		async #onCreateTask(params)
		{
			logger.log(`${this.constructor.name}.onClickActionTask`);

			try
			{
				const { openTaskCreateForm } = await requireLazy('tasks:layout/task/create/opener');
				openTaskCreateForm(params);

				AnalyticsService.getInstance().sendOpenCreateTask(this.dialogId);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.createTask catch`, error);
			}
		}

		createTask()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!isModuleInstalled('tasks') || !dialog)
			{
				return;
			}

			const openTaskCreateFormOptions = {
				initialTaskData: {
					title: '',
					description: '',
					IM_CHAT_ID: dialog.chatId,
				},
				closeAfterSave: true,
				analyticsLabel: {
					c_section: 'chat',
					c_element: 'create_button',
				},
			};

			const collabId = this.store.getters['dialoguesModel/collabModel/getByDialogId'](this.dialogId)?.collabId;
			if (collabId > 0)
			{
				openTaskCreateFormOptions.initialTaskData.group = {
					id: collabId,
					name: dialog.name,
				};
			}

			void this.#onCreateTask(openTaskCreateFormOptions);
		}

		/**
		 * @param {MessagesModelState} messageData
		 * @return {Promise}
		 */
		async createTaskFomMessage(messageData)
		{
			const taskData = await this.#getCreateTaskData(messageData.id);

			if (!taskData.params || !isModuleInstalled('tasks'))
			{
				return;
			}

			let auditors = [];
			try
			{
				if (taskData.params?.AUDITORS)
				{
					const auditorsIds = taskData.params.AUDITORS.split(',');
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
				logger.error(`${this.constructor.name}.onClickActionTask get auditors error find ${error}`);
			}

			const files = taskData.params?.UF_TASK_WEBDAV_FILES_DATA || [];
			const openTaskCreateFormOptions = {
				initialTaskData: {
					title: messageData.text,
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
			};

			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const collabInfo = this.store.getters['dialoguesModel/collabModel/getByDialogId'](this.dialogId);
			const isCollab = DialogHelper.createByDialogId(this.dialogId)?.isCollab;

			if (isCollab && collabInfo && collabInfo.collabId > 0)
			{
				openTaskCreateFormOptions.initialTaskData.group = {
					id: collabInfo.collabId,
					name: dialog.name,
				};
			}

			await this.#onCreateTask(openTaskCreateFormOptions);
		}

		/**
		 * @param {?number} messageId
		 */
		async createMeeting(messageId)
		{
			if (!isModuleInstalled('calendar'))
			{
				return;
			}

			try
			{
				const { Entry } = await requireLazy('calendar:entry');
				if (!Entry)
				{
					return;
				}

				let ownerId = MessengerParams.getUserId();
				let calType = 'user';

				const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
				const collabInfo = this.store.getters['dialoguesModel/collabModel/getByDialogId'](this.dialogId);
				const collabId = collabInfo?.collabId;

				if (collabId > 0)
				{
					ownerId = collabId;
					calType = 'group';
				}

				if (dialog?.chatId > 0)
				{
					const uuid = Uuid.getV4();
					this.#onCalendarEntrySaveHandler = (event) => this.#onCalendarEntrySave(event, dialog.chatId, uuid, messageId);
					BX.addCustomEvent(EventType.calendar.addMeeting, this.#onCalendarEntrySaveHandler);

					void Entry.openEventEditForm({
						ownerId,
						calType,
						createChatId: dialog.chatId,
						uuid,
					});

					AnalyticsService.getInstance().sendOpenCreateMeeting(this.dialogId);
				}
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.createMeeting catch`, error);
			}
		}

		/**
		 * @param eventData
		 * @param {number} eventData.eventId
		 * @param {number} eventData.createChatId
		 * @param {string} eventData.uuid
		 * @param {string} uuid
		 * @param {number} chatId
		 * @param {number} messageId
		 */
		#onCalendarEntrySave(eventData, chatId, uuid, messageId)
		{
			const isEqualChatId = chatId === eventData.createChatId;
			const isEqualUuid = uuid === eventData.uuid;

			if (!isEqualChatId || !isEqualUuid)
			{
				return;
			}

			this.#unsubscribeCalendarEvent();
			const queryParams = {
				CALENDAR_ID: eventData.eventId,
				CHAT_ID: chatId,
			};

			if (messageId)
			{
				queryParams.MESSAGE_ID = messageId;
			}

			BX.rest.callMethod(RestMethod.imChatCalendarAdd, queryParams)
				.catch((error) => {
					logger.error(`${this.constructor.name}.onCalendarEntrySave error`, error);
				});
		}

		#unsubscribeCalendarEvent()
		{
			BX.removeCustomEvent(EventType.calendar.addMeeting, this.#onCalendarEntrySaveHandler.bind());
		}
	}

	module.exports = {
		EntityManager,
	};
});
