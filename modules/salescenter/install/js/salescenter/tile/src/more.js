import {Base} from "./base";

class More extends Base
{
	static type()
	{
		return 'more';
	}
	getType()
	{
		return More.type();
	}
}

export
{
	More
}