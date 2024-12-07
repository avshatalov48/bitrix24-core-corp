/**
 * @module layout/ui/fields/datetime
 */
jn.define('layout/ui/fields/datetime', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { dateTime, bigCross } = require('assets/common');
	const { BaseField } = require('layout/ui/fields/base');
	const { longDate, longTime } = require('utils/date/formats');
	const { PropTypes } = require('utils/validation');
	const DatePickerType = {
		DATE: 'date',
		DATETIME: 'datetime',
	};
	const { Icon } = require('assets/icons');

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
			const defaultDatePickerType = enableTime ? DatePickerType.DATETIME : DatePickerType.DATE;
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
				text: this.getEmptyText(),
			});
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					onLongClick: this.getContentLongClickHandler(),
				},
				Text({
					style: this.styles.value,
					text: this.getDisplayedValue(),
				}),
			);
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
					text: this.getEmptyText(),
				});
			}

			return View(
				{
					onLongClick: this.getContentLongClickHandler(),
				},
				Text({
					style: this.styles.value,
					text: this.getDisplayedValue(),
				}),
			);
		}

		/**
		 * @private
		 * @return {string}
		 */
		getDefaultEmptyEditableValue()
		{
			return BX.message('FIELDS_DATE_CHOOSE_DATE');
		}

		/**
		 * @private
		 * @return {Promise}
		 */
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
					this.removeFocus()
						.then(() => {
							if (Number.isInteger(newTs))
							{
								const timeInSeconds = DateTimeField.getTimeInSeconds(newTs);
								const timeWith00Seconds = timeInSeconds - (timeInSeconds % 60);
								this.handleChange(timeWith00Seconds);
							}
						}).catch(console.error);
				},
			);

			return Promise.resolve();
		}

		/**
		 * @public
		 * @return {*|string}
		 */
		getDisplayedValue()
		{
			return this.getDateString(this.getValue());
		}

		getDateString(date)
		{
			const formatter = this.getConfig().dateFormatter;

			if (formatter)
			{
				return String(formatter(date));
			}

			// eslint-disable-next-line no-undef
			return BX.type.isNumber(date) ? DateFormatter.getDateString(date, this.getConfig().dateFormat) : '';
		}

		getTimeInMilliseconds(value)
		{
			return value * 1000;
		}

		static getTimeInSeconds(value)
		{
			return Math.floor(value / 1000);
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', true);
		}

		renderEditIcon()
		{
			if (this.props.editIcon)
			{
				return this.props.editIcon;
			}

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
								width: 24,
								height: 24,
							},
							tintColor: AppTheme.colors.base3,
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
						tintColor: AppTheme.colors.base3,
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
								width: 24,
								height: 24,
							},
							tintColor: AppTheme.colors.base3,
							svg: {
								content: dateTime(),
							},
						},
					),
				);
			}

			return null;
		}

		canCopyValue()
		{
			return true;
		}

		prepareValueToCopy()
		{
			return this.getDisplayedValue();
		}

		getDefaultLeftIcon()
		{
			return Icon.CALENDAR_WITH_SLOTS;
		}

		isEmptyValue(value)
		{
			if (value === 0)
			{
				return true;
			}

			return super.isEmptyValue(value);
		}
	}

	DateTimeField.propTypes = {
		...BaseField.propTypes,
		config: PropTypes.shape({
			// base field props
			showAll: PropTypes.bool, // show more button with count if it's multiple
			styles: PropTypes.shape({
				externalWrapperBorderColor: PropTypes.string,
				externalWrapperBorderColorFocused: PropTypes.string,
				externalWrapperBackgroundColor: PropTypes.string,
				externalWrapperMarginHorizontal: PropTypes.number,
			}),
			deepMergeStyles: PropTypes.object, // override styles
			parentWidget: PropTypes.object, // parent layout widget
			copyingOnLongClick: PropTypes.bool,
			titleIcon: PropTypes.object,

			// datetime field props
			enableTime: PropTypes.bool,
			datePickerType: PropTypes.oneOf(Object.values(DatePickerType)),
			dateFormat: PropTypes.string,
			dateFormatter: PropTypes.func,
			defaultListTitle: PropTypes.string,
			checkTimezoneOffset: PropTypes.bool,
			items: PropTypes.array, // menu items for date picker
		}),
	};

	DateTimeField.defaultProps = {
		...BaseField.defaultProps,
		showEditIcon: true,
		config: {
			...BaseField.defaultProps.config,
			enableTime: true,
			datePickerType: DatePickerType.DATETIME,
			dateFormat: `${longDate()} ${longTime()}`,
			defaultListTitle: '',
			checkTimezoneOffset: false,
			items: [],
		},
	};

	module.exports = {
		DateTimeType: 'datetime',
		DateTimeFieldClass: DateTimeField,
		DateTimeField: (props) => new DateTimeField(props),
		DatePickerType,
	};
});
