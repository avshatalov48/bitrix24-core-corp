import { Loc } from 'main.core';

const fs = require('fs');
const path = require('path');

export default function loadMessages(langPath = __dirname + '../../lang/', lang = 'en', langFile = 'config.php')
{
	const langFilePath = path.join(path.normalize(langPath), lang, langFile);
	const contents = fs.readFileSync(langFilePath, 'ascii');

	const regex = /\$MESS\[['"](?<code>.+?)['"]]\s*=\s*['"](?<phrase>.*?)['"]/gm;
	let match;

	while ((match = regex.exec(contents)) !== null)
	{
		if (match.index === regex.lastIndex)
		{
			regex.lastIndex++;
		}

		const { code, phrase } = match.groups;
		Loc.setMessage(code, phrase);
	}
}