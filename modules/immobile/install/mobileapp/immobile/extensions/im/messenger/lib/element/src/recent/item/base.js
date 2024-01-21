/**
 * @module im/messenger/lib/element/recent/item/base
 */
jn.define('im/messenger/lib/element/recent/item/base', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { core } = require('im/messenger/core');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const {
		Path,
		MessageStatus,
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
			};

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
				.createTitleStyle()
				.createSubtitleStyle()
				.createAvatarStyle()
				.createDateStyle()
				.createCounterStyle()
			;
		}

		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 * @return RecentItem
		 */
		initParams(modelItem, options)
		{
			this.params = {
				model: {
					recent: modelItem,
					dialog: this.getDialogById(modelItem.id),
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
			this.title = ChatTitle.createFromDialogId(item.id, {
				showItsYou: true,
			}).getTitle();

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createSubtitle()
		{
			return this;
		}

		/**
		 * @return RecentItem
		 */
		createImageUrl()
		{
			const modelItem = this.getModelItem();
			this.imageUrl = ChatAvatar.createFromDialogId(modelItem.id).getAvatarUrl();

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
			const date = DateHelper.cast(this.getModelItem().message.date, new Date());
			this.date = Math.round(date.getTime() / 1000);

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createDisplayedDate()
		{
			const date = DateHelper.cast(this.getModelItem().message.date, null);
			this.displayedDate = DateFormatter.getRecentFormat(date);

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createMessageCount()
		{
			const dialog = this.getDialogItem();
			if (dialog && dialog.counter)
			{
				this.messageCount = dialog.counter;
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
		createAvatarStyle()
		{
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
			const modelItem = this.getModelItem();
			const dialog = this.getDialogItem();
			let subtitleStyle = {};
			if (dialog.writingList.length > 0)
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

			if (modelItem.message.senderId === core.getUserId())
			{
				subtitleStyle = {
					image: {
						name: 'reply',
						sizeMultiplier: 0.7,
					},
				};

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
			const message = item.message;

			let name = '';
			let url = '';
			let sizeMultiplier = 0.7;

			if (message.senderId === core.getUserId())
			{
				if (item.liked)
				{
					url = this.getImageUrlByFileName('status_reaction.png');
					sizeMultiplier = 1.2;
				}
				else if (message.status === MessageStatus.received)
				{
					name = 'message_send';
				}
				else if (message.status === MessageStatus.error)
				{
					name = 'message_error';
				}
				else if (message.status === MessageStatus.delivered)
				{
					name = 'message_delivered';
				}
				else if (item.pinned)
				{
					name = 'message_pin';
					sizeMultiplier = 0.9;
				}
			}
			else if (item.pinned)
			{
				name = 'message_pin';
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

			this.styles.date = dateStyle;

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createCounterStyle()
		{
			const dialog = this.getDialogItem();
			this.styles.counter.backgroundColor = dialog.muteList.includes(core.getUserId())
				? AppTheme.colors.base5
				: AppTheme.colors.accentMainPrimaryalt
			;

			return this;
		}

		getDialogById(dialogId)
		{
			return core.getStore().getters['dialoguesModel/getById'](dialogId);
		}

		/**
		 * @return {RecentModelState}
		 */
		getModelItem()
		{
			return this.params.model.recent;
		}

		/**
		 * @return {DialoguesModelState}
		 */
		getDialogItem()
		{
			return this.params.model.dialog;
		}

		getMuteAction()
		{
			const dialog = this.getDialogItem();

			return dialog.muteList.includes(core.getUserId()) ? UnmuteAction : MuteAction;
		}

		getHideAction()
		{
			return HideAction;
		}

		getPinAction()
		{
			const item = this.getModelItem();

			return item.pinned === true ? UnpinAction : PinAction;
		}

		getReadAction()
		{
			const item = this.getModelItem();
			const dialog = this.getDialogItem();

			return (item.unread === true || dialog.counter > 0) ? ReadAction : UnreadAction;
		}

		getProfileAction()
		{
			return ProfileAction;
		}

		getImageUrlByFileName(fileName = '')
		{
			return `${Path.toComponents}images/${fileName}`;
		}
	}

	module.exports = {
		RecentItem,
	};
});
