import { ItemData } from './item';

export type PartiesData = {
	company: ItemData,
	representative: ItemData,
	employees: Array<ItemData>,
	validation: ItemData,
};
export type DocumentData = {
	uid: string;
	title: string;
	blocks: Array<{ party: number; }>;
	externalId: string | null;
	integrationId: number | null,
	templateUid: string | null;
};
