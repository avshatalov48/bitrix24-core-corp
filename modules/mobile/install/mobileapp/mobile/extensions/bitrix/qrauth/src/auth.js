/**
 * @module qrauth/src/auth
 */
jn.define('qrauth/src/auth', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { Box, BoxFooter } = require('ui-system/layout/box');
	const { QRCodeScannerComponent } = require('qrauth/src/scanner');
	const { Button, ButtonSize } = require('ui-system/form/buttons/button');
	const { Color, Indent, Component, Corner } = require('tokens');
	const { Link2 } = require('ui-system/blocks/link');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text2, Text4, Text6, H3, BBCodeText } = require('ui-system/typography');

	const contentHeight = 144;
	const pageManagerHeight = 450;
	const cloud = Boolean(jnExtensionData.get('qrauth').cloud);
	const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/qrauth/';

	/**
	 * @class QRCodeAuthComponent
	 */
	class QRCodeAuthComponent extends LayoutComponent
	{
		static async open(parentWidget, params)
		{
			const { title } = params;
			const component = new QRCodeAuthComponent(params);
			const bottomSheet = new BottomSheet({
				titleParams: {
					text: title || Loc.getMessage('QRAUTH_AUTH_TITLE'),
					type: 'dialog',
				},
				component,
			});

			const positionHeight = QRCodeAuthComponent.getContentHeight() + pageManagerHeight;
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

		render()
		{
			return Box(
				{
					withScroll: true,
					withPaddingHorizontal: true,
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
					text: Loc.getMessage('SCAN_QR_BUTTON'),
					size: ButtonSize.L,
					onClick: this.onClickScan,
				}),
			);
		}

		static getContentHeight()
		{
			return contentHeight + Indent.XL3.toNumber();
		}

		renderGuide()
		{
			return View(
				{},
				this.renderHint(),
				this.renderHeader(),
				this.renderAnimation(),
				this.renderDemoVideo(),
				this.renderDemoImage(),
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

			return Card(
				{
					testId: 'QR_SCANNER_HINT',
					design: CardDesign.ACCENT,
					style: {
						marginBottom: Indent.XL3.toNumber(),
					},
				},
				View(
					{
						style: {
							flexShrink: 1,
							flexDirection: 'row',
						},
					},
					IconView({
						size: 24,
						icon: Icon.INFO_CIRCLE,
						color: Color.accentMainPrimaryalt,
						style: {

							marginRight: Indent.M.toNumber(),
						},
					}),
					Text4({
						text: hintText || Loc.getMessage('QR_SCANNER_HINT_MSGVER_1'),
						color: Color.accentMainPrimaryalt,
						style: {
							flexShrink: 1,
						},
					}),
				),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginBottom: Indent.XL3.toNumber(),
					},
				},
				H3({
					text: Loc.getMessage('QR_HOW_TO_AUTH_MSGVER_1'),
				}),
				IconView({
					icon: Icon.ARROW_DOWN,
					size: 20,
					color: Color.base4,
				}),
			);
		}

		renderAnimation()
		{
			const { showAnimation } = this.props;

			if (!showAnimation)
			{
				return null;
			}

			return View({
				style: {
					marginBottom: Indent.XL3.toNumber(),
				},
			});
		}

		renderStepsGuide()
		{
			const steps = this.getStepsGuide();

			return View(
				{},
				...steps.map((step, index) => {
					const isFirst = index === 0;
					const isLast = index === steps.length - 1;
					const stepNumber = index + 1;

					return this.renderStepGuide({ step, stepNumber, isFirst, isLast });
				}),
			);
		}

		renderStepGuide({ step, stepNumber, isFirst, isLast })
		{
			const badgeSize = 20;

			return View(
				{},
				View(
					{
						style: {
							flexDirection: 'row',
							marginTop: isFirst ? 0 : Indent.L.toNumber(),
						},
					},
					this.renderStepRow({ step, stepNumber, badgeSize }),
				),
				this.renderDivider({ isLast, badgeSize }),
			);
		}

		renderStepRow({ step, stepNumber, badgeSize })
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						width: '100%',
					},
				},
				this.renderStepBadge({ stepNumber, badgeSize }),
				step,
			);
		}

		renderStepBadge({ stepNumber, badgeSize })
		{
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

		renderDivider({ isLast, badgeSize })
		{
			if (isLast)
			{
				return null;
			}

			return View({
				style: {
					height: 1,
					width: '100%',
					marginLeft: badgeSize + Indent.XL.toNumber(),
					marginTop: Indent.XL.toNumber(),
					backgroundColor: Color.bgSeparatorSecondary.toHex(),
				},
			});
		}

		renderDemoImage()
		{
			if (cloud)
			{
				return null;
			}

			return this.renderContentWrapper(
				Image({
					style: {
						width: '100%',
						height: contentHeight,
					},
					uri: `${currentDomain}${pathToExtension}images/qrdemo.png`,
				}),
			);
		}

		renderDemoVideo()
		{
			if (!cloud)
			{
				return null;
			}

			return this.renderContentWrapper(
				Video(
					{
						style: {
							height: contentHeight,
							backgroundColor: Color.bgPrimary.toHex(),
						},
						scaleMode: 'fit',
						// eslint-disable-next-line no-undef
						uri: sharedBundle.getVideo('demo.mp4'),
						enableControls: false,
						loop: true,
					},
				),
			);
		}

		renderContentWrapper(content)
		{
			return View(
				{
					style: {
						height: contentHeight,
						borderRadius: Corner.S.toNumber(),
						marginBottom: Indent.XL3.toNumber(),
					},
				},
				content,
			);
		}

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
					value: Loc.getMessage('STEP_PRESS_SELF_HOSTED'),
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
						width: 24,
						height: 24,
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
					const { redirectUrl } = this.props;
					const component = new QRCodeScannerComponent({
						redirectUrl,
						ui: layout,
					});

					layout.showComponent(component);
				},
			});
		};
	}

	module.exports = {
		QRCodeAuthComponent,
	};
});
