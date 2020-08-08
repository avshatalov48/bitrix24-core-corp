/* global assert */
import {Type} from 'main.core';
import Address from '../../../src/entity/address';
import StringConverter from '../../../src/entity/address/converter/stringconverter';
import Format from '../../../src/entity/format';
import AddressType from '../../../src/entity/address/addresstype';

describe('StringConverter', () =>
{
	it('Should be a function', () =>
	{
		assert(typeof StringConverter === 'function');
	});

	const testData = `{ 
				"id":175,
				"languageId":"en",
				"fieldCollection":{ 
					"100":"USA",
					"200":"Texas",
					"300":"Austin",
					"410":"617 Red River St"
				}
		}`;

	const formatData = {
		languageId: 'en',
		code: 'TEST_FORMAT',
		template: '',
		name: 'test format',
		// template: '{{#UNKNOWN#\n}}{{#LOCALITY#\n}}{{#ADM_LEVEL_1#\n}}{{#COUNTRY#}}',
		fieldCollection: [
			{
				type: AddressType.COUNTRY,
				name: 'Country',
				sort: 400,
			},
			{
				type: AddressType.ADM_LEVEL_1,
				name: 'Region',
				sort: 300,
			},
			{
				type: AddressType.LOCALITY,
				name: 'City',
				sort: 200,
			},
			{
				type: AddressType.ADDRESS_LINE_1,
				name: 'Other',
				sort: 100,
			}
		]
	};

	describe('convertAddressToString', () =>
	{
		it('Should return string using field sort', () =>
		{
			const address = new Address(JSON.parse(testData));
			const format = new Format(formatData);
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_FIELD_SORT,
				StringConverter.CONTENT_TYPE_HTML
			);

			assert(Type.isString(result));
			assert.equal(result, '617 Red River St, Austin, Texas, USA');
		});

		it('Should return string using field sort with space delimiter', () =>
		{
			const address = new Address(JSON.parse(testData));
			const format = new Format(formatData);
			format.delimiter = ' ';
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_FIELD_SORT,
				StringConverter.CONTENT_TYPE_HTML
			);

			assert(Type.isString(result));
			assert.equal(result, '617 Red River St Austin Texas USA');
		});

		it('Should return string using field type', () =>
		{
			const address = new Address(JSON.parse(testData));
			const format = new Format(formatData);
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_FIELD_TYPE,
				StringConverter.CONTENT_TYPE_HTML
			);

			assert(Type.isString(result));
			assert.equal(result, 'USA, Texas, Austin, 617 Red River St');
		});

		it('Should return string using template', () =>
		{
			const address = new Address(JSON.parse(testData));
			const format = new Format(formatData);
			format.template = '{{#ADDRESS_LINE_1#\n}}{{#LOCALITY#\n}}{{#ADM_LEVEL_1#\n}}{{#COUNTRY#}}';
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_TEMPLATE,
				StringConverter.CONTENT_TYPE_HTML
			);

			assert(Type.isString(result));
			assert.equal(result, '617 Red River St<br/>Austin<br/>Texas<br/>USA');
		});

		it('Should return string using template with text content type', () =>
		{
			const address = new Address(JSON.parse(testData));
			const format = new Format(formatData);
			format.template = '{{#ADDRESS_LINE_1#\n}}{{#LOCALITY#\n}}{{#ADM_LEVEL_1#\n}}{{#COUNTRY#}}';
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_TEMPLATE,
				StringConverter.CONTENT_TYPE_TEXT
			);

			assert(Type.isString(result));
			assert.equal(result, '617 Red River St\nAustin\nTexas\nUSA');
		});

		it('Should return string using template and remove redundant placeholders', () =>
		{
			const address = new Address(JSON.parse(testData));
			const format = new Format(formatData);
			format.template = '{{#ADDRESS_LINE_1#\n}}{{#REDUDANT#\n}}{{#LOCALITY#\n}}{{#REDUDANT#\n}}{{#ADM_LEVEL_1#\n}}{{#COUNTRY#}}{{#REDUDANT#\n}}';
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_TEMPLATE,
				StringConverter.CONTENT_TYPE_HTML
			);

			assert(Type.isString(result));
			assert.equal(result, '617 Red River St<br/>Austin<br/>Texas<br/>USA');
		});

		it('Should return string using template and remove 2 x redundant line brakes', () =>
		{
			const address = new Address(JSON.parse(`{ 
				"id":175,
				"languageId":"en",
				"fieldCollection":{ 
					"100":"USA",					
					"300":"Austin",
					"410":"617 Red River St"
				}
			}`));

			const format = new Format(formatData);
			format.template = '{{#ADDRESS_LINE_1#\n}}{{#LOCALITY#\n}}{{#ADM_LEVEL_1#}}\n{{#COUNTRY#}}{{#REDUDANT#\n}}';
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_TEMPLATE,
				StringConverter.CONTENT_TYPE_HTML
			);

			assert(Type.isString(result));
			assert.equal(result, '617 Red River St<br/>Austin<br/>USA');
		});

		it('Should return string using template and remove 3 x redundant line brakes', () =>
		{
			const address = new Address(JSON.parse(`{ 
				"id":175,
				"languageId":"en",
				"fieldCollection":{ 
					"100":"USA",						
					"410":"617 Red River St"
				}
			}`));

			const format = new Format(formatData);
			format.template = '{{#ADDRESS_LINE_1#\n}}{{#LOCALITY#}}\n{{#ADM_LEVEL_1#}}\n{{#COUNTRY#}}{{#REDUDANT#\n}}';
			const result = StringConverter.convertAddressToString(
				address,
				format,
				StringConverter.STRATEGY_TYPE_TEMPLATE,
				StringConverter.CONTENT_TYPE_HTML
			);

			assert(Type.isString(result));
			assert.equal(result, '617 Red River St<br/>USA');
		});
	});
});