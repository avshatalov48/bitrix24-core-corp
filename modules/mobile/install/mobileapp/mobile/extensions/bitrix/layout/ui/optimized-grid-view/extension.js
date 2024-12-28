/**
 * @module layout/ui/optimized-grid-view
 *
 * Should be kept in sync with http://mobile.git.bx/main/android/-/blob/master/apps/test-app/assets/test-core/optimized_grid_view.js
 * Send message to https://bitrix24.team/company/personal/user/1084627/ if you want to change this file
 */

jn.define('layout/ui/optimized-grid-view', (require, exports, module) => {
	function OptimizedGridView(props)
	{
		function calculateRenderFully(component)
		{
			if (component === null)
			{
				return;
			}

			if (typeof component.render === 'function')
			{
				const renderedComponent = component.render();
				calculateRenderFully(renderedComponent);

				component.renderCache = renderedComponent;
			}

			if (Array.isArray(component.children))
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

		return GridView(
			{
				...props,
				renderItemsBatch: (itemRenderRequests) => {
					return doRenderItemsBatch(itemRenderRequests);
				},
			},
		);
	}

	module.exports = { OptimizedGridView };
});
