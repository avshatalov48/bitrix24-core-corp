jn.define('layout/ui/product-grid/components/property-collection', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { hint } = require('layout/ui/product-grid/components/hint');

	class PropertyCollection extends LayoutComponent
	{
		/**
		 * @param {{
		 *     items: {name: '', value: ''}
		 * }} props
		 */
		constructor(props)
		{
			super(props);
		}

		render()
		{
			const properties = this.props.items;

			if (!properties || properties.length === 0)
			{
				return null;
			}

			return View(
				{
					style: Styles.wrapper,
				},
				View(
					{
						style: Styles.left,
					},
					...properties.map((property) => View(
						{
							onClick: () => hint(property.name),
						},
						Text({
							text: String(property.name),
							ellipsize: 'end',
							numberOfLines: 1,
							style: {
								color: AppTheme.colors.base5,
								fontSize: 14,
							},
						}),
					)),
				),
				View(
					{
						style: Styles.right,
					},
					...properties.map((property) => Text({
						text: String(property.value),
						style: {
							color: AppTheme.colors.base3,
							fontSize: 14,
						},
					})),
				),
			);
		}
	}

	const Styles = {
		wrapper: {
			borderRadius: 6,
			borderWidth: 1,
			borderColor: AppTheme.colors.bgSeparatorPrimary,
			paddingTop: 6,
			paddingBottom: 6,
			paddingLeft: 10,
			paddingRight: 10,
			marginBottom: 16,
			flexDirection: 'row',
		},
		left: {
			flexGrow: 1,
			paddingRight: 4,
			maxWidth: '50%',
		},
		right: {
			flexGrow: 2,
		},
	};

	module.exports = { PropertyCollection };
});
