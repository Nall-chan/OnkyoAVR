<?php

declare(strict_types=1);
/**
 * @author        Michael Tröger <nall4chan@gmail.com>
 * @copyright     2020-2026 Michael Tröger
 * @license       Custom Non-Commercial Share-Alike License (see LICENSE)
 *
 * @version       3.00
 */
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';  // diverse Klassen
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoNetplayer {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property int $ParentID Die InstanzID des IO-Parent
 * @property \OnkyoAVR\ONKYO_Zone_NetPlayer $OnkyoZone
 * @property string $Multi_Cover
 * @property int $Layer
 * @property int $Sequence
 * @property int $ServiceType
 * @property int $UiType
 * @property int $ListItems
 * @property string $FolderName
 * @property string $WebHookSecret
 * @property array $PlaylistIds Index des Eintrags in der Playlist => ID des Eintrags in der Liste des Gerätes
 * @property int $PlaylistCurrent Index des zuletzt gewählten Eintrags der Playlist
 * @property int $PlaylistLayer Ebene des Gerätes, aus der die Playlist stammt (-1 = unbekannt)
 * @property int $ListGeneration Zähler, der bei jeder neuen Liste des Gerätes (NLT) erhöht wird. Bricht das Laden einer veralteten Liste ab.
 * @property bool $SeekSupported Das Gerät erlaubt das Springen in der Wiedergabe (Position setzen)
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void SetValueString(string $Ident, string $value)
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 * @method void UnregisterProfile(string $Name)
 * @method bool RegisterHook(string $WebHook)
 * @method bool UnregisterHook(string $WebHook)
 * @method int FindIDForIdent(string $Ident)
 * @method int RegisterParent()
 * @method bool IORequestAction(string $Ident, mixed $Value)
 * @method void IOMessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data)
 */
class OnkyoNetplayer extends IPSModuleStrict
{
    use \OnkyoAVR\ActionErrorHandler;
    use \OnkyoNetplayer\DebugHelper,
        \OnkyoNetplayer\BufferHelper,
        \OnkyoNetplayer\InstanceStatus,
        \OnkyoNetplayer\VariableHelper,
        \OnkyoNetplayer\VariableProfileHelper{
            \OnkyoNetplayer\InstanceStatus::MessageSink as IOMessageSink;
            \OnkyoNetplayer\InstanceStatus::RequestAction as IORequestAction;
        }
    /**
     * Ident der Playlist-Variable (Liste des Gerätes für die Mediaplayer-Kachel)
     */
    private const PlaylistIdent = 'Playlist';

    /**
     * Verzögerung in ms, nach der bei andauerndem Stop Cover, Titel und Position zurückgesetzt werden
     */
    private const ClearOnStopDelay = 3000;

    /**
     * Anzahl der Einträge, die pro Anfrage von der Liste des Gerätes gelesen werden. Das Gerät braucht für große Listen sehr lange (Alben: 1892 Einträge ca. 34 s)
     */
    private const ListPageSize = 100;

    /**
     * ID des Eintrags 'Bitte warten' in der Playlist (keine Auswahl am Gerät)
     */
    private const PlaylistWaitId = -2;

    /**
     * Create
     *
     * @return void
     */
    public function Create(): void
    {
        parent::Create();
        $this->RegisterPropertyInteger('Zone', \OnkyoAVR\ONKYO_Zone_NetPlayer::ZoneMain);
        $this->RegisterPropertyBoolean('showCover', true);
        $this->RegisterPropertyBoolean('showNavigation', true);
        $this->RegisterPropertyBoolean('showPlaylist', false);
        $this->RegisterTimer('ClearOnStop', 0, 'IPS_RequestAction($_IPS[\'TARGET\'], \'ClearOnStop\', 0);');
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
        $this->PlaylistIds = [];
        $this->PlaylistCurrent = 0;
        $this->PlaylistLayer = -1;
        $this->SeekSupported = false;
        $this->ListGeneration = 0;
    }

    /**
     * GetCompatibleParents
     *
     * @return string
     */
    public function GetCompatibleParents(): string
    {
        return json_encode(
            [
                'type'     => 'connect',
                'moduleIDs'=> [
                    \OnkyoAVR\GUID::Splitter
                ]
            ]
        );
    }

    /**
     * ApplyChanges
     *
     * @return void
     */
    public function ApplyChanges(): void
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
        $this->WebHookSecret = '';
        $this->PlaylistIds = [];
        $this->PlaylistCurrent = 0;
        $this->PlaylistLayer = -1;
        $this->SeekSupported = false;
        $this->ListGeneration = 0;
        $this->SetTimerInterval('ClearOnStop', 0);
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
        $this->RegisterVariableInteger(
            \OnkyoAVR\ISCP_API_Commands::NPR,
            $this->Translate('Internet Radio Presets'),
            [
                'MIN'              => 1,
                'MAX'              => 40,
                'DIGITS'           => 0,
                'CUSTOM_GRADIENT'  => '[]',
                'GRADIENT_TYPE'    => 0,
                'ICON'             => '',
                'PRESENTATION'     => VARIABLE_PRESENTATION_SLIDER,
                'INTERVALS'        => '[]',
                'INTERVALS_ACTIVE' => false,
                'PERCENTAGE'       => false,
                'PREFIX'           => '',
                'STEP_SIZE'        => 1,
                'SUFFIX'           => '',
                'USAGE_TYPE'       => 5
            ],
            0
        );
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NPR);
        // Shuffle und Repeat sind nur durchschaltbar (kein absolutes Setzen): ein Eintrag, dessen Beschriftung den Zustand zeigt.
        $this->RegisterSingleOptionEnumeration(\OnkyoAVR\ISCP_API_Commands::NST . '2', 'Shuffle', 'Shuffle', 'off');
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '2');
        $this->RegisterSingleOptionEnumeration(\OnkyoAVR\ISCP_API_Commands::NST . '1', 'Repeat', 'Repeat', 'off');
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '1');
        // Die Mediaplayer-Kachel benötigt für die Wiedergabe zwingend ein Playback-Profil (keine Darstellung möglich).
        $this->RegisterVariableInteger(\OnkyoAVR\ISCP_API_Commands::NST . '0', $this->Translate('State'), '~PlaybackPreviousNext', 0);
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '0');

        $this->RegisterVariableInteger(
            \OnkyoAVR\ISCP_API_Commands::NTR . '0',
            $this->Translate('Current Track'),
            [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION
            ],
            0
        );
        $this->DisableAction(\OnkyoAVR\ISCP_API_Commands::NTR . '0');
        $this->RegisterVariableInteger(\OnkyoAVR\ISCP_API_Commands::NTR . '1', $this->Translate('Number of Tracks'), '', 0);
        $this->RegisterVariableString(\OnkyoAVR\ISCP_API_Commands::NTM . '0', $this->Translate('Position'), '', 0);
        $this->RegisterVariableString(\OnkyoAVR\ISCP_API_Commands::NTM . '1', $this->Translate('Duration'), '', 0);
        // Fortschritt für die Mediaplayer-Kachel: Slider mit Verwendung 'Fortschritt' (USAGE_TYPE 4)
        $this->RegisterVariableInteger(
            \OnkyoAVR\ISCP_API_Commands::NTM,
            $this->Translate('Position'),
            [
                'PRESENTATION'     => VARIABLE_PRESENTATION_SLIDER,
                'MIN'              => 0,
                'MAX'              => 100,
                'STEP_SIZE'        => 1,
                'DIGITS'           => 0,
                'CUSTOM_GRADIENT'  => '[]',
                'GRADIENT_TYPE'    => 0,
                'ICON'             => '',
                'INTERVALS'        => '[]',
                'INTERVALS_ACTIVE' => false,
                'PERCENTAGE'       => false,
                'PREFIX'           => '',
                'SUFFIX'           => ' %',
                'USAGE_TYPE'       => 4
            ],
            0
        );
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NTM);
        $this->RegisterVariableString(\OnkyoAVR\ISCP_API_Commands::NAL, $this->Translate('Album'), '', 0);
        // Für Titel und Interpret gibt es (noch) keine Darstellung, die Mediaplayer-Kachel erwartet die Profile ~Song und ~Artist.
        $this->RegisterVariableString(\OnkyoAVR\ISCP_API_Commands::NTI, $this->Translate('Title'), '~Song', 0);
        $this->RegisterVariableString(\OnkyoAVR\ISCP_API_Commands::NAT, $this->Translate('Artist'), '~Artist', 0);

        $this->RegisterVariableInteger(
            \OnkyoAVR\ISCP_API_Commands::NDS . '0',
            $this->Translate('Network'),
            $this->GetValueOptionsPresentation([
                0 => $this->Translate('no connection'),
                1 => $this->Translate('Ethernet'),
                2 => $this->Translate('Wireless'),
            ]),
            0
        );
        $UsbOptions = [
            0 => $this->Translate('disable'),
            1 => $this->Translate('no device'),
            2 => $this->Translate('iPod/iPhone'),
            3 => $this->Translate('Memory'),
            4 => $this->Translate('Wireless Adaptor'),
            5 => $this->Translate('Bluetooth Adaptor'),
        ];
        $this->RegisterVariableInteger(\OnkyoAVR\ISCP_API_Commands::NDS . '1', $this->Translate('Front USB'), $this->GetValueOptionsPresentation($UsbOptions), 0);
        $this->RegisterVariableInteger(\OnkyoAVR\ISCP_API_Commands::NDS . '2', $this->Translate('Rear USB'), $this->GetValueOptionsPresentation($UsbOptions), 0);

        $this->RegisterNetworkServiceVariable([]);
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NSV);

        if ($this->ReadPropertyBoolean('showCover')) {
            $this->SetCover();
        } else {
            unset($APICommands[array_search(\OnkyoAVR\ISCP_API_Commands::NJA, $APICommands)]);
            $CoverID = $this->FindIDForIdent('Cover');
            if ($CoverID > 0) {
                // Eigenes Cover-Medienobjekt dieser Instanz (vom Modul angelegt); es gibt keine Methode zum Löschen (kein MaintainMedia).
                @IPS_DeleteMedia($CoverID, true);
            }
        }
        if ($this->ReadPropertyBoolean('showNavigation')) {
            $this->RegisterVariableString(
                \OnkyoAVR\ISCP_API_Commands::NLA,
                'Navigation',
                [
                    'PRESENTATION' => VARIABLE_PRESENTATION_WEB_CONTENT,
                    'HTML_TYPE'    => 0,
                    'PADDING'      => false
                ],
                20
            );
        } else {
            $this->UnregisterVariable(\OnkyoAVR\ISCP_API_Commands::NLA);
        }
        if ($this->ReadPropertyBoolean('showPlaylist')) {
            // Die Mediaplayer-Kachel erwartet für die Playlist das Profil ~Playlist (keine Darstellung vorhanden).
            $this->RegisterVariableString(self::PlaylistIdent, $this->Translate('Playlist'), '~Playlist', 21);
            $this->EnableAction(self::PlaylistIdent);
            // Gültiger Startwert passend zu den zurückgesetzten Puffern (PlaylistIds), die Kachel kann eine leere Variable nicht darstellen
            // Startzustand: Der Eintrag '..' (eine Ebene zurück, ID -1) ist immer vorhanden. Die Liste füllt sich bei der nächsten Navigation.
            $this->PlaylistIds = [-1];
            $this->SetValueString(self::PlaylistIdent, json_encode(['entries' => [['song' => '..']], 'current' => 0]));
        } else {
            $this->UnregisterVariable(self::PlaylistIdent);
        }
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        if ($this->ReadPropertyBoolean('showNavigation')) {
            $this->RegisterHook('OnkyoNetPlayer' . $this->InstanceID);
        } else {
            $this->UnregisterHook('OnkyoNetPlayer');
        }
        $this->RegisterParent();
        if ($this->HasActiveParent()) {
            $this->IOChangeState(IS_ACTIVE);
        }
        $this->UnregisterProfile('Onkyo.NetTunerPreset');
        $this->UnregisterProfile('Onkyo.Status');
        $this->UnregisterProfile('Onkyo.Shuffle.' . $this->InstanceID);
        $this->UnregisterProfile('Onkyo.Repeat.' . $this->InstanceID);
        $this->UnregisterProfile('Onkyo.Tracks');
        $this->UnregisterProfile('Onkyo.Network');
        $this->UnregisterProfile('Onkyo.USB');
        $this->UnregisterProfile('Onkyo.SelectNetworkService.' . $this->InstanceID);
        $this->UnregisterProfile('Onkyo.SelectNetworkInput.' . $this->InstanceID);
    }

    /**
     * MessageSink
     *
     * @param  int $TimeStamp
     * @param  int $SenderID
     * @param  int $Message
     * @param  array $Data
     * @return void
     */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);

        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->UnregisterMessage(0, IPS_KERNELSTARTED);
                $this->KernelReady();
                break;
        }
    }

    /**
     * Menu
     *
     * @return bool
     */
    public function Menu(): bool
    {
        return $this->SendKey('TOP');
    }

    /**
     * SelectNetworkService
     *
     * @param  int $ServiceIndex
     * @return bool
     */
    public function SelectNetworkService(int $ServiceIndex): bool
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

    /**
     * SelectInfoListItem
     *
     * @param  int $Index
     * @return bool
     */
    public function SelectInfoListItem(int $Index): bool
    {
        return $this->SelectListItem($Index, $this->Layer);
    }

    /**
     * RequestInfoListData
     *
     * @return array
     */
    public function RequestInfoListData(): array
    {
        return $this->ReadInfoListData(null);
    }
    /**
     * SendKey
     *
     * @param  string $Key
     * @return bool
     */
    public function SendKey(string $Key): bool
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data($this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::NTC), $Key, false);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            return false;
        }
        return true;
    }

    /**
     * RequestAction
     *
     * @param  string $Ident
     * @param  mixed $Value
     * @return void
     */
    public function RequestAction(string $Ident, mixed $Value): void
    {
        $this->RunWithErrorOutput(function () use ($Ident, $Value): void
        {
            $this->ProcessRequestAction($Ident, $Value);
        });
    }

    /**
     * RequestState
     *
     * @param  string $Ident
     * @return bool
     */
    public function RequestState(string $Ident): bool
    {
        if ($Ident == 'ALL') {
            $this->RequestZoneState();
            return true;
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

    /**
     * PreviousTrack
     *
     * @return bool
     */
    public function PreviousTrack(): bool
    {
        if ($this->GetValue(\OnkyoAVR\ISCP_API_Commands::NST . '0') != 2) {
            $this->Stop();
            sleep(3);
        }

        return $this->SendKey('TRDN');
    }

    /**
     * NextTrack
     *
     * @return bool
     */
    public function NextTrack(): bool
    {
        return $this->SendKey('TRUP');
    }

    /**
     * Play
     *
     * @return bool
     */
    public function Play(): bool
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    /**
     * Pause
     *
     * @return bool
     */
    public function Pause(): bool
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    /**
     * Stop
     *
     * @return bool
     */
    public function Stop(): bool
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    /**
     * Shuffle
     *
     * @return bool
     */
    public function Shuffle(): bool
    {
        return $this->SendKey('RANDOM');
    }

    /**
     * Repeat
     *
     * @return bool
     */
    public function Repeat(): bool
    {
        return $this->SendKey(strtoupper(__FUNCTION__));
    }

    /**
     * SetPosition
     *
     * @param  int $Value
     * @return bool
     */
    public function SetPosition(int $Value): bool
    {
        if ($Value > $this->StringToSeconds($this->GetValue(\OnkyoAVR\ISCP_API_Commands::NTM . '1'))) {
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

    /**
     * CallPreset
     *
     * @param  int $Value
     * @return bool
     */
    public function CallPreset(int $Value): bool
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

    /**
     * SavePreset
     *
     * @return bool
     */
    public function SavePreset(): bool
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data(
            $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::NPR),
            'SET',
            false
        );
        return $this->Send($APIData);
    }

    /**
     * ReceiveData
     *
     * @param  string $JSONString
     * @return string
     */
    public function ReceiveData(string $JSONString): string
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data($JSONString);
        $this->SendDebug('ReceiveData', $APIData, 0);
        $this->UpdateVariable($APIData);
        return '';
    }

    /**
     * GetConfigurationForm
     *
     * @return string
     */
    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($Form);
        }

        if ($this->ReadPropertyBoolean('showNavigation')) {
            // Lesen einer fremden Instanz (Util Control) erlaubt: wird für UC_GetIconList (Symbolauswahl) benötigt.
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
     * KernelReady
     *
     * Wird ausgeführt wenn der Kernel hochgefahren wurde.
     *
     * @return void
     */
    protected function KernelReady(): void
    {
        $this->RegisterParent();
    }

    /**
     * IOChangeState
     *
     * Wird ausgeführt wenn sich der Status vom Parent ändert.
     *
     * @param  int $State
     * @return void
     */
    protected function IOChangeState(int $State): void
    {
        if ($State == IS_ACTIVE) {
            if ($this->HasActiveParent()) {
                $this->RequestProfile();
                $this->RequestZoneState();
            }
        }
    }

    /**
     * ProcessHookdata
     *
     * Verarbeitet Daten aus dem Webhook.
     *
     * @global array $_GET
     * @return void
     */
    protected function ProcessHookdata(): void
    {
        if ((!isset($_GET['Type'])) || (!isset($_GET['Secret']))) {
            echo $this->Translate('Bad Request');
            return;
        }
        $CalcSecret = base64_encode(sha1($this->WebHookSecret . '0' . (string) ($_GET['ID'] ?? ''), true));
        // Zeitkonstanter Vergleich; ein leeres Secret (noch nicht erzeugt) lässt keinen Zugriff zu.
        if (($this->WebHookSecret === '') || (!hash_equals($CalcSecret, rawurldecode((string) $_GET['Secret'])))) {
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
     * GetTableHeader
     *
     * Liefert den Header der HTML-Tabelle.
     *
     * @param  array $Config_Table Die Konfiguration der Tabelle
     * @param  array $Config_Columns
     * @return string HTML-String
     */
    protected function GetTableHeader(array $Config_Table, array $Config_Columns): string
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
     * GetTable
     *
     * Liefert den Inhalt der HTML-Box für ein Tabelle.
     *
     * @param array  $Array_Data        Die Nutzdaten der Tabelle.
     * @param string $HookPrefix  Der Prefix des Webhook.
     * @param string $HookType    Ein String welcher als Parameter Type im Webhook übergeben wird.
     * @param string $HookId      Der Index aus dem Array $Data welcher die Nutzdaten (Parameter ID) des Webhook enthält.
     *
     * @return string Der HTML-String.
     */
    protected function GetTable(array $Array_Data, string $HookPrefix, string $HookType, string $HookId): string
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
     * GetTableFooter
     *
     * Liefert den Footer der HTML-Tabelle.
     *
     * @return string HTML-String
     */
    protected function GetTableFooter(): string
    {
        $table = '</tbody>' . PHP_EOL;
        $table .= '</table>' . PHP_EOL;

        return $table;
    }

    /**
     * ReadInfoListData
     *
     * Liest die Liste des Gerätes (seitenweise, siehe ListPageSize).
     *
     * @param  null|callable $Progress Wird nach jeder Seite mit (Einträge bisher, geladen, gesamt) aufgerufen.
     * @return array ['Title' => string, 'List' => array], bei veralteter Liste zusätzlich 'Aborted' => true.
     */
    private function ReadInfoListData(?callable $Progress): array
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
                    $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::NetServiceList);
                    $ret = $this->Send($APIData);
                    $this->SendDebug(\OnkyoAVR\ISCP_API_Commands::NLA, $ret, 0);
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
                // No break. Add additional comment above this line if intentional
            default:
                if ($this->UiType == 3) { // Menü screen
                    $Title = $this->FolderName;
                    $this->SendDebug(\OnkyoAVR\ISCP_API_Commands::NLA, $List, 0);
                    break;
                }
                if ($this->UiType == 2) { // playing screen
                    $Title = '';
                    $this->SendDebug(\OnkyoAVR\ISCP_API_Commands::NLA, $List, 0);
                    break;
                }
                if ($this->UiType == 1) { // Menü screen
                    $Title = $this->FolderName;
                    $this->SendDebug(\OnkyoAVR\ISCP_API_Commands::NLA, $List, 0);
                    break;
                }
                if ($this->ListItems == 0) {
                    break;
                }
                $Pages = $this->ReadListPages($Progress);
                if (isset($Pages['Aborted'])) {
                    return ['Title' => '', 'List' => $List, 'Aborted' => true];
                }
                $Title = $Pages['Title'];
                $List = array_merge($List, $Pages['Items']);
                $this->SendDebug(\OnkyoAVR\ISCP_API_Commands::NLA . ' (Einträge)', count($List), 0);
                break;
        }
        return ['Title' => $Title, 'List' => $List];
    }

    /**
     * ReadListPages
     *
     * Liest alle Einträge der aktuellen Liste des Gerätes in Seiten. Wechselt die Liste während des Ladens, wird abgebrochen.
     *
     * @param  null|callable $Progress Wird nach jeder Seite aufgerufen (Einträge bisher, geladen, gesamt).
     * @return array ['Title' => string, 'Items' => array], bei veralteter Liste ['Aborted' => true].
     */
    private function ReadListPages(?callable $Progress): array
    {
        $Items = [];
        $Start = 0;
        $Total = $this->ListItems;
        $Generation = $this->ListGeneration;
        while ($Start < $Total) {
            $Count = min(self::ListPageSize, $Total - $Start);
            $Page = $this->ReadListPage($Start, $Count);
            if ($this->ListGeneration != $Generation) {
                $this->SendDebug('List', 'Changed while loading, aborted at ' . $Start . ' of ' . $Total, 0);
                return ['Aborted' => true];
            }
            if (isset($Page['Error'])) {
                return ['Title' => $Page['Error'], 'Items' => $Items];
            }
            if (count($Page['Items']) == 0) {
                break; // Das Gerät liefert nichts mehr
            }
            $Items = array_merge($Items, $Page['Items']);
            $Start += $Count;
            if (($Progress !== null) && ($Start < $Total)) {
                $Progress($Items, $Start, $Total);
            }
        }
        return ['Title' => $this->FolderName, 'Items' => $Items];
    }

    /**
     * ReadListPage
     *
     * Liest einen Ausschnitt der aktuellen Liste des Gerätes.
     *
     * @param  int $Start Index des ersten Eintrags.
     * @param  int $Count Anzahl der Einträge.
     * @return array ['Items' => array] mit 'ID' (Index in der Liste des Gerätes), 'Type' und 'Title', oder ['Error' => string].
     */
    private function ReadListPage(int $Start, int $Count): array
    {
        $Sequence = $this->Sequence;
        if ($Sequence == 0xffff) {
            $this->Sequence = 1;
        } else {
            $this->Sequence = $Sequence + 1;
        }
        $Data = 'L' . sprintf('%04X', $Sequence) . sprintf('%02X', max(0, $this->Layer)) . sprintf('%04X', $Start) . sprintf('%04X', $Count);
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::NLA, $Data);
        $ret = $this->Send($APIData);
        if ($ret == null) {
            $this->SendDebug('ERROR', 'NLA Data Timeout', 0);
            return ['Error' => $this->Translate('Timeout')];
        }
        if ($ret[0] != 'X') {
            $this->SendDebug('ERROR', 'NLA Data invalid', 0);
            $this->LogMessage('NLA Data invalid', KL_ERROR);
            return ['Error' => $this->Translate('Data invalid')];
        }
        if (hexdec(substr($ret, 1, 4)) != $Sequence) {
            $this->SendDebug('ERROR', 'NLA Data wrong sequence', 0);
            $this->LogMessage('NLA ' . $this->Translate('Data has wrong sequence number'), KL_ERROR);
            return ['Error' => $this->Translate('Data has wrong sequence number')];
        }
        try {
            $xml = new SimpleXMLElement(substr($ret, 9), LIBXML_NOBLANKS + LIBXML_NONET + LIBXML_NOERROR);
        } catch (Exception $ex) {
            $this->SendDebug('ERROR', 'NLA Data is invalid XML', 0);
            $this->LogMessage('NLA ' . $this->Translate('Data is invalid XML'), KL_ERROR);
            return ['Error' => $this->Translate('Data is invalid XML')];
        }
        if ($ret[5] == 'E') {
            $error = $xml->xpath('//error');
            if (count($error) == 1) {
                $this->SendDebug('ERROR ' . (string) $error[0]['code'], (string) $error[0]['message'], 0);
                trigger_error((string) $error[0]['message'], E_USER_NOTICE);
                return ['Error' => (string) $error[0]['message']];
            }
        }
        $Items = [];
        foreach ($xml->xpath('//item') as $Key => $Item) {
            $Items[] = [
                'ID'    => $Start + (int) $Key,
                'Type'  => hexdec((string) $Item['iconid']),
                'Title' => (string) $Item['title'],
            ];
        }
        return ['Items' => $Items];
    }

    /**
     * SelectListItem
     *
     * Wählt einen Eintrag der Liste des Gerätes. Das Gerät erwartet die Ebene der Liste, in der der Eintrag steht.
     *
     * @param  int $Index Index des Eintrags in der Liste des Gerätes, -1 = eine Ebene zurück.
     * @param  int $Layer Ebene der Liste.
     * @return bool
     */
    private function SelectListItem(int $Index, int $Layer): bool
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

        $Data = 'I' . sprintf('%02X', max(0, $Layer)) . sprintf('%04X', $Index) . '----';
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::NLA, $Data, false);
        $ret = $this->Send($APIData);
        if ($ret == null) {
            return false;
        }
        return true;
    }

    /**
     * RegisterSingleOptionEnumeration
     *
     * Legt eine Variable mit Darstellung 'Aufzählung' und genau einem Eintrag (Wert 0) an.
     * Wird für Wiedergabe-Modi genutzt, die das Gerät nur durchschaltet: Die Beschriftung zeigt den aktuellen Zustand.
     *
     * @param  string $Ident Ident der Variable.
     * @param  string $Name Name der Variable (wird übersetzt, nur beim Anlegen verwendet).
     * @param  string $Icon Symbol der Variable.
     * @param  string $Caption Beschriftung des einzigen Eintrags (wird übersetzt).
     * @return void
     */
    private function RegisterSingleOptionEnumeration(string $Ident, string $Name, string $Icon, string $Caption): void
    {
        $this->RegisterVariableInteger(
            $Ident,
            $this->Translate($Name),
            [
                'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
                'ICON'         => $Icon,
                'OPTIONS'      => json_encode([[
                    'Value'      => 0,
                    'Caption'    => $this->Translate($Caption),
                    'IconActive' => false,
                    'IconValue'  => '',
                    'Color'      => -1
                ]])
            ],
            0
        );
    }

    /**
     * RegisterNetworkServiceVariable
     *
     * Legt die Variable 'Network Service' mit Darstellung 'Aufzählung' an.
     *
     * @param  array $Services Wert => Name der wählbaren Netzwerkdienste.
     * @return void
     */
    private function RegisterNetworkServiceVariable(array $Services): void
    {
        $Options = [];
        foreach ($Services as $Value => $Name) {
            $Options[] = [
                'Value'      => (int) $Value,
                'Caption'    => (string) $Name,
                'IconActive' => false,
                'IconValue'  => '',
                'Color'      => -1
            ];
        }
        $this->RegisterVariableInteger(
            \OnkyoAVR\ISCP_API_Commands::NSV,
            $this->Translate('Network Service'),
            [
                'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
                'ICON'         => '',
                'OPTIONS'      => json_encode($Options)
            ],
            0
        );
    }

    /**
     * GetValueOptionsPresentation
     *
     * Erzeugt die Parameter der Darstellung 'Wertanzeige' für eine reine Anzeige (ohne Aktion),
     * bei der ganzzahlige Werte als Text angezeigt werden (über Intervalle mit Konstante).
     *
     * @param  array $Options Wert => Beschriftung.
     * @return array Parameter für RegisterVariableInteger.
     */
    private function GetValueOptionsPresentation(array $Options): array
    {
        $Intervals = [];
        foreach ($Options as $Value => $Caption) {
            $Intervals[] = [
                'IntervalMinValue' => (int) $Value,
                'IntervalMaxValue' => (int) $Value,
                'ConstantActive'   => true,
                'ConstantValue'    => (string) $Caption,
                'ConversionFactor' => 1,
                'DigitsActive'     => false,
                'DigitsValue'      => 0,
                'IconActive'       => false,
                'IconValue'        => '',
                'PrefixActive'     => false,
                'PrefixValue'      => '',
                'SuffixActive'     => false,
                'SuffixValue'      => ''
            ];
        }
        return [
            'PRESENTATION'     => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'INTERVALS_ACTIVE' => true,
            'INTERVALS'        => json_encode($Intervals)
        ];
    }

    /**
     * ProcessRequestAction
     *
     * Führt die eigentliche Bedienaktion aus. Fehler meldet RequestAction an das Frontend.
     *
     * @param  string $Ident
     * @param  mixed $Value
     * @return void
     */
    private function ProcessRequestAction(string $Ident, mixed $Value): void
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return;
        }
        switch ($Ident) {
            /* case 'SLI':
              return $this->SelectInput($Value); */
            case \OnkyoAVR\ISCP_API_Commands::NPR:
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NPR, $Value);
                $this->CallPreset($Value);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NTM:
                if (!$this->SeekSupported) {
                    trigger_error($this->Translate('Command not available.'), E_USER_NOTICE);
                    break;
                }
                $Total = $this->StringToSeconds($this->GetValue(\OnkyoAVR\ISCP_API_Commands::NTM . '1'));
                $Time = ($Total / 100) * (int) $Value;
                $this->SetPosition(intval($Time));
                break;
            case \OnkyoAVR\ISCP_API_Commands::NTC:
                $this->SendKey($Value);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NLA:
                $this->SendDebug('RequestAction', \OnkyoAVR\ISCP_API_Commands::NLA, 0);
                if ($this->ReadPropertyBoolean('showNavigation') || $this->ReadPropertyBoolean('showPlaylist')) {
                    $Progress = null;
                    // Große Listen brauchen am Gerät lange: In der Playlist den Warte-Eintrag mit dem Fortschritt zeigen.
                    // Im Wiedergabe-Bildschirm bleibt die zuletzt gezeigte Liste erhalten.
                    if ($this->ReadPropertyBoolean('showPlaylist') && ($this->UiType != 2) && ($this->ListItems > self::ListPageSize)) {
                        $this->WritePlaylistWait([], 0, $this->ListItems);
                        $Progress = function (array $Items, int $Loaded, int $Total): void
                        {
                            $this->WritePlaylistWait($Items, $Loaded, $Total);
                        };
                    }
                    $Result = $this->ReadInfoListData($Progress);
                    if (!empty($Result['Aborted'])) {
                        break; // Das Gerät hat die Liste gewechselt, die neue Liste lädt selbst
                    }
                    $this->RefreshNavigationTable($Result);
                }
                break;
            case 'ClearOnStop':
                // Timer: Der Stop dauert an, Anzeige zurücksetzen
                $this->SetTimerInterval('ClearOnStop', 0);
                if ($this->GetValue(\OnkyoAVR\ISCP_API_Commands::NST . '0') == 1) {
                    $this->Multi_Cover = '';
                    $this->SetCover();
                    $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NAL, '');
                    $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NTI, '');
                    $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NAT, '');
                    $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NTM . '0', '--:--');
                    $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NTM . '1', '--:--');
                    $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NTM, 0);
                }
                break;
            case self::PlaylistIdent:
                // Die Kachel sendet ein JSON, 'current' ist der Index des gewählten Eintrags in der Playlist.
                $this->SendDebug('Playlist RequestAction', $Value, 0);
                $Data = json_decode((string) $Value, true);
                $Index = is_array($Data) ? ($Data['current'] ?? null) : null;
                $PlaylistIds = $this->PlaylistIds;
                if ((!is_int($Index)) || (!array_key_exists($Index, $PlaylistIds))) {
                    trigger_error($this->Translate('Invalid Value.'), E_USER_NOTICE);
                    break;
                }
                if ($PlaylistIds[$Index] == self::PlaylistWaitId) {
                    break; // Der Eintrag 'Bitte warten' ist keine Auswahl
                }
                if ($this->UiType == 2) {
                    // Im Wiedergabe-Bildschirm nimmt das Gerät keine Auswahl aus der Liste an: erst zur Liste zurück, bis das Gerät sie meldet.
                    $this->SendDebug('Playlist select', 'Playing screen, back to the list first', 0);
                    if (!$this->SendKey('RETURN')) {
                        break;
                    }
                    for ($Wait = 0; ($Wait < 30) && ($this->UiType == 2); $Wait++) {
                        usleep(100000);
                    }
                    if ($this->UiType == 2) {
                        trigger_error($this->Translate('Timeout'), E_USER_NOTICE);
                        break;
                    }
                }
                $this->PlaylistCurrent = $Index;
                $Layer = ($this->PlaylistLayer >= 0) ? $this->PlaylistLayer : $this->Layer;
                $this->SendDebug('Playlist select', ['Index' => $Index, 'Id' => $PlaylistIds[$Index], 'Layer' => $Layer, 'Layer last NLT' => $this->Layer], 0);
                $this->SelectListItem($PlaylistIds[$Index], $Layer);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NST . '0':
                switch ($Value) {
                    case 0:
                        $this->PreviousTrack();
                        break;
                    case 1:
                        $this->Stop();
                        break;
                    case 2: // Play
                        if ($this->GetValue(\OnkyoAVR\ISCP_API_Commands::NST . '0') == 2) {
                            break;
                        }
                        $this->Play();
                        break;
                    case 3: // Pause
                        if ($this->GetValue(\OnkyoAVR\ISCP_API_Commands::NST . '0') == 3) {
                            break;
                        }
                        $this->Pause();
                        break;
                    case 4:
                        $this->NextTrack();
                        break;
                    default:
                        trigger_error($this->Translate('Invalid Value.'), E_USER_NOTICE);
                        break;
                }
                break;
            case \OnkyoAVR\ISCP_API_Commands::NST . '1':
                $this->Repeat();
                break;
            case \OnkyoAVR\ISCP_API_Commands::NST . '2':
                $this->Shuffle();
                break;
            case \OnkyoAVR\ISCP_API_Commands::NTR . '0':
                switch ($Value) {
                    case -999:
                        $this->PreviousTrack();
                        break;
                    case 999:
                        $this->NextTrack();
                        break;
                }
                return;
            case \OnkyoAVR\ISCP_API_Commands::NSV:
                $this->SelectNetworkService($Value);
                break;
            default:
                trigger_error($this->Translate('Invalid Ident.'), E_USER_NOTICE);
                break;
        }
    }

    /**
     * SetCover
     *
     * @return void
     */
    private function SetCover(): void
    {
        $this->SendDebug('Refresh Cover', '', 0);
        $CoverID = $this->FindIDForIdent('Cover');
        if (!$CoverID) {
            // Für Medienobjekte gibt es keine IPSModule-Methoden (kein MaintainMedia), daher direkt IPS_*.
            $CoverID = IPS_CreateMedia(1);
            IPS_SetParent($CoverID, $this->InstanceID);
            IPS_SetIdent($CoverID, 'Cover');
            IPS_SetName($CoverID, 'Cover');
            IPS_SetPosition($CoverID, 27);
            IPS_SetMediaCached($CoverID, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'Cover_' . $this->InstanceID . '.onkyo.png';
            IPS_SetMediaFile($CoverID, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        $CoverRAW = $this->Multi_Cover;
        $this->SendDebug('Cover data', strlen($CoverRAW) . ' bytes, state ' . $this->GetValue(\OnkyoAVR\ISCP_API_Commands::NST . '0'), 0);

        // Cover kommen als BMP oder JPEG, die Visualisierungen benötigen PNG
        $CoverPng = \OnkyoAVR\CoverImage::ToPng($CoverRAW);
        if (($CoverPng === '') && ($CoverRAW !== '')) {
            $this->SendDebug('Cover', 'Image cannot be converted to PNG', 0);
        }
        $CoverRAW = $CoverPng;
        if ($CoverRAW === '') {
            $CoverRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'nocover.png');
        }
        // Inhalt des eigenen Cover-Medienobjekts setzen, keine IPSModule-Methode vorhanden.
        IPS_SetMediaContent($CoverID, base64_encode($CoverRAW));
    }

    /**
     * RefreshNavigationTable
     *
     * @param  array $List
     * @return void
     */
    private function RefreshNavigationTable(array $List): void
    {
        if ($this->ReadPropertyBoolean('showNavigation')) {
            $HTML = $this->GetTable($List, 'OnkyoNetPlayer', 'Index', 'ID');
            $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NLA, $HTML);
        }
        if ($this->ReadPropertyBoolean('showPlaylist')) {
            $this->RefreshPlaylist($List['List']);
        }
    }

    /**
     * UpdatePlaylistCurrent
     *
     * Markiert in der Playlist den Titel, der gerade gespielt wird ('current').
     * Das Gerät meldet bei jedem Titelwechsel die Titelnummer (NTR) und den Titel (NTI). Die Nummer entspricht der Position in der
     * gezeigten Liste, weil der Eintrag '..' den Index 0 belegt. Der Titel wird zur Absicherung mit dem Eintrag verglichen.
     *
     * @return void
     */
    private function UpdatePlaylistCurrent(): void
    {
        if (!$this->ReadPropertyBoolean('showPlaylist')) {
            return;
        }
        $Playlist = json_decode($this->GetValue(self::PlaylistIdent), true);
        if ((!is_array($Playlist)) || (!isset($Playlist['entries'])) || (count($Playlist['entries']) < 2)) {
            return;
        }
        $Songs = array_column($Playlist['entries'], 'song');
        $Track = $this->GetValue(\OnkyoAVR\ISCP_API_Commands::NTR . '0');
        $Title = $this->GetValue(\OnkyoAVR\ISCP_API_Commands::NTI);
        $Index = \OnkyoAVR\PlaylistIndex::Find($Songs, $Track, $Title);
        $this->SendDebug('Playlist current', ['Track' => $Track, 'Title' => $Title, 'Index' => $Index], 0);
        if ($Index === null) {
            return;
        }
        // Die Liste des Gerätes enthält nur Titel. Interpret und Dauer (Sekunden) sind nur für den gespielten Titel bekannt.
        $Entries = $Playlist['entries'];
        foreach ($Entries as &$Entry) {
            unset($Entry['artist'], $Entry['duration']);
        }
        unset($Entry);
        $Artist = $this->GetValue(\OnkyoAVR\ISCP_API_Commands::NAT);
        if ($Artist != '') {
            $Entries[$Index]['artist'] = $Artist;
        }
        $Duration = $this->StringToSeconds($this->GetValue(\OnkyoAVR\ISCP_API_Commands::NTM . '1'));
        if ($Duration > 0) {
            $Entries[$Index]['duration'] = $Duration;
        }
        $NewPlaylist = ['entries' => $Entries, 'current' => $Index];
        if ($NewPlaylist === $Playlist) {
            return;
        }
        $this->PlaylistCurrent = $Index;
        $this->SetValueString(self::PlaylistIdent, json_encode($NewPlaylist));
    }

    /**
     * WritePlaylistWait
     *
     * Schreibt die bisher geladenen Einträge und den Eintrag 'Bitte warten' mit dem Fortschritt in die Playlist-Variable,
     * solange eine große Liste des Gerätes noch geladen wird.
     *
     * @param  array $Items Bisher geladene Einträge ('ID', 'Type', 'Title'), ohne den Eintrag '..'.
     * @param  int $Loaded Anzahl der geladenen Einträge.
     * @param  int $Total Anzahl der Einträge der Liste.
     * @return void
     */
    private function WritePlaylistWait(array $Items, int $Loaded, int $Total): void
    {
        $Entries = [['song' => '..']];
        $Ids = [-1];
        foreach ($Items as $Item) {
            $Entries[] = ['song' => (string) $Item['Title']];
            $Ids[] = (int) $Item['ID'];
        }
        $Wait = ($Loaded == 0) ? $this->Translate('Please wait...') : sprintf($this->Translate('Please wait... (%d of %d)'), $Loaded, $Total);
        $Entries[] = ['song' => $Wait];
        $Ids[] = self::PlaylistWaitId;
        $this->PlaylistIds = $Ids;
        $this->PlaylistCurrent = 0;
        $this->PlaylistLayer = $this->Layer;
        $this->SetValueString(self::PlaylistIdent, json_encode(['entries' => $Entries, 'current' => 0]));
    }
    /**
     * RefreshPlaylist
     *
     * Schreibt die Liste des Gerätes im Format der Mediaplayer-Kachel (~Playlist) in die Playlist-Variable.
     * Die Einträge enthalten den Titel; der Index eines Eintrags wird in PlaylistIds auf die ID in der Liste des Gerätes abgebildet.
     * Der Eintrag '..' (ID -1) bedeutet eine Ebene zurück.
     *
     * @param  array $Items Einträge mit den Schlüsseln 'ID', 'Type' und 'Title'.
     * @return void
     */
    private function RefreshPlaylist(array $Items): void
    {
        if (($this->UiType == 2) && (count($this->PlaylistIds) > 1)) {
            // Wiedergabe-Bildschirm: Das Gerät liefert keine Liste. Die zuletzt gezeigte Liste bleibt erhalten, der gewählte Titel wird markiert.
            $Playlist = json_decode($this->GetValue(self::PlaylistIdent), true);
            if (is_array($Playlist) && isset($Playlist['entries'])) {
                $Playlist['current'] = $this->PlaylistCurrent;
                $this->SetValueString(self::PlaylistIdent, json_encode($Playlist));
                return;
            }
        }
        $this->PlaylistCurrent = 0;
        // Die Ebene gilt für die gezeigte Liste. Spätere Meldungen des Gerätes (z.B. vom Wiedergabe-Bildschirm) ändern sie nicht.
        $this->PlaylistLayer = $this->Layer;
        $Entries = [];
        $Ids = [];
        foreach ($Items as $Item) {
            $Entries[] = ['song' => (string) $Item['Title']];
            $Ids[] = (int) $Item['ID'];
        }
        $this->PlaylistIds = $Ids;
        // Die Kachel benötigt das Feld 'current'. Eine Navigationsliste hat keinen aktuellen Eintrag, daher der erste.
        $this->SetValueString(self::PlaylistIdent, json_encode(['entries' => $Entries, 'current' => 0]));
    }

    /**
     * ProcessPopUpInfo
     *
     * @param  string $NPUData
     * @return void
     */
    private function ProcessPopUpInfo(string $NPUData): void
    {
        if ($NPUData[0] == 'L') { //list not supported
            return;
        }
        if ((!$this->ReadPropertyBoolean('showNavigation')) && (!$this->ReadPropertyBoolean('showPlaylist'))) {
            return;
        }
        $NPU = explode(chr(0), substr($NPUData, 1));
        $Cursor = (int) $NPU[3][0];
        $NPU[3] = substr($NPU[3], 1);
        $this->SendDebug(\OnkyoAVR\ISCP_API_Commands::NPU, $NPU, 0);
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

    /**
     * ProcessListTitelInfo
     *
     * @param  string $NLTData
     * @return void
     */
    private function ProcessListTitelInfo(string $NLTData): void
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
        $this->ListGeneration = $this->ListGeneration + 1; // veraltete Ladevorgänge der Liste abbrechen
        $this->ServiceType = $ServiceType;

        $this->UiType = $UiType;
        $this->Layer = $Layer;
        $this->ListItems = $ListItems;

        if ($ServiceType == 0xf3) {
            $this->FolderName = 'Network';
        } else {
            $this->FolderName = $FolderName;
        }
        $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NSV, $ServiceType);
        // Navigation in eigenem Thread neu laden, damit die Verarbeitung der Geräteantwort (ReceiveData) nicht blockiert.
        IPS_RunScriptText('IPS_RequestAction(' . $this->InstanceID . ',\'NLA\',0);');
    }

    /**
     * UpdateVariable
     *
     * @param  \OnkyoAVR\ISCP_API_Data $APIData
     * @return void
     */
    private function UpdateVariable(\OnkyoAVR\ISCP_API_Data $APIData): void
    {
        switch ($APIData->APICommand) {
            case \OnkyoAVR\ISCP_API_Commands::NMS:

                // Die Aktion von NTM bleibt immer aktiv: der Schieberegler 'Fortschritt' der Mediaplayer-Kachel benötigt sie.
                $this->SeekSupported = ($APIData->Data[5] == 'S');
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
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NDS . '0', $StatusValue);

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
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NDS . '1', $StatusValue);

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
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NDS . '2', $StatusValue);

                break;
            case \OnkyoAVR\ISCP_API_Commands::NPU:
                $this->ProcessPopUpInfo($APIData->Data);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NLT:
                $this->ProcessListTitelInfo($APIData->Data);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NTR:
                $Data = explode('/', (string) $APIData->Data);
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NTR . '0', (int) $Data[0]);
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NTR . '1', (int) $Data[1]);
                $this->UpdatePlaylistCurrent();
                break;
            case \OnkyoAVR\ISCP_API_Commands::NST:
                switch ((string) $APIData->Data[0]) {
                    case 'S':
                    case 'E':
                        $StatusValue = 1; // Stop
                        // Bei einem Titelwechsel meldet das Gerät kurz Stop. Cover und Titel werden daher erst zurückgesetzt, wenn der Stop andauert.
                        $this->SetTimerInterval('ClearOnStop', self::ClearOnStopDelay);
                        break;
                    case 'P':
                        $StatusValue = 2; // Play
                        $this->SetTimerInterval('ClearOnStop', 0);
                        break;
                    case 'p':
                        $StatusValue = 3; // Pause
                        $this->SetTimerInterval('ClearOnStop', 0);
                        break;
                    default:
                        $StatusValue = $this->GetValue(\OnkyoAVR\ISCP_API_Commands::NST . '0');
                        break;
                }
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NST . '0', $StatusValue);
                switch ((string) $APIData->Data[1]) {
                    case 'x':
                        $RepeatValue = 'off';
                        $this->DisableAction(\OnkyoAVR\ISCP_API_Commands::NST . '1');
                        break;
                    case '-':
                        $RepeatValue = 'off';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '1');
                        break;
                    case 'R':
                        $RepeatValue = 'All';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '1');
                        break;
                    case 'F':
                        $RepeatValue = 'Folder';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '1');
                        break;
                    case '1':
                        $RepeatValue = 'Title';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '1');
                        break;
                }

                $this->RegisterSingleOptionEnumeration(\OnkyoAVR\ISCP_API_Commands::NST . '1', 'Repeat', 'Repeat', $RepeatValue);

                switch ((string) $APIData->Data[2]) {
                    case 'x':
                        $ShuffleValue = 'off';
                        $this->DisableAction(\OnkyoAVR\ISCP_API_Commands::NST . '2');
                        break;
                    case '-':
                        $ShuffleValue = 'off';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '2');
                        break;
                    case 'S':
                        $ShuffleValue = 'All';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '2');
                        break;
                    case 'A':
                        $ShuffleValue = 'Album';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '2');
                        break;
                    case 'F':
                        $ShuffleValue = 'Folder';
                        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::NST . '2');
                        break;
                }
                $this->RegisterSingleOptionEnumeration(\OnkyoAVR\ISCP_API_Commands::NST . '2', 'Shuffle', 'Shuffle', $ShuffleValue);
                break;
            case \OnkyoAVR\ISCP_API_Commands::NJA:
                if (!$this->ReadPropertyBoolean('showCover')) {
                    break;
                }
                switch ((string) $APIData->Data[0]) {
                    case '0':
                    case '1':
                        if ($APIData->Data[1] == '0') {
                            $this->SendDebug('Cover', 'Start, type ' . ($APIData->Data[0] == '0' ? 'BMP' : 'JPEG'), 0);
                            $this->Multi_Cover = '';
                        }
                        $this->Multi_Cover .= hex2bin(substr($APIData->Data, 2));
                        if ($APIData->Data[1] == '2') {
                            $this->SendDebug('Cover', 'End, size ' . strlen($this->Multi_Cover) . ' bytes', 0);
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
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NTM . '0', $Data[0]);
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NTM . '1', $Data[1]);
                if ($Data[1] != '--:--') {
                    $NTM0 = $this->StringToSeconds($Data[0]);
                    $NTM1 = $this->StringToSeconds($Data[1]);
                    $Total = (100 / ($NTM1 ? $NTM1 : 100)) * $NTM0;
                    // Prozentwert 0..100 für den Schieberegler 'Fortschritt' (Ganzzahl)
                    $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::NTM, (int) min(100, max(0, round($Total))));
                }
                break;
            case \OnkyoAVR\ISCP_API_Commands::NAL:
            case \OnkyoAVR\ISCP_API_Commands::NTI:
            case \OnkyoAVR\ISCP_API_Commands::NAT:
                $this->SetValueString((string) $APIData->APICommand, (string) $APIData->Data);
                if ($APIData->APICommand != \OnkyoAVR\ISCP_API_Commands::NAL) {
                    $this->UpdatePlaylistCurrent();
                }
                break;
        }
    }

    /**
     * RequestProfile
     *
     * @return void
     */
    private function RequestProfile(): void
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
        // bestimmte Werte als $ResultDataNetServiceList hinzufügen für USB / USB rear / NET / Bluetooth
        $APIDataNetServiceList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::NetServiceList);
        $ResultDataNetServiceList = $this->Send($APIDataNetServiceList);
        if (count($ResultDataNetServiceList) > 0) {
            foreach ($ResultDataNetServiceList as $Value => $NetServiceProfileData) {
                $AssociationNSV[] = [$Value, $NetServiceProfileData, '', -1];
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
        $Services = [];
        foreach ($AssociationNSV as $Association) {
            $Services[$Association[0]] = $Association[1];
        }
        $this->RegisterNetworkServiceVariable($Services);
    }

    /**
     * RequestZoneState
     *
     * @return void
     */
    private function RequestZoneState(): void
    {
        $ApiCmds = \OnkyoAVR\ONKYO_Zone_NetPlayer::$ReadAPICommands;
        foreach ($ApiCmds as $ApiCmd) {
            $APIData = new \OnkyoAVR\ISCP_API_Data($ApiCmd, \OnkyoAVR\ISCP_API_Commands::Request);
            $ResultData = @$this->Send($APIData);
            if ($ResultData === null) {
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NAL, '');
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NTI, '');
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NAT, '');
                break;
            }
            if ($ResultData === '----/----') {
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NAL, '');
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NTI, '');
                $this->SetValueString(\OnkyoAVR\ISCP_API_Commands::NAT, '');
                break;
            }
            $APIData->Data = $ResultData;
            $this->UpdateVariable($APIData);
        }
    }

    /**
     * Send
     *
     * @param  \OnkyoAVR\ISCP_API_Data $APIData
     * @return mixed
     */
    private function Send(\OnkyoAVR\ISCP_API_Data $APIData): mixed
    {
        $this->SendDebug('ForwardData', $APIData, 0);

        try {
            if (!$this->HasActiveParent()) {
                throw new Exception($this->Translate('Instance has no active parent.'), E_USER_NOTICE);
            }
            $ret = $this->SendDataToParent($APIData->ToJSONString(\OnkyoAVR\GUID::SendToSplitter));
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

    /**
     * GenerateHTMLStyleProperty
     *
     * @return array
     */
    private function GenerateHTMLStyleProperty(): array
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

    /**
     * SecondsToString
     *
     * @param  int $Time
     * @return string
     */
    private function SecondsToString(int $Time): string
    {
        if ($Time > 3600) {
            return sprintf('%02d:%02d:%02d', ($Time / 3600), ($Time / 60 % 60), $Time % 60);
        } else {
            return sprintf('%02d:%02d', ($Time / 60 % 60), $Time % 60);
        }
    }

    /**
     * StringToSeconds
     *
     * @param  string $Value
     * @return int
     */
    private function StringToSeconds(string $Value): int
    {
        $Parts = explode(':', $Value);
        $Seconds = (int) array_pop($Parts);
        foreach ($Parts as $Part) {
            $Seconds += (int) $Part * 60;
        }
        return $Seconds;
    }
}
