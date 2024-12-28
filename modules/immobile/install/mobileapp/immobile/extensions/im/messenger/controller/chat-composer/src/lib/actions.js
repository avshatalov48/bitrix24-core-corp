/**
 * @module im/messenger/controller/chat-composer/lib/actions
 */
jn.define('im/messenger/controller/chat-composer/lib/actions', (require, exports, module) => {
	const { Loc } = require('loc');

	/**
	 * @param {Omit<SettingsPanelAction, 'testId'>} props
	 * @return {SettingsPanelAction}
	 */
	const dialogTypeAction = (props) => {
		return {
			testId: 'SETTING_ACTION_TYPE',
			title: props.title,
			subtitle: props.subtitle,
			icon: props.icon,
			divider: props.divider ?? true,
			onClick: props.onClick,
		};
	};

	/**
	 * @param {Omit<SettingsPanelAction, 'testId'>} props
	 * @return {SettingsPanelAction}
	 */
	const participantsAction = (props) => {
		return {
			testId: 'SETTING_ACTION_PARTICIPANTS',
			title: props.title,
			subtitle: props.subtitle,
			icon: props.icon,
			divider: props.divider ?? true,
			onClick: props.onClick,
		};
	};

	/**
	 * @param {Omit<SettingsPanelAction, 'testId' | 'title'>} props
	 * @return {SettingsPanelAction}
	 */
	const managersAction = (props) => {
		return {
			testId: 'SETTING_ACTION_MANAGERS',
			title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_MANAGERS_TITLE'),
			subtitle: props.subtitle,
			icon: props.icon,
			divider: props.divider ?? true,
			onClick: props.onClick,
		};
	};

	/**
	 * @param {Omit<SettingsPanelAction, 'testId' | 'title' | 'subtitle'>} props
	 * @return {SettingsPanelAction}
	 */
	const rulesAction = (props) => {
		return {
			testId: 'SETTING_ACTION_RULES',
			title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_RULES_TITLE'),
			subtitle: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SETTING_ACTION_RULES_SUBTITLE'),
			icon: props.icon,
			divider: props.divider ?? true,
			onClick: props.onClick,
		};
	};

	module.exports = {
		dialogTypeAction,
		participantsAction,
		managersAction,
		rulesAction,
	};
});
