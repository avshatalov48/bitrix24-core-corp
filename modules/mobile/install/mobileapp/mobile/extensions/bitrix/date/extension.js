(() => {
	// Maps datetime format from php to java.
	// Reference for symbols on the left - https://www.php.net/manual/en/datetime.format.php
	// Reference for symbols on the right - https://docs.oracle.com/javase/7/docs/api/java/text/SimpleDateFormat.html
	const mapper = {
		D: 'E',
		N: Application.getPlatform() === 'ios' ? 'c' : 'u',
		d: 'dd',
		j: 'd',
		M: 'MMM',
		F: 'MMMM',
		m: 'MM',
		n: 'M',
		i: 'mm',
		l: 'EEEE',
		H: 'HH',
		G: 'H',
		h: 'hh',
		g: 'h',
		s: 'ss',
		Y: 'y',
	};

	const data = this.jnExtensionData.get('date');

	if (data.markers)
	{
		const { am, pm } = data.markers;

		DateFormatter.amSymbol = am;
		DateFormatter.pmSymbol = pm;
	}

	this.dateFormatter = {
		convert: (value) => {
			/*
				Right regular expression /\b(?<!\\)(\w)+\b/g ,
				but iOS can't use lookbehind in JS regular expressions :(
				return value.replace(/\b(?<!\\)(\w)+\b/g, find => mapper[find] ? mapper[find] : find);

				In some languages, date formats may include more than just formatting characters,
				such as LONG_DATE_FORMAT in China: Y\?Fj\?.
				Therefore, we are forced to additionally check each character,
				if it is not a format character, then we replace the slash
				to an apostrophe according to the rules of SimpleDateFormat
			*/

			const items = value
				.split(' ')
				.map((item) => {
					if (mapper[item])
					{
						return mapper[item];
					}

					const getPreparedWords = (chars) => {
						const results = [];
						let word = '';

						chars.forEach((char) => {
							if (char === '\\')
							{
								word = char;

								return;
							}

							word += char;
							results.push(word);

							word = '';
						});

						return results;
					};

					const chars = [...item];
					const preparedWords = getPreparedWords(chars);

					const results = [];

					let quotationMarkAddedToBegin = false;
					for (let i = 0; i < preparedWords.length; i++)
					{
						const word = preparedWords[i];
						if (word.length === 0)
						{
							continue;
						}

						const nextWord = preparedWords[i + 1];

						if (word.indexOf('\\') === 0)
						{
							let replacement = "'";
							if (quotationMarkAddedToBegin)
							{
								replacement = '';
							}

							let replacedWord = word.replace('\\', replacement);

							if (
								!nextWord
								|| (nextWord && nextWord.indexOf('\\') !== 0)
							)
							{
								replacedWord += "'";
								quotationMarkAddedToBegin = false;
							}
							else
							{
								quotationMarkAddedToBegin = true;
							}

							results.push(replacedWord);
							continue;
						}

						results.push(word.replaceAll(/\b(\w)+\b/g, (find) => (mapper[find] ? mapper[find] : find)));
					}

					return results.join('');
				})
			;

			return items.join(' ').replaceAll(/\s+/g, ' ');
		},

		formats: (() => data.formats || {})(),

		get: (timestamp, format, locale = null) => DateFormatter.getDateString(timestamp, this.dateFormatter.convert(format), locale),

		test: (timestamp) => {
			for (const format in this.dateFormatter.formats)
			{
				const phpFormat = this.dateFormatter.formats[format];
				const convertedFormat = this.dateFormatter.convert(phpFormat);
				console.log(`${DateFormatter.getDateString(timestamp, convertedFormat)} ---- (${phpFormat} -> ${convertedFormat})`);
			}
		},
	};
})();
