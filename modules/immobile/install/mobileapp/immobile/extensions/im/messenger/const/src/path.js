/**
 * @module im/messenger/const/path
 */
jn.define('im/messenger/const/path', (require, exports, module) => {
	const immobilePath = `${currentDomain}/bitrix/mobileapp/immobile/`;

	const Path = Object.freeze({
		toImmobile: immobilePath,
		toComponents: `${immobilePath}components/im/messenger/`,
		toExtensions: `${immobilePath}extensions/im/messenger/`,
	});

	module.exports = {
		Path,
	};
});
