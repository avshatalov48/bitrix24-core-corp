/**
 * @module crm/entity-tab/type/traits/exclude-item
 */
jn.define('crm/entity-tab/type/traits/exclude-item', (require, exports, module) => {
	const { confirmDestructiveAction } = require('alert');
	const { Loc } = require('loc');

	function excludeItem(action, itemId)
	{
		return new Promise((resolve, reject) => {
			confirmDestructiveAction({
				title: Loc.getMessage('M_CRM_ENTITY_TAB_ACTION_EXCLUDE'),
				description: Loc.getMessage('M_CRM_ENTITY_TAB_ACTION_EXCLUDE_CONFIRMATION'),
				destructionText: Loc.getMessage('M_CRM_ENTITY_TAB_ACTION_EXCLUDE_CONFIRMATION_OK'),
				onDestruct: () => {
					BX.ajax.runComponentAction('bitrix:crm.kanban', 'excludeEntity', {
						mode: 'ajax',
						data: {
							entityType: this.getName(),
							ids: [itemId],
						},
					}).then(() => {
						resolve({
							action: 'delete',
							id: itemId,
						});
					}).catch(({ errors }) => {
						console.error(errors);
						reject();
					});
				},
				onCancel: reject,
			});
		});
	}

	module.exports = { excludeItem };
});
