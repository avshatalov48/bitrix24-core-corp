/**
 * @module im/messenger/controller/dialog-creator/navigation-selector/view
 */
jn.define('im/messenger/controller/dialog-creator/navigation-selector/view', (require, exports, module) => {

	const { MessengerParams } = require('im/messenger/lib/params');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');
	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { UserSearchController } = require('im/messenger/controller/search');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { navigationButton } = require('im/messenger/controller/dialog-creator/navigation-button');

	const privateChatIcon = `<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20 0C31.0457 0 40 8.95431 40 20C40 31.0457 31.0457 40 20 40C8.95431 40 0 31.0457 0 20C0 8.95431 8.95431 0 20 0Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M11.999 14.0774C11.999 13.3707 12.5719 12.7979 13.2786 12.7979H22.6248C23.3315 12.7979 23.9043 13.3707 23.9043 14.0774V20.4473C23.9043 21.154 23.3315 21.7268 22.6248 21.7268H17.9518L15.385 24.2936C15.2339 24.4448 14.9755 24.3377 14.9755 24.124V21.7268H13.2786C12.5719 21.7268 11.999 21.154 11.999 20.4473V14.0774Z" fill="white"/><path d="M17.9517 23.7111V24.4157C17.9517 25.1224 18.5246 25.6953 19.2312 25.6953H22.7707L24.4746 27.6831C24.6196 27.8522 24.8967 27.7497 24.8967 27.527V25.6953H26.5933C27.2999 25.6953 27.8728 25.1224 27.8728 24.4157V18.0458C27.8728 17.3392 27.2999 16.7663 26.5933 16.7663H25.8889V22.0317C25.8889 22.9592 25.137 23.7111 24.2095 23.7111H17.9517Z" fill="white"/></svg>`;
	const openChatIcon = `<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20 0C31.0457 0 40 8.95431 40 20C40 31.0457 31.0457 40 20 40C8.95431 40 0 31.0457 0 20C0 8.95431 8.95431 0 20 0Z" fill="${AppTheme.colors.accentMainSuccess}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M14.3167 16.8816C14.3188 16.881 14.3209 16.8804 14.3231 16.88L27.2758 14.141C27.6489 14.0621 27.9999 14.3467 27.9999 14.728V24.117C27.9999 24.5107 27.6272 24.7977 27.2466 24.6971L22.2779 23.3838V24.974C22.2779 25.5263 21.8302 25.974 21.2779 25.974H16.6038C16.0515 25.974 15.6038 25.5263 15.6038 24.974V21.6196L14.3182 21.2798L14.3128 21.2782C14.2856 21.2985 14.2546 21.3144 14.2207 21.3246L12.4192 21.8702C12.2265 21.9285 12.0322 21.7843 12.0322 21.583V16.5738C12.0322 16.3726 12.2265 16.2284 12.4192 16.2867L14.2207 16.8322C14.2561 16.843 14.2885 16.8599 14.3167 16.8816ZM20.963 23.0362L16.9188 21.9672V24.4991C16.9188 24.6647 17.0531 24.7991 17.2188 24.7991H20.663C20.8286 24.7991 20.963 24.6647 20.963 24.4991V23.0362Z" fill="white"/></svg>`;
	const joinEmployee = `<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20 39.9982C31.0457 39.9982 40 31.0444 40 19.9991C40 8.9539 31.0457 0 20 0C8.9543 0 0 8.9539 0 19.9991C0 31.0444 8.9543 39.9982 20 39.9982Z" fill="${AppTheme.colors.accentSoftBlue2}"/><path d="M18.6362 13.1818H21.3635V26.8182H18.6362V13.1818Z" fill="${AppTheme.colors.accentMainPrimaryalt}"/><path d="M26.818 18.6363V21.3636L13.1816 21.3636L13.1816 18.6363L26.818 18.6363Z" fill="${AppTheme.colors.accentMainPrimaryalt}"/></svg>`;
	class NavigationSelectorView extends LayoutComponent
	{

		/**
		 *
		 * @param { Object } props
		 * @param { Array } props.userList
		 * @param { Function } props.onClose
		 * @param { Function } props.onItemSelected
		 * @param { Function } props.onCreateOpenChat
		 * @param { Function } props.onCreatePrivateChat
		 * @param { Function } props.onClickInviteButton
		 */
		constructor(props)
		{
			super(props);

			/** @type SingleSelector */
			this.selectorRef = null;
			/** @type UserSearchController */
			this.searchController = null;
		}


		render()
		{
			return new SingleSelector({
				recentText: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_RECENT_TEXT'),
				itemList: this.props.userList,
				buttons: this.getButtons(),
				searchMode: 'overlay',
				onItemSelected: (itemData) => {
					this.props.onItemSelected(itemData);
				},
				onSearchItemSelected: (itemData) => {
					this.props.onItemSelected(itemData);
				},
				ref: ref => {
					this.selectorRef = ref;
					if (typeof ref !== 'undefined')
					{
						this.searchController = new UserSearchController(this.selectorRef);
					}
				},
			});
		}

		search(query)
		{
			if (query === '')
			{
				this.selectorRef.showMainContent(true);
				return;
			}
			this.searchController.setSearchText(query);
		}

		searchShow()
		{
			this.selectorRef.enableShadow();
			this.searchController.open();
		}

		searchClose()
		{
			this.selectorRef.showMainContent();
			this.selectorRef.disableShadow();
		}

		getButtons()
		{
			return [
				this.getNewChatButton(),
				this.getNewChannelButton(),
				this.getInviteButton(),
			];
		}

		getNewChannelButton()
		{
			return navigationButton({
				testId: 'create_channel',
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_CHANNEL'),
				iconSvg: openChatIcon,
				onClick: () => {
					this.props.onCreateOpenChat();
				},
				withSeparator: true,
			});
		}

		getNewChatButton()
		{
			return navigationButton({
				testId: 'create_group_chat',
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_CHAT'),
				iconSvg: privateChatIcon,
				onClick: () => {
					this.props.onCreatePrivateChat();
				},
				withSeparator: true,
			});
		}
		getInviteButton()
		{
			if (MessengerParams.get('INTRANET_INVITATION_CAN_INVITE', false))
			{
				return navigationButton({
					text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_INVITE_EMPLOYEES'),
					onClick: () => {
						this.props.onClickInviteButton();
					},
					iconSvg: joinEmployee,
					withSeparator: false,
				});
			}
		}
	}

	module.exports = { NavigationSelectorView };

});