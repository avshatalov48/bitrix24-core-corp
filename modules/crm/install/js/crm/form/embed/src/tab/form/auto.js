import {Tag, Dom, Type} from 'main.core';
import {Form} from './form';
import {DataProvider} from "../../data_provider";
import {Wizard} from "../../wizard/wizard";
import {openFeedbackForm} from '../../util';
import {Layout} from 'ui.sidepanel.layout';
import {HELP_CENTER_ID, HELP_CENTER_URL} from "../../tab";

export class Auto extends Form
{
	#container: HTMLElement;
	#wizard: Wizard;
	#rendered: boolean = false;

	constructor(formId: number, dataProvider: DataProvider)
	{
		super(formId, dataProvider);
		this.#container = super.render();
	}

	load(force: boolean = false): Promise
	{
		return super.load(force).then(() => {
			this.#wizard = new Wizard(
				'auto',
				this.formId,
				this.dataProvider.getValues('auto'),
				this.dataProvider.getOptions('auto'),
				this.dataProvider.getDict()
			);
			this.#wizard.subscribe('BX:Crm:Form:Embed:needToSave', (event) => {
				return this.save();
			});
			this.#wizard.subscribe('BX:Crm:Form:Embed:valueChanged', (event) => {
				this.updateDependentFields(this.#container, event.data.name, event.data.value);
			});
			if (!this.#rendered)
			{
				this.render()
			}
		}).catch((error) => {
			console.error(error);
			if (!this.#rendered)
			{
				this.#renderError();
			}
		});
	}

	save(): Promise
	{
		this.data.embed.viewValues['auto'] = this.#wizard.getValues();
		return super.save();
	}

	render(): HTMLElement
	{
		this.#container.innerHTML = '';

		const headerSection = this.renderHeaderSection(
			'clock',
			BX.Loc.getMessage('EMBED_SLIDER_AUTO_TITLE'),
			BX.Loc.getMessage('EMBED_SLIDER_AUTO_DESC'),
			HELP_CENTER_ID,
			HELP_CENTER_URL
		);
		Dom.append(headerSection, this.#container);

		if (!super.loaded)
		{
			this.loader.show(this.#container);
		}
		else
		{
			// update with actual help-center link
			Dom.replace(headerSection, this.renderHeaderSection(
				'clock',
				BX.Loc.getMessage('EMBED_SLIDER_AUTO_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_AUTO_DESC'),
				this.dataProvider.data.embed.helpCenterId,
				this.dataProvider.data.embed.helpCenterUrl
			));

			Dom.append(this.renderPreviewSection(
				BX.Loc.getMessage('EMBED_SLIDER_QR_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_QR_DESC'),
				BX.Loc.getMessage('EMBED_SLIDER_OPEN_IN_NEW_TAB'),
				this.dataProvider.data.embed.previewLink.replace('#preview#', 'auto')
			), this.#container);

			Dom.append(this.renderBaseSettings(), this.#container);

			const code = this.data.embed.scripts['auto'].text;
			Dom.append(this.renderCodeBlock(code), this.#container);
			Dom.append(this.renderCopySection(
				BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE'),
				BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE2'),
				code,
				this.renderBubble(BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE_BUBBLE_AUTO'), true)
			), this.#container);

			this.#rendered = true;
		}

		return this.#container;
	}

	#renderError(): HTMLElement
	{
		this.#container.innerHTML = '';
		Dom.append(super.renderError(super.data), this.#container);
		this.#rendered = true;
	}

	renderBaseSettings(): HTMLElement
	{
		const section = this.renderSection(
			this.renderSettingsHeader(BX.Loc.getMessage('EMBED_SLIDER_SETTINGS_HEADING'), false, () => {
				BX.SidePanel.Instance.open("crm.webform:embed:" + this.formId + ":auto:expert", {
					width: 920,
					cacheable: false,
					contentCallback: () => this.#createAdvancedSettingsLayout(),
					events: {
						onCloseComplete: (event) => {
							this.load(true).then(() => {
								this.render();
							})
						}
					}
				});
			})
		);

		const wrapper = Tag.render`<div class="crm-form-embed__basic"></div>`;

		this.#wizard.renderOptionTo(wrapper, 'delay', true);
		Dom.addClass(wrapper.querySelector('.ui-ctl-dropdown'), 'crm-form-embed__select--time');

		Dom.append(wrapper, section);
		return section;
	}

	#createAdvancedSettingsLayout(): HTMLElement
	{
		const layoutContent = this.#renderAdvancedSettingsLayoutContent()

		this.#wizard.subscribe('BX:Crm:Form:Embed:valueChanged', (event) => {
			this.updateDependentFields(layoutContent, event.data.name, event.data.value);
		});

		return Layout.createContent({
			extensions: ['crm.form.embed', 'ui.sidepanel-content', 'ui.forms', 'landing.ui.field.color', 'ui.switcher'],
			title: BX.Loc.getMessage('EMBED_SLIDER_EXPERT_MODE_TITLE'),
			design: {
				section: false,
			},
			toolbar ({Button}): Array
			{
				return [
					new Button({
						// icon: Button.Icon.SETTING,
						color: Button.Color.LIGHT_BORDER,
						text: BX.Loc.getMessage('EMBED_SLIDER_TOOLBAR_BTN_FEEDBACK'),
						onclick: openFeedbackForm,
					}),
				];
			},
			content: () => layoutContent,
			buttons: ({SaveButton, ApplyButton, cancelButton}) => {
				return [
					new SaveButton({
						onclick: btn => {
							btn.setWaiting(true);
							this.save().then(() => {
								btn.setWaiting(false);
								this.render();
								BX.SidePanel.Instance.close();
							});
						}
					}),
					new ApplyButton({
						onclick: btn => {
							btn.setWaiting(true);
							this.save().then(() => {
								btn.setWaiting(false);
								this.render();
							});
						}
					}),
					cancelButton,
				];
			},
		});
	}

	#renderAdvancedSettingsLayoutContent(): HTMLElement
	{
		const container = this.renderContainer();

		Dom.append(this.renderHeaderSection(
			'clock',
			BX.Loc.getMessage('EMBED_SLIDER_AUTO_TITLE2'),
			BX.Loc.getMessage('EMBED_SLIDER_AUTO_DESC2'),
			this.dataProvider.data.embed.helpCenterId,
			this.dataProvider.data.embed.helpCenterUrl
		), container);

		const stepperInner = Tag.render`<div></div>`;

		Dom.append(this.#wizard.renderControlContainer([
			this.#wizard.renderOption('delay', false),
			this.#wizard.renderOption('type', false),
		]), stepperInner);

		const containerPosition = Tag.render`<div class="crm-form-embed__settings--side-block"></div>`;
		const containerVertical = Tag.render`<div data-option="vertical"></div>`;
		this.#wizard.renderOptionTo(containerPosition, 'position', false);
		this.#wizard.renderOptionTo(containerVertical, 'vertical', false);
		const containerPositionVertical = this.#wizard.renderControlContainer([
			containerPosition,
			containerVertical,
		]);
		Dom.append(containerPositionVertical, stepperInner);

		const stepperContent = [
			{
				html: [
					{
						header: BX.Loc.getMessage('EMBED_SLIDER_AUTO_WIZARD_TITLE'),
						node: stepperInner,
					}
				]
			}
		];

		Dom.append(this.renderStepperSection(stepperContent), container);

		// TODO field dependency
		this.updateDependentFields(container, 'type', this.data.embed.viewValues['auto']['type']);

		return container;
	}
}