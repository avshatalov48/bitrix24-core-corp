/**
 * @module tasks/layout/checklist/list/src/checkbox-counter
 */
jn.define('tasks/layout/checklist/list/src/checkbox-counter', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { CheckBox } = require('layout/ui/checkbox');
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { pathToExtension } = require('tasks/layout/checklist/list/src/constants');

	const CHECKBOX_SIZE = 24;
	const IMPORTANT_SIZE = {
		width: 17,
		height: 17,
	};

	/**
	 * @class CheckBoxCounter
	 */
	class CheckBoxCounter extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.progressRef = null;

			this.state = {
				checked: props.checked,
				important: props.isImportant,
			};
			this.currentProgress = 0;

			this.handleOnClick = this.handleOnClick.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				checked: props.checked,
				important: props.isImportant,
			};
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			this.setProgress(nextProps);

			return !isEqual(nextState, this.state);
		}

		setProgress(props)
		{
			const { completedCount, totalCount } = props;

			this.updateProgress({ completedCount, totalCount });
		}

		isLogSuppressed()
		{
			return true;
		}

		toggleAnimateImportant(show)
		{
			return new Promise((resolve) => {
				this.setState({
					important: show,
				}, resolve);
			});
		}

		renderImportant()
		{
			const { important } = this.state;

			return View(
				{
					style: {
						position: 'absolute',
						top: -1,
						right: -2,
						width: important ? IMPORTANT_SIZE.width : 0,
						height: important ? IMPORTANT_SIZE.height : 0,
						opacity: important ? 1 : 0,
					},
					onClick: this.handleOnClick,
				},
				Image({
					style: IMPORTANT_SIZE,
					svg: {
						uri: `${pathToExtension}images/important.svg`,
					},
					resizeMode: 'center',
				}),
			);
		}

		render()
		{
			const { isDisabled, progressMode, counterMode } = this.props;
			const { checked } = this.state;

			const isShowProgress = (progressMode || counterMode) && !checked;

			return View(
				{
					style: {
						width: 28,
						height: 28,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							justifyContent: 'center',
							width: CHECKBOX_SIZE,
							height: CHECKBOX_SIZE,
							borderRadius: 11.5,
							opacity: isDisabled ? 0.5 : 1,
							backgroundColor: AppTheme.colors.bgSeparatorPrimary,
						},
						onClick: this.handleOnClick,
					},
					isShowProgress
						? this.renderProgressView()
						: new CheckBox({
							checked,
							onClick: this.handleOnClick,
						}),
				),
				this.renderImportant(),
			);
		}

		renderProgressView()
		{
			const { counterMode, totalCount, completedCount } = this.props;
			const calculateCurrentPercent = this.calculateCurrentPercent(completedCount, totalCount);

			let fontSize = (completedCount > 9 || totalCount > 9) ? 8 : 9;
			fontSize = (completedCount > 9 && totalCount > 9) ? 7 : fontSize;

			return ProgressView(
				{
					ref: (ref) => {
						this.progressRef = ref;
					},
					params: {
						type: 'circle',
						currentPercent: calculateCurrentPercent,
						color: AppTheme.colors.accentExtraDarkblue,
					},
					style: {
						width: CHECKBOX_SIZE,
						height: CHECKBOX_SIZE,
						justifyContent: 'center',
						alignItems: 'center',
					},
					onClick: this.handleOnClick,
				},
				View(
					{
						style: {
							position: 'absolute',
							width: 20,
							height: 20,
							justifyContent: 'center',
							alignItems: 'center',
							borderRadius: 10,
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
					counterMode && Text({
						style: {
							fontSize,
							textAlign: 'center',
						},
						numberOfLines: 1,
						text: `${completedCount}/${totalCount}`,
					}),
				),
			);
		}

		handleOnClick()
		{
			const { isDisabled, onClick } = this.props;
			const { checked } = this.state;

			if (isDisabled)
			{
				return;
			}

			this.setState(
				{ checked: !checked },
				() => {
					if (onClick)
					{
						onClick(!checked);
					}
				},
			);
		}

		updateProgress({ completedCount, totalCount })
		{
			const calculateCurrentPercent = this.calculateCurrentPercent(completedCount, totalCount);

			if (!this.progressRef || calculateCurrentPercent === this.currentProgress)
			{
				return Promise.resolve();
			}

			this.currentProgress = calculateCurrentPercent;

			return new Promise((resolve) => {
				this.progressRef.setProgress(
					calculateCurrentPercent,
					{
						duration: 500,
						style: 'linear',
					},
					resolve,
				);
			});
		}

		calculateCurrentPercent(completedCount, totalCount)
		{
			return parseInt(
				(completedCount > 0 ? (completedCount * 100 / totalCount) : 0).toFixed(0),
				10,
			);
		}
	}

	module.exports = { CheckBoxCounter };
});

