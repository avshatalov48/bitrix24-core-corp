/**
 * @module crm/timeline/item/ui/icon
 */
jn.define('crm/timeline/item/ui/icon', (require, exports, module) => {
	const { stringify } = require('utils/string');
	const { get } = require('utils/object');
	const { Haptics } = require('haptics');
	const { TimelineItemIconLogo } = require('crm/timeline/item/ui/icon/logo');

	const CounterTypeColor = {
		DANGER: '#FF5752',
		SUCCESS: '#9DCF00',

		get(code)
		{
			code = stringify(code).toUpperCase();

			return this[code] || this.DANGER;
		},
	};

	class TimelineItemIcon extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				play: false,
				isLoading: false,
				isLoaded: false,
			};

			/** @type {EventEmitter} */
			this.itemScopeEventBus = this.props.itemScopeEventBus;
		}

		get additionalIcon()
		{
			return BX.prop.getObject(this.props, 'additionalIcon', null);
		}

		componentDidMount()
		{
			if (this.props.hasPlayer)
			{
				this.itemScopeEventBus.on('AudioPlayer::onPlay', () => {
					this.setState({
						play: true,
						isLoading: false,
						isLoaded: true,
					});
				});

				this.itemScopeEventBus.on('AudioPlayer::onPause', () => {
					this.setState({
						play: false,
						isLoading: false,
					});
				});
				this.itemScopeEventBus.on('AudioPlayer::onFinish', () => {
					this.setState({
						play: false,
						isLoading: false,
					});
				});
			}
		}

		render()
		{
			const { onAction, counterType } = this.props;
			const action = get(this.props, 'logo.action', null);

			return View(
				{
					style: {
						paddingTop: 12,
						paddingLeft: 12,
						paddingBottom: 12,
					},
					onClick: () => {
						if (this.props.hasPlayer)
						{
							Haptics.impactLight();
							if (!this.state.isLoading)
							{
								this.setState({ isLoading: true });
							}
						}

						return onAction && action && onAction(action);
					},
				},
				View(
					{
						style: {
							width: 84,
							height: 84,
							borderRadius: 12,
							backgroundColor: this.getIconTintedColor(),
						},
					},
					this.renderIcon(),
				),
				counterType && Counter({
					color: CounterTypeColor.get(counterType),
					text: '1',
				}),
			);
		}

		getIconTintedColor()
		{
			const iconType = get(this.props, 'logo.iconType', null);

			return iconType ? '#ffe8e8' : '#e5f9ff';
		}

		renderIcon()
		{
			return new TimelineItemIconLogo({
				...this.props.logo,
				hasPlayer: this.props.hasPlayer,
				play: this.state.play,
				isLoading: this.state.isLoading,
				isLoaded: this.state.isLoaded,
			});
		}
	}

	function Counter({ color, text })
	{
		return View(
			{
				style: {
					position: 'absolute',
					left: 8,
					top: 9,
					backgroundColor: color,
					width: 18,
					height: 18,
					alignItems: 'center',
					justifyContent: 'center',
					borderRadius: 44,
				},
			},
			Text({
				text: String(text),
				style: {
					color: '#ffffff',
					fontSize: 12,
					fontWeight: '500',
				},
			}),
		);
	}

	module.exports = { TimelineItemIcon };
});
