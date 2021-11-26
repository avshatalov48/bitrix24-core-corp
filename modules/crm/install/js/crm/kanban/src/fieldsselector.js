import {UI} from 'ui.notification';
import {PopupManager} from "main.popup";
import {Dom, Loc, Tag, Text, Type} from "main.core";

type FieldsSelectorOptions = {
	type: string,
	entityTypeName: string,
	sections: Array,
	selectedFields: Array,
	ignoredFields: Object,
	onSelect?: Function
}

const TYPE_VIEW = 'view';
const TYPE_EDIT = 'edit';

export default class FieldsSelector
{
	constructor(options: FieldsSelectorOptions)
	{
		this.popup = null;
		this.fields = null;
		this.options = options;
		this.type =
			this.options.hasOwnProperty('type')
				? this.options.type
				: TYPE_VIEW
		;

		this.selectedFields =
			this.options.hasOwnProperty('selectedFields')
				? this.options.selectedFields.slice(0)
				: []
		;
	}

	show()
	{
		if (!this.popup)
		{
			this.popup = this.createPopup();
		}
		if (this.fields)
		{
			this.popup.setContent(this.getFieldsLayout());
		}
		else
		{
			this.loadPopupContent(this.popup);
		}
		this.popup.show();
	}

	createPopup()
	{
		return PopupManager.create({
			id: 'kanban_custom_fields_' + this.type,
			className: 'crm-kanban-popup-field',
			titleBar: BX.message('CRM_KANBAN_CUSTOM_FIELDS_' + this.type.toUpperCase()),
			cacheable: false,
			closeIcon: true,
			lightShadow: true,
			overlay: true,
			draggable: true,
			closeByEsc: true,
			contentColor: 'white',
			maxHeight: window.innerHeight - 50,
			events: {
				onClose: () => (this.popup = null)
			},
			buttons: [
				new BX.UI.SaveButton({
					color: BX.UI.Button.Color.PRIMARY,
					state: this.fields ? '' : BX.UI.Button.State.DISABLED,
					onclick: () =>
					{
						const selectedFields =
							this.fields
								? this.fields.filter(field => this.selectedFields.indexOf(field.NAME) >= 0)
								: []
						;
						if (selectedFields.length)
						{
							this.popup.close();
							this.executeCallback(selectedFields);
						}
						else
						{
							UI.Notification.Center.notify({
								content: Loc.getMessage('CRM_KANBAN_POPUP_AT_LEAST_ONE_FIELD'),
								autoHide: true,
								autoHideDelay: 2000,
							});
						}
					}
				}),
				new BX.UI.CancelButton({
					onclick: () =>
					{
						this.popup.close();
					}
				}),
			]
		});
	}

	loadPopupContent(popup: Popup)
	{
		const loaderContainer = Tag.render`<div class="crm-kanban-popup-field-loader"></div>`;

		const loader = new BX.Loader({
			target: loaderContainer,
			size: 80
		});
		loader.show();

		popup.setContent(loaderContainer);

		BX.ajax.runComponentAction(
			'bitrix:crm.kanban',
			'getFields',
			{
				mode: 'ajax',
				data: {
					entityType: this.options.entityTypeName,
					viewType: this.type,
				}
			}
		)
			.then(
				(response) =>
				{
					loader.destroy();

					this.fields = response.data;
					popup.setContent(this.getFieldsLayout());
					popup.getButtons().forEach(button => button.setDisabled(false));
					popup.adjustPosition();
				}
			)
			.catch(
				(response) =>
				{
					BX.Kanban.Utils.showErrorDialog(response.errors.pop().message);
				}
			);

		return popup;
	}

	getFieldsLayout()
	{
		const sectionsWithFields = this.distributeFieldsBySections(this.fields);

		const container = Tag.render`<div class="crm-kanban-popup-field"></div>`;

		this.getSections().forEach((section) =>
		{
			const sectionName = section.name;
			if (sectionsWithFields.hasOwnProperty(sectionName) && sectionsWithFields[sectionName].length)
			{
				Dom.append(
					Tag.render`<div class="crm-kanban-popup-field-title">${Text.encode(section.title)}</div>`,
					container
				);
				Dom.append(
					Tag.render`<div class="crm-kanban-popup-field-wrapper">
						${sectionsWithFields[sectionName].map(
						(field) =>
						{
							let label = field.LABEL;
							if (
								!label.length
								&& section['elements']
								&& section['elements'][field.NAME]
								&& section['elements'][field.NAME]['title']
								&& section['elements'][field.NAME]['title'].length
							)
							{
								label = section['elements'][field.NAME]['title'];
							}
							const encodedLabel = Text.encode(label);

							return Tag.render`
								<div class="crm-kanban-popup-field-item" title="${encodedLabel}">
									<input 
										id="cf_${Text.encode(field.ID)}" 
										type="checkbox" 
										name="${Text.encode(field.NAME)}"
										class="crm-kanban-popup-field-item-input"
										data-label="${encodedLabel}"
										${this.selectedFields.indexOf(field.NAME) >= 0 ? 'checked' : ''}
										onclick="${this.onFieldClick.bind(this)}"
									/>
									<label for="cf_${Text.encode(field.ID)}" class="crm-kanban-popup-field-item-label">
										${encodedLabel}
									</label>
								</div>`;
						}
					)}
					</div>`,
					container
				);
			}
		});

		return container;
	}

	distributeFieldsBySections(fields: Array)
	{
		// remove ignored fields from result:
		const ignoredFields = this.getIgnoredFields();
		fields = fields.filter(item => !(ignoredFields.hasOwnProperty(item.NAME) && ignoredFields[item.NAME]));

		let fieldsBySections = {};
		let defaultSectionName = '';
		const sections = this.options.hasOwnProperty('sections') ? this.options.sections : [];
		for (let i = 0; i < sections.length; i++)
		{
			const section = sections[i];
			const sectionName = section.name;
			fieldsBySections[sectionName] = [];
			if (Type.isPlainObject(section.elements))
			{
				fieldsBySections[sectionName] = this.filterFieldsByList(fields, section.elements);
			}
			else if (section.hasOwnProperty('elementsRule'))
			{
				fieldsBySections[sectionName] = this.filterFieldsByRule(fields, new RegExp(section.elementsRule));
			}
			else if (section.elements === '*')
			{
				defaultSectionName = sectionName;
			}
		}
		if (defaultSectionName !== '')
		{
			fieldsBySections[defaultSectionName] = this.filterNotUsedFields(fields, fieldsBySections);
		}

		return fieldsBySections;
	}

	filterFieldsByList(fields: Array, whiteList: Object): Array
	{
		return fields.filter((item) => whiteList.hasOwnProperty(item.NAME));
	}

	filterFieldsByRule(fields: Array, rule: RegExp): Array
	{
		return fields.filter((item) => item.NAME.match(rule));
	}

	filterNotUsedFields(fields: Array, alreadyUsedFieldsBySection: Object): Array
	{
		let alreadyUsedFieldsNames = Object.values(alreadyUsedFieldsBySection).reduce(
			(prevFields, sectionFields) =>
			{
				return prevFields.concat(sectionFields.map((item) => item.NAME))
			},
			[]
		);

		return fields.filter(item => alreadyUsedFieldsNames.indexOf(item.NAME) < 0);
	}

	getSections(): Array
	{
		return this.options.hasOwnProperty('sections') ? this.options.sections : [];
	}

	getIgnoredFields()
	{
		let fields = Object.assign({}, this.options.ignoredFields);
		let extraFields = [];
		if (this.type === TYPE_EDIT)
		{
			extraFields = [
				'ID',
				'CLOSED',
				'CLOSEDATE',
				'DATE_CREATE',
				'DATE_MODIFY',
				'COMMENTS',
				'OPPORTUNITY',
			]
		}
		else
		{
			extraFields = [
				'PHONE',
				'EMAIL',
				'WEB',
				'IM',
			]
		}
		extraFields.forEach((fieldName => (fields[fieldName] = true)));

		return fields;
	}

	executeCallback(selectedFields: Array)
	{
		if (!this.options.hasOwnProperty('onSelect') || !Type.isFunction(this.options.onSelect))
		{
			return;
		}
		let callbackPayload = {};
		selectedFields.forEach(
			(field) =>
			{
				callbackPayload[field.NAME] =
					field.LABEL
						? field.LABEL
						: ''
				;
			}
		);
		this.options.onSelect(callbackPayload);
	}

	onFieldClick(event)
	{
		const fieldName = event.target.name;
		if (event.target.checked && this.selectedFields.indexOf(fieldName) < 0)
		{
			this.selectedFields.push(fieldName);
		}
		if (!event.target.checked && this.selectedFields.indexOf(fieldName) >= 0)
		{
			this.selectedFields.splice(this.selectedFields.indexOf(fieldName), 1);
		}
	}
}
