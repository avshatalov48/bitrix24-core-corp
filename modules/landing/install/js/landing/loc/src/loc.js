import {Type, Loc as MainLoc} from 'main.core';
import {Env} from 'landing.env';

export class Loc extends MainLoc
{
	static getMessage(key: string): string
	{
		const pageType = Env.getInstance().getType();

		if (pageType)
		{
			const typedMessageKey = `${key}__${pageType}`;

			if (Type.isString(BX.message[typedMessageKey]))
			{
				return MainLoc.getMessage(typedMessageKey);
			}
		}

		return MainLoc.getMessage(key);
	}
}