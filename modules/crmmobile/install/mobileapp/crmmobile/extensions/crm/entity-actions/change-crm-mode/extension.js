/**
 * @module crm/entity-actions/change-crm-mode
 */
jn.define('crm/entity-actions/change-crm-mode', (require, exports, module) => {
	const { Loc } = require('loc');

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
		const onAction = async () => {
			const { CrmMode } = await requireLazy('crm:crm-mode');

			CrmMode.openWizard();
		};

		return { id, title, iconUrl, onAction };
	};

	module.exports = { getActionChangeCrmMode };
});
