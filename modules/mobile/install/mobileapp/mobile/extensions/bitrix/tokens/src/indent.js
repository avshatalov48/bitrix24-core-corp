/**
 * @module tokens/src/indent
 * @return String
 */
jn.define('tokens/src/indent', (require, exports, module) => {
	const IndentTypes = Object.freeze({
		XL4: 'XL4',
		XL3: 'XL3',
		XL2: 'XL2',
		XL: 'XL',
		L: 'L',
		M: 'M',
		S: 'S',
		XS: 'XS',
		XS2: 'XS2',
	});

	const Indent = Object.freeze({
		[IndentTypes.XL4]: 24,
		[IndentTypes.XL3]: 18,
		[IndentTypes.XL2]: 12,
		[IndentTypes.XL]: 12,
		[IndentTypes.L]: 10,
		[IndentTypes.M]: 8,
		[IndentTypes.S]: 6,
		[IndentTypes.XS]: 4,
		[IndentTypes.XS2]: 2,
	});

	module.exports = { Indent, IndentTypes };
});
