/**
 * @module crm/timeline/item/ui/body/blocks/money-pill
 */
jn.define('crm/timeline/item/ui/body/blocks/money-pill', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const AppTheme = require('apptheme');

	const CORNER_SVG = `<svg width="11" height="15" viewBox="0 0 11 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.78153 9.80851C0.336104 8.60889 0.336106 6.39111 1.78154 5.19149L6.08408 1.62063C8.03915 -0.00196496 11 1.38845 11 3.92915L11 11.0708C11 13.6115 8.03915 15.002 6.08407 13.3794L1.78153 9.80851Z" fill="${AppTheme.colors.accentExtraGrass}"/></svg>`;

	/**
	 * @class TimelineItemBodyMoneyPill
	 */
	class TimelineItemBodyMoneyPill extends TimelineItemBodyBlock
	{
		get opportunity()
		{
			return BX.prop.getInteger(this.props, 'opportunity', null);
		}

		get currencyId()
		{
			return BX.prop.getString(this.props, 'currencyId', null);
		}

		getFormattedMoneyWithCurrency()
		{
			return Money.create({ amount: this.opportunity, currency: this.currencyId }).formatted;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					svg: {
						content: CORNER_SVG,
					},
					style: {
						width: 11,
						height: 15,
						position: 'absolute',
					},
				}),
				View(
					{
						style: {
							backgroundColor: AppTheme.colors.accentExtraGrass,
							borderRadius: 66,
							height: 35,
							justifyContent: 'center',
							paddingLeft: 12,
							paddingRight: 15,
							marginLeft: 6,
						},
					},
					Text({
						style: {
							color: AppTheme.colors.baseWhiteFixed,
							fontWeight: '600',
							fontSize: 19,
							lineHeight: 12,

						},
						text: `+ ${this.getFormattedMoneyWithCurrency()}`,
					}),
				),
			);
		}
	}

	module.exports = { TimelineItemBodyMoneyPill };
});
