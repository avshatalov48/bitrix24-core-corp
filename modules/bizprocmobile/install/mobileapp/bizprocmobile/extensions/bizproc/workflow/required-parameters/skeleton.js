/**
 * @module bizproc/workflow/required-parameters/skeleton
 */
jn.define('bizproc/workflow/required-parameters/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Line } = require('utils/skeleton');
	const { PureComponent } = require('layout/pure-component');

	class WorkflowRequiredParametersSkeleton extends PureComponent
	{
		render()
		{
			return View(
				{ style: { paddingVertical: 12 } },
				this.renderSection(),
				this.renderSection(),
				this.renderSection(),
			);
		}

		renderSection()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						marginBottom: 12,
						paddingHorizontal: 16,
						paddingBottom: 10,
					},
				},
				View(
					{
						style: {
							borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
							borderBottomWidth: 0.5,
							marginBottom: 6,
						},
					},
					Line(180, 10, 15, 10),
				),
				View(
					{ style: { paddingVertical: 12 } },
					Line(150, 8),
					Line(60, 10, 10),
				),

			);
		}
	}

	module.exports = { WorkflowRequiredParametersSkeleton };
});
