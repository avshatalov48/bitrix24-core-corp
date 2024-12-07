export type ShowToastParams = {
	message: string,
	position?: ToastPosition,
	offset?: number,
	backgroundColor?: string,
	backgroundOpacity?: number,
	svg?: string,
	svgType?: string,
	icon?: object,
}

export enum ToastPosition {
	top = 'top',
	bottom = 'bottom',
}