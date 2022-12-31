/**
 * @module layout/ui/range-slider
 */
jn.define('layout/ui/range-slider', (require, exports, module) => {
	const { EventEmitter } = require('event-emitter');
	const { throttle, debounce } = require('utils/function');
	const nothing = () => {};
	const SECONDS_COUNT = 60;

	class RangeSlider extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		get enabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
					interactable: true,
				},
				View(
					{
						style: {
							flex: 1,
						},
						clickable: this.enabled,
						onPan: () => {},
						onTouchesBegan: ({x}) => {
							this.wrapperRef.onTouchesBegan(x);
						},
						onTouchesMoved: ({ x}) => {
							this.wrapperRef.onTouchesMoved(x);
						},
						onTouchesEnded: ({ x}) => {
							this.wrapperRef.onTouchesEnded(x)
						},
					},
					new RangeSliderWrapper({
						...this.props,
						ref: ref => this.wrapperRef = ref,
					}),
				),
			);
		}
	}

	class RangeSliderWrapper extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.isTouchEnd = true;
			this.isEventsBinded = false;
			this.state = {
				position: 0,
			};

			this.updatePosition = throttle(({position, callback}) => {
				this.setState({
					position,
				}, callback);
			}, 25);

			this.freezeTouchEnd = debounce((value) => {
				this.isTouchEnd = value;
			}, 500);
		}

		componentWillReceiveProps(newProps)
		{
			if (newProps.enabled)
			{
				if (!this.isEventsBinded)
				{
					this.bindEvents(newProps);
				}
				this.isEventsBinded = true;
			}
		}

		bindEvents(props)
		{
			if (props.player)
			{
				props.player.on('timeupdate', ({currentTime}) => {
					if (this.isTouchEnd)
					{
						const position = this.getPositionByValue(currentTime);
						if (this.state.position !== position)
						{
							this.setState({
								position: position,
							});
						}
					}
				});
			}
		}

		get uid()
		{
			return BX.prop.get(this.props, 'uid', Random.getString());
		}

		get value()
		{
			return BX.prop.getInteger(this.props, 'value', 0);
		}

		get maximumValue()
		{
			return BX.prop.getInteger(this.props, 'maximumValue', 0);
		}

		get enabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}

		get active()
		{
			return BX.prop.getBoolean(this.props, 'active', false);
		}

		get showValues()
		{
			return BX.prop.getBoolean(this.props, 'showValues', true);
		}

		render()
		{
			return View(
				{
					clickable: false,
					style: {
						flex: 1,
						justifyContent: 'center',
					},
				},
				View(
					{
						style: {
							backgroundColor: '#E0F7FE',
							maxHeight: 6,
							flex: 1,
							flexDirection: 'column',
							borderRadius: 3,
							position: "absolute",
							top: 19,
							left: 0,
							right: 0,
							height: 6,
							marginLeft: 10,
							marginRight: 10,
						},
						onLayout: ({width}) => {
							this.rangeSliderWidth = width;
						},
						clickable: false,
					},
					this.renderDivisions(),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							flexShrink: 2,
							alignItems: 'center',
						},
						clickable: false,
					},
					View(
						{
							style: {
								backgroundColor: '#2FC6F6',
								height: 6,
								width: this.state.position,
								borderRadius: 3,
								marginLeft: 10,
							},
							clickable: false,
						},
					),
					//marker
					View(
						{
							style: {
								width: 20,
								height: 20,
								borderRadius: 10,
								borderWidth: 1,
								borderColor: '#CED4DA',
								backgroundColor: '#FFFFFF',
								marginLeft: -10,
							},
							clickable: false,
						},
					),
				),
				this.showValues && View(
					{
						style: {
							position: 'absolute',
							bottom: 0,
							left: 0,
							width: '100%',
							justifyContent: 'space-between',
							flexDirection: 'row',
							paddingHorizontal: 10,
						},
						clickable: false,
					},
					Text(
						{
							style: {
								color: this.active ? '#2FC6F6' :'#A8ADB4',
								fontSize: 10,
								minWidth: 50,
							},
							clickable: false,
							text: this.convertSecondsToTime(this.getValueByPosition(this.state.position)),
						},
					),
					Text(
						{
							style: {
								color: '#A8ADB4',
								fontSize: 10,
								minWidth: 50,
								textAlign: 'right',
							},
							clickable: false,
							text: this.convertSecondsToTime(this.maximumValue),
						},
					),
				),
			);
		}

		renderDivisions()
		{
			if (this.maximumValue === 0)
			{
				return null;
			}

			const minutes = this.getMinutes(this.maximumValue);

			const divisions = Array.from({length: minutes}).map((division, index) => (
				View(
					{
						style: {
							height: 4,
							width: 1,
							backgroundColor: '#2FC6F6',
							position: 'absolute',
							top: 1,
							left: this.getPositionByValue((index + 1) * SECONDS_COUNT)
						},
						clickable: false,
					},
				)
			));

			return View(
				{
					clickable: false,
				},
				...divisions,
			);
		}

		onTouchesBegan(x)
		{
			if (this.enabled)
			{
				this.isTouchEnd = false;
				this.setPosition(x, this.rangeSliderWidth);
			}
		}

		onTouchesMoved(x)
		{
			if (this.enabled)
			{
				this.setPosition(x, this.rangeSliderWidth);
			}
		}

		onTouchesEnded(x)
		{
			if (this.enabled)
			{
				this.freezeTouchEnd(true);

				this.setState({
					position: this.getPosition(x, this.rangeSliderWidth)
				}, () => {
					this.onSlidingComplete();
				});
			}
		}

		setPosition(position, rangeSliderWidth, callback = nothing)
		{
			position = Math.max(0, position);
			position = Math.min(rangeSliderWidth, position);

			if (position === this.state.position)
			{
				return;
			}

			this.updatePosition({position, callback});
		}

		getPosition(position, rangeSliderWidth)
		{
			position = Math.max(0, position);
			position = Math.min(rangeSliderWidth, position);

			return position;
		}

		onValueChange()
		{
			const { onValueChange } = this.props;
			if (onValueChange)
			{
				onValueChange(this.getValueByPosition(this.state.position));
			}
		}

		getValueByPosition(position)
		{
			return Math.round(position * this.maximumValue / this.rangeSliderWidth);
		}

		onSlidingComplete()
		{
			const { onSlidingComplete } = this.props;

			if (onSlidingComplete)
			{
				onSlidingComplete(this.getValueByPosition(this.state.position));
			}
		}

		getPositionByValue(value)
		{
			if (!this.rangeSliderWidth || !this.maximumValue)
			{
				return 0;
			}

			return Math.floor(value * this.rangeSliderWidth / this.maximumValue);
		}

		convertSecondsToTime(totalSeconds)
		{
			const padTo2Digits = (num) => {
				return num.toString().padStart(2, '0');
			};

			const minutes = this.getMinutes(totalSeconds);
			const seconds = Math.floor(totalSeconds % SECONDS_COUNT);

			return `${minutes}:${padTo2Digits(seconds)}`;
		}

		getMinutes(totalSeconds)
		{
			return Math.floor(totalSeconds / SECONDS_COUNT);
		}
	}

	module.exports = { RangeSlider };
});