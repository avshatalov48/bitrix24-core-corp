/**
 * @module layout/ui/whats-new/items/skeleton
 */
jn.define('layout/ui/whats-new/items/skeleton', (require, exports, module) => {
	const { Line } = require('utils/skeleton');
	const { Color, Indent, Corner } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { HEADER_HEIGHT } = require('layout/ui/whats-new/meta');

	/**
	 * @class WhatsNewItemSkeleton
	 */
	class WhatsNewItemSkeleton extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						paddingTop: this.props.fullScreen && HEADER_HEIGHT,
						backgroundColor: Color.accentSoftBlue3.toHex(),
					},
				},
				...this.renderItems(),
			);
		}

		renderItems()
		{
			const count = this.props.length > 0 || this.getDefaultItemsCount();

			return Array.from({ length: count }).map(() => this.renderItem());
		}

		getDefaultItemsCount()
		{
			return 5;
		}

		renderItem()
		{
			return View(
				{},
				Card(
					{
						testId: 'whats-new-item-skeleton',
						hideCross: true,
						design: CardDesign.PRIMARY,
						style: {
							marginTop: Indent.M.toNumber(),
							marginBottom: Indent.XS.toNumber(),
							marginHorizontal: Indent.XL3.toNumber(),

							borderWidth: 1,
							borderColor: Color.accentSoftBlue1.toHex(),
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								justifyContent: 'space-between',
								marginBottom: Indent.XL.toNumber(),
							},
						},
						Line(120, 19),
						Line(61, 19),
					),
					Line(220, 24, 0, Indent.XL.toNumber()),
					Line(259, 16, 0, Indent.XL.toNumber()),
					Line(190, 16, 0, Indent.XL3.toNumber()),
					Line('100%', 106, 0, Indent.XL4.toNumber(), Corner.S.toNumber()),
					View(
						{
							style: {
								flexDirection: 'row',
								justifyContent: 'space-between',
							},
						},
						View(
							{
								style: {
									flexDirection: 'row',
									justifyContent: 'space-between',
									width: 162,
									marginRight: Indent.XL2.toNumber(),
								},
							},
							Line(40, 24),
							Line(40, 24),
							Line(40, 24),
						),
						Line(92, 24),
					),
				),
			);
		}
	}

	module.exports = {
		WhatsNewItemSkeleton,
	};
});
