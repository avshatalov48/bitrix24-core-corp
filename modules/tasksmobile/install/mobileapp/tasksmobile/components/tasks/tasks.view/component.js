(() => {
	const mode = BX.componentParameters.get('MODE', 'view');
	const userId = BX.componentParameters.get('USER_ID', '0');

	if (mode === 'edit')
	{
		new TaskCardEdit(taskcard, userId);

		return;
	}

	new TaskCardView(taskcard, userId);
})();
