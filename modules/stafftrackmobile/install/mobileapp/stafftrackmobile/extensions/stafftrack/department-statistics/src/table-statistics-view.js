/**
 * @module stafftrack/department-statistics/table-statistics-view
 */
jn.define('stafftrack/department-statistics/table-statistics-view', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { Text5 } = require('ui-system/typography/text');

	const { PureComponent } = require('layout/pure-component');

	class TableStatisticsView extends PureComponent
	{
		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							paddingBottom: Indent.L.toNumber(),
						},
					},
					Text5({
						text: this.props.left.title,
						color: Color.base4,
					}),
					Text5({
						text: this.props.right.title,
						color: Color.base4,
					}),
				),
				(this.props.items.length > 0) && UIScrollView(
					{
						showsVerticalScrollIndicator: false,
						style: {
							flex: 1,
						},
						children: this.props.items.map((item) => this.renderItem(item)),
					},
				),
			);
		}

		renderItem(item)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingVertical: Indent.S.toNumber(),
						marginBottom: Indent.XS.toNumber(),
					},
					onClick: () => this.props.onItemClick?.(item),
				},
				this.props.left.render(item),
				this.renderSeparator(),
				this.props.right.render(item),
			);
		}

		renderSeparator()
		{
			return View(
				{
					style: {
						height: 1,
						flex: 1,
						backgroundColor: Color.bgSeparatorPrimary.toHex(),
						marginHorizontal: Indent.XL.toNumber(),
					},
				},
			);
		}
	}

	module.exports = { TableStatisticsView };
});
