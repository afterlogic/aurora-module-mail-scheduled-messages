<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages\Storages\Nodb;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package MailScheduledMessages
 * @subpackage Storages
 */
class Storage extends \Aurora\Modules\MailScheduledMessages\Storages\Storage
{
    /**
     *
     * @param \Aurora\System\Managers\AbstractManager $oManager
     */
    public function __construct(\Aurora\System\Managers\AbstractManager &$oManager)
    {
        parent::__construct($oManager);
    }
}
