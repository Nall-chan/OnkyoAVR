<?php

declare(strict_types=1);
/**
 * @author        Michael Tröger <micha@nall-chan.net>
 * @copyright     2020 Michael Tröger
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 *
 * @version       2.0
 */
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';  // diverse Klassen
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/WebhookHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property int $ParentID Die InstanzeID des IO-Parent
 * @property \OnkyoAVR\ONKYO_Zone_NetPlayer $OnkyoZone
 * @property string $Multi_Cover
 * @property int $Layer
 * @property int $Sequence
 * @property int $ServiceType
 * @property int $UiType
 * @property int $ListItems
 * @property int $ActiveIndex
 * @property string $FolderName
 * @property string $WebHookSecret
 */
class OnkyoNetplayer extends IPSModule
{
    use \OnkyoNetplayer\DebugHelper,
        \OnkyoNetplayer\BufferHelper,
        \OnkyoNetplayer\InstanceStatus,
        \OnkyoNetplayer\VariableHelper,
        \OnkyoNetplayer\VariableProfileHelper,
        \OnkyoNetplayer\WebhookHelper {
        \OnkyoNetplayer\InstanceStatus::MessageSink as IOMessageSink;
        \OnkyoNetplayer\InstanceStatus::RequestAction as IORequestAction;
    }

    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}');
        $this->RegisterPropertyInteger('Zone', \OnkyoAVR\ONKYO_Zone_NetPlayer::ZoneMain);
        $this->RegisterPropertyBoolean('showCover', true);
        $this->RegisterPropertyBoolean('showNavigation', true);
        $Style = $this->GenerateHTMLStyleProperty();
        $this->RegisterPropertyString('Icons', json_encode($Style['Icons']));
        $this->RegisterPropertyString('Table', json_encode($Style['Table']));
        $this->RegisterPropertyString('Columns', json_encode($Style['Columns']));
        $this->RegisterPropertyString('Rows', json_encode($Style['Rows']));
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone_NetPlayer();
        $this->Multi_Cover = '';
        $this->FolderName = '';
        $this->Layer = -1;
        $this->Sequence = 1;
        $this->ServiceType = 0;
        $this->UiType = 0;
        $this->ListItems = 0;
        $this->WebHookSecret = '';
    }

    /**
     * Interne Funktion des SDK.
     */
    public function Destroy()
    {
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return parent::Destroy();
        }
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterProfile('Onkyo.NetTunerPreset');
            $this->UnregisterProfile('Onkyo.Status');
            $this->UnregisterProfile('Onkyo.Shuffle.' . $this->InstanceID);
            $this->UnregisterProfile('Onkyo.Repeat.' . $this->InstanceID);
            $this->UnregisterProfile('Onkyo.Tracks');
            $this->UnregisterProfile('Onkyo.Network');
            $this->UnregisterProfile('Onkyo.USB');
            $this->UnregisterProfile('Onkyo.SelectNetworkService.' . $this->InstanceID);
            $CoverID = @IPS_GetObjectIDByIdent('Cover', $this->InstanceID);
            if ($CoverID > 0) {
                @IPS_DeleteMedia($CoverID, true);
            }
        }

        parent::Destroy();
    }

    public function ApplyChanges()
    {
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        $this->Multi_Cover = '';
        $this->Layer = -1;
        $this->Sequence = 1;
        $this->ServiceType = 0;
        $this->UiType = 0;
        $this->ListItems = 0;
        $this->FolderName = '';
        parent::ApplyChanges();
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone_NetPlayer($this->ReadPropertyInteger('Zone'));
        $this->SetSummary($this->OnkyoZone->GetName());

        $Zone = new \OnkyoAVR\ONKYO_Zone(\OnkyoAVR\ONKYO_Zone::Netplayer);
        $APICommands = $Zone->GetAPICommands();
        if (count($APICommands) > 0) {
            foreach ($APICommands as $APICommand) {
                $Lines[] = '.*"APICommand":"' . $APICommand . '".*';
            }
            $Line = implode('|', $Lines);
            $this->SetReceiveDataFilter('(' . $Line . ')');
            $this->SendDebug('FILTER', $Line, 0);
        } else {
            $this->SetReceiveDataFilter('.*"APICommand":"NOTING".*');
            $this->SendDebug('FILTER', 'NOTHING', 0);
        }

        $this->RegisterProfileInteger('Onkyo.NetTunerPreset', '', '', '', 1, 40, 1);
        $this->RegisterVariableInteger('NPR', $this->Translate('Internet Radio Presets'), 'Onkyo.NetTunerPreset', 0);
        $this->EnableAction('NPR');
        $this->RegisterProfileIntegerEx('Onkyo.Status', 'Information', '', '', [
            [0, 'Stop', '', -1],
            [1, 'Play', '', -1],
            [2, 'Pause', '', -1],
        ]);
        $this->RegisterProfileIntegerEx('Onkyo.Shuffle.' . $this->InstanceID, 'Shuffle', '', '', [
            [0, $this->Translate('off'), '', -1],
        ]);
        $this->RegisterProfileIntegerEx('Onkyo.Repeat.' . $this->InstanceID, 'Repeat', '', '', [
            [0, $this->Translate('off'), '', -1],
        ]);
        $this->RegisterVariableInteger('NST2', $this->Translate('Shuffle'), 'Onkyo.Shuffle.' . $this->InstanceID, 0);
        $this->EnableAction('NST2');
        $this->RegisterVariableInteger('NST1', $this->Translate('Repeat'), 'Onkyo.Repeat.' . $this->InstanceID, 0);
        $this->EnableAction('NST1');
        $this->RegisterVariableInteger('NST0', $this->Translate('State'), 'Onkyo.Status', 0);
        $this->EnableAction('NST0');

        $this->RegisterProfileIntegerEx('Onkyo.Tracks', '', '', '', [
            [-999, '<<', '', -1],
            [0, '%d', '', -1],
            [999, '>>', '', -1],
        ]);
        $this->RegisterProfileIntegerEx('Onkyo.Network', '', '', '', [
            [0, $this->Translate('no connection'), '', -1],
            [1, $this->Translate('Ethernet'), '', -1],
            [2, $this->Translate('Wireless'), '', -1],
        ]);
        $this->RegisterProfileIntegerEx('Onkyo.USB', '', '', '', [
            [0, $this->Translate('disable'), '', -1],
            [1, $this->Translate('no device'), '', -1],
            [2, $this->Translate('iPod/iPhone'), '', -1],
            [3, $this->Translate('Memory'), '', -1],
            [4, $this->Translate('Wireless Adaptor'), '', -1],
            [5, $this->Translate('Bluetooth Adaptor'), '', -1],
        ]);
        $this->RegisterVariableInteger('NTR0', $this->Translate('Current Track'), 'Onkyo.Tracks', 0);
        $this->RegisterVariableInteger('NTR1', $this->Translate('Number of Tracks'), '', 0);
        $this->EnableAction('NTR0');
        $this->RegisterVariableString('NTM0', $this->Translate('Position'), '', 0);
        $this->RegisterVariableString('NTM1', $this->Translate('Duration'), '', 0);
        $this->RegisterVariableInteger('NTM', $this->Translate('Position'), '~Intensity.100', 0);
        $this->RegisterVariableString('NAL', $this->Translate('Album'), '', 0);
        $this->RegisterVariableString('NTI', $this->Translate('Title'), '', 0);
        $this->RegisterVariableString('NAT', $this->Translate('Artist'), '', 0);

        $this->RegisterVariableInteger('NDS0', $this->Translate('Network'), 'Onkyo.Network', 0);
        $this->RegisterVariableInteger('NDS1', $this->Translate('Front USB'), 'Onkyo.USB', 0);
        $this->RegisterVariableInteger('NDS2', $this->Translate('Rear USB'), 'Onkyo.USB', 0);

        $this->RegisterProfileInteger('Onkyo.SelectNetworkService.' . $this->InstanceID, '', '', '', 0, 0, 0);
        $this->RegisterVariableInteger('NSV', $this->Translate('Network Service'), 'Onkyo.SelectNetworkService.' . $this->InstanceID, 0);
        $this->EnableAction('NSV');

        if ($this->ReadPropertyBoolean('showCover')) {
            $this->SetCover();
        } else {
            unset($APICommands[array_search(\OnkyoAVR\ISCP_API_Commands::NJA, $APICommands)]);
            $CoverID = @IPS_GetObjectIDByIdent('Cover', $this->InstanceID);
            if ($CoverID > 0) {
                @IPS_DeleteMedia($CoverID, true);
            }
        }
        if ($this->ReadPropertyBoolean('showNavigation')) {
            $this->RegisterVariableString('NLA', 'Navigation', '~HTMLBox', 20);
        } else {
            $this->UnregisterVariable('NLA');
        }
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        if ($this->ReadPropertyBoolean('showNavigation')) {
            $this->RegisterHook('/hook/OnkyoNetPlayer' . $this->InstanceID);
        } else {
            $this->UnregisterHook('/hook/OnkyoNetPlayer' . $this->InstanceID);
        }
        $this->RegisterParent();
        if ($this->HasActiveParent()) {
            $this->IOChangeState(IS_ACTIVE);
        }
    }

    /**
     * Interne Funktion des SDK.
     */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);

        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;
        }
    }

    public function Menu()
    {
        return $this->SendKey('TOP');
    }

    public function SelectNetworkService(int $ServiceIndex)
    {
        if ($ServiceIndex >= 0xf0) {
            switch ($ServiceIndex) {
                case 0xf0:
                    $Input = 0x29;
                    break;
                case 0xf1:
                    $Input = 0x2A;
                    break;
                case 0xf3:
                    $Input = 0x2b;
                    break;
                case 0xf4:
                    $Input = 0x2E;
                    break;
                default:
                    trigger_error($this->Translate('Unknown NetworkService'), E_USER_NOTICE);

                    return false;
            }
            $APIData = new \OnkyoAVR\ISCP_API_Data($this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::SLI), sprintf('%02X', $Input));
            $ResultData = $this->Send($APIData);
            if ($ResultData === null) {
                return false;
            }

            return true;
        }
        $Data = sprintf('%02X', $ServiceIndex) . '0';
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::NSV, $Data, false);
        $ret = $this->Send($APIData);
        if ($ret == null) {
            return false;
        }

        return true;
    }

    public function SelectInfoListItem(int $Index)
    {
        if ($this->ServiceType == 0xf3) {
            return $this->SelectNetworkService($Index);
        }
        if ($Index == -1) {
            return $this->SendKey('RETURN');
        }
        $Sequence = $this->Sequence;
        if ($Sequence == 0xffff) {
            $this->Sequence = 1;
        } else {
            $this->Sequence = $Sequence + 1;
        }

        $Data = 'I' . sprintf('%02X', $this->Layer) . sprintf('%04X', $Index) . '----';
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::NLA, $Data, false);
        $ret = $this->Send($APIData);
        if ($ret == null) {
            return false;
        }
        return true;
    }

    public function RequestInfoListData()
    {
        $Title = $this->Translate('empty');
        $List[] = [
            'ID'    => -1,
            'Type'  => -1,
            'Title' => '..',
        ];
        switch ($this->ServiceType) {
            case 0xff:
                break;
            case 0xf3:
                if ($this->UiType == 0) {
                    $List = [];
                    $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::NetserviceList);
                    $ret = $this->Send($APIData);
                    $this->SendDebug('NLA', $ret, 0);
                    foreach ($ret as $Key => $Item) {
                        $List[] = [
                            'ID'    => (int) $Key,
                            'Type'  => 0x2F,
                            'Title' => $Item,
                        ];
                    }

                    $Title = $this->FolderName;
                    break;
                }
                // No break ist Absicht. Warum, habe ich vergessen :D
                // FIXME: No break. Please add proper comment if intentional
            default:
                if ($this->UiType == 3) { //menü screen
                    $Title = $this->FolderName;
                    $this->SendDebug('NLA', $List, 0);
                    break;
                }
                if ($this->UiType == 2) { //playing screen
                    $Title = '';
                    $this->SendDebug('NLA', $List, 0);
                    break;
                }
                if ($this->UiType == 1) { //menü screen
                    $Title = $this->FolderName;
                    $this->SendDebug('NLA', $List, 0);
                    break;
                }
                if ($this->ListItems == 0) {
                    break;
                }
                $Sequence = $this->Sequence;
                if ($Sequence == 0xffff) {
                    $this->Sequence = 1;
                } else {
                    $this->Sequence = $Sequence + 1;
                }
                $Data = 'L' . sprintf('%04X', $Sequence) . sprintf('%02X', $this->Layer) . '0000' . sprintf('%04X', $this->ListItems);
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::NLA, $Data);
                $ret = $this->Send($APIData);
                if ($ret == null) {
                    $this->SendDebug('ERROR', 'NLA Data Timeout', 0);
                    $Title = $this->Translate('Timeout');
                    break;
                }
                if ($ret[0] != 'X') {
                    $this->SendDebug('ERROR', 'NLA Data invalid', 0);
                    $this->LogMessage('NLA Data invalid', KL_ERROR);
                    $Title = $this->Translate('Data invalid');
                    break;
                }
                if (hexdec(substr($ret, 1, 4)) != $Sequence) {
                    $this->SendDebug('ERROR', 'NLA Data wrong sequence', 0);
                    $this->LogMessage('NLA ' . $this->Translate('Data has wrong sequence number'), KL_ERROR);
                    $Title = $this->Translate('Data has wrong sequence number');
                    break;
                }

                try {
                    $xml = new SimpleXMLElement(substr($ret, 9), LIBXML_NOBLANKS + LIBXML_NONET + LIBXML_NOERROR);
                } catch (Exception $ex) {
                    $this->SendDebug('ERROR', 'NLA Data is invalid XML', 0);
                    $this->LogMessage('NLA ' . $this->Translate('Data is invalid XML'), KL_ERROR);
                    $Title = $this->Translate('Data is invalid XML');
                    break;
                }
                if ($ret[5] == 'E') {
                    $error = $xml->xpath('//error');
                    if (count($error) == 1) {
                        $this->SendDebug('ERROR ' . (string) $error[0]['code'], (string) $error[0]['message'], 0);
                        trigger_error((string) $error[0]['message'], E_USER_NOTICE);
                        $Title = (string) $error[0]['message'];
                        break;
                    }
                }
                foreach ($xml->xpath('//item') as $Key => $Item) {
                    $List[] = [
                        'ID'    => (int) $Key,
                        'Type'  => hexdec($Item['iconid']),
                        'Title' => (string) $Item['title'],
                    ];
                }
                $Title = $this->FolderName;
                $this->SendDebug('NLA', $List, 0);
                break;
        }

        return ['Title' => $Title, 'List' => $List];
    }

    public function SendKey(string $Key)
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data($this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::NTC), $Key, false);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            return false;
        }

        return true;
    }

    //################# ActionHandler

    public function RequestAction($Ident, $Value)
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return true;
        }
        switch ($Ident) {
            /* case 'SLI':
              return $this->SelectInput($Value); */
            case 'NPR':
                $this->SetValue('NPR', $Value);

                return $this->CallPreset($Value);
            case 'NTM':
                $Total = $this->StringToSeconds($this->GetValue('NTM1'));
                $Time = ($Total / 100) * (int) $Value;

                return $this->SetPosition(intval($Time));
            case 'NTC':
                return $this->SendKey($Value);
            case 'NLA':
                $this->SendDebug('RequestAction', 'NLA', 0);
                if ($this->ReadPropertyBoolean('showNavigation')) {
                    $Result = $this->RequestInfoListData();
                    $this->RefreshNavigationTable($Result);
                }

                return true;
            case 'NST0':
                switch ($Value) {
                    case 0:
                        return $this->Stop();
                    case 1:
                        if ($this->GetValue('NST0') == 1) {
                            return true;
                        }

                        return $this->Pause();
                    case 2:
                        if ($this->GetValue('NST0') == 2) {
                            return true;
                        }

                        return $this->Pause();
                }
                echo 'Invalid Value';

                return false;
            case 'NST1':
                return $this->Repeat();
            case 'NST2':
                return $this->Shuffle();
            case 'NTR0':
                switch ($Value) {
                    case -999:
                        return $this->PreviousTrack();
                    case 999:
                        return $this->NextTrack();
                }

                return;
            case 'NSV':
                return $this->SelectNetworkService($Value);
        }
        echo $this->Translate('Invalid Ident');
    }

    //################# PUBLIC

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     */
    public function RequestState(string $Ident)
    {
        if ($Ident == 'ALL') {
            return $this->RequestZoneState();
        }
        $ApiCmd = substr($Ident, 0, 3);
        if (!in_array($Ident, \OnkyoAVR\ONKYO_Zone_NetPlayer::$ReadAPICommands)) {
            trigger_error($this->Translate('Invalid ident'), E_USER_NOTICE);

            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data($ApiCmd, \OnkyoAVR\ISCP_API_Commands::Request);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            return false;
        }
        $APIData->Data = $ResultData;
        $this->UpdateVariable($APIData);

        return true;
    }

    public function PreviousTrack()
    {
        if ($this->GetValue('NST0') != 0) {
            $this->Stop();
            sleep(3);
        }

        return $this->SendKey('TRDN');
    }

    public function NextTrack()
    {
        return $this->SendKey('TRUP');
    }

    public function Play()
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    public function Pause()
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    public function Stop()
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    public function Shuffle()
    {
        return $this->SendKey('RANDOM');
    }

    public function Repeat()
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    public function SetPosition(int $Value)
    {
        if ($Value > $this->StringToSeconds($this->GetValue('NTM1'))) {
            trigger_error($this->Translate('Value greater as duration'), E_USER_NOTICE);

            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::NTS, $this->SecondsToString($Value), false);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            return false;
        }

        return true;
    }

    public function CallPreset(int $Value)
    {
        if (($Value < 1) || ($Value > 40)) {
            trigger_error(sprintf($this->Translate('%s out of range.'), 'Value'), E_USER_NOTICE);

            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
            $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::NPR),
            sprintf('%02X', $Value),
            false
        );

        return $this->Send($APIData);
    }

    public function SavePreset()
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data(
            $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::NPR),
            'SET',
            false
        );

        return $this->Send($APIData);
    }

    public function ReceiveData($JSONString)
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data($JSONString);
        $this->SendDebug('ReceiveData', $APIData, 0);
        $this->UpdateVariable($APIData);
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->ReadPropertyBoolean('showNavigation')) {
            $id = IPS_GetInstanceListByModuleID('{B69010EA-96D5-46DF-B885-24821B8C8DBD}')[0];
            $Icons[] = [
                'caption' => $this->Translate('none'),
                'value'   => 'Transparent',
            ];
            foreach (UC_GetIconList($id) as $Icon) {
                $Icons[] = [
                    'caption' => $Icon,
                    'value'   => $Icon,
                ];
            }
            $Form['elements'][3]['items'][3]['columns'][2]['edit']['options'] = $Icons;
        } else {
            unset($Form['elements'][3]);
        }
        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return json_encode($Form);
    }

    /**
     * Wird ausgeführt wenn der Kernel hochgefahren wurde.
     */
    protected function KernelReady()
    {
        $this->RegisterParent();
    }

    /**
     * Wird ausgeführt wenn sich der Status vom Parent ändert.
     */
    protected function IOChangeState($State)
    {
        if ($State == IS_ACTIVE) {
            if ($this->HasActiveParent()) {
                $this->RequestProfile();
                $this->RequestZoneState();
            }
        }
    }

    /**
     * Verarbeitet Daten aus dem Webhook.
     *
     * @global array $_GET
     */
    protected function ProcessHookdata()
    {
        if ((!isset($_GET['Type'])) || (!isset($_GET['Secret']))) {
            echo $this->Translate('Bad Request');

            return;
        }

        $CalcSecret = base64_encode(sha1($this->WebHookSecret . '0' . (string) $_GET['ID'], true));
        if ($CalcSecret != rawurldecode($_GET['Secret'])) {
            echo $this->Translate('Access denied');
            return;
        }
        if ($_GET['Type'] != 'Index') {
            echo $this->Translate('Bad Request');

            return;
        }

        if ($this->SelectInfoListItem((int) $_GET['ID'])) {
            echo 'OK';
        }
    }

    /**
     * Liefert den Header der HTML-Tabelle.
     *
     * @param array $Config Die Konfiguration der Tabelle
     *
     * @return string HTML-String
     */
    protected function GetTableHeader($Config_Table, $Config_Columns)
    {
        $table = '';
        // Kopf der Tabelle erzeugen
        $table .= '<table style="' . $Config_Table['<table>'] . '">' . PHP_EOL;
        // JS Rückkanal erzeugen
        $table .= '<script type="text/javascript" id="script' . $this->InstanceID . '">
function xhrGet' . $this->InstanceID . '(o)
{
    var HTTP = new XMLHttpRequest();
    HTTP.open(\'GET\',o.url,true);
    HTTP.send();
    HTTP.addEventListener(\'load\', function()
    {
        if (HTTP.status >= 200 && HTTP.status < 300)
        {
            if (HTTP.responseText !== \'OK\')
                sendError' . $this->InstanceID . '(HTTP.responseText);
        } else {
            sendError' . $this->InstanceID . '(HTTP.statusText);
        }
    });
}

function sendError' . $this->InstanceID . '(data)
{
var notify = document.getElementsByClassName("ipsNotifications")[0];
var newDiv = document.createElement("div");
newDiv.innerHTML =\'<div style="height:auto; visibility: hidden; overflow: hidden; transition: height 500ms ease-in 0s" class="ipsNotification"><div class="spacer"></div><div class="message icon error" onclick="document.getElementsByClassName(\\\'ipsNotifications\\\')[0].removeChild(this.parentNode);"><div class="ipsIconClose"></div><div class="content"><div class="title">Fehler</div><div class="text">\' + data + \'</div></div></div></div>\';
if (notify.childElementCount === 0)
	var thisDiv = notify.appendChild(newDiv.firstChild);
else
	var thisDiv = notify.insertBefore(newDiv.firstChild,notify.childNodes[0]);
var newheight = window.getComputedStyle(thisDiv, null)["height"];
thisDiv.style.height = "0px";
thisDiv.style.visibility = "visible";
function sleep (time) {
  return new Promise((resolve) => setTimeout(resolve, time));
}
sleep(10).then(() => {
	thisDiv.style.height = newheight;
});
}

</script>';
        $table .= '<colgroup>' . PHP_EOL;
        $colgroup = [];
        foreach ($Config_Columns as $Column) {
            if ($Column['show'] !== true) {
                continue;
            }
            $colgroup[$Column['index']] = '<col width="' . $Column['width'] . 'em" />' . PHP_EOL;
        }
        ksort($colgroup);
        $table .= implode('', $colgroup) . '</colgroup>' . PHP_EOL;
        $table .= '<thead style="' . $Config_Table['<thead>'] . '">' . PHP_EOL;
        $table .= '<tr>';
        $th = [];
        foreach ($Config_Columns as $Column) {
            if ($Column['show'] !== true) {
                continue;
            }
            $ThStyle = [];
            if ($Column['color'] >= 0) {
                $ThStyle[] = 'color:#' . substr('000000' . dechex($Column['color']), -6);
            }
            $ThStyle[] = 'text-align:' . $Column['align'];
            $ThStyle[] = $Column['style'];
            $th[$Column['index']] = '<th style="' . implode(';', $ThStyle) . ';">' . $Column['name'] . '</th>';
        }
        ksort($th);
        $table .= implode('', $th) . '</tr>' . PHP_EOL;
        $table .= '</thead>' . PHP_EOL;
        $table .= '<tbody style="' . $Config_Table['<tbody>'] . '">' . PHP_EOL;

        return $table;
    }

    /**
     * Liefert den Inhalt der HTML-Box für ein Tabelle.
     *
     * @param array  $Data        Die Nutzdaten der Tabelle.
     * @param string $HookPrefix  Der Prefix des Webhook.
     * @param string $HookType    Ein String welcher als Parameter Type im Webhook übergeben wird.
     * @param string $HookId      Der Index aus dem Array $Data welcher die Nutzdaten (Parameter ID) des Webhook enthält.
     * @param int    $CurrentLine Die Aktuelle Zeile welche als Aktiv erzeugt werden soll.
     *
     * @return string Der HTML-String.
     */
    protected function GetTable($Array_Data, $HookPrefix, $HookType, $HookId)
    {
        $Data = $Array_Data['List'];
        $Config_Table = array_column(json_decode($this->ReadPropertyString('Table'), true), 'style', 'tag');
        $Config_Columns = json_decode($this->ReadPropertyString('Columns'), true);
        $Config_Rows = json_decode($this->ReadPropertyString('Rows'), true);
        $Config_Rows_BgColor = array_column($Config_Rows, 'bgcolor', 'row');
        $Config_Rows_Color = array_column($Config_Rows, 'color', 'row');
        $Config_Rows_Style = array_column($Config_Rows, 'style', 'row');
        $Config_Icons = json_decode($this->ReadPropertyString('Icons'), true);
        $Config_Icon = array_column($Config_Icons, 'icon', 'typ');
        $NewSecret = base64_encode(openssl_random_pseudo_bytes(12));
        $this->WebHookSecret = $NewSecret;

        $HTMLData = $this->GetTableHeader($Config_Table, $Config_Columns);
        $HTMLData .= '<caption style="' . $Config_Table['<caption>'] . '">' . $Array_Data['Title'] . '</caption>';

        $pos = 0;
        if (count($Data) > 0) {
            foreach ($Data as $Line) {
                $Line['Position'] = $pos;
                if (array_key_exists($Line['Type'], $Config_Icon)) {
                    $Line['Icon'] = '<div class="iconMediumSpinner ipsIcon' . $Config_Icon[$Line['Type']] . '" style="width: 100%; background-position: center center;"></div>';
                } else {
                    $Line['Icon'] = '<div class="iconMediumSpinner ipsIconTransparent" style="width: 100%; background-position: center center;"></div>';
                }
                $LineSecret = base64_encode(sha1($NewSecret . '0' . (string) $Line[$HookId], true));
                $LineIndex = ($Line['Type'] == 0x36 ? 'active' : ($pos % 2 ? 'odd' : 'even'));
                $TrStyle = [];
                if ($Config_Rows_BgColor[$LineIndex] >= 0) {
                    $TrStyle[] = 'background-color:#' . substr('000000' . dechex($Config_Rows_BgColor[$LineIndex]), -6);
                }
                if ($Config_Rows_Color[$LineIndex] >= 0) {
                    $TrStyle[] = 'color:#' . substr('000000' . dechex($Config_Rows_Color[$LineIndex]), -6);
                }
                $TdStyle[] = $Config_Rows_Style[$LineIndex];
                $HTMLData .= '<tr style="' . implode(';', $TrStyle) . ';" onclick="eval(document.getElementById(\'script' . $this->InstanceID . '\').innerHTML.toString()); window.xhrGet' . $this->InstanceID . '({ url: \'hook/' . $HookPrefix . $this->InstanceID . '?Type=' . $HookType . '&ID=' . ($HookId == 'Url' ? rawurlencode($Line[$HookId]) : $Line[$HookId]) . '&Secret=' . rawurlencode($LineSecret) . '\' });">';

                $td = [];
                foreach ($Config_Columns as $Column) {
                    if ($Column['show'] !== true) {
                        continue;
                    }
                    if (!array_key_exists($Column['key'], $Line)) {
                        $Line[$Column['key']] = '';
                    }
                    $TdStyle = [];
                    $TdStyle[] = 'text-align:' . $Column['align'];
                    $TdStyle[] = $Column['style'];

                    $td[$Column['index']] = '<td style="' . implode(';', $TdStyle) . ';">' . (string) $Line[$Column['key']] . '</td>';
                }
                ksort($td);
                $HTMLData .= implode('', $td) . '</tr>';
                $HTMLData .= '</tr>' . PHP_EOL;
                $pos++;
            }
        }
        $HTMLData .= $this->GetTableFooter();

        return $HTMLData;
    }

    /**
     * Liefert den Footer der HTML-Tabelle.
     *
     * @return string HTML-String
     */
    protected function GetTableFooter()
    {
        $table = '</tbody>' . PHP_EOL;
        $table .= '</table>' . PHP_EOL;

        return $table;
    }

    //################# PRIVATE
    private function SetCover()
    {
        $this->SendDebug('Refresh Cover', '', 0);
        $CoverID = @IPS_GetObjectIDByIdent('Cover', $this->InstanceID);
        if ($CoverID === false) {
            $CoverID = IPS_CreateMedia(1);
            IPS_SetParent($CoverID, $this->InstanceID);
            IPS_SetIdent($CoverID, 'Cover');
            IPS_SetName($CoverID, 'Cover');
            IPS_SetPosition($CoverID, 27);
            IPS_SetMediaCached($CoverID, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'Cover_' . $this->InstanceID . '.onkyo';
            IPS_SetMediaFile($CoverID, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        $CoverRAW = $this->Multi_Cover;

        if ($this->GetValue('NST0') == 0) {
            $CoverRAW = '';
        }
        if ($CoverRAW === '') {
            $CoverRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'nocover.png');
        }
        IPS_SetMediaContent($CoverID, base64_encode($CoverRAW));
    }

    private function RefreshNavigationTable(array $List)
    {
        $HTML = $this->GetTable($List, 'OnkyoNetPlayer', 'Index', 'ID');
        $this->SetValueString('NLA', $HTML);
    }

    private function ProcessPopUpInfo(string $NPUData)
    {
        if ($NPUData[0] == 'L') { //list not supported
            return;
        }
        if (!$this->ReadPropertyBoolean('showNavigation')) {
            return;
        }
        $NPU = explode(chr(0), substr($NPUData, 1));
        $Cursor = (int) $NPU[3][0];
        $NPU[3] = substr($NPU[3], 1);
        $this->SendDebug('NPU', $NPU, 0);
        $this->SendDebug('NPU Cursor', $Cursor, 0);
        $Title = array_shift($NPU);
        $List = [];
        foreach ($NPU as $Item) {
            if ($Item == '') {
                continue;
            }
            $List[] = [
                'ID'    => -1,
                'Type'  => -1,
                'Title' => $Item,
            ];
        }
        $this->RefreshNavigationTable(['Title' => $Title, 'List' => $List]);
    }

    private function ProcessListTitelInfo(string $NLTData)
    {
        $FolderName = substr($NLTData, 22);
        $ServiceType = hexdec(substr($NLTData, 0, 2));
        $UiType = hexdec($NLTData[2]);
        $ListItems = hexdec(substr($NLTData, 8, 4));
        $Layer = hexdec(substr($NLTData, 12, 2));
        $this->SendDebug('Folder', $FolderName, 0);
        $this->SendDebug('Layer', $Layer, 0);
        $this->SendDebug('Layer old', $this->Layer, 0);
        $this->SendDebug('UiType', $UiType, 0);
        $this->SendDebug('ListItems', $ListItems, 0);
        $this->SendDebug('ServiceType', $ServiceType, 0);
        $this->SendDebug('ServiceType old', $this->ServiceType, 0);
        if (($this->ServiceType == $ServiceType) && ($this->UiType == $UiType) && ($this->Layer == $Layer)) {
            return;
        }
        $this->ServiceType = $ServiceType;

        $this->UiType = $UiType;
        $this->Layer = $Layer;
        $this->ListItems = $ListItems;

        if ($ServiceType == 0xf3) {
            $this->FolderName = 'Network';
        } else {
            $this->FolderName = $FolderName;
        }
        $this->SetValue('NSV', $ServiceType);

        IPS_RunScriptText('IPS_RequestAction(' . $this->InstanceID . ',\'NLA\',0);');

        //      }
    }

    private function UpdateVariable(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        switch ($APIData->APICommand) {
            /*            case \OnkyoAVR\ISCP_API_Commands::SLI:
              case \OnkyoAVR\ISCP_API_Commands::SLZ:
              case \OnkyoAVR\ISCP_API_Commands::SL3:
              case \OnkyoAVR\ISCP_API_Commands::SL4:
              $this->SetValue('SLI', hexdec($APIData->Data));
              break;
             */
            case \OnkyoAVR\ISCP_API_Commands::NMS:

                if ($APIData->Data[5] == 'S') {
                    $this->EnableAction('NTM');
                } else {
                    $this->DisableAction('NTM');
                }
                break;
            case \OnkyoAVR\ISCP_API_Commands::NDS:
                switch ((string) $APIData->Data[0]) {
                    case 'E':
                        $StatusValue = 1;
                        break;
                    case 'W':
                        $StatusValue = 2;
                        break;
                    default:
                        $StatusValue = 0;
                        break;
                }
                $this->SetValue('NDS0', $StatusValue);

                switch ((string) $APIData->Data[1]) {
                    case 'x':
                        $StatusValue = 0;
                        break;
                    case 'i':
                        $StatusValue = 2;
                        break;
                    case 'M':
                        $StatusValue = 3;
                        break;
                    case 'W':
                        $StatusValue = 4;
                        break;
                    case 'B':
                        $StatusValue = 5;
                        break;
                    default:
                        $StatusValue = 1;
                        break;
                }
                $this->SetValue('NDS1', $StatusValue);

                switch ((string) $APIData->Data[2]) {
                    case 'x':
                        $StatusValue = 0;
                        break;
                    case 'i':
                        $StatusValue = 2;
                        break;
                    case 'M':
                        $StatusValue = 3;
                        break;
                    case 'W':
                        $StatusValue = 4;
                        break;
                    case 'B':
                        $StatusValue = 5;
                        break;
                    default:
                        $StatusValue = 1;
                        break;
                }
                $this->SetValue('NDS2', $StatusValue);

                break;
            case \OnkyoAVR\ISCP_API_Commands::NPU:
                $this->ProcessPopUpInfo($APIData->Data);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NLT:
                $this->ProcessListTitelInfo($APIData->Data);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NTR:
                $Data = explode('/', (string) $APIData->Data);
                $this->SetValue('NTR0', (int) $Data[0]);
                $this->SetValue('NTR1', (int) $Data[1]);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NST:
                switch ((string) $APIData->Data[0]) {
                    case 'S':
                    case 'E':
                        $StatusValue = 0;
                        $this->Multi_Cover = '';
                        $this->SetCover();
                        $this->SetValue('NAL', '');
                        $this->SetValue('NTI', '');
                        $this->SetValue('NAT', '');
                        $this->SetValue('NTM0', '--:--');
                        $this->SetValue('NTM1', '--:--');
                        $this->SetValue('NTM', 0);
                        $this->DisableAction('NTM');
                        break;
                    case 'P':
                        $StatusValue = 1;
                        break;
                    case 'p':
                        $StatusValue = 2;
                        break;
                    default:
                        $StatusValue = $this->GetValue('NST0');
                        break;
                }
                $this->SetValue('NST0', $StatusValue);
                switch ((string) $APIData->Data[1]) {
                    case 'x':
                        $RepeatValue = 'off';
                        $this->DisableAction('NST1');
                        break;
                    case '-':
                        $RepeatValue = 'off';
                        $this->EnableAction('NST1');
                        break;
                    case 'R':
                        $RepeatValue = 'All';
                        $this->EnableAction('NST1');
                        break;
                    case 'F':
                        $RepeatValue = 'Folder';
                        $this->EnableAction('NST1');
                        break;
                    case '1':
                        $RepeatValue = 'Title';
                        $this->EnableAction('NST1');
                        break;
                }

                $this->RegisterProfileIntegerEx('Onkyo.Repeat.' . $this->InstanceID, 'Repeat', '', '', [
                    [0, $this->Translate($RepeatValue), '', -1],
                ]);

                switch ((string) $APIData->Data[2]) {
                    case 'x':
                        $ShuffleValue = 'off';
                        $this->DisableAction('NST2');
                        break;
                    case '-':
                        $ShuffleValue = 'off';
                        $this->EnableAction('NST2');
                        break;
                    case 'S':
                        $ShuffleValue = 'All';
                        $this->EnableAction('NST2');
                        break;
                    case 'A':
                        $ShuffleValue = 'Album';
                        $this->EnableAction('NST2');
                        break;
                    case 'F':
                        $ShuffleValue = 'Folder';
                        $this->EnableAction('NST2');
                        break;
                }
                $this->RegisterProfileIntegerEx('Onkyo.Shuffle.' . $this->InstanceID, 'Shuffle', '', '', [
                    [0, $this->Translate($ShuffleValue), '', -1],
                ]);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NJA:
                if (!$this->ReadPropertyBoolean('showCover')) {
                    break;
                }
                switch ((string) $APIData->Data[0]) {
                    case '0':
                    case '1':
                        if ($APIData->Data[1] == '0') {
                            $this->Multi_Cover = '';
                        }
                        $this->Multi_Cover .= hex2bin(substr($APIData->Data, 2));
                        if ($APIData->Data[1] == '2') {
                            $this->SetCover();
                        }
                        break;
                    case '2': // URL
                        break;
                    case 'n': // no image
                        break;
                }
                break;
            case \OnkyoAVR\ISCP_API_Commands::NTM:
                $Data = explode('/', (string) $APIData->Data);
                $this->SetValue('NTM0', $Data[0]);
                $this->SetValue('NTM1', $Data[1]);
                if ($Data[1] != '--:--') {
                    $NTM0 = $this->StringToSeconds($Data[0]);
                    $NTM1 = $this->StringToSeconds($Data[1]);
                    $Total = (100 / $NTM1) * $NTM0;
                    $this->SetValueInteger('NTM', $Total);
                }
                break;
            case \OnkyoAVR\ISCP_API_Commands::NAL:
            case \OnkyoAVR\ISCP_API_Commands::NTI:
            case \OnkyoAVR\ISCP_API_Commands::NAT:
                $this->SetValue((string)$APIData->APICommand, (string) $APIData->Data);
                break;
        }
    }

    //################# Datapoints
    private function RequestProfile()
    {
        $AssociationNSV = [];
        $zone = $this->OnkyoZone->thisZone;
        // SLI // SLZ // SL3 // SL4
        $APIDataSelectorList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::SelectorList);
        $ResultDataSelectorList = $this->Send($APIDataSelectorList);
        if (count($ResultDataSelectorList) > 0) {
            foreach ($ResultDataSelectorList as $Value => $SelectorProfileData) {
                if (($Value < 0x29) || ($Value > 0x2E)) {
                    continue;
                }
                if (((int) $SelectorProfileData['Zone'] & $zone) == $zone) {
                    switch ($Value) {
                        case 0x29:
                            $AssociationNSV[] = [0xF0, $SelectorProfileData['Name'], '', -1];
                            break;
                        case 0x2A:
                            $AssociationNSV[] = [0xF1, $SelectorProfileData['Name'], '', -1];
                            break;
                        case 0x2b:
                            $AssociationNSV[] = [0xF3, $SelectorProfileData['Name'], '', -1];
                            break;
                        case 0x2e:
                            $AssociationNSV[] = [0xF4, $SelectorProfileData['Name'], '', -1];
                            break;
                    }
                }
            }
        } else {
            /* $AssociationSLI = [
              [0x29, 'USB', '', -1],
              [0x2B, 'Network', '', -1],
              [0x2E, 'Bluetooth', '', -1]
              ]; */
            $AssociationNSV = [
                [0xF0, 'USB', '', -1],
                [0xF3, 'Network', '', -1],
                [0xF4, 'Bluetooth', '', -1],
            ];
        }
        /*      foreach ($AssociationSLI as &$AssociationItem) {
          $AssociationItem[1] = $this->Translate($AssociationItem[1]);
          }
          $this->RegisterProfileIntegerEx('Onkyo.SelectNetworkInput.' . $this->InstanceID, '', '', '', $AssociationSLI);
         */
        // bestimmte Werte als $ResultDataNetserviceList hinzufügen für USB / USB rear / NET / Bluetooth
        $APIDataNetserviceList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::NetserviceList);
        $ResultDataNetserviceList = $this->Send($APIDataNetserviceList);
        if (count($ResultDataNetserviceList) > 0) {
            foreach ($ResultDataNetserviceList as $Value => $NetserviceProfileData) {
                $AssociationNSV[] = [$Value, $NetserviceProfileData, '', -1];
            }
            /* $Association[] = [0xF1, 'USB', '', -1];
              $Association[] = [0xF3, 'Network', '', -1]; */
        } else {
            $AssociationNSV = [
                [0x00, 'DLNA', '', -1],
                [0x01, 'My Favorites', '', -1],
                [0x02, 'vTuner', '', -1],
                [0x03, 'SiriusXM', '', -1],
                [0x04, 'Pandora', '', -1],
                [0x05, 'Rhapsody', '', -1],
                [0x06, 'Last.fm', '', -1],
                [0x07, 'Napster', '', -1],
                [0x08, 'Slacker', '', -1],
                [0x09, 'Mediafly', '', -1],
                [0x0A, 'Spotify', '', -1],
                [0x0B, 'AUPEO!', '', -1],
                [0x0C, 'Radiko', '', -1],
                [0x0D, 'e-onkyo', '', -1],
                [0x0E, 'TuneIn Radio', '', -1],
                [0x0F, 'mp3tunes', '', -1],
                [0x10, 'Simfy', '', -1],
                [0x11, 'Home Media', '', -1],
                [0x12, 'Deezer', '', -1],
                [0x13, 'iHeartRadio', '', -1],
                [0x18, 'Airplay', '', -1],
                [0x1A, 'onkyo music', '', -1],
                [0x1B, 'TIDAL', '', -1],
                [0x41, 'FireConnect', '', -1],
                /* [0xF0, 'USB/USB(Front)', '', -1],
                      [0xF1, 'USB(Rear)', '', -1],
                      [0xF3, 'Network', '', -1],
                      [0xF4, 'Bluetooth', '', -1] */
            ];
        }
        $this->RegisterProfileIntegerEx('Onkyo.SelectNetworkService.' . $this->InstanceID, '', '', '', $AssociationNSV);
    }

    //------------------------------------------------------------------------------
    private function RequestZoneState()
    {
        $ApiCmds = \OnkyoAVR\ONKYO_Zone_NetPlayer::$ReadAPICommands;
        foreach ($ApiCmds as $ApiCmd) {
            $APIData = new \OnkyoAVR\ISCP_API_Data($ApiCmd, \OnkyoAVR\ISCP_API_Commands::Request);
            $ResultData = @$this->Send($APIData);
            if ($ResultData === null) {
                $this->SetValue('NAL', '');
                $this->SetValue('NTI', '');
                $this->SetValue('NAT', '');
                break;
            }
            if ($ResultData === '----/----') {
                $this->SetValue('NAL', '');
                $this->SetValue('NTI', '');
                $this->SetValue('NAT', '');
                break;
            }
            $APIData->Data = $ResultData;
            $this->UpdateVariable($APIData);
        }
    }

    private function Send(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        $this->SendDebug('ForwardData', $APIData, 0);

        try {
            if (!$this->HasActiveParent()) {
                throw new Exception($this->Translate('Instance has no active parent.'), E_USER_NOTICE);
            }
            $ret = $this->SendDataToParent($APIData->ToJSONString('{8F47273A-0B69-489E-AF36-F391AE5FBEC0}'));
            if ($ret === false) {
                $this->SendDebug('Response', 'No answer', 0);

                return null;
            }
            $result = unserialize($ret);
            $this->SendDebug('Response ' . $APIData->APICommand, $result, 0);

            return $result;
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            trigger_error($exc->getMessage(), E_USER_NOTICE);

            return null;
        }
    }

    private function GenerateHTMLStyleProperty()
    {
        $NewTableConfig = [
            [
                'tag'   => '<table>',
                'style' => 'margin:0 auto; font-size:0.8em;',
            ],
            [
                'tag'   => '<caption>',
                'style' => 'margin:0 auto; font-size:2em; font-weight:bold;',
            ],
            [
                'tag'   => '<thead>',
                'style' => '',
            ],
            [
                'tag'   => '<tbody>',
                'style' => '',
            ],
        ];
        $NewColumnsConfig = [
            [
                'index' => 0,
                'key'   => 'Icon',
                'name'  => '',
                'show'  => true,
                'width' => 50,
                'color' => 0xffffff,
                'align' => 'center',
                'style' => '',
            ],
            [
                'index' => 1,
                'key'   => 'Position',
                'name'  => 'Pos',
                'show'  => true,
                'width' => 50,
                'color' => 0xffffff,
                'align' => 'center',
                'style' => '',
            ],
            [
                'index' => 2,
                'key'   => 'Title',
                'name'  => $this->Translate('Title'),
                'show'  => true,
                'width' => 250,
                'color' => 0xffffff,
                'align' => 'center',
                'style' => '',
            ],
        ];
        $NewRowsConfig = [
            [
                'row'     => 'odd',
                'name'    => $this->Translate('odd'),
                'bgcolor' => 0x000000,
                'color'   => 0xffffff,
                'style'   => '',
            ],
            [
                'row'     => 'even',
                'name'    => $this->Translate('even'),
                'bgcolor' => 0x080808,
                'color'   => 0xffffff,
                'style'   => '',
            ],
            [
                'row'     => 'active',
                'name'    => $this->Translate('active'),
                'bgcolor' => 0x808000,
                'color'   => 0xffffff,
                'style'   => '',
            ],
        ];
        $NewIconsConfig = [
            [
                'typ'  => -1,
                'name' => $this->Translate('back'),
                'icon' => 'Backspace',
            ], [
                'typ'  => 0x00,
                'name' => $this->Translate('unknown'),
                'icon' => 'Transparent',
            ], [
                'typ'  => 0x29, // +0x44
                'name' => $this->Translate('Folder'),
                'icon' => 'Database',
            ], [
                'typ'  => 0x31,
                'name' => $this->Translate('USB'),
                'icon' => 'Mobile',
            ], [
                'typ'  => 0x2B,
                'name' => $this->Translate('Server'),
                'icon' => 'Notebook',
            ], [
                'typ'  => 0x2D,
                'name' => $this->Translate('Track'),
                'icon' => 'Melody',
            ], [
                'typ'  => 0x2F,
                'name' => $this->Translate('Stream'),
                'icon' => 'Melody',
            ], [
                'typ'  => 0x36, //+0x41
                'name' => $this->Translate('Playing'),
                'icon' => 'Speaker',
            ], [
                'typ'  => 0x39,
                'name' => $this->Translate('Album'),
                'icon' => 'Database',
            ], [
                'typ'  => 0x3A,
                'name' => $this->Translate('Playlist'),
                'icon' => 'Melody',
            ],
        ];

        return ['Table' => $NewTableConfig, 'Columns' => $NewColumnsConfig, 'Rows' => $NewRowsConfig, 'Icons' => $NewIconsConfig];
    }

    private function SecondsToString($Time)
    {
        if ($Time > 3600) {
            return sprintf('%02d:%02d:%02d', ($Time / 3600), ($Time / 60 % 60), $Time % 60);
        } else {
            return sprintf('%02d:%02d', ($Time / 60 % 60), $Time % 60);
        }
    }

    private function StringToSeconds($Value)
    {
        $Parts = explode(':', $Value);
        $Seconds = array_pop($Parts);
        foreach ($Parts as $Part) {
            $Seconds += $Part * 60;
        }

        return $Seconds;
    }
}
