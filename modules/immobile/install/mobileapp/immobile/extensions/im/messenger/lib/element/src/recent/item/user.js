/**
 * @module im/messenger/lib/element/recent/item/user
 */
jn.define('im/messenger/lib/element/recent/item/user', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { merge } = require('utils/object');

	const { RecentItem } = require('im/messenger/lib/element/recent/item/base');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { core } = require('im/messenger/core');

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

			const message = item.message;
			if (message.id === 0)
			{
				this.subtitle = ChatTitle.createFromDialogId(item.id).getDescription();

				return this;
			}

			this.subtitle = message.text;

			return this;
		}

		createSubtitleStyle()
		{
			if (this.checkNeedsBirthdayPlaceholder() === false)
			{
				return this;
			}

			this.styles.subtitle = {
				font: {
					size: '14',
					color: AppTheme.colors.accentSoftElementGreen1,
					useColor: true,
					fontStyle: 'medium',
				},
				cornerRadius: 12,
				backgroundColor: AppTheme.colors.accentSoftGreen2,
				padding: {
					top: 3.5,
					right: 12,
					bottom: 3.5,
					left: 12,
				},
			};

			return this;
		}

		createColor()
		{
			const item = this.getModelItem();
			const user = core.getStore().getters['usersModel/getById'](item.id);
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
			const user = core.getStore().getters['usersModel/getById'](item.id);
			if (user && user.id === core.getUserId())
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

			return core.getStore().getters['recentModel/needsBirthdayPlaceholder'](item.id);
		}

		checkNeedsBirthdayIcon()
		{
			const item = this.getModelItem();

			return core.getStore().getters['recentModel/needsBirthdayIcon'](item.id);
		}

		checkNeedsVacationIcon()
		{
			const item = this.getModelItem();

			return core.getStore().getters['recentModel/needsVacationIcon'](item.id);
		}
	}

	module.exports = {
		UserItem,
	};
});
