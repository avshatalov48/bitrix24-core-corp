/**
 * @module user-profile/common-tab/src/block/common-fields/src/field/date-field
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/field/date-field', (require, exports, module) => {
	const { BaseField } = require('user-profile/common-tab/src/block/common-fields/src/field/base-field');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { Moment } = require('utils/date');
	const { Text4 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { DateTimeInput, InputSize, InputMode, InputDesign, Icon } = require('ui-system/form/inputs/datetime');
	const { Type } = require('type');

	class DateField extends BaseField
	{
		prepareValue(value)
		{
			if (Type.isNil(value))
			{
				return null;
			}

			if (Type.isString(value))
			{
				return new Date(value).getTime() / 1000;
			}

			return value;
		}

		renderViewModeFieldValue(value, idx)
		{
			return new FriendlyDate({
				moment: Type.isNil(value) ? null : Moment.createFromTimestamp(this.prepareValue(value)),
				defaultFormat: this.getViewModeDefaultFormat,
				showTime: false,
				useTimeAgo: false,
				futureAllowed: false,
				renderContent: this.#renderFriendlyDateContent,
			});
		}

		getViewModeDefaultFormat = (moment) => {
			const { format } = this.props;

			// eslint-disable-next-line no-undef
			return dateFormatter.get(moment.timestamp, format);
		};

		renderEditModeFieldValue(value, idx)
		{
			const { id } = this.props;

			return DateTimeInput({
				testId: this.getTestId(`${id.toLowerCase()}-date-input-${idx}`),
				value: this.prepareValue(value),
				enableTime: false,
				size: InputSize.M,
				design: InputDesign.GREY,
				mode: InputMode.NAKED,
				dateFormat: 'd MMMM y',
				rightContent: Icon.CALENDAR,
				onChange: (newValue) => this.onChange(newValue, idx),
				style: {
					width: '100%',
				},
			});
		}

		getFieldContainerStyle()
		{
			const { isEditMode, isFirst } = this.props;

			return {
				marginTop: isFirst ? 0 : Indent.XL2.toNumber(),
				alignItems: 'flex-start',
				borderBottomColor: Color.bgSeparatorSecondary.toHex(),
				borderBottomWidth: isEditMode ? 1 : 0,
			};
		}

		getFieldTitleStyle()
		{
			return {};
		}

		#renderFriendlyDateContent({ state })
		{
			return Text4({
				text: state.text,
				color: Color.base1,
			});
		}
	}

	module.exports = { DateField };
});
