/**
 * @module crm/timeline/item/ui/body/blocks/money
 */
jn.define('crm/timeline/item/ui/body/blocks/money', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineFontSize, TimelineFontColor, TimelineFontWeight } = require('crm/timeline/item/ui/styles');

	/**
	 * @class TimelineItemBodyMoney
	 */
	class TimelineItemBodyMoney extends TimelineItemBodyBlock
	{
		get opportunity()
		{
			return BX.prop.getInteger(this.props, 'opportunity', null);
		}

		get currencyId()
		{
			return BX.prop.getString(this.props, 'currencyId', null);
		}

		get color()
		{
			return BX.prop.getString(this.props, 'color', null);
		}

		getFormattedMoneyWithCurrency()
		{
			return Money.create({ amount: this.opportunity, currency: this.currencyId }).formatted;
		}

		render()
		{
			return Text({
				style: {
					fontSize: 14,
					color: TimelineFontColor.get(this.color),
					fontWeight: '400',
				},
				text: this.getFormattedMoneyWithCurrency(),
			});
		}
	}

	module.exports = { TimelineItemBodyMoney };
});
