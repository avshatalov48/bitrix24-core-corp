/**
 * @module crm/entity-tab/type/entities/base
 */
jn.define('crm/entity-tab/type/entities/base', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { openChat } = require('crm/entity-tab/type/traits/open-chat');

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
					showArrow: true,
					onClickCallback: this.addActivity.bind(this),
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					data: {
						svgIcon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.6171 17.2808L14.317 15.9807L13.398 16.8998L15.5671 19.0689L15.568 19.068L15.618 19.118L19.6529 15.0832L18.7338 14.1641L15.6171 17.2808ZM16.2745 22.3743C13.0686 22.3743 10.4697 19.7754 10.4697 16.5695C10.4697 13.3635 13.0686 10.7646 16.2745 10.7646C19.4804 10.7646 22.0793 13.3635 22.0793 16.5695C22.0793 19.7754 19.4804 22.3743 16.2745 22.3743Z" fill="#6a737f"/><path fill-rule="evenodd" clip-rule="evenodd" d="M14.1416 9.005C14.8197 8.81421 15.535 8.71215 16.2742 8.71215C16.5865 8.71215 16.8947 8.73038 17.1975 8.76583V7.59625C17.1975 7.21625 17.045 6.8525 16.7738 6.585L11.4412 1.31375C11.2388 1.11375 10.9625 1 10.6737 1H2.085C1.485 1 1 1.48 1 2.0725V19.94C1 20.5312 1.485 21.0113 2.085 21.0113H9.79169C9.37261 20.4008 9.03752 19.7281 8.80309 19.01H3.31375C3.15375 19.01 3.025 18.8825 3.025 18.7237V3.2875C3.025 3.13 3.15375 3.00125 3.31375 3.00125H8.81C8.97 3.00125 9.09875 3.13 9.09875 3.2875V8.72C9.09875 8.8775 9.22875 9.005 9.38875 9.005H14.1416ZM10.7254 11.0063H5.41C5.21125 11.0063 5.04875 11.1663 5.04875 11.3638V12.65C5.04875 12.8463 5.21125 13.0075 5.41 13.0075H9.26866C9.64727 12.2644 10.1406 11.5895 10.7254 11.0063ZM8.57176 15.0087H5.41C5.21125 15.0087 5.04875 15.1675 5.04875 15.365V16.6525C5.04875 16.8488 5.21125 17.01 5.41 17.01H8.42887C8.42082 16.8642 8.41674 16.7174 8.41674 16.5696C8.41674 16.0352 8.47009 15.5132 8.57176 15.0087ZM11.2325 3.98375C11.1725 3.98375 11.1237 4.0325 11.1237 4.09V6.89625C11.1237 6.955 11.1725 7.00375 11.2325 7.00375H14.0712C14.1012 7.00375 14.1275 6.9925 14.1488 6.9725C14.1913 6.93 14.1913 6.8625 14.1488 6.82125L11.3088 4.015C11.2887 3.995 11.2612 3.98375 11.2325 3.98375ZM6.64 9.005H5.48375C5.24375 9.005 5.04875 8.8125 5.04875 8.575V7.4325C5.04875 7.195 5.24375 7.00375 5.48375 7.00375H6.64C6.88 7.00375 7.07375 7.195 7.07375 7.4325V8.575C7.07375 8.8125 6.88 9.005 6.64 9.005Z" fill="#6a737f"/></svg>',
					},
					isDisabled: !permissions.update,
				},
			];
			if (this.params.isChatSupported)
			{
				actions.push({
					id: 'chat',
					title: BX.message('M_CRM_ENTITY_TAB_ACTION_CHAT'),
					sort: 1050,
					onClickCallback: openChat.bind(this, { entityTypeId: this.getId() }),
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					data: {
						svgIcon: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.5672 6.14844C18.0699 6.14844 19.2881 7.36662 19.2881 8.86932V15.0831C19.2881 16.5858 18.0699 17.804 16.5672 17.804L11.9749 17.805C10.3779 19.6811 9.4145 20.6515 9.08477 20.716C8.51327 20.8277 8.19352 19.8574 8.12551 17.805L7.4328 17.804C5.9301 17.804 4.71191 16.5858 4.71191 15.0831V8.86932C4.71191 7.36662 5.9301 6.14844 7.4328 6.14844H16.5672ZM16.371 8.15775H7.62902C7.09234 8.15775 6.65727 8.59281 6.65727 9.12949V14.7962C6.65727 15.3329 7.09234 15.7679 7.62902 15.7679H16.371C16.9077 15.7679 17.3427 15.3329 17.3427 14.7962V9.12949C17.3427 8.59281 16.9077 8.15775 16.371 8.15775Z" fill="#525C69"/></svg>',
					},
					sectionCode: 'additional',
					showArrow: true,
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
