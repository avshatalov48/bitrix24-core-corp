/**
 * @module crm/ui/loading-progress
 */
jn.define('crm/ui/loading-progress', (require, exports, module) => {
	const { transparent } = require('utils/color');
	const { NotifyManager } = require('notify-manager');
	const { ProgressBar } = require('layout/ui/progress-bar');
	const { BitrixCloudLoader } = require('layout/ui/loaders/bitrix-cloud');

	/**
	 * @class LoadingProgress
	 */
	class LoadingProgressBar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.setValue = this.setValue.bind(this);
			this.progress = this.getProgress();
		}

		setValue(value)
		{
			this.progress.setValue(value);
		}

		componentDidMount()
		{
			BX.addCustomEvent('Crm.LoadingProgress::updateProgress', this.setValue);
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('Crm.LoadingProgress::updateProgress', this.setValue);
		}

		getProgress()
		{
			const { value, maxValue, title = '', description = '' } = this.props;

			return new ProgressBar({
				title,
				description,
				value,
				maxValue,
				showCaption: true,
				styles: {
					description: {
						color: '#7d858f',
					},
					caption: {
						color: '#6a737f',
					},
				},
			});
		}

		renderButton()
		{
			const { button } = this.props;

			if (!button)
			{
				return null;
			}

			const { text = '', style = {}, onClickEvent = '' } = button;

			return Button({
				testId: 'cancelCreateDeals',
				style: {
					position: 'relative',
					borderWidth: 1,
					borderColor: '#dfe0e3',
					borderRadius: 6,
					...style,
				},
				text,
				onClick: () => {
					if (onClickEvent)
					{
						BX.postComponentEvent(onClickEvent, []);
					}
				},
			});
		}

		render()
		{
			const { maxValue } = this.props;
			NotifyManager.hideLoadingIndicatorWithoutFallback();

			if (Number(maxValue) <= 0 || !Number.isInteger(Number(maxValue)))
			{
				console.error(`maxValue:${maxValue} not integer`);
				return null;
			}

			return View(
				{
					style: {
						top: 0,
						position: 'absolute',
						width: '100%',
						height: '100%',
						justifyContent: 'center',
						alignItems: 'center',
						paddingHorizontal: 36,
						backgroundColor: transparent('#ffffff', 0.95),
					},
					clickable: true,
				},
				BitrixCloudLoader({
					width: 130,
					height: 130,
					lottieOptions: {
						style: {
							marginBottom: 16,
						},
					},
				}),
				this.progress,
				this.renderButton(),
			);
		}
	}

	module.exports = { LoadingProgressBar };
});
