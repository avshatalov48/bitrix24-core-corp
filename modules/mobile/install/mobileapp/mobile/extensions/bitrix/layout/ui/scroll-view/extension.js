/**
 * @module layout/ui/scroll-view
 */
jn.define('layout/ui/scroll-view', (require, exports, module) => {
	const toArray = (children) => (Array.isArray(children) ? children : [children]);

	/**
	 * @return {ScrollViewMethods}
	 */
	const UIScrollView = (props, ...restChildren) => {
		const { children, ...rest } = props;

		const wrapperViewProps = {};

		if (rest.horizontal)
		{
			wrapperViewProps.style = {
				flexDirection: 'row',
			};
		}

		const viewChildren = toArray(restChildren);
		const renderChildren = viewChildren.length > 0 ? viewChildren : toArray(children);

		return ScrollView(
			rest,
			View(
				wrapperViewProps,
				...renderChildren,
			),
		);
	};

	module.exports = { UIScrollView };
});
