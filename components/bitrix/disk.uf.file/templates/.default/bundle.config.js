module.exports = {
	input: './upload-menu-compatibility.js',
	output: './script.js',
	namespace: 'BX.Disk.UF',
	adjustConfigPhp: false,
	namespaceFunction: null,
	browserslist: true,
	protected: true,
	concat: {
		js: [
			'./script.js',
			'./script-old.js',
		],
	}
};