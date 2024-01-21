/**
 * @module lists/element-creation-guide/detail-step/skeleton
 */
jn.define('lists/element-creation-guide/detail-step/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Line, Circle } = require('utils/skeleton');

	class DetailStepSkeleton extends LayoutComponent
	{
		render()
		{
			const items = [
				{ key: '1', type: 'skeleton-item-36' },
				{ key: '2', type: 'skeleton-item-21' },
				{ key: '3', type: 'skeleton-item-21' },
				{ key: '4', type: 'skeleton-item-circle' },
				{ key: '5', type: 'skeleton-item-30' },
			];

			return ListView({
				style: { flex: 1 },
				data: [{ items }],
				isRefreshing: false,
				renderItem: this.renderItem.bind(this),
			});
		}

		renderItem(item)
		{
			return View(
				{},
				item.type === 'skeleton-item-circle' && this.renderCircleItem(),
				item.type !== 'skeleton-item-circle' && this.renderLineItem(item),
				this.renderSeparator(),
			);
		}

		renderLineItem(item)
		{
			let firstLineWidth = '38%';
			let secondLineWidth = '21%';

			if (item.type === 'skeleton-item-30')
			{
				firstLineWidth = '15%';
				secondLineWidth = '30%';
			}

			if (item.type === '')
			{
				firstLineWidth = '13%';
				secondLineWidth = '36%';
			}

			return View(
				{ style: { marginLeft: 18, marginBottom: 19 } },
				Line(firstLineWidth, 3, 18, 0, 88),
				Line(secondLineWidth, 6, 14, 0, 88),
			);
		}

		renderCircleItem()
		{
			return View(
				{
					style: { marginLeft: 18, marginBottom: 15 },
				},
				View(
					{
						style: { marginLeft: 1 },
					},
					Line('8%', 3, 18, 0, 88),
				),
				View(
					{
						style: {
							marginTop: 6,
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					Circle(18),
					View(
						{
							style: { marginLeft: 10 },
						},
						Line(80, 6, 0, 0, 88),
					),
				),
			);
		}

		renderSeparator()
		{
			return View(
				{
					style: {
						height: 1,
						marginLeft: 18,
						marginRight: 15,
						backgroundColor: AppTheme.colors.bgSeparatorSecondary,
					},
				},
			);
		}
	}

	module.exports = { DetailStepSkeleton };
});
