export type SettingPage = {
	title: string;
	id: string;
	items: Array<SettingItem>;
};

type SettingSectionType = 'section';
type SettingLinkType = 'link';
type SettingInfoType = 'info';
type SettingToggleType = 'toggle';
type SettingButtonType = 'button';
type SettingThemeSwitchType = 'theme-switch';
type SettingVideoQualitySwitchType = 'video-quality-switch';
type SettingVideoBannerType = 'video-banner';
type SettingLocSelectorType = 'loc-selector';
type SettingDescriptionType = 'description';
type SettingBannerType = 'banner';
type SettingImageType = 'image';
type SettingStyleSwitchType = 'style-switch';

type SettingItemType =
	SettingSectionType
	| SettingLinkType
	| SettingToggleType
	| SettingButtonType
	| SettingThemeSwitchType
	| SettingVideoQualitySwitchType
	| SettingLocSelectorType
	| SettingDescriptionType
	| SettingInfoType
	| SettingBannerType
	| SettingStyleSwitchType
;

interface BaseSettingController
{
	get(): Promise<any>;

	set(value: unknown): Promise<void>;

	setOnChange(callback: (value: unknown) => void): BaseSettingController;
}

export interface SettingItem
{
	id: string;
	type: SettingItemType;
	title?: string;
	prefilter?: (settingsData: unknown) => boolean;
	value?: unknown;
}

export interface SettingSection extends SettingItem
{
	type: SettingSectionType;
	items: Array<SettingItem>;
}

export interface SettingLink extends SettingItem
{
	type: SettingLinkType;
	nextPage: SettingPage;
	subtitle?: string;
	icon?: string;
}

export interface SettingInfo extends SettingItem
{
	type: SettingInfoType;
	subtitle?: string;
	icon?: string;
}

export interface SettingToggle extends SettingItem
{
	type: SettingToggleType;
	subtitle?: string;
	icon?: string;
}

export interface SettingButton extends SettingItem
{
	type: SettingButtonType;
	onClick: Function;
	subtitle?: string;
	icon?: string;
}

export interface SettingThemeSwitch extends SettingItem
{
	type: SettingThemeSwitchType;
	subtitle?: string;
	icon?: string;
}

export interface SettingStyleSwitch extends SettingItem
{
	type: SettingStyleSwitchType;
	controller: BaseSettingController;
}

export interface SettingVideoQualitySwitch extends SettingItem
{
	type: SettingVideoQualitySwitchType;
	controller: BaseSettingController;
}

export interface SettingLocSelector extends SettingItem
{
	type: SettingLocSelectorType;
	controller: BaseSettingController;
}

export interface SettingCacheIntervalSelector extends SettingItem
{
	type: SettingLocSelectorType;
	controller: BaseSettingController;
}

export interface SettingCacheBanner extends SettingItem
{
	type: SettingVideoBannerType;
	controller: BaseSettingController;
}

export interface SettingBanner extends SettingItem
{
	type: SettingBannerType;
	bannerImageName: string;
	text: string;
}

export interface SettingImage extends SettingItem
{
	type: SettingImageType;
	name: string;
}

export interface SettingDescription extends SettingItem
{
	type: SettingDescriptionType;
	text: string;
}

export interface SettingVideoBanner extends SettingItem
{
	type: SettingVideoBannerType;
	controller: BaseSettingController;
}

export type ItemProps = SettingItem & {
	onChange: (id: string, controller: BaseSettingController, value: unknown) => void;
	value?: unknown;
};
