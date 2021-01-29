import {Base} from "./base";

class Offer extends Base
{
	constructor(props)
	{
		super(props);

		this.width = 735;
	}

	static type()
	{
		return 'offer';
	}

	getType()
	{
		return Offer.type();
	}

}

export
{
	Offer
}