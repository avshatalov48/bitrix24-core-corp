/* eslint-disable no-implicit-globals, no-console */

/**
 * @description Removing old event subscriptions after reload();
 */
function destructMessengerIfExist()
{
	const require = jn.require;
	const { Type } = require('type');

	if (Type.isObject(window.messenger) && Type.isFunction(window.messenger.destructor))
	{
		window.messenger.destructor();
		window.messenger = undefined;
	}
}

/**
 * @description Perform actions before the messenger launch.
 * Attention! Adding resource-intensive operations to this method will degrade performance.
 */
function executeBeforeMessengerInit()
{
	const require = jn.require;

	window.tabs?.setActiveItem(BX.componentParameters.get('FIRST_TAB_ID', 'chats'));

	/** region messenger developer tools */
	window.messengerDebug = {};

	window.messengerDebug.showDeveloperMenu = async () => {
		const { showDeveloperMenu } = await requireLazy('im:messenger/lib/dev/menu').catch((error) => {
			console.error('showDeveloperMenu requireLazy error:', error);
		});
		showDeveloperMenu();
	};

	window.messengerDebug.openConsole = async () => {
		const { Console } = await requireLazy('im:messenger/lib/dev/tools');
		Console.open();
	};

	window.messengerDebug.clearDatabaseAndRestart = async () => {
		const { clearDatabaseAndRestart } = await requireLazy('im:messenger/api/cleaning');
		void clearDatabaseAndRestart();
	};

	const { actionTimer } = require('im/messenger/lib/dev/action-timer');
	window.messengerDebug.actionTimer = actionTimer;
	/** endregion messenger developer tools */
}

/**
 * @description messenger application entry point
 * @return {Promise<void>}
 */
async function initMessenger()
{
	const require = jn.require;
	const { Messenger } = require('im/messenger-v2/application/messenger');

	const messenger = new Messenger();
	/** @description messenger object reference for debugging purposes only. */
	window.messenger = messenger;

	return messenger.init();
}

/**
 * @description component entry point
 * @return {Promise<void>}
 */
async function launchComponent()
{
	const require = jn.require;
	const { ActionTimer } = require('im/messenger/lib/dev/action-timer');

	try
	{
		const actionTimer = new ActionTimer();
		actionTimer.start('launchComponent');

		destructMessengerIfExist();
		actionTimer.logDuration('launchComponent', '🗑️ 1. destructMessengerIfExist complete');

		executeBeforeMessengerInit();
		actionTimer.logDuration('launchComponent', '⚡️ 2. executeBeforeMessengerInit complete');

		await initMessenger();
		actionTimer.logDuration('launchComponent', '✅ 3. launchComponent complete');
	}
	catch (error)
	{
		console.error('🚨 launchComponent error', error);
	}
}

void launchComponent();
