import {FacebookConversion} from "./facebookconversion";
import { Loc, Tag, Type, Event, Dom} from "main.core";
import './style.css'

export class Deal extends FacebookConversion
{
	constructor(width : number = 800)
	{
		super(width);
		this.code = 'facebook.deal';
	}

	getContentTitle()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_DEAL_CONTENT_TITLE');
	}

	getSliderTitle()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_DEAL_SLIDER_TITLE');
	}

	getScriptMessage()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_DEAL_SLIDER_ITEM_TITLE');
	}
}