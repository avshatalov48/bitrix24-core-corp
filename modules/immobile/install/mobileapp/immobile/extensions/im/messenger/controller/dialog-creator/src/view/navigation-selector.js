/**
 * @module im/messenger/controller/dialog-creator/navigation-selector/view
 */
jn.define('im/messenger/controller/dialog-creator/navigation-selector/view', (require, exports, module) => {

	const { MessengerParams } = require('im/messenger/lib/params');
	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { UserSearchController } = require('im/messenger/controller/search');
	const { Loc } = require('loc');
	const { navigationButton } = require('im/messenger/controller/dialog-creator/navigation-button');
	const { Theme } = require('im/lib/theme');

	const privateChatIcon = `<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20 0C31.0457 0 40 8.95431 40 20C40 31.0457 31.0457 40 20 40C8.95431 40 0 31.0457 0 20C0 8.95431 8.95431 0 20 0Z" fill="${Theme.colors.accentBrandBlue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15 12.8633C13.3431 12.8633 12 14.2064 12 15.8633V19.7642C12 20.8732 12.6018 21.8416 13.4966 22.3608V23.7756C13.4966 25.0151 14.9952 25.6359 15.8717 24.7594L17.7228 22.9083C17.7692 22.8619 17.8114 22.8137 17.8496 22.7642H21.8708C23.5277 22.7642 24.8708 21.421 24.8708 19.7642V18.2342H25.1999C26.0836 18.2342 26.8 18.9505 26.8 19.8341V22.451C26.8 23.0646 26.4549 23.5996 25.9425 23.8688L25.3008 24.2061V25.7562C25.3008 25.8157 25.2854 25.846 25.2721 25.8649C25.2549 25.8892 25.2251 25.9155 25.1827 25.933C25.1404 25.9505 25.1007 25.9531 25.0714 25.948C25.0487 25.9441 25.0163 25.9336 24.9742 25.8915L23.1336 24.051H21.036C20.9752 24.051 20.9151 24.0476 20.8561 24.0409C20.8175 24.0424 20.7787 24.0432 20.7398 24.0432H18.7324C19.2378 24.7729 20.0811 25.2509 21.036 25.2509H22.6365L24.1257 26.74C25.0022 27.6165 26.5008 26.9957 26.5008 25.7562V24.931C27.3921 24.4626 28 23.5278 28 22.451V19.8341C28 18.2878 26.7464 17.0342 25.1999 17.0342H24.8708V15.8633C24.8708 14.2064 23.5277 12.8633 21.8708 12.8633H15ZM14.6966 21.6697L14.0988 21.3229C13.5588 21.0096 13.2 20.4281 13.2 19.7642V15.8633C13.2 14.8692 14.0059 14.0633 15 14.0633H21.8708C22.865 14.0633 23.6708 14.8692 23.6708 15.8633V19.7642C23.6708 20.7583 22.865 21.5642 21.8708 21.5642H17.2587L16.8984 22.0325C16.8923 22.0405 16.8844 22.0497 16.8743 22.0598L15.0232 23.9109C14.9811 23.9529 14.9487 23.9634 14.926 23.9674C14.8967 23.9724 14.857 23.9699 14.8147 23.9523C14.7723 23.9348 14.7425 23.9086 14.7254 23.8843C14.712 23.8654 14.6966 23.8351 14.6966 23.7756V21.6697Z" fill="white"/></svg>`;
	const channelIcon = `<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20 0C31.0457 0 40 8.95431 40 20C40 31.0457 31.0457 40 20 40C8.95431 40 0 31.0457 0 20C0 8.95431 8.95431 0 20 0Z" fill="${Theme.colors.accentMainSuccess}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M26.25 13.6511L15.11 17.7811L13.06 17.0111C12.41 16.7611 11.71 17.2511 11.71 17.9511V22.0611C11.71 22.7611 12.41 23.2411 13.06 23.0011L15.18 22.2111L16.16 22.5011V25.3911C16.16 26.0011 16.65 26.4911 17.26 26.4911H21.26C21.87 26.4911 22.36 26.0011 22.36 25.3911V24.3411L26.33 25.5211C27.16 25.7711 28 25.1411 28 24.2711V14.8711C28 13.9611 27.1 13.3411 26.25 13.6511ZM12.91 21.7711V18.2311L14.5 18.8311V21.1711L12.91 21.7711ZM21.16 25.2811H17.36V22.8511L21.16 23.9811V25.2911V25.2811ZM26.8 24.2611C26.8 24.3311 26.74 24.3711 26.67 24.3611L15.7 21.1111V18.8411L26.67 14.7811C26.67 14.7811 26.71 14.7811 26.72 14.7811C26.73 14.7811 26.75 14.7811 26.76 14.8011C26.78 14.8111 26.79 14.8211 26.79 14.8411C26.79 14.8411 26.8 14.8611 26.8 14.8911V24.2811V24.2611Z" fill="white"/></svg>`;
	const collabIcon = channelIcon;
	const joinEmployee = `<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20 39.9982C31.0457 39.9982 40 31.0444 40 19.9991C40 8.9539 31.0457 0 20 0C8.9543 0 0 8.9539 0 19.9991C0 31.0444 8.9543 39.9982 20 39.9982Z" fill="${Theme.colors.accentSoftBlue2}"/><path d="M19.2498 26.819C19.2498 27.2332 19.5855 27.569 19.9998 27.569C20.414 27.569 20.7498 27.2332 20.7498 26.819V20.751H26.8182C27.2324 20.751 27.5682 20.4152 27.5682 20.001C27.5682 19.5868 27.2324 19.251 26.8182 19.251H20.7498V13.1826C20.7498 12.7684 20.414 12.4326 19.9998 12.4326C19.5855 12.4326 19.2498 12.7684 19.2498 13.1826V19.251H13.1818C12.7676 19.251 12.4318 19.5868 12.4318 20.001C12.4318 20.4152 12.7676 20.751 13.1818 20.751H19.2498V26.819Z" fill="${Theme.colors.accentMainPrimaryalt}"/></svg>`;

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
				this.getNewCollabButton(),
				this.getInviteButton(),
			];
		}

		getNewChannelButton()
		{
			return navigationButton({
				testId: 'create_channel',
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_CHANNEL'),
				iconSvg: channelIcon,
				onClick: () => {
					this.props.onCreateChannel();
				},
				withSeparator: true,
			});
		}

		getNewCollabButton()
		{
			if (MessengerParams.isCollabAvailable())
			{
				return navigationButton({
					testId: 'create_collab',
					text: 'create_collab',
					iconSvg: collabIcon,
					onClick: () => {
						this.props.onCreateCollab();
					},
					withSeparator: true,
				});
			}
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