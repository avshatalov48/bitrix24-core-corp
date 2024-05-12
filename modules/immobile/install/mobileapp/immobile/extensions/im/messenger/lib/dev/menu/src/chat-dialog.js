/**
 * @module im/messenger/lib/dev/menu/chat-dialog
 */
jn.define('im/messenger/lib/dev/menu/chat-dialog', (require, exports, module) => {

	const {
		TextMessage,
		DeletedMessage,
		ImageMessage,
		AudioMessage,
		VideoMessage,
		FileMessage,
		SystemTextMessage,
		UnsupportedMessage,
		DateSeparatorMessage,
		UnreadSeparatorMessage,
	} = require('im/messenger/lib/element');
	const { MessengerParams } = require('im/messenger/lib/params');

	class ChatDialog
	{
		constructor()
		{
			this.titleParams = {
				text: 'All types of messages',
				detailText: '',
				imageColor: '#C3F2FF',
				useLetterImage: true,
			};

			this.widget = null;
		}

		open()
		{
			PageManager.openWidget(
				'chat.dialog',
				{
					titleParams: this.titleParams,
				},
			)
				.then(this.onWidgetReady.bind(this))
				.catch(error => console.error(error))
			;
		}

		onWidgetReady(widget)
		{
			this.widget = widget;
			this.widget.on('messageTap', (index, message) => {
				if (message.id === this.getSendingMessage().id)
				{
					const sendingMessage = this.getSendingMessage(!message.isSending);
					this.widget.updateMessageById(sendingMessage.id, sendingMessage);
				}
			});

			this.widget.setMessages([
				new SystemTextMessage({
					text: 'Short system message',
				}),
				new SystemTextMessage({
					text: 'Long system message. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum nec vulputate velit. Maecenas nec cursus lacus. Nunc ullamcorper elit non condimentum sodales. Suspendisse fringilla tempor vehicula. Vivamus vitae nisi vitae tortor tempor sodales nec eget dui. Suspendisse pellentesque interdum dolor, et imperdiet turpis ullamcorper id.',
				}),
				new DateSeparatorMessage('date-separator', new Date(Date.now() - 86400000)),
				new TextMessage({
					id: Math.random().toString(),
					text: 'Short text message. me: false.',
					authorId: 100,
					date: new Date(Date.now() - 86400000),
				}),
				new TextMessage({
					id: Math.random().toString(),
					text: 'Long text message. me: false. \nLorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum nec vulputate velit. Maecenas nec cursus lacus. Nunc ullamcorper elit non condimentum sodales. Suspendisse fringilla tempor vehicula. Vivamus vitae nisi vitae tortor tempor sodales nec eget dui. Suspendisse pellentesque interdum dolor, et imperdiet turpis ullamcorper id.',
					authorId: 100,
					date: new Date(Date.now() - 86400000),
					params: {
						REACTION: {
							like: [1, 2, 3, 4, 5, 6, 7]
						}
					},
				}),
				new UnreadSeparatorMessage(),
				new DateSeparatorMessage('date-separator', new Date()),
				new TextMessage({
					id: Math.random().toString(),
					text: 'Short text message. me: true.',
					authorId: MessengerParams.getUserId(),
					date: new Date(),
				}),
				new TextMessage({
					id: Math.random().toString(),
					text: 'Long text message. me: true. \nLorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum nec vulputate velit. Maecenas nec cursus lacus. Nunc ullamcorper elit non condimentum sodales. Suspendisse fringilla tempor vehicula. Vivamus vitae nisi vitae tortor tempor sodales nec eget dui. Suspendisse pellentesque interdum dolor, et imperdiet turpis ullamcorper id.',
					authorId: MessengerParams.getUserId(),
					date: new Date(),
					params: {
						REACTION: {
							like: [1, 2, 3]
						}
					},
				}),
				new TextMessage({
					id: Math.random().toString(),
					text: 'Unsent message',
					authorId: MessengerParams.getUserId(),
					date: new Date(),
					error: true,
				}),
				{
					id: Math.random().toString(),
					message: [
						{
							'type': 'quote-active',
							'dialogId': 'chat870',
							'messageId': '11856',
							'title': 'Lev Tregubov',
							'text': 'blue quote',
						},
						{
							'type': 'text',
							'text': 'text 1',
						},
						{
							'type': 'quote-inactive',
							'title': 'Lev Tregubov',
							'text': 'gray quote',
						},
						{
							'type': 'text',
							'text': 'text 2',
						},
						{
							'type': 'quote-inactive',
							'text': 'gray quote without title',
						},
						{
							'type': 'text',
							'text': 'text 3',
						},
						{
							'type': 'quote-inactive',
							'text': 'first quote without title',
						},
						{
							'type': 'text',
							'text': 'text 4',
						},
						{
							'type': 'quote-inactive',
							'text': 'second quote without title\ncontinuation of the second quote without title',
						},
						{
							'type': 'text',
							'text': 'text 5',
						},
					],
					me: true,
					date: '10:00',
					error: false,
					style: {
						roundedCorners: true,
						rightTail: true,
					},
				},
				this.getSendingMessage(true),
			].reverse());
		}

		getSendingMessage(isSending = true)
		{
			return {
				id: 'sending-image',
				type: 'image',
				message: [
					{
						type: 'text',
						text: 'isSending: ' + (isSending ? ' true' : ' false'),
					}
				],
				imageUrl: `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/src/dialog/message-menu/images/reaction/kiss.png`,
				authorId: MessengerParams.getUserId(),
				me: true,
				date: '10:00',
				error: false,
				isSending: isSending,
				style: {
					roundedCorners: true,
					rightTail: true,
				},
			}
		}
	}

	module.exports = {
		ChatDialog,
	};
});
