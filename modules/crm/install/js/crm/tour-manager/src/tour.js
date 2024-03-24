import { Guide } from 'ui.tour';

export interface TourInterface
{
	getGuide(): Guide;
	canShow(): boolean;
	show(): void;
}
