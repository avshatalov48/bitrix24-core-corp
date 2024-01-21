/**
 * @module layout/ui/buttons-toolbar
 */
jn.define('layout/ui/buttons-toolbar', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @function ButtonsToolbar
	 */
	function ButtonsToolbar(props)
	{
		const { buttons, ...passThroughProps } = props;

		const separatorStyle = (index) => (index ? {
			borderLeftWidth: 1,
			borderLeftColor: AppTheme.colors.bgSeparatorPrimary,
		} : {});

		return new UI.BottomToolbar({
			...passThroughProps,
			items: buttons.map((button, index) => View(
				{
					style: {
						flex: 1,
						paddingLeft: index === 0 ? 2 : 4,
						paddingRight: index === buttons.length - 1 ? 2 : 4,
						...separatorStyle(index),
					},
				},
				button,
			)),
		});
	}

	module.exports = { ButtonsToolbar };
});
