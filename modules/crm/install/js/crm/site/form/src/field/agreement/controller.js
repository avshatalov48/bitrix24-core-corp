import * as BaseField from '../base/controller';
import * as Component from './component';

type OptionsContent = {
	title: string;
	text: ?string;
	url: ?string;
	html: ?string;
};

type Options = {
	id: ?string;
	name: ?string;
	label: ?string;
	required: ?Boolean;
	value: ?string;
	checked: ?Boolean;
	content: string|OptionsContent;
};

class Controller extends BaseField.Controller
{
	consentRequested: Boolean = false;

	static type(): string
	{
		return 'agreement';
	}

	static component()
	{
		return Component.FieldAgreement;
	}

	constructor(options: Options)
	{
		options.type = 'agreement';
		options.visible = true;
		options.multiple = false;
		options.items = null;
		options.values = null;
		super(options);
	}

	isLink()
	{
		return !!this.options.content.url;
	}

	applyConsent()
	{
		this.consentRequested = false;
		this.item().selected = true;
	}

	rejectConsent()
	{
		this.consentRequested = false;
		this.item().selected = false;
	}

	requestConsent()
	{
		this.consentRequested = false;
		if (!this.required || this.valid())
		{
			return true;
		}

		if (!this.isLink())
		{
			this.consentRequested = true;
		}

		return false;
	}
}

export {Controller, Options}