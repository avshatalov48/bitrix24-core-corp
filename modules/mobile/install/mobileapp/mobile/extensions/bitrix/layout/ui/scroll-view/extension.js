/**
 * @module layout/ui/scroll-view
 */
jn.define('layout/ui/scroll-view', (require, exports, module) => {
	/**
	 * @return {ScrollViewMethods}
	 */
	const UIScrollView = (props) => {
		const { children, ...rest } = props;

		const wrapperViewProps = {};

		if (rest.horizontal)
		{
			wrapperViewProps.style = {
				flexDirection: 'row',
			};
		}

		return ScrollView(
			rest,
			View(
				wrapperViewProps,
				...Array.isArray(children) ? children : [children],
			),
		);
	};

	module.exports = { UIScrollView };
});
