/**
 * @module crm/entity-actions/change-crm-mode
 */
jn.define('crm/entity-actions/change-crm-mode', (require, exports, module) => {
	const { Loc } = require('loc');
	const { CrmMode } = require('crm/crm-mode');

	/**
	 * @function getActionChangeCrmMode
	 * @returns {object}
	 */
	const getActionChangeCrmMode = () => {
		const id = 'crm-change-mode';
		const title = Loc.getMessage('M_CRM_ENTITY_ACTION_CHANGE_CRM_MODE');
		const iconUrl = '/bitrix/mobileapp/crmmobile/extensions/crm/entity-actions/images/gear.png';

		/**
		 * @method onAction
		 */
		const onAction = () => {
			CrmMode.openWizard();
		};

		return { id, title, iconUrl, onAction };
	};

	module.exports = { getActionChangeCrmMode };
});
