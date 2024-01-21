/**
 * @module layout/ui/progress-bar
 */
jn.define('layout/ui/progress-bar', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ProgressBarCaption } = require('layout/ui/progress-bar/caption');

	const COLORS = {
		progress: AppTheme.colors.accentBrandBlue,
		backgroundColor: AppTheme.colors.base7,
	};

	const SIZE = {
		width: '100%',
		height: 10,
	};

	/**
	 * @typedef ProgressBar
	 * @property {number} value
	 * @property {number} maxValue
	 * @property {?string} title
	 * @property {?string} description
	 * @property {?boolean} showCaption
	 * @property {?styles} object
	 */
	class ProgressBar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.stack = [];
			this.inProgress = false;
			this.progressRef = null;
			this.captionRef = null;

			const { value, maxValue, color } = props;

			this.config = {
				value: Number(value) || 0,
				maxValue: Number(maxValue) || Math.max(value, maxValue),
				color: color || COLORS.progress,
			};

			this.state = {
				progressWidth: 0,
			};
		}

		getProgressWidth(value)
		{
			const { progressWidth } = this.state;
			const { maxValue } = this.config;

			return value === maxValue
				? progressWidth
				: Math.floor(value * progressWidth / maxValue);
		}

		checkInitParams(value, maxValue)
		{
			if (!Number.isInteger(Number(maxValue)) || !Number.isInteger(Number(value)))
			{
				console.error(`value: ${value} or maxValue: ${maxValue} not a number`);

				return false;
			}

			if (value > maxValue || maxValue === 0)
			{
				console.error(`value: ${value} greater than maxValue: ${maxValue}`);

				return false;
			}

			return true;
		}

		setValue(progressValue)
		{
			const value = Number(progressValue);
			const { maxValue } = this.config;

			if (!this.checkInitParams(value, maxValue))
			{
				return;
			}

			const width = this.getProgressWidth(value);
			this.stack.push(this.getAnimate({ value, width }));
			this.animate();
		}

		animate(force)
		{
			if (this.stack.length === 0)
			{
				this.inProgress = false;
			}

			if (this.stack.length > 0 && (force || !this.inProgress))
			{
				this.inProgress = true;
				const animate = this.stack.shift();
				animate()
					.then(() => this.animate(true))
					.catch(console.error);
			}
		}

		getAnimate({ value, width })
		{
			const { maxValue } = this.config;
			const { onFinish } = this.props;

			return () => {
				const stepValue = Math.min(value, maxValue);
				const isFinish = stepValue === maxValue;
				if (this.captionRef)
				{
					this.captionRef.setValue(Math.min(value, maxValue));
				}

				return new Promise((resolve) => {
					if (!this.progressRef)
					{
						resolve();

						return;
					}

					this.progressRef.animate({
						width,
						duration: 100,
					}, () => {
						if (isFinish && onFinish)
						{
							onFinish();
						}

						resolve();
					});
				});
			};
		}

		getStyles(type)
		{
			const { styles = {} } = this.props;

			return styles[type] || {};
		}

		render()
		{
			const { showCaption } = this.props;
			const { value, maxValue } = this.config;

			if (!this.checkInitParams(value, maxValue))
			{
				return null;
			}

			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'column',
						alignItems: 'center',
					},
				},
				this.renderTitle(),
				this.renderProgressLine(),
				showCaption && new ProgressBarCaption({
					ref: (ref) => {
						this.captionRef = ref;
					},
					value,
					maxValue,
					style: this.getStyles('caption'),
				}),
				this.renderDescription(),
			);
		}

		renderProgressLine()
		{
			const { progressWidth } = this.state;
			const { value, color } = this.config;

			return View(
				{
					style: {
						width: SIZE.width,
						height: SIZE.height,
						borderRadius: 999,
						marginVertical: 10,
						backgroundColor: COLORS.backgroundColor,
					},
					onLayout: ({ width: layoutWidth }) => {
						if (layoutWidth && progressWidth !== layoutWidth)
						{
							this.setState({ progressWidth: layoutWidth });
						}
					},
				},
				View({
					ref: (ref) => {
						this.progressRef = ref;
					},
					style: {
						position: 'absolute',
						height: SIZE.height,
						width: this.getProgressWidth(value),
						backgroundColor: color,
					},
				}),
			);
		}

		renderTitle()
		{
			const { title } = this.props;

			if (!title || typeof title !== 'string')
			{
				return null;
			}

			return Text({
				style: {
					marginBottom: 12,
					fontSize: 19,
					textAlign: 'center',
					...this.getStyles('title'),
				},
				text: title,
			});
		}

		renderDescription()
		{
			const { description } = this.props;

			if (!description || typeof description !== 'string')
			{
				return null;
			}

			return Text({
				style: {
					marginTop: 18,
					fontSize: 16,
					textAlign: 'center',
					...this.getStyles('description'),
				},
				text: description,
			});
		}
	}

	module.exports = { ProgressBar };
});
