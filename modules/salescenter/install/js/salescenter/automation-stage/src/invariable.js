import {Base} from "./base";

class Invariable extends Base
{
	constructor(props)
	{
		super(props);

		this.id = '';
		this.color = '#2fc6f6';
		this.colorText = 'dark';
	}

	static type()
	{
		return 'invariable';
	}
	getType()
	{
		return Invariable.type();
	}
}

export
{
	Invariable
}