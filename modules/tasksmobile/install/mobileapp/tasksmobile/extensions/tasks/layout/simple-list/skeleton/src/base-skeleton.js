/**
 * @module tasks/layout/simple-list/skeleton/src/base-skeleton
 */
jn.define('tasks/layout/simple-list/skeleton/src/base-skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');

	class TaskBaseItemSkeleton extends LayoutComponent
	{
		constructor(props) {
			super(props);

			this.visible = !this.props.fullScreen;
			this.visibilityContainer = null;
		}

		getDefaultItemsCount()
		{
			return 10;
		}

		componentDidMount() {
			setTimeout(() => {
				if (!this.visible && this.visibilityContainer)
				{
					this.visibilityContainer.animate({
						opacity: 1,
						duration: 300,
					}, () => {
						this.visible = true;
					});
				}
			}, 100);
		}

		render() {
			return View(
				{
					style: {
						opacity: this.visible ? 1 : 0,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
					ref: (ref) => {
						this.visibilityContainer = ref;
					},
				},
				...this.renderItems(),
			);
		}

		renderItems() {
			const count = this.props.length > 0 || this.getDefaultItemsCount();

			return Array.from({ length: count }).map((element, index) => this.renderItem(index, count));
		}

		renderItem(index, count)
		{
			const isLast = index === count - 1;

			return View(
				{
					style: {
						paddingHorizontal: 18,
					},
				},
				View(
					{
						style: {
							borderBottomWidth: isLast ? 0 : 1,
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
					),
				),
			);
		}
	}

	module.exports = { TaskBaseItemSkeleton };
});
