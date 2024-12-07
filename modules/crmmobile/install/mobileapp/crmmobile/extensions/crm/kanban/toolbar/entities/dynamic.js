/**
 * @bxjs_lang_path extension.php
 * @module crm/kanban/toolbar/entities/dynamic
 */
jn.define('crm/kanban/toolbar/entities/dynamic', (require, exports, module) => {
	const { BaseToolbar } = require('crm/kanban/toolbar/entities/base');

	/**
	 * @class DynamicToolbar
	 */
	class DynamicToolbar extends BaseToolbar
	{
		// @todo needs to be refactored in the next iteration
	}

	module.exports = { DynamicToolbar };
});
