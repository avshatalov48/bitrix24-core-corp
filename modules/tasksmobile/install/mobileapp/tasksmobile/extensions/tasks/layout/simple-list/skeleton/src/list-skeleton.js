/**
 * @module tasks/layout/simple-list/skeleton/src/list-skeleton
 */
jn.define('tasks/layout/simple-list/skeleton/src/list-skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const {
		Line,
		Circle,
	} = require('utils/skeleton');
	const { TaskBaseItemSkeleton } = require('tasks/layout/simple-list/skeleton/src/base-skeleton');

	class TaskListItemSkeleton extends TaskBaseItemSkeleton
	{
		renderItem(index, count)
		{
			return View(
				{
					style: {
						paddingHorizontal: 18,
					},
				},
				View(
					{
						style: {
							borderBottomWidth: index === count - 1 ? 0 : 1,
							borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
							paddingTop: 17,
							paddingBottom: 16,
						},
					},
					View(
						{
							style: {
								backgroundColor: AppTheme.colors.bgContentPrimary,
								flexGrow: 1,
								flexDirection: 'column',
							},
						},
						this.renderNameWithDateChange(),
						this.renderNameSecondLine(),
						this.renderAvatarWithDeadline(),
					),
				),
			);
		}

		renderNameWithDateChange()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						flexGrow: 1,
					},
				},
				Line('90%', 8, 2),
				Line(23, 8, 2),
			);
		}

		renderNameSecondLine()
		{
			return View(
				{
					style: {
						flexGrow: 1,
						marginTop: 11,
					},
				},
				Line(81, 8),
			);
		}

		renderAvatarWithDeadline()
		{
			return View(
				{
					style: {
						width: 125,
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						flexGrow: 1,
						marginTop: 18,
					},
				},
				Circle(24),
				Line(92, 18),
			);
		}
	}

	module.exports = { TaskListItemSkeleton };
});
