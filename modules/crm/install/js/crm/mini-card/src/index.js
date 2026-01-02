import { MiniCard, type MiniCardOptions } from './mini-card';
import { EntityMiniCard, type EntityMiniCardOptions } from './entity-mini-card';
import { MiniCardItem, type MiniCardItemOptions } from './lib/model/mini-card-item';
import { Component, type ComponentOptions } from './lib/model/component';
import { MiniCardComponent } from './components/mini-card-component';

// eslint-disable-next-line @bitrix24/bitrix24-rules/need-alias
import 'crm_common';

import 'ui.design-tokens';

export type {
	MiniCardOptions,
	EntityMiniCardOptions,
	MiniCardItemOptions,
	ComponentOptions,
};

export {
	MiniCard,
	EntityMiniCard,
	MiniCardItem,
	Component,
};

export {
	MiniCardComponent,
};
