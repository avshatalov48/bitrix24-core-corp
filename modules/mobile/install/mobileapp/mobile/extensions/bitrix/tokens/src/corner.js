/**
 * @module tokens/src/corner
 * @return String
 */
jn.define('tokens/src/corner', (require, exports, module) => {
	const CornerTypes = Object.freeze({
		circle: 'circle',
		XL: 'XL',
		L: 'L',
		M: 'M',
		S: 'S',
		XS: 'XS',
	});

	const Corner = Object.freeze({
		[CornerTypes.circle]: 512,
		[CornerTypes.XL]: 18,
		[CornerTypes.L]: 12,
		[CornerTypes.M]: 8,
		[CornerTypes.S]: 6,
		[CornerTypes.XS]: 4,
	});

	module.exports = { Corner, CornerTypes };
});
