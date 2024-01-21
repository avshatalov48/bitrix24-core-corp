/**
 * @module bizproc/workflow/starter/catalog-step/skeleton
 */
jn.define('bizproc/workflow/starter/catalog-step/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Line } = require('utils/skeleton');
	const { PureComponent } = require('layout/pure-component');

	class CatalogStepSkeleton extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.numberOfElements = props.numberOfElements;
		}

		render()
		{
			const items = (
				Array.from({ length: this.numberOfElements })
					.map((value, index) => {
						return { key: String(index), type: 'skeleton-item' };
					})
			);

			return ListView({
				style: { flex: 1 },
				data: [{ items }],
				isRefreshing: false,
				renderItem: this.renderItem.bind(this),
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
						Line(159, 6, 0, 0, 88),
						Line(85, 6, 14, 0, 88),
					),
					View(
						{
							style: { alignItems: 'center', justifyContent: 'center' },
						},
						Line(22, 18, 0, 0, 4),
					),
				),
				this.renderSeparator(),
			);
		}

		renderSeparator()
		{
			return View({ style: { height: 1, marginLeft: 18, backgroundColor: AppTheme.colors.bgSeparatorSecondary } });
		}
	}

	module.exports = { CatalogStepSkeleton };
});
