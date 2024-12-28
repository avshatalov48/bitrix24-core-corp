/**
 * @module crm/entity-tab/type/entities/base
 */
jn.define('crm/entity-tab/type/entities/base', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { openChat } = require('crm/entity-tab/type/traits/open-chat');
	const { Icon } = require('ui-system/blocks/icon');

	/**
	 * @class Base
	 */
	class Base
	{
		/**
		 * @param {Object} params
		 */
		constructor(params)
		{
			this.params = params || {};
		}

		/**
		 * @returns {Number}
		 */
		getId()
		{
			abstract();
		}

		/**
		 * @returns {String|Null}
		 */
		getName()
		{
			return null;
		}

		getCategoryId()
		{
			return this.params.categoryId || 0;
		}

		getUserInfo()
		{
			return this.params.userInfo || null;
		}

		/**
		 * @return {{
		 * image: ImageProps,
		 * title: string|Function,
		 * description: string|Function,
		 * }}
		 */
		getEmptyEntityScreenConfig()
		{
			const image = this.getEmptyImage();
			const text = this.getEmptyEntityScreenDescriptionText();
			const title = this.getEmptyScreenTitle();

			return {
				image,
				title,
				description: () => BBCodeText({
					value: text,
					linksUnderline: false,
					style: {
						color: AppTheme.colors.base2,
						fontSize: 15,
						textAlign: 'center',
						lineHeightMultiple: 1.2,
					},
					onLinkClick: (url) => {
						qrauth.open({
							title: Loc.getMessage('M_CRM_ENTITY_TAB_ENTITY_EMPTY_DESCRIPTION_REDIRECT_TITLE'),
							redirectUrl: url,
							layout,
							analyticsSection: 'crm',
						});
					},
				}),
			};
		}

		getEmptyScreenTitle()
		{
			const entityTypeName = (this.getName() || 'COMMON');

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_ENTITY_EMPTY_TITLE2_${entityTypeName}`);
		}

		getEmptyColumnScreenConfig(data)
		{
			const screenConfig = {
				title: this.getEmptyColumnScreenTitle(),
				image: this.getEmptyImage(),
			};

			const { column } = data;
			if (column && column.semantics === 'P')
			{
				screenConfig.description = this.getEmptyColumnScreenDescription();
			}

			return screenConfig;
		}

		getEmptyColumnScreenTitle()
		{
			const entityTypeName = this.getName();

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_COLUMN_EMPTY_${entityTypeName}_TITLE`);
		}

		getEmptyColumnScreenDescription()
		{
			const entityTypeName = this.getName();

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_COLUMN_EMPTY_${entityTypeName}_DESCRIPTION`);
		}

		getUnsuitableStageScreenConfig()
		{
			return {
				title: this.getColumnUnsuitableForFilterTitle(),
				description: this.getColumnUnsuitableForFilterDescription(),
				image: this.getEmptyImage(),
			};
		}

		getColumnUnsuitableForFilterTitle()
		{
			const entityTypeName = this.getName();

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_COLUMN_USUITABLE_FOR_FILTER_TITLE_${entityTypeName}`);
		}

		getColumnUnsuitableForFilterDescription()
		{
			const entityTypeName = this.getName();

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_COLUMN_USUITABLE_FOR_FILTER_DESCRIPTION_${entityTypeName}`);
		}

		getEmptyImage()
		{
			return {
				style: {
					width: 218,
					height: 178,
				},
				svg: {
					uri: this.getPathToIcon(),
				},
			};
		}

		getPathToIcon()
		{
			return EmptyScreen.makeLibraryImagePath(`${this.getIconName()}.svg`, 'crm');
		}

		getIconName()
		{
			return 'kanban';
		}

		/**
		 * @returns {Object[]}
		 */
		getItemActions(permissions)
		{
			const actions = [
				{
					id: 'activity',
					sort: 200,
					title: Loc.getMessage('M_CRM_ENTITY_TAB_ACTION_ACTIVITY'),
					showActionLoader: false,
					onClickCallback: this.addActivity.bind(this),
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					icon: Icon.ADD_TIMELINE,
					isDisabled: !permissions.update,
				},
			];
			if (this.params.isChatSupported)
			{
				actions.push({
					id: 'chat',
					title: BX.message('M_CRM_ENTITY_TAB_ACTION_CHAT'),
					sort: 700,
					onClickCallback: openChat.bind(this, { entityTypeId: this.getId() }),
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					icon: Icon.EMPTY_MESSAGE,
					sectionCode: 'additional',
					isRawIcon: true,
					isDisabled: false, // !permissions.exclude,
				});
			}

			return actions;
		}

		async addActivity(action, itemId, { parentWidget } = {})
		{
			const { TimelineScheduler } = await requireLazy('crm:timeline/scheduler');

			if (parentWidget)
			{
				await new Promise((resolve) => {
					parentWidget.close(resolve);
				});
			}

			await (new TimelineScheduler({
				entity: {
					id: itemId,
					typeId: this.getId(),
					categoryId: this.getCategoryId(),
					reminders: this.params.reminders,
				},
				user: this.getUserInfo(),
			})).openActivityEditor();
		}

		showForbiddenActionNotification()
		{
			const title = Loc.getMessage('M_CRM_ENTITY_TAB_ACTION_FORBIDDEN_TITLE');
			const text = Loc.getMessage('M_CRM_ENTITY_TAB_ACTION_FORBIDDEN_TEXT');

			Notify.showUniqueMessage(text, title, { time: 3 });
		}

		/**
		 * @return {{
		 * image: ImageProps,
		 * title: string|Function,
		 * description: string|Function,
		 * }}
		 */
		getEmptySearchScreenConfig()
		{
			const image = this.getEmptyImage();
			const title = this.getEmptySearchScreenTitle();
			const description = Loc.getMessage('M_CRM_ENTITY_TAB_SEARCH_EMPTY_DESCRIPTION');

			return {
				image,
				title,
				description,
			};
		}

		getEmptySearchScreenTitle()
		{
			const entityTypeName = (this.getName() || 'COMMON');

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_SEARCH_EMPTY_${entityTypeName}_TITLE2`);
		}

		/**
		 * @returns {string}
		 */
		getCommunicationChannelsRedirectUrl()
		{
			return '/contact_center';
		}

		getEmptyEntityScreenDescriptionText()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_ENTITY_EMPTY_DESCRIPTION', {
				'#URL#': this.getCommunicationChannelsRedirectUrl(),
				'#MANY_ENTITY_TYPE_TITLE#': this.getManyEntityTypeTitle(),
				'#SINGLE_ENTITY_TYPE_TITLE#': this.getSingleEntityTypeTitle(),
			});
		}

		getManyEntityTypeTitle()
		{
			const entityTypeName = (this.getName() || 'COMMON');

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_ENTITY_EMPTY_MANY_${entityTypeName}`);
		}

		getSingleEntityTypeTitle()
		{
			const entityTypeName = (this.getName() || 'COMMON');

			return this.getLastMessageVer(`M_CRM_ENTITY_TAB_ENTITY_EMPTY_SINGLE_${entityTypeName}`);
		}

		getMenuActions()
		{
			return [];
		}

		getLastMessageVer(messageCode, verLimit = 5)
		{
			if (Loc.hasMessage(messageCode))
			{
				return Loc.getMessage(messageCode);
			}

			const messageWithVerText = `${messageCode}_MSGVER_`;
			for (let ver = 1; ver <= verLimit; ver++)
			{
				if (Loc.hasMessage(messageWithVerText + ver.toString()))
				{
					return Loc.getMessage(messageWithVerText + ver.toString());
				}
			}

			return null;
		}
	}

	function abstract(msg)
	{
		const message = msg || 'Abstract method must be implemented in child class';

		throw new Error(message);
	}

	module.exports = { Base };
});
