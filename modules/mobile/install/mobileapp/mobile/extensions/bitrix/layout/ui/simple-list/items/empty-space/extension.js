/**
 * @module layout/ui/simple-list/items/empty-space
 */
jn.define('layout/ui/simple-list/items/empty-space', (require, exports, module) => {
	/**
	 * @class EmptySpace
	 */
	class EmptySpace extends LayoutComponent
	{
		render()
		{
			const { item } = this.props;
			const defaultHeight = (Application.getPlatform() === 'android' ? 0 : 20);
			const height = item?.height || defaultHeight;

			return View(
				{
					style: {
						height,
						backgroundColor: item?.color || '#f5f7f8',
					},
				},
				Text({
					style: {
						height,
					},
					text: '', // empty View can't be rendered in Android
				}),
			);
		}
	}

	module.exports = { EmptySpace };
});
