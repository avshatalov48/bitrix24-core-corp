/**
 * @module im/messenger/lib/element/recent/item/base
 */
jn.define('im/messenger/lib/element/recent/item/base', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Color } = require('tokens');
	const { Type } = require('type');
	const { Theme } = require('im/lib/theme');
	const { Uuid } = require('utils/uuid');
	const { merge } = require('utils/object');
	const { Icon } = require('assets/icons');
	const { Feature } = require('im/messenger/lib/feature');
	const { DraftType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const {
		Path,
		MessageStatus,
		AnchorType,
	} = require('im/messenger/const');
	const {
		PinAction,
		UnpinAction,
		ReadAction,
		UnreadAction,
		MuteAction,
		UnmuteAction,
		ProfileAction,
		HideAction,
	} = require('im/messenger/lib/element/recent/item/action/action');
	const {
		CounterValue,
		CounterPostfix,
	} = require('im/messenger/lib/element/recent/const/test-id');
	const { parser } = require('im/messenger/lib/parser');

	const RecentItemSectionCode = Object.freeze({
		pinned: 'pinned',
		general: 'general',
	});

	/**
	 * @class RecentItem
	 */
	class RecentItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			this.id = 0;
			this.title = 'title';
			this.subtitle = 'subtitle';
			this.imageUrl = '';
			this.color = '';
			this.backgroundColor = '';
			this.date = 0;
			this.displayedDate = '';
			this.messageCount = 0;
			this.unread = false;
			this.sectionCode = '';
			this.sortValues = {};
			this.menuMode = '';
			this.actions = [];
			this.params = {};
			this.styles = {
				title: {
					font: {
						fontStyle: 'semibold',
						color: ChatTitle.createFromDialogId(modelItem.id).getTitleColor(),
						useColor: true,
					},
					additionalImage: {},
				},
				subtitle: {},
				avatar: {},
				date: {
					image: {
						sizeMultiplier: 0.7,
						name: '',
					},
				},
				counter: {},
				pin: {},
			};
			this.isSuperEllipseIcon = false;
			this.counterTestId = '';

			this
				.initParams(modelItem, options)
				.createId()
				.createTitle()
				.createSubtitle()
				.createImageUrl()
				.createColor()
				.createBackgroundColor()
				.createDate()
				.createDisplayedDate()
				.createMessageCount()
				.createUnread()
				.createSectionCode()
				.createSortValues()
				.createMenuMode()
				.createActions()
				.createParams()
				.createMutedTitleStyle()
				.createTitleStyle()
				.createSubtitleStyle()
				.createDraftRecent()
				.createAvatar()
				.createAvatarStyle()
				.createDateStyle()
				.createMentionStyle()
				.createCommentsStyle()
				.createCounterStyle()
				.createLikesStyle()
				.createPinnedStyle()
				.createCounterTestId()
			;
		}

		/**
		 * @return {RecentWidgetItem}
		 */
		toRecentWidgetItem()
		{
			return {
				id: this.id,
				title: this.title,
				subtitle: this.subtitle,
				avatar: this.avatar,
				imageUrl: this.imageUrl,
				color: this.color,
				backgroundColor: this.backgroundColor,
				date: this.date,
				displayedDate: this.displayedDate,
				messageCount: this.messageCount,
				counterTestId: this.counterTestId,
				unread: this.unread,
				sectionCode: this.sectionCode,
				sortValues: this.sortValues,
				menuMode: this.menuMode,
				actions: this.actions,
				params: this.params,
				styles: this.styles,
				isSuperEllipseIcon: this.isSuperEllipseIcon,
			};
		}

		/**
		 * @return Boolean
		 */
		get isMute()
		{
			return Boolean(this.getDialogItem()?.muteList?.includes(serviceLocator.get('core').getUserId()));
		}

		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 * @return RecentItem
		 */
		initParams(modelItem, options)
		{
			const dialog = this.getDialogById(modelItem.id);

			let counter = 0;
			if (Feature.isMessengerV2Enabled)
			{
				counter = serviceLocator.get('core').getStore().getters['counterModel/getCounterByChatId'](dialog?.chatId);
			}
			else
			{
				counter = dialog?.counter ?? 0;
			}

			this.params = {
				model: {
					recent: modelItem,
					dialog,
					counter,
				},
				options,
				id: modelItem.id,
				type: modelItem.type,
				useLetterImage: true,
			};

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createId()
		{
			this.id = this.getModelItem().id;

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createTitle()
		{
			const item = this.getModelItem();
			const title = ChatTitle.createFromDialogId(item.id, {
				showItsYou: true,
			}).getTitle();

			if (title)
			{
				this.title = title;
			}

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createSubtitle()
		{
			return this;
		}

		createDraftRecent()
		{
			const showDraft = this.getRenderProperty('showDraft', true);
			if (!showDraft)
			{
				return this;
			}

			if (!this.hasDraft())
			{
				return this;
			}

			const { text: draftText } = this.getDraftModel();
			const draftPrefix = (text) => {
				return Feature.isChatDialogListSupportsSubtitleBbCodes
					? `[COLOR=${Color.accentMainAlert.toHex()}]${text}[/COLOR]`
					: text;
			};

			this.subtitle = `${draftPrefix(Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DRAFT_SUBTITLE_PREFIX'))} ${draftText}`.trim();
			this.styles.subtitle = {
				image: null,
				font: null,
				cornerRadius: null,
				backgroundColor: null,
				padding: null,
			};

			return this;
		}

		/**
		 * @deprecated use to AvatarDetail
		 * @return RecentItem
		 */
		createImageUrl()
		{
			const item = this.getModelItem();
			this.imageUrl = ChatAvatar.createFromDialogId(item.id).getAvatarUrl();

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createColor()
		{
			return this;
		}

		/**
		 * @return RecentItem
		 */
		createBackgroundColor()
		{
			return this;
		}

		/**
		 * @return RecentItem
		 */
		createDate()
		{
			const getOrder = this.getRenderProperty('getOrder');
			if (Type.isFunction(getOrder))
			{
				this.date = getOrder(this);

				return this;
			}

			const date = DateHelper.cast(this.getItemDate(), new Date());
			this.date = Math.round(date.getTime() / 1000);

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createDisplayedDate()
		{
			const getDisplayedDate = this.getRenderProperty('getDisplayedDate');
			if (Type.isFunction(getDisplayedDate))
			{
				this.displayedDate = getDisplayedDate(this);

				return this;
			}

			const date = DateHelper.cast(this.getItemDate(), null);
			this.displayedDate = DateFormatter.getRecentFormat(date);

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createMessageCount()
		{
			const dialog = this.getDialogItem();

			const counter = this.getCounter();
			if (counter)
			{
				this.messageCount = counter;
			}

			if (dialog)
			{
				const chatId = dialog.chatId;
				const hasMentions = serviceLocator.get('core').getStore().getters['anchorModel/hasAnchorsByType'](
					chatId,
					AnchorType.mention,
				);

				this.messageCount = (counter === 1 && hasMentions) ? 0 : counter;
			}

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createUnread()
		{
			this.unread = this.getModelItem().unread;

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createSectionCode()
		{
			this.sectionCode = this.getModelItem().pinned
				? RecentItemSectionCode.pinned
				: RecentItemSectionCode.general
			;

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createSortValues()
		{
			this.sortValues = {
				order: this.date,
			};

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createMenuMode()
		{
			this.menuMode = 'dialog';

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createActions()
		{
			return this;
		}

		/**
		 * @return RecentItem
		 */
		createParams()
		{
			return this;
		}

		/**
		 * @return RecentItem
		 */
		createAvatar()
		{
			const modelItem = this.getModelItem();
			this.avatar = ChatAvatar.createFromDialogId(modelItem.id).getRecentItemAvatarProps();

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createAvatarStyle()
		{
			if (Feature.isImageInRecentAvatarStyleAvailable)
			{
				const isMessagesAutoDeleteEnabled = this.getDialogHelper()?.isMessagesAutoDeleteDelayEnabled;

				if (isMessagesAutoDeleteEnabled)
				{
					this.styles.avatar = {
						image: {
							name: Icon.TIMER_DOT.getIconName(),
							tintColor: Color.base2.toHex(),
						},
					};
				}
			}

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createMutedTitleStyle()
		{
			const isMuted = this.getDialogHelper()?.isMuted;

			if (isMuted)
			{
				this.styles.title = merge(this.styles.title, {
					additionalImage: {
						name: 'name_status_mute',
					},
				});
			}

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createTitleStyle()
		{
			return this;
		}

		/**
		 * @return RecentItem
		 */
		createSubtitleStyle()
		{
			const message = this.getItemMessage();
			const dialog = this.getDialogItem();
			let subtitleStyle = {};

			const hasInputAction = Type.isArrayFilled(dialog?.inputActions);
			if (hasInputAction)
			{
				subtitleStyle = {
					animation: {
						color: '#777777',
						type: 'bubbles',
					},
				};

				this.styles.subtitle = subtitleStyle;

				return this;
			}

			if (message.senderId === serviceLocator.get('core').getUserId())
			{
				subtitleStyle = {
					image: {
						name: Icon.REPLY.getIconName(),
						sizeMultiplier: 0.7,
					},
				};

				if (message?.subTitleIcon && message?.subTitleIcon !== '')
				{
					subtitleStyle = { image: { name: message.subTitleIcon, sizeMultiplier: 0.7 } };
				}

				this.styles.subtitle = subtitleStyle;

				return this;
			}

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createDateStyle()
		{
			const item = this.getModelItem();
			const message = this.getItemMessage();
			const isMessageFromCurrentUser = message.senderId === serviceLocator.get('core').getUserId();

			let name = '';
			let url = '';
			let sizeMultiplier = 0.7;
			let tintColor = '';

			const chatId = this.getDialogItem()?.chatId;
			const liked = serviceLocator.get('core').getStore().getters['anchorModel/hasAnchorsByType'](
				chatId,
				AnchorType.reaction,
			);

			if (liked && !Feature.isRecentLikeAvailable)
			{
				url = this.getImageUrlByFileName('status_reaction.png');
				name = Icon.HEART.getIconName();
				tintColor = Color.accentMainAlert.toHex();
				sizeMultiplier = 1.2;
			}
			else if (isMessageFromCurrentUser && !this.hasDraft())
			{
				switch (message.status)
				{
					case MessageStatus.received: {
						name = Icon.CHECK.getIconName();
						tintColor = Color.base4.toHex();

						break;
					}

					case MessageStatus.error: {
						name = 'message_error';
						break;
					}

					case MessageStatus.delivered: {
						name = Icon.DOUBLE_CHECK.getIconName();
						tintColor = Color.accentMainPrimaryalt.toHex();

						break;
					}

					default:
					{
						if (item.pinned && !Feature.isPinInRecentStyleAvailable)
						{
							name = Icon.PIN.getIconName();
							sizeMultiplier = 0.9;
						}
					}
				}
			}
			else if (item.pinned && !Feature.isPinInRecentStyleAvailable)
			{
				name = Icon.PIN.getIconName();
				sizeMultiplier = 0.9;
			}
			else
			{
				return this;
			}

			const dateStyle = {
				image: {
					sizeMultiplier,
					url,
				},
			};

			if (name !== '')
			{
				dateStyle.image.name = name;
			}

			if (tintColor !== '')
			{
				dateStyle.image.tintColor = tintColor;
			}

			this.styles.date = dateStyle;

			return this;
		}

		createMentionStyle()
		{
			const chatId = this.getDialogItem()?.chatId;

			const hasMention = serviceLocator.get('core').getStore().getters['anchorModel/hasAnchorsByType'](
				chatId,
				AnchorType.mention,
			);

			if (hasMention)
			{
				this.styles.mentions = {
					backgroundColor: Color.accentMainPrimary.toHex(),
					image: {
						name: Icon.SMALL_MENTION.getIconName(),
						tintColor: Color.baseWhiteFixed.toHex(),
						contentHeight: 16,
					},
				};
			}

			return this;
		}

		needShowLikes()
		{
			const dialog = this.getDialogItem();
			const counter = dialog?.counter ?? 0;
			const chatId = dialog?.chatId;
			const liked = serviceLocator.get('core').getStore().getters['anchorModel/hasAnchorsByType'](chatId, AnchorType.reaction);
			const hasMentions = serviceLocator.get('core').getStore().getters['anchorModel/hasAnchorsByType'](chatId, AnchorType.mention);

			if (!liked || (counter > 1 && hasMentions))
			{
				return false;
			}

			return true;
		}

		createLikesStyle()
		{
			if (!Feature.isRecentLikeAvailable || !this.needShowLikes())
			{
				return this;
			}

			this.styles.like = {
				backgroundColor: Color.accentMainAlert.toHex(),
				image: {
					name: Icon.HEART.getIconName(),
					tintColor: Color.baseWhiteFixed.toHex(),
					contentHeight: 16,
				},
			};

			return this;
		}

		/**
		 * @returns {RecentItem}
		 */
		createCommentsStyle()
		{
			return this;
		}

		/**
		 * @return RecentItem
		 */
		createCounterStyle()
		{
			this.styles.counter.backgroundColor = this.isMute ? Theme.colors.base5 : Theme.colors.accentMainPrimaryalt;

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createPinnedStyle()
		{
			const item = this.getModelItem();
			if (Feature.isPinInRecentStyleAvailable && item.pinned)
			{
				this.styles.pin = {
					image: {
						name: Icon.PIN.getIconName(),
						tintColor: Color.base4.toHex(),
					},
				};
			}

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createCounterTestId()
		{
			if (this.messageCount === 0 && !this.unread)
			{
				this.counterTestId = null;

				return this;
			}

			const prefix = 'chatListElement';
			const dialogId = this.getModelItem().id;
			const suffix = 'messages';
			const value = this.messageCount > 0 ? this.messageCount : CounterValue.unread;
			const postfix = this.isMute ? CounterPostfix.muted : CounterPostfix.unmuted;

			this.counterTestId = `${prefix}-${dialogId}-${suffix}-${value}-${postfix}`;

			return this;
		}

		/**
		 * @return {?DialoguesModelState}
		 */
		getDialogById(dialogId)
		{
			return serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);
		}

		/**
		 * @return {RecentModelState}
		 */
		getModelItem()
		{
			return this.params.model.recent;
		}

		/**
		 * {number} dialogId
		 * @return {?UsersModelState}
		 */
		getUserModelByDialogId(dialogId)
		{
			return serviceLocator.get('core').getStore().getters['usersModel/getById'](dialogId);
		}

		/**
		 * @return {DialogHelper}
		 */
		getDialogHelper()
		{
			return DialogHelper.createByDialogId(this.getModelItem().id);
		}

		/**
		 * @returns {Date}
		 */
		getItemDate()
		{
			const item = this.getModelItem();
			const uploadingLastActivityDate = item.uploadingState?.lastActivityDate;
			const draftLastActivityDate = this.getDraftModel()?.lastActivityDate;

			return draftLastActivityDate ?? uploadingLastActivityDate ?? item.lastActivityDate;
		}

		/**
		 * @returns {Date}
		 */
		getMessageDate()
		{
			const item = this.getModelItem();

			return DateHelper.cast(item.message?.date, new Date());
		}

		/**
		 * @returns {RecentMessage}
		 */
		getItemMessage()
		{
			const item = this.getModelItem();

			return item.uploadingState?.message ?? item.message;
		}

		/**
		 * @returns {MessagesModelState|null|{}}
		 */
		getModelMessage()
		{
			const message = this.getItemMessage();

			return Uuid.isV4(message?.id)
				? serviceLocator.get('core').getStore().getters['messagesModel/getByTemplateId'](message.id)
				: serviceLocator.get('core').getStore().getters['messagesModel/getById'](message.id);
		}

		/**
		 * @return {number}
		 */
		getCounter()
		{
			return this.params.model.counter;
		}

		/**
		 * @param {RecentModelState} [item=this.getModelItem()]
		 * @return {string}
		 */
		getMessageText(item = this.getModelItem())
		{
			const message = this.getItemMessage();
			const modelMessage = this.getModelMessage();

			const id = modelMessage?.id || modelMessage?.templateId;

			let messageText = '';
			if (id)
			{
				const messageFiles = serviceLocator.get('core').getStore().getters['messagesModel/getMessageFiles'](id);

				messageText = parser.simplify({
					text: modelMessage.text,
					attach: modelMessage?.params?.ATTACH ?? false,
					files: messageFiles,
					showFilePrefix: false,
				});
			}
			else
			{
				messageText = parser.simplify({
					text: message.text,
					attach: message?.params?.withAttach ?? false,
					files: message?.params?.withFile ?? false,
					showFilePrefix: false,
				});
			}

			return messageText;
		}

		/**
		 * @return {DraftModelState}
		 */
		getDraftModel()
		{
			return serviceLocator.get('core').getStore().getters['draftModel/getById'](this.id);
		}

		hasDraft()
		{
			const draft = this.getDraftModel();

			return draft && (draft.type !== DraftType.text || draft.text);
		}

		/**
		 * @return {DialoguesModelState || undefined}
		 */
		getDialogItem()
		{
			return this.params.model.dialog;
		}

		/**
		 * @return {RecentWidgetItemAction}
		 */
		getMuteAction()
		{
			return this.isMute ? UnmuteAction : MuteAction;
		}

		/**
		 * @return {RecentWidgetItemAction}
		 */
		getHideAction()
		{
			return HideAction;
		}

		/**
		 * @return {RecentWidgetItemAction}
		 */
		getPinAction()
		{
			const item = this.getModelItem();

			return item.pinned === true ? UnpinAction : PinAction;
		}

		/**
		 * @return {RecentWidgetItemAction}
		 */
		getReadAction()
		{
			const item = this.getModelItem();
			const counter = this.getCounter();

			return (item.unread === true || counter > 0) ? ReadAction : UnreadAction;
		}

		/**
		 * @return {RecentWidgetItemAction}
		 */
		getProfileAction()
		{
			return ProfileAction;
		}

		getImageUrlByFileName(fileName = '')
		{
			return `${Path.toExtensions}assets/common/png/${fileName}`;
		}

		/**
		 * @param {keyof CommonRenderServiceProps['itemOptions']} name
		 * @param defaultValue
		 * @returns {*|null}
		 */
		getRenderProperty(name, defaultValue = null)
		{
			if (Type.isNil(this.params.options?.[name]))
			{
				return defaultValue;
			}

			return this.params.options[name];
		}
	}

	module.exports = {
		RecentItem,
		RecentItemSectionCode,
	};
});
