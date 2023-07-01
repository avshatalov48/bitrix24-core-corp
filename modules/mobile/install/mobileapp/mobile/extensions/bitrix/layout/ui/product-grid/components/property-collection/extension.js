jn.define('layout/ui/product-grid/components/property-collection', (require, exports, module) => {

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

			if (!properties || !properties.length)
			{
				return null;
			}

			return View(
				{
					style: Styles.wrapper
				},
				View(
					{
						style: Styles.left,
					},
					...properties.map(property => View(
						{
							onClick: () => hint(property.name)
						},
						Text({
							text: `${property.name}`,
							ellipsize: 'end',
							numberOfLines: 1,
							style: {
								color: '#bdc1c6',
								fontSize: 14,
							}
						})
					))
				),
				View(
					{
						style: Styles.right,
					},
					...properties.map(property => Text({
						text: `${property.value}`,
						style: {
							color: '#828B95',
							fontSize: 14,
						}
					}))
				),
			);
		}
	}

	const Styles = {
		wrapper: {
			borderRadius: 6,
			borderWidth: 1,
			borderColor: '#d5d7db',
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
		}
	};

	module.exports = { PropertyCollection };

});