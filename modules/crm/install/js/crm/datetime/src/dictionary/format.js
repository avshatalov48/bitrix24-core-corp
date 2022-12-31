import { Extension, Type } from "main.core";
import { DateTimeFormat } from "main.date";

/**
 * Contains datetime formats for current culture.
 * See config.php of this extension for specific format details.
 * All formats are in BX.Main.DateTimeFormat format (de-facto - php format), even FORMAT_DATE and FORMAT_DATETIME
 *
 * @memberOf BX.Crm.DateTime.Dictionary
 */
const Format: {[key: string]: string} = {};

const formatsRaw = Extension.getSettings('crm.datetime').get('formats', {});
for (const name in formatsRaw)
{
	if (formatsRaw.hasOwnProperty(name) && Type.isStringFilled(formatsRaw[name]))
	{
		let value = formatsRaw[name];
		if (name === 'FORMAT_DATE' || name === 'FORMAT_DATETIME')
		{
			value = DateTimeFormat.convertBitrixFormat(value);
		}

		Format[name] = value;
	}
}

Object.freeze(Format);

export {
	Format,
}
