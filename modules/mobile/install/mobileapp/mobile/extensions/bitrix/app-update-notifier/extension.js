/**
 * @module app-update-notifier
 */
jn.define('app-update-notifier', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Indent, Color, Component } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { H1 } = require('ui-system/typography/heading');
	const { Text3, Text2 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { openStore } = require('utils/store');

	const RELATIVE_PATH = `${currentDomain}/bitrix/mobileapp`;
	const IMAGE_PATH = `${RELATIVE_PATH}/mobile/extensions/bitrix/assets`;

	const PATH_TO_EXTENSION = `${RELATIVE_PATH}/mobile/extensions/bitrix/app-update-notifier`;
	const VIDEO_PATH = `${PATH_TO_EXTENSION}/videos/${AppTheme.id}`;

	const CONTENT_SIZE = {
		width: 208,
		height: 162,
	};

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class AppUpdateNotifier
	 */
	class AppUpdateNotifier extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
		}

		get #style()
		{
			return this.props.style || {};
		}

		get #isOldBuild()
		{
			return this.props.isOldBuild ?? true;
		}

		get #defaultFeatures()
		{
			const collabIcon = isAndroid ? Icon.GROUP : Icon.COLLAB;
			const quantityIcon = isAndroid ? Icon.APPS : Icon.QUANTITY;

			return [
				{ icon: collabIcon, text: Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_1_V2') },
				{ icon: Icon.COPILOT, text: Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_2_V2') },
				{ icon: Icon.FILE, text: Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_3_V2') },
				{ icon: Icon.SPEAKER, text: Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_4_V2') },
				{ icon: Icon.CHATS_WITH_CHECK, text: Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_5_V2') },
				{ icon: Icon.BOTTOM_MENU, text: Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_6_V2') },
				{ icon: quantityIcon, text: Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_7_V2') },
			];
		}

		get #features()
		{
			return this.props.features || this.#defaultFeatures;
		}

		#getTestId(suffix = '')
		{
			const { testId = '' } = this.props;

			const base = testId || 'app-update-notifier';

			return `${base}-${suffix}`;
		}

		/**
		 * @param {Object} [props]
		 * @param {string} [props.title]
		 * @param {string} [props.testId]
		 * @param {boolean} [props.isOldBuild]
		 * @param {Object} [props.style]
		 * @param {string} [props.text]
		 * @param {Array} [props.features]
		 * @param {Object} [parentWidget]
		 */
		static open(props = {}, parentWidget = PageManager)
		{
			const isOldBuild = props.isOldBuild ?? true;
			const mediumHeight = isOldBuild ? 470 : 0;
			const mediumPercent = isOldBuild ? 0 : 100;

			const bottomSheet = new BottomSheet({
				modal: true,
				titleParams: {
					text: props.title ?? '',
					type: 'dialog',
				},
				component: (widget) => new AppUpdateNotifier({
					...props,
					layout: widget,
				}),
			})
				.setParentWidget(parentWidget)
				.setMediumPositionHeight(mediumHeight)
				.setMediumPositionPercent(mediumPercent)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.disableContentSwipe();

			if (!isOldBuild)
			{
				bottomSheet.hideNavigationBar()
					.hideNavigationBarBorder();
			}

			void bottomSheet.open();
		}

		/**
		 * @returns {string}
		 */
		static #getVideoPath()
		{
			return `${VIDEO_PATH}/app-update-anim.mp4`;
		}

		render()
		{
			const elements = this.#isOldBuild
				? [
					this.renderVideo(),
					this.renderText(),
				]
				: [
					this.renderConfetti(),
					this.renderVideo(),
					this.renderHeaderText(),
					this.renderFeatureList(),
				];

			return Box(
				{
					withScroll: true,
					style: this.getContainerStyle(),
					footer: BoxFooter(
						{},
						this.renderUpdateButton(),
					),
				},
				Area(
					{
						style: {
							width: '100%',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							paddingHorizontal: Component.areaPaddingLr.toNumber(),
							marginBottom: Indent.XS.toNumber(),
							marginTop: !this.#isOldBuild && isAndroid ? 15 : 0,
						},
					},
					...elements,
				),
			);
		}

		getContainerStyle()
		{
			const style = {
				flexDirection: 'column',
				justifyContent: 'flex-start',
				alignItems: 'center',
			};

			if (this.#style.backgroundColor)
			{
				style.backgroundColor = this.#style.backgroundColor;
			}

			return style;
		}

		renderConfetti()
		{
			const confetti = `${IMAGE_PATH}/graphic/light/confetti.svg`;

			return Image({
				style: {
					width: '110%',
					height: 135,
					position: 'absolute',
					top: 0,
					left: 0,
					zIndex: 1,
				},
				svg: {
					resizeMode: 'contain',
					uri: confetti,
				},
			});
		}

		renderVideo()
		{
			return Video({
				testId: this.#getTestId('video'),
				style: {
					...CONTENT_SIZE,
					marginTop: Indent.XL4.toNumber(),
					marginBottom: Indent.XL3.toNumber(),
					backgroundColor: Color.bgSecondary.toHex(),
				},
				scaleMode: 'fill',
				uri: AppUpdateNotifier.#getVideoPath(),
				loop: true,
			});
		}

		renderText()
		{
			return Text2({
				color: this.#style.textColor ?? Color.base1,
				text: this.props.text ?? Loc.getMessage('APP_UPDATE_NOTIFIER_NEED_UPDATE_V2'),
				style: {
					textAlign: 'center',
					width: '100%',
					marginBottom: Indent.XS.toNumber(),
				},
			});
		}

		renderHeaderText()
		{
			return View(
				{
					style: {
						marginVertical: Indent.XL3.toNumber(),
						alignItems: 'center',
					},
				},
				H1(
					{
						style: {
							width: '100%',
							textAlign: 'center',
							marginBottom: Indent.S.toNumber(),
						},
						color: Color.base1,
						text: Loc.getMessage('APP_UPDATE_NOTIFIER_TITLE_MAIN_V2'),
					},
				),
				Text3({
					text: Loc.getMessage('APP_UPDATE_NOTIFIER_TITLE_PARAGRAPH_V2'),
					color: Color.base3,
				}),
			);
		}

		renderFeatureList()
		{
			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'column',
						justifyContent: 'flex-start',
						alignItems: 'flex-start',
						marginTop: Indent.M.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				...this.#features.map(({ icon, text }) => this.featureItem(icon, text)),
			);
		}

		featureItem(icon, text = 'test')
		{
			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						justifyContent: 'flex-start',
						alignItems: 'center',
						marginBottom: Indent.XL.toNumber(),
					},
				},
				IconView({
					style: {
						marginRight: Indent.L.toNumber(),
					},
					icon,
					color: Color.accentMainSuccess,
					size: 28,
				}),
				Text3({
					text,
					color: Color.base1,
				}),
			);
		}

		renderUpdateButton()
		{
			return this.renderButton();
		}

		renderButton()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						marginTop: 19,
						paddingVertical: Indent.XL.toNumber(),
					},
				},
				Button({
					testId: this.#getTestId('open-link'),
					size: ButtonSize.L,
					stretched: true,
					text: Loc.getMessage('APP_UPDATE_NOTIFIER_OPEN_UPDATE_LINK'),
					onClick: openStore,
				}),
				Button({
					testId: this.#getTestId('close'),
					style: {
						marginTop: Indent.L.toNumber(),
					},
					size: ButtonSize.S,
					design: ButtonDesign.PLAN_ACCENT,
					stretched: true,
					text: Loc.getMessage('APP_UPDATE_NOTIFIER_CLOSE_V2'),
					onClick: () => this.close(),
				}),
			);
		}

		close()
		{
			if (this.layout)
			{
				this.layout.close();
			}
		}
	}

	module.exports = { AppUpdateNotifier };
});
