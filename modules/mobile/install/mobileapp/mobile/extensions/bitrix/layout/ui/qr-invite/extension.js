/**
 * @module layout/ui/qr-invite
 */
jn.define('layout/ui/qr-invite', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const AppTheme = require('apptheme');
	const { withCurrentDomain } = require('utils/url');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Loc } = require('loc');
	const { Indent, Component } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Text4, Text6 } = require('ui-system/typography/text');
	const { H3 } = require('ui-system/typography/heading');
	const { Avatar, AvatarEntityType, AvatarShape } = require('ui-system/blocks/avatar');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Area } = require('ui-system/layout/area');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Box } = require('ui-system/layout/box');
	const { Alert, ButtonType } = require('alert');
	const { Color } = require('tokens');

	const GRADIENT = {
		start: '#75E4BD',
		middle: '#32B2F4',
		end: '#0B83FF',
		angle: 75,
	};
	const CONTAINER_SIZE = 195;

	const entityTypeMap = {
		collab: {
			entityType: AvatarEntityType.COLLAB,
			shape: AvatarShape.HEXAGON,
			textColor: Color.collabAccentPrimary,
			entityName: Loc.getMessage('M_UI_QR_INVITE_ENTITY_GROUP_COLLAB'),
		},
		extranet: {
			entityType: AvatarEntityType.EXTRANET,
			shape: AvatarShape.CIRCLE,
			textColor: Color.accentMainWarning,
			entityName: '',
		},
		default: {
			entityType: AvatarEntityType.GROUP,
			shape: AvatarShape.CIRCLE,
			textColor: Color.accentBrandBlue,
			entityName: '',
		},
	};

	class QRInvite extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				loadingError: null,
				qrCode: null,
			};
			this.layoutWidget = props.layoutWidget ?? PageManager;
			this.isDarkMode = (AppTheme.id === 'dark');
			this.shimmerRef = null;
			this.entityNameSize ??= Animated.newCalculatedValue2D(0, 0);
			this.marginTop = this.entityNameSize.getValue2().interpolate({
				inputRange: [22, 48],
				outputRange: [98, 120],
			});
			this.height = this.entityNameSize.getValue2().interpolate({
				inputRange: [22, 48],
				outputRange: [329, 339],
			});
		}

		componentDidMount()
		{
			this.#generateQr();
		}

		get uri()
		{
			const { uri } = this.props;

			return withCurrentDomain(uri) || '';
		}

		get entityType()
		{
			return entityTypeMap[this.props.entityType] ?? entityTypeMap.default;
		}

		get entityId()
		{
			return this.props.entityId || null;
		}

		get testId()
		{
			return 'qr-invite';
		}

		#handleQrResponse(response)
		{
			const qrCodeSvg = response.data;
			if (qrCodeSvg)
			{
				const transparentQrCode = this.makeBlackTransparent(qrCodeSvg);

				this.setState({
					qrCode: transparentQrCode,
					loadingError: false,
				});
			}
			else
			{
				this.setState({ loadingError: true });
			}
		}

		#generateQr()
		{
			const executor = new RunActionExecutor(
				'mobile.QrInvite.generateQr',
				{
					url: this.uri,
					isDarkMode: this.isDarkMode,
				},
			)
				.enableJson()
				.setSkipRequestIfCacheExists()
				.setHandler((response) => this.#handleQrResponse(response))
				.setCacheHandler((cache) => this.#handleQrResponse(cache));

			executor.call(true).catch(() => {
				this.setState({ loadingError: true });
			});
		}

		makeBlackTransparent(svgString)
		{
			return svgString.replaceAll('fill="black"', 'fill="none"');
		}

		/**
		 * @param {Object} props
		 * @param {number} props.entityId
		 * @param {string} props.uri
		 * @param {string} props.bottomText
		 * @param {string} [props.entityType]
		 * @param {Object} [props.parentWidget]
		 * @param {string} [props.entityName]
		 * @param {string} [props.avatarUri]
		 * @param {string} [props.title]
		 * @returns {Promise<void>}
		 */
		static open(props)
		{
			const parentWidget = props.parentWidget ?? PageManager;

			new BottomSheet({
				titleParams: {
					text: props.title ?? Loc.getMessage('M_UI_QR_INVITE_TITLE'),
					type: 'dialog',
					useLargeTitleMode: true,
				},
				component: (layoutWidget) => new QRInvite({
					...props,
					layoutWidget,
				}),
			})
				.setParentWidget(parentWidget || PageManager)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setMediumPositionHeight(620, true)
				.open()
				.catch(console.error)
			;
		}

		render()
		{
			return Box(
				{},
				this.renderContent(),
			);
		}

		showErrorMessage()
		{
			return Alert.confirm(
				Loc.getMessage('M_UI_QR_INVITE_ERROR_TITLE'),
				Loc.getMessage('M_UI_QR_INVITE_ERROR_DESCRIPTION'),
				[
					{
						type: ButtonType.DEFAULT,
						onPress: this.closeWidget,
					},
				],
			);
		}

		renderShimmer()
		{
			return View(
				{
					style: {
						width: CONTAINER_SIZE,
						height: CONTAINER_SIZE,
						alignSelf: 'center',
						marginTop: this.marginTop,
					},
				},
				ShimmerView(
					{
						animating: true,
						ref: (ref) => this.shimmerRef = ref,
					},
					View(
						{
							style: {
								backgroundColor: Color.base6.toHex(),
								width: CONTAINER_SIZE,
								height: CONTAINER_SIZE,
								borderRadius: 6,
								borderWidth: 1,
							},
						},
					),
				),
			);
		}

		renderContent()
		{
			return AreaList(
				{},
				View(
					{
						style: {
							flexDirection: 'column',
							justifyContent: 'space-between',
							alignItems: 'center',
						},
					},
					this.renderQrCode(),
					this.renderButton(),
				),
			);
		}

		renderEntityName()
		{
			return View(
				{
					style: {
						width: 255,
						zIndex: 1,
						position: 'absolute',
						top: 58,
						left: 24,
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
					},
					onLayoutCalculated: {
						contentSize: this.entityNameSize,
					},
				},
				this.props.entityName && H3({
					text: this.props.entityName,
					numberOfLines: 2,
					ellipsize: 'end',
					color: Color.base1,
					style: {
						textAlign: 'center',
						width: '100%',
					},
				}),
			);
		}

		renderPreviewImage()
		{
			const avatarParams = this.entityType;

			return View(
				{
					style: {
						zIndex: 1,
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				Avatar({
					testId: `${this.testId}-avatar`,
					id: this.entityId,
					size: 60,
					accent: true,
					entityType: avatarParams.entityType,
					shape: avatarParams.shape,
					uri: this.props.avatarUri ?? null,
					name: this.props.entityName,
				}),
				Text6({
					text: avatarParams.entityName,
					textAlign: 'center',
					color: avatarParams.textColor,
					style: {
						marginTop: 12,
					},
				}),
			);
		}

		renderLoadingOrResult()
		{
			if (this.state.loadingError === null)
			{
				return this.renderShimmer();
			}

			if (this.state.qrCode)
			{
				return this.renderGeneratedQr();
			}

			return this.showErrorMessage();
		}

		renderGeneratedQr()
		{
			return View(
				{
					style: {
						width: CONTAINER_SIZE,
						height: CONTAINER_SIZE,
						alignSelf: 'center',
						marginTop: this.marginTop,
						backgroundColorGradient: GRADIENT,
					},
				},
				View(
					{
						style: {
							width: '100%',
							height: '100%',
							backgroundResizeMode: 'stretch',
							backgroundImageSvg: this.state.qrCode,
							zIndex: 1,
							position: 'absolute',
							top: 0,
							left: 0,
						},
					},
				),
			);
		}

		renderQrCode()
		{
			const fileName = 'qr-background.svg';
			const uri = makeLibraryImagePath(fileName, 'qr-invite');

			return Area(
				{
					style: {
						marginBottom: Indent.XL3.toNumber(),
						marginTop: Indent.XL.toNumber(),
						marginHorizontal: Indent.XL3.toNumber(),
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'flex-start',
						minHeight: 401,
					},
					isFirst: true,
				},
				this.renderPreviewImage(),
				View(
					{
						style: {
							width: 303,
							minHeight: this.height,
							marginTop: -58,
							backgroundImageSvgUrl: uri,
							backgroundResizeMode: 'stretch',
						},
					},
					this.renderEntityName(),
					this.renderLoadingOrResult(),
				),
				this.renderBottomText(),
			);
		}

		renderBottomText()
		{
			const { bottomText } = this.props;

			if (!bottomText)
			{
				return null;
			}

			return Text4({
				text: bottomText,
				color: Color.base2,
				style: {
					textAlign: 'center',
					marginVertical: Indent.XL3.toNumber(),
					maxWidth: 303,
				},
			});
		}

		closeWidget = () => {
			if (this.layoutWidget)
			{
				this.layoutWidget.close();
			}
		};

		renderButton()
		{
			return Button({
				testId: `${this.testId}-button`,
				text: Loc.getMessage('M_UI_QR_INVITE_BUTTON_TEXT'),
				design: ButtonDesign.FILLED,
				size: ButtonSize.L,
				stretched: true,
				style: {
					paddingBottom: Indent.XL.toNumber(),
					paddingHorizontal: Component.paddingLrMore.toNumber(),
				},
				onClick: this.closeWidget,
			});
		}
	}

	module.exports = { QRInvite };
});
