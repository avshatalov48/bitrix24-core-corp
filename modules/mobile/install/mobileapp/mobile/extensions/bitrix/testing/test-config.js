/**
 * @module testing/test-config
 */
jn.define('testing/test-config', (require, exports, module) => {

	const DEFAULT_TIMEOUT = 5000;

	/**
	 * @class TestConfig
	 */
	class TestConfig
	{
		timeout = DEFAULT_TIMEOUT;

		resetTimeout()
		{
			this.timeout = DEFAULT_TIMEOUT;
		}
	}

	module.exports = { config: new TestConfig() };
});
