function concatFilesBefore(options)
{
	const fs = require('fs');
	return {
		name: 'concatFilesBefore',
		generateBundle(file, bundle, isWrite)
		{
			if (!isWrite || !bundle['app.bundle.js']) return;
			bundle['app.bundle.js'].code = "(function(){\n\n"
				+ options.input.reduce((s, fileName) => s + fs.readFileSync(fileName) + "\n\n\n", '')
				+ bundle['app.bundle.js'].code
				+ "\n\n})();"
			;
			bundle['app.bundle.js'].map = false;
		},
	};
}

module.exports = {
	input: './src/app.js',
	output: './dist/app.bundle.js',
	namespace: 'b24form',
	protected: true,
	adjustConfigPhp: false,
	plugins: {
		custom: [
			concatFilesBefore({input: [
				'./babelhelpers/babel-external-helpers.js',
				'./src/vue/vue2.js',
			]})
		],

	}
};