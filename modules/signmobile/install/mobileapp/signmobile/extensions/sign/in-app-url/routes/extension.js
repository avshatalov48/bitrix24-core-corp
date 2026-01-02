/**
 * @module sign/in-app-url/routes
 */
jn.define('sign/in-app-url/routes', (require, exports, module) => {
	const { SignOpener } = require('sign/opener');
	const { Entry } = require('sign/entry');

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register('/sign/link/member/:memberId/', eventOpenHandler)
			.name('sign:document:open');
		inAppUrl.register('/sign/documents/', () => {
			Entry.openE2bMaster();
		});
	};

	const eventOpenHandler = ({ memberId }) => {
		SignOpener.openSigning({
			memberId,
		});
	};
});
