<?php

return [
	'extensions' => [
		'device/connection',

		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',

		'disk:statemanager/redux/slices/files/meta',
		'disk:statemanager/redux/slices/files/thunk',
		'disk:statemanager/redux/slices/files/extra-reducer',
		'disk:statemanager/redux/slices/files/model/file',

	],
];
