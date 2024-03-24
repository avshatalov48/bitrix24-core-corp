/**
 * @module layout/pure-component
 */
jn.define('layout/pure-component', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { log } = require('layout/pure-component/logger');

	const { PURE_COMPONENT_DEBUG = false } = this;
	const isBeta = Application.isBeta();

	/**
	 * @class PureComponent
	 */
	class PureComponent extends LayoutComponent
	{
		shouldComponentUpdate(nextProps, nextState)
		{
			// some version of Android had a bug with nextState wrapped in an array
			nextState = Array.isArray(nextState) ? nextState[0] : nextState;

			const hasStateChanged = !isEqual(this.state, nextState);
			if (hasStateChanged)
			{
				return true;
			}

			const hasPropsChanged = !isEqual(this.props, nextProps);
			if (hasPropsChanged)
			{
				this.logComponentDifference(this.props, nextProps, this.state, nextState);
			}

			return hasPropsChanged;
		}

		/**
		 * @protected
		 * @param {object} prevProps
		 * @param {object} nextProps
		 * @param {object} prevState
		 * @param {object} nextState
		 */
		logComponentDifference(prevProps, nextProps, prevState, nextState)
		{
			const isLogEnabled = (isBeta && !this.isLogSuppressed()) || PURE_COMPONENT_DEBUG;
			if (isLogEnabled)
			{
				log(this.getComponentDisplayName(), prevProps, nextProps, prevState, nextState);
			}
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isLogSuppressed()
		{
			return false;
		}

		/**
		 * @protected
		 * @return {string}
		 */
		getComponentDisplayName()
		{
			return this.constructor.name;
		}
	}

	module.exports = { PureComponent };
});
