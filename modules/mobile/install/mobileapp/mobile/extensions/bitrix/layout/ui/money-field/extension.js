/**
 * @module layout/ui/money-field
 */
jn.define('layout/ui/money-field', (require, exports, module) => {
	const { useCallback } = require('utils/function');

	const isIOS = Application.getPlatform() === 'ios';
	const API_VERSION = Application.getApiVersion();

	/**
	 * Determines if the platform requires special formatting.
	 * @return {boolean}
	 */
	const requiresSpecialFormatting = () => !isIOS && API_VERSION < 68;

	/**
	 * Formats or parses the value based on the platform and API version.
	 * @param {string} value
	 * @param {string} decimalSeparator
	 * @param {boolean} isFormatting
	 * @return {string}
	 */
	const processValue = (value, decimalSeparator = '.', isFormatting = true) => {
		if (!requiresSpecialFormatting())
		{
			return value;
		}

		return isFormatting ? value.replace('.', decimalSeparator) : value.replace(decimalSeparator, '.');
	};

	/**
	 * @param {string} value
	 * @param {function} onChangeText
	 * @param {number} decimalDigits
	 * @param {string} decimalSeparator
	 * @param {boolean} hideZero
	 * @param {boolean} useGroupSeparator
	 * @param {string} groupSeparator
	 * @param {object} restProps
	 * @return {MoneyField}
	 */
	const MoneyFieldWrapper = ({
		value,
		onChangeText,
		decimalDigits,
		decimalSeparator = '.',
		hideZero,
		useGroupSeparator,
		groupSeparator,
		...restProps
	}) => {
		const handleChangeText = useCallback((value) => {
			onChangeText?.(processValue(value, decimalSeparator, false));
		}, [onChangeText, decimalSeparator]);

		return MoneyField({
			...restProps,
			value: processValue(value, decimalSeparator),
			decimalDigits,
			decimalSeparator,
			hideZero: isIOS ? hideZero : true,
			useGroupSeparator: requiresSpecialFormatting() && useGroupSeparator,
			groupSeparator: requiresSpecialFormatting() && groupSeparator,
			onChangeText: handleChangeText,
		});
	};

	module.exports = {
		MoneyField: MoneyFieldWrapper,
	};
});
