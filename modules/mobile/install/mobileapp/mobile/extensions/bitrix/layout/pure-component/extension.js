/**
 * @module layout/pure-component
 */
jn.define('layout/pure-component', (require, exports, module) => {

	const { isEqual } = require('utils/object');

	class PureComponent extends LayoutComponent
	{
		shouldComponentUpdate(nextProps, nextState)
		{
			return !isEqual(this.props, nextProps);
		}
	}

	module.exports = { PureComponent };

});
