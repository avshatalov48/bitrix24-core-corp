/**
 * @module im/messenger/lib/ui/avatar/src/chat-avatar-adapter
 */
jn.define('im/messenger/lib/ui/avatar/src/chat-avatar-adapter', (require, exports, module) => {
	const { mergeImmutable, isObjectLike } = require('utils/object');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');

	/**
	 * @class ChatAvatarAdapter
	 */
	class ChatAvatarAdapter
	{
		/**
		 * @param {ChatAvatarAdapterProps} props
		 */
		constructor(props)
		{
			this.props = props;

			this.chatAvatar = ChatAvatar.createFromDialogId(props.dialogId);
		}

		/**
		 * @returns {AvatarDetail}
		 */
		getChatParams()
		{
			return this.chatAvatar.getBaseAvatarProps(this.#customParams());
		}

		/**
		 * @returns {Partial<AvatarDetail>}
		 */
		#customParams()
		{
			const { isNotes, placeholderSvgSize } = this.props;

			const params = {
				testId: this.getTestId(),
				style: this.getStyle(),
				placeholder: this.#getPlaceholder(),
			};

			if (isNotes)
			{
				const notesParams = this.chatAvatar.getAvatarNotesProps();

				if (placeholderSvgSize > 0)
				{
					notesParams.placeholder.svg.size = placeholderSvgSize;
				}

				return mergeImmutable(params, notesParams);
			}

			return params;
		}

		#getPlaceholder()
		{
			const { placeholder } = this.props;

			if (isObjectLike(placeholder))
			{
				return placeholder;
			}

			return {
				letters: null,
			};
		}

		getTestId()
		{
			const { testId } = this.props;

			return String(testId);
		}

		getStyle()
		{
			const { size = 32, style } = this.props;

			return {
				width: size,
				height: size,
				...style,
			};
		}
	}

	module.exports = {
		/**
		 * @param {ChatAvatarAdapterProps} props
		 * @returns {AvatarBaseProps}
		 */
		ChatAvatarAdapter: (props) => Avatar(
			(new ChatAvatarAdapter(props)).getChatParams(),
		),
	};
});
