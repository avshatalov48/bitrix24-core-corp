/**
 * @module im/messenger/lib/dev/menu/logging/enabler
 */
jn.define('im/messenger/lib/dev/menu/logging/enabler', (require, exports, module) => {
	const { Icon } = require('assets/icons');

	class Enabler extends LayoutComponent
	{
		render()
		{
			const { onClick, checked } = this.props;

			return View(
				{
					clickable: true,
					style: this.getStyle(),
					onClick: () => {
						if (onClick)
						{
							onClick();
						}
					},
				},
				checked
					? Image({
						resizeMode: 'contain',
						style: {
							width: 20,
							height: 20,
							opacity: 1,
						},
						tintColor: '#FFFFFF',
						named: Icon.CHECK.getIconName(),
					})
					: null,
			);
		}

		getStyle()
		{
			const { checked } = this.props;
			const style = {
				alignItems: 'center',
				justifyContent: 'center',
				alignContent: 'center',
				width: 26,
				height: 26,
				borderRadius: 13,
				margin: 15,
				marginRight: 25,
			};

			if (checked)
			{
				style.backgroundColor = '#569464';
			}
			else
			{
				style.borderWidth = 1;
				style.borderColor = '#2f2f34';
			}

			return style;
		}
	}

	module.exports = {
		Enabler,
	};
});
