/**
 * @module layout/ui/scroll-view
 */
jn.define('layout/ui/scroll-view', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');

	const toArray = (children) => (Array.isArray(children) ? children : [children]);

	/**
	 * @function UIScrollView
	 * @param {Object} props
	 * @param {boolean} [props.horizontal]
	 * @param {boolean} [props.bounces]
	 * @param {function} [props.onScroll]
	 * @param {Array<View>} [props.children]
	 * @param {Object} [props.viewProps]
	 * @param {...*} [restChildren]
	 * @returns {ScrollViewMethods}
	 */
	const UIScrollView = (props, ...restChildren) => {
		const { children, viewProps = {}, ...rest } = props;

		let wrapperViewProps = viewProps;

		if (rest.horizontal)
		{
			wrapperViewProps = mergeImmutable(
				{
					style: {
						flexDirection: 'row',
					},
				},
				wrapperViewProps,
			);
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
