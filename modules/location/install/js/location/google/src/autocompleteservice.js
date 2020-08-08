import {Location, AutocompleteServiceBase} from "location.core";

export default class AutocompleteService extends AutocompleteServiceBase
{
	/** {string} */
	#languageId;
	/** {google.maps.places.AutocompleteService} */
	#googleAutocompleteService;
	/** {Promise}*/
	#loaderPromise;
	/** {GoogleSource} */
	#googleSource;
	/** {string} */
	#localStorageKey = 'locationGoogleAutocomplete';
	/** {number} */
	#localStorageResCount = 30;

	constructor(props)
	{
		super(props);
		this.#languageId = props.languageId;
		this.#googleSource = props.googleSource;
		//Because googleSource could still be in the process of loading
		this.#loaderPromise = props.googleSource.loaderPromise
			.then(() => {
				this.#initAutocompleteService();
			});
	}

	#getLocalStoredResults(query, params): object
	{
		let result = null,
			storedResults = localStorage.getItem(this.#localStorageKey);

		if(storedResults)
		{
			try {
				storedResults = JSON.parse(storedResults);
			}
			catch (e) {
				return null;
			}

			if(Array.isArray(storedResults))
			{
				for(const [index, item] of storedResults.entries())
				{
					if(item && typeof item.query !== 'undefined' && item.query === query)
					{
						result = Object.assign({}, item);
						storedResults.splice(index, 1);
						storedResults.push(result);
						localStorage.setItem(this.#localStorageKey, JSON.stringify(storedResults));
						break;
					}
				}
			}
		}
		return result;
	}

	#getPredictionPromiseLocalStorage(query, params): ?Promise
	{
		let result = null,
			answer = this.#getLocalStoredResults(query, params);

		if(answer !== null)
		{
			result = new Promise((resolve) => {
					resolve(
						this.#convertToLocationsList(answer.answer, answer.status)
					);
				}
			);
		}

		return result;
	}

	#setPredictionResult(query, params, answer, status): void
	{
		let storedResults = localStorage.getItem(this.#localStorageKey);

		if(storedResults)
		{
			try {
				storedResults = JSON.parse(storedResults);
			}
			catch (e) {
				return;
			}
		}

		if(!Array.isArray(storedResults))
		{
			storedResults = [];
		}

		storedResults.push({
			status: status,
			query: query,
			answer: answer
		});

		if(storedResults.length > this.#localStorageResCount)
		{
			storedResults.shift();
		}

		localStorage.setItem(this.#localStorageKey, JSON.stringify(storedResults));
	}

	#getPredictionPromise(query, params): Promise
	{
		let result = this.#getPredictionPromiseLocalStorage(query, params);

		if(!result)
		{
			result = new Promise((resolve) => {
					this.#googleAutocompleteService.getQueryPredictions({input: query}, (result, status) => {
						let locationsList = this.#convertToLocationsList(result, status);
						this.#setPredictionResult(query, params, result, status);
						resolve(locationsList);
					});
				}
			);
		}

		return result;
	}

	/**
	 * Returns Promise witch  will transfer locations list
	 * @param {string} query
	 * @param {object} params
	 * @returns {Promise}
	 */
	autocomplete(query:string, params: Object): Promise
	{
		//Because google.maps.places.AutocompleteService could be still in the process of loading
		return this.#loaderPromise
			.then(() => {
				return this.#getPredictionPromise(query, params);
			},
			error => BX.debug(error)
		);
	}

	#initAutocompleteService()
	{
		if(typeof google === 'undefined' || typeof google.maps.places.AutocompleteService === 'undefined')
		{
			throw new Error('google.maps.places.AutocompleteService must be defined');
		}

		this.#googleAutocompleteService = new google.maps.places.AutocompleteService();
	}

	#convertToLocationsList(data, status)
	{
		if(status === 'ZERO_RESULTS')
		{
			return [];
		}

		if(!data || status !== 'OK')
		{
			return false;
		}

		let result = [];

		for (let item of data)
		{
			if(item.place_id)
			{
				const location = new Location({
					sourceCode: this.#googleSource.sourceCode,
					externalId: item.place_id,
					name: item.description,
					languageId: this.#languageId
				});

				result.push(location);
			}
		}

		return result;
	}
}