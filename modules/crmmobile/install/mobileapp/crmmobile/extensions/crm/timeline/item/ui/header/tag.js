/**
 * @module crm/timeline/item/ui/header/tag
 */
jn.define('crm/timeline/item/ui/header/tag', (require, exports, module) => {
	const TagType = {
		PRIMARY: 'primary',
		SECONDARY: 'secondary',
		SUCCESS: 'success',
		WARNING: 'warning',
		FAILURE: 'failure',
	};

	const TagColors = {
		[TagType.PRIMARY]: {
			backgroundColor: '#e5f9ff',
			color: '#008dba',
		},
		[TagType.SECONDARY]: {
			backgroundColor: '#dfe0e3',
			color: '#828b95',
		},
		[TagType.SUCCESS]: {
			backgroundColor: '#eaf6c3',
			color: '#688800',
		},
		[TagType.WARNING]: {
			backgroundColor: '#fef3b8',
			color: '#ae914b',
		},
		[TagType.FAILURE]: {
			backgroundColor: '#ffe8e8',
			color: '#c21b16',
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
