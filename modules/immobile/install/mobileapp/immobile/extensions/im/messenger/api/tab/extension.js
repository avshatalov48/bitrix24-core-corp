/**
 * @module im/messenger/api/tab
 */
jn.define('im/messenger/api/tab', (require, exports, module) => {
	const { Type } = require('type');

	const {
		EventType,
		ComponentCode,
	} = require('im/messenger/const');
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils');
	const { getApiVersion } = require('im/messenger/api/api-version');

	/**
	 * @param {TabOptions} options
	 * @return {Promise}
	 */
	async function openChatsTab(options)
	{
		return openTab(ComponentCode.imMessenger, options);
	}

	/**
	 * @param {TabOptions} options
	 * @return {Promise}
	 */
	async function openCopilotTab(options)
	{
		return openTab(ComponentCode.imCopilotMessenger, options);
	}

	/**
	 * @param {TabOptions} options
	 * @return {Promise}
	 */
	async function openChannelsTab(options)
	{
		return openTab(ComponentCode.imChannelMessenger, options);
	}

	/**
	 * @param {TabOptions} options
	 * @return {Promise}
	 */
	async function openCollabsTab(options)
	{
		return openTab(ComponentCode.imCollabMessenger, options);
	}

	/**
	 * @param {TabOptions} options
	 * @return {Promise}
	 */
	async function openLinesTab(options)
	{
		return openTab(ComponentCode.imOpenlinesRecent, options);
	}

	/**
	 * @param {TabOptions} options
	 * @return {Promise}
	 */
	async function openNotificationsTab(options)
	{
		return openTab(ComponentCode.imNotify, options);
	}

	/**
	 * @param {string} tabComponentCode
	 * @param {TabOptions} options
	 * @return {Promise}
	 */
	async function openTab(tabComponentCode, options)
	{
		if (!Object.values(ComponentCode).includes(tabComponentCode))
		{
			const error = new Error(`im: Error changing tab, tab ${tabComponentCode} does not exist.`);

			return Promise.reject(error);
		}

		const apiVersion = await getApiVersion();

		const {
			promise,
			resolve,
			reject,
		} = createPromiseWithResolvers();
		try
		{
			const { sendChangeTabEvent } = require(`im/messenger/api/tab/v${apiVersion}`);

			registerChangeTabResultHandler(
				tabComponentCode,
				resolve,
				reject,
			);

			sendChangeTabEvent(tabComponentCode, options)
				.catch((error) => {
					console.error('Messenger api: openTab error', error);
				})
			;
		}
		catch (error)
		{
			reject(error);
		}

		return promise;
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

	module.exports = {
		openChatsTab,
		openCopilotTab,
		openChannelsTab,
		openCollabsTab,
		openNotificationsTab,
		openLinesTab,
	};
});
