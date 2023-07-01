import "./collection-item-ads.css";

export const CollectionItemAds = {
	props: [
		'item',
	],
	template: `
		<div class="market-item-ads-container">
			<div class="market-item-ads-label">{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_ITEM_ADS_JS_ADVERTISING') }}</div>
			<div class="market-item-ads-container-inner">
				<div class="market-item-ads-images-block" style="background-image: url('/bitrix/js/market/images/demo/img_2.png');">
					<div class="market-item-ads-content">
						<div class="market-item-ads-logo">
							<img src="/bitrix/js/market/images/demo/demo-logo.svg" alt="">
						</div>
						<div class="market-item-ads-description">{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_ITEM_ADS_JS_ADVERTISING_SPACE_PARTNERS') }}</div>
					</div>
				</div>
				<div class="market-item-ads-info-block" style="background: #800000;">
					<a href="" target="_blank" class="market-item-ads-btn">{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_ITEM_ADS_JS_CLICK_TO_VIEW') }}</a>
				</div>
			</div>
		</div>
	`,
}