/**
 * @module crm/timeline/item/ui/market-banner
 */
jn.define('crm/timeline/item/ui/market-banner', (require, exports, module) => {
	const { Loc } = require('loc');

	function MarketBanner({ onClick, onClose } = {})
	{
		const nothing = () => {};

		return View(
			{
				style: {
					backgroundColor: '#ecfafe',
					paddingTop: 8,
					paddingBottom: 8,
					paddingLeft: 12,
					paddingRight: 12,
					flexDirection: 'row',
					justifyContent: 'space-between',
					borderTopWidth: 1,
					borderTopColor: '#d3f4ff',
				},
				onClick: onClick || nothing,
			},
			View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							marginBottom: 4,
						},
					},
					Image({
						style: {
							width: 13,
							height: 14,
							marginRight: 4,
						},
						svg: {
							content: '<svg width="13" height="14" viewBox="0 0 13 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.44231 0.575379C6.42989 0.579146 6.41693 0.586681 6.40344 0.593462L0.585809 2.89616C0.440043 2.96473 0.387138 3.11316 0.382812 3.28795V10.7022C0.384432 10.868 0.47135 11.0254 0.585809 11.0699L6.34733 13.3483C6.41859 13.3799 6.51198 13.3754 6.58919 13.3543L12.4024 11.0578C12.5168 11.0111 12.6021 10.8506 12.6011 10.684V3.33617C12.6043 3.11012 12.5482 2.97751 12.3981 2.90819L6.55031 0.593529C6.51036 0.573186 6.47902 0.56483 6.44231 0.575379ZM6.47686 1.36502L11.3055 3.28185L6.47686 5.18662L1.64395 3.27583L6.47686 1.36502Z" fill="#2FC6F6"/></svg>',
						},
					}),
					Text({
						text: Loc.getMessage('CRM_TIMELINE_ITEM_MARKET_TITLE'),
						style: {
							fontWeight: 'bold',
							marginRight: 6,
						},
					}),
					Text({
						text: Loc.getMessage('CRM_TIMELINE_ITEM_MARKET_DESCRIPTION'),
						style: {},
					}),
				),
				View(
					{},
					Text({
						text: Loc.getMessage('CRM_TIMELINE_ITEM_MARKET_READ_MORE'),
						style: {
							color: '#2066B0',
						},
					}),
				),
			),
			View(
				{
					style: {
						padding: 12,
					},
					onClick: onClose || nothing,
				},
				Image({
					style: {
						width: 9,
						height: 9,
					},
					svg: {
						content: '<svg width="9" height="9" viewBox="0 0 9 9" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.942809 0.659668L0 1.60248L3.06424 4.66672L0 7.73096L0.94281 8.67377L4.00705 5.60953L7.07107 8.67355L8.01388 7.73074L4.94986 4.66672L8.01388 1.6027L7.07107 0.659891L4.00705 3.72391L0.942809 0.659668Z" fill="black" fill-opacity="0.4"/></svg>',
					},
				}),
			),
		);
	}

	module.exports = { MarketBanner };
});
