/**
 * @module im/messenger/controller/channel-creator/creator
 */
jn.define('im/messenger/controller/channel-creator/creator', (require, exports, module) => {
	const { Type } = require('type');

	const {
		EventType,
		RestMethod,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { runAction } = require('im/messenger/lib/rest');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { AddSubscribersStep } = require('im/messenger/controller/channel-creator/step/add-subscribers');
	const { EnterNameStep } = require('im/messenger/controller/channel-creator/step/enter-name');
	const { SettingsStep } = require('im/messenger/controller/channel-creator/step/settings');

	class ChannelCreator
	{
		static async open(options = {}, parentWidget = PageManager)
		{
			const creator = new this(options, parentWidget);
			await creator.#openFirstStep();

			return creator;
		}

		constructor(options, parentWidget)
		{
			this.options = options;
			this.parentWidget = parentWidget;

			this.channelCreateOptions = {
				title: null,
				description: null,
				type: null,
				avatar: null,
				avatarPreview: null,
				recipientList: null,
			};

			this.enterNameStepWidget = null;
			this.settingStepWidget = null;
			this.addSubscribersStepWidget = null;
			this.#bindMethods();
		}

		#bindMethods()
		{
			this.onAddSubscribersStepComplete = this.onAddSubscribersStepComplete.bind(this);
			this.onEnterNameStepComplete = this.onEnterNameStepComplete.bind(this);
			this.onSettingsStepComplete = this.onSettingsStepComplete.bind(this);
		}

		async #openFirstStep()
		{
			this.enterNameStepWidget = await EnterNameStep.open({
				goToNextStep: this.onEnterNameStepComplete,
			}, this.parentWidget);
		}

		/**
		 * @private
		 * @param {EnterNameCompleteResult} result
		 * @return {Promise<void>}
		 */
		async onEnterNameStepComplete(result)
		{
			this.channelCreateOptions.title = result.title;
			this.channelCreateOptions.description = result.description;
			this.channelCreateOptions.avatar = result.avatarBase64;
			this.channelCreateOptions.avatarPreview = result.previewAvatarPath;

			this.settingStepWidget = await SettingsStep.open({
				goToNextStep: this.onSettingsStepComplete,
			}, this.enterNameStepWidget);
		}

		/**
		 * @private
		 * @param {SettingsCompleteResult} settingsCompleteResult
		 * @return {Promise<void>}
		 */
		async onSettingsStepComplete(settingsCompleteResult)
		{
			this.channelCreateOptions.type = settingsCompleteResult.mode;

			this.addSubscribersStepWidget = await AddSubscribersStep.open({
				userList: this.options.userList,
				goToNextStep: this.onAddSubscribersStepComplete,
			}, this.settingStepWidget);
		}

		/**
		 * @private
		 * @param addSubscribersCompleteResult
		 * @return {Promise<void>}
		 */
		async onAddSubscribersStepComplete(addSubscribersCompleteResult)
		{
			this.channelCreateOptions.recipientList = addSubscribersCompleteResult.recipientList;

			this.#createChannel();
		}

		async #createChannel()
		{
			const users = [];
			if (Type.isArrayFilled(this.channelCreateOptions.recipientList))
			{
				this.channelCreateOptions.recipientList.forEach((recipient) => {
					users.push(Number(recipient.id));
				});
			}

			if (!users.includes(serviceLocator.get('core').getUserId()))
			{
				users.push(serviceLocator.get('core').getUserId());
			}

			const config = {
				type: 'CHANNEL',
				title: this.channelCreateOptions.title ?? '',
				description: this.channelCreateOptions.description ?? '',
				ownerId: serviceLocator.get('core').getUserId(),
				searchable: this.channelCreateOptions.type === 'open' ? 'Y' : 'N',
			};
			if (users.length > 0)
			{
				config.users = users;
			}

			if (this.channelCreateOptions.avatar)
			{
				config.avatar = this.channelCreateOptions.avatar;
			}

			runAction(RestMethod.imV2ChatAdd, {
				data: {
					fields: config,
				},
			})
				.then((result) => {
					const chatId = result.chatId;

					this.#openChannel(chatId);
				})
				.catch((error) => {
					console.error(error);
				});
		}

		#openChannel(chatId)
		{
			this.addSubscribersStepWidget.close();

			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: `chat${chatId}`,
				context: OpenDialogContextType.chatCreation,
			});
		}
	}

	module.exports = {
		ChannelCreator,
	};
});
