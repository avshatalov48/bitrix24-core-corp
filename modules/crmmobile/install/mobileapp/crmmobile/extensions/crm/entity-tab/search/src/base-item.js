/**
 * @module crm/entity-tab/search/base-item
 */
jn.define('crm/entity-tab/search/base-item', (require, exports, module) => {
	const { Haptics } = require('haptics');

	/**
	 * @class BaseItem
	 */
	class BaseItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.icon = ICON;
			this.styles = styles;

			this.preset = null;
			this.counter = null;
		}

		render()
		{
			const { active, last } = this.props;

			return View(
				{
					style: this.styles.wrapper(active, this.getActiveColor(), this.props.default, last),
					onClick: () => this.onClick(),
				},
				...this.renderContent(),
			);
		}

		getButtonBackgroundColor()
		{
			this.abstract();
		}

		getActiveColor()
		{
			return '#c3f0ff';
		}

		renderContent()
		{
			this.abstract();
		}

		abstract(msg)
		{
			msg = msg || 'Abstract method must be implemented in child class';
			throw new Error(msg);
		}

		onClick()
		{
			Haptics.impactLight();

			const params = this.getOnClickParams();
			const active = !this.props.active;

			this.props.onClick(params, active);
		}

		getOnClickParams()
		{
			const params = {};
			const buttonBackgroundColor = this.getButtonBackgroundColor();
			if (buttonBackgroundColor)
			{
				params.data = {
					background: buttonBackgroundColor,
				};
			}

			return params;
		}
	}

	const styles = {
		wrapper: (active, color, isDefault = false, isLast = false) => {
			return {
				paddingHorizontal: 10,
				backgroundColor: active ? color : 'inherit',
				borderRadius: 30,
				justifyContent: 'center',
				alignItems: 'center',
				flexDirection: 'row',
				height: 32,
				marginLeft: isDefault ? 8 : 0,
				marginRight: (isLast && active) ? 8 : 0,
			};
		},
		title: {
			fontWeight: '500',
			fontSize: 16,
			lineHeight: 10,
			maxWidth: 300,
		},
		closeIcon: {
			marginLeft: 13,
			marginRight: 2,
			width: 8,
			height: 8,
		},
	};

	const ICON = '<svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.05882 0.000222688L8 0.941373L0.941178 8L1.38837e-06 7.05885L7.05882 0.000222688Z" fill="#828B95"/><path d="M0 0.94115L0.941176 0L8 7.05863L7.05882 7.99978L0 0.94115Z" fill="#828B95"/></svg>';

	module.exports = { BaseItem };
});
