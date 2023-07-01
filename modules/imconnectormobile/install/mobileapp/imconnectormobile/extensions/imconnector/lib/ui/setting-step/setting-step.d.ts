type SettingStepProps = {
	withStep: boolean,
	number: number,
	icon: string,
	title: string,
	description: string,
	// @ts-ignore
	additionalComponents?: Array<LayoutComponent>
	onLinkClick: Function;
	linksUnderline: boolean;

};