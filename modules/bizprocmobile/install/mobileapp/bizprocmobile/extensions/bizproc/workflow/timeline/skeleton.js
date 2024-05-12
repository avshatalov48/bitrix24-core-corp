/**
 * @module bizproc/workflow/timeline/skeleton
 */
jn.define('bizproc/workflow/timeline/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { StepWrapper, StepContent } = require('bizproc/workflow/timeline/components');
	const { PureComponent } = require('layout/pure-component');
	const { Line, Circle } = require('utils/skeleton');

	class Skeleton extends PureComponent
	{
		render()
		{
			return View(
				{},
				this.renderFirstStep(),
				this.renderEmptySpace(24),
				this.renderSecondStep(),
				this.renderEmptySpace(24),
				this.renderLastStep(),
			);
		}

		/**
		 * @return {View}
		 */
		renderFirstStep()
		{
			return StepWrapper(
				{
					showBorders: true,
					backgroundColor: AppTheme.colors.bgContentSecondary,
				},
				this.renderCounter({ hasTail: true }),
				StepContent(
					{},
					this.renderFirstStepContent(),
				),
			);
		}

		renderSecondStep()
		{
			return StepWrapper(
				{},
				this.renderCounter({ hasTail: true, hasTrunk: true }),
				StepContent(
					{},
					this.renderSecondStepContent(),
				),
			);
		}

		renderLastStep()
		{
			return StepWrapper(
				{},
				this.renderCounter({ hasTail: false, hasTrunk: true }),
				StepContent(
					{},
					Line('22%', 10, 3),
					Line('51%', 10, 6),
				),
			);
		}

		renderCounter({ hasTrunk, hasTail })
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'flex-start',
						alignItems: 'center',
						paddingRight: 12,
						paddingLeft: 12,
						marginBottom: -20, // 12 + 8 - height from content to bottom borderline + distance between elements
					},
				},
				// line over counter
				View(
					{
						style: {
							alignItems: 'center',
							height: 12,
						},
					},
					hasTrunk && View({
						style: {
							width: 1,
							maxHeight: 12,
							flex: 1,
							backgroundColor: AppTheme.colors.base7,
						},
					}),
				),
				// counter
				View(
					{
						style: {
							justifyContent: 'center',
							height: 18,
							width: 18,
						},
					},
					// circle
					View(
						{
							style: {
								alignSelf: 'center',
								alignItems: 'center',
								justifyContent: 'center',
							},
						},
						Circle(18),
					),
				),
				// line under counter
				hasTail && View(
					{
						style: {
							flex: 1,
							alignItems: 'center',
							flexDirection: 'column',
						},
					},
					View({
						style: {
							width: 1,
							height: '100%',
							backgroundColor: AppTheme.colors.base7,
						},
					}),
				),
			);
		}

		renderFirstStepContent()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						width: '100%',
					},
				},
				Line('76%', 12),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							marginTop: 17,
							marginBottom: 23,
						},
					},
					Line('40%', 9),
					Line('13%', 9),
				),
				this.renderUser(),
				Line('36%', 12, 27),
				Line('93%', 8, 11),
				Line('53%', 8, 10),
				Line('38%', 4, 16),
			);
		}

		renderSecondStepContent()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						width: '100%',
					},
				},
				Line('76%', 12),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							alignItems: 'center',
							marginTop: 13,
							marginBottom: 18,
						},
					},
					Line('40%', 9),
					Circle(18),
				),
				this.renderUser(),
				Line('38%', 4, 20),
			);
		}

		renderEmptySpace(height)
		{
			return View(
				{
					style: {
						height,
						width: 18,
						marginLeft: 12,
						alignItems: 'center',
					},
				},
				View({
					style: {
						width: 1,
						height: '100%',
						backgroundColor: AppTheme.colors.base7,
					},
				}),
			);
		}

		renderUser()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Circle(32),
				View(
					{
						style: {
							flexDirection: 'column',
							marginLeft: 10,
							width: '100%',
						},
					},
					Line('52%', 12, 4),
					Line('38%', 4, 10),
				),
			);
		}
	}

	module.exports = { Skeleton };
});
