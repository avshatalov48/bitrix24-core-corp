/**
 * Loads google source services
 * todo: save loaded instances
 */
export default class Loader
{
	static #loadingPromise = null;

	static #createSrc(apiKey, languageId)
	{
		return 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&libraries=places&language=' + languageId;
	}

	/**
	 * Loads google services
	 * @param {string} apiKey
	 * @param {string} languageId
	 * @returns {Promise}
	 */
	static load(apiKey: string, languageId: string): Promise
	{
		if(Loader.#loadingPromise === null)
		{
			Loader.#loadingPromise = new Promise((resolve) => {

				BX.load(
					[Loader.#createSrc(apiKey, languageId)],
					() => {
						resolve();
					}
				);
			});
		}

		return Loader.#loadingPromise;
	}
}