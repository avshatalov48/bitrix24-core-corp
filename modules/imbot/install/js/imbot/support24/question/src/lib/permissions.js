export class Permissions
{
	#isAdmin;
	#canAskQuestion;
	#canImproveTariff;

	constructor(options)
	{
		this.#isAdmin = Boolean(options.isAdmin);
		this.#canAskQuestion = Boolean(options.canAskQuestion);
		this.#canImproveTariff = Boolean(options.canImproveTariff);
	}

	get isAdmin(): boolean
	{
		return this.#isAdmin;
	}

	get canAskQuestion(): boolean
	{
		return this.#canAskQuestion;
	}

	get canImproveTariff(): boolean
	{
		return this.#canImproveTariff;
	}
}