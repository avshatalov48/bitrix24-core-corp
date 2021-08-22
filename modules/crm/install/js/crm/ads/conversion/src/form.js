import { FacebookConversion } from "./facebookconversion";
import { Loc, Tag, Type, Event, Dom } from "main.core";
import './style.css'

export class Form extends FacebookConversion
{
	constructor(width: number = 800)
	{
		super(width);
		this.code = 'facebook.webform';

	}

	getSliderTitle()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_FORM_SLIDER_TITLE');
	}

	getScriptMessage()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_FORM_SLIDER_ITEM_TITLE');
	}

	getContentTitle()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_FORM_CONTENT_TITLE');
	}

}