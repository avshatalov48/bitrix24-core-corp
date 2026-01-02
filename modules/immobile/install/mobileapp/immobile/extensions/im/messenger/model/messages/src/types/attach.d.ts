export type AttachModelItem = Array<AttachConfig>;

export type AttachConfig = {
	id: number,
	description: string,
	color: string,
	colorToken?: string,
	blocks: Array<AttachConfigBlock>
};

export type AttachConfigBlock = {
	[blockName: string]: Object
};

// message
export type AttachMessageConfig = {
	message: string
};

// delimiter
export type AttachDelimiterConfig = {
	delimiter: {
		size?: number,
		color?: string
	}
};

// file
export type AttachFileConfig = {
	file: Array<AttachFileItemConfig>
};

export type AttachFileItemConfig = {
	link: string,
	name?: string,
	size?: number
};

export type MobileAttachFileConfig = {
	file: Array<MobileAttachFileItemConfig>
};

export type MobileAttachFileItemConfig = {
	link: string,
	name?: string,
	size?: number,
	displayedSize?: string,
	downloadText: string,
};

// grid
export type AttachGridConfig = {
	grid: Array<AttachGridItemConfig>
};

export type AttachGridItemConfig = {
	display: string,
	name: string,
	value: string,
	width?: number,
	color?: string,
	colorToken?: string,
	link?: string
};

// html
export type AttachHtmlConfig = {
	html: string
};

// image
export type AttachImageConfig = {
	image: Array<AttachImageItemConfig>
};

export type AttachImageItemConfig = {
	link: string,
	width?: number,
	height?: number,
	name?: string,
	preview?: string
};

// link
export type AttachLinkConfig = {
	link: Array<AttachLinkItemConfig>
};

export type AttachLinkItemConfig = {
	link: string,
	name?: string,
	desc?: string,
	html?: string,
	preview?: string,
	width?: number,
	height?: number
};

// rich
export type AttachRichConfig = {
	richLink: Array<AttachRichItemConfig>
};

export type AttachRichItemConfig = {
	link: string,
	name?: string,
	desc?: string,
	html?: string,
	preview?: string,
	previewSize?: {
		width: number,
		height: number
	}
};

// user
export type AttachUserConfig = {
	user: Array<AttachRichItemConfig>
};

export type AttachUserItemConfig = {
	name: string,
	avatar: string,
	avatarType: string,
	link: string
};
