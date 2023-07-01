/**
 * @module imconnector/connectors/telegram/controllers/registrar
 */
jn.define('imconnector/connectors/telegram/controllers/registrar', (require, exports, module) => {
	const { Loc } = require('loc');
	const { RegistryView, stages } = require('imconnector/connectors/telegram/view/registry');
	const { TelegramRestManager } = require('imconnector/lib/rest-manager/telegram');
	class TelegramRegistrar
	{
		/**
		 * @param {?TelegramOpenLine} line
		 */
		constructor(line)
		{
			this.restManager = new TelegramRestManager();

			/** @type RegistryView */
			this.view = null;

			this.token = '';
			this.layoutWidget = null;

			this.isRegistry = false;
			this.connectorSettings = null;
			this.line = line;
			this.readyToRegister = false;
		}

		open(parentWidget, params)
		{
			parentWidget = (parentWidget || PageManager);

			parentWidget.openWidget(
				'layout',
				{
					title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_TITLE'),
					backgroundColor: '#eef2f4',
					backdrop: {
						mediumPositionHeight: RegistryView.getWidgetHeight(),
						onlyMediumPosition: true,
						navigationBarColor: '#eef2f4',
					},
					onReady: (layoutWidget) => {
						this.layoutWidget = layoutWidget;
						layoutWidget.showComponent(
							this.view = new RegistryView({
								layoutWidget,
								bannerIcon: params.bannerIcon,
								onTokenSubmit: (token) => this.token = token,
								onTokenSubmitFromClipboard: (token) => this.onTokenSubmitFromClipboard(token),
							}),
						);
					},
				},
			).then((layoutWidget) => {
				layoutWidget.enableNavigationBarBorder(false);

				layoutWidget.on('onViewRemoved', () => {
					if (this.isRegistry)
					{
						params.onRegistrySuccess(this.connectorSettings);
					}
				});

				layoutWidget.on('onViewHidden', () => {
					this.view.unregisterEventHandler();
				});

				layoutWidget.setRightButtons([{
					id: 'continue',
					color: '#2066B0',
					name: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_CONTINUE'),
					callback: () => {
						if (this.readyToRegister)
						{
							return;
						}
						this.readyToRegister = true;
						this.registry()
							.then((settings) => {
								this.connectorSettings = settings;
								this.changeConnectorStage(stages.complete)
									.then(() => setTimeout(() => this.layoutWidget.close(), 1500))
								;
							})
						;
					},
				}]);
			});
		}

		onTokenSubmitFromClipboard(token)
		{
			this.token = token;

			if (this.readyToRegister)
			{
				return;
			}
			this.readyToRegister = true;

			setTimeout(
				() => {
					this.registry()
						.then((settings) => {
							this.connectorSettings = settings;
							this.changeConnectorStage(stages.complete)
								.then(() => setTimeout(() => this.layoutWidget.close(), 1500))
							;
						})
					;
				},
				1000,
			);
		}

		enableRegistryButton()
		{
			this.isRegistryButtonDisabled = false;

			this.layoutWidget.setRightButtons([{
				id: 'continue',
				color: '#2066B0',
				name: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_CONTINUE'),
				callback: () => {
					this.registry()
						.then((settings) => {
							this.connectorSettings = settings;
							this.changeConnectorStage(stages.complete)
								.then(() => setTimeout(() => this.layoutWidget.close(), 1500))
							;
						})
					;
				},
			}]);
		}

		disableRegistryButton()
		{
			if (!this.isRegistryButtonDisabled)
			{
				this.isRegistryButtonDisabled = true;
				this.layoutWidget.setRightButtons([{
					id: 'continue',
					color: '#BEC2C7',
					name: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_CONTINUE'),
					callback: () => {},
				}]);
			}
		}

		/** @private */
		registry()
		{
			return new Promise((resolve, reject) => {
				this.isRegistry = true;
				Keyboard.dismiss();

				if (!RegistryView.checkToken(this.token))
				{
					this.readyToRegister = false;
					this.isRegistry = false;
					this.view.invalidTokenAlert();
					reject();
					return;
				}

				this.disableRegistryButton();
				this.changeConnectorStage(stages.loader)
					.then(() => this.restManager.registry(this.token, this.line))
					.then((connectorSettings) => resolve(connectorSettings))
					.catch((errors) => this.errorhandler(errors));
			});
		}

		changeConnectorStage(stage)
		{
			return new Promise((resolve, reject) => {
				this.view.setState({ stage }, () => resolve());
			});
		}

		/** @private */
		errorhandler(error)
		{
			this.readyToRegister = false;
			this.isRegistry = false;
			this.enableRegistryButton();
			switch (error.ex.error)
			{
				case errors.invalidToken: {
					this.changeConnectorStage(stages.registry).then(() => this.view.invalidTokenAlert());

					return;
				}
				default: {
					this.changeConnectorStage(stages.registry).then(() => this.view.unknownErrorAlert());
				}
			}
		}
	}

	const errors = {
		invalidToken: 'CONNECTOR_MESSENGER_RETURNED_WITH_AN_ERROR',
	};

	module.exports = { TelegramRegistrar };
});
