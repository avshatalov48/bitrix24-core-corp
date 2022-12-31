(() => {

	/**
	 * Usage:
	 * console.color('foo', {bar: 'baz'}).red()
	 * console.color('foo', {bar: 'baz'}).green()
	 */
	console.color = (...arguments) =>
	{
		let template = '';
		for (let i = 0; i < arguments.length; i++)
		{
			if(typeof arguments[i] === 'string')
			{
				template = template + '%s';
			}
			if(typeof arguments[i] === 'object')
			{
				template = template + '%O';
			}
			if(typeof arguments[i] === 'number')
			{
				template = template+'%d';
			}
		}

		class ColoredLog
		{
			constructor(template, ...data)
			{
				this.template = template;
				this.data = data[0];
			}

			red()
			{
				this.data.unshift(`background:#ffffff; color:#fb0000; font-size: 14px; padding:0 2px;`);
				this.data.unshift(`💋%c${template}`);
				console.log(...this.data);
			}

			green()
			{
				this.data.unshift(``);
				this.data.unshift(`background:#ffffff; color:green; font-size: 14x; padding:0 0; border-radius:3px;`);
				this.data.unshift(`---- %c🍀%c ----\n\n${template}\n\n-------------`);
				console.log(...this.data);
			}

			log()
			{
				console.log(...this.data);
			}
		}

		return new ColoredLog(template, arguments);
	};

	if (!console.group)
	{
		console.group = (label) => console.log(`>>> ${label}`);
	}

	if (!console.groupEnd)
	{
		console.groupEnd = () => console.log(`<<<`);
	}

})();