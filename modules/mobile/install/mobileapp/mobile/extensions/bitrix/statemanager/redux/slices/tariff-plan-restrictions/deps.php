<?php

return [
	'extensions' => [
		'statemanager/redux/reducer-registry',
		'statemanager/redux/slices/tariff-plan-restrictions/meta',
		'statemanager/redux/slices/tariff-plan-restrictions/selector',
		'statemanager/redux/slices/tariff-plan-restrictions/thunk',
		'statemanager/redux/slices/tariff-plan-restrictions/tools',
		'statemanager/redux/toolkit',
	],
	'bundle' => [
		'./src/extra-reducer',
	],
];
