/**
 * @module crm/entity-tab/type/entities/deal
 */
jn.define('crm/entity-tab/type/entities/deal', (require, exports, module) => {
	const { Base: BaseEntityType } = require('crm/entity-tab/type/entities/base');
	const { excludeItem } = require('crm/entity-tab/type/traits/exclude-item');
	const { TypeId, TypeName } = require('crm/type');
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');

	/**
	 * @class Deal
	 */
	class Deal extends BaseEntityType
	{
		/**
		 * @returns {Number}
		 */
		getId()
		{
			return TypeId.Deal;
		}

		/**
		 * @returns {String}
		 */
		getName()
		{
			return TypeName.Deal;
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

		getEmptySearchScreenConfig()
		{
			const config = super.getEmptySearchScreenConfig();
			if (this.params.categoriesCount > 1)
			{
				const entityTypeName = this.getName();
				config.description = Loc.getMessage(`M_CRM_ENTITY_TAB_SEARCH_WITH_TWO_OR_MORE_CATEGORIES_EMPTY_${entityTypeName}_DESCRIPTION2`);
			}

			return config;
		}

		getMenuActions()
		{
			return [
				{
					type: UI.Menu.Types.HELPDESK,
					data: {
						articleCode: '16758628',
					},
				},
			];
		}
	}

	module.exports = { Deal };
});
