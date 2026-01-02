import { Runtime, Type, Loc } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Model } from 'tasks.v2.const';

import { FileEvent, getFilesFromDataTransfer, isFilePasted } from 'ui.uploader.core';
import { COMMAND_PRIORITY_NORMAL, PASTE_COMMAND } from 'ui.lexical.core';
import { TextEditor, TextEditorOptions, Plugins } from 'ui.text-editor';
import type { UploaderFile, UploaderFileInfo } from 'ui.uploader.core';
import type { VueUploaderAdapter } from 'ui.uploader.vue';
import type { Store } from 'ui.vue3.vuex';

import { Core } from 'tasks.v2.core';
import { fileService, type FileService } from 'tasks.v2.provider.service.file-service';

import { DefaultEditorOptions } from './default-editor-options';

export type DescriptionTextEditorOptions = {
	content: string | null,
};

export class DescriptionTextEditor extends EventEmitter
{
	#editor: TextEditor;
	#fileService: FileService;
	#uploaderAdapter: VueUploaderAdapter;
	#syncHighlightsDebounced: Function;

	constructor(taskId: number | string, options: DescriptionTextEditorOptions)
	{
		super();

		this.setEventNamespace('Tasks.V2.Component.Description-Text-Editor');

		this.initService(taskId);
		this.initEditor(taskId, options);
		this.#subscribeToEvents();
		this.#registerCommands();

		this.#syncHighlightsDebounced = Runtime.debounce(this.#syncHighlights, 500, this);
	}

	getEditor(): TextEditor
	{
		return this.#editor;
	}

	initService(taskId: number | string): void
	{
		this.#fileService = fileService.get(taskId);
		this.#uploaderAdapter = fileService.get(taskId).getAdapter();
	}

	initEditor(taskId: number | string, options: DescriptionTextEditorOptions): void
	{
		const content = this.initDescription(taskId, options.content);

		const additionalEditorOptions: TextEditorOptions = {
			content,
			minHeight: 118,
			placeholder: Loc.getMessage('TASKS_V2_CHANGE_DESCRIPTION'),
			newLineMode: 'paragraph',
			events: {
				onChange: this.handleEditorChange,
			},
			file: {
				mode: 'disk',
				files: this.getFiles(),
			},
			visualOptions: {
				borderWidth: 0,
				blockSpaceInline: 'var(--ui-space-stack-md2)',
				colorBackground: 'transparent',
			},
		};

		this.#editor = new TextEditor({ ...DefaultEditorOptions, ...additionalEditorOptions });
	}

	initDescription(taskId: number | string, originalDescription: string | null): string | null
	{
		if (!Type.isStringFilled(originalDescription))
		{
			return originalDescription;
		}

		const mapping = this.getTempToServerFileIdMap();
		const changedDescription = originalDescription.replaceAll(/(\[disk file id=)(n\d+)/gi, (match, prefix, nId) => {
			return prefix + (mapping[nId] === undefined ? nId : mapping[nId]);
		});

		if (originalDescription !== changedDescription)
		{
			const fields = { description: changedDescription };

			void this.$store.dispatch(`${Model.Tasks}/update`, { id: taskId, fields });
		}

		return changedDescription;
	}

	setEditorText(taskId, content): void
	{
		const text = this.initDescription(taskId, content);

		this.#editor.setText(text);
	}

	getFiles(): UploaderFileInfo[]
	{
		return this.#uploaderAdapter.getItems();
	}

	getTempToServerFileIdMap(): Object
	{
		const filesMap = {};

		this.getFiles().forEach((file: UploaderFileInfo): void => {
			const key = `n${file.customData.objectId}`;

			filesMap[key] = file.serverFileId;
		});

		return filesMap;
	}

	destroy(): void
	{
		this.#unsubscribeToEvents();
		this.#editor.destroy();
		this.#editor = null;
	}

	#subscribeToEvents(): void
	{
		this.#fileService.subscribe('onFileComplete', this.onFileComplete);
		this.#fileService.subscribe('onFileRemove', this.onFileRemove);
	}

	#unsubscribeToEvents(): void
	{
		this.#fileService.unsubscribe('onFileComplete', this.onFileComplete);
		this.#fileService.unsubscribe('onFileRemove', this.onFileRemove);
	}

	#registerCommands(): void
	{
		this.#editor.registerCommand(
			PASTE_COMMAND,
			(clipboardEvent: ClipboardEvent) => {
				const clipboardData: DataTransfer = clipboardEvent.clipboardData;
				if (!clipboardData || !isFilePasted(clipboardData))
				{
					return false;
				}

				clipboardEvent.preventDefault();

				getFilesFromDataTransfer(clipboardData)
					.then((files: File[]): void => {
						files.forEach((file: File): void => {
							this.#uploaderAdapter.getUploader().addFile(file, {
								events: {
									[FileEvent.LOAD_ERROR]: () => {},
									[FileEvent.UPLOAD_ERROR]: () => {},
									[FileEvent.UPLOAD_COMPLETE]: (event: BaseEvent): void => {
										const uploaderFile: UploaderFile = event.getTarget();

										this.insertFile(uploaderFile.toJSON());
									},
								},
							});
						});
					})
					.catch((): void => {
						console.error('clipboard pasting error');
					})
				;

				return true;
			},
			COMMAND_PRIORITY_NORMAL,
		);
	}

	#syncHighlights(): void
	{
		if (!this.#editor)
		{
			return;
		}

		this.#editor.dispatchCommand(Plugins.File.GET_INSERTED_FILES_COMMAND, (nodes) => {
			const inserted: Set<number | string> = new Set();
			for (const node of nodes)
			{
				const { serverFileId } = node.getInfo();
				if (Type.isStringFilled(serverFileId) || Type.isNumber(serverFileId))
				{
					inserted.add(serverFileId);
				}
			}

			this.#uploaderAdapter.getUploader().getFiles().forEach((file: UploaderFile) => {
				if (inserted.has(file.getServerFileId()))
				{
					file.setCustomData('tileSelected', true);
					inserted.delete(file.getServerFileId());
				}
				else
				{
					file.setCustomData('tileSelected', false);
				}
			});

			for (const serverFileId: number | string of inserted)
			{
				this.handleRemoveFile(serverFileId);
			}
		});
	}

	onFileComplete = (event: BaseEvent): void => {
		const file = event.getData();

		this.#editor.dispatchCommand(Plugins.File.ADD_FILE_COMMAND, file);

		this.emit('filesChanged');
	};

	onFileRemove = (event: BaseEvent): void => {
		const { file } = event.getData();

		this.handleRemoveFile(file.serverFileId);

		this.emit('filesChanged');
	};

	handleEditorChange = (): void => {
		this.#syncHighlightsDebounced();

		this.emit('editorChanged');
	};

	handleRemoveFile(serverFileId): void
	{
		this.#editor.dispatchCommand(Plugins.File.REMOVE_FILE_COMMAND, {
			serverFileId,
			skipHistoryStack: true,
		});

		this.#syncHighlights();
	}

	insertFile(fileInfo): void
	{
		this.#editor.dispatchCommand(Plugins.File.INSERT_FILE_COMMAND, {
			serverFileId: fileInfo.serverFileId,
			width: 600,
			height: 600,
			info: fileInfo,
			inline: true,
		});
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

const instances = {};

export const descriptionTextEditor = {
	get(taskId: number | string, options: DescriptionTextEditorOptions = {}): DescriptionTextEditor
	{
		instances[taskId] ??= new DescriptionTextEditor(taskId, options);

		if (Type.isStringFilled(options?.content))
		{
			instances[taskId].setEditorText(taskId, options.content);
		}

		return instances[taskId];
	},
	replace(tempId: number | string, taskId: number): void
	{
		instances[taskId] = instances[tempId];
		instances[taskId].initService(taskId);

		delete instances[tempId];
	},
	delete(taskId: number | string): void
	{
		instances[taskId]?.destroy();

		delete instances[taskId];
	},
};
