/**
 * @module im/messenger/lib/dev/chat-dialog-benchmark
 */
jn.define('im/messenger/lib/dev/chat-dialog-benchmark', (require, exports, module) => {

	const {
		EventType,
	} = require('im/messenger/const');
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

	class ChatDialogBenchmark
	{
		constructor()
		{
			this.titleParams = {
				text: 'Scroll benchmark',
				detailText: 'Close and open to restart',
				imageUrl: '',
				imageColor: '#FF99AD',
				useLetterImage: true,
			};

			this.widget = null;
			this.messagesCount = 0;
		}

		showInstruction()
		{
			this.widget.setInputQuote({
				username: 'Scroll down to launch',
				message: 'Scroll up to stop',
			});
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
			this.showInstruction();
			this.setMessages(this.getRandomMessages(50));

			this.widget.on(EventType.dialog.viewableMessagesChanged, (indexList, messageList) => {
				if (indexList.includes(0))
				{
					this.addMessages(this.getRandomMessages(50));
				}
			})
		}

		updateInputPlaceholder()
		{
			this.widget.setInputPlaceholder('Count: ' + this.messagesCount);
		}

		setMessages(messages)
		{
			this.widget.setMessages(messages);
			this.messagesCount = messages.length;
			this.updateInputPlaceholder();
		}
		addMessages(messages)
		{
			this.widget.addMessages(messages);
			this.messagesCount += messages.length;
			this.updateInputPlaceholder();
		}

		getRandomMessages(count)
		{
			const messageList = [];
			for (let index = 0; index < count; index++)
			{
				messageList.push(this.getRandomTextMessage());
			}

			return messageList;
		}

		getRandomTextMessage()
		{
			const minLength = 15;
			const maxLength = 100;

			const length = this.getRandomNumberBetween(minLength, maxLength);
			const text = this.getRandomText(length);

			return new SystemTextMessage({
				text,
			});
		}

		getRandomText(length)
		{
			let result = '';
			const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			const charactersLength = characters.length;
			let counter = 0;
			while (counter < length) {
				result += characters.charAt(Math.floor(Math.random() * charactersLength));
				counter += 1;
			}

			return result;
		}
		getRandomNumberBetween(min, max)
		{
			return Math.random() * (max - min) + min;
		}
	}

	module.exports = {
		ChatDialogBenchmark,
	};
});
