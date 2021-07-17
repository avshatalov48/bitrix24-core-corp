import Input from './input';
import InputExtended from './input-extended';


export default class InputSimple extends Input
{
	constructor(objectId, data)
	{
		super(objectId, data);
	}

	static getExtendedInputClass()
	{
		return InputExtended;
	}

	static showPopup(objectId, data = null)
	{
		const className = this.getExtendedInputClass();
		const res = new className(objectId, data);

		if (data === null)
		{
			res.reload();
		}
		else // This behaviour is appropriate for current task
		{
			res.showSettings();
		}
		res.show();
	}
}
