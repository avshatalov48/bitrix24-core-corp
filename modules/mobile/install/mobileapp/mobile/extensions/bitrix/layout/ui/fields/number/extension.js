/**
 * @module layout/ui/fields/number
 */
jn.define('layout/ui/fields/number', (require, exports, module) => {
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { stringify } = require('utils/string');

	/** @var NumberPrecision */
	const Types = {
		INTEGER: 'integer',
		DOUBLE: 'double',
		NUMBER: 'number',
	};

	/**
	 * @class NumberField
	 */
	class NumberField extends StringFieldClass
	{
		renderReadOnlyContent()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				MoneyField(this.getFieldInputProps()),
				// for workability of copying by long click
				View({
					style: {
						width: '100%',
						height: '100%',
						position: 'absolute',
					},
					onLongClick: this.getContentLongClickHandler(),
					onClick: this.getContentClickHandler(),
				}),
			);
		}

		renderEditableContent()
		{
			return MoneyField(this.getFieldInputProps());
		}

		getFieldInputProps()
		{
			const fieldProps = super.getFieldInputProps();
			const formatConfig = this.getFormatConfig();

			return {
				...fieldProps,
				...formatConfig,
				enable: !this.isReadOnly(),
				shouldShowToolbar: this.shouldShowToolbar(),
			};
		}

		getConfig()
		{
			const config = super.getConfig();
			const isInteger = this.isIntegerField();

			return {
				...config,
				precision: this.getPrecision(),
				type: isInteger ? Types.INTEGER : Types.DOUBLE,
				keyboardType: isInteger ? 'number-pad' : 'decimal-pad',
			};
		}

		shouldShowToolbar()
		{
			return BX.prop.getBoolean(this.props, 'shouldShowToolbar', true);
		}

		getFormatConfig()
		{
			const config = this.getConfig();

			const formatConfig = {
				decimalDigits: BX.prop.getInteger(config, 'precision', 0),
				decimalSeparator: BX.prop.getString(config, 'decimalSeparator', '.'),
				hideZero: BX.prop.getBoolean(config, 'hideZero', true),
				useGroupSeparator: BX.prop.getBoolean(config, 'useGroupSeparator', false),
			};

			if (formatConfig.useGroupSeparator)
			{
				formatConfig.groupSize = BX.prop.getInteger(config, 'groupSize', 0);
				formatConfig.groupSeparator = BX.prop.getString(config, 'groupSeparator', ' ');
			}

			return formatConfig;
		}

		getPrecision()
		{
			const config = super.getConfig();

			return BX.prop.getInteger(config, 'precision', 0);
		}

		prepareSingleValue(value)
		{
			return super.prepareSingleValue(value);
		}

		isNumber(text)
		{
			return !isNaN(Number(text));
		}

		isInteger(value)
		{
			return Number.isInteger(Number(value));
		}

		isIntegerField()
		{
			const config = super.getConfig();
			const type = config.type || this.props.type;
			const precision = this.getPrecision();

			return type === Types.INTEGER || precision === 0;
		}

		getValidationError()
		{
			let error = super.getValidationError();
			if (!error)
			{
				const value = stringify(this.getValue());

				if (this.isIntegerField() && !this.isInteger(value))
				{
					error = BX.message('FIELDS_ERROR_INTEGER_NUMBER');
				}
				else if (!this.isNumber(value))
				{
					error = BX.message('FIELD_ERROR_NUMBER2');
				}
			}

			return error;
		}

		formatValue(text, previousValue)
		{
			const { precision } = this.getConfig();

			if (precision > 0)
			{
				const precisionRegex = new RegExp(
					`^$|^(\\d+(\\.\\d{0,${precision}})?|\\.?\\d{1,${precision}})$`,
					'g',
				);
				if (precisionRegex.test(text))
				{
					if (previousValue && Number(previousValue.replace(',', '.')) === 0 && !text.includes('.'))
					{
						return text.slice(-1);
					}

					return text;
				}

				const pow = 10 ** precision;
				const result = (Math.trunc(Number(text) * pow) / pow).toFixed(precision);

				return String(result);
			}

			return text;
		}
	}

	module.exports = {
		NumberType: 'number',
		NumberField: (props) => new NumberField(props),
		NumberPrecision: Types,
	};
});
