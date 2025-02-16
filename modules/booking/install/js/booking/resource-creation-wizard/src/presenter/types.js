export interface IStep
{
	hidden: boolean;
	labelNext(): string;
	labelBack(): string;
	next(): Promise<void> | void;
	back(): Promise<void> | void;
}
