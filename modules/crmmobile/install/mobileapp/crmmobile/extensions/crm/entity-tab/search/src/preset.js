/**
 * @module crm/entity-tab/search/preset
 */
jn.define('crm/entity-tab/search/preset', (require, exports, module) => {
	const { BaseItem } = require('crm/entity-tab/search/base-item');

	/**
	 * @class Preset
	 */
	class Preset extends BaseItem
	{
		constructor(props)
		{
			super(props);

			this.preset = {
				id: props.id,
				name: props.name,
			};
		}

		renderContent()
		{
			const content = [
				Text({
					style: this.styles.title,
					text: this.props.name,
					ellipsize: 'middle',
				}),
			];

			if (this.props.active)
			{
				content.push(
					Image({
						style: this.styles.closeIcon,
						svg: {
							content: this.icon,
						},
					}),
				);
			}

			return content;
		}

		getButtonBackgroundColor()
		{
			return (this.props.default ? null : '#2FC6F6');
		}

		getOnClickParams()
		{
			const params = super.getOnClickParams();
			params.preset = this.preset;

			return params;
		}

		isDefault()
		{
			return this.props.default;
		}
	}

	module.exports = { Preset };
});
