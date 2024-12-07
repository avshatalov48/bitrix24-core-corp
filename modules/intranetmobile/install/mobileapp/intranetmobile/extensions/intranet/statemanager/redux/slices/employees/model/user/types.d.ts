declare type IntranetUserReduxModel = {
	id: number;
	department: {[number]: string} | null;
	isExtranetUser: boolean;
	isWindowsAppInstalled: boolean;
	isLinuxAppInstalled: boolean;
	isMacAppInstalled: boolean;
	isIosAppInstalled: boolean;
	isAndroidAppInstalled: boolean;
	employeeStatus: number;
	dateRegister: number;
	requestStatus: 'Idle' | 'Pending' | 'Fulfilled' | 'Rejected';
	lastInvitationTimestamp: number;
};
