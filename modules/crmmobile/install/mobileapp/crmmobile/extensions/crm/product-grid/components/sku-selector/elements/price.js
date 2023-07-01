/**
 * @module crm/product-grid/components/sku-selector/elements/price
 */
jn.define('crm/product-grid/components/sku-selector/elements/price', (require, exports, module) => {
	const { Loc } = require('loc');

	const skip = () => {};

	function Price(props)
	{
		const { amount, currency, onClick, emptyPrice, taxMode } = props;

		const clickable = onClick && !emptyPrice;

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'flex-end',
					paddingBottom: 6,
				},
			},
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					onClick: () => (clickable ? onClick() : skip()),
				},
				!emptyPrice && MoneyView({
					money: Money.create({ amount, currency }),
					renderAmount: (formattedAmount) => Text({
						text: formattedAmount,
						style: {
							fontSize: 20,
							color: '#333333',
							fontWeight: 'bold',
						},
					}),
					renderCurrency: (formattedCurrency) => Text({
						text: formattedCurrency,
						style: {
							fontSize: 20,
							color: '#828B95',
							fontWeight: 'bold',
						},
					}),
				}),
				!emptyPrice && !taxMode && Image({
					style: {
						width: 12,
						height: 13,
						marginLeft: 10,
					},
					svg: {
						content: '<svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.9028 6.54857C11.9028 9.83544 9.23827 12.5 5.9514 12.5C2.66453 12.5 0 9.83544 0 6.54857C0 3.2617 2.66453 0.597168 5.9514 0.597168C9.23827 0.597168 11.9028 3.2617 11.9028 6.54857ZM6.8315 5.88918L4.62887 5.88951V6.5196H5.29013V9.19364H4.62887V9.8549H7.27393V9.19364H6.8315V5.88918ZM5.9514 4.84235C6.46511 4.84235 6.88155 4.4259 6.88155 3.9122C6.88155 3.39849 6.46511 2.98205 5.9514 2.98205C5.43769 2.98205 5.02125 3.39849 5.02125 3.9122C5.02125 4.4259 5.43769 4.84235 5.9514 4.84235Z" fill="#d5d7db"/></svg>',
					},
				}),
				emptyPrice && Text({
					text: Loc.getMessage('PRODUCT_GRID_CONTROL_SKU_SELECTOR_PRICE_EMPTY'),
					style: {
						fontSize: 16,
						color: '#828B95',
					},
				}),
			),
		);
	}

	module.exports = { Price };
});
