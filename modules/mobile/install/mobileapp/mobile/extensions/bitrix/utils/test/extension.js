/**
 * @module utils/test
 */
jn.define('utils/test', (require, exports, module) => {
	/**
	 * @function createTestIdGenerator
	 * @param {Object} props
	 * @param {string} [props.prefix = '']
	 * @param {Object} [props.context]
	 * @returns {function(string = ''): string}
	 */
	const createTestIdGenerator = (props) => {
		const { prefix = '', context } = props;

		return ((suffix = '') => {
			const testId = context?.props?.testId;

			return [testId, prefix, suffix].filter(Boolean).join('-');
		});
	};

	module.exports = {
		createTestIdGenerator,
	};
});
