/**
 * @module layout/ui/copilot-role-selector/src/views
 */
jn.define('layout/ui/copilot-role-selector/src/views', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { SafeImage } = require('layout/ui/safe-image');
	const { information, chevronRight } = require('layout/ui/copilot-role-selector/src/icons');
	const { Line, Circle } = require('utils/skeleton');

	const RolesListItem = ({ item, isLastItem = false, clickHandler = null, industryName = '', showIndustry = true }) => {
		return View(
			{
				style: {
					paddingLeft: 16,
					width: '100%',
				},
				onClick: () => {
					if (clickHandler)
					{
						clickHandler(item);
					}
				},
			},
			View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						paddingTop: 20,
						paddingRight: 16,
					},
				},
				RolesListItemImage(item),
				View(
					{
						style: {
							flexDirection: 'column',
							flex: 1,
							marginLeft: 18,
							paddingBottom: 20,
							borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
							borderBottomWidth: isLastItem ? 0 : 1,
						},
					},
					Text({
						style: {
							color: AppTheme.colors.base1,
							fontSize: 18,
							fontWeight: '500',
						},
						text: item.name,
					}),
					showIndustry && View(
						{
							style: {
								flexDirection: 'row',
								width: '100%',
								alignItems: 'flex-start',
								justifyContent: 'flex-start',
								marginBottom: 6,
								marginTop: 2,
							},
						},
						Text({
							style: {
								color: AppTheme.colors.base4,
								fontSize: 13,
								fontWeight: '500',
								marginRight: 5,
							},
							text: Loc.getMessage('COPILOT_CONTEXT_STEPPER_INDUSTRY_LABEL_TEXT'),
						}),
						Text({
							style: {
								flex: 1,
								color: AppTheme.colors.base2,
								fontSize: 13,
								fontWeight: '500',
							},
							text: industryName,
						}),
					),
					Text({
						style: {
							color: AppTheme.colors.base4,
							fontSize: 15,
							fontWeight: '400',
							marginTop: 6,
						},
						text: item.description,
					}),
				),
			),
		);
	};

	const ListUniversalRoleItem = (universalRoleItemData, clickHandler) => {
		return RolesListItem({
			item: universalRoleItemData,
			showIndustry: false,
			clickHandler,
		});
	};

	const ListFeedbackItem = ({ item, isLastItem = false, clickHandler = null }) => {
		const titleColor = AppTheme.colors.base3;

		return View(
			{
				style: {
					paddingLeft: 16,
					width: '100%',
				},
				onClick: () => {
					if (clickHandler)
					{
						clickHandler(item);
					}
				},
			},
			View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						paddingTop: 20,
						paddingRight: 16,
					},
				},
				FeedbackItemImage(),
				View(
					{
						style: {
							flexDirection: 'column',
							flex: 1,
							marginLeft: 18,
							paddingBottom: 20,
							borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
							borderBottomWidth: isLastItem ? 0 : 1,
						},
					},
					Text({
						style: {
							color: titleColor,
							fontSize: 18,
							fontWeight: '500',
						},
						text: item.name,
					}),
					Text({
						style: {
							color: AppTheme.colors.base4,
							fontSize: 15,
							fontWeight: '400',
						},
						text: item.description,
					}),
				),
			),
		);
	};

	const FeedbackItemImage = () => {
		return View(
			{
				style: {
					width: 48,
					height: 48,
					borderRadius: 24,
					marginTop: 4,
					backgroundColor: AppTheme.colors.base6,
					justifyContent: 'center',
					alignContent: 'center',
				},
			},
			Image({
				svg: {
					content: information(AppTheme.colors.baseWhiteFixed),
				},
				tintColor: AppTheme.colors.baseWhiteFixed,
				style: {
					width: 24,
					height: 24,
					alignSelf: 'center',
				},
			}),
		);
	};

	const RolesListItemImage = (item) => {
		return SafeImage({
			uri: encodeURI(item.avatar.small),
			style: {
				width: 48,
				height: 48,
				borderRadius: 24,
				marginTop: 4,
			},
			placeholder: `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/copilot-role-selector/image/avatar_copilot.png`,
		});
	};

	const RolesListSkeleton = (count = 10) => {
		const items = [];
		for (let i = 0; i < count; i++)
		{
			items.push(RolesListItemSkeleton(i, count));
		}

		return View(
			{
				style: {
					flexDirection: 'column',
					width: '100%',
					height: '100%',
					backgroundColor: AppTheme.colors.bgContentPrimary,
					borderTopLeftRadius: 12,
					borderTopRightRadius: 12,
				},
			},
			...items,
		);
	};

	const RolesListItemSkeleton = (index, count) => {
		const lineWidths = [
			{
				firstLine: 80,
				secondLine: 90,
				thirdLine: 70,
			},
			{
				firstLine: 70,
				secondLine: 75,
				thirdLine: 85,
			},
			{
				firstLine: 55,
				secondLine: 50,
				thirdLine: 70,
			},
		];
		const firstLineWidth = `${lineWidths[index % lineWidths.length].firstLine}%`;
		const secondLineWidth = `${lineWidths[index % lineWidths.length].secondLine}%`;
		const thirdLineWidth = `${lineWidths[index % lineWidths.length].thirdLine}%`;

		return View(
			{
				style: {
					paddingLeft: 16,
					width: '100%',
				},
			},
			View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						paddingTop: 20,
						paddingRight: 16,
					},
				},
				View(
					{
						style: {
							marginTop: 4,
						},
					},
					Circle(48),
				),
				View(
					{
						style: {
							flexDirection: 'column',
							flex: 1,
							marginLeft: 18,
							paddingBottom: 24,
							borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
							borderBottomWidth: index === count - 1 ? 0 : 1,
						},
					},
					Line(firstLineWidth, 12, 4, 11, 4),
					Line(secondLineWidth, 11, 0, 5, 4),
					Line(thirdLineWidth, 11, 0, 0, 4),
				),
			),
		);
	};

	const IndustriesListItem = ({ item, isLastItem = false, clickHandler = null }) => {
		return View(
			{
				style: {
					paddingLeft: 14,
					width: '100%',
				},
				onClick: () => {
					if (clickHandler)
					{
						clickHandler(item);
					}
				},
			},
			View(
				{
					style: {
						width: '100%',
						height: 58,
						flexDirection: 'row',
						paddingRight: 14,
						borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
						borderBottomWidth: isLastItem ? 0 : 1,
						alignItems: 'center',
					},
				},
				Text({
					style: {
						color: AppTheme.colors.base1,
						fontSize: 18,
						fontWeight: '400',
						flex: 1,
						lineSpacing: 0,
						lineHeightMultiple: 1,
					},
					numberOfLines: 1,
					text: item.name,
					ellipsize: 'end',
				}),
				Image({
					svg: {
						content: chevronRight(AppTheme.colors.base6),
					},
					tintColor: AppTheme.colors.base6,
					style: {
						width: 10,
						height: 16,
						alignSelf: 'center',
					},
				}),
			),
		);
	};

	const IndustriesListSkeleton = (count = 10) => {
		const items = [];
		for (let i = 0; i < count; i++)
		{
			items.push(renderIndustriesListItemSkeleton(i, count));
		}

		return View(
			{
				style: {
					flexDirection: 'column',
					width: '100%',
					height: '100%',
					backgroundColor: AppTheme.colors.bgContentPrimary,
					borderTopLeftRadius: 12,
					borderTopRightRadius: 12,
				},
			},
			...items,
		);
	};

	const renderIndustriesListItemSkeleton = (index, count) => {
		const lineWidths = [60, 70, 80, 70, 60];
		const currentItemWidth = `${lineWidths[index % lineWidths.length]}%`;

		return View(
			{
				style: {
					paddingLeft: 14,
					width: '100%',
				},
			},
			View(
				{
					style: {
						width: '100%',
						height: 58,
						flexDirection: 'row',
						paddingVertical: 20,
						paddingRight: 14,
						borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
						borderBottomWidth: index === count - 1 ? 0 : 1,
						justifyContent: 'space-between',
					},
				},
				Line(currentItemWidth, 14, 1, 0, 4),
				Image({
					svg: {
						content: chevronRight(AppTheme.colors.base6),
					},
					tintColor: AppTheme.colors.base6,
					style: {
						width: 10,
						height: 16,
						alignSelf: 'flex-end',
					},
				}),
			),
		);
	};

	module.exports = {
		RolesListItem,
		RolesListSkeleton,
		IndustriesListItem,
		IndustriesListSkeleton,
		ListFeedbackItem,
		ListUniversalRoleItem,
	};
});
