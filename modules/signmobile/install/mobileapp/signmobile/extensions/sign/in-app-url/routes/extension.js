/**
 * @module sign/in-app-url/routes
 */
jn.define('sign/in-app-url/routes', (require, exports, module) => {
	const { SignOpener } = require('sign/opener');

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register('/sign/link/member/:memberId/', eventOpenHandler)
			.name('sign:document:open');
	};

	const eventOpenHandler = ({ memberId }) => {
		SignOpener.openSigning({
			memberId,
		});
	};
});