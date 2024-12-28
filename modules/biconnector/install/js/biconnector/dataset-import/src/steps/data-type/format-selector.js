import { Dom, Event, Loc, Tag } from 'main.core';
import { Layout } from 'ui.sidepanel.layout';
import '../../css/format-selector.css';
import { DataType } from '../../types/data-types';
import type { DataFormatTemplate } from '../../types/data-types';

type DropdownControlOptions = {
	title: string,
	subtitle: string,
	options: Option[],
	fieldType: string,
	selected: string,
};

const OptionType = {
	CUSTOM: 'custom',
	VALUE: 'value',
};

type Option = {
	title: string,
	type: OptionType.CUSTOM | OptionType.VALUE,
	value: string,
};

export class FormatSelector
{
	static openSlider(selected: Object, dataFormatTemplates: DataFormatTemplate, onClose: function)
	{
		BX.SidePanel.Instance.open('biconnector:import-field-formats', {
			width: 584,
			contentCallback: () => {
				return Layout.createContent({
					extensions: ['ui.forms', 'ui.layout-form', 'ui.alerts', 'biconnector.dataset-import'],
					title: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_TITLE'),
					content()
					{
						return FormatSelector.#getContent(selected, dataFormatTemplates);
					},
					buttons({ cancelButton, SaveButton })
					{
						return [
							new SaveButton({
								onclick: () => {
									// hack: using a singleton instance to store the form didn't work
									const form = BX.SidePanel.Instance.getTopSlider().getContainer().querySelector('#formatSelectorForm');
									onClose(FormatSelector.#extractValues(form));
									BX.SidePanel.Instance.close();
								},
							}),
							cancelButton,
						];
					},
				});
			},
		});
	}

	static #extractValues(form): Object
	{
		const formData = new FormData(form);

		const result = Object.fromEntries(formData);

		const customFieldsToExtract = ['date', 'datetime'];

		customFieldsToExtract.forEach((field) => {
			if (result[field] === 'custom')
			{
				result[field] = result[`${field}CustomValue`];
			}

			delete result[`${field}CustomValue`];
		});

		return result;
	}

	static #getContent(selected: Object, dataFormatTemplates: DataFormatTemplate): HTMLElement
	{
		const formRoot = Tag.render`
			<form class="ui-form" id="formatSelectorForm">
			</form>
		`;

		Dom.append(FormatSelector.#getDropdownControl({
			title: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_DATE'),
			subtitle: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_DATE_HINT'),
			options: dataFormatTemplates[DataType.date],
			fieldType: DataType.date,
			selected: selected[DataType.date],
		}), formRoot);

		Dom.append(FormatSelector.#getDropdownControl({
			title: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_DATETIME'),
			subtitle: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_DATETIME_HINT'),
			options: dataFormatTemplates[DataType.datetime],
			fieldType: DataType.datetime,
			selected: selected[DataType.datetime],
		}), formRoot);

		Dom.append(FormatSelector.#getDropdownControl({
			title: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_MONEY'),
			subtitle: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_MONEY_HINT'),
			options: dataFormatTemplates[DataType.money],
			fieldType: DataType.money,
			selected: selected[DataType.money],
		}), formRoot);

		Dom.append(FormatSelector.#getDropdownControl({
			title: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_DOUBLE'),
			subtitle: Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_DOUBLE_HINT'),
			options: dataFormatTemplates[DataType.double],
			fieldType: DataType.double,
			selected: selected[DataType.double],
		}), formRoot);

		return Tag.render`
			<div class="format-selector">
				<div class="ui-alert ui-alert-primary format-selector__hint">
					<span class="ui-alert-message">
						${Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_HINT', {
							'[link]': '<a onclick="top.BX.Helper.show(`redirect=detail&code=23378698`)">',
							'[/link]': '</a>',
						})}
					</span>
				</div>
				${formRoot}
			</div>
		`;
	}

	static #getDropdownControl(options: DropdownControlOptions): HTMLElement
	{
		const selectRoot = Tag.render`
			<select class="ui-ctl-element" name="${options.fieldType}">
			</select>
		`;

		let isCustomSelected = true;
		let customElement = null;
		let customOptionInput = null;

		options.options.forEach((option) => {
			let optionElement = '';

			const isSelected = option.value === options.selected;
			if (isSelected)
			{
				isCustomSelected = false;
			}

			if (option.type === OptionType.VALUE)
			{
				optionElement = Tag.render`
					<option ${option.value === options.selected ? 'selected' : ''} value="${option.value}">${option.title}</option>
				`;
			}
			else if (option.type === OptionType.CUSTOM)
			{
				customElement = Tag.render`
					<option value="custom">${Loc.getMessage('DATASET_IMPORT_FIELD_FORMAT_SELECTOR_CUSTOM_FORMAT')}</option>
				`;
				optionElement = customElement;

				customOptionInput = Tag.render`
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100 format-selector__custom-value-input format-selector__custom-value-input--hidden">
						<input class="ui-ctl-element" name="${options.fieldType}CustomValue" type="text" placeholder="..." value="${option.value ?? ''}">
					</div>
				`;

				Event.bind(selectRoot, 'change', (event) => {
					const value = event.target.value;
					if (value === 'custom')
					{
						Dom.removeClass(customOptionInput, 'format-selector__custom-value-input--hidden');
					}
					else
					{
						Dom.addClass(customOptionInput, 'format-selector__custom-value-input--hidden');
					}
				});
			}

			Dom.append(optionElement, selectRoot);
		});

		if (isCustomSelected)
		{
			if (customElement)
			{
				customElement.setAttribute('selected', true);
			}

			if (customOptionInput)
			{
				customOptionInput.querySelector('input').value = options.selected;
				Dom.removeClass(customOptionInput, 'format-selector__custom-value-input--hidden');
			}
		}

		return Tag.render`
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${options.title}
					</div>
					<div class="format-selector__type-subtitle">
						${options.subtitle}
					</div>
				</div>
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					${selectRoot}
				</div>
				${customOptionInput}
			</div>
		`;
	}
}
