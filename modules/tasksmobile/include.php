<?php

use Bitrix\Main\Loader;

Loader::requireModule('mobile');
Loader::requireModule('mobileapp');
Loader::requireModule('tasks');
Loader::requireModule('socialnetwork');

\Bitrix\TasksMobile\Engine\AutoWire\Binder::registerDefaultAutoWirings();
