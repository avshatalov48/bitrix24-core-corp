IntranetInvite.event.init(
	{
		originator: BX.componentParameters.get('ORIGINATOR', ''),
		disableAdminConfirm: BX.componentParameters.get('DISABLE_ADMIN_CONFIRM', false),
		inviteComponent: inviteComponent
	}
);