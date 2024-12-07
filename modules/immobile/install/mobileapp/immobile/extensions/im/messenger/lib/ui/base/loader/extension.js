/**
 * @module im/messenger/lib/ui/base/loader
 */
jn.define('im/messenger/lib/ui/base/loader', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Theme } = require('im/lib/theme');

	class LoaderItem extends LayoutComponent
	{
		/**
		 *
		 * @param {Object} props
		 * @param {boolean} props.enable
		 * @param {string} [props.text]
		 */
		constructor(props)
		{
			super(props);
			this.state.enable = this.props.enable;

			this.text = Type.isString(props.text)
				? props.text
				: Loc.getMessage('IMMOBILE_LOADER_ITEM_DEFAULT_TEXT')
			;
		}

		render()
		{
			if (!this.state.enable)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						padding: 10,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				View(
					{},
					Loader({
						style: {
							height: 24,
						},
						tintColor: Theme.colors.base3,
						animating: true,
						size: 'small',
					}),
				),
				this.text === ''
					? null
					: View(
						{
							style: {
								marginLeft: 3,
							},
						},
						Text(
							{
								style: {
									color: Theme.colors.base3,
									fontSize: 18,
								},
								text: this.text,
							},
						),
					),
			);
		}

		disable()
		{
			this.setState({ enable: false });
		}

		enable()
		{
			this.setState({ enable: true });
		}

		isEnable()
		{
			return this.state.enable;
		}
	}

	module.exports = { LoaderItem };
});
