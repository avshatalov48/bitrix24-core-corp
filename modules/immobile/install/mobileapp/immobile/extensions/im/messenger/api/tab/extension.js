/**
 * @module im/messenger/api/tab
 */
jn.define('im/messenger/api/tab', (require, exports, module) => {
	const { Type } = require('type');
	const { EntityReady } = require('entity-ready');

	const {
		EventType,
		ComponentCode,
	} = require('im/messenger/const');

	/**
	 * @return {Promise}
	 */
	async function openChatsTab()
	{
		return openTab(ComponentCode.imMessenger);
	}

	/**
	 * @return {Promise}
	 */
	async function openCopilotTab()
	{
		return openTab(ComponentCode.imCopilotMessenger);
	}

	/**
	 * @return {Promise}
	 */
	async function openChannelsTab()
	{
		return openTab(ComponentCode.imChannelMessenger);
	}

	/**
	 * @return {Promise}
	 */
	async function openCollabsTab()
	{
		return openTab(ComponentCode.imCollabMessenger);
	}

	/**
	 * @return {Promise}
	 */
	async function openLinesTab()
	{
		return openTab(ComponentCode.imOpenlinesRecent);
	}

	/**
	 * @return {Promise}
	 */
	async function openNotificationsTab()
	{
		return openTab(ComponentCode.imNotify);
	}

	/**
	 * @param {string} tabComponentCode
	 * @return {Promise}
	 */
	async function openTab(tabComponentCode)
	{
		if (!Object.values(ComponentCode).includes(tabComponentCode))
		{
			const error = new Error(`im: Error changing tab, tab ${tabComponentCode} does not exist.`);

			return Promise.reject(error);
		}

		await EntityReady.wait('im.navigation');

		const {
			promise,
			resolve,
			reject,
		} = createPromiseWithResolvers();

		registerChangeTabResultHandler(
			tabComponentCode,
			resolve,
			reject,
		);

		sendChangeTabEvent(tabComponentCode);

		return promise;
	}

	function createPromiseWithResolvers()
	{
		let resolvePromise = () => {};

		let rejectPromise = () => {};
		const promise = new Promise((resolve, reject) => {
			resolvePromise = resolve;
			rejectPromise = reject;
		});

		return {
			promise,
			resolve: resolvePromise,
			reject: rejectPromise,
		};
	}

	function registerChangeTabResultHandler(tabComponentCode, successHandler, errorHandler)
	{
		const handler = ({ componentCode, errorText }) => {
			if (componentCode !== tabComponentCode)
			{
				return;
			}

			BX.removeCustomEvent(EventType.navigation.changeTabResult, handler);

			if (Type.isStringFilled(errorText))
			{
				errorHandler(new Error(errorText));

				return;
			}

			successHandler();
		};

		BX.addCustomEvent(EventType.navigation.changeTabResult, handler);

		return handler;
	}

	function sendChangeTabEvent(tabComponentCode)
	{
		BX.postComponentEvent(
			EventType.navigation.changeTab,
			[tabComponentCode],
			ComponentCode.imNavigation,
		);
	}

	module.exports = {
		openChatsTab,
		openCopilotTab,
		openChannelsTab,
		openCollabsTab,
		openNotificationsTab,
		openLinesTab,
	};
});
