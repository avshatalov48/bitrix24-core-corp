/**
 * @module im/messenger/lib/element/recent/item/user
 */
jn.define('im/messenger/lib/element/recent/item/user', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { merge } = require('utils/object');

	const { Theme } = require('im/lib/theme');
	const { SubTitleIconType } = require('im/messenger/const');
	const { RecentItem } = require('im/messenger/lib/element/recent/item/base');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class UserItem
	 */
	class UserItem extends RecentItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);
		}

		createTitleStyle()
		{
			if (this.checkNeedsVacationIcon())
			{
				this.styles.title = merge(this.styles.title, {
					image: {
						name: 'name_status_vacation',
					},
				});

				return this;
			}

			if (this.checkNeedsBirthdayIcon())
			{
				this.styles.title = merge(this.styles.title, {
					image: {
						name: 'name_status_birthday',
					},
				});

				return this;
			}

			return this;
		}

		createSubtitle()
		{
			const item = this.getModelItem();
			if (this.checkNeedsBirthdayPlaceholder())
			{
				this.subtitle = Loc.getMessage('IMMOBILE_ELEMENT_RECENT_USER_BIRTHDAY');

				return this;
			}

			const message = this.getItemMessage();
			if (message.id === 0)
			{
				this.subtitle = ChatTitle.createFromDialogId(item.id).getDescription();

				return this;
			}

			this.subtitle = this.getMessageText(item);

			return this;
		}

		createSubtitleStyle()
		{
			if (this.checkNeedsBirthdayPlaceholder())
			{
				this.styles.subtitle = {
					font: {
						size: '14',
						color: Theme.colors.accentExtraGrass,
						useColor: true,
						fontStyle: 'medium',
					},
					singleLine: true,
					cornerRadius: 12,
					backgroundColor: Theme.colors.accentSoftGreen2,
					padding: {
						top: 3.5,
						right: 12,
						bottom: 3.5,
						left: 12,
					},
				};

				return this;
			}

			const message = this.getItemMessage();
			if (message.senderId === serviceLocator.get('core').getUserId())
			{
				let subtitleStyle = {};

				if ([SubTitleIconType.wait, SubTitleIconType.error].includes(message?.subTitleIcon))
				{
					subtitleStyle = { image: { name: message.subTitleIcon, sizeMultiplier: 0.7 } };
				}

				this.styles.subtitle = subtitleStyle;

				return this;
			}

			return this;
		}

		createColor()
		{
			const item = this.getModelItem();
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](item.id);
			if (user)
			{
				this.color = user.color;
			}

			return this;
		}

		createDateStyle()
		{
			super.createDateStyle();

			const item = this.getModelItem();
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](item.id);
			if (user && user.id === serviceLocator.get('core').getUserId())
			{
				this.styles.date.image.name = 'message_delivered';
			}

			return this;
		}

		createActions()
		{
			this.actions = [
				this.getPinAction(),
				this.getReadAction(),
				this.getProfileAction(),
				this.getHideAction(),
			];

			return this;
		}

		checkNeedsBirthdayPlaceholder()
		{
			const item = this.getModelItem();

			return serviceLocator.get('core').getStore().getters['recentModel/needsBirthdayPlaceholder'](item.id);
		}

		checkNeedsBirthdayIcon()
		{
			const item = this.getModelItem();

			return serviceLocator.get('core').getStore().getters['recentModel/needsBirthdayIcon'](item.id);
		}

		checkNeedsVacationIcon()
		{
			const item = this.getModelItem();

			return serviceLocator.get('core').getStore().getters['recentModel/needsVacationIcon'](item.id);
		}
	}

	module.exports = {
		UserItem,
	};
});
