/**
 * @module im/messenger/lib/element/recent/item/chat/channel
 */
jn.define('im/messenger/lib/element/recent/item/chat/channel', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { ComponentCode } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatItem } = require('im/messenger/lib/element/recent/item/chat');
	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

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

			const dialog = this.params.model.dialog;

			this.params.model = {
				...this.params.model,
				commentsCounter: serviceLocator.get('core').getStore()
					.getters['commentModel/getChannelCounters'](dialog?.chatId),
			};

			return this;
		}

		/**
		 * @deprecated use to AvatarDetail
		 */
		setSuperEllipseIcon()
		{
			this.isSuperEllipseIcon = true;
			// for native support styles (isSuperEllipseIcon key will be deleted)
			if (Feature.isAvatarBorderStylesSupported)
			{
				const roundingRadiusByDesign = Theme.corner.M.toNumber();
				const heightIcon = 56;
				const borderPercent = Math.round((roundingRadiusByDesign / heightIcon) * 100);
				this.styles.image = { image: { borderRadius: borderPercent } };
			}
		}

		createMessageCount()
		{
			if (MessengerParams.getComponentCode() === ComponentCode.imChannelMessenger)
			{
				return this;
			}

			const dialog = this.getDialogItem();
			if (!dialog)
			{
				return this;
			}

			if (dialog.counter)
			{
				this.messageCount = dialog.counter;
			}

			this.messageCount += this.getCommentsCounterItem();

			return this;
		}

		createCounterStyle()
		{
			if (MessengerParams.getComponentCode() === ComponentCode.imChannelMessenger)
			{
				return this;
			}

			const dialog = this.getDialogItem();
			if (!dialog)
			{
				return this;
			}

			if (dialog?.muteList?.includes(serviceLocator.get('core').getUserId()))
			{
				this.styles.counter.backgroundColor = Theme.colors.base5;

				return this;
			}

			if (dialog.counter > 0)
			{
				this.styles.counter.backgroundColor = Theme.colors.accentMainPrimaryalt;

				return this;
			}

			if (this.getCommentsCounterItem() > 0)
			{
				this.styles.counter.backgroundColor = Theme.colors.accentMainSuccess;

				return this;
			}

			return this;
		}

		createActions()
		{
			if (!this.params.options.isNeedShowActions)
			{
				this.actions = [];

				return this;
			}

			this.actions = [
				this.getMuteAction(),
				this.getHideAction(),
				this.getPinAction(),
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
	}

	module.exports = { ChannelItem };
});
