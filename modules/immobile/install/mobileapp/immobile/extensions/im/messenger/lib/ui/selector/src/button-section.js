/**
 * @module im/messenger/lib/ui/selector/button-section
 */
jn.define('im/messenger/lib/ui/selector/button-section', (require, exports, module) => {

	class ButtonSection extends LayoutComponent
	{
		/**
		 *
		 * @param {Object}props
		 *
		 */
		constructor(props)
		{
			super(props);
			if (props.ref)
			{
				props.ref(this);
			}
		}

		render()
		{
			return View(
				{
					style: {
						marginTop: 10,
					},
				},
				...this.props.buttons,
				View(
					{
						style: {
							height: 20,
							backgroundColor: '#f6f7f8',
						},
					},
				),
			);
		}
	}

	module.exports = { ButtonSection };
});