/**
 * @module layout/ui/scroll-view
 */
jn.define('layout/ui/scroll-view', (require, exports, module) => {
	const toArray = (children) => (Array.isArray(children) ? children : [children]);

	/**
	 * @function UIScrollView
	 * @param {Object} props
	 * @param {boolean} [props.horizontal]
	 * @param {boolean} [props.bounces]
	 * @param {function} [props.onScroll]
	 * @param {Array<View>} [props.children]
	 * @param {...*} [restChildren]
	 * @returns {ScrollViewMethods}
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

	module.exports = {
		ScrollView: UIScrollView,
		UIScrollView,
	};
});
