import { Document } from 'sign.document';

export type PositionType = {
	top: number|string,
	left: number|string,
	width: number|string,
	height: number|string
};

export type BlockOptions = {
	id?: number,
	code: string,
	part: number,
	data?: any,
	position?: PositionType,
	style?: {[key: string]: string},
	document: Document,
	onClick?: () => {},
	onRemove?: () => {},
};
