/**
 * @module crm/mail/chain
 */
jn.define('crm/mail/chain', (require, exports, module) => {
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { Moment } = require('utils/date');
	const { dayShortMonth, shortTime } = require('utils/date/formats');
	const { throttle } = require('utils/function');
	const { clone, isEqual } = require('utils/object');
	const { ActionPanel } = require('crm/mail/chain/action-panel');
	const { Avatar } = require('crm/mail/message/elements/avatar');
	const { Icon, MoreButton } = require('crm/mail/message/elements/icon');
	const { ContactList } = require('crm/mail/message/elements/contact/list');
	const { getBodyPromise, getFilesDataPromise, deleteMessage } = require('crm/mail/message/tools/connector');
	const { MessageBody } = require('crm/mail/message/tools/messagebody');
	const { MailOpener } = require('crm/mail/opener');

	const titles = {
		fields: {
			to: BX.message('MESSAGE_VIEW_HEADER_TO'),
			cc: BX.message('MESSAGE_VIEW_HEADER_CC'),
			bcc: BX.message('MESSAGE_VIEW_HEADER_BCC'),
		},
	};

	const paddingRightHeader = 18;
	const paddingLeftHeader = 22;
	const avatarSize = 34;
	const contactsBlockLeftPadding = 8;
	const directorIconPaddingRight = 8;
	const maxWidthDate = 100;
	const directionItemWidth = 24;

	const allMarginsWidth = paddingRightHeader
		+ paddingLeftHeader
		+ avatarSize
		+ contactsBlockLeftPadding
		+ directorIconPaddingRight
		+ maxWidthDate
		+ directionItemWidth;

	let deviceWidth = device.screen.width;
	if (!deviceWidth)
	{
		deviceWidth = 360;
	}

	const maxWidthTextFiled = deviceWidth - allMarginsWidth;

	const icons = {
		incoming: {
			content: '<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.5895 11.0083L5.55807 11.0083C2.6143 11.0083 0.219447 8.61342 0.219447 5.66966C0.219447 2.72589 2.6143 0.331034 5.55807 0.331035L8.16949 0.331035L8.17809 2.46648L5.82683 2.46648C4.06115 2.46648 2.62366 3.90397 2.62366 5.66966C2.62366 7.43677 4.06115 8.87283 5.82683 8.87283L10.5852 8.87283L10.5752 4.35972L16.0012 9.78147L10.5995 15.2018L10.5895 11.0083Z" fill="#BDC1C6"/></svg>',
		},
		outgoing: {
			content: '<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.41049 4.99172H11.4419C14.3857 4.99172 16.7806 7.38658 16.7806 10.3303C16.7806 13.2741 14.3857 15.669 11.4419 15.669H8.83051L8.82192 13.5335H11.1732C12.9389 13.5335 14.3763 12.096 14.3763 10.3303C14.3763 8.56322 12.9389 7.12717 11.1732 7.12717H6.41479L6.42482 11.6403L0.998779 6.21853L6.40046 0.798218L6.41049 4.99172Z" fill="#BDC1C6"/></svg>',
		},
	};

	function MessageCard(props)
	{
		const message = props.message;
		const header = message.HEADER;

		if (message.isVisible === false)
		{
			return null;
		}

		const format = message.format;

		let loader = null;
		let subject = null;
		let footer = null;

		if (format === 'full')
		{
			footer = View(
				{
					style: {
						height: 42,
					},
				},
				MoreButton({
					testId: 'message-card-in-chain-more-button-in-footer',
					style: {
						bottom: 5,
						right: 18,
						position: 'absolute',
					},
					action: props.showMenuAction.bind(null, props.id, message.ID, message.SUBJECT, 'full'),
				}),
			);

			if (message.DESCRIPTION === undefined || message.FILES === undefined)
			{
				loader = Loader({
					style: {
						width: 45,
						height: 45,
						alignSelf: 'center',
						paddingBottom: 100,
					},
					animating: true,
					size: 'large',
				});
			}
			subject = message.SUBJECT;
		}

		return View(
			{
				style: {
					backgroundColor: '#ffffff',
					marginBottom: 4,
					marginTop: 4,
					borderRadius: 12,
				},
			},
			Header({
				direction: message.DIRECTION,
				subject: message.SUBJECT,
				date: message.DATE,
				time: message.TIME,
				format: message.format,
				to: header.to,
				from: header.from,
				bcc: header.bcc,
				cc: header.cc,
				id: message.ID,
			}),
			subject,
			new MessageBody({
				subject,
				files: message.FILES,
				isHiddenField: false,
				format: message.format,
				content: message.DESCRIPTION,
			}),
			loader,
			footer,
		);
	}

	function DirectionItem(props)
	{
		const styleImage = {
			width: directionItemWidth,
			height: 24,
		};

		const style = {
			paddingTop: 17,
			paddingRight: directorIconPaddingRight,
		};

		if (props.direction === '1')
		{
			return View(
				{
					style,
				},
				Image({
					style: styleImage,
					svg: icons.outgoing,
				}),
			);
		}

		return View(
			{
				style,
			},
			Image({
				style: styleImage,
				svg: icons.incoming,
			}),
		);
	}

	function Header(props)
	{
		const header = [];

		const {
			format,
			from,
			to,
			bcc,
			cc,
			id,
			date,
			subject,
		} = props;

		const fields = {
			to: {
				list: to,
			},
			cc: {
				list: cc,
			},
			bcc: {
				list: bcc,
			},
		};

		header.push(new ContactList({
			maxWidthTextFiled,
			format: 'big',
			list: from,
			fieldId: id,
		}));

		if (format === 'full')
		{
			header.push(...Object.entries(fields).map(([key, item]) => {
				return View(
					{
						style: {
							flexDirection: 'row',
							center: 'center',
						},
					},
					new ContactList({
						maxWidthTextFiled,
						format: 'little',
						list: item.list,
						title: titles.fields[key],
					}),
				);
			}));
		}
		else
		{
			header.push(Text({
				style: {
					textAlignVertical: 'top',
					paddingRight: 18,
					marginTop: 2,
					fontWeight: '500',
					fontSize: 14,
					color: '#525c69',
				},
				text: subject,
			}));
		}

		function renderDate(date)
		{
			const moment = Moment.createFromTimestamp(date);

			return new FriendlyDate({
				timeSeparator: '\r\n',
				moment,
				defaultFormat: (moment) => {
					const day = moment.format(dayShortMonth());
					const time = moment.format(shortTime);
					return `${day}\r\n${time}`;
				},
				useTimeAgo: true,
				showTime: true,
				style: {
					maxWidth: maxWidthDate,
					lineHeightMultiple: 1.3,
					textAlign: 'center',
					fontWeight: '400',
					fontSize: 13,
					color: '#959ca4',
				},
			});
		}

		function renderContacts(header)
		{
			return View(
				{
					style: {
						flex: 1,
						paddingTop: 12,
						paddingLeft: contactsBlockLeftPadding,
					},
				},
				...header,
			);
		}

		function renderAvatar()
		{
			return View(
				{
					style: {
						paddingTop: 12,
					},
				},
				Avatar({
					fullName: from[0].name,
					email: from[0].email,
					size: avatarSize,
				}),
			);
		}

		return View(
			{
				style: {
					paddingBottom: 12,
					flexDirection: 'row',
					paddingLeft: paddingLeftHeader,
					paddingRight: paddingRightHeader,
					width: '100%',
					backgroundColor: '#fff',
				},
			},
			View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				DirectionItem({
					direction: props.direction,
				}),
				renderAvatar(),
				renderContacts(header),
			),
			View(
				{
					style: {
						top: 11,
					},
				},
				renderDate(date),
			),
		);
	}

	class MessageChain extends LayoutComponent
	{
		constructor(props)
		{
			const {
				threadId,
				chain,
			} = props;

			super();

			this.state = {
				threadId,
			};

			this.actions = {
				replyButton: this.replyMessageAction.bind(this),
				replyAllButton: this.replyAllMessageAction.bind(this),
				forwardButton: this.forwardAction.bind(this),
				moreButton: this.showContextMenu.bind(this),
			};

			this.messageCount = 0;
			this.properties = chain.properties;
			this.setChain(threadId, chain.list, false);
		}

		showContextMenu(id = this.lastIncomingCardId, messageId = this.lastIncomingId, title = this.lastIncomingTitle, format = 'little')
		{
			if (title === undefined)
			{
				title = '';
			}

			const baseActions = [
				{
					id: 'reply',
					title: BX.message('MESSAGE_VIEW_CONTEXT_MENU_REPLY'),
					subTitle: '',
					data: {
						svgIcon: Icon('reply'),
					},
					onClickCallback: () => {
						menu.close(() => {
							this.replyMessageAction(id);
						});
					},
				},
				{
					id: 'reply-all',
					title: BX.message('MESSAGE_VIEW_CONTEXT_MENU_REPLY_ALL'),
					subTitle: '',
					data: {
						svgIcon: Icon('replyAll'),
					},
					onClickCallback: () => {
						menu.close(() => {
							this.replyAllMessageAction(id);
						});
					},
				},
				{
					id: 'forward',
					title: BX.message('MESSAGE_VIEW_CONTEXT_MENU_FORWARD'),
					subTitle: '',
					data: {
						svgIcon: Icon('forward'),
					},
					onClickCallback: () => {
						menu.close(() => {
							this.forwardAction(id);
						});
					},
				},
			];

			const moreActions = [
				{
					id: 'exclude-from-crm',
					title: BX.message('MESSAGE_VIEW_CONTEXT_MENU_EXCLUDE_FROM_CRM'),
					subTitle: '',
					data: {
						svgIcon: Icon('exclude'),
					},
					onClickCallback: () => {
						menu.close(() => {
							this.deleteMessage(id, messageId, true);
						});
					},
				},
				{
					id: 'mark-as-spam',
					title: BX.message('MESSAGE_VIEW_CONTEXT_MENU_MARK_AS_SPAM'),
					subTitle: '',
					data: {
						svgIcon: Icon('spam'),
					},
					onClickCallback: () => {
						menu.close(() => {
							this.deleteMessage(id, messageId, false, true);
						});
					},
				},
				{
					id: 'delete-message',
					title: BX.message('MESSAGE_VIEW_CONTEXT_MENU_DELETE'),
					subTitle: '',
					data: {
						svgIcon: Icon('remove'),
					},
					onClickCallback: () => {
						menu.close(() => {
							this.deleteMessage(id, messageId);
						});
					},
				},
			];

			let actions;

			if (format === 'full')
			{
				actions = [
					...baseActions,
					...moreActions,
				];
			}
			else
			{
				actions = [
					...moreActions,
				];
			}

			const menu = new ContextMenu({
				testId: 'message-card-in-chain-action-menu',
				actions,
				params: {
					title: `${BX.message('MESSAGE_VIEW_CONTEXT_MENU_TITLE_MESSAGE')} ${title}`,
					showCancelButton: true,
				},
			});

			menu.show();
		}

		getOwnerEntity(cardId, inResponseToMessage)
		{
			return {
				inResponseToMessage,
				ownerId: this.state.chain[cardId].OWNER_ID,
				ownerType: this.state.chain[cardId].OWNER_TYPE,
			};
		}

		getSendersSet(cardId)
		{
			return (this.getHeader(cardId)).accessMailboxesForSending;
		}

		getFiles(cardId)
		{
			// @todo add: upload files if the message is not expanded
			return this.state.chain[cardId].FILES;
		}

		getHeader(cardId)
		{
			return {
				owner: this.getOwnerEntity(cardId, this.state.chain[cardId].ID),
				...this.state.chain[cardId].HEADER,
			};
		}

		getSubject(cardId)
		{
			return this.state.chain[cardId].SUBJECT;
		}

		getReplyParams(cardId)
		{
			const header = this.getHeader(cardId);
			let contacts = this.clearFromEmployeeEmails(header.from, header.employeeEmails);

			if (contacts.length === 0)
			{
				contacts = this.clearFromEmployeeEmails(header.to, header.employeeEmails);
			}

			return {
				uploadSenders: false,
				uploadClients: false,
				isSendFiles: false,
				files: this.getFiles(cardId),
				senders: this.getSendersSet(cardId),
				subject: `Re: ${this.getSubject(cardId)}`,
				owner: header.owner,
				contacts,
				reply_message_body: this.getBodyHtml(cardId),
			};
		}

		clearFromEmployeeEmails(contacts, employeeContacts)
		{
			const employeeEmails = new Set(employeeContacts.map((item) => item.email));
			return contacts.filter(({ email }) => !employeeEmails.has(email));
		}

		getReplyAllParams(cardId)
		{
			const header = this.getHeader(cardId);

			return {
				uploadSenders: false,
				uploadClients: false,
				isSendFiles: false,
				files: this.getFiles(cardId),
				senders: this.getSendersSet(cardId),
				subject: `Re: ${this.getSubject(cardId)}`,
				owner: header.owner,
				contacts: this.clearFromEmployeeEmails([...header.to, ...header.from], header.employeeEmails),
				cc: this.clearFromEmployeeEmails([...header.cc, ...header.bcc], header.employeeEmails),
				reply_message_body: this.getBodyHtml(cardId),
			};
		}

		getForwardParams(cardId)
		{
			const header = this.getHeader(cardId);

			return {
				uploadSenders: false,
				uploadClients: false,
				isSendFiles: true,
				files: this.getFiles(cardId),
				senders: this.getSendersSet(cardId),
				subject: `Fwd: ${this.getSubject(cardId)}`,
				owner: header.owner,
				reply_message_body: this.getBodyHtml(cardId),
			};
		}

		getBodyHtml(cardId)
		{
			const chain = this.state.chain;

			if (chain[cardId].DESCRIPTION === undefined)
			{
				chain[cardId].DESCRIPTION = '';
			}

			return chain[cardId].DESCRIPTION;
		}

		replyMessageAction(cardId = this.lastIncomingCardId)
		{
			MailOpener.openSend(this.getReplyParams(cardId));
		}

		replyAllMessageAction(cardId = this.lastIncomingCardId)
		{
			MailOpener.openSend(this.getReplyAllParams(cardId));
		}

		forwardAction(cardId = this.lastIncomingCardId)
		{
			MailOpener.openSend(this.getForwardParams(cardId));
		}

		hideChainItem(cardId)
		{
			const chain = clone(this.state.chain);

			if (chain[cardId])
			{
				chain[cardId].isVisible = false;
				if (!isEqual(this.state.chain, chain))
				{
					this.setState({
						chain,
					});
				}
			}
		}

		showChainItem(cardId)
		{
			const chain = clone(this.state.chain);

			if (chain[cardId])
			{
				chain[cardId].isVisible = true;
				if (!isEqual(this.state.chain, chain))
				{
					this.setState({
						chain,
					});
				}
			}
		}

		onMessageDelete()
		{
			this.messageCount--;

			if (this.messageCount === 0)
			{
				layout.close();
			}
		}

		onMessageDeleteFailure(cardId)
		{
			this.showChainItem(cardId);
		}

		deleteMessage(cardId, messageId, excludeFromCrm = false, markAsSpam = false)
		{
			this.hideChainItem(cardId);

			deleteMessage({
				id: messageId,
				ownerId: this.state.chain[cardId].OWNER_ID,
				ownerType: this.state.chain[cardId].OWNER_TYPE,
				successAction: this.onMessageDelete.bind(this),
				failureAction: this.onMessageDeleteFailure.bind(this, cardId),
				excludeFromCrm,
				markAsSpam,
			});
		}

		loadMessage(id)
		{
			const chain = this.state.chain;

			if (chain[id].DESCRIPTION === undefined)
			{
				getBodyPromise(chain[id].ID).then((response) => {
					const chain = this.state.chain;
					const data = response.data;

					chain[id].DESCRIPTION = data.HTML;
					this.setState({
						chain,
					});
				});
			}

			if (chain[id].FILES === undefined)
			{
				chain[id].FILES = '';

				getFilesDataPromise(chain[id].ID).then((response) => {
					const chain = this.state.chain;
					const data = response.data;

					chain[id].FILES = data.FILES;
					this.setState({
						chain,
					});
				});
			}
		}

		cardTouch(id)
		{
			const chain = clone(this.state.chain);
			let cardHasChanged;

			if (chain[id].format === 'minimized')
			{
				chain[id].format = 'full';
				cardHasChanged = true;
			}
			else
			{
				chain[id].format = 'minimized';
				cardHasChanged = true;
			}

			if (cardHasChanged)
			{
				this.loadMessage(id);
				this.setState({
					chain,
				});
			}
		}

		setChain(threadId, chain, withRender = true)
		{
			this.messageCount = chain.length;

			this.lastIncomingCardId = null;
			this.lastIncomingTitle = '';
			this.lastIncomingId = this.properties.lastIncomingId;

			chain = chain.map((item, key) => {
				if (Number(item.ID) === Number(this.properties.lastIncomingId))
				{
					this.lastIncomingCardId = key;
					this.lastIncomingTitle = item.SUBJECT;
				}

				item.format = Number(threadId) === Number(item.ID) ? 'full' : 'minimized';

				return item;
			});

			if (!isEqual(this.state.chain, chain))
			{
				if (withRender)
				{
					this.setState({
						id: threadId,
						chain,
					});
				}
				else
				{
					this.state.chain = chain;
				}
			}
		}

		render()
		{
			const chain = this.state.chain;

			let ActionPanelIndentStub = null;
			let ActionPanelView = null;

			if (this.properties.lastIncomingId !== null)
			{
				ActionPanelView = new ActionPanel({
					actions: this.actions,
				});

				ActionPanelIndentStub = new ActionPanel({
					indentStub: true,
				});
			}

			if (chain.length === 0)
			{
				return null;
			}

			const cards = [];

			for (const [i, element] of chain.entries())
			{
				let action = this.cardTouch.bind(this, i);
				action = throttle(action, 500, this);

				cards.push(View(
					{
						testId: 'message-card-in-chain',
						onClick: () => {
							action();
						},
					},
					MessageCard({
						message: element,
						id: i,
						showMenuAction: this.showContextMenu.bind(this),
					}),
				));
			}

			return View(
				{},
				ScrollView(
					{
						style: {
							height: '100%',
							backgroundColor: '#f5f5f5',
						},
					},
					View(
						{},
						View({
							style: {
								flexDirection: 'row',
								flexWrap: 'wrap',
							},
						}),
						...cards,
						ActionPanelIndentStub,
					),
				),
				ActionPanelView,
			);
		}
	}

	module.exports = {
		MessageChain,
	};
});
