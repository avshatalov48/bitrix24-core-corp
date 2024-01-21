/**
 * @module imconnector/connectors/telegram
 */
jn.define('imconnector/connectors/telegram', (require, exports, module) => {
	const { TelegramRegistrar } = require('imconnector/connectors/telegram/controllers/registrar');
	const { TelegramEditor } = require('imconnector/connectors/telegram/controllers/editor');
	const { TelegramRestManager } = require('imconnector/lib/rest-manager/telegram');
	const { NotifyManager } = require('notify-manager');
	const { Type } = require('type');

	/**
	 * @class TelegramConnectorManager
	 */
	class TelegramConnectorManager
	{
		constructor()
		{
			this.restManager = new TelegramRestManager();
		}

		/**
		 * @param {number|null} userId
		 * @return {Promise<boolean>}
		 */
		hasAccess(userId = null)
		{
			return this.restManager.hasAccess(userId);
		}

		/**
		 * @return {Promise<boolean>}
		 */
		isRegistry()
		{
			return new Promise((resolve, reject) => {
				this.load()
					.then((lineList) => resolve(lineList.length > 0))
					.catch((errors) => reject(errors))
				;
			});
		}

		/**
		 * @return {Promise<TelegramSettings | null>}
		 */
		getCurrentLine()
		{
			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();

				this.load()
					.then((lineList) => {
						if (lineList.length > 0)
						{
							resolve(lineList[lineList.length - 1]);

							return;
						}

						resolve(null);
					})
					.catch((errors) => reject(errors))
					.finally(() => NotifyManager.hideLoadingIndicatorWithoutFallback())
				;
			});
		}

		/**
		 * @private
		 * @return {Promise<TelegramSettings[]>}
		 */
		load()
		{
			return new Promise((resolve, reject) => {
				this.restManager.getLineList()
					.then((lineList) => resolve(lineList))
					.catch((error) => reject(error))
				;
			});
		}

		/**
		 * @param {Object} parentWidget
		 * @param {TelegramIcon} bannerIcon
		 * @return {Promise<TelegramSettings>}
		 */
		async openRegistrar(parentWidget = null, bannerIcon = 'toSend')
		{
			const currentLine = await this.getCurrentLine();

			console.log('line with connector', currentLine);
			if (currentLine !== null)
			{
				const currentSettings = await this.restManager.getSettings(currentLine.lineId);

				return currentSettings;
			}

			const registryParams = await this.restManager.getRegistryParams(userId);

			const lineToConnect = registryParams
				.freeLines
				.reverse()
				.find((line) => line.canEditConnector === true)
			;

			if (Type.isUndefined(lineToConnect) && registryParams.permissions.canEditConnector === false)
			{
				throw new function AccessError()
				{
					this.code = 'ACCESS_DENIED';
				}();
			}

			return new Promise((resolve, reject) => {
				const registrar = new TelegramRegistrar(lineToConnect);
				registrar.open(parentWidget, {
					bannerIcon,
					onRegistrySuccess: (connectorSettings) => resolve(connectorSettings),
				});
			});
		}

		/**
		 * @param {Object} parentWidget
		 * @return {Promise<TelegramSettings>}
		 */
		openEditor(parentWidget = null)
		{
			return new Promise((resolve, reject) => {
				this.openRegistrar(parentWidget, 'toEdit')
					.then((connectorSettings) => {
						const editor = new TelegramEditor();
						editor.open(parentWidget, {
							connectorSettings,
							onConnectorDisable: (settings) => this.onConnectorDisable(settings),
							onSave: (settings) => {
								this.onQueueChange(settings)
									.then((result) => resolve(result))
									.catch((errors) => reject(errors))
								;
							},
						});
					})
					.catch((errors) => reject(errors))
				;
			});
		}

		/**
		 * @private
		 * @param {TelegramSettings} connectorSettings
		 */
		onConnectorDisable(connectorSettings)
		{
			this.restManager.disableConnector(connectorSettings.lineId);
		}

		/**
		 * @private
		 * @param {TelegramSettings} connectorSettings
		 * @return {Promise<unknown>}
		 */
		onQueueChange(connectorSettings)
		{
			return new Promise((resolve, reject) => {
				this.restManager.setUsers(connectorSettings.lineId, connectorSettings.users.map((user) => user.id))
					.then((result) => resolve(connectorSettings))
					.catch((error) => reject(error))
				;
			});
		}
	}

	module.exports = { TelegramConnectorManager };
});
