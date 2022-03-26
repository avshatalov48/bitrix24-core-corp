(() => {

	/**
	 * @class Loc
	 */
	class Loc
	{
		/**
		 * Returns translation by message code.
		 * @param {string} code
		 * @param {object} replacements
		 * @param {string} languageId
		 * @returns {string|null}
		 * @todo implement BX.message() proxy
		 */
		static getMessage(code, replacements = {}, languageId = null)
		{
			return BX.message(code);
		}

		/**
		 * Returns translation by message code.
		 * @param {string} code
		 * @param {number} value
		 * @param {object} replacements
		 * @param {string} languageId
		 * @returns {string|null}
		 * @todo implement method
		 */
		static getMessagePlural(code, value, replacements = {}, languageId = null)
		{
			throw new Error('Not implemented yet');
		}

		/**
		 * Returns language plural form id by number
		 * @param {number} value
		 * @param {string|null} languageId
		 * @returns {number}
		 */
		static getPluralForm(value, languageId)
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
					pluralForm = ((value !== 1) ? 1 : 0);
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

	jnexport(Loc);

})();