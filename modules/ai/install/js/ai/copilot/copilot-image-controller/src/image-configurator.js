import { Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';

import './css/image-configurator.css';

import { ImageConfiguratorStyles } from './image-configurator-styles';
import { ImageConfiguratorParams } from './image-configurator-params';
import type { ImageConfiguratorParamsCurrentValues } from './image-configurator-params-config';
import type { ImageCopilotFormat, ImageCopilotStyle, EngineInfo } from 'ai.engine';

export type ImageConfiguratorOptions = {
	formats: ImageCopilotFormat[];
	styles: ImageCopilotStyle[];
	engines: EngineInfo[];
}

export type getParamsResult = {
	style: string;
} | ImageConfiguratorParamsCurrentValues;

export class ImageConfigurator extends EventEmitter
{
	#container: HTMLElement;
	#imageConfiguratorStyles: ImageConfiguratorStyles;
	#imageConfiguratorParams: ImageConfiguratorParams;

	constructor(options: ImageConfiguratorOptions)
	{
		super(options);

		this.setEventNamespace('AI.Copilot.ImageConfigurator');

		this.#imageConfiguratorStyles = new ImageConfiguratorStyles({
			styles: options?.styles ?? [],
		});

		this.#imageConfiguratorParams = new ImageConfiguratorParams({
			formats: options?.formats ?? [],
			engines: options?.engines ?? [],
		});

		this.#imageConfiguratorParams.subscribe('change-parameter', (event) => {
			const data = event.getData();

			this.emit('change-parameter', data);
		});
	}

	setFormats(formats: ImageCopilotFormat[]): void
	{
		this.#imageConfiguratorParams.setFormats(formats);
	}

	setSelectedEngine(engineCode: string): void
	{
		this.#imageConfiguratorParams.setSelectedEngine(engineCode);
	}

	getParams(): getParamsResult
	{
		return {
			style: this.#imageConfiguratorStyles.getSelectedStyle(),
			...this.#imageConfiguratorParams.getCurrentValues(),
		};
	}

	isContainsTarget(target: HTMLElement): boolean
	{
		return this.#container?.contains(target) || this.#imageConfiguratorParams?.isContainsTarget(target);
	}

	render(): HTMLElement
	{
		this.#container = Tag.render`
			<div class="ai__copilot-image-configurator">
				<div class="ai__copilot-image-configurator_styles">
					${this.#renderImageStyles()}
				</div>
				<div class="ai__copilot-image-configurator_params">
					${this.#renderImageParams()}
				</div>
			</div>
		`;

		return this.#container;
	}

	#renderImageStyles(): HTMLElement
	{
		return this.#imageConfiguratorStyles.render();
	}

	#renderImageParams(): HTMLElement
	{
		return this.#imageConfiguratorParams.render();
	}
}
