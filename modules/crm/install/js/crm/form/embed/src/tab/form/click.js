import {Tag, Type, Dom, Event} from 'main.core';
import {Form} from './form';
import {DataProvider} from "../../data_provider";
import {Wizard} from "../../wizard/wizard";
import {openFeedbackForm} from '../../util';
import {Layout} from 'ui.sidepanel.layout';
import {HELP_CENTER_ID, HELP_CENTER_URL} from "../../tab";
import { EventEmitter } from 'main.core.events'
import "ui.alerts";

export class Click extends Form
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
				'click',
				this.formId,
				this.dataProvider.getValues('click'),
				this.dataProvider.getOptions('click'),
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
				this.render();
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
		this.dataProvider.updateValues('click', {...this.#wizard.getValues()});
		return super.save();
	}

	render(): HTMLElement
	{
		this.#container.innerHTML = '';

		const headerSection = this.renderHeaderSection(
			'click',
			BX.Loc.getMessage('EMBED_SLIDER_CLICK_TITLE'),
			BX.Loc.getMessage('EMBED_SLIDER_CLICK_DESC'),
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
				'click',
				BX.Loc.getMessage('EMBED_SLIDER_CLICK_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_CLICK_DESC'),
				this.dataProvider.data.embed.helpCenterId,
				this.dataProvider.data.embed.helpCenterUrl
			));

			Dom.append(this.renderPreviewSection(
				BX.Loc.getMessage('EMBED_SLIDER_QR_TITLE'),
				BX.Loc.getMessage('EMBED_SLIDER_QR_DESC'),
				BX.Loc.getMessage('EMBED_SLIDER_OPEN_IN_NEW_TAB'),
				this.dataProvider.data.embed.previewLink.replace('#preview#', 'click')
			), this.#container);

			Dom.append(this.renderFormSettings(), this.#container);
			Dom.append(this.renderButtonSettings(), this.#container);

			const code = this.dataProvider.data.embed.scripts['click'].text;
			Dom.append(this.renderCodeBlock(code), this.#container);
			Dom.append(this.renderCopySection(
				BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE'),
				BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE2'),
				code,
				this.renderBubble(BX.Loc.getMessage('EMBED_SLIDER_COPY_CODE_BUBBLE_CLICK'), true)
			), this.#container);

			this.#rendered = true;
		}

		return this.#container;
	}

	#renderError(): HTMLElement
	{
		this.#container.innerHTML = '';
		Dom.append(super.renderError(super.dataProvider.data), this.#container);
		this.#rendered = true;
	}

	renderFormSettings(): HTMLElement
	{
		const section = this.renderSection(
			this.renderSettingsHeader(BX.Loc.getMessage('EMBED_SLIDER_SETTINGS_HEADING'), false)
		);

		const formSettings = Tag.render`<div></div>`;
		Dom.append(this.#wizard.renderOption('type', true), formSettings);

		const containerPosition = Tag.render`<div class="crm-form-embed__settings--side-block --compact"></div>`;
		this.#wizard.renderOptionTo(containerPosition, 'position', true);

		const containerVertical = Tag.render`<div data-option="vertical"></div>`;
		this.#wizard.renderOptionTo(containerVertical, 'vertical', true);

		const containerPositionVertical = this.#wizard.renderControlContainer([
			containerPosition,
			containerVertical,
		]);
		Dom.append(containerPositionVertical, formSettings);

		this.updateDependentFields(formSettings, 'type', this.dataProvider.getValues('click')['type']);

		Dom.append(formSettings, section);
		return section;
	}

	renderButtonSettings(): HTMLElement
	{
		const enhancedMode =
			!Type.isUndefined(this.dataProvider.getValues('click')['button'])
			&& !Type.isUndefined(this.dataProvider.getValues('click')['button']['use'])
			&& this.dataProvider.getValues('click')['button']['use'] === '1'
		;
		// const linkMode =
		// 	!Type.isUndefined(this.dataProvider.getValues('click')?.button?.plain)
		// 	&& this.dataProvider.getValues('click')['button']['plain'] === '1'
		// ;

		const section = this.renderSection(
			Tag.render`
				<div class="ui-alert ui-alert-primary crm-form-embed-click-button-alert" id ='crm-form-embed-click-button-alert-${this.formId}'>
					<span class="ui-alert-message">${BX.Loc.getMessage('EMBED_SLIDER_CLICK_BUTTON_ALERT')}</span>
					<span class="ui-alert-close-btn" onclick="BX.hide(BX('crm-form-embed-click-button-alert-${this.formId}'));"></span>
				</div>
			`
		);

		Dom.append(
			this.renderSettingsHeader(BX.Loc.getMessage('EMBED_SLIDER_SETTINGS_HEADING_CLICK'), true,() => {
				BX.SidePanel.Instance.open("crm.webform:embed:" + this.formId + ":click:expert", {
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
			}),
			section
		);

		Dom.append(
			this.#wizard.renderOption('button.use', true),
			section.querySelector('.crm-form-embed__customization-settings--switcher')
		);

		if (enhancedMode)
		{
			Dom.addClass(section.querySelector('.crm-form-embed__link.--expert-mode'), '--visible');
			Dom.removeClass(section.querySelector('[data-roll="heading-block"]'), '--collapse');
		}
		else
		{
			Dom.removeClass(section.querySelector('.crm-form-embed__link.--expert-mode'), '--visible');
			Dom.addClass(section.querySelector('[data-roll="heading-block"]'), '--collapse');
		}

		const containerPlainAndText = this.#wizard.renderControlContainer([
			this.#wizard.renderOption('button.plain', true),
			this.#wizard.renderOption('button.text', true)
		]);

		// const optionStyle = (linkMode)
		// 	? this.#wizard.renderOption('button.decoration', true)
		// 	: this.#wizard.renderOption('buttonStyle', true)
		// ;
		const optionLinkStyle = this.#wizard.renderOption('button.decoration', true);
		optionLinkStyle.dataset.option = 'button.decoration';
		const optionButtonStyle = this.#wizard.renderOption('buttonStyle', true);
		optionButtonStyle.dataset.option = 'buttonStyle';

		const rowButtonSettings = this.#wizard.renderRow([
			// this.#wizard.renderTitle(BX.Loc.getMessage('EMBED_SLIDER_SUBTITLE_BUTTON_SETTINGS'), true),
			containerPlainAndText,
			// this.#wizard.renderTitle(BX.Loc.getMessage('EMBED_SLIDER_SUBTITLE_STYLE')),
			// optionStyle,
			optionButtonStyle,
			optionLinkStyle,
		]);

		const colBtnColor = this.#wizard.renderCol();
		const colTxtColor = this.#wizard.renderCol();
		this.#wizard.renderColorPopup(colBtnColor, 'button.color.background', true);
		this.#wizard.renderColorPopup(colTxtColor, 'button.color.text', true);

		const rowDesign = this.#wizard.renderRow(
			[
				// this.#wizard.renderTitle(
				// 	BX.Loc.getMessage('EMBED_SLIDER_SUBTITLE_DESIGN'),
				// 	true
				// ),
				this.#wizard.renderBlock([
					this.#wizard.renderCol([
						this.#wizard.renderLabel(BX.Loc.getMessage('EMBED_SLIDER_OPTION_BUTTON.FONT'), true),
						this.#wizard.renderOption('button.font', true),
					], true),
					colBtnColor,
					colTxtColor,
				]),
			],
			true
		);

		const wrapper = Tag.render`
			<div class="crm-form-embed__basic" data-roll="crm-form-embed__settings" ${enhancedMode ? '' : 'style="height: 0px;"'}></div>
		`;

		Dom.append(rowButtonSettings, wrapper);
		Dom.append(rowDesign, wrapper);
		Dom.append(wrapper, section);

		this.updateDependentFields(section, 'button.plain', this.dataProvider.getValues('click')['button']['plain']);

		return section;
	}

	#createAdvancedSettingsLayout(): Promise
	{
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
			content: () => this.#renderAdvancedSettingsLayoutContent(),
			buttons: ({cancelButton, ApplyButton, SaveButton}) => {
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

	#renderAdvancedSettingsLayoutContent(): Promise
	{
		return new Promise((resolve, reject) => {
			const container = this.renderContainer();

			Dom.append(this.renderHeaderSection(
				'click',
				BX.Loc.getMessage('EMBED_SLIDER_CLICK_TITLE_ADVANCED'),
				BX.Loc.getMessage('EMBED_SLIDER_CLICK_DESC_ADVANCED'),
				this.dataProvider.data.embed.helpCenterId,
				this.dataProvider.data.embed.helpCenterUrl
			), container);

			this.loader.show(container);

			EventEmitter.subscribeOnce('SidePanel.Slider:onOpenComplete', (event) => {
				if (event.target.getUrl() === "crm.webform:embed:" + this.formId + ":click:expert")
				{
					this.loader.hide();

					const containerButtonStyle = this.#wizard.renderControlContainer();
					containerButtonStyle.dataset.controlMode = 'advanced';
					containerButtonStyle.dataset.option = 'buttonStyle';
					this.#wizard.renderOptionTo(containerButtonStyle, 'buttonStyle', false);
					const containerLinkStyle = this.#wizard.renderControlContainer();
					containerLinkStyle.dataset.controlMode = 'advanced';
					containerLinkStyle.dataset.option = 'button.decoration';
					this.#wizard.renderOptionTo(containerLinkStyle, 'button.decoration', false);


					const containerText = this.#wizard.renderControlContainer();
					containerText.dataset.controlMode = 'advanced';
					this.#wizard.renderOptionTo(containerText, 'button.text', false);
					const containerTypeAndText = this.#wizard.renderControlContainer([
						this.#wizard.renderOption('button.plain', false),
						containerText,
					]);

					const step1 = Tag.render`<div></div>`;
					Dom.append(containerTypeAndText, step1);
					Dom.append(containerButtonStyle, step1);
					Dom.append(containerLinkStyle, step1);

					const rowFont = this.#wizard.renderControlContainer();
					rowFont.dataset.controlMode = 'advanced';
					rowFont.dataset.option = 'button.font';
					this.#wizard.renderOptionTo(rowFont, 'button.font', false);
					const stepButtonFont = Tag.render`
						<div>
							${rowFont}
						</div>
					`;

					// TODO white container
					const stepColor = this.renderSection();
					const containerColorBg = this.#wizard.renderControlContainer();
					const containerColorBgHover = this.#wizard.renderControlContainer();
					const containerColorText = this.#wizard.renderControlContainer();
					const containerColorTextHover = this.#wizard.renderControlContainer();
					containerColorBg.dataset.controlMode = 'advanced';
					containerColorBgHover.dataset.controlMode = 'advanced';
					containerColorText.dataset.controlMode = 'advanced';
					containerColorTextHover.dataset.controlMode = 'advanced';
					this.#wizard.renderColorInline(containerColorBg, 'button.color.background', false);
					this.#wizard.renderColorInline(containerColorText, 'button.color.text', false);
					this.#wizard.renderColorInline(containerColorBgHover, 'button.color.backgroundHover', false);
					this.#wizard.renderColorInline(containerColorTextHover, 'button.color.textHover', false);
					const getColorLabel = (messageId: string) => {
						const label = this.#wizard.renderLabel(BX.Loc.getMessage(messageId));
						Dom.addClass(label, 'crm-form-embed__label-text-color');
						return label;
					}
					Dom.append(Tag.render`<div>
						${this.#wizard.renderBlock([
							this.#wizard.renderCol([
								getColorLabel('EMBED_SLIDER_OPTION_BUTTON.COLOR.BACKGROUND'),
								containerColorBg,
							]),
							this.#wizard.renderCol([
								getColorLabel('EMBED_SLIDER_OPTION_BUTTON.COLOR.TEXT'),
								containerColorText,
							]),
							this.#wizard.renderCol([
								getColorLabel('EMBED_SLIDER_OPTION_BUTTON.COLOR.BACKGROUNDHOVER'),
								containerColorBgHover,
							]),
							this.#wizard.renderCol([
								getColorLabel('EMBED_SLIDER_OPTION_BUTTON.COLOR.TEXTHOVER'),
								containerColorTextHover,
							]),
						])}
					</div>`, stepColor);

					const step4 = Tag.render`<div></div>`;
					const containerButtonPosition = Tag.render`<div class="crm-form-embed__settings--side-block" data-option="button.align"></div>`;
					this.#wizard.renderOptionTo(containerButtonPosition, 'button.align', false);
					const containerLinkPosition = Tag.render`<div class="crm-form-embed__settings--side-block" data-option="link.align"></div>`;
					this.#wizard.renderOptionTo(containerLinkPosition, 'link.align', false);
					Dom.append(this.#wizard.renderControlContainer([
						containerButtonPosition,
						containerLinkPosition,
					]), step4);

					const step4Message = (
						!Type.isUndefined(this.dataProvider.getValues('click')?.button?.plain)
						&& this.dataProvider.getValues('click')['button']['plain'] === '1'
					)
						? 'EMBED_SLIDER_CLICK_STEP_4_TITLE_2'
						: 'EMBED_SLIDER_CLICK_STEP_4_TITLE'
					;

					const stepperContent = [
						{
							html: [
								{
									header: {
										title: BX.Loc.getMessage('EMBED_SLIDER_CLICK_STEP_1_TITLE'),
										// hint: 'hint text',
									},
									node: step1,
								}
							]
						},
						{
							html: [
								{
									header: BX.Loc.getMessage('EMBED_SLIDER_CLICK_STEP_2_TITLE'),
									node: stepButtonFont,
								}
							]
						},
						{
							html: [
								{
									header: BX.Loc.getMessage('EMBED_SLIDER_CLICK_STEP_3_TITLE'),
									node: stepColor,
								}
							]
						},
						{
							html: [
								{
									header: BX.Loc.getMessage(step4Message),
									node: step4,
								}
							]
						},
					];

					Dom.append(this.renderStepperSection(stepperContent), container);

					// TODO field dependency
					this.updateDependentFields(container, 'type', this.dataProvider.getValues('click')['type']);
					this.updateDependentFields(container, 'button.plain', this.dataProvider.getValues('click')['button']['plain']);

					this.#wizard.subscribe('BX:Crm:Form:Embed:valueChanged', (event) => {
						this.updateDependentFields(container, event.data.name, event.data.value);
					});
				}
			});

			resolve(container);
		});
	}
}