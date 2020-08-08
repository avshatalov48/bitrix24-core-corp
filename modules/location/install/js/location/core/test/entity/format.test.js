/* global assert */
import Format from "../../src/entity/format";
import FormatField from "../../src/entity/format/formatfield";

describe('Format', () => {

	it('Should be a function', () => {
		assert(typeof Format === 'function');
	});

	it('Should be constructed successfully without fields', () => {
		let format = new Format({
			languageId: 'en',
			code: 'test code',
			name: 'test name'
		});
		assert.ok(format instanceof Format);
	});

	it('Should be constructed successfully with fields', () => {
		let countryField = {
				type: 100,
				name: 'Country',
				sort: 100,
				description: 'The country of the buyer'
			},
			regionField = {
				type: 200,
				name: 'Region',
				sort: 200,
				description: 'The region of the buyer'
			},
			cityField = {
				type: 300,
				name: 'City',
				sort: 300,
				description: 'The City of the buyer'
			};

		let format = new Format({
			languageId: 'en',
			code: 'test code',
			name: 'test name',
			fieldCollection:[
				countryField,
				regionField,
				cityField
			]
		});

		assert.ok(format instanceof Format);

		let field = format.getField(200);
		assert.ok(field instanceof FormatField);
		assert.equal(field.type, 200);
		assert.equal(field.name, 'Region');
		assert.equal(field.sort, 200);
		assert.equal(field.description, 'The region of the buyer');
	});

	describe('setter', () => {
		it('Should return setted values', () => {
			let format = new Format({
				languageId: 'en',
				code: 'test code',
				name: 'test name'
			});

			assert.equal(format.languageId, 'en');
		});
	});

	describe('isFieldExists', () => {
		it('Should check field existence', () => {
			let format = new Format({
				languageId: 'en',
				code: 'test code',
				name: 'test name',
				fieldCollection:[{
					type: 100,
					name: 'Country',
					sort: 100,
					description: 'The country of the buyer'
				}]
			});

			assert.ok(format.isFieldExists(100));
			assert.equal(format.isFieldExists(200), false);
		});
	});
});