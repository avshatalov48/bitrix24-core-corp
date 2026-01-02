/**
 * @module im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/task
 */
jn.define('im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/task', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');

	const { WelcomeScreen } = require('im/messenger-v2/lib/widget/chat-recent/welcome-screen');

	/**
	 * @class TaskWelcomeScreen
	 */
	class TaskWelcomeScreen
	{
		constructor()
		{
			this.welcomeScreen = WelcomeScreen.create()
				.setUpperText(Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_TASK_TITLE'))
				.setLowerText(Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_TASK_TEXT'))
				.setIconName('ws_employees') // TODO: task-tab add empty state button
			;
		}

		toChatRecentWidgetItem()
		{
			return this.welcomeScreen.toChatRecentWidgetItem();
		}
	}

	module.exports = TaskWelcomeScreen;
});
