/**
 * @module im/messenger/controller/dialog-creator/navigation-selector/view
 */
jn.define('im/messenger/controller/dialog-creator/navigation-selector/view', (require, exports, module) => {
	const { Loc } = require('loc');

	const { MessengerParams } = require('im/messenger/lib/params');
	const { Feature } = require('im/messenger/lib/feature');
	const {
		ActionByUserType,
	} = require('im/messenger/const');
	const { UserPermission } = require('im/messenger/lib/permission-manager');

	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { UserSearchController } = require('im/messenger/controller/search');
	const { navigationButton } = require('im/messenger/controller/dialog-creator/navigation-button');
	const { buttonIcons } = require('im/messenger/assets/common');

	const privateChatIcon = '<svg width="40" height="41" viewBox="0 0 40 41" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 8.5C0 4.08172 3.58172 0.5 8 0.5H32C36.4183 0.5 40 4.08172 40 8.5V32.5C40 36.9183 36.4183 40.5 32 40.5H8C3.58172 40.5 0 36.9183 0 32.5V8.5Z" fill="#0075FF" fill-opacity="0.78"/><path d="M0.5 8.5C0.5 4.35786 3.85786 1 8 1H32C36.1421 1 39.5 4.35786 39.5 8.5V32.5C39.5 36.6421 36.1421 40 32 40H8C3.85786 40 0.5 36.6421 0.5 32.5V8.5Z" stroke="white" stroke-opacity="0.18"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.52344 13.9761C9.52344 12.3192 10.8666 10.9761 12.5234 10.9761H22.2622C23.9191 10.9761 25.2622 12.3192 25.2622 13.9761V15.5296H18.7229C16.5137 15.5296 14.7229 17.3205 14.7229 19.5296V25.5987L13.8047 26.5169C12.9282 27.3933 11.4296 26.7726 11.4296 25.5331V23.3802C10.3137 22.943 9.52344 21.8567 9.52344 20.5858V13.9761Z" fill="white" fill-opacity="0.7"/><g filter="url(#filter0_d_4066_31599)"><path fill-rule="evenodd" clip-rule="evenodd" d="M27.6766 16.6904C29.223 16.6904 30.4766 17.944 30.4766 19.4904V25.2424C30.4766 26.3927 29.783 27.381 28.7912 27.8118V29.4931C28.7912 30.7196 27.3193 31.3461 26.4353 30.4958L23.8849 28.0424H18.9908C17.4445 28.0424 16.1908 26.7888 16.1908 25.2424V19.4904C16.1908 17.944 17.4445 16.6904 18.9908 16.6904H27.6766Z" fill="white" fill-opacity="0.9" shape-rendering="crispEdges"/></g><defs><filter id="filter0_d_4066_31599" x="12.1904" y="16.6904" width="22.2861" height="22.1968" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.08 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4066_31599"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4066_31599" result="shape"/></filter></defs></svg>';
	const channelIcon = '<svg width="40" height="41" viewBox="0 0 40 41" fill="none" xmlns="http://www.w3.org/2000/svg"><rect y="0.5" width="40" height="40" rx="8" fill="#0075FF" fill-opacity="0.78"/><rect x="0.5" y="1" width="39" height="39" rx="7.5" stroke="white" stroke-opacity="0.18"/><g filter="url(#filter0_d_4066_31627)"><path d="M12.2485 17.1541L12.2562 17.1522L27.7073 12.8968C28.1523 12.8016 28.5711 13.1448 28.5711 13.6047V25.8796C28.5711 26.3543 28.1264 26.7004 27.6724 26.5791L21.7453 24.9953L20.1768 24.5761L15.3525 23.287L13.7839 22.8679L12.2504 22.4581L12.2439 22.4561C12.2114 22.4806 12.1744 22.4998 12.134 22.5121L9.98502 23.17C9.75522 23.2404 9.52344 23.0665 9.52344 22.8238V16.783C9.52344 16.5403 9.75522 16.3664 9.98502 16.4368L12.134 17.0946C12.1763 17.1076 12.2149 17.128 12.2485 17.1541Z" fill="white" fill-opacity="0.9" shape-rendering="crispEdges"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M14.7617 24.4395V26.2143C14.7617 27.7923 16.0409 29.0715 17.6189 29.0715H19.5236C21.0007 29.0715 22.2159 27.9507 22.3653 26.5132L20.476 25.9979V26.2143C20.476 26.7403 20.0496 27.1667 19.5236 27.1667H17.6189C17.0929 27.1667 16.6665 26.7403 16.6665 26.2143V24.9589L14.7617 24.4395Z" fill="white" fill-opacity="0.7"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.5234 23.9135C31.2053 23.1787 32.3806 21.5005 32.3806 19.5478C32.3806 17.5951 31.2053 15.9169 29.5234 15.1821V23.9135Z" fill="white" fill-opacity="0.7"/><defs><filter id="filter0_d_4066_31627" x="5.52344" y="12.8809" width="27.0479" height="21.7227" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.08 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4066_31627"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4066_31627" result="shape"/></filter></defs></svg>';
	const collabIcon = '<svg width="40" height="41" viewBox="0 0 40 41" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.7009 1.30097C18.7424 0.233009 21.2576 0.23301 23.2991 1.30097L36.7009 8.3117C38.7424 9.37967 40 11.3533 40 13.4893V27.5107C40 29.6467 38.7424 31.6203 36.7009 32.6883L23.2991 39.699C21.2576 40.767 18.7424 40.767 16.7009 39.699L3.29914 32.6883C1.25763 31.6203 0 29.6467 0 27.5107V13.4893C0 11.3533 1.25763 9.37967 3.29914 8.3117L16.7009 1.30097Z" fill="#19CC45"/><path opacity="0.6" fill-rule="evenodd" clip-rule="evenodd" d="M14.4 12.499C13.8139 13.9749 13.7347 15.6388 14.0669 17.107C14.2912 18.0978 14.8112 20.0237 14.9698 20.607C14.7553 20.6484 14.5294 20.6702 14.2923 20.6702C10.4695 20.6702 9.57129 15.1005 12.2779 13.1324C12.7273 12.8056 13.5912 12.4977 14.2923 12.4977C14.3285 12.4977 14.3644 12.4982 14.4 12.499Z" fill="white"/><path opacity="0.6" fill-rule="evenodd" clip-rule="evenodd" d="M12.9743 22.261C12.8794 22.3714 12.7794 22.4859 12.6759 22.6044C11.7349 23.6817 10.4986 25.0972 10.0611 26.8676L8.57271 26.8172C8.72548 23.7322 10.7753 22.5647 12.9743 22.261Z" fill="white"/><path opacity="0.6" fill-rule="evenodd" clip-rule="evenodd" d="M25.8516 12.4987C30.7426 12.5962 30.3236 20.6702 25.7618 20.6702C25.5228 20.6702 25.2951 20.6484 25.0791 20.6071C25.131 20.3713 25.2558 19.9616 25.4077 19.4627C25.78 18.2402 26.3152 16.4824 26.341 15.4373C26.3653 14.4537 26.212 13.4398 25.8516 12.4987Z" fill="white"/><path opacity="0.6" fill-rule="evenodd" clip-rule="evenodd" d="M29.8106 27.1477C29.4412 25.2458 28.1241 23.7379 27.134 22.6044C27.0195 22.4733 26.9094 22.3473 26.8057 22.2262C29.1106 22.4666 31.336 23.5978 31.4955 26.8173C31.5057 27.0241 31.3367 27.1923 31.1296 27.1923L29.8106 27.1477Z" fill="white"/><g opacity="0.9" filter="url(#filter0_d_2950_95559)"><path d="M28.278 29.0931C28.278 23.8168 23.9343 22.4977 20.0338 22.4977C16.1333 22.4977 11.7897 23.8168 11.7897 29.0931" fill="white"/></g><g opacity="0.9" filter="url(#filter1_d_2950_95559)"><path d="M20.094 20.7771C14.9715 20.7771 13.7679 13.4485 17.3947 10.8589C17.9969 10.4289 19.1545 10.0238 20.0941 10.0238C26.7749 10.0238 26.2439 20.7771 20.094 20.7771Z" fill="white"/></g><defs><filter id="filter0_d_2950_95559" x="7.78967" y="22.4977" width="24.4883" height="14.5953" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.08 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2950_95559"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2950_95559" result="shape"/></filter><filter id="filter1_d_2950_95559" x="11.3014" y="10.0238" width="17.6062" height="18.7532" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.08 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2950_95559"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2950_95559" result="shape"/></filter></defs></svg>';

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
		 * @param { Function|undefined } props.onCreateCollab
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
			if (!UserPermission.canPerformActionByUserType(ActionByUserType.createChannel))
			{
				return null;
			}

			return navigationButton({
				testId: 'create_channel',
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHANNEL_NEW_MSGVER_1'),
				subtitle: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_CHANNEL_SUBTITLE'),
				iconSvg: channelIcon,
				onClick: () => {
					this.props.onCreateChannel();
				},
				withSeparator: true,
			});
		}

		getNewChatButton()
		{
			if (!UserPermission.canPerformActionByUserType(ActionByUserType.createChat))
			{
				return null;
			}

			return navigationButton({
				testId: 'create_group_chat',
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_NEW_MSGVER_1'),
				subtitle: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_CHAT_SUBTITLE'),
				iconSvg: privateChatIcon,
				onClick: () => {
					this.props.onCreatePrivateChat();
				},
				withSeparator: true,
			});
		}

		getNewCollabButton()
		{
			if (
				!this.props.onCreateCollab
				|| !Feature.isCollabAvailable
				|| !Feature.isCollabCreationAvailable
				|| !UserPermission.canPerformActionByUserType(ActionByUserType.createCollab)
			)
			{
				return null;
			}

			return navigationButton({
				testId: 'create_collab',
				isNew: true,
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_COLLAB'),
				subtitle: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_COLLAB_SUBTITLE'),
				iconSvg: collabIcon,
				onClick: () => {
					this.props.onCreateCollab();
				},
				withSeparator: true,
			});
		}

		getInviteButton()
		{
			if (!MessengerParams.get('INTRANET_INVITATION_CAN_INVITE', false))
			{
				return null;
			}

			return navigationButton({
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_INVITE_EMPLOYEES'),
				testId: 'invite',
				onClick: () => {
					this.props.onClickInviteButton();
				},
				iconSvg: buttonIcons.specialAdd(),
				withSeparator: false,
			});
		}
	}

	module.exports = { NavigationSelectorView };
});
