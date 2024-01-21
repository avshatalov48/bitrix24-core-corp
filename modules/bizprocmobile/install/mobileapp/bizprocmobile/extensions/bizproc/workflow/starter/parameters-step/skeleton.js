/**
 * @module bizproc/workflow/starter/parameters-step/skeleton
 */
jn.define('bizproc/workflow/starter/parameters-step/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Line, Circle } = require('utils/skeleton');
	const { PureComponent } = require('layout/pure-component');

	class ParametersStepSkeleton extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.items = [
				{ key: '1', type: 'skeleton-item-36' },
				{ key: '2', type: 'skeleton-item-21' },
				{ key: '3', type: 'skeleton-item-21' },
				{ key: '4', type: 'skeleton-item-circle' },
				{ key: '5', type: 'skeleton-item-30' },
			];
		}

		render()
		{
			return ListView({
				style: { flex: 1 },
				data: [{ items: this.items }],
				isRefreshing: false,
				renderItem: this.renderItem.bind(this),
			});
		}

		renderItem(item)
		{
			const isCircleItem = item.type === 'skeleton-item-circle';

			return View(
				{},
				isCircleItem && this.renderCircleItem(),
				!isCircleItem && this.renderLineItem(item),
				this.renderSeparator(),
			);
		}

		renderCircleItem()
		{
			return View(
				{ style: { marginLeft: 18, marginBottom: 15 } },
				View(
					{ style: { marginLeft: 1 } },
					Line('8%', 3, 18, 0, 88),
				),
				View(
					{ style: { marginTop: 6, flexDirection: 'row', alignItems: 'center' } },
					Circle(18),
					View(
						{ style: { marginLeft: 10 } },
						Line(80, 6, 0, 0, 88),
					),
				),
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

			if (item.type === 'skeleton-item-36')
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

	module.exports = { ParametersStepSkeleton };
});
