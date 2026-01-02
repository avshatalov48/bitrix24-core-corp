/**
 * @module im/messenger/lib/element/recent/item/chat/channel
 */
jn.define('im/messenger/lib/element/recent/item/chat/channel', (require, exports, module) => {
	const { Type } = require('type');
	const { Theme } = require('im/lib/theme');
	const {
		ComponentCode,
		AnchorType,
	} = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');
	const { RecentItemSectionCode } = require('im/messenger/lib/element/recent/item/base');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Feature } = require('im/messenger/lib/feature');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');

	const { CounterPrefix, CounterValue, CounterPostfix } = require('im/messenger/lib/element/recent/const/test-id');

	/**
	 * @class ChannelItem
	 */
	class ChannelItem extends ChatItem
	{
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);

			this.setSuperEllipseIcon();
		}

		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 * @return RecentItem
		 */
		initParams(modelItem, options)
		{
			super.initParams(modelItem, options);

			this.dialog = this.params.model.dialog;
			const store = serviceLocator.get('core').getStore();
			let postsCountWithCounters = 0;
			if (Feature.isMessengerV2Enabled)
			{
				postsCountWithCounters = store.getters['counterModel/getNumberChildCounters'](this.dialog?.chatId);
			}
			else
			{
				postsCountWithCounters = store
					.getters['commentModel/getPostsCountWithCounters'](this.dialog?.chatId)
				;
			}

			this.params.model = {
				...this.params.model,
				commentsCounter: postsCountWithCounters,
			};

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createUnread()
		{
			if (MessengerParams.getComponentCode() === ComponentCode.imChannelMessenger)
			{
				this.unread = false;

				return this;
			}

			this.unread = this.getModelItem().unread;

			return this;
		}

		/**
		 * @deprecated use to AvatarDetail
		 */
		setSuperEllipseIcon()
		{
			this.isSuperEllipseIcon = true;
			// for native support styles (isSuperEllipseIcon key will be deleted)

			const roundingRadiusByDesign = Theme.corner.M.toNumber();
			const heightIcon = 56;
			const borderPercent = Math.round((roundingRadiusByDesign / heightIcon) * 100);
			this.styles.image = { image: { borderRadius: borderPercent } };
		}

		createMessageCount()
		{
			const showCounter = this.getRenderProperty('showCounter', true);
			if (!showCounter)
			{
				return this;
			}

			const dialog = this.getDialogItem();
			if (!dialog)
			{
				return this;
			}

			const counter = this.getCounter();
			if (counter)
			{
				this.messageCount = counter;
			}
			else if (this.getCommentsCounterItem())
			{
				this.messageCount = this.getCommentsCounterItem();
			}

			return this;
		}

		createCounterStyle()
		{
			const showCounter = this.getRenderProperty('showCounter', true);
			if (!showCounter)
			{
				return this;
			}

			const dialog = this.getDialogItem();
			if (!dialog)
			{
				return this;
			}

			if (this.isMute)
			{
				this.styles.counter.backgroundColor = Theme.colors.base5;

				return this;
			}

			const counter = this.getCounter();

			if (counter > 0)
			{
				this.styles.counter.backgroundColor = Theme.colors.accentMainPrimaryalt;

				return this;
			}

			if (this.getCommentsCounterItem() > 0 && !counter)
			{
				this.styles.counter.backgroundColor = Theme.colors.accentMainSuccess;

				return this;
			}

			if (this.unread)
			{
				this.styles.counter.backgroundColor = Theme.colors.accentMainPrimaryalt;
			}

			return this;
		}

		/**
		 * @return RecentItem
		 */
		createCounterTestId()
		{
			const commentCounters = this.getCommentsCounterItem();
			const dialog = this.getDialogItem();
			const dialogCounters = dialog.counter;

			if (this.messageCount === 0 && !this.unread && commentCounters === 0)
			{
				this.counterTestId = null;

				return this;
			}

			const prefix = CounterPrefix.counter;
			const value = dialogCounters > 0 || commentCounters > 0 ? dialogCounters || commentCounters : CounterValue.unread;

			let postfix = '';
			if (this.isMute)
			{
				postfix = CounterPostfix.muted;
			}
			else if (dialogCounters > 0)
			{
				postfix = CounterPostfix.unmuted;
			}
			else
			{
				postfix = CounterPostfix.watch;
			}

			const dialogId = this.getModelItem().id;
			const suffix = (this.getCommentsCounterItem() > 0 && !dialog.counter)
				? 'comments'
				: 'posts';

			this.counterTestId = `${prefix}-${dialogId}-${suffix}-${value}-${postfix}`;

			return this;
		}

		createActions()
		{
			const showActions = this.getRenderProperty('showActions', true);
			if (!showActions)
			{
				this.actions = [];

				return this;
			}

			this.actions = [
				this.getMuteAction(),
				this.getHideAction(),
				this.getPinAction(),
				this.getReadAction(),
			];

			return this;
		}

		/**
		 * @return {number}
		 */
		getCommentsCounterItem()
		{
			return this.params.model.commentsCounter;
		}

		createCommentsStyle()
		{
			const hasMention = serviceLocator.get('core').getStore().getters['anchorModel/hasAnchorsByType'](this.dialog?.chatId, AnchorType.mention);

			if (
				this.getCommentsCounterItem()
				&& this.getCounter()
				&& !hasMention
			)
			{
				this.styles.comments = {
					backgroundColor: Color.accentMainSuccess.toHex(),
					image: {
						name: Icon.SMALL_MESSAGE_2.getIconName(),
						tintColor: Color.baseWhiteFixed.toHex(),
						contentHeight: 16,
					},
				};
			}

			return this;
		}

		createSectionCode()
		{
			const showPin = this.getRenderProperty('showPin', true);
			if (!showPin)
			{
				this.sectionCode = RecentItemSectionCode.general;

				return this;
			}

			return super.createSectionCode();
		}

		createPinnedStyle()
		{
			const showPin = this.getRenderProperty('showPin', true);
			if (!showPin)
			{
				return this;
			}

			return super.createPinnedStyle();
		}

		needShowLikes()
		{
			const hasMention = serviceLocator.get('core').getStore().getters['anchorModel/hasAnchorsByType'](this.dialog?.chatId, AnchorType.mention);
			const showCounter = Boolean(this.getDialogItem().counter);
			const showComments = this.getCommentsCounterItem() && showCounter && !hasMention;

			let entitiesCount = 0;
			entitiesCount = showCounter ? entitiesCount + 1 : entitiesCount;
			entitiesCount = hasMention ? entitiesCount + 1 : entitiesCount;
			entitiesCount = showComments ? entitiesCount + 1 : entitiesCount;

			if (entitiesCount > 1)
			{
				return false;
			}

			return super.needShowLikes();
		}
	}

	module.exports = { ChannelItem };
});
