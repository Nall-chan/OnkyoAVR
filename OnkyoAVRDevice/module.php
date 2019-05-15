<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';  // diverse Klassen
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoAVR {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property string $ReplyAPIData Empfangsbuffer
 * @property int $ParentID Die InstanzeID des IO-Parent
 * @property \OnkyoAVR\ONKYO_Zone $OnkyoZone 
 */
class OnkyoAVR extends IPSModule
{

    use \OnkyoAVR\DebugHelper,
        \OnkyoAVR\BufferHelper,
        \OnkyoAVR\InstanceStatus,
        \OnkyoAVR\VariableHelper,
        \OnkyoAVR\VariableProfileHelper,
        \OnkyoAVR\Semaphore {
        \OnkyoAVR\InstanceStatus::MessageSink as IOMessageSink;
        \OnkyoAVR\InstanceStatus::RequestAction as IORequestAction;
    }
    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}');
        $this->RegisterPropertyInteger('Zone', \OnkyoAVR\ONKYO_Zone::None);
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone();
    }

    public function ApplyChanges()
    {
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        parent::ApplyChanges();
        $this->UnregisterVariable('ReplyAPIData');
        $this->OnkyoZone = new \OnkyoAVR\ONKYO_Zone($this->ReadPropertyInteger('Zone'));
        foreach (\OnkyoAVR\IPSProfiles::$ProfilAssociations as $Profile => $Association) {
            $this->RegisterProfileIntegerEx($Profile, '', '', '', $Association);
        }
        foreach (\OnkyoAVR\IPSProfiles::$ProfilInteger as $Profile => $Size) {
            $this->RegisterProfileInteger($Profile, '', '', '', $Size[0], $Size[1], $Size[2]);
        }

        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->RegisterParent();
        if ($this->CheckZone()) {
            if ($this->HasActiveParent()) {
                $this->IOChangeState(IS_ACTIVE);
            }
        }
        /*
          try {
          if ($this->GetZone()) {
          $this->RequestZoneState();
          }
          } catch (Exception $ex) {
          unset($ex);
          } */
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
                    $this->RequestZoneState();
                }
            }
        }
    }

    //################# PRIVATE

    private function CheckZone()
    {
        if ($this->OnkyoZone->thisZone == \OnkyoAVR\ONKYO_Zone::None) {
            $this->LogMessage($this->Translate('Zone not set.'), KL_ERROR);
            return false;
        }
        return true;
    }

    private function UpdateVariable(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        if ($APIData->Data == 'N/A') {
            return;
        }
        if ($APIData->Mapping->VarType == \OnkyoAVR\IPSVarType::vtDualInteger) {
            $Prefix = substr($APIData->Data, 0, 1);
            $this->MaintainVariable($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix], $this->Translate($APIData->Mapping->VarName[$Prefix]), \OnkyoAVR\IPSVarType::vtInteger, $APIData->Mapping->Profile, 0, true);
            if ($APIData->Mapping->EnableAction) {
                $this->EnableAction($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix]);
            }
            $Value = $APIData->Mapping->ValueMapping[substr($APIData->Data, 1, 2)];
            $this->SetValueInteger($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix], $Value);
            if (strlen($APIData->Data) > 3) {
                $Prefix = substr($APIData->Data, 3, 1);
                $this->MaintainVariable($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix], $this->Translate($APIData->Mapping->VarName[$Prefix]), \OnkyoAVR\IPSVarType::vtInteger, $APIData->Mapping->Profile, 0, true);
                if ($APIData->Mapping->EnableAction) {
                    $this->EnableAction($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix]);
                }
                $Value = $APIData->Mapping->ValueMapping[substr($APIData->Data, 4, 2)];
                $this->SetValueInteger($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix], $Value);
            }
            return;
        }
        $this->MaintainVariable($APIData->APICommand, $this->Translate($APIData->Mapping->VarName), $APIData->Mapping->VarType, $APIData->Mapping->Profile, 0, true);
        if ($APIData->Mapping->EnableAction) {
            $this->EnableAction($APIData->APICommand);
        }
        switch ($APIData->Mapping->VarType) {
            case \OnkyoAVR\IPSVarType::vtBoolean:
                $this->SetValueBoolean($APIData->APICommand, \OnkyoAVR\ISCP_API_Commands::$BoolValueMapping[$APIData->Data]);
                break;
            case \OnkyoAVR\IPSVarType::vtFloat:
                $this->SetValueFloat($APIData->APICommand, $APIData->Data / 100);
                break;
            case \OnkyoAVR\IPSVarType::vtInteger:
                $this->SetValueInteger($APIData->APICommand, hexdec($APIData->Data));
                break;
            case \OnkyoAVR\IPSVarType::vtString:
                $this->SetValueString($APIData->APICommand, $APIData->Data);
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
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->APICommand = substr($Ident, 0, 3);
        $APIData->Data = $Value;
        if (!$this->OnkyoZone->CmdAvaiable($APIData)) {
//            trigger_error("Illegal Command in this Zone.", E_USER_WARNING);
            echo 'Illegal Command in this Zone';
            return false;
        }
        // Mapping holen
        $APIData->GetMapping();
        $APIData->APICommand = $Ident;
        IPS_LogMessage('RequestValueMapping', print_r($APIData, 1));
        // Daten senden        Rückgabe ist egal, Variable wird automatisch durch Datenempfang nachgeführt
        try {
            $this->SendAPIData($APIData);
        } catch (Exception $ex) {
//            trigger_error($ex->getMessage(), E_USER_NOTICE);
            echo $ex->getMessage();
            return false;
//            return;
        }

        /*        if ($ret === false)
          {
          echo "Error on Send.";
          return;
          } */
    }

    //################# PUBLIC
    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     */
    public function RequestState()
    {
        if (!$this->HasActiveParent()) {
            trigger_error('Instance has no active Parent Instance!', E_USER_WARNING);
            return false;
        }

        if (!$this->CheckZone()) {
            return false;
        }
        $this->RequestZoneState();
    }

    public function Power(bool $Value)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->Data = $Value;
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::PWR;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::ZPW;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::PW3;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::PW4;
                break;
        }

        try {
            $this->SendAPIData($APIData);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SetVolume(int $Value)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->Data = $Value;
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::MVL;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::ZVL;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::VL3;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::VL4;
                break;
        }

        try {
            $this->SendAPIData($APIData);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SetMute(bool $Value)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->Data = $Value;
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::AMT;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::ZMT;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::MT3;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::MT4;
                break;
        }

        try {
            $this->SendAPIData($APIData);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SelectInput(int $Value)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->Data = $Value;
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::SLI;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::SLZ;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::SL3;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::SL4;
                break;
        }

        try {
            $this->SendAPIData($APIData);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SelectListingMode(int $Value)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->Data = $Value;
        switch ($this->OnkyoZone->thisZone) {
            case \OnkyoAVR\ONKYO_Zone::ZoneMain:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::LMD;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone2:
                $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::LMZ;
                break;
            case \OnkyoAVR\ONKYO_Zone::Zone3:
            case \OnkyoAVR\ONKYO_Zone::Zone4:
                trigger_error('Command not available at this Zone.', E_USER_NOTICE);
                return false;
                break;
        }

        try {
            $this->SendAPIData($APIData);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SetSleep(int $Value)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        if (($Value < 0) or ( $Value > 0x5)) {
            trigger_error('Value not valid.', E_USER_NOTICE);
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->Data = $Value;
        if ($this->OnkyoZone->thisZone != \OnkyoAVR\ONKYO_Zone::ZoneMain) {
            trigger_error('Command not available at this Zone.', E_USER_NOTICE);
            return false;
        }
        $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::SLP;

        try {
            $this->SendAPIData($APIData);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SendTVCommand(string $Command)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::CTV;
        $APIData->Data = $Command;

        try {
            $APIResult = $this->Send($APIData, false);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        if ($APIResult == false) {
            trigger_error('Error on send data.', E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SendBDCommand(string $Command)
    {

        if (!$this->CheckZone()) {
            return false;
        } $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->APICommand = \OnkyoAVR\ISCP_API_Commands::CDV;
        $APIData->Data = $Command;

        try {
            $APIResult = $this->Send($APIData, false);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        if ($APIResult == false) {
            trigger_error('Error on send data.', E_USER_NOTICE);
            return false;
        }
        return true;
    }

    public function SendCommand(string $Command, string $Value, bool $needResponse)
    {
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->APICommand = $Command;
        $APIData->Data = $Value;

        try {
            $APIResult = $this->Send($APIData, $needResponse);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        if ($needResponse) {
            if ($APIResult->Data == 'N/A') {
                trigger_error('Command (temporally) not available.', E_USER_NOTICE);
                return false;
            }
            if ($APIResult->Data != $APIData->Data) {
                trigger_error('Value not available.', E_USER_NOTICE);
                return false;
            }
        } else {
            if ($APIData === false) {
                trigger_error('Error on send data.', E_USER_NOTICE);
                return false;
            }
        }
        return true;
    }

    //################# Datapoints

    public function ReceiveData($JSONString)
    {
        $Data = json_decode($JSONString);
        if (!$this->CheckZone()) {
            return false;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->GetDataFromJSONObject($Data);
        $this->SendDebug('ReceiveData', $APIData, 0);

        if ($this->OnkyoZone->CmdAvaiable($APIData) === false) {
            if ($this->OnkyoZone->SubCmdAvaiable($APIData) === false) {
                return false;
            } else {
                $APIData->GetMapping();
                $APIData->APICommand = $APIData->APISubCommand->{$this->OnkyoZone->thisZone};
                $this->SendDebug('APISubCommand', $APIData->APICommand, 0);
            }
        } else {
            $APIData->GetMapping();
        }
        $this->SendDebug('ReceiveData', $APIData, 0);

        $this->ReceiveAPIData($APIData);
    }

    private function ReceiveAPIData(\OnkyoAVR\ISCP_API_Data $APIData)
    {

        $ReplyAPIData = $APIData->ToJSONString('');

        if (!$this->lock('ReplyAPIData')) {
            throw new Exception('ReplyAPIData is locked', E_USER_NOTICE);
        }
        $this->ReplyAPIData = $ReplyAPIData;
        $this->unlock('ReplyAPIData');
        $this->SendDebug('ReceiveAPIData', $ReplyAPIData, 0);
        if ($APIData->Mapping != null) {
            if ($APIData->Mapping->IsVariable) {
                $this->UpdateVariable($APIData);
            }
        }
    }

    //------------------------------------------------------------------------------
    private function RequestZoneState()
    {
        // Schleife von allen CMDs welche als Variable in dieser Zone sind.

        foreach (\OnkyoAVR\ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone] as $ApiCmd) {
            $APIData = new \OnkyoAVR\ISCP_API_Data();
            $APIData->APICommand = $ApiCmd;
            $APIData->GetMapping();
            if ($APIData->Mapping !== null) {
                if ($APIData->Mapping->RequestValue) {
                    $APIData->Data = \OnkyoAVR\ISCP_API_Commands::Request;

                    try {
                        $result = $this->Send($APIData, true);
                    } catch (Exception $exc) {
                        unset($exc);
                    }
                    //IPS_LogMessage('RequestZoneStateResult', print_r($result, true));
                }
            }
        }
    }

    private function SendAPIData(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        $DualType = substr($APIData->APICommand, 3, 1);
        $APIData->APICommand = substr($APIData->APICommand, 0, 3);
        if ($APIData->Mapping === null) {
            $APIData->GetMapping();
        }

        IPS_LogMessage('SendAPIData', print_r($APIData, 1));

        // Variable konvertieren..
        switch ($APIData->Mapping->VarType) {
            case \OnkyoAVR\IPSVarType::vtBoolean:
                $APIData->Data = \OnkyoAVR\ISCP_API_Commands::$BoolValueMapping[$APIData->Data];
                break;
            case \OnkyoAVR\IPSVarType::vtFloat:
//                echo "Float VarType not implemented.";

                throw new Exception('Float VarType not implemented.', E_USER_NOTICE);
                break;
            case \OnkyoAVR\IPSVarType::vtInteger:
                if ($APIData->Mapping->ValueMapping == null) {
                    $APIData->Data = strtoupper(substr('0' . dechex($APIData->Data), -2));
                } else {
                    $Mapping = array_flip($APIData->Mapping->ValueMapping);
                    if (array_key_exists($APIData->Data, $Mapping)) {
                        $APIData->Data = $Mapping[$APIData->Data];
                    } else {
                        $APIData->Data = strtoupper(substr('0' . dechex($APIData->Data), -2));
                    }
                }
                break;
            case \OnkyoAVR\IPSVarType::vtDualInteger:
                if ($DualType === false) {
                    throw new Exception('Error on get DualInteger.', E_USER_NOTICE);
//                    echo "Error on get DualInteger.";
//                    return false;
                }
                $Prefix = array_flip($APIData->Mapping->ValuePrefix)[$DualType];
                $Mapping = array_flip($APIData->Mapping->ValueMapping);
                if (array_key_exists($APIData->Data, $Mapping)) {
                    $APIData->Data = $Prefix . $Mapping[$APIData->Data];
                } else {
                    $APIData->Data = strtoupper($Prefix . substr('0' . dechex($APIData->Data), -2));
                }
                break;
            default:
//                echo "Unknow VarType.";
//                return;
                throw new Exception('Unknow VarType.', E_USER_NOTICE);
                break;
        }

        try {
            $ret = $this->Send($APIData);
        } catch (Exception $exc) {
            throw $exc;
        }

        if ($ret->Data == 'N/A') {
            throw new Exception('Command (temporally) not available.', E_USER_NOTICE);
//            return;
        }
        switch ($APIData->Mapping->VarType) {
            case \OnkyoAVR\IPSVarType::vtBoolean:
            case \OnkyoAVR\IPSVarType::vtInteger:
            case \OnkyoAVR\IPSVarType::vtFloat:
                if ($ret->Data != $APIData->Data) {
                    IPS_LogMessage('RequestAction', print_r($APIData, 1));
                    IPS_LogMessage('RequestActionResult', print_r($ret, 1));

                    throw new Exception('Value not available.', E_USER_NOTICE);
//                    echo "Value not available.";
//                    return;
                }
                break;
            case \OnkyoAVR\IPSVarType::vtDualInteger:
                if (strpos($ret->Data, $APIData->Data) === false) {
                    IPS_LogMessage('RequestAction', print_r($APIData, 1));
                    IPS_LogMessage('RequestActionResult', print_r($ret, 1));

                    throw new Exception('Value not available.', E_USER_NOTICE);
//                    echo "Value not available.";
//                    return;
                }
                break;
        }

        return $ret;
    }

    private function Send(\OnkyoAVR\ISCP_API_Data $APIData, $needResponse = true)
    {
        if (!$this->OnkyoZone->CmdAvaiable($APIData)) {
            throw new Exception('Command not available at this Zone.', E_USER_NOTICE);
        }
        if (!$this->HasActiveParent()) {
            throw new Exception('Instance has no active Parent.', E_USER_NOTICE);
        }

        if (!$this->lock('RequestSendData')) {
            throw new Exception('RequestSendData is locked', E_USER_NOTICE);
        }

        if ($needResponse) {
            if (!$this->lock('ReplyAPIData')) {
                $this->unlock('RequestSendData');

                throw new Exception('ReplyAPIData is locked', E_USER_NOTICE);
            }
            $this->ReplyAPIData = '';
            $this->unlock('ReplyAPIData');
        }
        $ret = $this->SendDataToParent($APIData->ToJSONString('{8F47273A-0B69-489E-AF36-F391AE5FBEC0}'));
        if ($ret === false) {
//            IPS_LogMessage('exc',print_r($ret,1));
            $this->unlock('RequestSendData');

            throw new Exception('Instance has no active Parent Instance!', E_USER_NOTICE);
        }
//        IPS_LogMessage('noexc', print_r($ret, 1));
        if (!$needResponse) {
            $this->unlock('RequestSendData');
            return true;
        }
        $ReplayAPIData = $this->WaitForResponse($APIData->APICommand);

        //        IPS_LogMessage('ReplayATData:'.$this->InstanceID,print_r($ReplayATData,1));

        if ($ReplayAPIData === false) {
            //          Senddata('TX_Status','Timeout');
            $this->unlock('RequestSendData');

            throw new Exception('Send Data Timeout', E_USER_NOTICE);
        }
        //            Senddata('TX_Status','OK')
        $this->unlock('RequestSendData');
        return $ReplayAPIData;
    }

    //################# DUMMYS / WOARKAROUNDS - protected

    private function WaitForResponse($APIData_Command)
    {
        for ($i = 0; $i < 300; $i++) {
            if ($this->ReplyAPIData === '') {
                IPS_Sleep(5);
            } else {
                if ($this->lock('ReplyAPIData')) {
                    $ret = $this->ReplyAPIData;
                    $this->ReplyAPIData = '';
                    $this->unlock('ReplyAPIData');
                    $JSON = json_decode($ret);
                    $APIData = new \OnkyoAVR\ISCP_API_Data();
                    $APIData->GetDataFromJSONObject($JSON);
                    if ($APIData_Command == $APIData->APICommand) {
                        return $APIData;
                    } else {
                        $i = $i - 100;
                        if ($i < 0) {
                            $i = 0;
                        }
                    }
                }
            }
        }
        if ($this->lock('ReplyAPIData')) {
            $this->ReplyAPIData = '';
            $this->unlock('ReplyAPIData');
        }

        return false;
    }

    protected function GetVariable($Ident, $VarType, $VarName, $Profile, $EnableAction)
    {
        $VarID = @$this->GetIDForIdent($Ident);
        if ($VarID > 0) {
            if (IPS_GetVariable($VarID)['VariableType'] != $VarType) {
                IPS_DeleteVariable($VarID);
                $VarID = false;
            }
        }
        if ($VarID === false) {
            $this->MaintainVariable($Ident, $VarName, $VarType, $Profile, 0, true);
            if ($EnableAction) {
                $this->MaintainAction($Ident, true);
            }
            $VarID = $this->GetIDForIdent($Ident);
        }
        return $VarID;
    }

}
