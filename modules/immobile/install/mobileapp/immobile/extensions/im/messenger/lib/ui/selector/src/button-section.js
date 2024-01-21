/**
 * @module im/messenger/lib/ui/selector/button-section
 */
jn.define('im/messenger/lib/ui/selector/button-section', (require, exports, module) => {
	const AppTheme = require('apptheme');
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
				{},
				View(
					{
						style: {
							backgroundColor: AppTheme.colors.bgContentPrimary,
							paddingTop: 12,
							paddingBottom: 12,
							borderRadius: 12,
						},
					},
					...this.props.buttons,
				),
				View(
					{
						style: {
							height: 20,
							backgroundColor: AppTheme.colors.bgNavigation,
						},
					},
				),
			);
		}
	}

	module.exports = { ButtonSection };
});