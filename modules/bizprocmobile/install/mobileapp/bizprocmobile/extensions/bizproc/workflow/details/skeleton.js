/**
 * @module bizproc/workflow/details/skeleton
 */
jn.define('bizproc/workflow/details/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { Line } = require('utils/skeleton');

	class WorkflowDetailsSkeleton extends PureComponent
	{
		render()
		{
			return View(
				{ style: { backgroundColor: AppTheme.colors.base7 } },
				this.renderWorkflow(),
			);
		}

		renderWorkflow()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						paddingHorizontal: 5,
						paddingVertical: 11,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						minHeight: device.screen.height * 0.85 - 250,
					},
				},
				View(
					{ style: { flexGrow: 1 } },
					View(
						{ style: { marginLeft: 11 } },
						Line(159, 10, 10, 7, 12),
						Line(196, 10, 12, 12, 12),
						Line(307, 4, 15, 8, 12),
						Line(278, 4, 11, 8, 12),
						Line(307, 4, 11, 9, 12),
						Line(175, 4, 11, 11, 12),
					),
					View(
						{
							style: {
								marginTop: 20,
								marginBottom: 2,
								padding: 11,
								borderRadius: 12,
								borderWidth: 1,
								borderColor: AppTheme.colors.bgSeparatorPrimary,
								backgroundColor: AppTheme.colors.bgContentSecondary,
							},
						},
						Line(74, 4, 11, 2, 12),
						Line(149, 8, 14, 15, 12),
						View({ style: { height: 1, backgroundColor: AppTheme.colors.bgSeparatorPrimary } }),
						Line(44, 4, 15, 1, 12),
						Line(216, 8, 13, 18, 12),
					),
				),
				View(
					{
						style: {
							marginTop: 22,
							marginHorizontal: 10,
							paddingHorizontal: 10,
							height: 36,
							borderRadius: 8,
							borderWidth: 1,
							borderColor: AppTheme.colors.base5,
							justifyContent: 'center',
							width: 90,
						},
					},
					Line(70, 8, 0, 0, 20),
				),
			);
		}
	}

	module.exports = { WorkflowDetailsSkeleton };
});
