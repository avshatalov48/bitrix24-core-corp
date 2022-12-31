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
			backgroundColor: '#dcf6fe',
			color: '#1097c2',
		},
		[TagType.SECONDARY]: {
			backgroundColor: '#e0e2e4',
			color: '#79818b',
		},
		[TagType.SUCCESS]: {
			backgroundColor: '#e0f5c2',
			color: '#589309',
		},
		[TagType.WARNING]: {
			backgroundColor: '#faf4a0',
			color: '#9d7e2b',
		},
		[TagType.FAILURE]: {
			backgroundColor: '#ffe3e2',
			color: '#cf1515',
		},
		getColorByType(tagType) {
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
					style: {
						backgroundColor,
						paddingHorizontal: 8,
						paddingVertical: 4,
						borderRadius: 10,
						marginRight: 4,
						marginBottom: 8,
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
