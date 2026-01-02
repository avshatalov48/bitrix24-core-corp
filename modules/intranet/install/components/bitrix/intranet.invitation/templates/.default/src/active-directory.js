export class ActiveDirectory
{
	showForm()
	{
		BX.UI.Feedback.Form.open(
			{
				id: 'intranet-active-directory',
				forms: [
					{ zones: ['ru'], id: 309, lang: 'ru', sec: 'fbc0n3' },
				],
			},
		);
	}
}
