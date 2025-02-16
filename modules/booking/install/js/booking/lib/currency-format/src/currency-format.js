import { Extension } from 'main.core';
import { CurrencyCore } from 'currency.currency-core';

type Settings = {
	baseCurrencyId: string,
	currencies: Currency[],
};

type Currency = {
	CURRENCY: string,
	FORMAT: Object,
};

class CurrencyFormat
{
	constructor()
	{
		CurrencyCore.setCurrencies(this.settings.currencies);
	}

	format(currencyId: string, value: number): string
	{
		return CurrencyCore.currencyFormat(value, currencyId, true);
	}

	getBaseCurrencyId(): string
	{
		return this.settings.baseCurrencyId;
	}

	get settings(): Settings
	{
		return Extension.getSettings('booking.lib.currency-format');
	}
}

export const currencyFormat = new CurrencyFormat();
