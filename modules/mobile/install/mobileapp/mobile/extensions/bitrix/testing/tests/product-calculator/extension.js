(() => {

	// @todo add more test cases

	const require = ext => jn.require(ext);

	const { describe, it, test, expect } = require('testing');

	const { DiscountType, ProductCalculator, TaxForSumStrategy } = require('crm/product-calculator');

	const defaultFields = {
		QUANTITY: 0,
		PRICE: 0,
		PRICE_EXCLUSIVE: 0,
		PRICE_NETTO: 0,
		PRICE_BRUTTO: 0,
		CUSTOMIZED: 'N',
		DISCOUNT_TYPE_ID: DiscountType.UNDEFINED,
		DISCOUNT_RATE: 0,
		TAX_INCLUDED: 'N',
		TAX_RATE: 0
	};

	describe('Product calculator basics', () => {

		test('initial fields should be immutable after calculations', () => {
			const calculator = new ProductCalculator(defaultFields);
			calculator.calculateDiscount(10);
			expect(calculator.getFields()).toEqual(defaultFields);
		});

		it('calculates price', () => {
			const fields = {
				...defaultFields,
				QUANTITY: 1,
				PRICE: 417.86,
				PRICE_BRUTTO: 835.71,
				PRICE_EXCLUSIVE: 379.87,
				PRICE_NETTO: 759.74,
				DISCOUNT_TYPE_ID: DiscountType.PERCENTAGE,
				DISCOUNT_RATE: 50,
				DISCOUNT_ROW: 379.87,
				DISCOUNT_SUM: 379.87,
				TAX_INCLUDED: 'Y',
				TAX_RATE: 10
			};

			const calculator = new ProductCalculator(fields);
			const result = calculator.calculatePrice(8000);

			expect(result).toEqual({
				BASE_PRICE: 8000,
				QUANTITY: 1,
				PRICE: 4000,
				PRICE_BRUTTO: 8000,
				PRICE_EXCLUSIVE: 3636.37,
				PRICE_NETTO: 7272.73,
				CUSTOMIZED: 'Y',
				DISCOUNT_TYPE_ID: DiscountType.PERCENTAGE,
				DISCOUNT_RATE: 50,
				DISCOUNT_ROW: 3636.36,
				DISCOUNT_SUM: 3636.36,
				TAX_INCLUDED: 'Y',
				TAX_RATE: 10,
				TAX_SUM: 363.64,
				SUM: 4000
			});
		});

		test('calculation using TaxForSumStrategy', () => {
			const fields = {
				...defaultFields,
				QUANTITY: 1,
				PRICE: 417.86,
				PRICE_BRUTTO: 835.71,
				PRICE_EXCLUSIVE: 379.87,
				PRICE_NETTO: 759.74,
				DISCOUNT_TYPE_ID: DiscountType.PERCENTAGE,
				DISCOUNT_RATE: 50,
				DISCOUNT_ROW: 379.87,
				DISCOUNT_SUM: 379.87,
				TAX_INCLUDED: 'Y',
				TAX_RATE: 10
			};

			const calculator = new ProductCalculator(fields);
			calculator.setCalculationStrategy(new TaxForSumStrategy(calculator));
			const result = calculator.calculatePrice(8000);

			expect(result).toEqual({
				BASE_PRICE: 8000,
				QUANTITY: 1,
				PRICE: 4000,
				PRICE_EXCLUSIVE: 4000,
				PRICE_NETTO: 8000,
				PRICE_BRUTTO: 8000,
				CUSTOMIZED: 'Y',
				DISCOUNT_TYPE_ID: DiscountType.PERCENTAGE,
				DISCOUNT_RATE: 50,
				DISCOUNT_SUM: 4000,
				DISCOUNT_ROW: 4000,
				TAX_INCLUDED: 'Y',
				TAX_RATE: 10,
				TAX_SUM: 363.64,
				SUM: 4000
			});
		});

	});

})();