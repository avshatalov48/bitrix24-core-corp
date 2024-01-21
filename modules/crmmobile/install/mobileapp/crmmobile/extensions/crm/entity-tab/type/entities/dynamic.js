/**
 * @module crm/entity-tab/type/entities/dynamic
 */
jn.define('crm/entity-tab/type/entities/dynamic', (require, exports, module) => {
	const { Base: BaseEntityType } = require('crm/entity-tab/type/entities/base');
	const { Type } = require('crm/type');
	const { Loc } = require('loc');

	/**
	 * @class Dynamic
	 */
	class Dynamic extends BaseEntityType
	{
		constructor(params)
		{
			super(params);

			this.name = null;
			this.id = null;
		}

		getId()
		{
			if (this.id === null)
			{
				throw new Error('Set Id first');
			}

			return this.id;
		}

		getName()
		{
			if (this.name === null)
			{
				throw new Error('Set name first');
			}

			return this.name;
		}

		setName(name)
		{
			if (Type.isDynamicTypeByName(name))
			{
				this.name = name;
				this.id = Type.getDynamicTypeIdByName(name);

				return this;
			}

			throw new Error(`Unknown typeName: ${name}`);
		}

		setId(id)
		{
			if (Type.isDynamicTypeById(id))
			{
				this.id = id;
				this.name = Type.getDynamicTypeNameById(id);

				return this;
			}

			throw new Error(`Unknown typeId: ${id}`);
		}

		getEmptyScreenTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_ENTITY_EMPTY_TITLE2_DYNAMIC');
		}

		getEmptyColumnScreenTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_COLUMN_EMPTY_DYNAMIC_TITLE');
		}

		getEmptyColumnScreenDescription()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_COLUMN_EMPTY_DYNAMIC_DESCRIPTION');
		}

		getColumnUnsuitableForFilterTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_COLUMN_USUITABLE_FOR_FILTER_TITLE_DYNAMIC');
		}

		getColumnUnsuitableForFilterDescription()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_COLUMN_USUITABLE_FOR_FILTER_DESCRIPTION_DYNAMIC');
		}

		getEmptySearchScreenTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_SEARCH_EMPTY_DYNAMIC_TITLE2');
		}

		getManyEntityTypeTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_ENTITY_EMPTY_MANY_DYNAMIC');
		}

		getSingleEntityTypeTitle()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_ENTITY_EMPTY_SINGLE_DYNAMIC');
		}

		getEmptyEntityScreenDescriptionText()
		{
			return Loc.getMessage('M_CRM_ENTITY_TAB_ENTITY_EMPTY_DESCRIPTION_ROBOTS', {
				'#MANY_ENTITY_TYPE_TITLE#': this.getManyEntityTypeTitle(),
			});
		}

		getMenuActions()
		{
			return [
				{
					type: UI.Menu.Types.HELPDESK,
					data: {
						articleCode: '18109574',
					},
				},
			];
		}
	}

	module.exports = { Dynamic };
});
