<?php

use Bitrix\Main\Loader;

Loader::requireModule('mobile');
Loader::requireModule('mobileapp');
Loader::requireModule('tasks');

\Bitrix\TasksMobile\Engine\AutoWire\Binder::registerDefaultAutoWirings();