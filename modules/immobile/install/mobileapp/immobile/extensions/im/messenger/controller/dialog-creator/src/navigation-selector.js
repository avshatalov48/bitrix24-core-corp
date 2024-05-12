/**
 * @module im/messenger/controller/dialog-creator/navigation-selector
 */
jn.define('im/messenger/controller/dialog-creator/navigation-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { EventType } = require('im/messenger/const');
	const { RecipientSelector } = require('im/messenger/controller/dialog-creator/recipient-selector');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { NavigationSelectorView } = require('im/messenger/controller/dialog-creator/navigation-selector/view');
	const { DialogDTO } = require('im/messenger/controller/dialog-creator/dialog-dto');
	const AppTheme = require('apptheme');
	const { openIntranetInviteWidget } = require('intranet/invite-opener');
	const { AnalyticsEvent } = require('analytics');

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
				onCreateOpenChat: () => {
					RecipientSelector.open(
						{
							dialogDTO: new DialogDTO().setType('OPEN'),
							userList: ChatUtils.objectClone(this.userList),
						},
						this.layout,
					);
				},
				onCreatePrivateChat: () => {
					RecipientSelector.open(
						{
							dialogDTO: (new DialogDTO()).setType('CHAT'),
							userList: ChatUtils.objectClone(this.userList),
						},
						this.layout,
					);
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
				backgroundColor: AppTheme.colors.bgNavigation,
				backdrop: {
					mediumPositionPercent: 85,
					horizontalSwipeAllowed: false,
					onlyMediumPosition: true,
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
				useLargeTitleMode: true,
			});
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
	}

	module.exports = { NavigationSelector };
});
