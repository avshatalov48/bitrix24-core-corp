(() => {
	const { TelegramConnectorManager } = jn.require('imconnector/connectors/telegram');
	const { NotificationServiceConsent } = jn.require('imconnector/consents/notification-service');
	const { CompleteButton } = jn.require('imconnector/lib/ui/buttons/complete');
	const { Alert } = jn.require('alert');
	class TelegramConnectorTest extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.manager = new TelegramConnectorManager();
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',

					},
				},
				View(
					{
						style: {
							marginTop: 15,
							flexDirection: 'row',
							alignItems: 'space-between',
							width: '100%',
							justifyContent: 'space-evenly',
						},
					},
					Button({
						style: {
							minWidth: 80,
							borderWidth: 1,
							borderColor: '#000',
						},
						text: 'registration + edit',
						onClick: () => this.openRegistryWidget(),
					}),
					Button({
						style: {
							minWidth: 80,
							borderWidth: 1,
							borderColor: '#000',
						},
						text: 'registration + send',
						onClick: () => this.openRegistrationPlusSendWidget(),
					}),
				),

			);
		}

		openRegistrationPlusSendWidget()
		{
			this.manager.openRegistrar(this.props.layout, 'toSend')
				.then((connectorSettings) => {
					this.openSendingComponent(connectorSettings);
				})
				.catch((error) => Alert.alert(
					error.message,
					error.code,
					'Ok',
				))
			;
		}

		openRegistryWidget()
		{
			this.manager.openEditor(this.props.layout).catch((error) => Alert.alert(
				error.message,
				error.code,
				'Ok',
			));
		}

		openSendingComponent(data)
		{
			this.props.layout.openWidget(
				'layout',
				{

					backdrop: {
						hideNavigationBar: true,
						mediumPositionPercent: 60,
						onlyMediumPosition: true,
					},
					onReady: (layoutWidget) => {
						layoutWidget.showComponent(
							this.view = new TestSendMessage({
								connectorSettings: data,
								layoutWidget,
							}),
						);
					},
				},
			);
		}
	}

	BX.onViewLoaded(() => {
		layout.showComponent(new TelegramConnectorTest({
			layout,
		}));
	});

	class TestSendMessage extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'column',
					},
				},
				Text(
					{
						text: 'send SMS message',
					},
				),
				CompleteButton({
					withoutIcon: true,
					text: 'Send',
					borderRadius: 20,
					width: 165,
					height: 42,
					onClick: () => {
						this.send();
					},
				}),
			);
		}

		sendMessage(result)
		{
			if (result)
			{
				Alert.alert(
					'emulating send message',
					null,
					'Ok',
				);
			}
		}

		send()
		{
			const consent = new NotificationServiceConsent();

			consent.open(this.props.layoutWidget)
				.then((result) => this.sendMessage(result))
			;
		}
	}
})();
