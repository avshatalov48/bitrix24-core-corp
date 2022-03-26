(() => {
	const Types = {
		INTEGER: 'integer',
		DOUBLE: 'double'
	};

	/**
	 * @class Fields.NumberField
	 */
	class NumberField extends Fields.StringInput
	{
		getConfig()
		{
			const config = super.getConfig();
			const type = config.type === Types.INTEGER ? Types.INTEGER : Types.DOUBLE;
			const precision = type === Types.INTEGER ? 0 : BX.prop.getInteger(config, 'precision', 2);

			return {
				...config,
				type,
				precision,
				keyboardType: type === Types.INTEGER ? 'number-pad' : 'decimal-pad'
			};
		}

		changeText(currentText)
		{
			const previousValue = String(this.props.value) || '';
			const hasComma = currentText.includes(',');

			const formattedText = hasComma ? currentText.replace(',', '.') : currentText;

			if (this.isNumber(formattedText) || currentText === '')
			{
				const result = hasComma ? this.formatValue(formattedText, previousValue).replace('.', ',') : this.formatValue(formattedText, previousValue);
				this.handleChange(result);
			}
			else
			{
				this.setState({
					value: previousValue
				});
			}
		}

		isNumber(text)
		{
			const config = this.getConfig();

			if (config.type === Types.INTEGER)
			{
				return Number.isInteger(Number(text));
			}

			if (config.type === Types.DOUBLE)
			{
				return !isNaN(Number(text));
			}

			return false;
		}

		formatValue(text, previousValue)
		{
			const config = this.getConfig();

			if (config.type === Types.DOUBLE)
			{
				const precisionRegex = new RegExp(`^$|^(\\d+(\\.\\d{0,${config.precision}})?|\\.?\\d{1,${config.precision}})$`, 'g');
				if (precisionRegex.test(text))
				{
					if (Number(previousValue.replace(',', '.')) === 0 && !text.includes('.'))
					{
						return text.slice(-1);
					}

					return text;
				}
				else
				{
					const pow = Math.pow(10, config.precision);
					const result = (Math.trunc(Number(text) * pow) / pow).toFixed(config.precision);

					return String(result);
				}
			}

			return text;
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.NumberField = NumberField;
	this.Fields.NumberField.Types = Types;
})();