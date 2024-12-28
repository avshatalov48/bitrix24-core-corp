<?php

namespace Bitrix\Mobile\Collab\ActionFilter;

use Bitrix\Main;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Socialnetwork\Collab\CollabFeature;

class CollabAccessControl extends Engine\ActionFilter\Base
{
    public function onBeforeAction(Event $event): EventResult|null
    {
        if (!CollabFeature::isOn())
        {
            $this->addError(new Error(
                Main\Localization\Loc::getMessage('COLLAB_ACCESS_CONTROL_ACCESS_DENIED')
            ));

            return new EventResult(EventResult::ERROR, null, null, $this);
        }

        return null;
    }
}