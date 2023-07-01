/**
 * @module im/messenger/lib/ui/base/buttons/full-width-button
 */
jn.define('im/messenger/lib/ui/base/buttons/full-width-button', (require, exports, module) => {

	const { debounce } = require('utils/function');
	class FullWidthButton extends LayoutComponent
	{
		/**
		 *
		 * @param {Object} props
		 * @param {string} props.text
		 * @param {Function} props.callback
		 * @param {string} [props.icon]
		 * @param {string} [props.testId]
		 */
		constructor(props)
		{
			super(props);
			this.content = null;
		}


		render()
		{
			console.log(this.props.testId);
			return View(
				{
					testId: this.props.testId,
					clickable: true,
					onClick : () => this.content.onClick(),
					onTouchesBegan: params => this.content.onTouchesBegan(params),
					onTouchesEnded: params => this.content.onTouchesEnded(params),
					onTouchesMoved: params => this.content.onTouchesMoved(params)
				},
				this.content = new ButtonContainer({
					text: this.props.text,
					svgIcon: this.props.svgIcon,
					onClick: this.props.callback,
				}),

			);
		}
	}

	class ButtonContainer extends LayoutComponent
	{

		constructor(props)
		{
			super(props);
			this.state.isPressed = false;
			this.x = 0;
			this.y = 0;
		}

		onTouchesBegan(params)
		{
			this.x = params.x;
			this.y = params.y;
			this.setState({isPressed: true});
		}

		onTouchesEnded(params)
		{
			this.setState({ isPressed: false });
		}

		onTouchesMoved(params)
		{
			if (this.x !== params.x || this.y !== params.y) {
				(debounce(() => {
					this.setState({isPressed: false})
				}, 250, this))();
			}
		}

		onClick()
		{
			this.props.onClick();
		}


		render()
		{
			return View(
				{
					clickable: false,
					style: {
						width: '100%',
						justifyContent: 'flex-start',
						alignItems: 'center',
						height: 50,
						backgroundColor: this.state.isPressed ? '#e9e9e9': '#FFF',
						paddingLeft: 20,
						flexDirection: 'row',
					},
				},
				View(
					{
						clickable: false,
					},
					Image(
						{
							style: {
								height: 32,
								width: 32,
								borderRadius: 16,
							},
							svg: {
								content: this.props.svgIcon
							}
						}
					)
				),
				View(
					{
						clickable: false,
						style: {
							marginLeft: 10,
						}
					},
					Text(
						{
							text: this.props.text,
							style: {
								fontSize: 17,
							}
						}
					)
				),
			);
		}
	}

	module.exports = { FullWidthButton };
});