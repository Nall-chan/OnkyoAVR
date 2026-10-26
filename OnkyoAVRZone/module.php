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
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/AttributeArrayHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property int $ParentID Die InstanzID des IO-Parent
 * @property \OnkyoAVR\ONKYO_Zone $OnkyoZone
 * @property bool $PhaseMatchingBass
 * @property array $ToneProfile Profil-Schlüssel => [Minimum, Maximum, Schrittweite(, Suffix)] (Standard- oder Gerätewerte)
 * @property array $AssociationProfile Profil-Schlüssel => Auswahlliste vom Gerät (Eingänge, Hörmodi)
 * @property array $MyConfig
 * @property array $LMDList
 * @method bool lock(string $ident)
 * @method void unlock(string $ident)
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 * @method void SetValueBoolean(string $Ident, bool $value)
 * @method void SetValueFloat(string $Ident, float $value)
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void SetValueString(string $Ident, string $value)
 * @method void RegisterProfileInteger(string $Name, string $Icon, string $Prefix, string $Suffix, int $MinValue, int $MaxValue, float $StepSize)
 * @method void RegisterProfileIntegerEx(string $Name, string $Icon, string $Prefix, string $Suffix, array $Associations, int $MaxValue = -1, float $StepSize = 0)
 * @method void RegisterProfileFloat(string $Name, string $Icon, string $Prefix, string $Suffix, float $MinValue, float $MaxValue, float $StepSize, int $Digits)
 * @method void UnregisterProfile(string $Name)
 * @method int FindIDForIdent(string $Ident)
 * @method void RegisterParent()
 * @method bool IORequestAction(string $Ident, mixed $Value)
 * @method void IOMessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data)
 */
class OnkyoAVR extends IPSModuleStrict
{
    use \OnkyoAVR\AttributeArrayHelper;
    use \OnkyoAVR\DebugHelper,
        \OnkyoAVR\BufferHelper,
        \OnkyoAVR\InstanceStatus,
        \OnkyoAVR\VariableHelper,
        \OnkyoAVR\VariableProfileHelper {
            \OnkyoAVR\InstanceStatus::MessageSink as IOMessageSink;
            \OnkyoAVR\InstanceStatus::RequestAction as IORequestAction;
        }
    /**
     * Name des Attributs mit der Auswahl der angebotenen Hörmodi (Liste mit 'Code' und 'Active')
     */
    private const AttributeListeningModes = 'ListeningModes';

    /**
     * Name des Attributs mit den Hörmodi, die das Gerät schon gemeldet hat
     */
    private const AttributeSeenListeningModes = 'SeenListeningModes';

    /**
     * Create
     *
     * @return void
     */
    public function Create(): void
    {
        parent::Create();
        $this->RegisterPropertyInteger('Zone', \OnkyoAVR\ONKYO_Zone::None);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::VL4, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::MT4, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SL4, true);

        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::VL3, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::MT3, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SL3, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TN3, true);

        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::ZVL, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::ZMT, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SLZ, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::ZTN, true);

        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TFR, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::MVL, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::AMT, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SLI, true);

        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::CTL, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SWL, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::LMD, true);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::LMD2, true);
        // Auswahl der angebotenen Hörmodi (leer = Standard) und vom Gerät gemeldete (gelernte) Modi
        $this->RegisterAttributeArray(self::AttributeListeningModes, []);
        $this->RegisterAttributeArray(self::AttributeSeenListeningModes, []);

        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TFW, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TFH, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TSR, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TSB, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TCT, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::TSW, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SW2, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::HDO, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::CEC, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::HAO, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::HAS, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::RES, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::VWM, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::VPM, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::DIF, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::DIM, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::ADQ, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::ADY, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::ADV, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SLA, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::IFA, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::IFV, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::SLP, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::LTN, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::MOT, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::RAS, false);
        $this->RegisterPropertyBoolean(\OnkyoAVR\ISCP_API_Commands::PMB, false);
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone();
        $this->PhaseMatchingBass = true;
        $this->ToneProfile = [];
        $this->AssociationProfile = [];
        $this->MyConfig = [];
        $this->LMDList = [];
        $this->SetReceiveDataFilter('.*"APICommand":"NOTING".*');
        $this->SendDebug('FILTER', 'NOTHING', 0);
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
        $this->SetReceiveDataFilter('.*"APICommand":"NOTING".*');
        $this->SendDebug('FILTER', 'NOTHING', 0);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        $this->LMDList = [];
        parent::ApplyChanges();
        // Standardwerte der Darstellungen (Tonwerte, Lautstärke); gerätespezifische Werte folgen in RequestProfile
        $ProfileData = [];
        foreach (\OnkyoAVR\IPSProfiles::$ProfileListIndexToProfile as $ProfileName) {
            if (array_key_exists($ProfileName, \OnkyoAVR\IPSProfiles::$ProfilInteger)) {
                $ProfileData[$ProfileName] = \OnkyoAVR\IPSProfiles::$ProfilInteger[$ProfileName];
            }
            if (array_key_exists($ProfileName, \OnkyoAVR\IPSProfiles::$ProfilFloat)) {
                $ProfileData[$ProfileName] = \OnkyoAVR\IPSProfiles::$ProfilFloat[$ProfileName];
            }
        }
        $ProfileData[\OnkyoAVR\IPSProfiles::ptVolume] = \OnkyoAVR\IPSProfiles::$ProfilInteger[\OnkyoAVR\IPSProfiles::ptVolume];
        $this->SendDebug('Default Profile', $ProfileData, 0);
        $this->ToneProfile = $ProfileData;
        $this->AssociationProfile = [];
        $OldZone = $this->OnkyoZone->thisZone;
        $NewZone = $this->ReadPropertyInteger('Zone');
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone($NewZone);

        // Power, Mute, Volume, Input überführen in neuen Ident
        if ((($OldZone != 0) && ($NewZone != 0)) && ($OldZone != $NewZone)) {
            $OldZoneIdents = \OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$OldZone];
            $NewZoneIdents = \OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone];
            for ($index = 0; $index < 4; $index++) {
                $VarId = $this->FindIDForIdent($OldZoneIdents[$index]);
                if ($VarId > 0) {
                    // Ident einer eigenen Variable bei Zonenwechsel übernehmen (Wert bleibt erhalten); keine IPSModule-Methode vorhanden.
                    IPS_SetIdent($VarId, $NewZoneIdents[$index]);
                }
            }
        }

        // Alle Properties auf einmal lesen (Schleife über dynamische Befehlsliste), daher IPS_GetConfiguration statt ReadProperty*.
        $MyProperties = json_decode(IPS_GetConfiguration($this->InstanceID), true);
        $this->PhaseMatchingBass = true;
        $this->SetSummary($this->OnkyoZone->GetName());
        $APICommands = $this->OnkyoZone->GetAPICommands();
        if (count($APICommands) > 0) {
            foreach ($APICommands as $APICommand) {
                if (array_key_exists($APICommand, $MyProperties)) {
                    if ($MyProperties[$APICommand] === false) {
                        continue;
                    }
                }
                $Lines[] = '.*"APICommand":"' . $APICommand . '".*';
            }
            $Line = implode('|', $Lines);
            $this->SetReceiveDataFilter('(' . $Line . ')');
            $this->SendDebug('FILTER', $Line, 0);
        }
        unset($MyProperties['Zone']);

        // Abgewählte Variablen entfernen und Variablen welche nicht in dieser Zone sind
        foreach ($MyProperties as $Key => &$Value) {
            if (!in_array($Key, \OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone])) {
                $Value = false;
            }
            if ($Value === false) {
                $Mapping = \OnkyoAVR\ISCP_API_Data_Mapping::GetMapping($Key);
                $VariableIdent = $Key;
                if ($Mapping != null) {
                    if ($Mapping->VarType == \OnkyoAVR\IPSVarType::vtDualInteger) {
                        foreach ($Mapping->ValuePrefix as $Prefix) {
                            $this->UnregisterVariable($VariableIdent . $Prefix);
                        }
                        continue;
                    }
                }
                $this->UnregisterVariable($VariableIdent);
            }
        }
        $this->MyConfig = $MyProperties;
        // Vorhandene Variablen auf die Darstellung umstellen, erst danach die alten Profile entfernen
        $this->RefreshVariablePresentations();
        $this->RemoveLegacyProfiles();
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->RegisterParent();
        if ($this->CheckZone()) {
            if ($this->HasActiveParent()) {
                $this->IOChangeState(IS_ACTIVE);
            }
        }
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
     * GetConfigurationForm
     *
     * @return string
     */
    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form_' . $this->OnkyoZone->thisZone . '.json'), true);
        $Rows = \OnkyoAVR\ListeningModes::GetRows(
            $this->GetSavedListeningModes(),
            $this->GetSeenListeningModes(),
            function (string $Text): string
            {
                return $this->Translate($Text);
            }
        );
        $this->SetListValues($Form['actions'], self::AttributeListeningModes, $Rows);
        return json_encode($Form);
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
        $this->ProcessRequestAction($Ident, $Value);
    }

    /**
     * RequestState
     *
     * @param  string $Ident
     * @return bool
     */
    public function RequestState(string $Ident): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        if ($Ident == 'ALL') {
            $this->RequestZoneState();
            return true;
        }
        $ApiCmd = substr($Ident, 0, 3);
        if (!$this->OnkyoZone->CmdAvailable($ApiCmd)) {
            echo $this->Translate('Command not available at this zone.') . PHP_EOL;
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data($ApiCmd, \OnkyoAVR\ISCP_API_Commands::Request);
        $Mapping = $APIData->GetMapping();
        if ($Mapping !== null) {
            if ($Mapping->RequestValue) {
                $ResultData = $this->Send($APIData);
                if ($ResultData === null) {
                    return false;
                }
                $APIData->Data = $ResultData;
                $this->UpdateVariable($APIData);
                return true;
            }
        }
        return false;
    }

    /**
     * Power
     *
     * @return bool
     */
    public function Power(): bool
    {
        return $this->SendPower(!$this->GetValue(\OnkyoAVR\ISCP_API_Commands::PWR));
    }

    /**
     * PowerOn
     *
     * @return bool
     */
    public function PowerOn(): bool
    {
        return $this->SendPower(true);
    }

    /**
     * PowerOff
     *
     * @return bool
     */
    public function PowerOff(): bool
    {
        return $this->SendPower(false);
    }

    /**
     * SetVolume
     *
     * @param  int $Value
     * @return bool
     */
    public function SetVolume(int $Value): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        // Bereich der Zone (vom Gerät gelesen, sonst Standardwerte)
        $Range = $this->ToneProfile[\OnkyoAVR\IPSProfiles::ptVolume] ?? \OnkyoAVR\IPSProfiles::$ProfilInteger[\OnkyoAVR\IPSProfiles::ptVolume];
        if (($Value < $Range[0]) || ($Value > $Range[1])) {
            echo sprintf($this->Translate('%s out of range.'), 'Value') . PHP_EOL;
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::MVL, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::ZVL, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::VL3, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::VL4, $Value);
                break;
        }
        return $this->SendAPIData($APIData);
    }

    /**
     * SetMute
     *
     * @param  bool $Value
     * @return bool
     */
    public function SetMute(bool $Value): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::AMT, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::ZMT, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::MT3, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::MT4, $Value);
                break;
        }
        return $this->SendAPIData($APIData);
    }

    /**
     * SelectInput
     *
     * @param  int $Value
     * @return bool
     */
    public function SelectInput(int $Value): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SLI, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SLZ, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SL3, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SL4, $Value);
                break;
        }
        return $this->SendAPIData($APIData);
    }

    /**
     * SelectAudioInput
     *
     * @param  int $Value
     * @return bool
     */
    public function SelectAudioInput(int $Value): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SLA, $Value);
        return $this->SendAPIData($APIData);
    }

    /**
     * SelectListingMode
     *
     * @param  int $Value
     * @return bool
     */
    public function SelectListingMode(int $Value): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::LMD, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::LMZ, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                echo $this->Translate('Command not available at this zone.') . PHP_EOL;
                return false;
        }
        return $this->SendAPIData($APIData);
    }

    /**
     * SetSleep
     *
     * @param  int $Duration
     * @return bool
     */
    public function SetSleep(int $Duration): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        if ($this->OnkyoZone->thisZone != \OnkyoAVR\ONKYO_Zone::ZoneMain) {
            echo $this->Translate('Command not available at this zone.') . PHP_EOL;
            return false;
        }
        if (($Duration < 0) || ($Duration > 0x5A)) {
            echo sprintf($this->Translate('%s out of range.'), 'Duration') . PHP_EOL;
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SLP, $Duration);
        return $this->SendAPIData($APIData);
    }

    /**
     * SetCenterLevel
     *
     * @param  float $Level
     * @return bool
     */
    public function SetCenterLevel(float $Level): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::CTL, $Level);
        return $this->SendAPIData($APIData);
    }

    /**
     * SetSubwooferLevel
     *
     * @param  float $Level
     * @return bool
     */
    public function SetSubwooferLevel(float $Level): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SWL, $Level);
        return $this->SendAPIData($APIData);
    }

    /**
     * SetSubwoofer2Level
     *
     * @param  float $Level
     * @return bool
     */
    public function SetSubwoofer2Level(float $Level): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SW2, $Level);
        return $this->SendAPIData($APIData);
    }

    /**
     * SetDisplayMode
     *
     * @param  int $Mode
     * @return bool
     */
    public function SetDisplayMode(int $Mode): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::DIF, $Mode);
        return $this->SendAPIData($APIData);
    }

    /**
     * SetDisplayDimmer
     *
     * @param  int $Level
     * @return bool
     */
    public function SetDisplayDimmer(int $Level): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::DIM, $Level);
        return $this->SendAPIData($APIData);
    }

    /**
     * GetAudioInformation
     *
     * @return false|array
     */
    public function GetAudioInformation(): false|array
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::IFA, \OnkyoAVR\ISCP_API_Commands::Request);
                break;
            default:
                echo $this->Translate('Command not available at this zone.') . PHP_EOL;

                return false;
        }
        $ret = $this->Send($APIData);
        if ($ret === null) {
            return false;
        }
        $Keys = [
            'Audio Input Port',
            'Input Signal Format',
            'Input Sampling Frequency',
            'Input Signal Channel',
            'Listening Mode',
            'Output Signal Channel',
            'Output Sampling Frequency',
            'PQLS',
            'Auto Phase Control Current Delay',
            'Auto Phase Control Phase',
            'Upmix Mode',
        ];
        $Values = explode(',', $ret);
        if (count($Values) > count($Keys)) {
            array_splice($Values, count($Keys));
        } else {
            $Values = array_pad($Values, count($Keys), '');
        }
        return array_combine($Keys, $Values);
    }

    /**
     * GetVideoInformation
     *
     * @return false|array
     */
    public function GetVideoInformation(): false|array
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::IFV, \OnkyoAVR\ISCP_API_Commands::Request);
                break;
            default:
                echo $this->Translate('Command not available at this zone.') . PHP_EOL;
                return false;
        }
        $ret = $this->Send($APIData);
        if ($ret === null) {
            return false;
        }
        $Keys = [
            'Video Input Port',
            'Input Resolution',
            'Input RGB/YCbCr',
            'Input Color Depth',
            'Video Output Port',
            'Output Resolution',
            'Output RGB/YCbCr',
            'Output Color Depth',
            'Picture Mode',
        ];
        $Values = explode(',', $ret);
        if (count($Values) > count($Keys)) {
            array_splice($Values, count($Keys));
        } else {
            $Values = array_pad($Values, count($Keys), '');
        }
        return array_combine($Keys, $Values);
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
            if ($this->CheckZone()) {
                if ($this->HasActiveParent()) {
                    $this->RequestProfile();
                    $this->RequestZoneState();
                }
            }
        }
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
        if (!$this->CheckZone()) {
            return;
        }
        if ($Ident == self::AttributeListeningModes) {
            $this->SetListeningModeActive((string) $Value);
            return;
        }
        if ($Ident == 'LMD2') {
            $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::LMD, $this->LMDList[$Value]['Code'], false);
            $this->Send($APIData);
            return;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data($Ident, $Value);
        $this->SendAPIData($APIData);
    }

    /**
     * CheckZone
     *
     * @return bool
     */
    private function CheckZone(): bool
    {
        if ($this->OnkyoZone->thisZone == \OnkyoAVR\ONKYO_Zone::None) {
            $this->SendDebug('Error', $this->Translate('Zone not set.'), 0);
            return false;
        }
        return true;
    }

    /**
     * sDecHex
     *
     * @param  int $d
     * @return string
     */
    private function sDecHex(int $d): string
    {
        return ($d < 0) ? ('-' . strtoupper(dechex(-$d))) : ($d == 0 ? '00' : '+' . strtoupper(dechex($d)));
    }

    /**
     * sHexDec
     *
     * @param  string $h
     * @return int
     */
    private function sHexDec(string $h): int
    {
        return ($h[0] === '-') ? -(hexdec(substr($h, 1))) : (($h[0] === '+') ? (hexdec(substr($h, 1))) : hexdec($h));
    }

    /**
     * UpdateVariable
     *
     * @param  \OnkyoAVR\ISCP_API_Data $APIData
     * @return void
     */
    private function UpdateVariable(\OnkyoAVR\ISCP_API_Data $APIData): void
    {
        if ($APIData->Data == 'N/A') {
            return;
        }
        $Mapping = $APIData->GetMapping();
        if ($Mapping == null) {
            return;
        }
        if (!$Mapping->IsVariable) {
            return;
        }
        $Profile = $this->GetPresentation($Mapping->Profile);
        if ($Mapping->VarType == \OnkyoAVR\IPSVarType::vtDualInteger) {
            $Prefix = substr($APIData->Data, 0, 1);
            $this->MaintainVariable($APIData->APICommand . $Mapping->ValuePrefix[$Prefix], $this->Translate($Mapping->VarName[$Prefix]), \OnkyoAVR\IPSVarType::vtInteger, $Profile, 0, true);
            if ($Mapping->EnableAction) {
                $this->EnableAction($APIData->APICommand . $Mapping->ValuePrefix[$Prefix]);
            }
            $Value = $Mapping->ValueMapping[substr($APIData->Data, 1, 2)];
            $this->SetValueInteger($APIData->APICommand . $Mapping->ValuePrefix[$Prefix], $Value);
            if (strlen($APIData->Data) > 3) {
                $Prefix = substr($APIData->Data, 3, 1);
                $this->MaintainVariable($APIData->APICommand . $Mapping->ValuePrefix[$Prefix], $this->Translate($Mapping->VarName[$Prefix]), \OnkyoAVR\IPSVarType::vtInteger, $Profile, 0, true);
                if ($Mapping->EnableAction) {
                    $this->EnableAction($APIData->APICommand . $Mapping->ValuePrefix[$Prefix]);
                }
                $Value = $Mapping->ValueMapping[substr($APIData->Data, 4, 2)];
                $this->SetValueInteger($APIData->APICommand . $Mapping->ValuePrefix[$Prefix], $Value);
            }

            return;
        }

        $this->MaintainVariable($APIData->APICommand, $this->Translate($Mapping->VarName), $Mapping->VarType, $Profile, 0, true);
        if ($Mapping->EnableAction) {
            $this->EnableAction($APIData->APICommand);
        }
        switch ($Mapping->VarType) {
            case \OnkyoAVR\IPSVarType::vtBoolean:
                $this->SetValueBoolean($APIData->APICommand, \OnkyoAVR\ISCP_API_Commands::$BoolValueMapping[$APIData->Data]);
                break;
            case \OnkyoAVR\IPSVarType::vtFloat:
                if (is_string($Mapping->ValueMapping)) {
                    switch ($Mapping->ValueMapping) {
                        case 'Level':
                            $Value = $this->sHexDec($APIData->Data);
                            $MyProfile = $this->ToneProfile;
                            if (array_key_exists($Mapping->Profile, $MyProfile)) {
                                if ($MyProfile[$Mapping->Profile][2] < 1) {
                                    $Value = $Value / 2;
                                }
                            }
                            break;
                        default:
                            $Value = hexdec($APIData->Data);
                            break;
                    }
                } else {
                    $Value = $APIData->Data / 100;
                }
                $this->SetValueFloat($APIData->APICommand, $Value);
                break;
            case \OnkyoAVR\IPSVarType::vtInteger:
                if (is_array($Mapping->ValueMapping)) {
                    if (array_key_exists($APIData->Data, $Mapping->ValueMapping)) {
                        $Value = $Mapping->ValueMapping[$APIData->Data];
                    } else {
                        $Value = hexdec($APIData->Data);
                    }
                } elseif (is_string($Mapping->ValueMapping)) {
                    switch ($Mapping->ValueMapping) {
                        case 'Level':
                            $Value = $this->sHexDec($APIData->Data);
                            break;
                        default:
                            $Value = hexdec($APIData->Data);
                            break;
                    }
                } else {
                    $Value = hexdec($APIData->Data);
                }
                $this->SetValueInteger($APIData->APICommand, $Value);
                if ($Mapping->Profile === \OnkyoAVR\IPSProfiles::ptListeningMode) {
                    $this->LearnListeningMode($Value);
                }
                break;
            case \OnkyoAVR\IPSVarType::vtString:
                $Value = $APIData->Data;
                if ($Mapping->ValueMapping != null) {
                    $Value = implode("\r\n", array_filter(explode($Mapping->ValueMapping, $APIData->Data)));
                }
                $this->SetValueString($APIData->APICommand, $Value);
                break;
        }
        // refresh
        switch ($APIData->APICommand) {
            case \OnkyoAVR\ISCP_API_Commands::SLI:
                if ($this->IsPowerOn()) {
                    $this->RequestState(\OnkyoAVR\ISCP_API_Commands::IFV);
                    $this->RequestState(\OnkyoAVR\ISCP_API_Commands::IFA);
                }
                break;
            case \OnkyoAVR\ISCP_API_Commands::SLA:
                if ($this->IsPowerOn()) {
                    $this->RequestState(\OnkyoAVR\ISCP_API_Commands::IFA);
                }
                break;
        }
    }

    /**
     * SendPower
     *
     * @param  bool $Value
     * @return bool
     */
    private function SendPower(bool $Value): bool
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::PWR, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::ZPW, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::PW3, $Value);
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::PW4, $Value);
                break;
        }

        return $this->SendAPIData($APIData);
    }

    /**
     * RequestProfile
     *
     * Liest die gerätespezifischen Wertebereiche und Auswahllisten (Eingänge, Lautstärke, Tonwerte, Hörmodi)
     * und stellt die Darstellungen der vorhandenen Variablen darauf um.
     *
     * @return void
     */
    private function RequestProfile(): void
    {
        $zone = $this->OnkyoZone->thisZone;
        $Associations = [];
        // SLI // SLZ // SL3 // SL4
        $APIDataSelectorList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::SelectorList);
        $ResultDataSelectorList = $this->Send($APIDataSelectorList);
        if (count($ResultDataSelectorList) > 0) {
            $Association = [];
            foreach ($ResultDataSelectorList as $Value => $SelectorProfileData) {
                if (((int) $SelectorProfileData['Zone'] & $zone) == $zone) {
                    $Association[] = [$Value, $SelectorProfileData['Name'], '', -1];
                }
            }
        } else {
            $Association = \OnkyoAVR\IPSProfiles::$ProfilAssociations[\OnkyoAVR\IPSProfiles::ptSelectInput];
        }
        $Associations[\OnkyoAVR\IPSProfiles::ptSelectInput] = $Association;

        // MVL // ZVL // VL3 // VL4
        $APIDataZoneList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ZoneList);
        $ResultDataZoneList = $this->Send($APIDataZoneList);
        $VolMax = 0;
        $VolStep = 0;
        if (array_key_exists($zone, $ResultDataZoneList)) {
            $VolMax = (int) $ResultDataZoneList[$zone]['VolMax'];
            $VolStep = (float) $ResultDataZoneList[$zone]['VolStep'];
        }
        // Liefert das Gerät keinen gültigen Bereich (0), gelten die Standardwerte
        if ($VolMax < 1) {
            $VolMax = 80;
        }
        if ($VolStep < 1) {
            $VolStep = 1;
        }
        $ProfileData = $this->ToneProfile;
        $ProfileData[\OnkyoAVR\IPSProfiles::ptVolume] = [0, $VolMax, $VolStep, ' %'];

        // PMB
        if ($zone == \OnkyoAVR\ONKYO_Zone::ZoneMain) {
            $APIDataPhaseMatchingBass = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::PhaseMatchingBass);
            $this->PhaseMatchingBass = $this->Send($APIDataPhaseMatchingBass);
        } else {
            $this->PhaseMatchingBass = false;
        }
        // Tone
        $APIDataProfileList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ProfileList);
        $ResultDataProfileList = $this->Send($APIDataProfileList);
        foreach ($ResultDataProfileList as $Name => $Values) {
            if (((int) $Values['Zone'] & $zone) != $zone) {
                continue;
            }
            if (array_key_exists($Name, \OnkyoAVR\IPSProfiles::$ProfileListIndexToProfile)) {
                $ProfileName = \OnkyoAVR\IPSProfiles::$ProfileListIndexToProfile[$Name];
                unset($Values['Zone']);
                $ProfileData[$ProfileName] = $Values;
            }
        }
        $this->ToneProfile = $ProfileData;
        $this->SendDebug('New Profile', $ProfileData, 0);

        //LMD Tasten
        if ($zone == \OnkyoAVR\ONKYO_Zone::ZoneMain) {
            if ($this->MyConfig['LMD2']) {
                $APIDataLMDList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::LMDList);
                $ResultDataLMDList = $this->Send($APIDataLMDList);
                if (count($ResultDataLMDList) > 0) {
                    $Association = [];
                    foreach ($ResultDataLMDList as $Value => $LMDProfileData) {
                        $Association[] = [$Value, $LMDProfileData['Name'], '', -1];
                    }
                } else {
                    $Association = \OnkyoAVR\IPSProfiles::$ProfilAssociations[\OnkyoAVR\IPSProfiles::ptSelectLMD];
                }
                $Associations[\OnkyoAVR\IPSProfiles::ptSelectLMD] = $Association;
                $this->AssociationProfile = $Associations;
                $this->RegisterVariableInteger('LMD2', $this->Translate('Listening Mode'), $this->GetPresentation(\OnkyoAVR\IPSProfiles::ptSelectLMD), 0);
                $this->EnableAction('LMD2');
                $this->LMDList = $ResultDataLMDList;
            } else {
                $this->UnregisterVariable('LMD2');
            }
        }
        $this->AssociationProfile = $Associations;
        $this->RefreshVariablePresentations();
    }

    /**
     * SetListValues
     *
     * Setzt die Werte einer Liste im Konfigurationsformular (sucht die Liste rekursiv).
     *
     * @param  array &$Elements Elemente des Formulars.
     * @param  string $Name Name der Liste.
     * @param  array $Values Zeilen der Liste.
     * @return void
     */
    private function SetListValues(array &$Elements, string $Name, array $Values): void
    {
        foreach ($Elements as &$Element) {
            if (isset($Element['name']) && ($Element['name'] == $Name)) {
                $Element['values'] = $Values;
            }
            if (isset($Element['items'])) {
                $this->SetListValues($Element['items'], $Name, $Values);
            }
        }
    }

    /**
     * GetSavedListeningModes
     *
     * @return string JSON der gespeicherten Auswahl der Hörmodi (leere Liste = Standard).
     */
    private function GetSavedListeningModes(): string
    {
        $Saved = $this->ReadAttributeArray(self::AttributeListeningModes);
        return json_encode(is_array($Saved) ? $Saved : []);
    }

    /**
     * SetListeningModeActive
     *
     * Wird von der Liste im Aktionsbereich des Konfigurationsformulars aufgerufen (onEdit).
     * Schaltet einen Hörmodus in der Auswahl der angebotenen Modi an oder aus und stellt die Darstellung um.
     *
     * @param  string $Value JSON mit 'Code' (hexadezimal) und 'Active'.
     * @return void
     */
    private function SetListeningModeActive(string $Value): void
    {
        $Row = json_decode($Value, true);
        $Names = \OnkyoAVR\ListeningModes::GetNames();
        if ((!is_array($Row)) || (!isset($Row['Code'])) || (!array_key_exists((int) hexdec((string) $Row['Code']), $Names))) {
            echo $this->Translate('Invalid Value.') . PHP_EOL;
            return;
        }
        $Code = (int) hexdec((string) $Row['Code']);
        // Auswahl vollständig aufbauen (Standard für Modi, die noch nicht gespeichert sind) und den einen Modus ändern
        $Active = array_flip(\OnkyoAVR\ListeningModes::GetActiveCodes($this->GetSavedListeningModes()));
        if (!empty($Row['Active'])) {
            $Active[$Code] = true;
        } else {
            unset($Active[$Code]);
        }
        $Saved = [];
        foreach (array_keys($Names) as $Mode) {
            $Saved[] = ['Code' => sprintf('%02X', $Mode), 'Active' => array_key_exists($Mode, $Active)];
        }
        $this->WriteAttributeArray(self::AttributeListeningModes, $Saved);
        $this->SendDebug('Listening mode', sprintf('%02X', $Code) . ' ' . (array_key_exists($Code, $Active) ? 'offered' : 'hidden'), 0);
        $this->RefreshVariablePresentations();
    }

    /**
     * GetSeenListeningModes
     *
     * @return int[] Codes der Hörmodi, die das Gerät schon gemeldet hat.
     */
    private function GetSeenListeningModes(): array
    {
        $Seen = $this->ReadAttributeArray(self::AttributeSeenListeningModes);
        return is_array($Seen) ? array_map('intval', $Seen) : [];
    }

    /**
     * LearnListeningMode
     *
     * Merkt sich einen Hörmodus, den das Gerät gemeldet hat. Er wird danach in der Darstellung angeboten.
     *
     * @param  int $Code Code des Hörmodus.
     * @return void
     */
    private function LearnListeningMode(int $Code): void
    {
        if (!array_key_exists($Code, \OnkyoAVR\ListeningModes::GetNames())) {
            return;
        }
        $Seen = $this->GetSeenListeningModes();
        if (in_array($Code, $Seen, true)) {
            return;
        }
        $Seen[] = $Code;
        sort($Seen);
        $this->WriteAttributeArray(self::AttributeSeenListeningModes, $Seen);
        $this->SendDebug('Listening mode learned', sprintf('%02X', $Code), 0);
        if (!in_array($Code, \OnkyoAVR\ListeningModes::GetActiveCodes($this->GetSavedListeningModes()), true)) {
            $this->RefreshVariablePresentations(); // neuer Modus in der Auswahl
        }
    }
    /**
     * GetPresentation
     *
     * Liefert die Parameter der Darstellung für einen Profil-Schlüssel aus dem Mapping,
     * mit den vom Gerät gelesenen Wertebereichen und Auswahllisten.
     *
     * @param  string $Profile Profil-Schlüssel aus \OnkyoAVR\IPSProfiles.
     * @return array Parameter der Darstellung.
     */
    private function GetPresentation(string $Profile): array
    {
        $Associations = $this->AssociationProfile;
        if ($Profile === \OnkyoAVR\IPSProfiles::ptListeningMode) {
            $Current = null;
            foreach ([\OnkyoAVR\ISCP_API_Commands::LMD, \OnkyoAVR\ISCP_API_Commands::LMZ] as $Ident) {
                if ($this->FindIDForIdent($Ident)) {
                    $Current = $this->GetValue($Ident);
                }
            }
            $Associations[$Profile] = \OnkyoAVR\ListeningModes::GetAssociations($this->GetSavedListeningModes(), $this->GetSeenListeningModes(), $Current);
        }
        return \OnkyoAVR\IPSProfiles::GetPresentation(
            $Profile,
            $this->ToneProfile,
            $Associations,
            function (string $Text): string
            {
                return $this->Translate($Text);
            }
        );
    }

    /**
     * RefreshVariablePresentations
     *
     * Setzt die Darstellung aller vorhandenen Statusvariablen dieser Zone neu.
     * Nötig nach einem Update (Umstellung von Profilen) und wenn sich gerätespezifische Wertebereiche ändern.
     *
     * @return void
     */
    private function RefreshVariablePresentations(): void
    {
        foreach (\OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone] as $ApiCmd) {
            $Mapping = \OnkyoAVR\ISCP_API_Data_Mapping::GetMapping($ApiCmd);
            if (($Mapping === null) || (!$Mapping->IsVariable)) {
                continue;
            }
            $Presentation = $this->GetPresentation($Mapping->Profile);
            if ($Mapping->VarType == \OnkyoAVR\IPSVarType::vtDualInteger) {
                $Variables = [];
                foreach ($Mapping->ValuePrefix as $Prefix => $Index) {
                    $Variables[$ApiCmd . $Index] = [$Mapping->VarName[$Prefix], \OnkyoAVR\IPSVarType::vtInteger];
                }
            } else {
                $Variables = [$ApiCmd => [$Mapping->VarName, $Mapping->VarType]];
            }
            foreach ($Variables as $Ident => [$Name, $Type]) {
                if (!$this->FindIDForIdent((string) $Ident)) {
                    continue;
                }
                $this->MaintainVariable((string) $Ident, $this->Translate($Name), $Type, $Presentation, 0, true);
                if ($Mapping->EnableAction) {
                    $this->EnableAction((string) $Ident);
                }
            }
        }
        if ($this->FindIDForIdent('LMD2')) {
            $this->RegisterVariableInteger('LMD2', $this->Translate('Listening Mode'), $this->GetPresentation(\OnkyoAVR\IPSProfiles::ptSelectLMD), 0);
            $this->EnableAction('LMD2');
        }
    }

    /**
     * RemoveLegacyProfiles
     *
     * Entfernt die Variablenprofile 'Onkyo.*' früherer Versionen, nachdem alle Variablen auf Darstellungen umgestellt wurden.
     * Fremde Profile (z.B. '~Switch') werden nie angefasst.
     *
     * @return void
     */
    private function RemoveLegacyProfiles(): void
    {
        $Profiles = array_merge(
            array_keys(\OnkyoAVR\IPSProfiles::$ProfilAssociations),
            array_keys(\OnkyoAVR\IPSProfiles::$ProfilInteger),
            array_keys(\OnkyoAVR\IPSProfiles::$ProfilFloat),
            [
                'NetRadioPreset.Onkyo',
                'SpeakerLayout.Onkyo',
                'ToneOffset.Onkyo',
                'Sleep.Onkyo',
                'DisplayMode.Onkyo',
                'DisplayDimmer.Onkyo',
                'SelectInput.Onkyo',
                'SelectInputAudio.Onkyo',
                'HDMIOutput.Onkyo',
                'HDMIAudioOutput.Onkyo',
                'VideoResolution.Onkyo',
                'VideoWideMode.Onkyo',
                'VideoPictureMode.Onkyo',
                'LMD.Onkyo',
                'LateNight.Onkyo',
                'Audyssey.Onkyo',
                'AudysseyDynamic.Onkyo',
                'DolbyVolume.Onkyo',
                'RadioPreset.Onkyo',
            ]
        );
        foreach ($Profiles as $Profile) {
            if (strpos($Profile, '%d')) {
                $Profile = sprintf($Profile, $this->InstanceID);
            }
            // Nur eigene Profile entfernen
            if ((strpos($Profile, 'Onkyo.') !== 0) && (substr($Profile, -6) !== '.Onkyo')) {
                continue;
            }
            $this->UnregisterProfile($Profile);
        }
    }

    /**
     * RequestZoneState
     *
     * @return void
     */
    private function RequestZoneState(): void
    {
        // Schleife von allen CMDs welche als Variable in dieser Zone sind.
        $MyProperties = $this->MyConfig;
        // Optionale Abfragen: Ausgaben (Fehlermeldungen von Send) nur ins Debug, nicht an den Aufrufer.
        $Level = ob_get_level();
        ob_start();
        try {
            $this->RequestZoneStateLoop($MyProperties);
        } finally {
            while (ob_get_level() > $Level) {
                $Output = (string) ob_get_clean();
                if (trim($Output) != '') {
                    $this->SendDebug('Request', trim($Output), 0);
                }
            }
        }
    }

    /**
     * IsPowerOn
     *
     * Liefert den zuletzt gemeldeten Einschaltzustand der Zone aus der eigenen Statusvariable.
     * Ohne Variable oder ohne gemeldeten Wert gilt die Zone als ausgeschaltet.
     *
     * @return bool
     */
    private function IsPowerOn(): bool
    {
        // Der Power-Befehl steht in jeder Zone (PWR, ZPW, PW3, PW4) an erster Stelle.
        $PowerIdent = \OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone][0] ?? '';
        if (($PowerIdent === '') || (@$this->GetIDForIdent($PowerIdent) === false)) {
            return false;
        }
        return (bool) $this->GetValue($PowerIdent);
    }

    /**
     * RequestZoneStateLoop
     *
     * @param  array $MyProperties
     * @return void
     */
    private function RequestZoneStateLoop(array $MyProperties): void
    {
        foreach (\OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone] as $ApiCmd) {
            if (array_key_exists($ApiCmd, $MyProperties)) {
                if ($MyProperties[$ApiCmd] === false) {
                    continue;
                }
            }
            if ($ApiCmd == \OnkyoAVR\ISCP_API_Commands::PMB) {
                if ($this->PhaseMatchingBass == false) {
                    continue;
                }
            }
            // Audio- und Videoinformation beantwortet das Gerät nur, wenn es eingeschaltet ist.
            if ((($ApiCmd == \OnkyoAVR\ISCP_API_Commands::IFA) || ($ApiCmd == \OnkyoAVR\ISCP_API_Commands::IFV)) && (!$this->IsPowerOn())) {
                continue;
            }
            $APIData = new \OnkyoAVR\ISCP_API_Data($ApiCmd, \OnkyoAVR\ISCP_API_Commands::Request);
            $Mapping = $APIData->GetMapping();
            if ($Mapping !== null) {
                if ($Mapping->RequestValue) {
                    $ResultData = $this->Send($APIData);
                    if ($ResultData === null) {
                        $VarName = \OnkyoAVR\ISCP_API_Data_Mapping::GetMapping($ApiCmd)->VarName;
                        if (is_array($VarName)) {
                            $VarName = implode(' & ', array_values($VarName));
                        }
                        // Optionale Abfrage, nicht jedes Gerät unterstützt jeden Befehl: nur Debug, kein Fehler.
                        $this->SendDebug('Request', sprintf($this->Translate('Error on read %s. Maybe your Device not support %s.'), $ApiCmd, $this->Translate($VarName)), 0);
                        continue;
                    }
                    $APIData->Data = $ResultData;
                    $this->UpdateVariable($APIData);
                }
            }
        }
    }

    /**
     * SendAPIData
     *
     * @param  \OnkyoAVR\ISCP_API_Data $APIData
     * @return bool
     */
    private function SendAPIData(\OnkyoAVR\ISCP_API_Data $APIData): bool
    {
        if (strlen($APIData->APICommand) == 4) {
            $SubIndex = substr($APIData->APICommand, -1);
            $APIData->APICommand = substr($APIData->APICommand, 0, 3);
        }

        try {
            if (!$this->OnkyoZone->CmdAvailable($APIData->APICommand)) {
                throw new Exception('Command not available at this zone.', E_USER_NOTICE);
            }
            $Mapping = $APIData->GetMapping();

            switch ($Mapping->VarType) {
                case \OnkyoAVR\IPSVarType::vtBoolean:
                    $APIData->Data = \OnkyoAVR\ISCP_API_Commands::$BoolValueMapping[$APIData->Data];
                    break;
                case \OnkyoAVR\IPSVarType::vtFloat:
                    if (is_string($Mapping->ValueMapping)) {
                        switch ($Mapping->ValueMapping) {
                            case 'Level':
                                $MyProfile = $this->ToneProfile;
                                if (array_key_exists($Mapping->Profile, $MyProfile)) {
                                    if ($MyProfile[$Mapping->Profile][2] < 1) {
                                        $APIData->Data = (int) ($APIData->Data * 2);
                                    } else {
                                        $APIData->Data = (int) $APIData->Data;
                                    }
                                }
                                $APIData->Data = $this->sDecHex($APIData->Data);
                                break;
                            default:
                                $APIData->Data = dechex($APIData->Data);
                                break;
                        }
                    } else {
                        $APIData->Data = $APIData->Data * 100;
                    }
                    break;
                case \OnkyoAVR\IPSVarType::vtInteger:
                    if (is_array($Mapping->ValueMapping)) {
                        $ValueMapping = array_flip($Mapping->ValueMapping);
                        if (array_key_exists($APIData->Data, $ValueMapping)) {
                            $APIData->Data = $ValueMapping[$APIData->Data];
                        } else {
                            $APIData->Data = sprintf('%02X', $APIData->Data);
                        }
                    } elseif (is_string($Mapping->ValueMapping)) {
                        switch ($Mapping->ValueMapping) {
                            case 'Level':
                                $APIData->Data = $this->sDecHex($APIData->Data);
                                break;
                            default:
                                $APIData->Data = dechex($APIData->Data);
                                break;
                        }
                    } else {
                        $APIData->Data = sprintf('%02X', $APIData->Data);
                    }
                    break;
                case \OnkyoAVR\IPSVarType::vtDualInteger:
                    $ISCP_ValuePrefix = array_flip($Mapping->ValuePrefix)[$SubIndex];
                    $ValueMapping = array_flip($Mapping->ValueMapping);
                    if (array_key_exists($APIData->Data, $ValueMapping)) {
                        $APIData->Data = $ISCP_ValuePrefix . $ValueMapping[$APIData->Data];
                    } else {
                        $APIData->Data = $ISCP_ValuePrefix . sprintf('%02X', $APIData->Data);
                    }
                    break;
                default:
                    throw new Exception('Unknown VariableType.', E_USER_NOTICE);
            }

            $ResultData = $this->Send($APIData);

            if ($ResultData == 'N/A') {
                throw new Exception('Command (temporally) not available.', E_USER_NOTICE);
            }
            $this->SendDebug('SendAPIData Result', $ResultData, 0);
            switch ($Mapping->VarType) {
                case \OnkyoAVR\IPSVarType::vtBoolean:
                case \OnkyoAVR\IPSVarType::vtInteger:
                case \OnkyoAVR\IPSVarType::vtFloat:
                    // Das Gerät bestätigt mit dem gesendeten Wert, oder bei Textwerten wie 'OFF' mit dem Zahlenwert (z.B. '00')
                    $Expected = [$APIData->Data];
                    if (is_array($Mapping->ValueMapping) && array_key_exists($APIData->Data, $Mapping->ValueMapping)) {
                        $Expected[] = sprintf('%02X', $Mapping->ValueMapping[$APIData->Data]);
                    }
                    if (!in_array($ResultData, $Expected)) {
                        if (in_array($APIData->APICommand, [\OnkyoAVR\ISCP_API_Commands::PWR, \OnkyoAVR\ISCP_API_Commands::ZPW, \OnkyoAVR\ISCP_API_Commands::PW3, \OnkyoAVR\ISCP_API_Commands::PW4])) {
                            // Beim Ausschalten antwortet das Gerät zuerst noch mit dem alten Zustand, der neue Zustand wird danach gemeldet
                            $this->SendDebug('Power', 'New state is reported by the device later', 0);
                            return true;
                        }
                        throw new Exception('Value not available.', E_USER_NOTICE);
                    }
                    break;
                case \OnkyoAVR\IPSVarType::vtDualInteger:
                    if (strpos($ResultData, $APIData->Data) === false) {
                        throw new Exception('Value not available.', E_USER_NOTICE);
                    }
                    break;
            }
            $APIData->Data = $ResultData;
            $this->UpdateVariable($APIData);
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            echo $this->Translate($exc->getMessage()) . PHP_EOL;
            return false;
        }
        return true;
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
                $this->SendDebug('Response' . $APIData->APICommand, 'No answer', 0);
                return null;
            }
            $result = unserialize($ret);
            $this->SendDebug('Response' . $APIData->APICommand, $result, 0);
            return $result;
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            echo $exc->getMessage() . PHP_EOL;
            return null;
        }
    }
}
