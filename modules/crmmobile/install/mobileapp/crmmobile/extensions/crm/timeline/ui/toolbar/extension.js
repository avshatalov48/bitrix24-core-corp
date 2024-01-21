/**
 * @module crm/timeline/ui/toolbar
 */
jn.define('crm/timeline/ui/toolbar', (require, exports, module) => {
	const { AppTheme } = require('apptheme/extended');
	const safeArea = { bottom: true };

	function Toolbar({ left, center, right })
	{
		return ToolbarShadow(
			View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				left && ToolbarLeft(left()),
				ToolbarCenter(
					center ? center() : View(),
				),
				right && ToolbarRight(right()),
			),
		);
	}

	function ToolbarShadow(...children)
	{
		return Shadow(
			{
				color: AppTheme.colors.shadowPrimary,
				radius: 3,
				offset: {
					y: -3,
				},
				inset: {
					left: 3,
					right: 3,
				},
				style: {
					borderTopLeftRadius: 12,
					borderTopRightRadius: 12,
				},
			},
			...children,
		);
	}

	function ToolbarLeft(...children)
	{
		return View(
			{
				safeArea,
				style: {
					paddingHorizontal: 20,
					paddingVertical: 16,
					borderRightWidth: 1,
					borderRightColor: AppTheme.colors.bgSeparatorPrimary,
					flexDirection: 'row',
					justifyContent: 'flex-start',
				},
			},
			...children,
		);
	}

	function ToolbarCenter(...children)
	{
		return View(
			{
				safeArea,
				style: {
					flexGrow: 1,
					flexDirection: 'row',
					justifyContent: 'flex-start',
					paddingHorizontal: 6,
				},
			},
			...children,
		);
	}

	function ToolbarIcon({ svg, width, height, onClick, tintColor })
	{
		return View(
			{
				onClick,
				style: {
					paddingHorizontal: 16,
					paddingVertical: 16,
				},
			},
			Image({
				tintColor: tintColor || AppTheme.colors.base3,
				svg: {
					content: svg,
				},
				style: {
					width,
					height,
				},
			}),
		);
	}

	function ToolbarRight(...children)
	{
		return View(
			{
				safeArea,
				style: {
					paddingHorizontal: 20,
					paddingVertical: 16,
					borderLeftWidth: 1,
					borderLeftColor: AppTheme.colors.bgSeparatorPrimary,
					flexDirection: 'row',
					justifyContent: 'flex-end',
				},
			},
			...children,
		);
	}

	function ToolbarKeyboardToggle()
	{
		return View(
			{
				onClick()
				{
					Keyboard.dismiss();
				},
			},
			Image({
				svg: {
					content: `<svg width="34" height="19" viewBox="0 0 34 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.35" d="M25.9551 8.09277H33.6525L29.8038 12.1296L25.9551 8.09277Z" fill="${AppTheme.colors.base2}"/><path opacity="0.6" fill-rule="evenodd" clip-rule="evenodd" d="M21.0882 13.0366V15.8045H18.2416V13.0366H21.0882ZM21.0536 11.2518H18.1876V8.4839H21.0536V11.2518ZM16.3106 8.4839V11.2518H13.3998V8.4839H16.3106ZM6.67285 3.93117V6.63797H3.76208V3.93117H6.67285ZM16.3005 13.0348V15.8026H8.61436V13.0348H16.3005ZM6.67312 13.0348V15.8026H3.76236V13.0348H6.67312ZM11.4588 8.48202V11.2499H8.61228V8.48202H11.4588ZM6.67104 8.48202V11.2499H3.76027V8.48202H6.67104ZM21.0839 3.92929V6.63609H18.2373V3.92929H21.0839ZM16.3062 3.92929V6.63609H13.3955V3.92929H16.3062ZM11.4542 3.92929V6.63609H8.60768V3.92929H11.4542ZM23.9242 2.96385C23.9242 1.85928 23.0288 0.963847 21.9242 0.963851L2.82716 0.963928C1.72259 0.963933 0.827167 1.85936 0.827167 2.96393V16.4465C0.827167 17.5511 1.7226 18.4465 2.82718 18.4465L21.9242 18.4465C23.0288 18.4465 23.9242 17.551 23.9242 16.4465V2.96385Z" fill="${AppTheme.colors.base2}"/></svg>`,
				},
				style: {
					width: 34,
					height: 19,
				},
			}),
		);
	}

	class ToolbarButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { text, disabled } = props;

			this.state = {
				text,
				disabled,
				loading: false,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state.disabled = props.disabled;
		}

		render()
		{
			return View(
				{
					onClick: () => {
						if (this.state.disabled || this.state.loading)
						{
							return;
						}

						this.startLoading();

						this.props.onClick().finally(() => this.stopLoading());
					},
				},
				Text({
					text: this.state.text,
					style: {
						color: this.state.disabled ? AppTheme.colors.base4 : AppTheme.colors.accentMainLinks,
						opacity: this.state.loading ? 0.5 : 1,
						fontSize: 18,
						fontWeight: '500',
					},
				}),
			);
		}

		/**
		 * @public
		 */
		disable()
		{
			this.setState({ disabled: true });
		}

		/**
		 * @public
		 */
		enable()
		{
			this.setState({ disabled: false });
		}

		/**
		 * @private
		 */
		startLoading()
		{
			this.setState({
				loading: true,
				text: this.props.loadingText,
			});
		}

		/**
		 * @private
		 */
		stopLoading()
		{
			this.setState({
				loading: false,
				text: this.props.text,
			});
		}
	}

	module.exports = {
		Toolbar,
		ToolbarShadow,
		ToolbarLeft,
		ToolbarCenter,
		ToolbarRight,
		ToolbarIcon,
		ToolbarKeyboardToggle,
		ToolbarButton,
	};
});
