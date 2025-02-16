import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { currencyFormat } from 'booking.lib.currency-format';
import type { MoneyStatistics } from 'booking.model.interface';

import { Statistics } from '../statistics/statistics';

export const Profit = {
	data(): Object
	{
		return {
			increasedValue: 0,
		};
	},
	computed: {
		...mapGetters({
			moneyStatistics: `${Model.Interface}/moneyStatistics`,
		}),
		popupId(): string
		{
			return 'booking-booking-after-title-profit-popup';
		},
		title(): string
		{
			return this.loc('BOOKING_BOOKING_AFTER_TITLE_PROFIT_POPUP_TITLE');
		},
		rows(): { title: string, value: string }[]
		{
			const otherCurrencies = this.moneyStatistics?.month
				?.filter(({ currencyId }) => currencyId !== this.baseCurrencyId)
				?.map(({ currencyId }) => currencyId) ?? []
			;

			return [
				this.getTodayRow(this.baseCurrencyId),
				...otherCurrencies.map((currencyId: string) => this.getTodayRow(currencyId)),
				this.getMonthRow(this.baseCurrencyId),
				...otherCurrencies.map((currencyId: string) => this.getMonthRow(currencyId)),
			];
		},
		todayProfit(): number
		{
			return this.getTodayProfit(this.moneyStatistics);
		},
		todayProfitFormatted(): string
		{
			return this.formatTodayProfit(this.todayProfit);
		},
		increasedValueFormatted(): string
		{
			return this.formatTodayProfit(this.increasedValue);
		},
		baseCurrencyId(): string
		{
			return currencyFormat.getBaseCurrencyId();
		},
	},
	methods: {
		getTodayRow(currencyId: string): { title: string, value: string }
		{
			const title = this.loc('BOOKING_BOOKING_AFTER_TITLE_PROFIT_POPUP_TOTAL_TODAY');

			return {
				title: currencyId === this.baseCurrencyId ? title : '',
				value: `+${this.getTodayProfitFormatted(currencyId)}`,
			};
		},
		getMonthRow(currencyId: string): { title: string, value: string }
		{
			const title = this.loc('BOOKING_BOOKING_AFTER_TITLE_PROFIT_POPUP_MONTH', {
				'#MONTH#': DateTimeFormat.format('f'),
			});

			return {
				title: currencyId === this.baseCurrencyId ? title : '',
				value: `<div>${this.getMonthProfitFormatted(currencyId)}</div>`,
			};
		},
		getTodayProfitFormatted(currency: string): string
		{
			const statistics = this.moneyStatistics?.today?.find(({ currencyId }) => currencyId === currency);
			const profit = statistics?.opportunity ?? 0;

			return currencyFormat.format(currency, profit);
		},
		getMonthProfitFormatted(currency: string): string
		{
			const statistics = this.moneyStatistics?.month?.find(({ currencyId }) => currencyId === currency);
			const profit = statistics?.opportunity ?? 0;

			return currencyFormat.format(currency, profit);
		},
		getTodayProfit(statistics: MoneyStatistics | null): number
		{
			const statistic = statistics?.today?.find(({ currencyId }) => currencyId === this.baseCurrencyId);

			return statistic?.opportunity ?? 0;
		},
		formatTodayProfit(profit: number): string
		{
			return `+ <span>${currencyFormat.format(this.baseCurrencyId, profit)}</span>`;
		},
	},
	watch: {
		moneyStatistics(newValue: MoneyStatistics, previousValue: MoneyStatistics): void
		{
			this.increasedValue = this.getTodayProfit(newValue) - this.getTodayProfit(previousValue);
		},
	},
	components: {
		Statistics,
	},
	template: `
		<Statistics
			:value="todayProfit"
			:valueFormatted="todayProfitFormatted"
			:increasedValue="increasedValue"
			:increasedValueFormatted="increasedValueFormatted"
			:popupId="popupId"
			:title="title"
			:rows="rows"
		/>
	`,
};
