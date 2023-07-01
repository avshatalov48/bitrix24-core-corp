/**
* @module im/messenger/lib/rest
*/
jn.define('im/messenger/lib/rest', (require, exports, module) => {

	const runAction = (action, config = {}) => {
		return new Promise((resolve, reject) => {
			BX.ajax.runAction(action, config)
				.then((response) => {
					return resolve(response.data);
				})
				.catch((response) => {
					return reject(response.errors);
				})
			;
		});
	};

	module.exports = {
		runAction,
	};
});