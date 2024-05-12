import { Event, Loc, Tag } from 'main.core';
import { BaseSettingsElement } from "ui.form-elements.field";
import { HelpMessage } from 'ui.section';
import { Checker, TextInput } from 'ui.form-elements.view';
import { EventEmitter, BaseEvent } from "main.core.events";
import { SiteTitle24Field } from './site-title-24-field';

export type SiteTitleFieldType = {
	parent: BaseSettingsElement,
	siteTitleOptions: SiteTitleInputType,
	siteTitleLabels: SiteTitleLabelType,
};
export type SiteTitleInputType = {
	name: string,
	canUserEditName: boolean,
	title: string,
	canUserEditTitle: boolean,
	logo24: string,
	canUserEditLogo24: boolean,
	logo: ?Array
};
export type SiteTitleLabelType = {
	title: ?string,
	logo24: ?string,
}

export class SiteTitleField extends BaseSettingsElement
{
	options: SiteTitleInputType;
	labels: SiteTitleLabelType;

	#content: HTMLElement;
	#contentLogo24: HTMLElement;
	#title: TextInput;
	#logo24: SiteTitle24Field;

	#inputMonitoringIntervalId: ?number;
	#inputMonitoringCountdown: number = 10;
	#inputMonitoringPrevState: ?string;

	constructor(params: SiteTitleFieldType)
	{
		super(params);
		this.setParentElement(params.parent);
		this.setEventNamespace('BX.Intranet.Settings');

		const options = params.siteTitleOptions;
		this.options = {
			title: options.title,
			canUserEditTitle: options.canUserEditTitle,
			logo24: options.logo24,
			canUserEditLogo24: options.canUserEditLogo24,
		};
		const labels = params.siteTitleLabels;
		this.labels = {
			title: labels.title,
			logo24: labels.logo24,
		}

		this.#initTitle(options, labels);
		this.#initLogo24(options, labels);
	}

	#initTitle(options: SiteTitleInputType, labels: SiteTitleLabelType)
	{
		this.#title = new TextInput({
			value: options.title,
			placeholder: options.title,
			label: labels.title ?? Loc.getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE_INPUT_LABEL'),
			id: 'siteTitle',
			inputName: 'title',
			isEnable: true,
			// bannerCode: '123',
			// helpDeskCode: '234',
			// helpMessageProvider: () => {}
		});

		this.#title.setEventNamespace(
			this.getEventNamespace()
		);
	}

	#initLogo24(options: SiteTitleInputType, labels: SiteTitleLabelType)
	{
		this.#logo24 = new SiteTitle24Field({
			title: labels.logo24,
			isEnable: options.canUserEditLogo24,
			checked: options.logo24,
		});
	}

	getFieldView()
	{
		return this.#title;
	}

	cancel(): void
	{
	}

	startInputMonitoring(): void
	{
		if (this.#inputMonitoringIntervalId > 0)
		{
			return;
		}
		this.#inputMonitoringIntervalId = setInterval(this.monitorInput.bind(this), 500);
	}

	stopInputMonitoring()
	{
		if (this.#inputMonitoringIntervalId > 0)
		{
			clearInterval(this.#inputMonitoringIntervalId);
			this.#inputMonitoringIntervalId = null;
		}
	}

	monitorInput()
	{
		const value = this.#title.getInputNode().value;
		if (this.#inputMonitoringPrevState !== value)
		{
			this.#inputMonitoringCountdown = 10;
			this.#inputMonitoringPrevState = value;
			EventEmitter.emit(
				EventEmitter.GLOBAL_TARGET,
				this.getEventNamespace() + ':Portal:Change',
				new BaseEvent({data: { title:  value } } )
			);
		}
		else if (--this.#inputMonitoringCountdown <= 0)
		{
			this.stopInputMonitoring();
		}
	}

	render(): HTMLElement
	{
		if (this.#content)
		{
			return this.#content;
		}

		Event.bind(this.#title.getInputNode(), 'focus', this.startInputMonitoring.bind(this));
		Event.bind(this.#title.getInputNode(), 'keydown', this.startInputMonitoring.bind(this));
		Event.bind(this.#title.getInputNode(), 'click', this.startInputMonitoring.bind(this));
		Event.bind(this.#title.getInputNode(), 'blur', this.stopInputMonitoring.bind(this));
		Event.bind(this.#title.getInputNode(), 'blur', this.stopInputMonitoring.bind(this));

		this.#logo24.getFieldView().subscribe('change', (event: BaseEvent) => {
			EventEmitter.emit(
				EventEmitter.GLOBAL_TARGET,
				this.getEventNamespace() + ':Portal:Change',
				new BaseEvent({ data: { logo24:  event.getData() === true ? '24' : '' } })
			);
		});

		this.#content = Tag.render`
		<div id="${this.#title.getId()}" class="ui-section__field-selector --no-border --no-margin --align-center">
			<div class="ui-section__field-container">
				<div class="ui-section__field-label_box">
					<label class="ui-section__field-label" for="${this.#title.getName()}">
						${this.#title.getLabel()}
					</label> 
				</div>
				<div class="ui-section__field-inner">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-block">
						${this.#title.getInputNode()}
					</div>
				</div>
			</div>
		</div>
		`;

		return this.#content;
	}

	getLogo24Field()
	{
		return this.#logo24;
	}
}
