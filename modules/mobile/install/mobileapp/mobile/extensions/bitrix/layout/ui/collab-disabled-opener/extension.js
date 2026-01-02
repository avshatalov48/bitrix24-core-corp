/**
 * @module layout/ui/collab-disabled-opener
 */
jn.define('layout/ui/collab-disabled-opener', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Color } = require('tokens');
	const { Icon } = require('ui-system/blocks/icon');
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { StatusBlock } = require('ui-system/blocks/status-block');

	const IMAGE_SIZE = 121;

	class CollabDisabled extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.mode = props.mode;
		}

		/**
		 * @typedef {Object} Modes
		 * @property {Mode} BACKDROP
		 * @property {Mode} FULLSCREEN
		 */
		static MODES = {
			BACKDROP: {
				mediumHeight: 370,
				titleKey: 'M_UI_COLLAB_DISABLED_TITLE',
				descriptionKey: 'M_UI_COLLAB_DISABLED_DESCRIPTION',
				showButton: false,
			},
			FULLSCREEN: {
				mediumPercent: 100,
				titleKey: 'M_UI_COLLAB_DISABLED_COLLABER_TITLE',
				descriptionKey: 'M_UI_COLLAB_DISABLED_COLLABER_DESCRIPTION',
				showButton: true,
			},
		};

		static #getViewMode(isCollaber)
		{
			return isCollaber ? CollabDisabled.MODES.FULLSCREEN : CollabDisabled.MODES.BACKDROP;
		}

		/**
		 * @param {Object} [props={}]
		 * @param {Object} [props.parentWidget]
		 * @param {Mode} [props.mode] - One of the modes defined in {@link CollabDisabled.MODES}
		 */
		static open(props = {})
		{
			const mode = props.mode ?? this.#getViewMode(env.isCollaber);

			const parentWidget = props.parentWidget ?? PageManager;

			const bottomSheet = new BottomSheet({
				component: (layoutWidget) => new CollabDisabled({
					...props,
					layoutWidget,
					mode,
				}),
			})
				.setParentWidget(parentWidget || PageManager)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
			;

			this.#configureBottomSheet(bottomSheet, mode);

			void bottomSheet.open();
		}

		static #configureBottomSheet(bottomSheet, mode)
		{
			if (mode === CollabDisabled.MODES.FULLSCREEN)
			{
				bottomSheet
					.hideNavigationBar()
					.hideNavigationBarBorder()
					.setMediumPositionPercent(mode.mediumPercent)
					.disableSwipe()
					.disableHorizontalSwipe()
					.disableContentSwipe()
					.showOnTop()
					.preventBottomSheetDismiss()
				;
			}
			else
			{
				bottomSheet
					.setMediumPositionHeight(mode.mediumHeight, false)
				;
			}
		}

		#getTestId = (suffix) => {
			const prefix = 'collab-disabled';

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		render()
		{
			const content = {
				image: this.getImage(),
				title: this.getTitle(),
				description: this.getDescription(),
			};

			if (this.props.mode.showButton)
			{
				content.button = this.getButton();
			}

			return this.renderStatusBlock(content, this.props.mode === CollabDisabled.MODES.BACKDROP);
		}

		renderStatusBlock(content, isBackdrop)
		{
			const statusBlockProps = {
				testId: this.#getTestId('status-block'),
				image: content.image,
				title: content.title,
				description: content.description,
				titleColor: Color.base1,
				descriptionColor: Color.base2,
				emptyScreen: !isBackdrop,
				preventRefresh: true,
			};

			if (!isBackdrop)
			{
				statusBlockProps.buttons = [content.button];
			}

			return StatusBlock(statusBlockProps);
		}

		getTitle()
		{
			return Loc.getMessage(this.props.mode.titleKey);
		}

		getDescription()
		{
			return Loc.getMessage(this.props.mode.descriptionKey);
		}

		getImage()
		{
			return Image({
				testId: this.#getTestId('graphic'),
				style: {
					width: IMAGE_SIZE,
					height: IMAGE_SIZE,
				},
				svg: {
					uri: makeLibraryImagePath('collab-disabled.svg', 'graphic'),
				},
			});
		}

		getButton()
		{
			return Button({
				testId: this.#getTestId('button'),
				text: Loc.getMessage('M_UI_COLLAB_DISABLED_BUTTON'),
				onClick: () => this.onPressButton(),
				leftIcon: Icon.LOG_OUT,
				design: ButtonDesign.OUTLINE_ACCENT_2,
				size: ButtonSize.L,
			});
		}

		onPressButton()
		{
			Application.exit();
		}
	}

	module.exports = {
		CollabDisabled,
	};
});
