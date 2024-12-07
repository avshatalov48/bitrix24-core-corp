/**
 * @module layout/ui/optimized-grid-view
 */
jn.define('layout/ui/optimized-grid-view', (require, exports, module) => {
	
	function OptimizedGridView(props) {
		function calculateRenderFully(component) {
			if (component == null) {
				return;
			}
			if (typeof component.render === "function") {
				let renderedComponent = component.render();
				calculateRenderFully(renderedComponent);;
				component.renderCache = renderedComponent;
			}
			if (Array.isArray(component.children)) {
				component.children.forEach((child) => {
					calculateRenderFully(child);
				});
			}
		}

		function doRenderItemsBatch(itemRenderRequests) {
			let result = itemRenderRequests.map((request) => {
				let item = props.renderItem(request.item, request.section, request.row);
				calculateRenderFully(item);
				return item;
			});

			return result;
		}

		return GridView(
			{
				...props, 
				renderItemsBatch: (itemRenderRequests) => {
					return doRenderItemsBatch(itemRenderRequests);
				}
			}
		);
	}

	module.exports = { OptimizedGridView };
});
