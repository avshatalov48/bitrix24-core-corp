import {Type} from "main.core";
import {PULL} from "pull.client";
import type {EditorOptions, DocumentSession, BaseObject} from "./types";
import ServerCommandHandler from "./server-command-handler";

export default class Waiting
{
	documentSession: DocumentSession = null;
	object: BaseObject = null;

	constructor(editorOptions: EditorOptions)
	{
		const options = Type.isPlainObject(editorOptions) ? editorOptions : {};

		this.documentSession = options.documentSession;
		this.object = options.object;

		const loader = new BX.Loader({
			target: document.querySelector('#test'),
		});
		loader.show();

		this.bindEvents();
	}

	bindEvents(): void
	{
		PULL.subscribe(new ServerCommandHandler({
			context: {
				object: this.object,
				documentSession: this.documentSession,
			},
			userManager: null,
		}));
	}
}