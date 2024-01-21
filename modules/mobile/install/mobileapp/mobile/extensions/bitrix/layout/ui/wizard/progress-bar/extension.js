/**
 * @module layout/ui/wizard/progress-bar
 */
jn.define('layout/ui/wizard/progress-bar', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @class ProgressBar
	 */
	class ProgressBar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.settings = props.step.getProgressBarSettings();
			this.step = props.step;
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);

			if (props.step)
			{
				this.settings = props.step.getProgressBarSettings();
				this.step = props.step;
			}
		}

		render()
		{
			if (!this.settings.isEnabled)
			{
				return null;
			}

			return View(
				{},
				this.renderTopContent(),
				this.renderFooterContent(),
			);
		}

		renderTopContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-start',
						width: '100%',
						paddingTop: 16,
						paddingHorizontal: 16,
					},
				},
				this.step.renderNumberBlock(),
				this.renderProgressBarTitle(),
			);
		}

		renderFooterContent()
		{
			const stepLines = [];

			for (let number = 1; number <= this.settings.count; number++)
			{
				let backgroundColorValue = this.settings.nextLineColor;
				if (this.settings.number === number)
				{
					backgroundColorValue = this.settings.currentLineColor;
				}
				else if (this.settings.number > number)
				{
					backgroundColorValue = this.settings.previousLineColor;
				}

				stepLines.push(
					View(
						{
							style: {
								height: 4,
								backgroundColor: backgroundColorValue,
								flexShrink: 1,
								width: '100%',
								marginHorizontal: 1,
							},
						},
					),
				);
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
						justifyContent: 'space-around',
						marginTop: 15,
						paddingBottom: 8,
						paddingHorizontal: 16,
					},
				},
				...stepLines,
			);
		}

		renderProgressBarTitle()
		{
			const { title } = this.settings;

			return Text({
				text: title.text,
				style: {
					marginLeft: 11,
					marginTop: 4,
					fontWeight: '500',
					flexShrink: 1,
					fontSize: 16,
					color: AppTheme.colors.base1,
					...title.style,
				},
			});
		}
	}

	module.exports = { ProgressBar };
});
