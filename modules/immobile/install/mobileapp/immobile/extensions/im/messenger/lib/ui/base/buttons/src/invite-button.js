/**
 * @module im/messenger/lib/ui/base/buttons/invite-button
 */
jn.define('im/messenger/lib/ui/base/buttons/invite-button', (require, exports, module) => {
	const { debounce } = require('utils/function');

	class InviteButton extends LayoutComponent
	{

		/**
		 *
		 * @param props
		 * @param {string} props.text
		 * @param {Function} props.callback
		 */
		constructor(props)
		{
			super(props);
			this.content = null;
		}

		render()
		{
			return View(
				{
					style: {
						marginBottom: 10
					},
					clickable: true,
					onClick : () => this.content.onClick(),
					onTouchesBegan: params => this.content.onTouchesBegan(params),
					onTouchesEnded: params => this.content.onTouchesEnded(params),
					onTouchesMoved: params => this.content.onTouchesMoved(params)
				},
				this.content = new ButtonContainer(this.props)
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
			this.props.callback();
		}

		render()
		{
			return View(
				{
					clickable: false,
					style: {
						flexDirection: 'row',
						width: '100%',
						justifyContent: 'flex-start',
						alignItems: 'center',
						height: 60,
						backgroundColor: '#FFF',
						marginLeft: 16,

					},
				},
				View(
					{
						clickable: false,
						style: {
							width: '70%',
							height: 45,
							borderColor: '#9ACF00',
							borderRadius: 22.5,
							borderWidth: 2,
							alignItems: 'center',
							justifyContent: 'center',
						}
					},
					View(
						{
							clickable: false,
							style: {
								width: '100%',
								flexDirection: 'row',
							}
						},
						View(
							{
								clickable: false,
								style: {
									marginLeft: 6,
								}
							},
							Image(
								{
									style: {
										height: 28,
										width: 28,
										borderRadius: 14,
									},
									svg: {
										content: !this.state.isPressed ? this.getInviteIcon() : this.getInviteIcon('#edf3cf')
									}
								}
							)
						),
						View(
							{
								clickable: false,
								style: {
									marginLeft: 10,
									alignItems: 'center',
									justifyContent: 'center',
								}
							},
							Text(
								{
									text: this.props.text,
									style: {
										color: !this.state.isPressed ? '#000' : '#e9e9e9',
										fontSize: 17,
									}
								}
							)
						),
					)
				),

			);
		}

		getInviteIcon(color = '#9ACF00')
		{
			return `<svg width="85" height="85" viewBox="0 0 85 85" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_208_8)">
<path opacity="0.983" fill-rule="evenodd" clip-rule="evenodd" d="M35.5 -0.5C39.8333 -0.5 44.1667 -0.5 48.5 -0.5C68.1667 3.83333 80.1667 15.8333 84.5 35.5C84.5 39.8333 84.5 44.1667 84.5 48.5C80.1398 68.1935 68.1398 80.1935 48.5 84.5C44.1667 84.5 39.8333 84.5 35.5 84.5C15.8065 80.1398 3.80645 68.1398 -0.5 48.5C-0.5 44.1667 -0.5 39.8333 -0.5 35.5C3.83333 15.8333 15.8333 3.83333 35.5 -0.5ZM23.5 46.5C23.5 43.8333 23.5 41.1667 23.5 38.5C28.6946 38.8205 33.6946 38.4872 38.5 37.5C38.5 32.8333 38.5 28.1667 38.5 23.5C40.8333 23.5 43.1667 23.5 45.5 23.5C45.5 28.5 45.5 33.5 45.5 38.5C50.5 38.5 55.5 38.5 60.5 38.5C60.5 41.1667 60.5 43.8333 60.5 46.5C55.5 46.5 50.5 46.5 45.5 46.5C45.5 51.1667 45.5 55.8333 45.5 60.5C43.1667 60.5 40.8333 60.5 38.5 60.5C38.5 55.8333 38.5 51.1667 38.5 46.5C33.5 46.5 28.5 46.5 23.5 46.5Z" fill="${color}"/>
<path opacity="0.544" fill-rule="evenodd" clip-rule="evenodd" d="M38.5 37.5C33.6946 38.4872 28.6946 38.8205 23.5 38.5C23.5 41.1667 23.5 43.8333 23.5 46.5C22.5233 43.7131 22.19 40.7131 22.5 37.5C27.8333 37.5 33.1667 37.5 38.5 37.5Z" fill="${color}"/>
</g>
<defs>
<clipPath id="clip0_208_8">
<rect width="85" height="85" fill="white"/>
</clipPath>
</defs>
</svg>
`

		}
	}

	module.exports = { InviteButton };
});

