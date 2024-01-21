/**
 * @module layout/ui/fields/stage-selector/stage-item
 */
jn.define('layout/ui/fields/stage-selector/stage-item', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isLightColor } = require('utils/color');
	const STAGE_WIDTH = device.screen.width * 0.48;

	/**
	 * @class StageItemClass
	 */
	class StageItemClass extends LayoutComponent
	{
		render()
		{
			const { stage = {}, index, activeIndex, showMenu, onStageClick, onStageLongClick, isReversed } = this.props;
			const { color, name } = stage;
			const preparedColor = this.prepareColor(color);
			const backgroundColor = index > activeIndex ? AppTheme.colors.bgSecondary : preparedColor;

			const textContrastColor = calculateTextColor(backgroundColor);

			return View(
				{
					testId: getTestId(index, activeIndex),
					style: {
						height: 50,
						width: STAGE_WIDTH,
						marginRight: 8,
						paddingTop: 8,
						paddingBottom: 8,
						flexDirection: 'row',
						opacity: (this.isUnsuitable() ? 0.3 : 1),
					},
					onClick: onStageClick && (() => onStageClick(stage)),
					onLongClick: onStageLongClick && (() => onStageLongClick(stage)),
				},
				isReversed && Image(
					{
						style: {
							width: 15,
							height: 34,
							marginRight: -5,
						},
						resizeMode: 'contain',
						svg: {
							content: svgImages.reversedArrow(backgroundColor, preparedColor),
						},
					},
				),
				View(
					{
						style: {
							width: STAGE_WIDTH - 10,
							height: '100%',
							borderRadius: 5,
							backgroundColor: index > activeIndex ? AppTheme.colors.bgSecondary : backgroundColor,
							flexDirection: 'column',
							borderBottomWidth: 3,
							borderBottomColor: color,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								justifyContent: 'space-between',
								height: '100%',
								paddingLeft: 8,
								paddingRight: index < activeIndex ? 10 : 0,
							},
						},
						Text(
							{
								numberOfLines: 1,
								ellipsize: 'end',
								style: {
									height: 'auto',
									color: index > activeIndex ? AppTheme.colors.base4 : textContrastColor,
									fontWeight: '500',
									flexShrink: 2,
								},
								text: name,
							},
						),
						showMenu && Image(
							{
								style: {
									width: 8,
									height: 5,
									marginHorizontal: 5,
									marginTop: 6,
									marginBottom: 4,
								},
								svg: {
									content: svgImages.stageSelectArrow(textContrastColor),
								},
							},
						),
					),
				),
				!isReversed && Image(
					{
						style: {
							width: 15,
							height: 34,
							marginLeft: -5,
						},
						resizeMode: 'contain',
						svg: {
							content: svgImages.arrow(backgroundColor, preparedColor),
						},
					},
				),
			);
		}

		isUnsuitable()
		{
			return false;
		}

		prepareColor(color)
		{
			if (color && color.length === 6)
			{
				return `#${color}`;
			}

			return color;
		}
	}

	const calculateTextColor = (baseColor) => (isLightColor(baseColor)
		? AppTheme.colors.baseBlackFixed
		: AppTheme.colors.baseWhiteFixed
	);

	const getTestId = (index, activeIndex) => {
		if (index === activeIndex)
		{
			return 'CURRENT_STAGE';
		}

		const diff = Math.abs(index - activeIndex);
		const postfix = diff > 1 ? `_${diff}` : '';

		return index > activeIndex ? `NEXT_STAGE${postfix}` : `PREV_STAGE${postfix}`;
	};

	const svgImages = {
		stageSelectArrow: (color) => `<svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M7.48524 0.949718L3.94971 4.48525L0.414173 0.949718H7.48524Z" fill="${color}"/></svg>`,
		arrow: (
			backgroundColor,
			borderColor,
		) => `<svg width="15" height="34" viewBox="0 0 15 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0H0.314926C2.30669 0 4.16862 0.9884 5.28463 2.63814L13.8629 15.3191C14.5498 16.3344 14.5498 17.6656 13.8629 18.6809L5.28463 31.3619C4.16863 33.0116 2.30669 34 0.314926 34H0V0Z" fill="${backgroundColor}"/><path d="M0 31H5.5L5.2812 31.3282C4.1684 32.9974 2.29502 34 0.288897 34H0V31Z" fill="${borderColor}"/></svg>`,
		reversedArrow: (
			backgroundColor,
			borderColor,
		) => `<svg width="15" height="34" viewBox="0 0 15 34" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M15 0H14.6851C12.6933 0 10.8314 0.9884 9.71536 2.6381L1.13709 15.3191C0.450193 16.3344 0.450193 17.6656 1.13709 18.6809L9.71536 31.3619C10.8314 33.0116 12.6933 34 14.6851 34H15V0Z" fill="${backgroundColor}"/> <path d="M15 31H9.5L9.7188 31.3282C10.8316 32.9974 12.705 34 14.7111 34H15V31Z" fill="${borderColor}"/> </svg>`,
	};

	module.exports = {
		StageItemClass,
		STAGE_WIDTH,
	};
});
