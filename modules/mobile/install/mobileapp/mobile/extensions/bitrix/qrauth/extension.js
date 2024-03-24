(function() {
	include('SharedBundle');

	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');

	function getDomain()
	{
		const domain = currentDomain;
		const regex = /^.+\.(bitrix24\.\w+|br\.\w+)$/i;
		const components = domain.match(regex);
		if (components != null && components.length === 2)
		{
			return components[1];
		}

		return domain;
	}

	const cloud = Boolean(this.jnExtensionData.get('qrauth').cloud);
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/qrauth/';
	const styles = {
		guideNumber: {
			textAlign: 'center',
			borderRadius: 13,
			backgroundColor: AppTheme.colors.accentSoftBlue1,
			fontSize: 14,
			fontWeight: 'bold',
			color: AppTheme.colors.base1,
			width: 26,
			height: 26,
			marginRight: 10,
		},
		hint: {
			height: 54,
			paddingLeft: 24,
			backgroundColor: AppTheme.colors.accentSoftBlue1,
		},
		browserNote: {
			opacity: 0.5,
			color: AppTheme.colors.base0,
			fontSize: 12,
		},
		guideView: {
			marginTop: 12,
			marginLeft: 18,
			marginRight: 18,
			marginBottom: 30,
		},
		guideTitle: {
			fontSize: 18,
			color: AppTheme.colors.base0,
		},
		hintView: {
			flex: 1,
			alignItems: 'center',
			flexDirection: 'row',
		},
		hintImage: {
			width: 23,
			height: 23,
		},
		hintText: {
			flex: 1,
			fontSize: 15,
			color: AppTheme.colors.base1,
			marginLeft: 18,
		},
	};
	const guideStepsTitles = [
		BX.message('STEP_OPEN_SITE_MSGVER_1').replace('#DOMAIN#', getDomain()),
		BX.message(cloud ? 'STEP_PRESS_CLOUD' : 'STEP_PRESS_SELF_HOSTED').replace(
			'#URL#',
			`${currentDomain}${pathToExtension}images/qrinline.png`,
		),
		BX.message('STEP_SCAN'),
	];

	class QRCodeGuide extends LayoutComponent
	{
		constructor({ showHint, hintText })
		{
			super({ showHint, hintText });

			this.state.showHint = Boolean(showHint);
			this.state.hintText = hintText || '';
		}

		render()
		{
			const { showHint, hintText } = this.state;

			return View(
				{
					backgroundColor: AppTheme.colors.bgContentPrimary,
				},
				showHint && this.hint(hintText),
				View(
					{
						style: styles.guideView,
					},
					Text({
						style: styles.guideTitle,
						text: `${BX.message('QR_HOW_TO_AUTH_MSGVER_1')}↓`,
					}),
					(cloud ? this.demoVideo() : null),
					this.guideSteps(guideStepsTitles),
				),
			);
		}

		guideSteps(points)
		{
			return View({
				style: {
					marginTop: 12,
				},
			}, ...points.map((text, index) => this.guidePoint(index + 1, text, index + 1 < points.length)));
		}

		demoVideo()
		{
			return View(
				{
					style: {
						height: 144,
						borderRadius: 6,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
					},
				},
				Video(
					{
						style: {
							height: 144,
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
						onReadyPlay: () => {
							console.log('can play');
						},
						scaleMode: 'fit',
						uri: sharedBundle.getVideo('demo.mp4'),
						enableControls: false,
						loop: true,
					},
				),
			);
		}

		hint(hintText)
		{
			return View(
				{
					style: styles.hint,
				},
				View(
					{
						style: styles.hintView,
					},
					Image({
						resizeMode: 'contain',
						style: styles.hintImage,
						svg: {
							uri: `${currentDomain}${pathToExtension}images/hint.svg?2`,
						},
					}),
					Text({
						style: styles.hintText,
						text: hintText || BX.message('QR_SCANNER_HINT_MSGVER_1'),
					}),
				),
			);
		}

		guidePoint(number, text, showBorder = false)
		{
			return View(
				{
					style: {
						height: 40,
						alignItems: 'center',
						flexDirection: 'row',
						justifyContent: 'flex-start',
					},
				},
				Text({
					style: styles.guideNumber,
					text: String(number),
				}),
				View(
					{ style: { flex: 1, justifyContent: 'center' } },
					View(
						{ style: { justifyContent: 'center', height: 40 } },
						BBCodeText({
							style: { fontSize: 15, color: AppTheme.colors.base1 },
							value: text,
						}),
					),
					showBorder ? View({
						style: {
							height: 1,
							backgroundColor: AppTheme.colors.bgSeparatorPrimary,
						},
					}) : null,
				),
			);
		}
	}

	/**
	 * @class QRCodeAuthComponent
	 */
	class QRCodeAuthComponent extends LayoutComponent
	{
		static open(parentWidget, { redirectUrl, showHint, hintText, title, description })
		{
			parentWidget.openWidget('layout', {
				backdrop: {
					bounceEnable: true,
					mediumPositionHeight: 500,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				title,
				onReady: (layout) => {
					layout.showComponent(
						new QRCodeAuthComponent({
							redirectUrl,
							showHint,
							hintText,
							parentWidget: layout,
						}, description),
					);
				},
				onError: (error) => console.log(error),
			});
		}

		/**
		 *
		 * @param props
		 * @param {LayoutComponent} description
		 */
		constructor({ redirectUrl, showHint, hintText, parentWidget }, description)
		{
			super({ redirectUrl, showHint });
			this.description = description;
			this.redirectUrl = redirectUrl || '';
			this.showHint = Boolean(showHint);
			this.hintText = hintText || '';
			this.parent = parentWidget || PageManager;
		}

		render()
		{
			return ScrollView(
				{
					style:
						{
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							paddingBottom: 60,
						},
						safeArea: {
							bottom: true,
						},
					},
					this.description,
					new QRCodeGuide({
						showHint: this.showHint,
						hintText: this.hintText,
					}),
					this.scanButton(),
				),
			);
		}

		scanButton()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignSelf: 'center',
						borderColor: AppTheme.colors.accentSoftBlue1,
						borderRadius: 6,
						borderWidth: 1,
						backgroundColor: {
							default: AppTheme.colors.bgContentPrimary,
							pressed: AppTheme.colors.base7,
						},
						height: 40,
						width: 284,
						alignItems: 'center',
					},
					onClick: () => {
						this.parent.openWidget('layout', {
							navigationBarColor: AppTheme.colors.bgSecondary,
							title: BX.message('STEP_CAMERA_TITLE'),
							onReady: (ui) => {
								const component = new QRCodeScannerComponent({ redirectUrl: this.redirectUrl, ui });
								ui.showComponent(component);
							},
						});
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
						},
					},
					Image({
						resizeMode: 'contain',
						style: {
							alignSelf: 'center',
							alignItems: 'center',
							width: 20,
							height: 20,
						},
						svg: { uri: `${currentDomain}${pathToExtension}images/photo.svg?2` },
					}),
					Text({
						style: { fontSize: 17, color: AppTheme.colors.base2, marginLeft: 8, fontWeight: '500' },
						text: BX.message('SCAN_QR_BUTTON'),
					}),
				),
			);
		}
	}

	/**
	 * @class QRCodeScannerComponent
	 */
	class QRCodeScannerComponent extends LayoutComponent
	{
		constructor(props)
		{
			const {
				ui,
				redirectUrl = '',
				external = false,
				url = null,
				onsuccess = function() {},
			} = props;
			super(props);

			this.redirectUrl = redirectUrl;
			this.ui = ui;
			this.onsuccess = onsuccess;
			this.url = url;
			this.state = { external };
		}

		renderAcceptDialog(resolve)
		{
			const { styles } = require('qrauth/src/styles');
			const action = new PrimaryButton({});
			action.props = {
				text: BX.message('ACCEPT_QR_AUTH'),
				style: {
					button: styles.button,
					text: styles.buttonText,
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
				text: BX.message('DECLINE_QR_AUTH'),
				style: { button: styles.button, text: styles.buttonText },
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
							value: BX.message('QR_WARNING').replace('#DOMAIN#', currentDomain),
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
						this.ui.close();
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
				notify.showIndicatorLoading();
			}, 100);

			qrauth.authorizeByUrl(value, this.redirectUrl)
				.then(() => {
					this.onsuccess();
					Notify.hideCurrentIndicator();

					if (this.successOverlay)
					{
						this.successOverlay.animate({
							duration: 1000,
							opacity: 0.8,
						});
					}

					setTimeout(() => {
						this.ui.close();
						this.ui = null;
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
	}

	jnexport(QRCodeAuthComponent, QRCodeScannerComponent);
})();
