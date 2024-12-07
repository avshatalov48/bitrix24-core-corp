/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/open-lines-menu-items
 */
jn.define('crm/entity-detail/component/open-lines-menu-items', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');

	const TelegramConnectorManagerOpener = () => {
		try
		{
			const { TelegramConnectorManager } = require('imconnector/connectors/telegram');

			return new TelegramConnectorManager();
		}
		catch (e)
		{
			console.warn(e, 'TelegramConnectorManager not found');

			return null;
		}
	};

	const getOpenLinesMenuItems = (entityTypeId, layout) => {
		const manager = TelegramConnectorManagerOpener();

		return (manager ? [getTelegramItem(manager, layout)] : []);
	};

	const getTelegramItem = (manager, layout) => {
		return {
			id: 'openLinesTelegramItem',
			sectionCode: 'top',
			onItemSelected: () => manager.openEditor(layout),
			title: Loc.getMessage('M_CRM_ACTION_SMART_ACTIVITY_OPEN_LINES_TELEGRAM_MSGVER_2'),
			checked: false,
			icon: Icon.TELEGRAM,
		};
	};

	module.exports = { getOpenLinesMenuItems };
});
