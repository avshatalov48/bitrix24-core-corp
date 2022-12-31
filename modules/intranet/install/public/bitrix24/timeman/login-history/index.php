<?php

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\ModuleManager;

require $_SERVER["DOCUMENT_ROOT"] . '/bitrix/header.php';
global $APPLICATION;
global $USER;

$isCloud = ModuleManager::isModuleInstalled('bitrix24');
$locked = $isCloud && !Feature::isFeatureEnabled('user_login_history');
$isAdmin = \Bitrix\Intranet\CurrentUser::get()->isAdmin();

if (!$locked)
{

    if (isset($_GET['user']) && ((int)$_GET['user'] > 0) && $isAdmin)
    {
        $userId = (int)$_GET['user'];
    }
    else
    {
        $userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
    }

    $APPLICATION->IncludeComponent(
        'bitrix:ui.sidepanel.wrapper',
        '',
        [
            'POPUP_COMPONENT_NAME' => 'bitrix:intranet.user.login.history',
            'POPUP_COMPONENT_TEMPLATE_NAME' => '',
            'POPUP_COMPONENT_PARAMS' => [
                'USER_ID' => $userId
            ],
            'USE_UI_TOOLBAR' => 'Y',
            'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
        ]
    );
}
else
{
    ?>
    <script>
        BX.ready(() => {
            BX.UI.InfoHelper.show("limit_office_login_history");
        });
    </script>
    <?php
}

require $_SERVER["DOCUMENT_ROOT"] . '/bitrix/footer.php';

