/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,currency_currencyCore) {
	'use strict';

	class CurrencyFormat {
	  constructor() {
	    currency_currencyCore.CurrencyCore.setCurrencies(this.settings.currencies);
	  }
	  format(currencyId, value) {
	    return currency_currencyCore.CurrencyCore.currencyFormat(value, currencyId, true);
	  }
	  getBaseCurrencyId() {
	    return this.settings.baseCurrencyId;
	  }
	  get settings() {
	    return main_core.Extension.getSettings('booking.lib.currency-format');
	  }
	}
	const currencyFormat = new CurrencyFormat();

	exports.currencyFormat = currencyFormat;

}((this.BX.Booking.Lib = this.BX.Booking.Lib || {}),BX,BX.Currency));
//# sourceMappingURL=currency-format.bundle.js.map
