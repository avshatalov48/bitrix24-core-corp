/**
 * @module im/messenger/db/const
 */
jn.define('im/messenger/db/const', (require, exports, module) => {
	const FieldType = Object.freeze({
		integer: 'integer',
		text: 'text',
		date: 'date',
		boolean: 'boolean',
		json: 'json', /** @deprecated. use object or array instead */
		map: 'map',
		set: 'set',
		object: 'object',
		array: 'array',
	});

	const SqlFieldType = {
		[FieldType.integer]: 'INTEGER',
		[FieldType.text]: 'TEXT',
		[FieldType.date]: 'TEXT',
		[FieldType.boolean]: 'TEXT',
		[FieldType.json]: 'TEXT',
		[FieldType.map]: 'TEXT',
		[FieldType.object]: 'TEXT',
		[FieldType.array]: 'TEXT',
	};

	const FieldDefaultValue = Object.freeze({
		zeroInteger: 0,
		emptyText: '',
		noneText: 'none',
		emptyDate: '',
		falseBoolean: '0',
		trueBoolean: '1',
		null: null,
		emptyObject: {}, // these types are a literal format because it will be a JSON.stringify()
		emptyArray: [],
		emptyMap: {},
		emptySet: {},
	});

	module.exports = {
		FieldType,
		FieldDefaultValue,
		SqlFieldType,
	};
});
