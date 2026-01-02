/**
 * @module layout/ui/qr-invite
 */
jn.define('layout/ui/qr-invite', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const AppTheme = require('apptheme');
	const { withCurrentDomain } = require('utils/url');
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Type } = require('type');
	const { BottomSheet } = require('bottom-sheet');
	const { Text5, Text6 } = require('ui-system/typography/text');
	const { H3 } = require('ui-system/typography/heading');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Area } = require('ui-system/layout/area');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Box, BoxFooter } = require('ui-system/layout/box');
	const { Alert, ButtonType } = require('alert');
	const { QrEntity } = require('layout/ui/qr-invite/src/entity');
	const { makeLibraryImagePath } = require('asset-manager');
	const { createTestIdGenerator } = require('utils/test');

	const CONTAINER_SIZE = 245;
	const QR_SIZE = 229;
	const QR_BORDER_RADIUS = 24;

	const departmentImage = makeLibraryImagePath('department.png', 'department');

	class QRInvite extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				uri: props.uri ?? null,
				loadingError: null,
				qrCode: null,
			};
			this.layoutWidget = props.layoutWidget ?? PageManager;
			this.isDarkMode = (AppTheme.id === 'dark');
			this.shimmerRef = null;
			this.defaultImages = {
				DEPARTMENT: departmentImage,
				COMPANY: departmentImage,
			};
			this.getTestId = createTestIdGenerator({
				prefix: 'qr-invite',
			});
		}

		async componentDidMount()
		{
			await this.#loadUriIfNeeded();
			this.#generateQr();
		}

		get uri()
		{
			const { uri } = this.state;

			return withCurrentDomain(uri) || '';
		}

		get entity()
		{
			const { entityType } = this.props;

			return entityType?.getValue();
		}

		get avatarUri()
		{
			const { entityType, avatarUri } = this.props;

			const entityName = entityType?.getName();
			const defaultImage = this.defaultImages[entityName];

			return avatarUri ?? defaultImage ?? null;
		}

		get isGradientBorder()
		{
			const entity = this.entity;

			return Type.isObject(entity?.borderColor) ?? false;
		}

		#loadUriIfNeeded = () => {
			return new Promise((resolve, reject) => {
				const { uri, loadUri } = this.props;
				if (uri)
				{
					resolve();

					return;
				}

				loadUri?.()
					.then((newUri) => {
						this.setState({ uri: newUri }, () => resolve());
					})
					.catch((e) => {
						console.error(e);
						reject();
					});
			});
		};

		#handleQrResponse(response)
		{
			const qrCodeSvg = response.data;
			if (qrCodeSvg)
			{
				this.setState({
					qrCode: qrCodeSvg,
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

		/**
		 * @param {Object} props
		 * @param {string} props.uri
		 * @param {string} props.entityType
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
					text: props.title ?? '',
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
				{
					footer: BoxFooter(
						{},
						this.renderButton(),
					),
				},
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
					},
				},
				ShimmerView(
					{
						animating: true,
						ref: (ref) => {
							if (ref)
							{
								this.shimmerRef = ref;
							}
						},
					},
					View(
						{
							testId: this.getTestId('code-shimmer'),
							style: {
								backgroundColor: Color.base6.toHex(),
								width: CONTAINER_SIZE,
								height: CONTAINER_SIZE,
								borderRadius: QR_BORDER_RADIUS,
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
				Area(
					{
						style: {
							alignItems: 'center',
							justifyContent: 'flex-start',
						},
					},
					this.renderEntityInfo(),
					View(
						{
							testId: this.getTestId('container'),
							style: {
								marginVertical: 30,
							},
						},
						this.renderLoadingOrResult(),
					),
					this.renderBottomText(),
				),
			);
		}

		renderEntityInfo()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				this.renderAvatar(this.entity),
				this.renderEntityType(this.entity),
				this.renderEntityName(),
			);
		}

		renderAvatar(entityParams)
		{
			return Avatar({
				testId: this.getTestId('avatar'),
				size: 72,
				accent: entityParams?.accent,
				entityType: entityParams?.entityType,
				shape: entityParams?.shape,
				uri: this.avatarUri,
				name: this.props.entityName,
			});
		}

		renderEntityType(entityParams)
		{
			return Text6({
				testId: this.getTestId(`type-${entityParams?.entityName}`),
				text: entityParams?.entityName,
				textAlign: 'center',
				color: entityParams?.textColor,
				style: {
					marginTop: Indent.S.toNumber(),
				},
			});
		}

		renderEntityName()
		{
			const { entityName } = this.props;

			return entityName && H3({
				testId: this.getTestId(`title-${entityName}`),
				text: entityName,
				numberOfLines: 2,
				ellipsize: 'end',
				color: Color.base1,
				style: {
					textAlign: 'center',
					marginTop: Indent.M.toNumber(),
				},
			});
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

		calculateOuterBorderRadius()
		{
			const gap = (CONTAINER_SIZE - QR_SIZE) / 2;

			return QR_BORDER_RADIUS + gap;
		}

		renderGeneratedQr()
		{
			return View(
				{
					style: {
						backgroundColorGradient: this.isGradientBorder ? this.entity?.borderColor : {},
						width: CONTAINER_SIZE,
						height: CONTAINER_SIZE,
						borderRadius: this.calculateOuterBorderRadius(),
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				View({
					testId: this.getTestId('code'),
					style: {
						alignSelf: 'center',
						width: QR_SIZE,
						height: QR_SIZE,
						borderRadius: QR_BORDER_RADIUS,
						backgroundImageSvg: this.state.qrCode,
						backgroundPosition: 'center',
						backgroundResizeMode: 'stretch',
					},
				}),
			);
		}

		renderBottomText()
		{
			const bottomText = this.entity?.bottomText;

			if (!bottomText)
			{
				return null;
			}

			return Text5({
				testId: this.getTestId(`bottom-text-${bottomText}`),
				text: bottomText,
				color: Color.base3,
				style: {
					textAlign: 'center',
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
				testId: this.getTestId('button'),
				text: Loc.getMessage('M_UI_QR_INVITE_BUTTON_TEXT'),
				design: ButtonDesign.OUTLINE,
				size: ButtonSize.L,
				stretched: true,
				style: {
					paddingBottom: Indent.XL.toNumber(),
				},
				onClick: this.closeWidget,
			});
		}
	}

	module.exports = { QRInvite, QrEntity };
});
