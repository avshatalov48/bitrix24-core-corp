/**
 * @module layout/ui/switcher
 */
jn.define('layout/ui/switcher', (require, exports, module) => {
	const { transition, parallel } = require('animation');

	const COLORS = {
		THUMB_DEFAULT: '#ffffff',
		TRACK_DEFAULT: '#d5d7db',
		TRACK_ACTIVE: '#2fc6f6',
	};

	class Switcher extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.position = null;
			this.switcherRef = null;
			this.firstRender = true;
			this.switcherContainerRef = null;
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			return false;
		}

		componentWillReceiveProps(props)
		{
			this.toggleValue(props);
		}

		toggleValue(props)
		{
			const {
				checked: wasChecked,
				animations: additionalAnimations = [],
				disabled,
			} = props;

			const position = Switcher.getPosition(wasChecked);
			if (this.position === position || disabled)
			{
				return null;
			}

			this.position = position;

			const animate = parallel(
				transition(this.switcherRef, {
					duration: 200,
					left: position,
					backgroundColor: this.getThumbColor(wasChecked),
				}),
				transition(this.switcherContainerRef, {
					duration: 200,
					backgroundColor: this.getTrackColor(wasChecked),
				}),
				...additionalAnimations,
			);

			return animate();
		}

		static getPosition(checked)
		{
			return checked ? 23 : 3;
		}

		render()
		{
			const { switcherContainer, switcher } = this.getStyles();

			return View(
				{
					testId: `${this.props.testId}-Container`,
					ref: (ref) => {
						this.switcherContainerRef = ref;
					},
					style: switcherContainer,
				},
				View(
					{
						ref: (ref) => {
							this.switcherRef = ref;
						},
						style: switcher,
					},
				),
			);
		}

		getThumbColor(checked)
		{
			const { thumbColor = {} } = this.props;

			const color = {
				true: COLORS.THUMB_DEFAULT,
				false: COLORS.THUMB_DEFAULT,
				...thumbColor,
			};

			return color[checked];
		}

		getTrackColor(checked)
		{
			const { trackColor = {} } = this.props;

			const color = {
				true: COLORS.TRACK_ACTIVE,
				false: COLORS.TRACK_DEFAULT,
				...trackColor,
			};

			return color[checked];
		}

		getStyles()
		{
			const { disabled, checked, styles = {} } = this.props;

			return {
				switcherContainer: {
					borderRadius: 14,
					backgroundColor: this.getTrackColor(checked),
					width: 37,
					height: 17,
					marginRight: 8,
					opacity: disabled ? 0.5 : 1,
				},
				switcher: {
					position: 'absolute',
					width: 11,
					height: 11,
					backgroundColor: this.getThumbColor(checked),
					borderRadius: 8,
					top: 3,
					left: Switcher.getPosition(checked),
				},
				...styles,
			};
		}
	}

	module.exports = { Switcher };
});
