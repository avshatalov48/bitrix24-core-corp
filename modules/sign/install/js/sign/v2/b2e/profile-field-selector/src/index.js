import { Dom, Loc, Tag, Type, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Layout } from 'ui.sidepanel.layout';

import "./style.css";

type Field = {
	name: string,
	caption: string
};

type ProfileFieldSelectorOptions = {
	preselectedFieldName?: string
}

export class ProfileFieldSelector extends EventEmitter
{
	#ui = {
		container: HTMLDivElement = null,
		fieldList: HTMLDivElement = null,
	};

	#fields: Array<Field> = [];
	#inputByFieldCodeMap: Map<string, HTMLInputElement> = new Map();

	#chosenField: ?Field = null;

	#preselectedFieldName: ?string = null;

	constructor(options: ProfileFieldSelectorOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.ProfileFieldSelector');
		if (Type.isStringFilled(options?.preselectedFieldName))
		{
			this.#preselectedFieldName = options.preselectedFieldName;
		}
	}

	#loadAvailableFields(): Promise<any>
	{
		if (this.#fields.length > 0)
		{
			return Promise.resolve(this.#render());
		}

		return new Promise((resolve) => {
			BX.ajax.runAction('sign.api_v1.b2e.fields.getAvailableProfileFields', {
				json: {},
			}).then(response => {
				if (Type.isObject(response.data.fields))
				{
					this.#fields = response.data.fields;
					this.#fillList();
				}
				resolve(this.#render());
			}, response => {
				resolve(this.#render());
			});
		});
	}

	#render(): HTMLDivElement
	{
		if (this.#ui.container)
		{
			return this.#ui.container;
		}

		this.#ui.fieldList = Tag.render`
			<div class="sign-b2e-profile-fields-selector-fields-list"></div>
		`;
		this.#ui.container = Tag.render`
			<div class="sign-b2e-profile-fields-selector">
				${this.#ui.fieldList}
			</div>
		`;

		this.#fillList();

		const preselectedField = this.#fields.find((field) => field.name === this.#preselectedFieldName);
		if (Type.isObject(preselectedField))
		{
			this.#chooseField(preselectedField);
		}

		return this.#ui.container;
	}

	getFieldCaptionByName(fieldName: string): string
	{
		const field = this.#fields.find((field) => field.name === fieldName);
		if (!Type.isObject(field))
		{
			return '';
		}

		return Type.isStringFilled(field?.caption)
			? field.caption
			: ''
		;
	}

	#fillList(): void
	{
		this.#fields.forEach((field) => {
			const radioInput = Tag.render`
				<input type="radio" class="ui-ctl-element">
			`;

			Event.bind(radioInput, 'click', () => {
				this.#chooseField(field);
			})

			const element = Tag.render`
				<div class="sign-b2e-profile-field-selector">
					<label class="ui-ctl ui-ctl-checkbox sign-b2e-profile-field-selector-checkbox">
						${radioInput}
						<div class="ui-ctl-label-text">${field.caption}</div>
					</label>
				</div>
			`;
			this.#inputByFieldCodeMap.set(field.name, radioInput);

			Dom.append(element, this.#ui.fieldList);
		});
	}

	#chooseField(field: Field): void
	{
		// Uncheck previous field
		if (!Type.isNull(this.#chosenField))
		{
			if (this.#inputByFieldCodeMap.has(this.#chosenField.name))
			{
				const input = this.#inputByFieldCodeMap.get(this.#chosenField.name);
				input.checked = false;
			}
		}

		this.#chosenField = field;

		if (this.#inputByFieldCodeMap.has(this.#chosenField.name))
		{
			const input = this.#inputByFieldCodeMap.get(this.#chosenField.name);
			input.checked = true;
		}
	}

	open(): void
	{
		const instance = this;
		BX.SidePanel.Instance.open("sign.b2e:profile-field-selector", {
			width: 700,
			cacheable: false,
			events: {
				onCloseComplete: () => {
					instance.emit('onSliderCloseComplete', { field: instance.#chosenField })
				},
			},
			contentCallback: () => {
				return Layout.createContent({
					extensions: ['ui.forms'],
					title: Loc.getMessage('SIGN_V2_B2E_PROFILE_FIELD_SELECTOR_TITLE'),
					design: {
						section: true,
					},
					content()
					{
						return instance.#loadAvailableFields();
					},
					buttons ({SaveButton, closeButton})
					{
						return [
							new SaveButton({
								text: Loc.getMessage('SIGN_V2_B2E_PROFILE_FIELD_SELECTOR_CHOOSE_BUTTON'),
								onclick: () => {
									BX.SidePanel.Instance.close();
								},
							}),
							closeButton
						];
					},
				});
			},
		});
	}
}