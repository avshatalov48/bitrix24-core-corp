module.exports = {
	input: 'src/push-invitations.js',
	output: {
		js: 'dist/push-invitations.bundle.js',
		css: 'src/style.css',
	},
	namespace: 'BX.Intranet',
	minification: true,
	browserslist: true,
	transformClasses: true,
};
