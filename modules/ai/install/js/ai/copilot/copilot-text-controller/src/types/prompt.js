export type Prompt = {
	code: string;
	title: string;
	icon: string;
	section: string;
	required: PromptRequired,
	children: Prompt[],
	workWithResult: boolean;
}

type PromptRequired = {
	context_message: boolean;
	user_message: boolean;
}
