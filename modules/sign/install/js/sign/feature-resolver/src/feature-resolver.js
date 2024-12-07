import { Extension, Type } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';

export class FeatureResolver
{
	static #canInstance: boolean = false;
	static #instance: FeatureResolver;
	#featureCodes: Set<string>;

	constructor(featureCodes: Array<string> = [])
	{
		if (!FeatureResolver.#canInstance)
		{
			throw new Error('Use FeatureResolver.instance() method to get instance of FeatureResolver');
		}

		this.#featureCodes = new Set(featureCodes);
	}

	static instance(): FeatureResolver
	{
		if (Type.isNil(FeatureResolver.#instance))
		{
			const settings: SettingsCollection = Extension.getSettings('sign.feature-resolver');
			FeatureResolver.#canInstance = true;
			FeatureResolver.#instance = new FeatureResolver(settings.get('featureCodes', []));
			FeatureResolver.#canInstance = false;
		}

		return FeatureResolver.#instance;
	}

	released(code: string): boolean
	{
		return this.#featureCodes.has(code);
	}
}
