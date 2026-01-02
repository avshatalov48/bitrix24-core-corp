/**
 * @module intranet/qualification/api
 */
jn.define('intranet/qualification/api', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	function getQualificationData()
	{
		return new Promise((resolve) => {
			const runActionExecutor = (
				new RunActionExecutor('bitrix24.v2.Qualification.getMobileConfiguration')
					.setSkipRequestIfCacheExists()
					.setCacheId('intranet.qualification.getMobileConfiguration')
					.setCacheHandler(() => resolve(null))
					.setHandler((response) => {
						runActionExecutor.getCache().saveData(response);
						resolve(response?.data);
					})
			);

			void runActionExecutor.call(true);
		});
	}

	function setPreset(presetId)
	{
		return new RunActionExecutor('mobile.tabs.setpreset', { name: presetId }).call();
	}

	function saveFieldValue(value, id, type)
	{
		return (
			new RunActionExecutor('intranetmobile.qualification.saveFieldValue', { value, id, type })
				.call()
		);
	}

	module.exports = {
		getQualificationData,
		setPreset,
		saveFieldValue,
	};
});
