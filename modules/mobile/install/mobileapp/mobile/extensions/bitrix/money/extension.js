/**
 * @module money
 */
jn.define('money', (require, exports, module) => {
	const MAX_RETRY_COUNT = 3;

	const { isEmpty } = require('utils/object');
	const { number_format } = require('utils/string');

	/**
	 * @class Money
	 */
	class Money
	{
		static get defaultFormat()
		{
			return {
				FORMAT_STRING: '#',
				DEC_POINT: '.',
				THOUSANDS_SEP: ' ',
				DECIMALS: 2,
				HIDE_ZERO: 'N',
				TEMPLATE: {
					PARTS: ['#'],
					SINGLE: '#',
					VALUE_INDEX: 0,
				},
			};
		}

		constructor({ amount, currency })
		{
			this.amount = amount || 0;
			this.currency = currency;

			if (!isFinite(parseFloat(this.amount)))
			{
				throw new Error('Invalid money amount');
			}
		}

		static create({ amount, currency })
		{
			return new Money({ amount, currency });
		}

		static init(retryCount = 0)
		{
			if (retryCount >= MAX_RETRY_COUNT)
			{
				return Promise.reject('Max retry count exceeded.', MAX_RETRY_COUNT);
			}

			return new Promise((resolve, reject) => {
				const cache = new Cache('MoneyCurrencyFormatList');
				const cachedData = cache.get();

				if (isEmpty(cachedData))
				{
					BX.ajax.runAction('mobile.currency.format.list', {})
						.then((response) => {
							Money.formats = response.data || {};
							BX.postComponentEvent('Money::onLoad', []);
							cache.set(Money.formats);
							resolve(response.data);
						})
						.catch((response) => {
							reject(response.errors);
						})
						.finally(() => {
							if (isEmpty(Money.formats))
							{
								retryCount++;
								setTimeout(() => Money.init(retryCount), 200 * retryCount);
							}
						});
				}
				else
				{
					Money.formats = cachedData;
					BX.postComponentEvent('Money::onLoad', []);
					resolve(cachedData);
				}
			});
		}

		get formatted()
		{
			let result = this.numberFormat();
			result = this.format.FORMAT_STRING.replace(/(^|[^&])#/, `$1${result}`);

			return jnComponent.convertHtmlEntities(result);
		}

		get formattedAmount()
		{
			return jnComponent.convertHtmlEntities(this.numberFormat());
		}

		get editableFormattedAmount()
		{
			return jnComponent.convertHtmlEntities(this.editableNumberFormat());
		}

		get formattedCurrency()
		{
			const result = this.format.FORMAT_STRING.replace(/(^|[^&])#/, '$1');

			return jnComponent.convertHtmlEntities(result).trim();
		}

		get format()
		{
			return Money.formats[this.currency] || Money.defaultFormat;
		}

		get template()
		{
			return this.format.TEMPLATE;
		}

		get currencyName()
		{
			return this.format.FULL_NAME || Money.formattedCurrency;
		}

		/**
		 * @private
		 * @returns {string}
		 */
		numberFormat()
		{
			let decimals = this.format.DECIMALS;

			if (this.format.HIDE_ZERO === 'Y' && this.amount == parseInt(this.amount, 10))
			{
				decimals = 0;
			}

			return number_format(
				this.amount,
				decimals,
				this.format.DEC_POINT,
				this.format.THOUSANDS_SEP,
			);
		}

		editableNumberFormat()
		{
			const amount = this.amount.toString().replace('.', this.format.DEC_POINT);

			let [integerPart, decimalPart] = amount.split(this.format.DEC_POINT);
			decimalPart = decimalPart || '';

			integerPart = integerPart.replaceAll(/\B(?=(\d{3})+(?!\d))/g, this.format.THOUSANDS_SEP);
			if (amount.includes(this.format.DEC_POINT))
			{
				return `${integerPart}${this.format.DEC_POINT}${decimalPart}`;
			}

			return String(integerPart);
		}
	}

	Money.formats = {};

	Money.init();

	module.exports = {
		Money,
	};
});

// todo remove after all global usages in other modules will be cleaned
(function() {
	const require = (ext) => jn.require(ext);
	const { Money } = require('money');

	jnexport(Money);
})();
