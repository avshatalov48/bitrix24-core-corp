/**
 * @module lists/element-creation-guide/catalog-step/skeleton
 */
jn.define('lists/element-creation-guide/catalog-step/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Line } = require('utils/skeleton');
	const { PureComponent } = require('layout/pure-component');

	class CatalogStepSkeleton extends PureComponent
	{
		render()
		{
			const items = [
				{ key: '1', type: 'skeleton-item' },
				{ key: '2', type: 'skeleton-item' },
				{ key: '3', type: 'skeleton-item' },
				{ key: '4', type: 'skeleton-item' },
			];

			return ListView({
				style: { flex: 1 },
				data: [{ items }],
				isRefreshing: false,
				renderItem: this.renderItem,
			});
		}

		renderItem()
		{
			return View(
				{},
				View(
					{
						style: {
							marginTop: 15,
							marginRight: 35,
							marginBottom: 18,
							marginLeft: 18,
							display: 'flex',
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					View(
						{},
						View(
							{},
							Line(159, 6, 0, 0, 88),
							Line(85, 6, 14, 0, 88),
						),
					),
					View(
						{
							style: { alignItems: 'center', justifyContent: 'center' },
						},
						Line(22, 18, 0, 0, 4),
					),
				),
				View(
					{
						style: {
							height: 1,
							marginLeft: 18,
							backgroundColor: AppTheme.colors.bgSeparatorSecondary,
						},
					},
				),
			);
		}
	}

	module.exports = { CatalogStepSkeleton };
});
