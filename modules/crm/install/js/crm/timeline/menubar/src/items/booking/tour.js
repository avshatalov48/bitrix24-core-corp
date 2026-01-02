import { BaseTour } from '../tools/base-tour';
import Options from './options';

export default class Tour extends BaseTour
{
	/**
	 * @override
	 * */
	saveUserOption(optionName: ?string = null): void
	{
		new Options().saveUserOption(optionName);
	}
}
