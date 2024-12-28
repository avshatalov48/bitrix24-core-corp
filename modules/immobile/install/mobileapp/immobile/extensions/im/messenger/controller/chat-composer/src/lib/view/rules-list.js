/**
 * @module im/messenger/controller/chat-composer/lib/view/rules-list
 */
jn.define('im/messenger/controller/chat-composer/lib/view/rules-list', (require, exports, module) => {
	const { Area } = require('ui-system/layout/area');
	const { Card } = require('ui-system/layout/card');
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');
	const { isEqual } = require('utils/object');
	const { UserRole } = require('im/messenger/const');
	const { StageSelector, Icon } = require('ui-system/blocks/stage-selector');
	const { UIMenu } = require('layout/ui/menu');
	const { DialogPermissions } = require('im/messenger/const');
	const { DialogType } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('chat-composer--managers-rules-list');

	/**
	 * @class RulesListView
	 * @typedef {LayoutComponent<RulesListViewProps, RulesListViewState>} RulesListView
	 */
	class RulesListView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {RulesListViewProps} props
		 */
		constructor(props)
		{
			super(props);

			this.state = this.props.permissions;
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			return !isEqual(this.props, nextProps) || !isEqual(this.state, nextState);
		}

		render()
		{
			return Area(
				{ isFirst: true },
				Card(
					{ border: true, excludePaddingSide: { all: true } },
					this.renderAddUserRuleSelector(),
					this.renderDivider(),
					this.renderRemoveUserRuleSelector(),
					this.renderDivider(),
					...this.renderEntityRuleSelectors(),
				),
			);
		}

		renderEntityRuleSelectors()
		{
			if (this.isChatType())
			{
				return this.renderChatRuleSelectors();
			}

			return this.renderChannelRuleSelectors();
		}

		renderChatRuleSelectors()
		{
			return [
				this.renderChangeDecorRuleSelector(),
				this.renderDivider(),
				this.renderSendMessageRuleSelector(),
			];
		}

		renderChannelRuleSelectors()
		{
			return [this.renderAddPublicationRuleSelector()];
		}

		renderAddUserRuleSelector()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.selectorAddRef = ref;
						}
					},
				},
				StageSelector({
					title: this.getAddUserRuleSelectorTitle(),
					subtitle: this.getLocByPermissions(this.state.manageUsersAdd),
					leftIconColor: '',
					rightIcon: Icon.CHEVRON_DOWN_SIZE_M,
					onClick: () => this.onRuleClick(
						DialogPermissions.manageUsersAdd,
						this.state.manageUsersAdd,
						this.selectorAddRef,
					),
				}),
			);
		}

		renderRemoveUserRuleSelector()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.selectorRemoveRef = ref;
						}
					},
				},
				StageSelector({
					title: this.getRemoveUserRuleSelectorTitle(),
					subtitle: this.getLocByPermissions(this.state.manageUsersDelete),
					leftIconColor: '',
					rightIcon: Icon.CHEVRON_DOWN_SIZE_M,
					onClick: () => this.onRuleClick(
						DialogPermissions.manageUsersDelete,
						this.state.manageUsersDelete,
						this.selectorRemoveRef,
					),
				}),
			);
		}

		renderChangeDecorRuleSelector()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.selectorChangeDecorRef = ref;
						}
					},
				},
				StageSelector({
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_CHANGE_DECOR_RULE_TITLE'),
					subtitle: this.getLocByPermissions(this.state.manageUi),
					leftIconColor: '',
					rightIcon: Icon.CHEVRON_DOWN_SIZE_M,
					onClick: () => this.onRuleClick(
						DialogPermissions.manageUi,
						this.state.manageUi,
						this.selectorChangeDecorRef,
					),
				}),
			);
		}

		renderSendMessageRuleSelector()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.selectorSendMessageRef = ref;
						}
					},
				},
				StageSelector({
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_SEND_MESSAGE_RULE_TITLE'),
					subtitle: this.getLocByPermissions(this.state.manageMessages),
					leftIconColor: '',
					rightIcon: Icon.CHEVRON_DOWN_SIZE_M,
					onClick: () => this.onRuleClick(
						DialogPermissions.manageMessages,
						this.state.manageMessages,
						this.selectorSendMessageRef,
					),
				}),
			);
		}

		renderAddPublicationRuleSelector()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.selectorAddPublicationRef = ref;
						}
					},
				},
				StageSelector({
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ADD_PUBLICATION_RULE_TITLE'),
					subtitle: this.getLocByPermissions(this.state.manageMessages),
					leftIconColor: '',
					rightIcon: Icon.CHEVRON_DOWN_SIZE_M,
					onClick: () => this.onRuleClick(
						DialogPermissions.manageMessages,
						this.state.manageMessages,
						this.selectorAddPublicationRef,
					),
				}),
			);
		}

		renderDivider()
		{
			return View({
				style: {
					width: '100%',
					bottom: 0,
					borderBottomWidth: 1,
					borderBottomColor: Theme.color.bgSeparatorPrimary.toHex(),
				},
			});
		}

		/**
		 * @return {boolean}
		 */
		isChatType()
		{
			return [DialogType.chat, DialogType.open].includes(this.props.dialogType);
		}

		getAddUserRuleSelectorTitle()
		{
			return this.isChatType()
				? Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_GROUP_CHAT_ADD_USER_RULE_TITLE')
				: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_CHANNEL_ADD_USER_RULE_TITLE');
		}

		getRemoveUserRuleSelectorTitle()
		{
			return this.isChatType()
				? Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_GROUP_CHAT_REMOVE_USER_RULE_TITLE')
				: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_CHANNEL_REMOVE_USER_RULE_TITLE');
		}

		/**
		 * @return {string} permission
		 * @return {string}
		 */
		getLocByPermissions(permission)
		{
			switch (permission)
			{
				case UserRole.guest:
				case UserRole.member:
					return this.isChatType()
						? Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_ALL_PARTICIPANTS')
						: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_ALL_SUBSCRIBERS');
				case UserRole.manager:
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_OWNER_AND_MANAGERS');
				case UserRole.owner:
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_ONLY_OWNER');
				default:
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_ALL_PARTICIPANTS');
			}
		}

		/**
		 * @param {string} rule
		 * @param {UserRole} userRule
		 * @param {LayoutComponent} ref
		 */
		onRuleClick(rule, userRule, ref)
		{
			this.openPopupMenu(rule, userRule, ref);
		}

		/**
		 * @param {string} rule
		 * @param {UserRole} userRole
		 * @param {LayoutComponent} ref
		 */
		openPopupMenu(rule, userRole, ref)
		{
			logger.log(`${this.constructor.name}.openPopupMenu current role:`, userRole);
			const menu = new UIMenu(this.prepareActionsData(rule, userRole));
			menu.show({ target: ref });
		}

		/**
		 * @param {string} rule
		 * @param {UserRole} userRole
		 * @return {Array<object>}
		 */
		prepareActionsData(rule, userRole)
		{
			const titleActionAll = this.isChatType()
				? Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_ALL_PARTICIPANTS')
				: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_ALL_SUBSCRIBERS');

			const actions = [
				{
					id: UserRole.member,
					title: titleActionAll,
					onItemSelected: () => this.changeUserRoleHandler(rule, UserRole.member),
					showIcon: false,
					testId: 'CHAT_COMPOSER_RULES_LIST_CONTEXT_MENU_ALL',
				},
				{
					id: UserRole.manager,
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_OWNER_AND_MANAGERS'),
					onItemSelected: () => this.changeUserRoleHandler(rule, UserRole.manager),
					showIcon: false,
					testId: 'CHAT_COMPOSER_RULES_LIST_CONTEXT_MENU_OWNER_AND_MANAGERS',
				},
				{
					id: UserRole.owner,
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_RULES_LIST_ROLE_ONLY_OWNER'),
					onItemSelected: () => this.changeUserRoleHandler(rule, UserRole.owner),
					showIcon: false,
					testId: 'CHAT_COMPOSER_RULES_LIST_CONTEXT_MENU_ONLY_OWNER',
				},
			];

			actions.forEach((action) => {
				if (action.id === userRole)
				{
					// eslint-disable-next-line no-param-reassign
					action.checked = true;
					// eslint-disable-next-line no-param-reassign
					action.onItemSelected = () => logger.log(`${this.constructor.name}.onItemSelected: the item has already been selected`);
				}
			});

			return actions;
		}

		/**
		 * @param {string} rule
		 * @param {UserRole} userRole
		 * @void
		 */
		async changeUserRoleHandler(rule, userRole)
		{
			this.props.callbacks.onChangeUserRoleInRule(rule, userRole);
		}
	}

	module.exports = { RulesListView };
});
