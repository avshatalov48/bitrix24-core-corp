module.exports = {
	input: './src/index.js',
	output: './script.js',
	namespace: 'BX.Tasks',
	adjustConfigPhp: false,
	concat: {
		js: [
			'./src/legacy.js',
			'./script.js',
		],
	}
};
