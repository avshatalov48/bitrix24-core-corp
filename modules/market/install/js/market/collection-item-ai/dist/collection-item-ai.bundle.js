this.BX = this.BX || {};
(function (exports) {
	'use strict';

	const CollectionItemAi = {
	  props: ['item'],
	  template: `
		<a class="market-item-ai" href="/sites/ai/" target="_parent">
			<div class="market-item-ai-title">{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_ITEM_AI_TITLE') }}</div>
			<div class="market-item-ai-button">{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_ITEM_AI_CREATE_SITE') }}</div>
		</a>
	`
	};

	exports.CollectionItemAi = CollectionItemAi;

}((this.BX.Market = this.BX.Market || {})));
