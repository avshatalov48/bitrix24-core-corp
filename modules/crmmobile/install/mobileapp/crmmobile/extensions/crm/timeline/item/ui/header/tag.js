/**
 * @module crm/timeline/item/ui/header/tag
 */
jn.define('crm/timeline/item/ui/header/tag', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const TagType = {
		PRIMARY: 'primary',
		SECONDARY: 'secondary',
		SUCCESS: 'success',
		WARNING: 'warning',
		FAILURE: 'failure',
	};

	const TagColors = {
		[TagType.PRIMARY]: {
			backgroundColor: AppTheme.colors.accentSoftBlue2,
			color: AppTheme.colors.accentSoftElementBlue1,
		},
		[TagType.SECONDARY]: {
			backgroundColor: AppTheme.colors.base6,
			color: AppTheme.colors.base3,
		},
		[TagType.SUCCESS]: {
			backgroundColor: AppTheme.colors.accentSoftGreen2,
			color: AppTheme.colors.accentSoftElementGreen1,
		},
		[TagType.WARNING]: {
			backgroundColor: AppTheme.colors.accentSoftOrange1,
			color: AppTheme.colors.accentExtraBrown,
		},
		[TagType.FAILURE]: {
			backgroundColor: AppTheme.colors.accentSoftRed2,
			color: AppTheme.colors.accentSoftElementRed1,
		},
		getColorByType(tagType)
		{
			return this[tagType] || this[TagType.SECONDARY];
		},
	};

	class Tag extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		get text()
		{
			return BX.prop.getString(this.props, 'title', '');
		}

		get type()
		{
			return BX.prop.getString(this.props, 'type', null);
		}

		render()
		{
			const { backgroundColor, color } = TagColors.getColorByType(this.type);

			return View(
				{
					testId: `TimelineItemHeaderTag_${this.type}`,
					style: {
						backgroundColor,
						paddingHorizontal: 8,
						paddingVertical: 4,
						borderRadius: 10,
						marginRight: 4,
					},
				},
				Text(
					{
						style: {
							color,
							flexShrink: 2,
							fontSize: 9,
							fontWeight: '700',
						},
						ellipsize: 'end',
						numberOfLines: 1,
						text: this.text.toLocaleUpperCase(env.languageId),
					},
				),
			);
		}
	}

	module.exports = { Tag };
});
