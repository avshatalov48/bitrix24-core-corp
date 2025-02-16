class HelpDesk
{
	show(code: string, anchor: ?string = null): void
	{
		if (top.BX.Helper)
		{
			const params = {
				redirect: 'detail',
				code,
				...(anchor !== null && { anchor }),
			};

			const queryString = Object.entries(params)
				.map(([key, value]) => `${key}=${value}`)
				.join('&');

			top.BX.Helper.show(queryString);
		}
	}
}

export const helpDesk = new HelpDesk();
