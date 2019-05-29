<?php

// todo Zonenwechsel muss aufräumen...
//
declare(strict_types=1);
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

    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}');
        $this->RegisterPropertyInteger('Zone', \OnkyoAVR\ONKYO_Zone::None);
        $this->RegisterPropertyBoolean('VL4', true);
        $this->RegisterPropertyBoolean('MT4', true);
        $this->RegisterPropertyBoolean('SL4', true);

        $this->RegisterPropertyBoolean('VL3', true);
        $this->RegisterPropertyBoolean('MT3', true);
        $this->RegisterPropertyBoolean('SL3', true);
        $this->RegisterPropertyBoolean('TN3', true);

        $this->RegisterPropertyBoolean('ZVL', true);
        $this->RegisterPropertyBoolean('ZMT', true);
        $this->RegisterPropertyBoolean('SLZ', true);
        $this->RegisterPropertyBoolean('ZTN', true);

        $this->RegisterPropertyBoolean('TFR', true);
        $this->RegisterPropertyBoolean('MVL', true);
        $this->RegisterPropertyBoolean('AMT', true);
        $this->RegisterPropertyBoolean('SLI', true);

        $this->RegisterPropertyBoolean('CTL', true);
        $this->RegisterPropertyBoolean('SWL', true);
        $this->RegisterPropertyBoolean('LMD', true);
        $this->RegisterPropertyBoolean('LMD2', true);

        $this->RegisterPropertyBoolean('TFW', false);
        $this->RegisterPropertyBoolean('TFH', false);
        $this->RegisterPropertyBoolean('TSR', false);
        $this->RegisterPropertyBoolean('TSB', false);
        $this->RegisterPropertyBoolean('TCT', false);
        $this->RegisterPropertyBoolean('TSW', false);
        $this->RegisterPropertyBoolean('SW2', false);
        $this->RegisterPropertyBoolean('HDO', false);
        $this->RegisterPropertyBoolean('CEC', false);
        $this->RegisterPropertyBoolean('HAO', false);
        $this->RegisterPropertyBoolean('HAS', false);
        $this->RegisterPropertyBoolean('RES', false);
        $this->RegisterPropertyBoolean('VWM', false);
        $this->RegisterPropertyBoolean('VPM', false);
        $this->RegisterPropertyBoolean('DIF', false);
        $this->RegisterPropertyBoolean('DIM', false);
        $this->RegisterPropertyBoolean('ADQ', false);
        $this->RegisterPropertyBoolean('ADY', false);
        $this->RegisterPropertyBoolean('ADV', false);
        $this->RegisterPropertyBoolean('SLA', false);
        $this->RegisterPropertyBoolean('IFA', false);
        $this->RegisterPropertyBoolean('IFV', false);
        $this->RegisterPropertyBoolean('SLP', false);
        $this->RegisterPropertyBoolean('LTN', false);
        $this->RegisterPropertyBoolean('MOT', false);
        $this->RegisterPropertyBoolean('RAS', false);
        $this->RegisterPropertyBoolean('PMB', false);
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone();
        $this->PhaseMatchingBass = true;
        $this->ToneProfile = [];
        $this->LMDList = [];
        $this->SetReceiveDataFilter('.*"APICommand":"NOTING".*');
        $this->SendDebug('FILTER', 'NOTHING', 0);
    }

    public function Destroy()
    {
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return parent::Destroy();
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
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone($this->ReadPropertyInteger('Zone'));

        if (@$this->GetIDForIdent('ReplyAPIData') > 0) {
            $this->PerformModulUpdate();
            return;
        }
        // prüfung ob $OldZone != $this->ReadPropertyInteger('Zone')
        // dann Idents anpassen!
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
        foreach ($MyPropertys as $Key => $Value) {
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

    protected function ModulUpdateErrorHandler($errno, $errstr)
    {
        $this->SendDebug('ERROR', utf8_decode($errstr), 0);
        echo $errstr;
    }

    private function PerformModulUpdate()
    {
        set_error_handler([$this, 'ModulUpdateErrorHandler']);
        $this->UnregisterVariable('ReplyAPIData');
        // Update machen !!!
        $Zone = $this->OnkyoZone;
        $OldProfileList = [
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
            'RadioPreset.Onkyo'
        ];
        $OldVariableNames = [
            'Subwoofer Bass'       => 'Subwoofer Level',
            'Sleep Set'            => 'Sleeptimer',
            'Audio Input Selector' => 'Audio Input',
            'Video Wide Mode'      => 'Video Mode',
            'Input Selector'       => 'Input'
        ];
        $OldVariables = [
            \OnkyoAVR\ISCP_API_Commands::TUN,
            \OnkyoAVR\ISCP_API_Commands::PRS,
            'LMZ',
            'LTZ',
            'RAZ',
            \OnkyoAVR\ISCP_API_Commands::TUZ,
            \OnkyoAVR\ISCP_API_Commands::PRZ,
            \OnkyoAVR\ISCP_API_Commands::TU3,
            \OnkyoAVR\ISCP_API_Commands::PR3,
            \OnkyoAVR\ISCP_API_Commands::TU4,
            \OnkyoAVR\ISCP_API_Commands::PR4,
            \OnkyoAVR\ISCP_API_Commands::NTC,
            \OnkyoAVR\ISCP_API_Commands::NTZ,
            \OnkyoAVR\ISCP_API_Commands::NT3,
            \OnkyoAVR\ISCP_API_Commands::NT4,
            \OnkyoAVR\ISCP_API_Commands::NPR,
            \OnkyoAVR\ISCP_API_Commands::NPZ,
            \OnkyoAVR\ISCP_API_Commands::NP3,
            \OnkyoAVR\ISCP_API_Commands::NP4
        ];
        foreach ($OldVariables as $OldVariable) {
            @$this->UnregisterVariable($OldVariable);
        }

        $MyPropertys = json_decode(IPS_GetConfiguration($this->InstanceID), true);
        foreach (IPS_GetChildrenIDs($this->InstanceID)as $ObjectID) {
            $Object = IPS_GetObject($ObjectID);

            if ($Object['ObjectType'] != OBJECTTYPE_VARIABLE) {
                continue;
            }
            $Variable = IPS_GetVariable($ObjectID);

            $ApiCmd = substr($Object['ObjectIdent'], 0, 3);
            if (!$Zone->CmdAvaiable($ApiCmd)) {
                $this->SendDebug('Wrong Zone UnregisterVariable', $ApiCmd, 0);
                $this->UnregisterVariable($ApiCmd);
            }
            $Mapping = \OnkyoAVR\ISCP_API_Data_Mapping::GetMapping($ApiCmd);
            if ($Mapping != null) { //Variable bekannt
                if (array_key_exists($ApiCmd, $MyPropertys)) {
                    // Werkssettings sagt false
                    if ($MyPropertys[$ApiCmd] === false) {
                        //Aber alte Variable vorhanden => settings updaten
                        $this->SendDebug('Update Property', $ApiCmd, 0);
                        IPS_SetProperty($this->InstanceID, $ApiCmd, true);
                    }
                }
                $Profile = $Mapping->Profile;
                if (strpos($Profile, '%d')) {
                    $Profile = sprintf($Profile, $this->InstanceID);
                }
                if ($Mapping->VarType == \OnkyoAVR\IPSVarType::vtDualInteger) {
                    $Mapping->VarType = \OnkyoAVR\IPSVarType::vtInteger;
                    $ISCP_ValuePrefix = array_flip($Mapping->ValuePrefix)[$Object['ObjectIdent'][3]];
                    $Mapping->VarName = $Mapping->VarName[$ISCP_ValuePrefix];
                }
                //Profile neu setzen
                $this->SendDebug('Update Profile', $Object, 0);
                $this->MaintainVariable($Object['ObjectIdent'], $Object['ObjectName'], $Mapping->VarType, $Profile, $Object['ObjectPosition'], true);
                // Hat sich der Variabletyp verändert?
                if ($Variable['VariableType'] != $Mapping->VarType) {
                    $ObjectID = $this->GetIDForIdent($Object['ObjectIdent']); //neue VariableID
                }
                //Name ist unverändert
                if ($Object['ObjectName'] == $Mapping->VarName) {
                    $this->SendDebug('Update Translated Name', $Object['ObjectName'], 0);
                    IPS_SetName($ObjectID, $this->Translate($Object['ObjectName']));
                    continue;
                }
                if (array_key_exists($Object['ObjectName'], $OldVariableNames)) {
                    $this->SendDebug('Update Old Name', $Object['ObjectName'], 0);
                    IPS_SetName($ObjectID, $this->Translate($Mapping->VarName));
                }
            } else {
                $this->SendDebug('Skip Variable', $ApiCmd, 0);
            }
        }
        foreach ($OldProfileList as $OldProfile) {
            $this->UnregisterProfile($OldProfile);
        }

        if (IPS_HasChanges($this->InstanceID)) {
            IPS_RunScriptText('IPS_ApplyChanges(' . $this->InstanceID . ');');
        }
        restore_error_handler();
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
            if ($this->CheckZone()) {
                if ($this->HasActiveParent()) {
                    $this->RequestProfile();
                    $this->RequestZoneState();
                }
            }
        }
    }

    public function GetConfigurationForm()
    {
        return file_get_contents(__DIR__ . '/form_' . $this->OnkyoZone->thisZone . '.json');
    }

    //################# PRIVATE

    private function CheckZone()
    {
        if ($this->OnkyoZone->thisZone == \OnkyoAVR\ONKYO_Zone::None) {
            $this->SendDebug('Error', $this->Translate('Zone not set.'), 0);
            $this->LogMessage($this->Translate('Zone not set.'), KL_ERROR);
            return false;
        }
        return true;
    }

    private function sdechex(int $d)
    {
        return ($d < 0) ? ('-' . strtoupper(dechex(-$d))) : ($d == 0 ? '00' : '+' . strtoupper(dechex($d)));
    }

    private function shexdec(string $h)
    {
        return ($h[0] === '-') ? -(hexdec($h)) : hexdec($h);
    }

    private function UpdateVariable(\OnkyoAVR\ISCP_API_Data $APIData)
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
    }

    //################# ActionHandler

    public function RequestAction($Ident, $Value)
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return true;
        }
        if (!$this->CheckZone()) {
            return false;
        }
        if ($Ident == 'LMD2') {
            $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::LMD, $this->LMDList[$Value]['Code'], false);
            $this->Send($APIData);
            return;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data($Ident, $Value);

        $this->SendAPIData($APIData);
    }

    //################# PUBLIC

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     */
    public function RequestState(string $Ident)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        if ($Ident == 'ALL') {
            return $this->RequestZoneState();
        }
        $ApiCmd = substr($Ident, 0, 3);
        if (!$this->OnkyoZone->CmdAvaiable($ApiCmd)) {
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

    public function Power()
    {
        return $this->SendPower(!$this->GetValue(\OnkyoAVR\ISCP_API_Commands::PWR));
    }

    public function PowerOn()
    {
        return $this->SendPower(true);
    }

    public function PowerOff()
    {
        return $this->SendPower(false);
    }

    private function SendPower(bool $Value)
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

    public function SetVolume(int $Value)
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

    public function SetMute(bool $Value)
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

    public function SelectInput(int $Value)
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
                $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SL5, $Value);
                break;
        }
        return $this->SendAPIData($APIData);
    }

    public function SelectAudioInput(int $Value)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SLA, $Value);
        return $this->SendAPIData($APIData);
    }

    public function SelectListingMode(int $Value)
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

    public function SetSleep(int $Duration)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        if ($this->OnkyoZone->thisZone != \OnkyoAVR\ONKYO_Zone::ZoneMain) {
            trigger_error($this->Translate('Command not available at this zone.'), E_USER_NOTICE);
            return false;
        }
        if (($Duration < 0) or ($Duration > 0x5A)) {
            trigger_error(sprintf($this->Translate('%s out of range.'), 'Duration'), E_USER_NOTICE);
            return false;
        }

        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SLP, $Duration);
        return $this->SendAPIData($APIData);
    }

    public function SetCenterLevel(float $Level)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::CTL, $Level);
        return $this->SendAPIData($APIData);
    }

    public function SetSubwooferLevel(float $Level)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SWL, $Level);
        return $this->SendAPIData($APIData);
    }

    public function SetSubwoofer2Level(float $Level)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::SW2, $Level);
        return $this->SendAPIData($APIData);
    }

    public function SetDisplayMode(int $Mode)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::DIF, $Mode);
        return $this->SendAPIData($APIData);
    }

    public function SetDisplayDimmer(int $Level)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::DIM, $Level);
        return $this->SendAPIData($APIData);
    }

    public function GetAudioInfomation()
    {
        //TODO
    }

    public function GetVideoInfomation()
    {
        //TODO
    }

    public function SendCommand(string $Command, string $Value, bool $needResponse)
    {
        trigger_error('Diese Funktion wird nicht mehr unterstützt!', E_USER_DEPRECATED);
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data($Command, $Value, $needResponse);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            trigger_error('Error on send command.', E_USER_NOTICE);
            return false;
        }
        if ($needResponse) {
            if ($ResultData == 'N/A') {
                trigger_error('Command (temporally) not available.', E_USER_NOTICE);
                return false;
            }
            return $ResultData;
        } else {
            if ($APIData === false) {
                trigger_error('Error on send command.', E_USER_NOTICE);
                return false;
            }
        }
        return true;
    }

    //################# Datapoints

    public function ReceiveData($JSONString)
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data($JSONString);
        $this->SendDebug('ReceiveData', $APIData, 0);
        $this->UpdateVariable($APIData);
    }

    private function RequestProfile()
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
            $Volsetep = (float) $ResultDataZoneList[$zone]['Volsetep'];
        } else {
            $Volmax = 80;
            $Volsetep = 1;
        }
        $ptVolumeProfile = sprintf(\OnkyoAVR\IPSProfiles::ptVolume, $this->InstanceID);
        $this->RegisterProfileInteger($ptVolumeProfile, 'Speaker', '', ' %', 0, $Volmax, $Volsetep);

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

    //------------------------------------------------------------------------------
    private function RequestZoneState()
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
                    $ResultData = $this->Send($APIData);
                    if ($ResultData === null) {
                        continue;
                    }
                    $APIData->Data = $ResultData;
                    $this->UpdateVariable($APIData);
                }
            }
        }
    }

    private function SendAPIData(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        try {
            if (!$this->OnkyoZone->CmdAvaiable($APIData->APICommand)) {
                throw new Exception('Command not available at this zone.', E_USER_NOTICE);
            }
            $Mapping = $APIData->GetMapping();
            //$this->SendDebug('SendAPIData', $APIData, 0);
            //$this->SendDebug('SendAPIData Mapping', $Mapping, 0);

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
                    $ISCP_ValuePrefix = array_flip($Mapping->ValuePrefix)[$APIData->SubIndex];
                    $ValueMapping = array_flip($Mapping->ValueMapping);
                    if (array_key_exists($APIData->Data, $ValueMapping)) {
                        $APIData->Data = $ISCP_ValuePrefix . $ValueMapping[$APIData->Data];
                    } else {
                        $APIData->Data = $ISCP_ValuePrefix . sprintf('%02X', $APIData->Data);
                    }
                    break;
                default:
                    throw new Exception('Unknow VariableType.', E_USER_NOTICE);
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

    private function Send(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        $this->SendDebug('ForwardData', $APIData, 0);

        try {
            if (!$this->HasActiveParent()) {
                throw new Exception($this->Translate('Instance has no active parent.'), E_USER_NOTICE);
            }
            $ret = $this->SendDataToParent($APIData->ToJSONString('{8F47273A-0B69-489E-AF36-F391AE5FBEC0}'));
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
