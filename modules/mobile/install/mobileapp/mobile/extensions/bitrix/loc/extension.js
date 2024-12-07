/**
 * @module loc
 */
jn.define('loc', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class Loc
	 */
	class Loc
	{
		/**
		 * Gets message by id
		 * @param {string} messageId
		 * @param {object} replacements
		 * @return {?string}
		 */
		static getMessage(messageId, replacements = null)
		{
			let mess = BX.message(messageId);

			if (Type.isString(mess) && Type.isPlainObject(replacements))
			{
				Object.keys(replacements).forEach((replacement) => {
					const globalRegexp = new RegExp(replacement, 'gi');

					mess = mess.replace(
						globalRegexp,
						() => (Type.isNil(replacements[replacement]) ? '' : String(replacements[replacement])),
					);
				});
			}

			return mess;
		}

		/**
		 * Returns last version of message if exists.
		 * @param {string} messageId
		 * @param {object} [replacements]
		 * @param {number} [versionLimit=1]
		 * @return {?string|null}
		 */
		static getLastMessageVer(messageId, replacements = null, versionLimit = 1)
		{
			const messageWithVerText = `${messageId}_MSGVER_`;

			for (let ver = versionLimit; ver >= 1; ver--)
			{
				if (Loc.hasMessage(messageWithVerText + ver.toString()))
				{
					return Loc.getMessage(messageWithVerText + ver.toString(), replacements);
				}
			}

			if (Loc.hasMessage(messageId))
			{
				return Loc.getMessage(messageId, replacements);
			}

			return null;
		}

		/**
		 * Checks if message exist
		 * @param {string} messageId
		 * @return {boolean}
		 */
		static hasMessage(messageId)
		{
			return Type.isString(messageId) && Type.isStringFilled(BX.message[messageId]);
		}

		/**
		 * Sets message
		 * @param {string} id
		 * @param {string} [value]
		 */
		static setMessage(id, value)
		{
			if (Type.isString(id) && Type.isString(value))
			{
				BX.message({ [id]: value });
			}
		}

		/**
		 * Returns translation by message code.
		 * @param {string} code
		 * @param {number} value
		 * @param {object} replacements
		 * @param {string|null} languageId
		 * @returns {string|null}
		 */
		static getMessagePlural(code, value, replacements = {}, languageId = null)
		{
			const pluralForm = Loc.getPluralForm(value, languageId);

			const messageCode = `${code}_PLURAL_${pluralForm}`;

			return Loc.getMessage(messageCode, replacements);
		}

		/**
		 * Returns translation by message code with plural form.
		 * @param {string} code
		 * @param {number} value
		 * @param {object} [replacements]
		 * @param {?string} [languageId]
		 * @param {number} [versionLimit=1]
		 * @returns {string|null}
		 */
		static getLastMessageVerPlural(code, value, replacements = {}, languageId = null, versionLimit = 1)
		{
			const pluralForm = Loc.getPluralForm(value, languageId);

			const messageCode = `${code}_PLURAL_${pluralForm}`;

			return Loc.getLastMessageVer(messageCode, replacements, versionLimit);
		}

		/**
		 * Returns language plural form id by number
		 * @param {number} value
		 * @param {string|null} languageId
		 * @returns {number}
		 */
		static getPluralForm(value, languageId = null)
		{
			let pluralForm;

			languageId = languageId || env.languageId;
			value = parseInt(value);

			if (value < 0)
			{
				value = (-1) * value;
			}

			switch (languageId)
			{
				case 'br':
				case 'fr':
				case 'tr':
					pluralForm = ((value > 1) ? 1 : 0);
					break;

				case 'ar':
				case 'de':
				case 'en':
				case 'hi':
				case 'it':
				case 'la':
					pluralForm = ((value === 1) ? 0 : 1);
					break;

				case 'ru':
				case 'ua':
					if (
						(value % 10 === 1)
						&& (value % 100 !== 11)
					)
					{
						pluralForm = 0;
					}
					else if (
						(value % 10 >= 2)
						&& (value % 10 <= 4)
						&& (
							(value % 100 < 10)
							|| (value % 100 >= 20)
						)
					)
					{
						pluralForm = 1;
					}
					else
					{
						pluralForm = 2;
					}
					break;

				case 'pl':
					if (value === 1)
					{
						pluralForm = 0;
					}
					else if (
						value % 10 >= 2
						&& value % 10 <= 4
						&& (
							value % 100 < 10
							|| value % 100 >= 20
						)
					)
					{
						pluralForm = 1;
					}
					else
					{
						pluralForm = 2;
					}
					break;

				case 'id':
				case 'ja':
				case 'ms':
				case 'sc':
				case 'tc':
				case 'th':
				case 'vn':
					pluralForm = 0;
					break;

				default:
					pluralForm = 1;
					break;
			}

			return pluralForm;
		}
	}

	module.exports = {
		Loc,
	};
});
