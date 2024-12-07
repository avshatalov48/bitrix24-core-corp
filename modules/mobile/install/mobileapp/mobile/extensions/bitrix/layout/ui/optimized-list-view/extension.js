/**
 * @module layout/ui/optimized-list-view
 */
jn.define('layout/ui/optimized-list-view', (require, exports, module) => {
	function OptimizedListView(props)
	{
		function calculateRenderFully(component)
		{
			if (component === null)
			{
				return;
			}

			if (typeof component?.render === 'function')
			{
				const renderedComponent = component.render();
				calculateRenderFully(renderedComponent);
				component.renderCache = renderedComponent;
			}

			if (Array.isArray(component?.children))
			{
				component.children.forEach((child) => {
					calculateRenderFully(child);
				});
			}
		}

		function doRenderItemsBatch(itemRenderRequests)
		{
			return itemRenderRequests.map((request) => {
				const item = props.renderItem(request.item, request.section, request.row);
				calculateRenderFully(item);

				return item;
			});
		}

		return ListView(
			{
				...props,
				renderItemsBatch: (itemRenderRequests) => {
					return doRenderItemsBatch(itemRenderRequests);
				},
			},
		);
	}

	module.exports = { OptimizedListView };
});
