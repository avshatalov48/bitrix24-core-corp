import {Base} from './base'

class Watch extends Base
{
	static type()
	{
		return 'watch';
	}
	getType()
	{
		return Watch.type();
	}
	getIcon()
	{
		return 'watch';
	}
}

export
{
	Watch
}