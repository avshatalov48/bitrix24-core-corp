/**
 * @module im/messenger/controller/dialog-creator/navigation-selector/view
 */
jn.define('im/messenger/controller/dialog-creator/navigation-selector/view', (require, exports, module) => {

	const { MessengerParams } = require('im/messenger/lib/params');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');
	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { UserSearchController } = require('im/messenger/controller/search');
	const { Loc } = require('loc');

	const privateChatIcon = `<svg width="126" height="127" viewBox="0 0 126 127" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_202_18)"><path opacity="0.989" fill-rule="evenodd" clip-rule="evenodd" d="M125.5 52.5C125.5 60.1667 125.5 67.8333 125.5 75.5C119.016 101.984 102.683 118.984 76.5 126.5C67.1667 126.5 57.8333 126.5 48.5 126.5C22.2905 118.958 5.95713 101.958 -0.5 75.5C-0.5 67.8333 -0.5 60.1667 -0.5 52.5C8.12619 20.3334 29.1262 3.00002 62.5 0.5C95.9129 3.01195 116.913 20.3453 125.5 52.5Z" fill="#31C6F6"/><path fill-rule="evenodd" clip-rule="evenodd" d="M58.5 36.5C69.5345 35.7069 74.2012 40.8736 72.5 52C70.892 55.217 69.5587 58.5504 68.5 62C68.9031 63.4728 69.5697 64.8061 70.5 66C74.7925 67.424 78.6259 69.5907 82 72.5C83.8186 78.4409 85.3186 84.4409 86.5 90.5C71.1667 90.5 55.8333 90.5 40.5 90.5C41.0068 85.125 41.8402 79.7917 43 74.5C48.1151 70.3678 53.2817 66.3678 58.5 62.5C54.7382 55.5167 53.5715 48.1833 55 40.5C56.1022 39.051 57.2689 37.7177 58.5 36.5Z" fill="#FDFEFE"/><path fill-rule="evenodd" clip-rule="evenodd" d="M37.5 81.5C32.8333 81.5 28.1667 81.5 23.5 81.5C23.5 78.1667 23.5 74.8333 23.5 71.5C26.2446 69.6218 29.2446 67.9551 32.5 66.5C34.321 65.2723 35.321 63.6056 35.5 61.5C33.5714 61.7692 31.9047 61.2692 30.5 60C30.9613 56.7712 31.4613 53.2712 32 49.5C33.2815 41.9417 37.4482 39.7751 44.5 43C46.0037 44.4907 46.8371 46.324 47 48.5C47.6323 52.5704 48.1323 56.4037 48.5 60C46.9751 61.0086 45.3084 61.5086 43.5 61.5C43.4802 63.143 44.1468 64.4763 45.5 65.5C43.9918 67.5075 42.1584 69.1741 40 70.5C39.0051 74.1451 38.1717 77.8117 37.5 81.5Z" fill="#FCFEFE"/><path fill-rule="evenodd" clip-rule="evenodd" d="M102.5 71.5C102.5 74.8333 102.5 78.1667 102.5 81.5C97.8333 81.5 93.1667 81.5 88.5 81.5C88.6758 74.6663 86.0092 69.333 80.5 65.5C81.8532 64.4763 82.5198 63.143 82.5 61.5C79.7395 62.0853 78.0728 61.0853 77.5 58.5C78.3332 54.168 79.1665 49.8346 80 45.5C82.2063 41.8748 85.373 40.7081 89.5 42C91.3333 42.5 92.5 43.6667 93 45.5C94.143 50.5548 94.9763 55.3881 95.5 60C92.7164 60.4362 91.5498 61.9362 92 64.5C95.2946 67.2243 98.7946 69.5576 102.5 71.5Z" fill="#FBFDFE"/><path fill-rule="evenodd" clip-rule="evenodd" d="M23.5 71.5C23.5 74.8333 23.5 78.1667 23.5 81.5C28.1667 81.5 32.8333 81.5 37.5 81.5C32.6946 82.4872 27.6946 82.8205 22.5 82.5C22.185 78.6286 22.5184 74.9619 23.5 71.5Z" fill="#94E1F9"/><path fill-rule="evenodd" clip-rule="evenodd" d="M102.5 71.5C103.482 74.9619 103.815 78.6286 103.5 82.5C98.3054 82.8205 93.3054 82.4872 88.5 81.5C93.1667 81.5 97.8333 81.5 102.5 81.5C102.5 78.1667 102.5 74.8333 102.5 71.5Z" fill="#8CDEF9"/></g><defs><clipPath id="clip0_202_18"><rect width="126" height="127" fill="white"/></clipPath></defs></svg>`;
	const openChatIcon = `<svg width="126" height="126" viewBox="0 0 126 126" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_203_40)"><path opacity="0.989" fill-rule="evenodd" clip-rule="evenodd" d="M51.5 -0.5C58.8333 -0.5 66.1667 -0.5 73.5 -0.5C101.167 6.5 118.5 23.8333 125.5 51.5C125.5 58.8333 125.5 66.1667 125.5 73.5C118.5 101.167 101.167 118.5 73.5 125.5C66.1667 125.5 58.8333 125.5 51.5 125.5C23.8333 118.5 6.5 101.167 -0.5 73.5C-0.5 66.1667 -0.5 58.8333 -0.5 51.5C6.5 23.8333 23.8333 6.5 51.5 -0.5Z" fill="#30C6F5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M90.5 37.5C92.5 37.5 94.5 37.5 96.5 37.5C96.5 52.5 96.5 67.5 96.5 82.5C88.4306 81.1492 80.4306 79.4825 72.5 77.5C72.6646 80.5184 72.498 83.5184 72 86.5C71.5 87 71 87.5 70.5 88C62.1667 88.6667 53.8333 88.6667 45.5 88C45 87.5 44.5 87 44 86.5C43.8299 80.8042 43.3299 75.1376 42.5 69.5C38.0548 68.0963 33.7214 68.4297 29.5 70.5C28.5023 62.5277 28.169 54.5277 28.5 46.5C33.371 48.2078 38.371 48.7078 43.5 48C59.3227 44.6244 74.9894 41.1244 90.5 37.5Z" fill="#FCFEFE"/><path fill-rule="evenodd" clip-rule="evenodd" d="M51.5 71.5C49.5864 74.6843 48.9197 78.351 49.5 82.5C48.5166 78.7016 48.1832 74.7016 48.5 70.5C49.791 70.2627 50.791 70.596 51.5 71.5Z" fill="#B4EAFC"/><path fill-rule="evenodd" clip-rule="evenodd" d="M51.5 71.5C56.2129 72.6259 60.8796 73.9593 65.5 75.5C66.48 78.0865 66.8134 80.7531 66.5 83.5C60.642 83.8222 54.9753 83.4889 49.5 82.5C48.9197 78.351 49.5864 74.6843 51.5 71.5Z" fill="#41C8F6"/></g><defs><clipPath id="clip0_203_40"><rect width="126" height="126" fill="white"/></clipPath></defs></svg>`;

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
			return new SingleSelector(
					{
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
						}
					}
				);
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
			return ButtonFactory.createFullWidthButton({
				testId: 'create_channel',
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_CHANNEL'),
				svgIcon: openChatIcon,
				callback: () => {
					this.props.onCreateOpenChat();
				}
			});
		}

		getNewChatButton()
		{
			return ButtonFactory.createFullWidthButton({
				testId: 'create_group_chat',
				text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_NEW_CHAT'),
				svgIcon: privateChatIcon,
				callback: () => {
					this.props.onCreatePrivateChat();
				}
			});
		}
		getInviteButton()
		{
			if (MessengerParams.get('INTRANET_INVITATION_CAN_INVITE', false))
			{
				return ButtonFactory.createInviteButton({
					text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_INVITE_EMPLOYEES'),
					callback: () => {
						this.props.onClickInviteButton();
					}
				});
			}
		}
	}

	module.exports = { NavigationSelectorView };

});