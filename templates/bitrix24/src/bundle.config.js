module.exports = {
	input: './index.js',
	output: {
		js: '../dist/bitrix24.bundle.js',
		css: '../dist/bitrix24.bundle.css',
	},
	namespace: 'BX.Intranet.Bitrix24',
	browserslist: true,
	adjustConfigPhp: false,
};
