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
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property int $ParentID Die InstanzeID des IO-Parent
 * @property \OnkyoAVR\ONKYO_Zone $OnkyoZone
 * @property bool $PhaseMatchingBass
 * @property array $ToneProfile
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
 * @method int RegisterParent()
 */
class OnkyoAVR extends IPSModule
{
    use \OnkyoAVR\DebugHelper,
        \OnkyoAVR\BufferHelper,
        \OnkyoAVR\InstanceStatus,
        \OnkyoAVR\VariableHelper,
        \OnkyoAVR\VariableProfileHelper {
            \OnkyoAVR\InstanceStatus::MessageSink as IOMessageSink;
            \OnkyoAVR\InstanceStatus::RequestAction as IORequestAction;
        }

    /**
     * Create
     *
     * @return void
     */
    public function Create()
    {
        parent::Create();
        $this->ConnectParent(\OnkyoAVR\GUID::Splitter);
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
        $this->LMDList = [];
        $this->SetReceiveDataFilter('.*"APICommand":"NOTING".*');
        $this->SendDebug('FILTER', 'NOTHING', 0);
    }

    /**
     * Destroy
     *
     * @return void
     */
    public function Destroy()
    {
        if (IPS_GetKernelRunlevel() != KR_READY) {
            parent::Destroy();
            return;
        }
        if (!IPS_InstanceExists($this->InstanceID)) {
            foreach (array_keys(\OnkyoAVR\IPSProfiles::$ProfilAssociations) as $Profile) {
                if (strpos($Profile, '%d')) {
                    $Profile = sprintf($Profile, $this->InstanceID);
                }
                $this->UnregisterProfile($Profile);
            }
            foreach (array_keys(\OnkyoAVR\IPSProfiles::$ProfilInteger) as $Profile) {
                if (strpos($Profile, '%d')) {
                    $Profile = sprintf($Profile, $this->InstanceID);
                }
                $this->UnregisterProfile($Profile);
            }
            foreach (array_keys(\OnkyoAVR\IPSProfiles::$ProfilFloat) as $Profile) {
                if (strpos($Profile, '%d')) {
                    $Profile = sprintf($Profile, $this->InstanceID);
                }
                $this->UnregisterProfile($Profile);
            }
        }
        parent::Destroy();
    }

    /**
     * ApplyChanges
     *
     * @return void
     */
    public function ApplyChanges()
    {
        $this->SetReceiveDataFilter('.*"APICommand":"NOTING".*');
        $this->SendDebug('FILTER', 'NOTHING', 0);
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        $this->LMDList = [];
        parent::ApplyChanges();
        foreach (\OnkyoAVR\IPSProfiles::$ProfilAssociations as $Profile => $Association) {
            if (strpos($Profile, '%d')) {
                $Profile = sprintf($Profile, $this->InstanceID);
            }
            foreach ($Association as &$AssociationItem) {
                $AssociationItem[1] = $this->Translate($AssociationItem[1]);
            }
            $this->RegisterProfileIntegerEx($Profile, '', '', '', $Association);
        }
        foreach (\OnkyoAVR\IPSProfiles::$ProfilInteger as $Profile => $Size) {
            if (strpos($Profile, '%d')) {
                $Profile = sprintf($Profile, $this->InstanceID);
            }
            $this->RegisterProfileInteger($Profile, '', '', $Size[3], $Size[0], $Size[1], $Size[2]);
        }
        foreach (\OnkyoAVR\IPSProfiles::$ProfilFloat as $Profile => $Size) {
            if (strpos($Profile, '%d')) {
                $Profile = sprintf($Profile, $this->InstanceID);
            }
            $this->RegisterProfileFloat($Profile, '', '', '', $Size[0], $Size[1], $Size[2], ($Size[2] < 1 ? 1 : 0));
        }
        $ProfileData = [];
        foreach (\OnkyoAVR\IPSProfiles::$ProfileListIndexToProfile as $ProfileName) {
            if (array_key_exists($ProfileName, \OnkyoAVR\IPSProfiles::$ProfilInteger)) {
                $ProfileData[$ProfileName] = \OnkyoAVR\IPSProfiles::$ProfilInteger[$ProfileName];
            }
            if (array_key_exists($ProfileName, \OnkyoAVR\IPSProfiles::$ProfilFloat)) {
                $ProfileData[$ProfileName] = \OnkyoAVR\IPSProfiles::$ProfilFloat[$ProfileName];
            }
        }
        $this->SendDebug('Default Profile', $ProfileData, 0);
        $this->ToneProfile = $ProfileData;
        $OldZone = $this->OnkyoZone->thisZone;
        $NewZone = $this->ReadPropertyInteger('Zone');
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone($NewZone);
        // Power, Mute, Volume, Input überführen in neuen Ident
        if ((($OldZone != 0) && ($NewZone != 0)) && ($OldZone != $NewZone)) {
            $OldZoneIdents = \OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$OldZone];
            $NewZoneIdents = \OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone];
            for ($index = 0; $index < 4; $index++) {
                $VarId = @$this->GetIDForIdent($OldZoneIdents[$index]);
                if ($VarId > 0) {
                    IPS_SetIdent($VarId, $NewZoneIdents[$index]);
                }
            }
        }

        $MyPropertys = json_decode(IPS_GetConfiguration($this->InstanceID), true);
        $this->PhaseMatchingBass = true;
        $this->SetSummary($this->OnkyoZone->GetName());
        $APICommands = $this->OnkyoZone->GetAPICommands();
        if (count($APICommands) > 0) {
            foreach ($APICommands as $APICommand) {
                if (array_key_exists($APICommand, $MyPropertys)) {
                    if ($MyPropertys[$APICommand] === false) {
                        continue;
                    }
                }
                $Lines[] = '.*"APICommand":"' . $APICommand . '".*';
            }
            $Line = implode('|', $Lines);
            $this->SetReceiveDataFilter('(' . $Line . ')');
            $this->SendDebug('FILTER', $Line, 0);
        }
        unset($MyPropertys['Zone']);

        // Abgewählte Variablen entfernen und Variablen welche nicht in dieser Zone sind
        foreach ($MyPropertys as $Key => &$Value) {
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
        $this->MyConfig = $MyPropertys;
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
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);

        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;
        }
    }

    /**
     * GetConfigurationForm
     *
     * @return string
     */
    public function GetConfigurationForm()
    {
        return file_get_contents(__DIR__ . '/form_' . $this->OnkyoZone->thisZone . '.json');
    }

    /**
     * RequestAction
     *
     * @param  string $Ident
     * @param  mixed $Value
     * @return void
     */
    public function RequestAction($Ident, $Value)
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return;
        }
        if (!$this->CheckZone()) {
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
     * RequestState
     *
     * @param  string $Ident
     * @return void
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
            trigger_error($this->Translate('Command not available at this zone.'), E_USER_NOTICE);
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
                trigger_error($this->Translate('Command not available at this zone.'), E_USER_NOTICE);
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
            trigger_error($this->Translate('Command not available at this zone.'), E_USER_NOTICE);
            return false;
        }
        if (($Duration < 0) || ($Duration > 0x5A)) {
            trigger_error(sprintf($this->Translate('%s out of range.'), 'Duration'), E_USER_NOTICE);
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
     * @return array|false
     */
    public function GetAudioInformation()
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::IFA, \OnkyoAVR\ISCP_API_Commands::Request);
                break;
            default:
                trigger_error($this->Translate('Command not available at this zone.'), E_USER_NOTICE);

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
     * @return array|false
     */
    public function GetVideoInformation()
    {
        if (!$this->CheckZone()) {
            return false;
        }
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::IFV, \OnkyoAVR\ISCP_API_Commands::Request);
                break;
            default:
                trigger_error($this->Translate('Command not available at this zone.'), E_USER_NOTICE);

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
    public function ReceiveData($JSONString)
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data($JSONString);
        $this->SendDebug('ReceiveData', $APIData, 0);
        $this->UpdateVariable($APIData);
        return '';
    }

    /**
     * RequestZoneStateErrorHandler
     *
     * @param  int $errno
     * @param  string $errstr
     * @return bool
     */
    protected function RequestZoneStateErrorHandler($errno, $errstr)
    {
        return true;
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
        $this->UnregisterMessage(0, IPS_KERNELSTARTED);
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
    protected function IOChangeState($State)
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
     * CheckZone
     *
     * @return bool
     */
    private function CheckZone()
    {
        if ($this->OnkyoZone->thisZone == \OnkyoAVR\ONKYO_Zone::None) {
            $this->SendDebug('Error', $this->Translate('Zone not set.'), 0);
            return false;
        }
        return true;
    }

    /**
     * sdechex
     *
     * @param  int $d
     * @return string
     */
    private function sdechex(int $d): string
    {
        return ($d < 0) ? ('-' . strtoupper(dechex(-$d))) : ($d == 0 ? '00' : '+' . strtoupper(dechex($d)));
    }

    /**
     * shexdec
     *
     * @param  string $h
     * @return int
     */
    private function shexdec(string $h): int
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
        if (strpos($Mapping->Profile, '%d')) {
            $Profile = sprintf($Mapping->Profile, $this->InstanceID);
        } else {
            $Profile = $Mapping->Profile;
        }
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
                            $Value = $this->shexdec($APIData->Data);
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
                            $Value = $this->shexdec($APIData->Data);
                            break;
                        default:
                            $Value = hexdec($APIData->Data);
                            break;
                    }
                } else {
                    $Value = hexdec($APIData->Data);
                }
                $this->SetValueInteger($APIData->APICommand, $Value);
                break;
            case \OnkyoAVR\IPSVarType::vtString:
                $Value = $APIData->Data;
                if ($Mapping->ValueMapping != null) {
                    $Value = implode("\r\n", array_filter(explode($Mapping->ValueMapping, $APIData->Data)));
                }
                $this->SetValueString($APIData->APICommand, $Value);
                break;
        }
        // refreshs
        switch ($APIData->APICommand) {
            case \OnkyoAVR\ISCP_API_Commands::SLI:
                $this->RequestState(\OnkyoAVR\ISCP_API_Commands::IFV);
                $this->RequestState(\OnkyoAVR\ISCP_API_Commands::IFA);
                break;
            case \OnkyoAVR\ISCP_API_Commands::SLA:
                $this->RequestState(\OnkyoAVR\ISCP_API_Commands::IFA);
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
     * @return void
     */
    private function RequestProfile(): void
    {
        $zone = $this->OnkyoZone->thisZone;
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
        foreach ($Association as &$AssociationItem) {
            $AssociationItem[1] = $this->Translate($AssociationItem[1]);
        }
        $ptSelectInputProfile = sprintf(\OnkyoAVR\IPSProfiles::ptSelectInput, $this->InstanceID);
        $this->RegisterProfileIntegerEx($ptSelectInputProfile, '', '', '', $Association);

        // MVL // ZVL // VL3 // VL4
        $APIDataZoneList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ZoneList);
        $ResultDataZoneList = $this->Send($APIDataZoneList);
        if (array_key_exists($zone, $ResultDataZoneList)) {
            $Volmax = (int) $ResultDataZoneList[$zone]['Volmax'];
            $Volstep = (float) $ResultDataZoneList[$zone]['Volstep'];
        } else {
            $Volmax = 80;
            $Volstep = 1;
        }
        $ptVolumeProfile = sprintf(\OnkyoAVR\IPSProfiles::ptVolume, $this->InstanceID);
        $this->RegisterProfileInteger($ptVolumeProfile, 'Speaker', '', ' %', 0, $Volmax, $Volstep);

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
        $ProfileData = [];
        foreach ($ResultDataProfileList as $Name => $Values) {
            if (((int) $Values['Zone'] & $zone) != $zone) {
                continue;
            }
            if (array_key_exists($Name, \OnkyoAVR\IPSProfiles::$ProfileListIndexToProfile)) {
                $ProfileName = \OnkyoAVR\IPSProfiles::$ProfileListIndexToProfile[$Name];
                unset($Values['Zone']);
                $ProfileData[$ProfileName] = $Values;
                if (strpos($ProfileName, '%d')) {
                    $Profile = sprintf($ProfileName, $this->InstanceID);
                } else {
                    $Profile = $ProfileName;
                }
                if (array_key_exists($ProfileName, \OnkyoAVR\IPSProfiles::$ProfilInteger)) {
                    $this->RegisterProfileInteger($Profile, '', '', '', $Values[0], $Values[1], $Values[2]);
                }
                if (array_key_exists($ProfileName, \OnkyoAVR\IPSProfiles::$ProfilFloat)) {
                    $this->RegisterProfileFloat($Profile, '', '', '', $Values[0], $Values[1], $Values[2], ($Values[2] < 1 ? 1 : 0));
                }
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
                foreach ($Association as &$AssociationItem) {
                    $AssociationItem[1] = $this->Translate($AssociationItem[1]);
                }
                $ptSelectLMDProfile = sprintf(\OnkyoAVR\IPSProfiles::ptSelectLMD, $this->InstanceID);
                $this->RegisterProfileIntegerEx($ptSelectLMDProfile, '', '', '', $Association);
                $this->RegisterVariableInteger('LMD2', $this->Translate('Listening Mode'), $ptSelectLMDProfile, 0);
                $this->EnableAction('LMD2');
                $this->LMDList = $ResultDataLMDList;
            } else {
                $this->UnregisterVariable('LMD2');
                $ptSelectLMDProfile = sprintf(\OnkyoAVR\IPSProfiles::ptSelectLMD, $this->InstanceID);
                $this->UnregisterProfile($ptSelectLMDProfile);
            }
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
        $MyPropertys = $this->MyConfig;
        foreach (\OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone] as $ApiCmd) {
            if (array_key_exists($ApiCmd, $MyPropertys)) {
                if ($MyPropertys[$ApiCmd] === false) {
                    continue;
                }
            }
            if ($ApiCmd == \OnkyoAVR\ISCP_API_Commands::PMB) {
                if ($this->PhaseMatchingBass == false) {
                    continue;
                }
            }
            $APIData = new \OnkyoAVR\ISCP_API_Data($ApiCmd, \OnkyoAVR\ISCP_API_Commands::Request);
            $Mapping = $APIData->GetMapping();
            if ($Mapping !== null) {
                if ($Mapping->RequestValue) {
                    set_error_handler([$this, 'RequestZoneStateErrorHandler'], error_reporting());
                    $ResultData = $this->Send($APIData);
                    restore_error_handler();
                    if ($ResultData === null) {
                        $VarName = \OnkyoAVR\ISCP_API_Data_Mapping::GetMapping($ApiCmd)->VarName;
                        if (is_array($VarName)) {
                            $VarName = implode(' & ', array_values($VarName));
                        }
                        echo sprintf($this->Translate('Error on read %s. Maybe your Device not support %s.'), $ApiCmd, $this->Translate($VarName)) . "\r\n";
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
                                $APIData->Data = $this->sdechex($APIData->Data);
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
                                $APIData->Data = $this->sdechex($APIData->Data);
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
                    if ($ResultData != $APIData->Data) {
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
            trigger_error($this->Translate($exc->getMessage()), E_USER_NOTICE);
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
            trigger_error($exc->getMessage(), E_USER_NOTICE);
            return null;
        }
    }
}
