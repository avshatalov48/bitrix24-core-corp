/**
 * @module crm/tunnel-list/item/delay-interval'
 */
jn.define('crm/tunnel-list/item/delay-interval', (require, exports, module) => {
	const DelayIntervalType = {
		After: 'after',
		Before: 'before',
		In: 'in',
	};

	const DelayIntervalBasis = {
		CurrentDate: '{=System:Date}',
		CurrentDateTime: '{=System:Now}',
		CurrentDateTimeLocal: '{=System:NowLocal}',
	};

	class DelayInterval
	{
		constructor(params)
		{
			this.basis = DelayIntervalBasis.CurrentDateTime;
			this.type = DelayIntervalType.After;
			this.value = 0;
			this.valueType = 'i';
			this.workTime = false;
			this.localTime = false;
			if (BX.type.isPlainObject(params))
			{
				if (params.type)
				{
					this.setType(params.type);
				}

				if (params.value)
				{
					this.setValue(params.value);
				}

				if (params.valueType)
				{
					this.setValueType(params.valueType);
				}

				if (params.basis)
				{
					this.setBasis(params.basis);
				}

				if (params.workTime)
				{
					this.setWorkTime(params.workTime);
				}

				if (params.localTime)
				{
					this.setLocalTime(params.localTime);
				}
			}
		}

		setType(type)
		{
			if (
				type !== DelayIntervalType.After
				&& type !== DelayIntervalType.Before
				&& type !== DelayIntervalType.In
			)
			{
				type = DelayIntervalType.After;
			}
			this.type = type;
		}

		setValue(value)
		{
			value = parseInt(value, 10);
			this.value = value >= 0 ? value : 0;
		}

		setValueType(valueType)
		{
			if (valueType !== 'i' && valueType !== 'h' && valueType !== 'd')
			{
				valueType = 'i';
			}

			this.valueType = valueType;
		}

		setBasis(basis)
		{
			if (BX.type.isNotEmptyString(basis))
			{
				this.basis = basis;
			}
		}

		setWorkTime(flag)
		{
			this.workTime = !!flag;
		}

		setLocalTime(flag)
		{
			this.localTime = !!flag;
		}
	}

	module.exports = { DelayInterval };
});
