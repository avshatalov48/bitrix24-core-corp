/**
 * @module im/messenger/controller/dialog/lib/pin/list-item
 */
jn.define('im/messenger/controller/dialog/lib/pin/list-item', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { parser } = require('im/messenger/lib/parser');

	/**
	 * @class PinListItem
	 */
	class PinListItem
	{
		/**
		 * @constant
		 * @type {string}
		 */
		#widgetSectionItemsId = 'pinList';

		/**
		 * @param {PinModelState} modelItem
		 * @param {object} customProps
		 */
		constructor(modelItem = {}, customProps = {})
		{
			this.modelItem = modelItem;
			this.customProps = customProps;
		}

		getNativeProps()
		{
			return { ...this.#buildProps(), ...this.customProps };
		}

		#buildProps()
		{
			const store = serviceLocator.get('core').getStore();
			let props = this.#buildBaseProps();
			const user = store.getters['usersModel/getById'](this.modelItem.message.authorId);

			if (user)
			{
				const userProps = this.#buildUserProps(user);
				props = { ...props, ...userProps };
			}

			return props;
		}

		#buildBaseProps()
		{
			const date = this.#createDate(this.modelItem.message.date);

			return {
				id: this.modelItem.message.id,
				subtitle: this.#prepareMessageText(this.modelItem.message),
				date,
				displayedDate: this.#createDisplayedDate(this.modelItem.message.date),
				sectionCode: this.#widgetSectionItemsId,
				menuMode: 'dialog',
				actions: [
					{
						title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_PIN_LIST_ITEM_ACTION_UNPIN_TITLE'),
						iconName: 'action_unpin',
						identifier: 'deletePin',
						color: Color.accentMainAlert.toHex(),
						direction: 'rightToLeft',
						fillOnSwipe: true,
					},
				],
			};
		}

		/**
		 * @param {UsersModelState} userModel
		 * @returns {object}
		 */
		#buildUserProps(userModel)
		{
			const titleColor = ChatTitle.createFromDialogId(userModel.id).getTitleColor();
			const avatar = ChatAvatar.createFromDialogId(userModel.id);

			return {
				title: userModel.name,
				imageUrl: userModel.avatar,
				color: userModel.color,
				styles: {
					title: {
						font: {
							fontStyle: 'semibold',
							color: titleColor,
							useColor: true,
						},
					},
				},
				isSuperEllipseIcon: false,
				avatar: avatar.getRecentItemAvatarProps({ useNotes: false }),
			};
		}

		/**
		 * @return {number}
		 */
		#createDate(value)
		{
			const date = DateHelper.cast(new Date(value), null);

			return Math.round(date.getTime() / 1000);
		}

		/**
		 * @return {string}
		 */
		#createDisplayedDate(value)
		{
			const date = DateHelper.cast(new Date(value), null);

			return DateFormatter.getRecentFormat(date);
		}

		/**
		 * @param {MessagesModelState} modelMessage
		 * @return {Message}
		 */
		#prepareMessageText(modelMessage)
		{
			const store = serviceLocator.get('core').getStore();

			let messageFiles = [];
			if (Type.isArrayFilled(modelMessage.files))
			{
				messageFiles = modelMessage.files
					.map((fileId) => store.getters['filesModel/getById'](fileId))
				;
			}

			return parser.simplify({
				text: modelMessage.text,
				attach: modelMessage?.params?.ATTACH ?? false,
				files: messageFiles,
				showFilePrefix: false,
			});
		}
	}

	module.exports = {
		PinListItem,
	};
});
