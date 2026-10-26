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
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoTuner {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property int $ParentID Die InstanzID des IO-Parent
 * @property int $MaxPreset
 * @property array $TunerPresentation
 * @property \OnkyoAVR\ONKYO_Zone_Tuner $OnkyoZone
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 * @method void SetValueFloat(string $Ident, float $value)
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void UnregisterProfile(string $Name)
 * @method int RegisterParent()
 * @method bool IORequestAction(string $Ident, mixed $Value)
 * @method void IOMessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data)
 */
class OnkyoTuner extends IPSModuleStrict
{
    use \OnkyoTuner\DebugHelper,
        \OnkyoTuner\BufferHelper,
        \OnkyoTuner\InstanceStatus,
        \OnkyoTuner\VariableHelper,
        \OnkyoTuner\VariableProfileHelper {
            \OnkyoTuner\InstanceStatus::MessageSink as IOMessageSink;
            \OnkyoTuner\InstanceStatus::RequestAction as IORequestAction;
        }

    /**
     * Create
     *
     * @return void
     */
    public function Create(): void
    {
        parent::Create();
        $this->RegisterPropertyInteger('Zone', \OnkyoAVR\ONKYO_Zone_Tuner::ZoneMain);
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone_Tuner();
        $this->MaxPreset = 10;
        $this->TunerPresentation = \OnkyoAVR\ONKYO_Zone_Tuner::$TunerPresentation;
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
        parent::ApplyChanges();
        $this->MaxPreset = 10;
        $this->TunerPresentation = \OnkyoAVR\ONKYO_Zone_Tuner::$TunerPresentation;
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

        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->RegisterParent();
        if ($this->HasActiveParent()) {
            $this->IOChangeState(IS_ACTIVE);
        }
        $this->UnregisterProfile('Onkyo.TunerPreset.' . $this->InstanceID);
        $this->UnregisterProfile('Onkyo.TunerBand.' . $this->InstanceID);
        $this->UnregisterProfile('Onkyo.TunerFreq.' . $this->InstanceID);
        $this->UnregisterProfile('Onkyo.TunerFreq' . $this->InstanceID); // Schreibweise ohne Punkt aus früheren Versionen
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
        if ($Ident == 'ALL') {
            $this->RequestZoneState();
            return true;
        }
        $ApiCmd = substr($Ident, 0, 3);
        if (!in_array($Ident, \OnkyoAVR\ONKYO_Zone_Tuner::$ZoneCMDs[$this->OnkyoZone->thisZone])) {
            echo $this->Translate('Invalid ident.') . PHP_EOL;
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data($this->OnkyoZone->GetReadAPICommands()[$ApiCmd], \OnkyoAVR\ISCP_API_Commands::Request);
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            return false;
        }
        $APIData->Data = $ResultData;
        $this->UpdateVariable($APIData);
        return true;
    }

    public function SetFrequency(float $Value): bool
    {
        $ValueValid = false;
        $NewBand = 0;
        foreach ($this->TunerPresentation as $Presentation) {
            if (($Value >= $Presentation['MIN']) && ($Value <= $Presentation['MAX'])) {
                $ValueValid = true;
                $NewBand = $Presentation[\OnkyoAVR\ISCP_API_Commands::SLI];
            }
        }
        if (!$ValueValid) {
            echo sprintf($this->Translate('%s out of range.'), 'Value') . PHP_EOL;
            return false;
        }
        $result = true;
        if ($NewBand == \OnkyoAVR\ONKYO_Zone_Tuner::SLI_FM) { //FM
            $Value = $Value * 100;
        }
        if ($this->GetValue(\OnkyoAVR\ISCP_API_Commands::SLI) != $NewBand) {
            $APIData = new \OnkyoAVR\ISCP_API_Data(
                $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::SLI),
                sprintf('%02X', $NewBand),
                false
            );
            $ResultData = $this->Send($APIData);
            if ($ResultData === null) {
                $result = false;
            }
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
            $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::TUN),
            sprintf('%05.0F', $Value),
            false
        );
        $ResultData = $this->Send($APIData);
        if ($ResultData === null) {
            $result = false;
        }
        return $result;
    }

    /**
     * SetBand
     *
     * @param  int $Value
     * @return bool
     */
    public function SetBand(int $Value): bool
    {
        $ValueValid = false;
        foreach ($this->TunerPresentation as $Presentation) {
            if ($Value == $Presentation[\OnkyoAVR\ISCP_API_Commands::SLI]) {
                $ValueValid = true;
            }
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
            $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::SLI),
            sprintf('%02X', $Value)
        );
        return $this->SendAPIData($APIData);
    }

    /**
     * CallPreset
     *
     * @param  int $Value
     * @return bool
     */
    public function CallPreset(int $Value): bool
    {
        if (($Value < 1) || ($Value > $this->MaxPreset)) {
            echo sprintf($this->Translate('%s out of range.'), 'Value') . PHP_EOL;
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
            $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::PRS),
            sprintf('%02X', $Value)
        );
        return $this->SendAPIData($APIData);
    }

    /**
     * SetPreset
     *
     * @param  int $Value
     * @return bool
     */
    public function SetPreset(int $Value): bool
    {
        if (($Value < 1) || ($Value > $this->MaxPreset)) {
            echo sprintf($this->Translate('%s out of range.'), 'Value') . PHP_EOL;
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data(
            $this->OnkyoZone->GetZoneCommand(\OnkyoAVR\ISCP_API_Commands::PRM),
            sprintf('%02X', $Value)
        );
        return $this->SendAPIData($APIData);
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
            if ($this->HasActiveParent()) {
                $this->RequestPresentations();
                $this->RequestZoneState();
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
        switch ($Ident) {
            case \OnkyoAVR\ISCP_API_Commands::SLI:
                $this->SetBand($Value);
                break;
            case \OnkyoAVR\ISCP_API_Commands::TUN:
                $this->SetFrequency($Value);
                break;
            case \OnkyoAVR\ISCP_API_Commands::PRS:
                $this->CallPreset($Value);
                break;
        }
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
            case \OnkyoAVR\ISCP_API_Commands::TUN:
            case \OnkyoAVR\ISCP_API_Commands::TUZ:
            case \OnkyoAVR\ISCP_API_Commands::TU3:
            case \OnkyoAVR\ISCP_API_Commands::TU4:
                if ((int) $APIData->Data < 1629) {
                    $Value = (int) $APIData->Data;
                    $Presentation = $this->TunerPresentation[\OnkyoAVR\ONKYO_Zone_Tuner::AM];
                } else {
                    $Value = (((int) $APIData->Data) / 100);
                    $Presentation = $this->TunerPresentation[\OnkyoAVR\ONKYO_Zone_Tuner::FM];
                }
                $this->SetValueFloat(\OnkyoAVR\ISCP_API_Commands::TUN, $Value);
                break;
            case \OnkyoAVR\ISCP_API_Commands::PRS:
            case \OnkyoAVR\ISCP_API_Commands::PRZ:
            case \OnkyoAVR\ISCP_API_Commands::PR3:
            case \OnkyoAVR\ISCP_API_Commands::PR4:
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::PRS, hexdec($APIData->Data));
                break;
            case \OnkyoAVR\ISCP_API_Commands::SLI:
            case \OnkyoAVR\ISCP_API_Commands::SLZ:
            case \OnkyoAVR\ISCP_API_Commands::SL3:
            case \OnkyoAVR\ISCP_API_Commands::SL4:

                switch (hexdec($APIData->Data)) {
                    case \OnkyoAVR\ONKYO_Zone_Tuner::SLI_FM:
                        $Presentation = $this->TunerPresentation[\OnkyoAVR\ONKYO_Zone_Tuner::FM];
                        break;
                    case \OnkyoAVR\ONKYO_Zone_Tuner::SLI_AM:
                        $Presentation = $this->TunerPresentation[\OnkyoAVR\ONKYO_Zone_Tuner::AM];
                        break;
                }
                $this->SetValueInteger(\OnkyoAVR\ISCP_API_Commands::SLI, hexdec($APIData->Data));
                break;
            case \OnkyoAVR\ISCP_API_Commands::PRM:
            case \OnkyoAVR\ISCP_API_Commands::RDS:
            case \OnkyoAVR\ISCP_API_Commands::PTS:
            default:
                return;
        }
        if (isset($Presentation)) {
            unset($Presentation['SLI']);
            $Presentation = array_merge([
                'PRESENTATION'        => VARIABLE_PRESENTATION_SLIDER,
                'DIGITS'              => 0,
                'CUSTOM_GRADIENT'     => '[]',
                'ICON'                => '',
                'GRADIENT_TYPE'       => 0,
                'INTERVALS'           => '[]',
                'INTERVALS_ACTIVE'    => false,
                'PERCENTAGE'          => false,
                'PREFIX'              => '',
                'THOUSANDS_SEPARATOR' => '',
                'USAGE_TYPE'          => 5,
            ], $Presentation);
            $this->SendDebug('TUNER', $Presentation, 0);
            $this->RegisterVariableFloat(
                \OnkyoAVR\ISCP_API_Commands::TUN,
                $this->Translate('Tuner Frequency'),
                $Presentation,
                0
            );
            $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::TUN);
        }
    }

    /**
     * RequestPresentations
     *
     * @return void
     */
    private function RequestPresentations(): void
    {
        $APIDataPresetList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::PresetList);
        $ResultDataPresetList = $this->Send($APIDataPresetList);
        $PresetOptions = [];
        if (count($ResultDataPresetList) > 0) {
            foreach ($ResultDataPresetList as $Value => $Name) {
                if (is_int($Value)) {
                    $PresetOptions[] = [
                        'Value'      => $Value,
                        'Caption'    => $Name,
                        'IconActive' => false,
                        'IconValue'  => '',
                        'Color'      => -1
                    ];
                }
            }
            $this->MaxPreset = $ResultDataPresetList['MaxPreset'];
        }
        $this->SendDebug('PresetOptions', $PresetOptions, 0);
        $this->RegisterVariableInteger(
            \OnkyoAVR\ISCP_API_Commands::PRS,
            $this->Translate('Radio Stations'),
            [
                'ICON'            => '',
                'PRESENTATION'    => VARIABLE_PRESENTATION_ENUMERATION,
                'OPTIONS'         => json_encode($PresetOptions)
            ],
            0
        );
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::PRS);

        $APIDataTunerList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::TunerList);
        $ResultDataTunerList = $this->Send($APIDataTunerList);
        $BandOptions = [];
        if (count($ResultDataTunerList) == 0) {
            $ResultDataTunerList = \OnkyoAVR\ONKYO_Zone_Tuner::$TunerPresentation;
        }
        foreach ($ResultDataTunerList as $Band => &$Presentation) {
            $Presentation = array_merge(\OnkyoAVR\ONKYO_Zone_Tuner::$TunerPresentation[$Band], $Presentation);
            $BandOptions[] = [
                'Value'      => \OnkyoAVR\ONKYO_Zone_Tuner::$TunerPresentation[$Band][\OnkyoAVR\ISCP_API_Commands::SLI],
                'Caption'    => $Band,
                'IconActive' => false,
                'IconValue'  => '',
                'Color'      => -1
            ];
        }
        $this->SendDebug('PresetAssociation', $ResultDataTunerList, 0);
        $this->SendDebug('BandOptions', $BandOptions, 0);
        $this->TunerPresentation = $ResultDataTunerList;
        $this->RegisterVariableInteger(
            \OnkyoAVR\ISCP_API_Commands::SLI,
            'Tuner Band',
            [
                'ICON'            => '',
                'PRESENTATION'    => VARIABLE_PRESENTATION_ENUMERATION,
                'OPTIONS'         => json_encode($BandOptions)
            ],
            0
        );
        $this->EnableAction(\OnkyoAVR\ISCP_API_Commands::SLI);
    }

    /**
     * RequestZoneState
     *
     * @return void
     */
    private function RequestZoneState(): void
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

    /**
     * SendAPIData
     *
     * @param  \OnkyoAVR\ISCP_API_Data $APIData
     * @return bool
     */
    private function SendAPIData(\OnkyoAVR\ISCP_API_Data $APIData): bool
    {
        $APIData->Data = $this->Send($APIData);
        if ($APIData->Data == null) {
            return false;
        }
        $this->UpdateVariable($APIData);
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
                $this->SendDebug('Response', 'No answer', 0);

                return null;
            }
            $result = unserialize($ret);
            $this->SendDebug('Response ' . $APIData->APICommand, $result, 0);

            return $result;
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            echo $exc->getMessage() . PHP_EOL;
            return null;
        }
    }
}
