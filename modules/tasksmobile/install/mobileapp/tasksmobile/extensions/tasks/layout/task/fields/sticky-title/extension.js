/**
 * @module tasks/layout/task/fields/sticky-title
 */
jn.define('tasks/layout/task/fields/sticky-title', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @class StickyTitle
	 */
	class StickyTitle extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.container = null;
			this.isAnimating = false;
			this.breakpoint = null;

			this.state = {
				title: props.title,
				isVisible: false,
			};

			this.onClick = this.onClick.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state.title = props.title;
		}

		/**
		 * @public
		 * @param {string} title
		 */
		setTitle(title)
		{
			this.setState({ title });
		}

		/**
		 * Specify scrollY position, after which sticky title should be displayed
		 * @public
		 * @param {number} scrollY
		 */
		setBreakpoint(scrollY)
		{
			this.breakpoint = Math.max(scrollY, 5);
		}

		/**
		 * @public
		 * @param {number} scrollY
		 */
		toggle(scrollY)
		{
			if (this.breakpoint === null)
			{
				return;
			}

			if (scrollY > this.breakpoint)
			{
				this.show();
			}
			else
			{
				this.hide();
			}
		}

		/**
		 * @public
		 */
		show()
		{
			if (this.state.isVisible)
			{
				return;
			}

			this.animate(1, 250);
		}

		/**
		 * @public
		 */
		hide()
		{
			if (!this.state.isVisible)
			{
				return;
			}

			this.animate(0, 100);
		}

		/**
		 * @private
		 * @param {1|0} opacity
		 * @param {number} duration
		 */
		animate(opacity, duration = 200)
		{
			if (this.canAnimate())
			{
				this.isAnimating = true;

				this.container.animate({ opacity, duration }, () => {
					this.isAnimating = false;
					this.setState({ isVisible: Boolean(opacity) });
				});
			}
		}

		/**
		 * @private
		 * @return {boolean}
		 */
		canAnimate()
		{
			return this.container && !this.isAnimating;
		}

		/**
		 * @private
		 */
		onClick()
		{
			if (this.props.onClick)
			{
				this.props.onClick();
			}
		}

		render()
		{
			return View(
				{
					ref: (ref) => {
						this.container = ref;
					},
					onClick: this.state.isVisible ? this.onClick : undefined,
					testId: this.props.testId,
					style: {
						position: 'absolute',
						top: 0,
						height: 48,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
						borderBottomWidth: 1,
						width: '100%',
						opacity: 0,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text({
					testId: `${this.props.testId}_text`,
					text: this.state.title,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						fontSize: 18,
						fontWeight: '400',
						color: AppTheme.colors.base1,
						marginRight: 111,
						marginLeft: 16,
					},
				}),
			);
		}
	}

	module.exports = { StickyTitle };
});
