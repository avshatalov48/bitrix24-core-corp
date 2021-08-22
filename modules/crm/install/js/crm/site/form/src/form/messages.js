class Storage
{
	language: String = 'en';
	messages: Object = {};

	setMessages (messages)
	{
		this.messages = messages;
	}

	setLanguage (language)
	{
		this.language = language;
	}

	get (code)
	{
		let mess = this.messages;
		let lang = this.language || 'en';
		if (mess[lang] && mess[lang][code])
		{
			return mess[lang][code];
		}

		lang = 'en';
		if (mess[lang] && mess[lang][code])
		{
			return mess[lang][code];
		}

		return mess[code] || '';
	}
}

export {
	Storage,
}