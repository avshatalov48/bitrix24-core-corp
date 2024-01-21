/**
 * @module im/messenger/const/promo
 */
jn.define('im/messenger/const/promo', (require, exports, module) => {
	const Promo = {
		immobileRelease2023: 'immobile:chat-v2:16112023:mobile',
		immobileVideo2020: 'im:video:01042020:mobile',
	};

	const PromoType = {
		widget: 'widget',
		spotlight: 'spotlight',
	};

	module.exports = { Promo, PromoType };
});
