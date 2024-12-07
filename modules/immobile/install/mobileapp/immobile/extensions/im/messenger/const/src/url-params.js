/**
 * @module im/messenger/const/url-params
 */
jn.define('im/messenger/const/url-params', (require, exports, module) => {

	const UrlGetParameter = Object.freeze({
		openChat: 'IM_DIALOG',
		openMessage: 'IM_MESSAGE',
		openLines: 'IM_LINES',
		openCopilotChat: 'IM_COPILOT',
	});

	module.exports = { UrlGetParameter };
});
