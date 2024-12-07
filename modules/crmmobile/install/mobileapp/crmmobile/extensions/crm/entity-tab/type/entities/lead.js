/**
 * @module crm/entity-tab/type/entities/lead
 */
jn.define('crm/entity-tab/type/entities/lead', (require, exports, module) => {
	const { Base: BaseEntityType } = require('crm/entity-tab/type/entities/base');
	const { excludeItem } = require('crm/entity-tab/type/traits/exclude-item');
	const { TypeId, TypeName } = require('crm/type');
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');

	/**
	 * @class Lead
	 */
	class Lead extends BaseEntityType
	{
		/**
		 * @returns {Number}
		 */
		getId()
		{
			return TypeId.Lead;
		}

		/**
		 * @returns {String}
		 */
		getName()
		{
			return TypeName.Lead;
		}

		/**
		 * @returns {Object[]}
		 */
		getItemActions(permissions)
		{
			const actions = super.getItemActions(permissions);

			return [
				...actions,
				{
					id: 'exclude',
					title: Loc.getMessage('M_CRM_ENTITY_TAB_ACTION_EXCLUDE'),
					sort: 500,
					onClickCallback: excludeItem.bind(this),
					onDisableClick: this.showForbiddenActionNotification.bind(this),
					icon: Icon.CIRCLE_CROSS,
					isDisabled: !permissions.exclude,
				},
			];
		}

		getMenuActions()
		{
			return [
				{
					type: UI.Menu.Types.HELPDESK,
					data: {
						articleCode: '19787578',
					},
				},
			];
		}
	}

	module.exports = { Lead };
});
