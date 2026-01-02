/**
 * @module im/messenger/controller/dialog-creator/navigation-selector
 */
jn.define('im/messenger/controller/dialog-creator/navigation-selector', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { AnalyticsEvent } = require('analytics');

	const { Theme } = require('im/lib/theme');
	const { CopilotRoleSelector } = require('layout/ui/copilot-role-selector');

	const {
		EventType,
		Analytics,
		DialogType,
		OpenDialogContextType,
		ComponentCode,
	} = require('im/messenger/const');
	const { NavigationSelectorView } = require('im/messenger/controller/dialog-creator/navigation-selector/view');
	const { CreateChannel, CreateGroupChat } = require('im/messenger/controller/chat-composer');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { ChatService } = require('im/messenger/provider/services/chat');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { isModuleInstalled } = require('module');

	class NavigationSelector
	{
		/**
		 *
		 * @param {Array} userList
		 * @param parentLayout
		 */
		static open({ userList }, parentLayout = null)
		{
			const widget = new NavigationSelector(userList, parentLayout);
			widget.show();
		}

		constructor(userList, parentLayout)
		{
			this.userList = userList || [];
			this.layout = parentLayout || null;

			this.view = new NavigationSelectorView({
				userList,
				onClose: () => {
					this.layout.close();
				},
				onItemSelected: (itemData) => {
					MessengerEmitter.emit(EventType.messenger.openDialog, itemData, 'im.messenger');
					this.layout.close();
				},
				onCreateChannel: () => {
					this.sendAnalyticsStartCreate(Analytics.Category.channel, Analytics.Type.channel);

					const createChannel = new CreateChannel();
					createChannel.open({}, this.layout);
				},
				onCreatePrivateChat: () => {
					this.sendAnalyticsStartCreate(Analytics.Category.chat, Analytics.Type.chat);

					const createGroupChat = new CreateGroupChat();
					createGroupChat.open({}, this.layout).catch((error) => {
						console.error(error);
					});
				},
				onCreateCollab: async () => {
					try
					{
						const { openCollabCreate } = await requireLazy('collab/create');

						this.sendAnalyticsStartCreate(Analytics.Category.collab, Analytics.Type.collab);
						await openCollabCreate({
							// todo provide some analytics here
						}, this.layout);
					}
					catch (error)
					{
						console.error(error);
					}
				},
				onCreateCopilot: async () => {
					try
					{
						this.sendAnalyticsStartCreate(
							Analytics.Category.copilot,
							Analytics.Type.copilot,
							Analytics.Section.chatTab,
						);

						const selectedRole = await CopilotRoleSelector.open({
							parentLayout: this.layout,
							showOpenFeedbackItem: true,
						});

						const fields = {
							type: DialogType.copilot.toUpperCase(),
							copilotMainRole: selectedRole?.role?.code,
						};

						const chatService = new ChatService();
						const newChatWithCopilot = await chatService.createCopilot(fields);
						const chatId = newChatWithCopilot.chatId;

						MessengerEmitter.emit(
							EventType.messenger.openDialog,
							{
								dialogId: `chat${chatId}`,
								context: OpenDialogContextType.chatCreation,
								chatType: DialogType.copilot,
							},
							ComponentCode.imMessenger,
						);
					}
					catch (error)
					{
						console.error(error);
					}
				},
				onClickInviteButton: async () => {
					if (isModuleInstalled('intranet'))
					{
						const { openIntranetInviteWidget } = require('intranet/invite-opener-new');
						openIntranetInviteWidget?.({
							analytics: new AnalyticsEvent().setSection('chat'),
							parentLayout: this.layout,
						});
					}
				},
			});
		}

		show()
		{
			const config = {
				title: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_CREATE_TITLE'),
				useLargeTitleMode: true,
				modal: true,
				backgroundColor: Theme.colors.bgContentPrimary,
				backdrop: {
					mediumPositionPercent: 85,
					horizontalSwipeAllowed: false,
					// onlyMediumPosition: true,
				},
				onReady: (layoutWidget) => {
					this.layout = layoutWidget;
					layoutWidget.showComponent(this.view);
					AnalyticsService.getInstance().sendOpenDialogCreator();
				},
			};

			if (this.layout !== null)
			{
				this.layout.openWidget(
					'layout',
					config,
				).then((layoutWidget) => {
					this.configureWidget(layoutWidget);
				});

				return;
			}

			PageManager.openWidget(
				'layout',
				config,
			).then((layoutWidget) => {
				this.configureWidget(layoutWidget);
			});
		}

		configureWidget(layoutWidget)
		{
			layoutWidget.setTitle({
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_CREATE_TITLE'),
				useLargeTitleMode: true,
			}, true);
			layoutWidget.enableNavigationBarBorder(false);
		}

		sendAnalyticsStartCreate(category, type, section)
		{
			AnalyticsService.getInstance()
				.sendStartCreation({ category, type, section })
			;
		}
	}

	module.exports = { NavigationSelector };
});
