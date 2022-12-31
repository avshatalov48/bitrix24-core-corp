/**
 * @module layout/ui/buttons-toolbar
 */
jn.define('layout/ui/buttons-toolbar', (require, exports, module) => {
	/**
	 * @function ButtonsToolbar
	 */
	function ButtonsToolbar(props)
	{
		const { buttons, ...passThroughProps } = props;

		const separatorStyle = (index) => index ? {
			borderLeftWidth: 1,
			borderLeftColor: '#eef2f4',
		} : {};

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
