module.exports = {
	input: 'src/todo-editor.js',
	output: 'dist/todo-editor-v2.bundle.js',
	namespace: 'BX.Crm.Activity',
	browserslist: true,
	transformClasses: true,
	plugins: {
		resolve: true,
		custom: [
			{
				name: 'optional-check-dependencies',
				renderChunk(code, chunk, options) {
					const bxNamespaceEndRegex = /;\s*$/m;
					const additionalNamespace = 'this.BX.Location = this.BX.Location || {};';
					const transformedCode = code.replace(bxNamespaceEndRegex, `;\n${additionalNamespace}`);

					return {
						code: transformedCode,
						//map: null,
					};
				},
			},
		],
	},
};
