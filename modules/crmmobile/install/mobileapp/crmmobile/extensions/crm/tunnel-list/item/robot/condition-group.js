/**
 * @module crm/tunnel-list/item/condition-group
 */
jn.define('crm/tunnel-list/item/condition-group', (require, exports, module) => {
	const ConditionGroupType = {
		Field: 'field',
		Mixed: 'mixed',
	};

	const ConditionGroupJoiner = {
		And: 'AND',
		Or: 'OR',
	};

	class ConditionGroup
	{
		constructor(params)
		{
			this.type = ConditionGroupType.Field;
			const items = [];

			if (BX.type.isPlainObject(params))
			{
				if (params.type)
				{
					this.type = params.type;
				}

				if (BX.type.isArray(params.items))
				{
					params.items.forEach((item) => {
						const properties = item.properties || {};
						const condition = new Condition(properties, this);
						items.push([condition, item.operator]);
					});
				}
			}
			this.items = [...items];
		}
	}

	class Condition
	{
		constructor(params, group)
		{
			this.object = 'Document';
			this.field = '';
			this.operator = '!empty';
			this.value = '';

			this.parentGroup = null;

			if (BX.type.isPlainObject(params))
			{
				if (params.object)
				{
					this.setObject(params.object);
				}

				if (params.field)
				{
					this.setField(params.field);
				}

				if (params.operator)
				{
					this.setOperator(params.operator);
				}

				if ('value' in params)
				{
					this.setValue(params.value);
				}
			}

			if (group)
			{
				this.parentGroup = group;
			}
		}

		setObject(object)
		{
			if (BX.type.isNotEmptyString(object))
			{
				this.object = object;
			}
		}

		setField(field)
		{
			if (BX.type.isNotEmptyString(field))
			{
				this.field = field;
			}
		}

		setOperator(operator)
		{
			if (!operator)
			{
				operator = '=';
			}
			this.operator = operator;
		}

		setValue(value)
		{
			this.value = value;
			if (this.operator === '=' && this.value === '')
			{
				this.operator = 'empty';
			}
			else if (this.operator === '!=' && this.value === '')
			{
				this.operator = '!empty';
			}
		}
	}

	module.exports = { ConditionGroup, ConditionGroupType, ConditionGroupJoiner };
});
