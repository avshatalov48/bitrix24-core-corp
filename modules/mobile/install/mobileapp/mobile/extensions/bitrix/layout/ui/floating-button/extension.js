/**
 * @module layout/ui/floating-button
 */
jn.define('layout/ui/floating-button', (require, exports, module) => {
	const { Haptics } = require('haptics');
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

		getButtonStyle()
		{
			return {
				...this.getButtonViewStyle(),
				...this.position,
			};
		}

		getButtonViewStyle()
		{
			return styles.airView;
		}

		render()
		{
			return this.renderAirStyleButton();
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
