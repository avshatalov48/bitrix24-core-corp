export type HeaderButtonsConfig = {
	rightButtons: Array<Button | PopupCreateButton>;
}

export type HeaderButtonConfig = {
	id: string;
	type: string;
	callback: () => Promise<void>;
	testId?: string;
	shouldShow?: () => Promise<boolean>;
	badgeCode?: string;
}

export type HeaderPopupCreateButtonConfig = {
	id: string;
	type: string;
	title: string;
	buttons: HeaderPopupButtonConfig[];
	testId?: string;
	sections?: HeaderSectionConfig[],
	badgeCode?: string;
}

export type HeaderSectionConfig = {
	id: string;
}

export type HeaderPopupButtonConfig = {
	id: string;
	title: string;
	callback: () => Promise<void>;
	sectionCode?: string;
	iconName?: string;
	shouldShow?: () => Promise<boolean>;
}
