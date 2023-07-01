/**
 * @module imconnector/connectors/telegram/controllers/editor
 */
jn.define('imconnector/connectors/telegram/controllers/editor', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { EditView } = require('imconnector/connectors/telegram/view/edit');
	const { TelegramRestManager } = require('imconnector/lib/rest-manager/telegram');

	class TelegramEditor
	{
		constructor()
		{
			this.restManager = new TelegramRestManager();
			this.tmpQueue = [];
		}

		/**
		 *
		 * @param parentWidget
		 * @param {Object} params
		 * @param {TelegramSettings} params.connectorSettings
		 * @param {Function} params.onQueueChange
		 * @param {Function} params.onConnectorDisable
		 */
		open(parentWidget, params)
		{
			if (Type.isArray(params.connectorSettings.users))
			{
				this.show(parentWidget, params);

				return;
			}

			this.restManager.getUsersData(params.connectorSettings.userIds)
				.then((userList) => {
					params.connectorSettings.users = userList;
					this.show(parentWidget, params);
				})
			;
		}

		/**
		 * @private
		 */
		show(parentWidget, params)
		{
			const backdropConfig = {
				onlyMediumPosition: true,
			};

			if (device.screen.height < 710)
			{
				backdropConfig.mediumPositionPercent = 95;
			}
			else
			{
				backdropConfig.mediumPositionHeight = 670;
			}
			this.tmpQueue = params.connectorSettings.users;

			parentWidget = (parentWidget || PageManager);
			parentWidget.openWidget(
				'layout',
				{
					title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_TITLE'),
					backdrop: backdropConfig,
					onReady: (layout) => {
						layout.showComponent(new EditView({
							parentWidget: layout,
							withScroll: device.screen.width < 400,
							connectorSettings: params.connectorSettings,
							onQueueChange: (queue) => this.tmpQueue = queue,
							onConnectorDisable: (settings) => {
								layout.close();
								params.onConnectorDisable(params.connectorSettings);
							},
						}));
					},
				},
			).then((layoutWidget) => {
				if (params.connectorSettings.canEditLine)
				{
					layoutWidget.setRightButtons([{
						id: 'continue',
						color: '#2066B0',
						name: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_SAVE'),
						callback: () => {
							params.connectorSettings.users = this.tmpQueue;
							params.onSave(params.connectorSettings);
							layoutWidget.close();
						},
					}]);
				}

				layoutWidget.enableNavigationBarBorder(false);
			});
		}
	}

	module.exports = { TelegramEditor };
});
