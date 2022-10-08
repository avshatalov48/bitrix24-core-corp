import {Conversion} from "./conversion";
import {Deal} from "./deal";
import {Form} from "./form";
import {Payment} from "./payment";
import {Lead} from "./lead";

import 'ui.fonts.opensans';

export class Registry
{
	static conversion(code : string) : Conversion
	{
		switch (code)
		{
			case 'facebook_conversion_deal':
				return new Deal();
			case 'facebook_conversion_webform':
				return new Form();
			case 'facebook_conversion_payment':
				return new Payment();
			case 'facebook_conversion_lead':
				return new Lead();
		}

	}
}