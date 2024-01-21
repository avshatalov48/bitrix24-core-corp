/**
 * @module layout/ui/scroll-view
 */
jn.define('layout/ui/scroll-view', (require, exports, module) => {

	const UIScrollView = (props) => {
		const { children, ...rest } = props;

		return ScrollView(
			rest,
			View(
				{},
				...Array.isArray(children) ? children : [children],
			),
		);
	};

	module.exports = { UIScrollView };
});
