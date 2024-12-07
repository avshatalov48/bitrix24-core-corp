/**
 * @module layout/ui/floating-button
 */
jn.define('layout/ui/floating-button', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Feature } = require('feature');
	const AppTheme = require('apptheme');
	const { withPressed } = require('utils/color');
	const { HideOnScrollAnimator } = require('animation/hide-on-scroll');
	const { FloatingActionButton, FloatingActionButtonSupportNative } = require(
		'ui-system/form/buttons/floating-action-button',
	);

	/**
	 * @class FloatingButtonComponent
	 */
	class FloatingButtonComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			if (!props.onClick)
			{
				throw new Error('Need to set onClick callback.');
			}

			this.position = props.position || {};
			this.viewStyle = props.viewStyle || {};

			this.viewRef = null;
		}

		get vibrationEnabled()
		{
			return BX.prop.getBoolean(this.props, 'vibrationEnabled', true);
		}

		animateOnScroll(scrollParams, scrollViewHeight)
		{
			if (!this.viewRef)
			{
				return;
			}

			const animator = this.getAnimator();
			if (animator)
			{
				animator.animateByScroll(this.viewRef, scrollParams, scrollViewHeight);
			}
		}

		/**
		 * @return {HideOnScrollAnimator}
		 */
		getAnimator()
		{
			if (!this.animator)
			{
				const { bottom } = this.getButtonViewStyle();

				this.animator = new HideOnScrollAnimator({ initialTopPosition: bottom });
			}

			return this.animator;
		}

		show()
		{
			return this.getAnimator().show(this.viewRef);
		}

		hide()
		{
			return this.getAnimator().hide(this.viewRef);
		}

		renderAirStyleButton()
		{
			if (FloatingActionButtonSupportNative(this.getLayout()))
			{
				return null;
			}

			return View(
				{
					ref: (ref) => {
						this.viewRef = ref;
					},
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					style: this.getButtonStyle(),
				},
				this.renderFloatingActionButton(),
			);
		}

		renderButton()
		{
			return View(
				{
					ref: (ref) => {
						this.viewRef = ref;
					},
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					testId: this.props.testId,
					style: this.getButtonStyle(),
				},
				Shadow(
					{
						radius: 2,
						color: '#330984ab',
						offset: {
							x: 0,
							y: 2,
						},
						style: styles.shadow,
					},
					View(
						{
							style: styles.shadowView,
							onClick: () => this.onClick(),
							onLongClick: () => this.onLongClick(),
						},
						Image({
							style: styles.button,
							svg: {
								content: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.74915 1.25085C6.74915 0.560027 7.30917 0 8 0C8.69083 0 9.25085 0.560027 9.25085 1.25085V6.74915H14.7491C15.44 6.74915 16 7.30917 16 8C16 8.69083 15.44 9.25085 14.7491 9.25085H9.25085V14.7491C9.25085 15.44 8.69083 16 8 16C7.30917 16 6.74915 15.44 6.74915 14.7491V9.25085H1.25085C0.560027 9.25085 0 8.69083 0 8C0 7.30917 0.560027 6.74915 1.25085 6.74915H6.74915V1.25085Z" fill="white"/></svg>',
							},
						}),
					),
				),
			);
		}

		getButtonStyle()
		{
			return {
				...this.getButtonViewStyle(),
				...this.position,
			};
		}

		getButtonViewStyle()
		{
			return Feature.isAirStyleSupported() ? styles.airView : styles.view;
		}

		render()
		{
			return Feature.isAirStyleSupported()
				? this.renderAirStyleButton()
				: this.renderButton();
		}

		getLayout()
		{
			const { parentLayout } = this.props;

			return parentLayout;
		}

		renderFloatingActionButton()
		{
			const { testId, accent, parentLayout } = this.props;

			return FloatingActionButton({
				testId,
				accent,
				parentLayout,
				onClick: this.onClick,
				onLongClick: this.onLongClick,
			});
		}

		onClick = () => {
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		};

		onLongClick = () => {
			const { onLongClick } = this.props;

			if (onLongClick)
			{
				this.vibrate();
				onLongClick();
			}
		};

		vibrate()
		{
			if (this.vibrationEnabled)
			{
				Haptics.impactLight();
			}
		}
	}

	const styles = {
		airView: {
			position: 'absolute',
			right: 24,
			bottom: 18,
		},
		view: {
			position: 'absolute',
			right: Application.getPlatform() === 'android' ? 14 : 13,
			bottom: Application.getPlatform() === 'android' ? 12 : 11,
		},
		shadow: {
			borderRadius: 30,
		},
		shadowView: {
			height: Application.getPlatform() === 'android' ? 56 : 60,
			width: Application.getPlatform() === 'android' ? 56 : 60,
			borderRadius: 30,
			backgroundColor: withPressed(AppTheme.colors.accentBrandBlue),
			justifyContent: 'center',
			alignItems: 'center',
		},
		button: {
			width: 16,
			height: 16,
		},
	};

	module.exports = { FloatingButtonComponent };
});

(() => {
	const { FloatingButtonComponent } = jn.require('layout/ui/floating-button');

	this.UI = this.UI || {};
	this.UI.FloatingButtonComponent = FloatingButtonComponent;
})();
