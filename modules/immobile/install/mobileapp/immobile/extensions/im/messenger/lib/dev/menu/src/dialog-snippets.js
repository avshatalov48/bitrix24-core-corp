/**
 * @module im/messenger/lib/dev/menu/dialog-snippets
 */
jn.define('im/messenger/lib/dev/menu/dialog-snippets', (require, exports, module) => {
    const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { CheckBox } = require('im/messenger/lib/ui/base/checkbox');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const AppTheme = require('apptheme');
	const { MessageRest } = require('im/messenger/provider/rest');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, ComponentCode } = require('im/messenger/const');
	const { Type } = require('type');
	const { Alert } = require('alert');
	/**
	 * @typedef {LayoutComponent<{}, {spam: {dialogId: string|number, counter: number, messageCounter: number}}>} DialogSnippets
	 */
	class DialogSnippets extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.store = serviceLocator.get('core').getStore();
			this.state.spam = {
				dialogId: 'null',
				counter: 10,
				messageCounter: 0,
				withRandomMessageText: false,
			};
		}

		render()
		{
			return View(
				{},
				this.spamSection,

			);
		}

		get spamSection()
		{
			return View(
				{},
				View(
					{
						style: {
							borderBottomWidth: 1,
							borderTopWidth: 1,
							borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
							borderTopColor: AppTheme.colors.bgSeparatorPrimary,
							marginBottom: 2,
							flexDirection: 'row',
							alignItems: 'center',
							justifyContent: 'flex-start',
							padding: 10,
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
								marginRight: 10,
							},
						},
						Button({
							style: {
								backgroundColor: AppTheme.colors.accentSoftBlue1,
							},
							text: 'Choose Dialog for Spam',
							onClick: () => this.chooseDialog(),
						}),
						Text({
							text: `dialogId: ${this.state.spam.dialogId.toString()}`,
						}),
						Text({
							text: 'Counter',
						}),
						TextInput({
							style: {
								width: '100%',
								height: 24,
								borderWidth: 1,
								borderColor: AppTheme.colors.bgSeparatorPrimary,
							},
							placeholder: this.state.spam.counter.toString(),
							onChangeText: (text) => {
								this.state.spam.counter = Number(text);
							},
						}),
						Text({
							text: 'With random message text',
						}),
						new CheckBox({
							checked: this.state.withRandomMessageText,
							onClick: () => {
								this.state.spam.withRandomMessageText = !this.state.spam.withRandomMessageText;
							},
						}),
					),
				),
				View(
					{},
					Text({
						text: `messages sent: ${this.state.spam.messageCounter}`,
					}),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-start',
						},
					},
					Button({
						style: {
							height: 50,
							width: 100,
							backgroundColor: AppTheme.colors.accentMainAlert,
							marginRight: 20,
						},
						text: 'DDoS',
						onClick: () => this.DoS(),
					}),
					Button({
						style: {
							height: 50,
							width: 100,
							backgroundColor: AppTheme.colors.accentMainSuccess,
						},
						text: 'Go to Chat',
						onClick: () => {
							if (this.state.spam.dialogId === 'null')
							{
								Alert.alert('Error', 'Invalid dialogId');

								return;
							}
							MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: this.state.spam.dialogId }, ComponentCode.imMessenger);
						},
					}),
				),
			);
		}

		generateRandomText()
		{
			const words = [
				'Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
				'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
				'magna', 'aliqua', 'Ut', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
				'exercitation', 'ullamco', 'laboris', 'nisi', 'ut', 'aliquip', 'ex', 'ea', 'commodo',
				'consequat', 'Duis', 'aute', 'irure', 'dolor', 'in', 'reprehenderit', 'in', 'voluptate',
				'velit', 'esse', 'cillum', 'dolore', 'eu', 'fugiat', 'nulla', 'pariatur', 'Excepteur',
				'sint', 'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'in', 'culpa', 'qui',
				'officia', 'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum',
			];

			const minSentenceLength = 1;
			const maxSentenceLength = words.length;
			const sentenceLength = Math.floor(Math.random() * (maxSentenceLength - minSentenceLength + 1)) + minSentenceLength;

			let sentence = '';
			for (let i = 0; i < sentenceLength; i++)
			{
				const randomIndex = Math.floor(Math.random() * words.length);
				sentence += `${words[randomIndex]} `;
			}

			return sentence.trim();
		}

		async DoS()
		{
			if (!Type.isNumber(this.state.spam.counter))
			{
				Alert.alert('Error', 'Invalid counter');

				return;
			}

			if (this.state.spam.dialogId === 'null')
			{
				Alert.alert('Error', 'Invalid dialogId');

				return;
			}
			this.setState({
				spam: {
					...this.state.spam,
					messageCounter: 0,
				},
			});

			const dialogId = this.state.spam.dialogId;
			await MessageRest.send({
				dialogId,
				text: '[START]---------------------',
			});

			this.setState({
				spam: {
					...this.state.spam,
					messageCounter: 1,
				},
			});

			for (let messageIndex = 1; messageIndex <= this.state.spam.counter; messageIndex++)
			{
				const messageText = this.state.spam.withRandomMessageText
					? `[b]${messageIndex}[/b]\n${this.generateRandomText()}`
					: messageIndex
				;

				// eslint-disable-next-line no-await-in-loop
				await MessageRest.send({
					dialogId,
					text: messageText,
				});

				this.setState({
					spam: {
						...this.state.spam,
						messageCounter: this.state.spam.messageCounter + 1,
					},
				});
			}

			await MessageRest.send({
				dialogId,
				text: '[FINISH]---------------------',
			});

			this.setState({
				spam: {
					...this.state.spam,
					messageCounter: 'complete',
				},
			});
		}

		chooseDialog()
		{
			const recentList = this.store.getters['recentModel/getSortedCollection']();
			const dialogList = recentList.map((recentItem) => {
				return this.store.getters['dialoguesModel/getById'](recentItem.id);
			});

			PageManager.openWidget('layout', {
				backdrop: {
					horizontalSwipeAllowed: false,
					mediumPositionPercent: 50,
					topPosition: 150,
					onlyMediumPosition: false,
					hideNavigationBar: true,
				},
				onReady: (layoutWidget) => {
					layoutWidget.showComponent(new SingleSelector({
						itemList: this.prepareItems(dialogList),
						onItemSelected: (item) => {
							this.setState({
								spam: {
									...this.state.spam,
									dialogId: item.dialogId,
								},
							});

							layoutWidget.close();
						},
					}));
				},
			});
		}

		prepareItems(itemList)
		{
			return itemList.map((item) => {
				const chatTitle = ChatTitle.createFromDialogId(item.dialogId);
				const chatAvatar = ChatAvatar.createFromDialogId(item.dialogId);

				return {
					data: {
						id: item.dialogId,
						title: chatTitle.getTitle(),
						subtitle: chatTitle.getDescription(),
						avatarUri: chatAvatar.getAvatarUrl(),
						avatarColor: item.color,
					},
					type: 'chats',
					selected: false,
					disable: false,
					isWithPressed: true,
				};
			});
		}
	}

	module.exports = { DialogSnippets };
});
