/**
 * @module crm/timeline/item/ui/body/blocks/money-pill
 */
jn.define('crm/timeline/item/ui/body/blocks/money-pill', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const pathToImages = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/timeline/item/ui/body/blocks/money-pill/images/`;

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
					svg: { uri: `${pathToImages}corner.svg` },
					style: {
						width: 11,
						height: 15,
						position: 'absolute',
					},
				}),
				View(
					{
						style: {
							backgroundColor: '#59BD51',
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
							color: '#ffffff',
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
