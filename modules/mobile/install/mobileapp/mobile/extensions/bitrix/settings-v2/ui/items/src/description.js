/**
 * @module settings-v2/ui/items/src/description
 */
jn.define('settings-v2/ui/items/src/description', (require, exports, module) => {
	const { Text5 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { createTestIdGenerator } = require('utils/test');

	class DescriptionItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-description-item',
				context: this,
			});
		}

		render()
		{
			const { id, text } = this.props;

			return Text5(
				{
					testId: this.getTestId(id),
					color: Color.base4,
					text,
					style: {
						marginTop: Indent.L.toNumber(),
					},
				},
			);
		}
	}

	module.exports = {
		DescriptionItem,
	};
});
