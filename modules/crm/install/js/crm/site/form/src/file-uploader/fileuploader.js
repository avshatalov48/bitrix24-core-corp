import Chunk from './chunk'
import Event from '../util/event'
import type { FileData } from '../field/file/item';
import type { Identification } from '../form/types';

type ChunkOptions = {
	minSize: number,
	defaultSize: number,
}

export type ControllerOption = {
	key: string,
	value: string | Object,
}

export default class FileUploader extends Event
{
	#file: File;
	#fileData: FileData;
	#fieldId: String;

	#chunkOffset: Number | null = null;
	#fileToken = null;

	#identification: Identification;
	#controller = 'crm.fileUploader.siteFormFileUploaderController';
	#action = 'crm.site.fileUploader.upload';

	#currentChunk: Chunk;

	#started = false;

	#uploaded = false;

	#chunkOptions: ChunkOptions;

	#additionalControllerOptions: Array<ControllerOption> = [];

	constructor(options)
	{
		super();
		this.#identification = options.identification;

		const serverOptions = window.b24form.common.properties.uploader || {};
		this.#chunkOptions = {
			minSize: serverOptions.chunkMinSize || 1024 * 1024,
			defaultSize: serverOptions.chunkDefaultSize || 5 * 1024 * 1024,
		}
	}

	upload(fileData: FileData): Promise
	{
		if (this.#chunkOffset !== null)
		{
			return;
		}

		this.#uploaded = false;
		this.#fileData = fileData;
		this.#file = fileData.file;

		let uploadingPromise: Promise = Promise.resolve();

		const nextChunk = this.#getNextChunk();
		this.#currentChunk = nextChunk;

		if (nextChunk)
		{
			uploadingPromise = uploadingPromise.then(() => {
				return this.#uploadChunk(nextChunk);
			});
		}

		return uploadingPromise.then(() => {
			return {
				'uploaded': this.#uploaded,
				'token': this.#fileToken
			};
		});
	}

	continueUpload(): Promise
	{
		let uploadingPromise: Promise = Promise.resolve();

		if (!this.#currentChunk || !this.#started || this.#uploaded)
		{
			return uploadingPromise;
		}

		uploadingPromise = uploadingPromise.then(() => {
			return this.#uploadChunk(this.#currentChunk);
		});

		return uploadingPromise.then(() => {
			return {
				'uploaded': this.#uploaded,
				'token': this.#fileToken
			};
		});
	}

	#getNextChunk(): Chunk | null
	{
		if (this.getChunkOffset() !== null && this.getChunkOffset() >= this.getFile().size)
		{
			// End of File
			return null;
		}

		if (this.getChunkOffset() === null)
		{
			// First call
			this.#chunkOffset = 0;
		}

		let chunk: Chunk;
		if (this.getChunkOffset() === 0 && this.getFile().size <= this.getChunkSize())
		{

			chunk = new Chunk(this.getFile(), this.getChunkOffset());
			this.#chunkOffset = this.getFile().size;
		}
		else
		{
			const currentChunkSize = Math.min(this.getChunkSize(), this.getFile().size - this.getChunkOffset());
			const nextOffset = this.getChunkOffset() + currentChunkSize;
			const fileRange = this.getFile().slice(this.getChunkOffset(), nextOffset);

			chunk = new Chunk(fileRange, this.getChunkOffset());
			this.#chunkOffset = nextOffset;
		}

		return chunk;
	}

	#uploadChunk(chunk: Chunk): Promise
	{
		this.#started = true;
		this.#currentChunk = chunk;

		const totalSize = this.getFile().size;
		const isOnlyOneChunk = chunk.getOffset() === 0 && totalSize === chunk.getSize();

		let fileName = this.getFile().name;
		if (fileName.normalize)
		{
			fileName = fileName.normalize();
		}

		let headers: Headers = new Headers({
				'Content-Type': this.getFile().type || 'application/octet-stream',
				'X-Upload-Content-Name': encodeURIComponent(fileName),
				'Crm-Webform-Cors': 'Y'
		});

		if (!isOnlyOneChunk)
		{
			const rangeStart = chunk.getOffset();
			const rangeEnd = chunk.getOffset() + chunk.getSize() - 1;
			const rangeHeader = `bytes ${rangeStart}-${rangeEnd}/${totalSize}`;

			headers.append('Content-Range', rangeHeader);
		}

		return this.#postChunk({
			data: chunk.getData(),
			headers: headers,
			address: this.#identification.address + `/bitrix/services/main/ajax.php?action=${this.#action}`
		});
	}

	isStarted(): boolean
	{
		return this.#started;
	}

	#postChunk(config): Promise
	{
		let controllerOptions = {
			'formId': this.#identification.id,
			'secCode': this.#identification.sec,
			'fieldId': this.getFieldId(),
		};

		this.#additionalControllerOptions.forEach((parameter) => {
			controllerOptions[parameter.key] = parameter.value;
		});

		const getParameters =
			`&controller=${this.#controller}` +
			`&token=${this.#fileToken || 0}` +
			`&controllerOptions=${JSON.stringify(controllerOptions)}`
		;

		config.address += getParameters;

		return new Promise((resolve, reject) => {
			let xhr = new XMLHttpRequest();
			xhr.open('POST', config.address);
			xhr.withCredentials = true;

			config.headers.forEach((value, key) => {
				xhr.setRequestHeader(key, value);
			})

			xhr.upload.onprogress = (event) => {
				if (event.lengthComputable)
				{
					this.onUploadProgress(event.loaded)
				}
			};

			xhr.onload = () => {
				if(xhr.status >= 200 && xhr.status < 300) {
					resolve(xhr.response);
				} else {
					reject(xhr.response);
				}
			};

			xhr.onerror = () => reject(xhr.statusText);
			xhr.send(config.data);
		}).then(response => {
			return JSON.parse(response);
		}).then(response => {
			if (response.status === "error")
			{
				response.errors[0] = response.errors[0] || {message: 'Unknown error'};
				throw new Error(response.errors[0].message);
			}
			if (response.data.token)
			{
				this.setToken(response.data.token);
				let promise = Promise.resolve();

				if (!response.data.done)
				{
					// Upload next chunk
					const nextChunk = this.#getNextChunk();

					if (nextChunk)
					{
						promise = promise.then(() => {
							return this.#uploadChunk(nextChunk);
						});
					}
				}
				else
				{
					this.onDone();
				}

				return promise;
			}
			else
			{
				throw new Error('Chunk not Uploaded');
			}
		});
	}

	onUploadProgress(uploadedBytes: number): void
	{
		let leftToSent = this.#currentChunk.getSize() - uploadedBytes;
		let uploadedDataBytes = this.#chunkOffset - leftToSent;

		this.progress = Math.floor((uploadedDataBytes / this.#file.size) * 1000) / 10;

		this.emit('onChunkUploaded', (uploadedDataBytes / this.#file.size));
	}

	onChunkUploaded(): void
	{
		this.progress = Math.floor(this.#chunkOffset / this.#file.size * 1000) / 10;

		this.emit('onChunkUploaded', this.#chunkOffset / this.#file.size);
	}

	onDone(): void
	{
		this.#fileData.token = this.#fileToken;
		this.#fileData.content = '';

		this.#uploaded = true;
		this.refresh();
	}

	refresh(): void
	{
		this.#file = null;
		this.#fileData = null;
		this.#fileToken = null;
		this.#chunkOffset = null;
		this.#started = false;
	}

	getChunkSize(): number
	{
		return this.#chunkOptions.defaultSize;
	}

	getFile(): File
	{
		return this.#file;
	}

	setToken(token): void
	{
		this.#fileToken = token;
	}

	getChunkOffset(): Number | null
	{
		return this.#chunkOffset;
	}

	setFieldId(fieldId: String): void
	{
		this.#fieldId = fieldId;
	}

	getFieldId(): ?String
	{
		return this.#fieldId || null;
	}

	addControllerOption(parameter: ControllerOption): void
	{
		this.#additionalControllerOptions.push(parameter);
	}
}