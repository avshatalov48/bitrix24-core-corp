/**
 * @module crm/entity-detail/component/on-model-ready
 */
jn.define('crm/entity-detail/component/on-model-ready', (require, exports, module) => {
	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const onEntityModelReady = (detailCard) => {
		const categoryId = detailCard.getFieldFromModel('CATEGORY_ID', 0);

		detailCard.setComponentParams({ categoryId });
	};

	module.exports = { onEntityModelReady };
});
