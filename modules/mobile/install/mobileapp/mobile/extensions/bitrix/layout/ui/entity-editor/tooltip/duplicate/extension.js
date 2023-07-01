/**
 * @module layout/ui/entity-editor/tooltip/duplicate
 */
jn.define('layout/ui/entity-editor/tooltip/duplicate', (require, exports, module) => {

	const { get, has, isEmpty } = require('utils/object');
	const { EventEmitter } = require('event-emitter');

	let Duplicates;

	try
	{
		Duplicates = require('crm/duplicates').Duplicates;
	}
	catch (e)
	{
		console.warn(e);

		return;
	}

	/**
	 * DuplicateTooltip
	 */
	class DuplicateTooltip
	{
		constructor(props)
		{
			this.props = props;
			this.fieldRef = null;
			this.fieldValueMap = {
				title: ['TITLE'],
				companyTitle: ['COMPANY_TITLE'],
				fullName: ['NAME', 'LAST_NAME', 'SECOND_NAME'],
			};
			this.customEventEmitter = EventEmitter.createWithUid(this.props.uid);
			this.customEventEmitter.on('DetailCard::onUpdate', this.clearTooltip.bind(this));
		}

		static isEnabledDuplicateControl(schemeData, entityTypeName)
		{
			return has(schemeData, ['duplicateControl', 'groupId'])
				&& Duplicates.checkEnableForEntityType(entityTypeName);
		}

		static create(props)
		{
			const duplicate = new DuplicateTooltip(props);

			return (fieldRef) => duplicate.initialize(fieldRef);
		}

		clearTooltip()
		{
			if (this.fieldRef)
			{
				this.fieldRef.clearTooltip();
			}
		}

		getSchemeElement()
		{
			return get(this.props, ['settings', 'schemeElement'], null);
		}

		getEditor()
		{
			return get(this.props, ['settings', 'editor'], null);
		}

		getColor()
		{
			return '#E89B06';
		}

		getValue(fieldValue)
		{
			const value = typeof fieldValue === 'object' ? fieldValue.VALUE : fieldValue;

			return typeof value === 'string' ? value.trim() : value;
		}

		getValues()
		{
			const editor = this.getEditor();
			const duplicateControl = this.getDuplicateControl();
			const fieldsNames = this.fieldValueMap[duplicateControl.groupId];
			const fieldValue = this.getValue(this.fieldRef.getValue());

			if (isEmpty(fieldValue))
			{
				return Promise.resolve({});
			}

			if (fieldsNames && editor)
			{
				return editor.getValuesToSave().then((values) => {
					const result = {};
					fieldsNames.forEach((fieldName) => {
						const value = this.getValue(values[fieldName]);
						if (value)
						{
							result[fieldName] = value;
						}
					});

					return result;
				});
			}

			const schemeElement = this.getSchemeElement();

			return Promise.resolve({
				[schemeElement.name]: [fieldValue],
			});
		}

		getDuplicateControl()
		{
			const schemeElement = this.getSchemeElement();
			const data = schemeElement.getData();

			return data.duplicateControl;
		}

		isAllowed()
		{
			return get(this.getEditor(), ['payload', 'isCreationFromSelector']);
		}

		initialize(fieldRef)
		{
			const { fieldType, entityTypeName, entityId } = this.props;
			this.fieldRef = fieldRef;

			return new Promise((resolve) => {
					this.getValues()
						.then((values) =>
							Duplicates.find({
								entityId,
								entityTypeName,
								values,
								duplicateControl: this.getDuplicateControl(),
							}),
						)
						.then((duplicates) => {
							const { TOTAL_DUPLICATES } = duplicates;
							const color = this.getColor();

							if (TOTAL_DUPLICATES > 0)
							{
								resolve({
									message: Duplicates.getTooltipContent({
										duplicates,
										fieldType,
										entityTypeName,
										color,
										parentUid: fieldRef.uid,
										isAllowed: this.isAllowed(),
										style: fieldRef.styles.errorText,
									}),
									color,
								});
							}
							else
							{
								resolve({ message: null });
							}
						});
				},
			);
		}
	}

	module.exports = { DuplicateTooltip };
});