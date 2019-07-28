<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';  // diverse Klassen
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property int $ParentID Die InstanzeID des IO-Parent
 * @property int $MaxPreset
 * @property int $TunerProfile
 * @property \OnkyoAVR\ONKYO_Zone_Tuner $OnkyoZone
 */
class OnkyoTuner extends IPSModule
{
    use \OnkyoTuner\DebugHelper,
        \OnkyoTuner\BufferHelper,
        \OnkyoTuner\InstanceStatus,
        \OnkyoTuner\VariableHelper,
        \OnkyoTuner\VariableProfileHelper {
        \OnkyoTuner\InstanceStatus::MessageSink as IOMessageSink;
        \OnkyoTuner\InstanceStatus::RequestAction as IORequestAction;
    }

    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}');
        $this->RegisterPropertyInteger('Zone', \OnkyoAVR\ONKYO_Zone_Tuner::ZoneMain);
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone_Tuner();
        $this->MaxPreset = 10;
        $this->TunerProfile = \OnkyoAVR\ONKYO_Zone_Tuner::$TunerProfile;
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
            $this->UnregisterProfile('Onkyo.TunerBand.' . $this->InstanceID);
            $this->UnregisterProfile('Onkyo.TunerPreset.' . $this->InstanceID);
            $this->UnregisterProfile('Onkyo.TunerFreq.' . $this->InstanceID);
        }

        parent::Destroy();
    }

    public function ApplyChanges()
    {
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        parent::ApplyChanges();
        $this->MaxPreset = 10;
        $this->TunerProfile = \OnkyoAVR\ONKYO_Zone_Tuner::$TunerProfile;
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone_Tuner($this->ReadPropertyInteger('Zone'));
        $this->SetSummary($this->OnkyoZone->GetName());

        $Zone = new \OnkyoAVR\ONKYO_Zone(\OnkyoAVR\ONKYO_Zone::Tuner);
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
        $BandAssociation = [
            [\OnkyoAVR\ONKYO_Zone_Tuner::SLI_FM, 'FM', '', -1],
            [\OnkyoAVR\ONKYO_Zone_Tuner::SLI_AM, 'AM', '', -1]
        ];
        $this->RegisterProfileIntegerEx('Onkyo.TunerBand.' . $this->InstanceID, '', '', '', $BandAssociation);
        $this->RegisterVariableInteger('SLI', 'Tuner Band', 'Onkyo.TunerBand.' . $this->InstanceID, 0);
        $this->EnableAction('SLI');
        $this->RegisterProfileInteger('Onkyo.TunerPreset.' . $this->InstanceID, '', '', '', 0, 0, 0);
        $this->RegisterVariableInteger('PRS', $this->Translate('Radio Stations'), 'Onkyo.TunerPreset.' . $this->InstanceID, 0);
        $this->EnableAction('PRS');
        $this->RegisterProfileFloat('Onkyo.TunerFreq' . $this->InstanceID, '', '', ' MHz', 87, 108, 0.5, 1);
        $this->RegisterVariableFloat('TUN', $this->Translate('Tuner Frequency'), 'Onkyo.TunerFreq' . $this->InstanceID, 0);
        $this->EnableAction('TUN');

        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
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

    //################# PRIVATE

    private function UpdateVariable(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        switch ($APIData->APICommand) {
            case \OnkyoAVR\ISCP_API_Commands::TUN:
            case \OnkyoAVR\ISCP_API_Commands::TUZ:
            case \OnkyoAVR\ISCP_API_Commands::TU3:
            case \OnkyoAVR\ISCP_API_Commands::TU4:

                if ((int) $APIData->Data < 1629) {
                    $Value = (int) $APIData->Data;
                } else {
                    $Value = (((int) $APIData->Data) / 100);
                }
                $this->SetValue('TUN', $Value);
                break;
            case \OnkyoAVR\ISCP_API_Commands::PRS:
            case \OnkyoAVR\ISCP_API_Commands::PRZ:
            case \OnkyoAVR\ISCP_API_Commands::PR3:
            case \OnkyoAVR\ISCP_API_Commands::PR4:
                $this->SetValue('PRS', hexdec($APIData->Data));
                break;
            case \OnkyoAVR\ISCP_API_Commands::SLI:
            case \OnkyoAVR\ISCP_API_Commands::SLZ:
            case \OnkyoAVR\ISCP_API_Commands::SL3:
            case \OnkyoAVR\ISCP_API_Commands::SL4:
                switch (hexdec($APIData->Data)) {
                    case \OnkyoAVR\ONKYO_Zone_Tuner::SLI_FM:
                        $Profile = $this->TunerProfile[\OnkyoAVR\ONKYO_Zone_Tuner::FM];
                        break;
                    case \OnkyoAVR\ONKYO_Zone_Tuner::SLI_AM:
                        $Profile = $this->TunerProfile[\OnkyoAVR\ONKYO_Zone_Tuner::AM];
                        break;
                }
                if (isset($Profile)) {
                    $this->RegisterProfileFloat('Onkyo.TunerFreq' . $this->InstanceID, '', '', $Profile['Suffix'], $Profile['Min'], $Profile['Max'], $Profile['Step'], $Profile['Digits']);
                }
                $this->SetValue('SLI', hexdec($APIData->Data));
                break;
            case \OnkyoAVR\ISCP_API_Commands::PRM:
            case \OnkyoAVR\ISCP_API_Commands::RDS:
            case \OnkyoAVR\ISCP_API_Commands::PTS:
            default:
                return;
        }
    }

    //################# ActionHandler

    public function RequestAction($Ident, $Value)
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return true;
        }
        switch ($Ident) {
            case 'SLI':
                return $this->SetBand($Value);
            case 'TUN':
                return $this->SetFrequency($Value);
            case 'PRS':
                return $this->CallPreset($Value);
        }
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
        if (!in_array($Ident, \OnkyoAVR\ONKYO_Zone_Tuner::$ZoneCMDs[$this->OnkyoZone->thisZone])) {
            trigger_error($this->Translate('Invalid ident.'), E_USER_NOTICE);
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data($this->OnkyoZone->GetReadAPICommands($ApiCmd), \OnkyoAVR\ISCP_API_Commands::Request);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            return false;
        }
        $APIData->Data = $ResultData;
        $this->UpdateVariable($APIData);
        return true;
    }

    public function SetFrequency(float $Value)
    {
        $ValueValid = false;
        $NewBand = 0;
        foreach ($this->TunerProfile as $Profile) {
            if (($Value >= $Profile['Min']) and ($Value <= $Profile['Max'])) {
                $ValueValid = true;
                $NewBand = $Profile['SLI'];
            }
        }
        if (!$ValueValid) {
            trigger_error(sprintf($this->Translate('%s out of range.'), 'Value'), E_USER_NOTICE);
            return false;
        }
        $result = true;
        if ($NewBand == \OnkyoAVR\ONKYO_Zone_Tuner::SLI_FM) { //FM
            $Value = $Value * 100;
        }
        if ($this->GetValue('SLI') != $NewBand) {
            $APIData = new \OnkyoAVR\ISCP_API_Data(
                    $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::SLI), sprintf('%02X', $NewBand), false);
            $ResultData = $this->Send($APIData);
            if ($ResultData === null) {
                $result = false;
            }
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
                $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::TUN), sprintf('%05.0F', $Value), false);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            $result = false;
        }
        return $result;
    }

    public function SetBand(int $Value)
    {
        $ValueValid = false;
        foreach ($this->TunerProfile as $Profile) {
            if ($Value == $Profile['SLI']) {
                $ValueValid = true;
            }
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
                $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::SLI), sprintf('%02X', $Value));
        return $this->SendAPIData($APIData);
    }

    public function CallPreset(int $Value)
    {
        if (($Value < 1) or ($Value > $this->MaxPreset)) {
            trigger_error(sprintf($this->Translate('%s out of range.'), 'Value'), E_USER_NOTICE);
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
                $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::PRS), sprintf('%02X', $Value));
        return $this->SendAPIData($APIData);
    }

    public function SetPreset(int $Value)
    {
        if (($Value < 1) or ($Value > $this->MaxPreset)) {
            trigger_error(sprintf($this->Translate('%s out of range.'), 'Value'), E_USER_NOTICE);
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
                $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::PRM), sprintf('%02X', $Value));
        return $this->SendAPIData($APIData);
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
        $APIDataPresetList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::PresetList);
        $ResultDataPresetList = $this->Send($APIDataPresetList);
        $PresetAssociation = [];
        if (count($ResultDataPresetList) > 0) {
            foreach ($ResultDataPresetList as $Value => $Name) {
                if (is_int($Value)) {
                    $PresetAssociation[] = [$Value, $Name, '', -1];
                }
            }
            $this->MaxPreset = $ResultDataPresetList['MaxPreset'];
        }
        $this->SendDebug('PresetAssociation', $PresetAssociation, 0);
        $this->RegisterProfileIntegerEx('Onkyo.TunerPreset.' . $this->InstanceID, '', '', '', $PresetAssociation);

        $APIDataTunerList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::TunerList);
        $ResultDataTunerList = $this->Send($APIDataTunerList);
        $BandAssociation = [];
        if (count($ResultDataTunerList) == 0) {
            $ResultDataTunerList = \OnkyoAVR\ONKYO_Zone_Tuner::$TunerProfile;
        }
        foreach ($ResultDataTunerList as $Band => &$Profile) {
            $Profile = array_merge(\OnkyoAVR\ONKYO_Zone_Tuner::$TunerProfile[$Band], $Profile);
            $BandAssociation[] = [\OnkyoAVR\ONKYO_Zone_Tuner::$TunerProfile[$Band]['SLI'], $Band, '', -1];
        }
        $this->SendDebug('PresetAssociation', $ResultDataTunerList, 0);
        $this->SendDebug('BandAssociation', $BandAssociation, 0);
        $this->TunerProfile = $ResultDataTunerList;
        $this->RegisterProfileIntegerEx('Onkyo.TunerBand.' . $this->InstanceID, '', '', '', $BandAssociation);
    }

    //------------------------------------------------------------------------------
    private function RequestZoneState()
    {
        $ApiCmds = $this->OnkyoZone->GetReadAPICommands();
        foreach ($ApiCmds as $ApiCmd) {
            $APIData = new \OnkyoAVR\ISCP_API_Data($ApiCmd, \OnkyoAVR\ISCP_API_Commands::Request);
            $ResultData = $this->Send($APIData);
            if ($ResultData === null) {
                continue;
            }
            $APIData->Data = $ResultData;
            $this->UpdateVariable($APIData);
        }
    }

    private function SendAPIData(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        $APIData->Data = $this->Send($APIData);
        if ($APIData->Data == null) {
            return false;
        }
        $this->UpdateVariable($APIData);
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
}
