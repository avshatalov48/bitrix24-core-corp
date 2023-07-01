/**
 * @module im/messenger/lib/ui/base/loader
 */
jn.define('im/messenger/lib/ui/base/loader', (require, exports, module) => {

	const { Loc } = require('loc');
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
			this.text = props.text || Loc.getMessage('IMMOBILE_LOADER_ITEM_DEFAULT_TEXT');
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
							height: 24
						},
						tintColor: '#80333333',
						animating: true,
						size: 'small',
					}),
				),
				View(
					{
						style: {
							marginLeft: 3,
						},
					},
					Text(
						{
							style: {
								color: '#80333333',
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