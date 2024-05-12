/**
 * @module layout/ui/fields/multiple-combined
 */
jn.define('layout/ui/fields/multiple-combined', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { cross, pen } = require('assets/common');
	const { AddButton } = require('layout/ui/buttons/add-button');
	const { BaseMultipleField } = require('layout/ui/fields/base-multiple');
	const { titleIcons } = require('layout/ui/fields/multiple-combined/title-icons');
	const { last } = require('utils/array');
	const { throttle } = require('utils/function');
	const { stringify } = require('utils/string');

	const THROTTLE_INTERVAL = Application.getPlatform() === 'ios' ? 250 : 500;

	/**
	 * @class MultipleCombinedField
	 */
	class MultipleCombinedField extends BaseMultipleField
	{
		constructor(props)
		{
			props.type = 'combined-v2';

			super(props);
			this.state.showAll = false;

			this.handleAddButtonClick = throttle(() => this.onAddField(), THROTTLE_INTERVAL);
			this.handleDeleteButtonClick = throttle((index) => this.onDeleteField(index), THROTTLE_INTERVAL);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);
			this.state.showAll = !this.isEnableToEdit();
		}

		canFocusTitle()
		{
			return false;
		}

		shouldAnimateOnFocus()
		{
			return this.isEmptyEditable();
		}

		prepareSingleValue(value, prevValue)
		{
			if (!BX.type.isPlainObject(value) || !value.hasOwnProperty('value'))
			{
				value = {
					value: {
						VALUE: '',
						VALUE_TYPE: this.getNextValueType(prevValue),
					},
				};
			}

			return {
				...value,
				id: value.id || this.generateNextIndex(),
				isNew: !value.id || value.id.startsWith('n'),
			};
		}

		isEmptyValue({ value })
		{
			return !value || stringify(value.VALUE) === '';
		}

		renderReadOnlyContent()
		{
			return this.renderEditableContent();
		}

		renderEditableContent()
		{
			this.styles = this.getStyles();

			let fields = this.getValue();
			const shouldHideExcessItems = this.isEditable() && !this.isEnableToEdit() && !this.state.showAll;
			if (shouldHideExcessItems)
			{
				fields = fields.filter((field, index) => !this.isPossibleHiddenField(field, index));
			}

			const hiddenFieldsCount = this.getHiddenFieldsCount();

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
								flex: 1,
							},
						},
						...fields.map((item, index) => this.renderField(item, index)),
					),
				),
				!this.isEnableToEdit() && this.renderShowAllButton(hiddenFieldsCount),
				this.renderAddButton(),
			);
		}

		renderEditIcon()
		{
			if (this.isEditable() && !this.isEnableToEdit() && !this.isEmpty())
			{
				return View(
					{
						style: {
							width: 30,
							height: 30,
							justifyContent: 'center',
							alignItems: 'center',
							alignSelf: 'flex-start',
							marginTop: 9,
						},
						onClick: () => {
							if (this.props.onEdit)
							{
								this.props.onEdit();
							}
						},
					},
					Image({
						style: {
							width: 12,
							height: 12,
						},
						svg: {
							content: pen(),
						},
					}),
				);
			}

			return null;
		}

		getNextValueType(value)
		{
			const { items } = this.getConfig();

			if (!Array.isArray(value) || value.length === 0)
			{
				return items[0].VALUE;
			}

			const filteredItems = items.filter(({ VALUE }) => !value.some(({ value }) => value.VALUE_TYPE === VALUE));
			const nextValue = filteredItems.length > 0 ? filteredItems[0] : last(items);

			return nextValue.VALUE;
		}

		renderAddButton()
		{
			if (this.isReadOnly())
			{
				return null;
			}

			return AddButton({
				text: BX.prop.getString(this.getConfig(), 'addButtonText', ''),
				color: AppTheme.colors.base4,
				onClick: this.handleAddButtonClick,
				deepMergeStyles: this.styles.addButton,
			});
		}

		onAddField()
		{
			let promise = Promise.resolve();

			if (this.props.onEdit)
			{
				promise = promise.then(() => this.props.onEdit());
			}

			return promise.then(() => super.onAddField());
		}

		getInnerFieldTitle()
		{
			return '';
		}

		renderAddOrDeleteFieldButton(index, isNew)
		{
			if (this.isReadOnly() || !this.isEnableToEdit())
			{
				return null;
			}

			return View(
				{
					style: this.styles.addOrDeleteFieldButtonWrapper,
					onClick: () => this.handleDeleteButtonClick(index),
				},
				Image({
					tintColor: AppTheme.colors.base3,
					style: this.styles.buttonContainer,
					resizeMode: 'cover',
					svg: {
						content: cross(),
					},
				}),
			);
		}

		renderLeftIcons()
		{
			const type = this.getConfig().primaryField.type;

			if (titleIcons.hasOwnProperty(type) && this.isEmptyEditable() && !this.state.focus)
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
								width: titleIcons[type].width,
								height: titleIcons[type].height,
							},
							svg: {
								content: titleIcons[type].content(this.getTitleColor()),
							},
						},
					),
				);
			}

			return null;
		}

		getDefaultStyles()
		{
			const styles = this.getChildFieldStyles();

			if (this.hasHiddenEmptyView())
			{
				return this.getHiddenEmptyChildFieldStyles(styles);
			}

			return styles;
		}

		getChildFieldStyles()
		{
			const defaultStyles = super.getDefaultStyles();

			return {
				...defaultStyles,
				multipleFieldContainer: {
					justifyContent: 'flex-start',
					alignItems: 'flex-start',
					flexGrow: 2,
				},
				multipleFieldWrapper: {
					flexDirection: 'row',
					flexWrap: 'no-wrap',
					alignItems: 'center',
					marginBottom: 8,
					paddingTop: 0,
				},
				addOrDeleteFieldButtonWrapper: {
					justifyContent: 'center',
					marginLeft: 8,
					width: 24,
					alignItems: 'flex-end',
				},
				multipleCombinedTitle: {
					fontSize: 10,
					color: AppTheme.colors.base4,
					marginTop: 4,
					paddingBottom: 6,
				},
				buttonContainer: {
					width: 24,
					height: 24,
				},
				addButton: {
					text: {
						marginLeft: 4,
					},
					image: {
						width: 20,
						height: 20,
					},
				},
			};
		}

		getHiddenEmptyChildFieldStyles(styles)
		{
			return {
				...styles,
				title: {
					...styles.title,
					fontSize: this.isEmptyEditable() && !this.state.focus ? 16 : 10,
					marginBottom: 2,
				},
				wrapper: {
					...styles.wrapper,
					paddingTop: this.isEmptyEditable() && !this.state.focus ? 12 : 8,
					paddingBottom: this.isEmptyEditable() && !this.state.focus ? 18 : 12,
				},
			};
		}

		hasCapitalizeTitleInEmpty()
		{
			return !this.state.focus;
		}

		getHiddenFieldsCount()
		{
			return this.getValue().filter((item, index) => this.isPossibleHiddenField(item, index)).length;
		}

		isPossibleHiddenField(field, index)
		{
			return !this.isNewField(field) && index > 3;
		}

		isNewField(item)
		{
			return BX.prop.getBoolean(item, 'isNew', false);
		}
	}

	module.exports = {
		MultipleCombinedType: 'multiple-combined',
		MultipleCombinedField: (props) => new MultipleCombinedField(props),
	};
});
