/**
 * @module ui-system/layout/area-list
 */
jn.define('ui-system/layout/area-list', (require, exports, module) => {
	const { Component } = require('tokens');
	const { ScrollView } = require('layout/ui/scroll-view');
	const { mergeImmutable } = require('utils/object');

	/**
	 * @function AreaList
	 * @param {Object} props
	 * @param {boolean} [props.divided]
	 * @param {Array<View>} children
	 * @return {AreaList}
	 */
	function AreaList(props = {}, ...children)
	{
		PropTypes.validate(AreaList.propTypes, props, 'AreaList');

		const {
			divided,
			...restProps
		} = props;

		const style = {
			height: '100%',
		};

		return ScrollView(
			mergeImmutable(restProps, { style }),
			...children.map((child, index) => {
				const isFirst = index === 0;

				if (!divided || isFirst)
				{
					return child;
				}

				return View(
					{
						style: {
							marginTop: Component.areaListGapMore.toNumber(),
						},
					},
					child,
				);
			}),
		);
	}

	AreaList.defaultProps = {
		divided: false,
	};

	AreaList.propTypes = {
		divided: PropTypes.bool,
	};

	module.exports = { AreaList };
});
