/**
 * @module im/messenger/lib/converter/chat-layout
 */
jn.define('im/messenger/lib/converter/chat-layout', (require, exports, module) => {
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');

	/**
	 * @class ChatLayoutConverter
	 */
	class ChatLayoutConverter
	{
		/**
		 * @param {DialogId} id
		 * @param {boolean} isPressed
		 */
		static toListItem({ id, isPressed = true })
		{
			const chatTitle = ChatTitle.createFromDialogId(id);
			const chatAvatar = ChatAvatar.createFromDialogId(id);

			return {
				data: {
					id,
					title: chatTitle.getTitle(),
					subtitle: chatTitle.getDescription(),
					avatarUri: chatAvatar.getAvatarUrl(),
					avatarColor: chatAvatar.getColor(),
					avatar: chatAvatar.getListItemAvatarProps(),
				},
				type: 'chats',
				isPressed,
				isSuperEllipseAvatar: chatAvatar.getIsSuperEllipseIcon(),
			};
		}

		/**
		 * @param {DialogId} id
		 * @param {boolean} isPressed
		 */
		static toSingleSelectorItem({ id, isPressed = true })
		{
			return ChatLayoutConverter.toListItem({ id, isPressed });
		}

		static toMultiSelectorItem({ id, isPressed = true, selected = false, disable = false })
		{
			const chatTitle = ChatTitle.createFromDialogId(id);
			const chatAvatar = ChatAvatar.createFromDialogId(id);

			return {
				data: {
					id,
					title: chatTitle.getTitle(),
					subtitle: chatTitle.getDescription(),
					avatarUri: chatAvatar.getAvatarUrl(),
					avatarColor: chatAvatar.getColor(),
					avatar: chatAvatar.getListItemAvatarProps(),
				},
				type: 'chats',
				isPressed,
				selected,
				disable,
				isSuperEllipseAvatar: chatAvatar.getIsSuperEllipseIcon(),
			};
		}
	}

	module.exports = { ChatLayoutConverter };
});
