export type EditorOptions = {
	targetNode: HTMLElement,
	saveButtonNode: HTMLElement,
	cancelButtonNode: HTMLElement,
	editorNode: HTMLElement,
	editorWrapper: HTMLElement,
	documentSession: DocumentSession,
	object: BaseObject,
	editorJson: any,
};

export type DocumentSession = {
	id: number,
	hash: string,
}

export type BaseObject = {
	id: number,
}