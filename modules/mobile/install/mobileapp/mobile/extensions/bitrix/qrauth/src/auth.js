/**
 * @module qrauth/src/auth
 */
jn.define('qrauth/src/auth', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { BottomSheet } = require('bottom-sheet');
	const { Box, BoxFooter } = require('ui-system/layout/box');
	const { QRCodeScannerComponent } = require('qrauth/src/scanner');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');
	const { Color, Indent, Component } = require('tokens');
	const { Link2 } = require('ui-system/blocks/link');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Area } = require('ui-system/layout/area');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Text2, Text4, Text6, BBCodeText } = require('ui-system/typography');
	const { AnalyticsEvent } = require('analytics');

	const CONTENT_SIZE = {
		height: 144,
		width: 339,
	};
	const PAGE_MANAGER_HEIGHT = 450;

	const DEMO_CONTENT = {
		image: 'qrdemo_v3.png',
		video: 'qrdemo_v1.mp4',
	};

	const cloud = Boolean(jnExtensionData.get('qrauth').cloud);
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/qrauth';

	/**
	 * @typedef {Object} QRCodeAuthProps
	 * @property {boolean} [showHint]
	 * @property {boolean} [showAnimation]
	 * @property {string} [title]
	 * @property {string} [hintText]
	 * @property {string} [redirectUrl]
	 * @property {string} [description]
	 *
	 * @class QRCodeAuthComponent
	 */
	class QRCodeAuthComponent extends LayoutComponent
	{
		/**
		 * @param parentWidget
		 * @param {QRCodeAuthProps} params
		 * @returns {Promise<PageManager>}
		 */
		static async open(parentWidget, params)
		{
			const { title } = params;
			const component = new QRCodeAuthComponent(params);
			const bottomSheet = new BottomSheet({
				titleParams: {
					text: title || Loc.getMessage('LOGIN_ON_DESKTOP_DEFAULT_TITLE_MSGVER_3'),
					type: 'dialog',
				},
				component,
			});

			const positionHeight = QRCodeAuthComponent.getContentHeight() + PAGE_MANAGER_HEIGHT;
			const bottomSheetLayout = await bottomSheet
				.setParentWidget(parentWidget || PageManager)
				.setMediumPositionHeight(positionHeight)
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setBackgroundColor(Color.bgSecondary.toHex())
				.open()
				.catch(console.error);

			component.setParentWidget(bottomSheetLayout);

			return bottomSheetLayout;
		}

		constructor(props)
		{
			super(props);

			this.parentWidget = props.parentWidget;
		}

		componentDidMount()
		{
			const { analyticsSection } = this.props;

			const event = new AnalyticsEvent({
				tool: 'intranet',
				category: 'activation',
				event: 'qr_scanner_shown',
				c_section: analyticsSection,
			});

			event.send();
		}

		render()
		{
			return Box(
				{
					withScroll: true,
					footer: this.renderBoxFooter(),
					safeArea: {
						bottom: true,
					},
					style: {
						paddingVertical: Indent.XL2.toNumber(),
					},
				},
				this.renderDescription(),
				this.renderGuide(),
			);
		}

		renderDescription()
		{
			const { description } = this.props;

			if (!description)
			{
				return null;
			}

			return description;
		}

		renderBoxFooter()
		{
			return BoxFooter(
				{
					safeArea: true,
				},
				Button({
					leftIcon: Icon.CAMERA,
					testId: 'QRAUTH_OPEN_CAMERA_QR',
					stretched: true,
					text: Loc.getMessage('SCAN_QR_BUTTON_MSGVER_2'),
					size: ButtonSize.L,
					onClick: this.onClickScan,
				}),
			);
		}

		static getContentHeight()
		{
			return CONTENT_SIZE.height + Indent.XL3.toNumber();
		}

		renderGuide()
		{
			return AreaList(
				{
					withScroll: false,
				},
				this.renderHint(),
				this.renderDemo(),
				this.renderStepsGuide(),
			);
		}

		renderHint()
		{
			const { showHint, hintText } = this.props;

			if (!showHint)
			{
				return null;
			}

			return Area(
				{
					isFirst: true,
					excludePaddingSide: {
						horizontal: false,
					},
				},
				Card(
					{
						testId: 'QR_SCANNER_HINT',
						design: CardDesign.ACCENT,
					},
					View(
						{
							style: {
								flexShrink: 1,
								flexDirection: 'row',
							},
						},
						Text4({
							text: hintText || Loc.getMessage('QR_SCANNER_HINT_MSGVER_2'),
							color: Color.accentMainPrimaryalt,
							style: {
								flexShrink: 1,
							},
						}),
					),
				),
			);
		}

		renderStepsGuide()
		{
			const steps = this.getStepsGuide();

			return Area(
				{},
				...steps.map((step, index) => {
					const isFirst = index === 0;
					const isLast = index === steps.length - 1;
					const stepNumber = index + 1;

					return this.renderStepGuide({ step, stepNumber, isFirst, isLast });
				}),
			);
		}

		renderStepGuide({ step, stepNumber, isFirst })
		{
			const marginTop = isFirst ? Indent.XL : Indent.XL3;

			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: marginTop.toNumber(),
					},
				},
				this.renderStepRow({ step, stepNumber }),
			);
		}

		renderStepRow({ step, stepNumber })
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						width: '100%',
					},
				},
				this.renderStepBadge({ stepNumber }),
				step,
			);
		}

		renderStepBadge({ stepNumber })
		{
			const badgeSize = 22;

			return View(
				{
					style: {
						width: badgeSize,
						height: badgeSize,
						alignItems: 'center',
						justifyContent: 'center',
						marginRight: Indent.XL.toNumber(),
						borderRadius: Component.elementAccentCorner.toNumber(),
						backgroundColor: Color.accentSoftBlue2.toHex(),
					},
				},
				Text6({
					text: String(stepNumber),
					color: Color.base2,
				}),
			);
		}

		renderDemo()
		{
			const renderDemoContent = cloud
				? this.renderDemoVideo
				: this.renderDemoImage;

			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
					style: {
						alignItems: 'center',
					},
				},
				renderDemoContent(),
			);
		}

		renderDemoImage = () => Image({
			resizeMode: 'contain',
			style: CONTENT_SIZE,
			uri: QRCodeAuthComponent.getDemoImagePath(),
		});

		renderDemoVideo = () => Video({
			style: {
				...CONTENT_SIZE,
				backgroundColor: Color.bgPrimary.toHex(),
			},
			scaleMode: 'fit',
			uri: QRCodeAuthComponent.getDemoVideoPath(),
			enableControls: false,
			loop: true,
		});

		getStepsGuide()
		{
			return [
				this.renderStepOne(),
				this.renderStepTwo(),
				this.renderStepThree(),
			];
		}

		renderStepOne()
		{
			const domain = this.getDomain();

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						flex: 1,
						flexWrap: 'wrap',
					},
				},
				Text2({
					color: Color.base2,
					text: Loc.getMessage('STEP_OPEN_SITE_MSGVER_2'),
					onLinkClick: this.handleOnLinkClick,
					style: {
						marginRight: Indent.XS.toNumber(),
					},
				}),
				Link2({
					testId: 'QR_DOMAIN_LINK',
					text: domain,
					href: domain,
				}),
			);
		}

		renderStepTwo()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				BBCodeText({
					size: 2,
					color: Color.base2,
					value: Loc.getMessage('STEP_PRESS_SELF_HOSTED_MSGVER_2'),
				}),
				this.renderRoundedQRView(),
			);
		}

		renderStepThree()
		{
			return Text2({
				color: Color.base2,
				text: Loc.getMessage('STEP_SCAN'),
			});
		}

		renderRoundedQRView()
		{
			return View(
				{
					style: {
						position: 'relative',
						width: 22,
						height: 22,
						borderWidth: 1,
						alignItems: 'center',
						justifyContent: 'center',
						marginLeft: Indent.XS.toNumber(),
						borderColor: Color.bgSeparatorSecondary.toHex(),
						borderRadius: Component.elementAccentCorner.toNumber(),
					},
				},
				IconView({
					size: 20,
					icon: Icon.QR_CODE,
					color: Color.base4,
					style: {
						position: 'absolute',
						marginLeft: Indent.M.toNumber(),
					},
				}),
			);
		}

		getDomain()
		{
			const regex = /^.+\.(bitrix24\.\w+|br\.\w+)$/i;
			const components = currentDomain.match(regex);

			if (components !== null && components.length === 2)
			{
				return components[1];
			}

			return currentDomain;
		}

		handleOnLinkClick = ({ url }) => {
			console.log({ url });
		};

		getParentWidget()
		{
			return this.parentWidget || PageManager;
		}

		setParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;
		}

		onClickScan = () => {
			this.getParentWidget().openWidget('layout', {
				navigationBarColor: Color.bgSecondary.toHex(),
				title: Loc.getMessage('STEP_CAMERA_TITLE'),
				onReady: (layout) => {
					const { redirectUrl, analyticsSection } = this.props;
					const component = new QRCodeScannerComponent({
						redirectUrl,
						analyticsSection,
						ui: layout,
					});

					layout.showComponent(component);
				},
			});
		};

		/**
		 * @returns {string}
		 */
		static getDemoImagePath()
		{
			return QRCodeAuthComponent.getContentPath('images', DEMO_CONTENT.image);
		}

		/**
		 * @returns {string}
		 */
		static getDemoVideoPath()
		{
			return QRCodeAuthComponent.getContentPath('videos', DEMO_CONTENT.video);
		}

		/**
		 * @param {string} folder
		 * @param {string} name
		 * @returns {string}
		 */
		static getContentPath(folder, name)
		{
			return `${currentDomain}${pathToExtension}/${folder}/${AppTheme.id}/${name}`;
		}
	}

	module.exports = {
		QRCodeAuthComponent,
	};
});
