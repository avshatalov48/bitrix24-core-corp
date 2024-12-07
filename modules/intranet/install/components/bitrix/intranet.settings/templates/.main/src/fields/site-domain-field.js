import { Loc, Tag, Text, Event } from 'main.core';
import { BaseSettingsElement } from "ui.form-elements.field";
import { TextInput } from 'ui.form-elements.view';
import { SettingsField } from 'ui.form-elements.field';

export type SiteTitleFieldType = {
	parent: BaseSettingsElement,
	siteDomainOptions: SiteDomainType,
};

export type SiteDomainType = {
	hostname: string,
	subDomainName: string,
	mainDomainName: string,
	isRenameable: boolean,
	occupiedDomains: Object,
	label: ?string,
	exampleDns: Object,
};

export class SiteDomainField extends SettingsField
{
	options: SiteDomainType;

	#content: HTMLElement;
	#title: TextInput;

	constructor(params: SiteTitleFieldType)
	{
		const options = params.siteDomainOptions;
		params.fieldView = new TextInput({
			value: options.subDomainName,
			placeholder: options.subDomainName,
			label: Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME3'),
			id: 'subDomainName',
			inputName: 'subDomainName',
			isEnable: true,
		});

		super(params);
		this.setParentElement(params.parent);

		this.getFieldView().setEventNamespace(
			this.getEventNamespace()
		);
		this.getFieldView().getInputNode().setAttribute('autocomplete', 'off');

		let timeout = null;

		Event.bind(this.getFieldView().getInputNode(), 'input', () => {
			clearTimeout(timeout);
			timeout = setTimeout(() => {
				this.validateInput();
			}, 1000);
		});

		this.options = {
			hostname: options.hostname,
			subDomainName: options.subDomainName,
			mainDomainName: options.mainDomainName,
			isRenameable: options.isRenameable,
			occupiedDomains: options.occupiedDomains,
		};
		this.options.mainDomainName = ['.', this.options.mainDomainName].join('').replace('..', '.');
	}

	validateInput()
	{
		let newDomain = this.getFieldView().getInputNode().value;
		newDomain = newDomain.trim();

		if (newDomain.length < 3 || newDomain.length > 60)
		{
			this.getFieldView().setErrors([Loc.getMessage('INTRANET_SETTINGS_DOMAIN_RENAMING_LENGTH_ERROR')]);
		}
		else if (!/^([a-zA-Z0-9]([a-zA-Z0-9\\-]{0,58})[a-zA-Z0-9])$/.test(newDomain))
		{
			this.getFieldView().setErrors([Loc.getMessage('INTRANET_SETTINGS_DOMAIN_RENAMING_FORMAT_ERROR')]);
		}
		else if (this.options.occupiedDomains.includes(newDomain))
		{
			this.getFieldView().setErrors([Loc.getMessage('INTRANET_SETTINGS_DOMAIN_RENAMING_DOMAIN_EXISTS_ERROR')]);
		}
		else
		{
			this.getFieldView().cleanError();
		}
	}

	cancel(): void
	{
	}

	render(): HTMLElement
	{
		if (this.#content)
		{
			return this.#content;
		}

		if (this.options.isRenameable !== true)
		{
			const copyButton = Tag.render`<div class="settings-tools-description-link">${Loc.getMessage('INTRANET_SETTINGS_COPY')}</div>`;
			BX.clipboard.bindCopyClick(copyButton, {text: () => {
					return this.options.hostname;
				}})
			;
			this.#content = Tag.render`
				<div>
					<div class="ui-section__field-label_box">
						<div class="ui-section__field-label">${Loc.getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME4')}</div>
					</div>
					<div class="intranet-settings__domain_box">
						<div class="intranet-settings__domain_name">${Text.encode(this.options.hostname)}</div>
						${copyButton}
					</div>
				</div>`;
		}
		else
		{
			this.#content = Tag.render`<div id="${this.getFieldView().getId()}" class="ui-section__field-selector --no-border">
				<div class="ui-section__field-container">
					<div class="ui-section__field-label_box">
						<label class="ui-section__field-label" for="${this.getFieldView().getName()}">
							${this.getFieldView().getLabel()}
						</label> 
					</div>
					<div class="ui-section__field-inner">
						<div class="intarnet-settings__domain_inline-field">
							<div class="ui-ctl ui-ctl-textbox ui-ctl-block">
								${this.getFieldView().getInputNode()}
							</div>
							<div class="intarnet-settings__domain_name">${Text.encode(this.options.mainDomainName)}</div>
						</div>
						${this.getFieldView().renderErrors()}
					</div>
				</div>
			</div>`;
		}

		return this.#content;
	}
}
