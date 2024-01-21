/**
 * @module crm/ui/loading-progress
 */
jn.define('crm/ui/loading-progress', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isNil } = require('utils/type');
	const { transparent } = require('utils/color');
	const { NotifyManager } = require('notify-manager');
	const { ProgressBar } = require('layout/ui/progress-bar');
	const { BitrixCloudLoader } = require('layout/ui/loaders/bitrix-cloud');

	/**
	 * @class LoadingProgress
	 */
	class LoadingProgressBar extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			const {
				show = false,
				value = 0,
				maxValue = 0,
				button = {},
				title = '',
				description = '',
			} = props;

			this.progressBar = null;
			this.state = {
				value,
				maxValue,
				button,
				title,
				description,
				show,
			};
			this.updateProgress = this.updateProgress.bind(this);
		}

		updateProgress(params)
		{
			const { show, value } = params;
			const { show: stateShow } = this.state;

			if (!isNil(show) && stateShow !== show)
			{
				this.setState(params);
			}

			if (this.progressBar && value)
			{
				this.progressBar.setValue(value);
			}
		}

		componentDidMount()
		{
			BX.addCustomEvent('Crm.LoadingProgress::updateProgress', this.updateProgress);
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('Crm.LoadingProgress::updateProgress', this.updateProgress);
		}

		getProgressBar()
		{
			const { value, maxValue, title = '', description = '' } = this.state;

			if (Number(maxValue) <= 0 || !Number.isInteger(Number(maxValue)))
			{
				return null;
			}

			return new ProgressBar({
				title,
				description,
				value,
				maxValue,
				showCaption: true,
				styles: {
					description: {
						color: AppTheme.colors.base4,
					},
					caption: {
						color: AppTheme.colors.base4,
					},
				},
			});
		}

		renderButton()
		{
			const { button } = this.state;

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
					borderColor: AppTheme.colors.base6,
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
			const { show } = this.state;
			NotifyManager.hideLoadingIndicatorWithoutFallback();

			if (!this.progressBar)
			{
				this.progressBar = this.getProgressBar();
			}

			return View(
				{
					style: {
						position: show ? 'absolute' : 'relative',
						display: show ? 'flex' : 'none',
						top: 0,
						width: '100%',
						height: '100%',
						justifyContent: 'center',
						alignItems: 'center',
						paddingHorizontal: 36,
						backgroundColor: transparent(AppTheme.colors.bgContentPrimary, 0.95),
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
				this.progressBar,
				this.renderButton(),
			);
		}
	}

	module.exports = { LoadingProgressBar };
});
