import {FacebookConversion} from "./facebookconversion";
import { Loc, Tag, Type, Event, Dom} from "main.core";
import './style.css'

export class Payment extends FacebookConversion
{
	constructor(width : number = 800)
	{
		super(width);
		this.code = 'facebook.payment';
	}

	getScriptMessage()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_SLIDER_TITLE');
	}

	getSliderTitle()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_SLIDER_TITLE');
	}

	onItemEnable(id,switcher)
	{
		this.onOptionClick(switcher);
	}

	onItemDisable(id,switcher)
	{
		this.onOptionClick(switcher);
	}

	getContentTitle()
	{
		return Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_CONTENT_TITLE');
	}

	onOptionClick(switcher)
	{
		this.data.configuration.enable = !(this.data.configuration.enable == 'true');
		this.saveConfiguration(this.data.configuration)
			.then((response) => {
				if (!response.data.success)
				{
					switcher.check(!this.data.configuration.enabled,false);
					this.notify(Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
				}
			})
			.catch(() => {
					switcher.check(!this.data.configuration.enabled,false);
					this.notify(Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
				}
			)
	}

	saveData(data)
	{
		if (data)
		{
			data.configuration = Type.isObject(data.configuration)? data.configuration : {};
			data.items = [
				{
					id: null,
					name: Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_OPTION'),
					enable: data.configuration.enable == 'true'
				}
			];
		}
		super.saveData(data);
	}
}