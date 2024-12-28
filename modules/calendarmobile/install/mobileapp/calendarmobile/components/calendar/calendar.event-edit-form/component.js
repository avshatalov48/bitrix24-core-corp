(() => {
	const require = (ext) => jn.require(ext);

	const { EventEditForm } = require('calendar/event-edit-form');

	BX.onViewLoaded(async () => {
		const ownerId = BX.componentParameters.get('OWNER_ID', null);
		const calType = BX.componentParameters.get('CAL_TYPE', 'user');
		const participantsEntityList = BX.componentParameters.get('PARTICIPANTS_ENTITY_LIST', []);
		const description = BX.componentParameters.get('DESCRIPTION', '');
		const createChatId = BX.componentParameters.get('CREATE_CHAT_ID', null);
		const uuid = BX.componentParameters.get('UUID', null);

		await EventEditForm.initEditForm({
			ownerId,
			calType,
			participantsEntityList,
			description,
			createChatId,
			uuid,
		});

		layout.enableNavigationBarBorder(false);
		layout.showComponent(
			new EventEditForm({
				layout,
			}),
		);
	});
})();
