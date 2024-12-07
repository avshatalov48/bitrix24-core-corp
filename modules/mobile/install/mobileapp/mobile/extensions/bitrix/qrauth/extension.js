/**
 * @module qrauth
 */
jn.define('qrauth', (require, exports, module) => {
	const { QRCodeScannerComponent } = require('qrauth/src/scanner');
	const { QRCodeAuthComponent } = require('qrauth/src/auth');

	module.exports = {
		QRCodeAuthComponent,
		QRCodeScannerComponent,
	};
});

(function() {
	// eslint-disable-next-line no-undef
	include('SharedBundle');

	const require = (ext) => jn.require(ext);
	const { QRCodeScannerComponent, QRCodeAuthComponent } = require('qrauth');

	jnexport(QRCodeAuthComponent, QRCodeScannerComponent);
})();
