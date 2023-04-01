/**
 * @module layout/pure-component
 */
jn.define('layout/pure-component', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { log } = require('layout/pure-component/logger');

	const globalScope = this;
	const isBeta = Application.getApiVersion() >= 44 && Application.isBeta();

	/**
	 * @class PureComponent
	 */
	class PureComponent extends LayoutComponent
	{
		shouldComponentUpdate(nextProps, nextState)
		{
			// some version of Android had a bug with nextState wrapped in an array
			nextState = Array.isArray(nextState) ? nextState[0] : nextState;

			const isLogEnabled = isBeta && !this.isLogSuppressed() || globalScope.PURE_COMPONENT_DEBUG;
			const hasChanged = !isEqual(this.props, nextProps) || !isEqual(this.state, nextState);

			if (isLogEnabled && hasChanged)
			{
				log(this.constructor.name, this.props, nextProps, this.state, nextState);
			}

			return hasChanged;
		}

		/**
		 * @return {boolean}
		 */
		isLogSuppressed()
		{
			return false;
		}
	}

	module.exports = { PureComponent };
});
