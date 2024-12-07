export const FormType = {
	EMAIL: 'email',
	PHONE: 'phone',
};

export type ReinvitePopupOptions = {
	userId: number,
	formType: FormType,
	bindElement?: Element,
	transport?: function,
	inputValue?: string
};