/**
 * @module lists/element-creation-guide/description-step/skeleton
 */
jn.define('lists/element-creation-guide/description-step/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Line } = require('utils/skeleton');

	class DescriptionStepSkeleton extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'column',
						justifyContent: 'space-between',
						height: '100%',
					},
				},
				View(
					{ style: { margin: 16 } },
					Line(135, 8, 0, 0, 88),
					View(
						{ style: { marginTop: 4 } },
						Line('90%', 3, 12),
						Line('100%', 3, 12),
						Line('85%', 3, 12),
						Line('100%', 3, 12),
						Line('50%', 3, 12),
					),
				),
				View(
					{
						style: {
							margin: 16,
							borderWidth: 1,
							borderColor: AppTheme.colors.bgSeparatorSecondary,
							borderRadius: 18,
						},
					},
					View(
						{ style: { margin: 18 } },
						Line(114, 6),
						View(
							{ style: { marginRight: 15, marginTop: 8 } },
							Line('100%', 3, 10),
							Line('100%', 3, 10),
							Line('50%', 3, 10),
							Line('100%', 3, 18),
						),
					),
				),
			);
		}
	}

	module.exports = { DescriptionStepSkeleton };
});
