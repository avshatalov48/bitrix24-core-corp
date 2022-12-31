/**
 * @module testing/report
 */
jn.define('testing/report', (require, exports, module) => {

	class TestingReport
	{
		constructor()
		{
			this.log = [];
		}

		get totalAssertions()
		{
			return this.log.filter(item => item.type === 'success' || item.type === 'fail').length;
		}

		get totalFailures()
		{
			return this.log.filter(item => item.type === 'fail').length;
		}

		group(title)
		{
			this.log.push({ type: 'groupStart', title });
		}

		groupEnd()
		{
			this.log.push({ type: 'groupEnd' });
		}

		success(message)
		{
			this.log.push({ type: 'success', message });
		}

		fail(message, expected, actual)
		{
			this.log.push({ type: 'fail', message, expected, actual });
		}
	}

	module.exports = {
		TestingReport,
	};

});