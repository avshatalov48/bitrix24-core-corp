/**
 * @module qrauth/src/scanner
 */
jn.define('qrauth/src/scanner', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Notify } = require('notify');
	const { qrauth } = require('qrauth/utils');
	const { AnalyticsEvent } = require('analytics');

	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/qrauth/';

	/**
	 * @class QRCodeScannerComponent
	 */
	class QRCodeScannerComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				ui,
				redirectUrl = '',
				external = false,
				url = null,
				analyticsSection = '',
			} = props;

			this.redirectUrl = redirectUrl;
			this.parentLayout = ui;
			this.analyticsSection = analyticsSection;
			this.url = url;
			this.state = { external };
		}

		handleOnSuccess()
		{
			const { onsuccess } = this.props;

			if (onsuccess)
			{
				onsuccess();
			}
		}

		renderAcceptDialog(resolve)
		{
			const action = new PrimaryButton({});
			action.props = {
				text: Loc.getMessage('ACCEPT_QR_AUTH'),
				style: {
					button: this.getStyle().button,
					text: this.getStyle().buttonText,
				},
				onClick: () => {
					if (resolve)
					{
						resolve(true);
					}
				},
			};

			const cancel = new CancelButton({});
			cancel.props = {
				text: Loc.getMessage('DECLINE_QR_AUTH'),
				style: {
					button: this.getStyle().button,
					text: this.getStyle().buttonText,
				},
				onClick: () => {
					if (resolve)
					{
						resolve(false);
					}
				},
			};

			return View(
				{
					style: {
						justifyContent: 'flex-start',
						alignItems: 'center',
					},
				},
				View(
					{ style: { flex: 1, alignItems: 'center' } },
					View({ style: { marginTop: 28 } }, Image({
						style: {
							opacity: 0.5,
							width: 140,
							height: 140,
						},
						svg: { uri: `${currentDomain}${pathToExtension}images/qr.svg` },
					})),
					View(
						{
							style: {
								margin: 20,
								padding: 20,
							},
						},
						BBCodeText({
							style: {
								fontSize: 19,
								fontWeight: 'bold',
								color: AppTheme.colors.base1,
							},
							value: Loc.getMessage('QR_WARNING').replace('#DOMAIN#', currentDomain),
						}),
					),

					action,
					cancel,
				),
			);
		}

		render()
		{
			const { external = false, accepted = false } = this.state;
			if (external)
			{
				if (accepted === true)
				{
					return View(
						{
							style: {
								justifyContent: 'flex-start',
								alignItems: 'center',
							},
						},
						View({ style: { flex: 1, justifyContent: 'center' } }, Image({
							style: {
								opacity: 0.8,
								width: 200,
								selfAlign: 'center',
								height: 200,
							},
							svg: { uri: `${currentDomain}${pathToExtension}images/qr.svg` },
						})),
						this.successView(),
					);
				}

				return this.renderAcceptDialog((accepted) => {
					if (accepted)
					{
						this.setState({ accepted }, () => {
							if (this.url)
							{
								this.onResult({ value: this.url });
							}
						});
					}
					else
					{
						this.parentLayout.close();
					}
				});
			}

			return View({}, this.cameraView());
		}

		cameraView()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						alignItems: 'center',
						justifyContent: 'center',
						padding: 10,
					},
				},
				CameraView({
					style: {
						borderRadius: 12,
						height: '100%',
						width: '100%',
						borderWidth: 0.5,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						backgroundColor: AppTheme.colors.bgContentTertiary,
					},
					scanTypes: ['qr_code'],
					result: this.onResult.bind(this),
					error: (error) => console.error(error),
					ref: (ref) => {
						this.cameraRef = ref;
					},
				}),
				this.successView(),
			);
		}

		successView()
		{
			return View({
				style: {
					position: 'absolute',
					height: '100%',
					width: '100%',
					opacity: 0,
					borderRadius: 12,
					justifyContent: 'center',
					backgroundColor: AppTheme.colors.accentMainSuccess,
				},
				ref: (view) => {
					this.successOverlay = view;
				},
			}, Image({
				style: {
					alignSelf: 'center',
					alignItems: 'center',
					resizeMode: 'contain',
					width: 180,
					height: 180,
				},
				svg: { uri: `${currentDomain}${pathToExtension}images/success.svg?2` },
			}));
		}

		onResult({ value })
		{
			if (this.cameraRef)
			{
				this.cameraRef.setScanEnabled(false);
			}
			setTimeout(() => {
				// eslint-disable-next-line no-undef
				notify.showIndicatorLoading();
			}, 100);

			qrauth.authorizeByUrl(value, this.redirectUrl)
				.then(() => {
					const event = new AnalyticsEvent({
						tool: 'intranet',
						category: 'activation',
						event: 'auth_complete',
						type: 'auth',
						c_section: this.analyticsSection,
						c_sub_section: 'qrcode',
						p1: 'platform_web',
						p2: `redirectUrl_${this.redirectUrl}`,
						p3: `userId_${env.userId}`,
					});
					event.send();
					this.handleOnSuccess();
					Notify.hideCurrentIndicator();

					if (this.successOverlay)
					{
						this.successOverlay.animate({
							duration: 1000,
							opacity: 0.8,
						});
					}

					setTimeout(() => {
						this.parentLayout.close();
						this.parentLayout = null;
					}, 1000);
				})
				.catch((error) => {
					Notify.showIndicatorError({ text: error.message, hideAfter: 2000 });
					if (this.cameraRef)
					{
						setTimeout(() => this.cameraRef.setScanEnabled(true), 3000);
					}
				});
		}

		getStyle()
		{
			return {
				buttonText: {
					fontSize: 18,
				},
				button: {

					marginTop: 14,
					width: 220,
					height: 54,
				},
			};
		}
	}

	module.exports = {
		QRCodeScannerComponent,
	};
});
