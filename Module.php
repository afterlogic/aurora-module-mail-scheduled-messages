<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Settings $oModuleSettings
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
    protected $aRequireModules = ['Mail'];

    /**
     * @var Manager
     */
    public $oManager = null;

    public $sScheduledFolderName = 'Scheduled';

    public $oMailModuleAccountsManager = null;

    public $oMailModuleMailManager = null;

    /**
     * @return Module
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @return Module
     */
    public static function Decorator()
    {
        return parent::Decorator();
    }

    /**
     * @return Settings
     */
    public function getModuleSettings()
    {
        return $this->oModuleSettings;
    }

    public function init()
    {
        $this->oManager = new Manager($this);

        $this->subscribeEvent('Mail::GetFolders::before', array($this, 'onBeforeGetFolders'));
        $this->subscribeEvent('Mail::GetFolders::after', array($this, 'onAfterGetFolders'));
        $this->subscribeEvent('Mail::GetMessage::after', array($this, 'onAfterGetMessage'));
        $this->subscribeEvent('Mail::MoveMessages::after', array($this, 'onMoveOrDeleteMessages'));
        $this->subscribeEvent('Mail::DeleteMessages::after', array($this, 'onMoveOrDeleteMessages'));
        $this->subscribeEvent('Mail::DeleteAccount::after', array($this, 'onAfterDeleteAccount'));

        $this->denyMethodsCallByWebApi([
            'GetMessagesForSend',
            'GetMessage',
            'RemoveMessage'
        ]);

        $oMailModule = \Aurora\Modules\Mail\Module::getInstance();
        $this->oMailModuleAccountsManager = $oMailModule->getAccountsManager();
        $this->oMailModuleMailManager = $oMailModule->getMailManager();
    }

    protected function getScheduledFolderFullName($oAccount)
    {
        $oNamespace = \Aurora\Modules\Mail\Module::getInstance()->getMailManager()->getFoldersNamespace($oAccount);
        $sNamespace = $oNamespace ? $oNamespace->GetPersonalNamespace() : '';
        return $sNamespace . $this->sScheduledFolderName;
    }

    public function onBeforeGetFolders(&$aArgs, &$mResult)
    {
        $iAccountID = $aArgs['AccountID'];
        $oAccount = $this->oMailModuleAccountsManager->getAccountById($iAccountID);
        if ($oAccount) {
            $sScheduledFolderFullName = $this->getScheduledFolderFullName($oAccount);
            $aResult = \Aurora\Modules\Mail\Module::Decorator()->GetRelevantFoldersInformation($iAccountID, array($sScheduledFolderFullName), false);
            if (empty($aResult['Counts'])) {
                try {
                    \Aurora\Modules\Mail\Module::Decorator()->CreateFolder($iAccountID, $sScheduledFolderFullName, '', '/');
                } catch (\Exception $oException) {
                }
            }
        }
    }

    public function onAfterGetFolders(&$aArgs, &$mResult)
    {
        $oFolderCollection = &$mResult['Folders'];

        $aList = &$oFolderCollection->GetAsArray();
        $iSentPos = 0;
        $sNamespace = $oFolderCollection->getNamespace();
        if (!empty($sNamespace)) {
            $sNamespaceFolderName = substr($sNamespace, 0, -1);
            $oNamespaceFolder = &$oFolderCollection->getFolder($sNamespaceFolderName, true);
            if ($oNamespaceFolder) {
                $aList = &$oNamespaceFolder->getSubFolders()->GetAsArray();
            }
        }

        foreach ($aList as $iKey => $oFolder) {
            if ($oFolder->getType() == \Aurora\Modules\Mail\Enums\FolderType::Sent) {
                $iSentPos = $iKey;
                break;
            }
        }

        $oScheduledFolder = null;
        foreach ($aList as $iKey => $oFolder) {
            if ($oFolder->getName() === $this->sScheduledFolderName) {
                $oScheduledFolder = $oFolder;
                unset($aList[$iKey]);
                break;
            }
        }

        if ($oScheduledFolder) {
            array_splice($aList, $iSentPos + 1, 0, [$oScheduledFolder]);
        }
    }

    public function onAfterGetMessage($aArgs, &$mResult)
    {
        if ($mResult instanceof \Aurora\Modules\Mail\Classes\Message) {
            $iAccountID = $aArgs['AccountID'];
            $aMessage = $this->oManager->getMessage($iAccountID, $mResult->getFolder(), $mResult->getUid());
            if ($aMessage !== false) {
                $mResult->addExtend('ScheduleTimestamp', ['ScheduleTimestamp' => $aMessage['ScheduleTimestamp']]);
            }
        }
    }

    public function onMoveOrDeleteMessages($aArgs, &$mResult)
    {
        if ($mResult) {
            $iAccountID = $aArgs['AccountID'];
            $oAccount = $this->oMailModuleAccountsManager->getAccountById($iAccountID);
            $sFolder = $aArgs['Folder'];
            if ($sFolder === $this->getScheduledFolderFullName($oAccount)) {
                $sUids = $aArgs['Uids'];
                $aUids = \Aurora\System\Utils::ExplodeIntUids((string) $sUids);
                foreach ($aUids as $sUid) {
                    $this->oManager->removeMessage($iAccountID, $sFolder, $sUid);
                }
            }
        }
    }
    public function onAfterDeleteAccount($aArgs, &$mResult)
    {
        if ($mResult) {
            $this->oManager->removeAccountMessages($aArgs['AccountID']);
        }
    }

    public function GetSettings()
    {
        return [
            'ScheduledFolderName' => $this->sScheduledFolderName,
            'PredefinedSchedule' => $this->oModuleSettings->PredefinedSchedule
        ];
    }

    public function SaveScheduledMessage(
        $AccountID,
        $Fetcher = null,
        $Alias = null,
        $IdentityID = 0,
        $To = "",
        $Cc = "",
        $Bcc = "",
        $Recipients = array(),
        $Subject = "",
        $Text = "",
        $IsHtml = false,
        $Importance = \MailSo\Mime\Enumerations\MessagePriority::NORMAL,
        $SendReadingConfirmation = false,
        $Attachments = array(),
        $InReplyTo = "",
        $References = "",
        $Sensitivity = \MailSo\Mime\Enumerations\Sensitivity::NOTHING,
        $DraftFolder = "",
        $DraftUid = "",
        $CustomHeaders = [],
        $ScheduleDateTime = null
    ) {
        $iNewUid = 0;
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        $oAccount = $this->oMailModuleAccountsManager->getAccountById($AccountID);

        \Aurora\Modules\Mail\Module::checkAccess($oAccount);

        $oIdentity = $IdentityID !== 0 ? \Aurora\Modules\Mail\Module::getInstance()->getIdentitiesManager()->getIdentity($IdentityID, $AccountID) : null;
        $oMessage = \Aurora\Modules\Mail\Module::Decorator()->BuildMessage(
            $oAccount,
            $To,
            $Cc,
            $Bcc,
            $Subject,
            $IsHtml,
            $Text,
            $Attachments,
            null,
            $InReplyTo,
            $References,
            $Importance,
            $Sensitivity,
            $SendReadingConfirmation,
            $Fetcher,
            $Alias,
            false,
            $oIdentity,
            $CustomHeaders
        );
        if ($oMessage) {
            $oMessage->SetDate($ScheduleDateTime);
            $rMessageStream = \MailSo\Base\ResourceRegistry::CreateMemoryResource();
            $iMessageStreamSize = \MailSo\Base\Utils::MultipleStreamWriter($oMessage->ToStream(true), array($rMessageStream), 8192, true, true, true);

            $FolderFullName = $this->getScheduledFolderFullName($oAccount);
            $this->oMailModuleMailManager->appendMessageFromStream($oAccount, $rMessageStream, $FolderFullName, $iMessageStreamSize, $iNewUid);
            $this->oMailModuleMailManager->setMessageFlag($oAccount, $FolderFullName, [$iNewUid], \MailSo\Imap\Enumerations\MessageFlag::SEEN, \Aurora\Modules\Mail\Enums\MessageStoreAction::Add);

            if (0 < strlen($DraftFolder) && 0 < strlen($DraftUid)) {
                try {
                    $this->oMailModuleMailManager->deleteMessage($oAccount, $DraftFolder, array($DraftUid));
                } catch (\Exception $oException) {
                }
            }

            $this->oManager->removeMessage($oAccount->Id, $FolderFullName, $iNewUid);
            $this->oManager->addMessage($oAccount->Id, $FolderFullName, $iNewUid, $ScheduleDateTime);
        }

        return $iNewUid;
    }

    public function GetMessagesForSend($ScheduledTimestamp)
    {
        return $this->oManager->getMessagesForSend($ScheduledTimestamp);
    }

    public function GetMessage($AccountID, $FolderFullName, $MessageUid)
    {
        return $this->oManager->getMessage($AccountID, $FolderFullName, $MessageUid);
    }

    public function RemoveMessage($AccountID, $FolderFullName, $MessageUid)
    {
        return $this->oManager->removeMessage($AccountID, $FolderFullName, $MessageUid);
    }
}
