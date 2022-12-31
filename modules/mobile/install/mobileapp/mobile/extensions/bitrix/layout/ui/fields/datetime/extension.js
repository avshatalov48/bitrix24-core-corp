/**
 * @module layout/ui/fields/datetime
 */
jn.define('layout/ui/fields/datetime', (require, exports, module) => {

	const { dateTime, bigCross } = require('assets/common');
	const { BaseField } = require('layout/ui/fields/base');
	const { longDate, longTime } = require('utils/date/formats');

	/**
	 * @class DateTimeField
	 */
	class DateTimeField extends BaseField
	{
		useHapticOnChange()
		{
			return true;
		}

		getConfig()
		{
			const config = super.getConfig();
			const enableTime = BX.prop.getBoolean(config, 'enableTime', true);
			const defaultDatePickerType = enableTime ? 'datetime' : 'date';
			const defaultDateFormat = enableTime ? `${longDate()} ${longTime()}` : longDate();

			return {
				...config,
				enableTime,
				datePickerType: BX.prop.getString(config, 'datePickerType', defaultDatePickerType),
				dateFormat: BX.prop.getString(config, 'dateFormat', defaultDateFormat),
				defaultListTitle: BX.prop.getString(config, 'defaultListTitle', this.props.title || ''),
				checkTimezoneOffset: BX.prop.getBoolean(config, 'checkTimezoneOffset', false),
				items: BX.prop.getArray(config, 'items', []),
			};
		}

		prepareSingleValue(value)
		{
			if (!value)
			{
				return value;
			}

			return value + this.getTimezoneOffset(value);
		}

		getValueWhileReady()
		{
			let value = this.getValue();
			if (value)
			{
				value -= this.getTimezoneOffset(value);
			}

			return Promise.resolve(value);
		}

		getTimezoneOffset(value)
		{
			const { enableTime, checkTimezoneOffset } = this.getConfig();

			if (!enableTime || !checkTimezoneOffset)
			{
				return 0;
			}

			const nowTimezoneOffset = (new Date()).getTimezoneOffset();
			const valueTimezoneOffset = (new Date(this.getTimeInMilliseconds(value))).getTimezoneOffset();

			return (valueTimezoneOffset - nowTimezoneOffset) * 60;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();
			styles.value.flex = null;
			styles.emptyValue.flex = null;

			if (this.hasHiddenEmptyView())
			{
				return this.getHiddenEmptyChildFieldStyles(styles);
			}

			return styles;
		}

		getHiddenEmptyChildFieldStyles(styles)
		{
			const isEmptyEditable = this.isEmptyEditable();
			const paddingBottomWithoutError = isEmptyEditable ? 18 : 13;

			return {
				...styles,
				wrapper: {
					...styles.wrapper,
					paddingTop: isEmptyEditable ? 12 : 8,
					paddingBottom: this.hasErrorMessage() ? 5 : paddingBottomWithoutError,
				},
				container: {
					...styles.container,
					height: isEmptyEditable ? 0 : null,
				},
			};
		}

		renderEmptyContent()
		{
			return Text({
				style: this.styles.emptyValue,
				text: this.getReadOnlyEmptyValue(),
			});
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return Text({
				style: this.styles.value,
				text: this.getFormattedDate(),
			});
		}

		renderEditableContent()
		{
			if (this.isEmpty())
			{
				if (this.hasHiddenEmptyView())
				{
					return null;
				}

				return Text({
					style: this.styles.emptyValue,
					text: this.getEditableEmptyValue(),
				});
			}

			return Text({
				style: this.styles.value,
				text: this.getFormattedDate(),
			});
		}

		getEditableEmptyValue()
		{
			return this.props.emptyValue || BX.message('FIELDS_DATE_CHOOSE_DATE');
		}

		handleAdditionalFocusActions()
		{
			const config = this.getConfig();

			dialogs.showDatePicker(
				{
					title: config.defaultListTitle,
					type: config.datePickerType,
					value: (this.getTimeInMilliseconds(this.getValue()) || Date.now()),
					items: config.items,
				},
				(eventName, newTs) => {
					this
						.removeFocus()
						.then(() => {
							if (Number.isInteger(newTs))
							{
								const timeInSeconds = this.getTimeInSeconds(newTs);
								const timeWith00Seconds = timeInSeconds - (timeInSeconds % 60);
								this.handleChange(timeWith00Seconds);
							}
						})
					;
				},
			);

			return Promise.resolve();
		}

		getFormattedDate()
		{
			const value = this.getValue();

			return (BX.type.isNumber(value) ? DateFormatter.getDateString(value, this.getConfig().dateFormat) : '');
		}

		getTimeInMilliseconds(value)
		{
			return value * 1000;
		}

		getTimeInSeconds(value)
		{
			return Math.floor(value / 1000);
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', true);
		}

		renderEditIcon()
		{
			if (this.isEmpty())
			{
				if (this.hasHiddenEmptyView())
				{
					return null;
				}

				return View(
					{
						style: {
							width: 24,
							height: 24,
							justifyContent: 'center',
							alignItems: 'center',
							marginLeft: 12,
						},
					},
					Image(
						{
							style: {
								width: 16,
								height: 16,
							},
							svg: {
								content: dateTime(),
							},
						},
					),
				);
			}

			return View(
				{
					style: {
						width: 24,
						height: 24,
						justifyContent: 'center',
						alignItems: 'center',
						marginLeft: 12,
					},
					onClick: () => this.handleChange(null),
				},
				Image(
					{
						style: {
							width: 33,
							height: 33,
						},
						svg: {
							content: bigCross(),
						},
					},
				),
			);
		}

		renderLeftIcons()
		{
			if (this.isEmptyEditable())
			{
				return View(
					{
						style: {
							width: 24,
							height: 24,
							justifyContent: 'center',
							alignItems: 'center',
							marginRight: 8,
						},
					},
					Image(
						{
							style: {
								width: 16,
								height: 16,
							},
							svg: {
								content: dateTime(this.getTitleColor()),
							},
						},
					),
				);
			}

			return null;
		}
	}

	module.exports = {
		DateTimeType: 'datetime',
		DateTimeFieldClass: DateTimeField,
		DateTimeField: (props) => new DateTimeField(props),
	};

});
