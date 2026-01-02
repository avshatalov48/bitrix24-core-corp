/**
 * @module intranet/invite-new/src/numbered-list
 */
jn.define('intranet/invite-new/src/numbered-list', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent } = require('tokens');
	const { Text4, Text6 } = require('ui-system/typography/text');

	const itemCircleSize = 22;

	class NumberedList extends PureComponent
	{
		render()
		{
			const { items, style = {} } = this.props;
			const renderedItems = items.map((item, index) => this.renderItem(item, index));

			return View(
				{
					style: {
						...style,
					},
				},
				...renderedItems,
			);
		}

		renderItem(item, index)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginBottom: Indent.XL3.toNumber(),
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							width: itemCircleSize,
							height: itemCircleSize,
							backgroundColor: Color.accentSoftBlue2.toHex(),
							borderRadius: itemCircleSize / 2,
							marginHorizontal: Indent.XL.toNumber(),
							alignItems: 'center',
							justifyContent: 'center',
						},
					},
					Text6({
						text: String(index + 1),
						accent: true,
						color: Color.base2,
					}),
				),
				Text4({
					style: {
						flex: 1,
					},
					text: item.text,
				}),
			);
		}
	}

	module.exports = {
		NumberedList: (props) => new NumberedList(props),
	};
});
