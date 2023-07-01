/**
 * @module layout/ui/banners
 */
 jn.define('layout/ui/banners', (require, exports, module) => {

	const { BackdropHeader } = require('layout/ui/banners/backdrop-header');
	const { BannerButton } = require('layout/ui/banners/banner-button');

	module.exports = { BackdropHeader, BannerButton };
});