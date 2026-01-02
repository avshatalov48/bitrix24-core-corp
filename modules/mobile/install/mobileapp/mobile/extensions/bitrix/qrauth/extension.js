/**
 * @module qrauth
 */
jn.define('qrauth', (require, exports, module) => {
	// eslint-disable-next-line no-undef
	include('SharedBundle');

	const { QRCodeScannerComponent } = require('qrauth/src/scanner');
	const { QRCodeAuthComponent } = require('qrauth/src/auth');

	module.exports = {
		QRCodeAuthComponent,
		QRCodeScannerComponent,
	};
});

// todo remove after all global usages in other modules will be cleaned
(function() {
	const require = (ext) => jn.require(ext);
	const { QRCodeScannerComponent, QRCodeAuthComponent } = require('qrauth');

	jnexport(QRCodeAuthComponent, QRCodeScannerComponent);
})();
