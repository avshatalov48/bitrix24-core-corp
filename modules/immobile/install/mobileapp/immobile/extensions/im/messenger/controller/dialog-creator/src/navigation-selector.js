/**
 * @module im/messenger/controller/dialog-creator/navigation-selector
 */
jn.define('im/messenger/controller/dialog-creator/navigation-selector', (require, exports, module) => {
	/* global ChatUtils */
	const { Loc } = require('loc');

	const { EventType, Analytics } = require('im/messenger/const');
	const { RecipientSelector } = require('im/messenger/controller/dialog-creator/recipient-selector');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { NavigationSelectorView } = require('im/messenger/controller/dialog-creator/navigation-selector/view');
	const { DialogDTO } = require('im/messenger/controller/dialog-creator/dialog-dto');
	const { ChannelCreator } = require('im/messenger/controller/channel-creator');
	const { CreateChannel, CreateGroupChat } = require('im/messenger/controller/chat-composer');
	const { Feature } = require('im/messenger/lib/feature');
	const { Theme } = require('im/lib/theme');
	const { openIntranetInviteWidget } = require('intranet/invite-opener-new');
	const { AnalyticsEvent } = require('analytics');
	const { AnalyticsService } = require('im/messenger/provider/service');

	class NavigationSelector
	{
		/**
		 *
		 * @param {Array} userList
		 * @param {DialogDTO} DialogDTO
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

					if (Feature.isChatComposerSupported)
					{
						const createChannel = new CreateChannel();
						createChannel.open({}, this.layout);

						return;
					}

					void ChannelCreator.open({
						userList: ChatUtils.objectClone(this.userList),
					}, this.layout);
				},
				onCreatePrivateChat: () => {
					this.sendAnalyticsStartCreate(Analytics.Category.chat, Analytics.Type.chat);

					if (Feature.isChatComposerSupported)
					{
						const createGroupChat = new CreateGroupChat();
						createGroupChat.open({}, this.layout).catch((error) => {
							console.error(error);
						});

						return;
					}

					RecipientSelector.open(
						{
							dialogDTO: (new DialogDTO()).setType('CHAT'),
							userList: ChatUtils.objectClone(this.userList),
						},
						this.layout,
					);
				},
				onCreateCollab: async () => {
					if (!Feature.isCollabSupported)
					{
						Feature.showUnsupportedWidget({}, this.layout);

						return;
					}

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
				onClickInviteButton: () => {
					openIntranetInviteWidget({
						analytics: new AnalyticsEvent().setSection('chat'),
						parentLayout: this.layout,
					});
				},
			});
		}

		show()
		{
			const config = {
				title: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_CREATE_TITLE'),
				useLargeTitleMode: true,
				modal: true,
				backgroundColor: Theme.isDesignSystemSupported ? Theme.colors.bgContentPrimary : Theme.colors.bgNavigation,
				backdrop: {
					mediumPositionPercent: 85,
					horizontalSwipeAllowed: false,
					// onlyMediumPosition: true,
				},
				onReady: (layoutWidget) => {
					this.layout = layoutWidget;
					layoutWidget.showComponent(this.view);
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
			layoutWidget.search.mode = 'bar';
			layoutWidget.setRightButtons([
				{
					type: 'search',
					id: 'search',
					name: 'search',
					callback: () => {
						layoutWidget.search.show();
						this.view.searchShow();
					},
				},
			]);
			layoutWidget.search.on('cancel', () => {
				this.view.searchClose();
			});
			layoutWidget.search.on('textChanged', (text) => {
				clearTimeout(this.searchTimeout);
				this.searchTimeout = setTimeout(() => {
					this.view.search(text.text);
				}, 200);
			});
			layoutWidget.enableNavigationBarBorder(false);
		}

		sendAnalyticsStartCreate(category, type)
		{
			AnalyticsService.getInstance()
				.sendStartCreation({ category, type })
			;
		}
	}

	module.exports = { NavigationSelector };
});
