/**
 * @module tasks/layout/simple-list/skeleton/src/list-skeleton
 */
jn.define('tasks/layout/simple-list/skeleton/src/list-skeleton', (require, exports, module) => {
	const { Color } = require('tokens');
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
							borderBottomWidth: index === count - 1 ? 0 : 0.5,
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
							paddingTop: 17,
							paddingBottom: 16,
						},
					},
					View(
						{
							style: {
								backgroundColor: Color.bgContentPrimary.toHex(),
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
						marginTop: 1,
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
						marginTop: 12,
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
						flexDirection: 'row',
						alignItems: 'center',
						flexGrow: 1,
						marginTop: 18,
					},
				},
				View(
					{
						style: {
							marginRight: 10,
						},
					},
					Circle(28),
				),
				Line(92, 18),
			);
		}
	}

	module.exports = { TaskListItemSkeleton };
});
