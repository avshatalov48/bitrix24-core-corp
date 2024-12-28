/**
 * @module sign/master/steps/fields-input-step/fields-layout
 */

jn.define('sign/master/steps/fields-input-step/fields-layout', (require, exports, module) => {
	const { DateTimeInput, InputSize, InputMode, InputDesign, DatePickerType } = require('ui-system/form/inputs/datetime');
	const { makeLibraryImagePath } = require('ui-system/blocks/status-block');
	const { CardBanner } = require('ui-system/blocks/banners/card-banner');
	const { StringInput } = require('ui-system/form/inputs/string');
	const { SelectInput } = require('sign/master/steps/fields-input-step/fields-layout/select-input');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { H5 } = require('ui-system/typography/heading');
	const { useCallback } = require('utils/function');
	const { Icon } = require('ui-system/blocks/icon');
	const { Indent, Component, Color } = require('tokens');
	const { trim } = require('utils/string');
	const { date } = require('utils/date/formats');
	const { Moment } = require('utils/date');
	const { Loc } = require('loc');

	/**
	 * @class FieldsLayout
	 */
	class FieldsLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {};
			this.selectFields = [];
			this.fieldRefs = [];
			this.changeButtonStatus = props.changeButtonStatus;
		}

		componentDidMount()
		{
			this.updateFields();
			Keyboard.on(Keyboard.Event.WillShow, () => {
				this.scrollTo();
			});
		}

		#closeKeyboard = () => {
			Keyboard.dismiss();
		};

		#onChange = (value, uid) => {
			this.setState({ [uid]: value });

			const isAllFieldsFilled = this.props.fields.every((field) => {
				if (field.required)
				{
					const fieldValue = trim(this.state[field.uid]);

					return fieldValue !== undefined && fieldValue !== null && fieldValue !== '';
				}

				return true;
			});

			this.changeButtonStatus(isAllFieldsFilled, this.state);
		};

		#onFocus = (uid) => {
			this.position = this.fieldRefs[uid];
			this.scrollTo();
		};

		scrollTo()
		{
			this.scrollViewRef.scrollTo({
				y: 130 + (45 * this.position),
				animated: true,
			});
		}

		formatFields()
		{
			this.fields = this.props.fields.reduce((acc, field) => {
				if (field.name === null || field.name === '' || field.name === undefined)
				{
					return acc;
				}

				this.fieldRefs[field.uid] = acc.counter;

				switch (field.type)
				{
					case 'string':
						return {
							fields: [
								...acc.fields,
								View(
									{
										style: {
											height: 65,
										},
									},
									StringInput({
										onSubmit: this.#closeKeyboard,
										value: this.state[field.uid],
										label: field.name,
										placeholder: Loc.getMessage('SIGN_MOBILE_MASTER_FIELDS_INPUT_STEP_TEXT_INPUT_PLACEHOLDER'),
										enableKeyboardHide: false,
										onChange: useCallback((value) => this.#onChange(value, field.uid), [field.uid]),
										onFocus: useCallback(() => this.#onFocus(field.uid), [field.uid]),
										size: InputSize.L,
										design: InputDesign.GREY,
										mode: InputMode.STROKE,
										style: {
											marginTop: 10,
										},
									}),
								),
							],
							counter: acc.counter + 1,
						};
					case 'list':
						return {
							fields: [
								...acc.fields,
								View(
									{
										style: {
											height: 65,
										},
									},
									SelectInput({
										id: field.uid,
										title: field.name,
										value: this.state[field.uid],
										size: InputSize.L,
										design: InputDesign.GREY,
										mode: InputMode.STROKE,
										onContentClick: useCallback(() => this.#onFocus(field.uid), [field.uid]),
										onChange: useCallback((value) => this.#onChange(value, field.uid), [field.uid]),
										erase: false,
										withFocused: true,
										config: {
											items: field.items.map((item) => {
												return {
													value: item.code,
													name: item.label,
												};
											}),
										},
										style: {
											marginTop: 10,
										},
									}),
								),
							],
							counter: acc.counter + 1,
						};
					case 'date':
						return {
							fields: [
								...acc.fields,
								View(
									{
										style: {
											height: 65,
										},
									},
									DateTimeInput({
										label: field.name,
										defaultListTitle: field.name,
										value: this.state[field.uid],
										size: InputSize.L,
										mode: InputMode.STROKE,
										design: InputDesign.GREY,
										rightStickContent: Icon.CALENDAR_WITH_SLOTS,
										erase: false,
										withFocused: true,
										enableTime: false,
										datePickerType: DatePickerType.DATE,
										dateFormatter: this.getFormattedDate,
										checkTimezoneOffset: false,
										onContentClick: useCallback(() => this.#onFocus(field.uid), [field.uid]),
										onChange: useCallback((value) => this.#onChange(value, field.uid), [field.uid]),
										style: {
											marginTop: 10,
										},
									}),
								),
							],
							counter: acc.counter + 1,
						};
					default:
						return acc;
				}
			}, { fields: [], counter: 1 }).fields;
		}

		updateFields()
		{
			this.props.fields.forEach((field) => {
				this.setState({ [field.uid]: field.value });
			});
		}

		getFormattedDate(sourceDate)
		{
			if (!sourceDate)
			{
				return '';
			}

			const moment = new Moment(sourceDate * 1000);

			return moment.format(date());
		}

		renderText({ text, typography, style })
		{
			return typeof text === 'string'
				? typography({ text, style })
				: View({ style }, text);
		}

		render()
		{
			this.formatFields();

			const documentCard = Card(
				{
					hideCross: true,
					onClick: this.#closeKeyboard,
					design: CardDesign.PRIMARY,
					border: true,
					excludePaddingSide: {
						left: true,
						bottom: true,
					},
				},
				View(
					{
						clickable: true,
						onClick: this.#closeKeyboard,
					},
					this.renderText({
						type: 'title',
						text: Loc.getMessage('SIGN_MOBILE_MASTER_DOCUMENT_CARD_DOCUMENT'),
						typography: H5,
						style: {
							marginBottom: Indent.XS.toNumber(),
							marginLeft: Component.cardPaddingLr.toNumber(),
						},
					}),
					CardBanner(
						{
							onClick: this.#closeKeyboard,
							image: Image({
								svg: {
									uri: makeLibraryImagePath('document.svg', 'empty-states', 'sign'),
								},
								style: {
									width: 28,
									height: 36,
								},
							}),
							title: this.props.templateTitle,
							description:
								Loc.getMessage(
									'SIGN_MOBILE_MASTER_DOCUMENT_CARD_INN',
									{
										'#COMPANY_NAME#': this.props.company.name,
										'#COMPANY_TAX_ID#': this.props.company.taxId,
									},
								),
							hideCross: true,
							design: CardDesign.PRIMARY,
						},
					),
				),
			);

			const fieldsTitle = this.renderText({
				type: 'title',
				text: Loc.getMessage('SIGN_MOBILE_MASTER_DOCUMENT_CARD_DOCUMENT_INFO'),
				typography: H5,
				style: {
					height: 20,
					marginTop: 30,
					marginBottom: Indent.XS.toNumber(),
				},
			});

			return ScrollView(
				{
					style: {
						flex: 1,
						margin: '5%',
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					ref: (ref) => {
						this.scrollViewRef = ref;
					},
				},
				View(
					{
						clickable: true,
						onClick: this.#closeKeyboard,
					},
					documentCard,
					fieldsTitle,
					...this.fields,
				),
			);
		}
	}

	module.exports = { FieldsLayout };
});
