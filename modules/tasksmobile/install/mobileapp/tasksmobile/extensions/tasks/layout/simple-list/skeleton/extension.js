/**
 * @module tasks/layout/simple-list/skeleton
 */
jn.define('tasks/layout/simple-list/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Line, Circle } = require('utils/skeleton');

	const DEFAULT_ITEMS_COUNT = 10;

	class TaskListItemSkeleton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: !this.props.fullScreen,
			};

			this.visibilityContainer = null;
		}

		componentDidMount()
		{
			setTimeout(() => {
				if (!this.state.visible && this.visibilityContainer)
				{
					this.visibilityContainer.animate({ opacity: 1, duration: 300 }, () => {
						this.state.visible = true;
					});
				}
			}, 100);
		}

		render()
		{
			return View(
				{},
				View(
					{
						style: {
							opacity: this.state.visible ? 1 : 0,
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
						ref: (ref) => {
							this.visibilityContainer = ref;
						},
					},
					...this.renderItems(),
				),
			);
		}

		renderItems()
		{
			const count = this.props.length > 0 || DEFAULT_ITEMS_COUNT;

			return Array.from({ length: count }).map((element, index) => View(
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
						TaskNameWithDateChange(),
						TaskNameSecondLine(),
						AvatarWithDeadline(),
					),
				),
			));
		}
	}

	const TaskNameWithDateChange = () => View(
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

	const TaskNameSecondLine = () => View(
		{
			style: {
				flexGrow: 1,
				marginTop: 11,
			},
		},
		Line(81, 8),
	);

	const AvatarWithDeadline = () => View(
		{
			style: {
				marginTop: 18,
			},
		},
		View(
			{
				style: {
					width: 125,
					flexDirection: 'row',
					justifyContent: 'space-between',
					alignItems: 'center',
					flexGrow: 1,
				},
			},
			Circle(24),
			Line(92, 18),
		),
	);

	module.exports = {
		TaskListItemSkeleton,
	};
});
