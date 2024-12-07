/**
 * @module tasks/layout/simple-list/skeleton/src/kanban-skeleton
 */
jn.define('tasks/layout/simple-list/skeleton/src/kanban-skeleton', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Line, Circle } = require('utils/skeleton');
	const { TaskBaseItemSkeleton } = require('tasks/layout/simple-list/skeleton/src/base-skeleton');

	class TaskKanbanItemSkeleton extends TaskBaseItemSkeleton
	{
		getDefaultItemsCount()
		{
			return 5;
		}

		renderItem(index, count)
		{
			return View(
				{
					style: {
						paddingTop: index === 0 ? 16 : 0,
						paddingBottom: index === count - 1 ? 0 : 10,
						backgroundColor: Color.bgPrimary.toHex(),
					},
				},
				View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: Color.bgContentPrimary.toHex(),
							flexGrow: 1,
							flexDirection: 'column',
						},
					},
					this.renderHeader(),
					this.renderStageSelector(),
					this.renderSpace(),
					this.renderResponsible(),
				),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: {
						justifyContent: 'space-between',
						paddingTop: 20,
						paddingHorizontal: 16,
						paddingBottom: 21,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					Line(94, 16),
					Line(38, 16),
				),
				Line(130, 10, 7),
				View(
					{
						style: {
							marginTop: 21,
							flexDirection: 'row',
							width: 99,
							justifyContent: 'space-between',
						},
					},
					Circle(21),
					Line(66, 21, 0, 0, 10),
				),
			);
		}

		renderStageSelector()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginBottom: 33,
						marginLeft: 16,
						width: 373,
						justifyContent: 'space-between',
					},
				},
				Line(181, 29),
				Line(181, 29),
			);
		}

		renderSpace()
		{
			return View(
				{
					style: {
						paddingHorizontal: 16,
						marginBottom: 18,
					},
				},
				Line(130, 10, 0, 10),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							alignItems: 'center',
							width: 163,
						},
					},
					Circle(21),
					Line(130, 10),
				),
			);
		}

		renderResponsible()
		{
			return View(
				{
					style: {
						paddingHorizontal: 16,
						marginBottom: 29,
					},
				},
				Line(130, 10, 0, 10),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							width: 87,
						},
					},
					Circle(21),
					Circle(21),
					Circle(21),
				),
			);
		}
	}

	module.exports = { TaskKanbanItemSkeleton };
});
