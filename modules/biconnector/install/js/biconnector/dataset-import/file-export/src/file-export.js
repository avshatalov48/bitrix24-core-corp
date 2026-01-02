import { ajax as Ajax, Dom } from 'main.core';

type Dataset = {
	id: number,
	title: ?string,
};

export class FileExport
{
	static #instance: FileExport;
	#exportDownloadLinks: Map<number, string>;

	constructor()
	{
		this.#exportDownloadLinks = new Map();
	}

	static getInstance(): FileExport
	{
		if (!FileExport.#instance)
		{
			FileExport.#instance = new FileExport();
		}

		return FileExport.#instance;
	}

	downloadOnce(dataset: Dataset): Promise
	{
		return new Promise((resolve, reject) => {
			this.#getDownloadLink(dataset.id)
				.then((link) => {
					this.#downloadDatasetFromLink(link, dataset);
					URL.revokeObjectURL(link);
					this.#exportDownloadLinks.delete(dataset.id);

					resolve();
				})
				.catch((error) => {
					reject(error);
				});
		});
	}

	download(dataset: Dataset): Promise
	{
		return new Promise((resolve, reject) => {
			this.#getDownloadLink(dataset.id)
				.then((link) => {
					this.#downloadDatasetFromLink(link, dataset);

					resolve();
				})
				.catch((error) => {
					reject(error);
				});
		});
	}

	#getDownloadLink(datasetId: number): Promise
	{
		return new Promise((resolve, reject) => {
			if (this.#exportDownloadLinks.has(datasetId))
			{
				resolve(this.#exportDownloadLinks.get(datasetId));
			}

			Ajax.runAction(
				'biconnector.externalsource.dataset.export',
				{
					data: {
						id: datasetId,
						exportFormat: 'csv',
					},
				},
			)
				.then((response) => {
					const blob = new Blob([response.data], { type: 'text/csv' });
					const link = URL.createObjectURL(blob);
					this.#exportDownloadLinks.set(datasetId, link);

					resolve(link);
				})
				.catch((error) => {
					reject(error);
				});
		});
	}

	#downloadDatasetFromLink(link: string, dataset: Dataset): void
	{
		const anchorElement = document.createElement('a');
		anchorElement.href = link;
		anchorElement.download = `${dataset.title ?? 'csv_dataset'}.csv`;
		Dom.append(anchorElement, document.body);
		anchorElement.click();
		Dom.remove(anchorElement);
	}
}
