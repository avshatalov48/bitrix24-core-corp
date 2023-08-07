import FileUploader from '../file-uploader/fileuploader'
import { Controller as FormController} from './controller';
import type { FileData } from '../field/file/item';
import type {ControllerOption} from "../file-uploader/fileuploader";

type CrmFieldFile = {
	fieldId: String,
	fileData: FileData,
};

export default class Uploader
{
	#form: FormController;
	#files: Array<CrmFieldFile>;

	#fileUploader: FileUploader;
	#filesControllers: Array;

	#currentFileIndex = 0;

	#summarySize = 0;
	#allProgress = 0;
	#lastUpdateTime = null;

	progressToShow = 0;
	timeLeft = null;

	alreadyStarted = false;

	running = false;

	constructor(form: FormController)
	{
		this.#form = form;

		this.#fileUploader = new FileUploader({
			identification: this.#form.identification || ""
		});

		this.#fileUploader.subscribe('onChunkUploaded', this.updateProgress.bind(this));
	}

	upload(): Promise
	{
		this.alreadyStarted = true;
		let uploadingPromise: Promise = Promise.resolve();
		uploadingPromise = uploadingPromise.then(() => {
			return this.#uploadNext();
		})

		return uploadingPromise;
	}

	#uploadNext(): Promise
	{
		let promise = Promise.resolve();

		if (this.#currentFileIndex < this.#files.length)
		{
			promise = promise.then(() => {
				const currentFile = this.#files[this.#currentFileIndex];

				this.running = true;

				if (this.#fileUploader.isStarted())
				{
					return this.#fileUploader.continueUpload();
				}
				else
				{
					this.#fileUploader.setFieldId(currentFile.fieldId);
					return this.#fileUploader.upload(currentFile.fileData);
				}
			}).then((response) => {

				if (response.uploaded)
				{
					this.onFileUploaded();
					return this.#uploadNext();
				}
				else
				{
					throw new Error('file not uploaded');
				}
			});

			this.running = false;
		}

		promise.catch(err => {
			this.running = false;
			throw err;
		})

		return promise;
	}

	onFileUploaded()
	{
		this.#currentFileIndex++;
		this.#allProgress = this.progressToShow;
		this.#lastUpdateTime = null;
	}

	updateProgress(currentFileProgress)
	{
		const cur = this.#getCurrentUploadingFile().fileData;
		const currentProgress = (this.#allProgress + (currentFileProgress * cur.size) / this.getSummarySize());

		if (this.#lastUpdateTime)
		{
			const timePassed = (new Date()) - this.#lastUpdateTime;
			const progressDelta = currentProgress - this.progressToShow;
			const speed = progressDelta / timePassed; // uploading progress per millisecond

			this.timeLeft = Math.round((1 - currentProgress) / (1000 * speed));
		}

		this.progressToShow = currentProgress;
		this.#lastUpdateTime = new Date();
	}

	hasFilesToSend()
	{
		if (this.alreadyStarted)
		{
			return this.#files.length > 0;
		}

		this.#filesControllers = this.#form.getFields().filter(e => e.type === 'file' && e.values().length > 0)
		this.#files = this.#filesControllers.reduce(
			(ar, e) => {
				e.values().forEach((value) => {
					const item: CrmFieldFile = {
						fieldId: e.id,
						fileData: value
					};
					ar.push(item);
				});
				return ar;
			},
			[]
		);

		let fieldsSizeMap = {};
		this.#files.forEach((value) => {
				if (value.fieldId in fieldsSizeMap)
				{
					fieldsSizeMap[value.fieldId] += value.fileData.size;
				}
				else
				{
					fieldsSizeMap[value.fieldId] = value.fileData.size;
				}
			},
		);

		const option: ControllerOption = {
			value: fieldsSizeMap,
			key: 'fieldsSize'
		};
		this.#fileUploader.addControllerOption(option);

		this.#currentFileIndex = 0;
		this.refreshSummarySize();

		return this.#files.length > 0;
	}

	refreshSummarySize()
	{
		this.#summarySize = this.#files.reduce((ac, e) => {
			return ac + e.fileData.size
		}, 0);
	}

	getSummarySize()
	{
		return this.#summarySize;
	}

	#getCurrentUploadingFile()
	{
		return this.#files[this.#currentFileIndex];
	}
}