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

	class NavigationSelector
	{
		/**
		 *
		 * @param {Array} userList
		 * @param {DialogDTO} DialogDTO
		 * @param parentLayout
		 */
		static open({ userList}, parentLayout = null)
		{
			const widget = new NavigationSelector(userList, parentLayout);
			widget.show();
		}

		constructor(userList, parentLayout)
		{
			this.userList = userList || [];
			this.layout = parentLayout || null;

			this.view = new NavigationSelectorView({
				userList: userList,
				onClose: () => {
					this.layout.close();
				},
				onItemSelected: (itemData) => {
					MessengerEmitter.emit(EventType.messenger.openDialog, itemData);
					this.layout.close();
				},
				onCreateOpenChat: () => {
					RecipientSelector.open({
							dialogDTO: new DialogDTO().setType('OPEN'),
							userList: ChatUtils.objectClone(this.userList),
						},
						this.layout,
					);
				},
				onCreatePrivateChat: () => {
					RecipientSelector.open({
							dialogDTO: (new DialogDTO()).setType('CHAT'),
							userList: ChatUtils.objectClone(this.userList),
						},
						this.layout,
					);
				},
				onClickInviteButton: () => {
					IntranetInvite.openRegisterSlider({
						originator: 'im.chat.create',
						registerUrl: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_URL', ''),
						rootStructureSectionId: BX.componentParameters.get('INTRANET_INVITATION_ROOT_STRUCTURE_SECTION_ID', 0),
						adminConfirm: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM', false),
						disableAdminConfirm: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_ADMIN_CONFIRM_DISABLE', false),
						sharingMessage: BX.componentParameters.get('INTRANET_INVITATION_REGISTER_SHARING_MESSAGE', ''),
						parentLayout: this.layout,
					});
				}
			});
		}

		show()
		{
			const config = {
				title: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_CREATE_TITLE'),
				useLargeTitleMode: true,
				modal: true,
				backdrop: {
					mediumPositionPercent: 85,
					horizontalSwipeAllowed: false,
					onlyMediumPosition: true,
				},
				onReady: layoutWidget =>
				{
					this.layout = layoutWidget
					layoutWidget.showComponent(this.view);
				},
			};

			if (this.layout !== null)
			{
				this.layout.openWidget(
					'layout',
					config,
				).then(layoutWidget => {
					this.configureWidget(layoutWidget);
				});

				return;
			}

			PageManager.openWidget(
				'layout',
				config,
			).then(layoutWidget => {
				this.configureWidget(layoutWidget);
			});
		}

		configureWidget(layoutWidget)
		{
			layoutWidget.setTitle({
				useLargeTitleMode: true
			});
			layoutWidget.search.mode = 'bar';
			layoutWidget.setRightButtons([
				{
					type: "search",
					id: "search",
					name: 'search',
					callback: () => {
						layoutWidget.search.show();
						this.view.searchShow();
					},
				},
			]);
			layoutWidget.set
			layoutWidget.search.on('cancel', () => {
				this.view.searchClose();
			});
			layoutWidget.search.on('textChanged', text => {
				this.view.search(text.text);
			});
		}
	}



	module.exports = { NavigationSelector };
});