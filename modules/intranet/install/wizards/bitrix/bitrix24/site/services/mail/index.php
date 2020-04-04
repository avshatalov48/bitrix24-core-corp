<?php

if (CModule::IncludeModule('mail') && IsModuleInstalled("bitrix24"))
{
    $installFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/mail/install/index.php';
    if (!is_file($installFile))
        return false;

    include_once($installFile);

    $moduleIdTmp = 'mail';
    if (!class_exists($moduleIdTmp))
        return false;

    $module = new $moduleIdTmp;

    $module->installBitrix24MailService();
}
