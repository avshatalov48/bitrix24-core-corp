/**
 * @module crm/tunnel-list/item/robot
 */
jn.define('crm/tunnel-list/item/robot', (require, exports, module) => {
	const { DelayInterval } = require('crm/tunnel-list/item/delay-interval');
	const { ConditionGroup, ConditionGroupType } = require('crm/tunnel-list/item/condition-group');

	class Robot
	{
		constructor(data = {})
		{
			if (data)
			{
				this.data = data;
			}

			this.name = this.getName(this.data);

			this.delay = new DelayInterval(this.data.delay);
			this.conditionGroup = new ConditionGroup(this.data.conditionGroup);
			if (!this.data.conditionGroup)
			{
				this.conditionGroup.type = ConditionGroupType.Mixed;
			}

			this.responsible = this.data.responsible;
		}

		getName(data)
		{
			return BX.prop.getString(data, 'name', Robot.generateName());
		}

		static generateName()
		{
			return `A${parseInt(Math.random() * 100_000, 10)
			}_${parseInt(Math.random() * 100_000, 10)
			}_${parseInt(Math.random() * 100_000, 10)
			}_${parseInt(Math.random() * 100_000, 10)}`;
		}
	}

	module.exports = { Robot };
});
