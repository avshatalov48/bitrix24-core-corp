/**
 * @module crm/entity-detail/component/right-buttons-provider
 */
jn.define('crm/entity-detail/component/right-buttons-provider', (require, exports, module) => {
	const { addImportButton } = require('crm/entity-detail/component/right-buttons-provider/import-from-contact-list');
	const { useInDuplicates } = require('crm/entity-detail/component/right-buttons-provider/use-in-duplicates');

	/**
	 * @param {*[]} buttons
	 * @param {DetailCardComponent} detailCard
	 * @returns {*[]}
	 */
	const rightButtonsProvider = (buttons, detailCard) => {
		buttons = addImportButton(buttons, detailCard);
		buttons = useInDuplicates(buttons, detailCard);

		return buttons;
	};

	module.exports = { rightButtonsProvider };
});
