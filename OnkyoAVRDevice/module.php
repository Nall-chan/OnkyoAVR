<?

require_once(__DIR__ . "/../OnkyoAVRClass.php");  // diverse Klassen

class OnkyoAVR extends IPSModule
{

    private $OnkyoZone = null;

    public function Create()
    {
        parent::Create();
        $this->ConnectParent("{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}");
//        $this->RegisterPropertyBoolean("EmulateStatus", false);
        $this->RegisterPropertyInteger("Zone", ONKYO_Zone::None);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->RegisterVariableString("ReplyAPIData", "ReplyAPIData", "", -3);
        IPS_SetHidden($this->GetIDForIdent('ReplyAPIData'), true);

        foreach (IPSProfiles::$ProfilAssociations as $Profile => $Association)
        {
            $this->RegisterProfileIntegerEx($Profile, "", "", "", $Association);
        }
        foreach (IPSProfiles::$ProfilInteger as $Profile => $Size)
        {
            $this->RegisterProfileInteger($Profile, "", "", "", $Size[0], $Size[1], $Size[2]);
        }
//        if fKernelRunlevel = KR_READY then
        if ($this->GetZone())
            $this->RequestZoneState();
    }

################## PRIVATE     

    private function GetZone()
    {
        $this->OnkyoZone = new ONKYO_Zone();
        $this->OnkyoZone->thisZone = $this->ReadPropertyInteger("Zone");
        if ($this->OnkyoZone->thisZone == ONKYO_Zone::None)
            return false;
        return true;
    }

    private function UpdateVariable(ISCP_API_Data $APIData)
    {
        if ($APIData->Data == "N/A")
            return;
        switch ($APIData->Mapping->VarType)
        {
            case IPSVarType::vtBoolean:
                $VarID = $this->GetVariable($APIData->APICommand, $APIData->Mapping->VarType, $APIData->Mapping->VarName, $APIData->Mapping->Profile, $APIData->Mapping->EnableAction);
                $Value = ISCP_API_Commands::$BoolValueMapping[$APIData->Data];
                SetValueBoolean($VarID, $Value);
                break;
            case IPSVarType::vtFloat:
                $VarID = $this->GetVariable($APIData->APICommand, $APIData->Mapping->VarType, $APIData->Mapping->VarName, $APIData->Mapping->Profile, $APIData->Mapping->EnableAction);
                $Value = $APIData / 100;
                SetValueFloat($VarID, $Value);

//                throw new Exception("Float VarType not implemented.");
                break;
            case IPSVarType::vtInteger:
                $VarID = $this->GetVariable($APIData->APICommand, $APIData->Mapping->VarType, $APIData->Mapping->VarName, $APIData->Mapping->Profile, $APIData->Mapping->EnableAction);
                $Value = hexdec($APIData->Data);
                SetValueInteger($VarID, $Value);
                break;
            case IPSVarType::vtString:
                $VarID = $this->GetVariable($APIData->APICommand, $APIData->Mapping->VarType, $APIData->Mapping->VarName, $APIData->Mapping->Profile, $APIData->Mapping->EnableAction);
                $Value = $APIData->Data;
                SetValueString($VarID, $Value);
                break;
            case IPSVarType::vtDualInteger:
                {
                    $Prefix = substr($APIData->Data, 0, 1);
                    $VarID = $this->GetVariable($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix], IPSVarType::vtInteger, $APIData->Mapping->VarName[$Prefix], $APIData->Mapping->Profile, $APIData->Mapping->EnableAction);
                    $Value = $APIData->Mapping->ValueMapping[substr($APIData->Data, 1, 2)];
                    SetValueInteger($VarID, $Value);
                    if (strlen($APIData->Data) > 3)
                    {
                        $Prefix = substr($APIData->Data, 3, 1);
                        $VarID = $this->GetVariable($APIData->APICommand . $APIData->Mapping->ValuePrefix[$Prefix], IPSVarType::vtInteger, $APIData->Mapping->VarName[$Prefix], $APIData->Mapping->Profile, $APIData->Mapping->EnableAction);
                        $Value = $APIData->Mapping->ValueMapping[substr($APIData->Data, 4, 2)];
                        SetValueInteger($VarID, $Value);
                    }
                }
        }
    }

################## ActionHandler

    public function RequestAction($Ident, $Value)
    {
        if (!$this->GetZone())
            throw new Exception("Illegal Zone");

        $APIData = new ISCP_API_Data();
        $APIData->APICommand = substr($Ident, 0, 3);
        $APIData->Data = $Value;
        if (!$this->OnkyoZone->CmdAvaiable($APIData))
        {
            echo "Illegal Command in this Zone";
            return;
        }
        // Mapping holen
        $APIData->GetMapping();
        $APIData->APICommand = $Ident;
        IPS_LogMessage('RequestValueMapping', print_r($APIData, 1));
        // Daten senden        Rückgabe ist egal, Variable wird automatisch durch Datenempfang nachgeführt
        try
        {
            $ret = $this->SendAPIData($APIData);
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
            return;
        }

        /*        if ($ret === false)
          {
          echo "Error on Send.";
          return;
          } */
    }

################## PUBLIC
    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     */

    public function RequestState()
    {
        if (!$this->HasActiveParent())
            throw new Exception('Instance has no active Parent Instance!');
        if ($this->GetZone())
            $this->RequestZoneState();
    }

    public function Power(boolean $Value)
    {
        if (!$this->GetZone())
        {
            echo "Zone not set!";
            return;
        }
        $APIData = new ISCP_API_Data();
        $APIData->Data=$Value;
        switch ($this->OnkyoZone->thisZone)
        {
            case ONKYO_Zone::ZoneMain:
                $APIData->APICommand = ISCP_API_Commands::PWR;
                break;
            case ONKYO_Zone::Zone2:
                $APIData->APICommand = ISCP_API_Commands::ZPW;
                break;
            case ONKYO_Zone::Zone3:
                $APIData->APICommand = ISCP_API_Commands::PW3;
                break;
            case ONKYO_Zone::Zone4:
                $APIData->APICommand = ISCP_API_Commands::PW4;
                break;
        }
        try
        {
            $this->SendAPIData($APIData);
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
            return false;
        }
        return true;
    }

    public function SetVolume(integer $Value)
    {
        if (!$this->GetZone())
        {
            echo "Zone not set!";
            return;
        }
        $APIData = new ISCP_API_Data();
        $APIData->Data=$Value;
        switch ($this->OnkyoZone->thisZone)
        {
            case ONKYO_Zone::ZoneMain:
                $APIData->APICommand = ISCP_API_Commands::MVL;
                break;
            case ONKYO_Zone::Zone2:
                $APIData->APICommand = ISCP_API_Commands::ZVL;
                break;
            case ONKYO_Zone::Zone3:
                $APIData->APICommand = ISCP_API_Commands::VL3;
                break;
            case ONKYO_Zone::Zone4:
                $APIData->APICommand = ISCP_API_Commands::VL4;
                break;
        }
        try
        {
            $this->SendAPIData($APIData);
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
            return false;
        }
        return true;
    }

    public function SetMute(boolean $Value)
    {
        if (!$this->GetZone())
        {
            echo "Zone not set!";
            return;
        }
        $APIData = new ISCP_API_Data();
        $APIData->Data=$Value;
        switch ($this->OnkyoZone->thisZone)
        {
            case ONKYO_Zone::ZoneMain:
                $APIData->APICommand = ISCP_API_Commands::AMT;
                break;
            case ONKYO_Zone::Zone2:
                $APIData->APICommand = ISCP_API_Commands::ZMT;
                break;
            case ONKYO_Zone::Zone3:
                $APIData->APICommand = ISCP_API_Commands::MT3;
                break;
            case ONKYO_Zone::Zone4:
                $APIData->APICommand = ISCP_API_Commands::MT4;
                break;
        }
        try
        {
            $this->SendAPIData($APIData);
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
            return false;
        }
        return true;
        
    }

    public function SelectInput(integer $Value)
    {
        
    }

    public function SelectListingMode(integer $Value)
    {
        
    }

    public function SetSleep(integer $Value)
    {
        
    }

    public function SendTVCommand(string $Command)
    {
        if (!$this->GetZone())
        {
            echo "Zone not set!";
            return;
        }
        $APIData = new ISCP_API_Data();
        $APIData->APICommand = ISCP_API_Commands::CTV;
        $APIData->Data=$Command;
        try
        {
            $APIResult= $this->SendData($APIData);
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
            return false;
        }
        if ($APIResult->Data =="N/A")
        {
            echo "Command (temporally) not available.";
            return false;
        }
        if ($APIResult->Data <> $APIData->Data)
        {
            echo "Value not available.";
            return false;
        }
        return true;
        
    }

    public function SendBDCommand(string $Command)
    {
        if (!$this->GetZone())
        {
            echo "Zone not set!";
            return;
        }
        $APIData = new ISCP_API_Data();
        $APIData->APICommand = ISCP_API_Commands::CDV;
        $APIData->Data=$Command;
        try
        {
            $APIResult= $this->SendData($APIData);
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
            return false;
        }
        if ($APIResult->Data =="N/A")
        {
            echo "Command (temporally) not available.";
            return false;
        }
        if ($APIResult->Data <> $APIData->Data)
        {
            echo "Value not available.";
            return false;
        }
        return true;        
    }

    public function SendCommand(string $Command, string $Value)
    {
        if (!$this->GetZone())
        {
            echo "Zone not set!";
            return;
        }
        $APIData = new ISCP_API_Data();
        $APIData->APICommand = $$Command;
        $APIData->Data=$Value;
        try
        {
            $APIResult= $this->SendData($APIData);
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
            return false;
        }
        if ($APIResult->Data =="N/A")
        {
            echo "Command (temporally) not available.";
            return false;
        }
        if ($APIResult->Data <> $APIData->Data)
        {
            echo "Value not available.";
            return false;
        }
        return true;        
    }

################## Datapoints

    public function ReceiveData($JSONString)
    {
        $Data = json_decode($JSONString);
//IPS_LogMessage('ReceiveData',print_r($Data,true));
        if ($Data->DataID <> '{43E4B48E-2345-4A9A-B506-3E8E7A964757}')
            return false;
        if ($this->GetZone() === false)
            return false;

        $APIData = new ISCP_API_Data();
        $APIData->GetDataFromJSONObject($Data);
//        IPS_LogMessage('ReceiveAPIData1', print_r($APIData, true));

        if ($this->OnkyoZone->CmdAvaiable($APIData) === false)
        {
//            IPS_LogMessage('CmdAvaiable', 'false');

            if ($this->OnkyoZone->SubCmdAvaiable($APIData) === false)
            {
//                IPS_LogMessage('SubCmdAvaiable', 'false');
                return false;
            }
            else
            {
                $APIData->GetMapping();
                $APIData->APICommand = $APIData->APISubCommand->{$this->OnkyoZone->thisZone};
                IPS_LogMessage('APISubCommand', $APIData->APICommand);
            }
        }
        else
            $APIData->GetMapping();

//        IPS_LogMessage('ReceiveAPIData2', print_r($APIData, true));


        $this->ReceiveAPIData($APIData);
    }

    private function ReceiveAPIData(ISCP_API_Data $APIData)
    {
        $ReplyAPIDataID = $this->GetIDForIdent('ReplyAPIData');
        $ReplyAPIData = $APIData->ToJSONString('');

        if (!$this->lock('ReplyAPIData'))
            throw new Exception('ReplyAPIData is locked');
        SetValueString($ReplyAPIDataID, $ReplyAPIData);
        $this->unlock('ReplyAPIData');
//        IPS_LogMessage('ReceiveAPIData2', print_r($APIData, true));
        if ($APIData->Mapping <> null)
            if ($APIData->Mapping->IsVariable)
                $this->UpdateVariable($APIData);
    }

//------------------------------------------------------------------------------
    private function RequestZoneState()
    {
        // Schleife von allen CMDs welche als Variable in dieser Zone sind.

        foreach (ONKYO_Zone::$ZoneCMDs[$this->OnkyoZone->thisZone] as $ApiCmd)
        {
            $APIData = new ISCP_API_Data();
            $APIData->APICommand = $ApiCmd;
            $APIData->GetMapping();
            if ($APIData->Mapping !== null)
                if ($APIData->Mapping->RequestValue)
                {
                    $APIData->Data = ISCP_API_Commands::Request;
                    try
                    {
                        $result = $this->Send($APIData);
                    }
                    catch (Exception $exc)
                    {
                        unset($exc);
                    }
                    IPS_LogMessage('RequestZoneStateResult', print_r($result, true));
                }
        }
    }

    private function SendAPIData(ISCP_API_Data $APIData)
    {
        $DualType = substr($APIData->APICommand, 3, 1);
        $APIData->APICommand = substr($APIData->APICommand, 0, 3);
        if (!property_exists($APIData->Mapping,'VarType'))
            $APIData->GetMapping();

        // Variable konvertieren..        
        switch ($APIData->Mapping->VarType)
        {
            case IPSVarType::vtBoolean:
                $APIData->Data = ISCP_API_Commands::$BoolValueMapping[$APIData->Data];
                break;
            case IPSVarType::vtFloat:
//                echo "Float VarType not implemented.";

                throw new Exception("Float VarType not implemented.");
                break;
            case IPSVarType::vtInteger:
                if ($APIData->Mapping->ValueMapping == null)
                    $APIData->Data = strtoupper(substr('0' . dechex($APIData->Data), -2));
                else
                {
                    $Mapping = array_flip($APIData->Mapping->ValueMapping);
                    if (array_key_exists($APIData->Data, $Mapping))
                        $APIData->Data = $Mapping[$APIData->Data];
                    else
                        $APIData->Data = strtoupper(substr('0' . dechex($APIData->Data), -2));
                }
                break;
            case IPSVarType::vtDualInteger:
                if ($DualType === false)
                {
                    throw new Exception("Error on get DualInteger.");
//                    echo "Error on get DualInteger.";
//                    return false;
                }
                $Prefix = array_flip($APIData->Mapping->ValuePrefix)[$DualType];
                $Mapping = array_flip($APIData->Mapping->ValueMapping);
                if (array_key_exists($APIData->Data, $Mapping))
                    $APIData->Data = $Prefix . $Mapping[$APIData->Data];
                else
                    $APIData->Data = strtoupper($Prefix . substr('0' . dechex($APIData->Data), -2));
                break;
            default:
//                echo "Unknow VarType.";
//                return;
                throw new Exception("Unknow VarType.");
                break;
        }
        try
        {
            $ret = $this->Send($APIData);
        }
        catch (Exception $exc)
        {
            throw $exc;
        }

        if ($ret->Data == "N/A")
        {
                throw new Exception("Command (temporally) not available.");
//            return;
        }
        switch ($APIData->Mapping->VarType)
        {
            case IPSVarType::vtBoolean:
            case IPSVarType::vtInteger:
            case IPSVarType::vtFloat:
                if ($ret->Data <> $APIData->Data)
                {
                    IPS_LogMessage('RequestAction', print_r($APIData, 1));
                    IPS_LogMessage('RequestActionResult', print_r($ret, 1));
                throw new Exception("Value not available.");
//                    echo "Value not available.";
//                    return;
                }
                break;
            case IPSVarType::vtDualInteger:
                if (strpos($ret->Data, $APIData->Data) === false)
                {
                    IPS_LogMessage('RequestAction', print_r($APIData, 1));
                    IPS_LogMessage('RequestActionResult', print_r($ret, 1));
                throw new Exception("Value not available.");
//                    echo "Value not available.";
//                    return;
                }
                break;
        }
        
        return $ret;
    }

    private function Send(ISCP_API_Data $APIData)
    {
        if (!$this->OnkyoZone->CmdAvaiable($APIData))
            throw new Exception("Command not available at this Zone.");
        if (!$this->HasActiveParent())
            throw new Exception("Instance has no active Parent.");

        $ReplyAPIDataID = $this->GetIDForIdent('ReplyAPIData');
        if (!$this->lock('RequestSendData'))
            throw new Exception('RequestSendData is locked');

        if (!$this->lock('ReplyAPIData'))
        {
            $this->unlock('RequestSendData');
            throw new Exception('ReplyAPIData is locked');
        }
        SetValueString($ReplyAPIDataID, '');
        $this->unlock('ReplyAPIData');
        try
        {
            $this->SendDataToParent($APIData);
        }
        catch (Exception $exc)
        {
            $this->unlock('RequestSendData');
            throw new Exception($exc);
        }
        $ReplayAPIData = $this->WaitForResponse($APIData->APICommand);

        //        IPS_LogMessage('ReplayATData:'.$this->InstanceID,print_r($ReplayATData,1));

        if ($ReplayAPIData === false)
        {
            //          Senddata('TX_Status','Timeout');
            $this->unlock('RequestSendData');
            throw new Exception('Send Data Timeout');
        }
        //            Senddata('TX_Status','OK')
        $this->unlock('RequestSendData');
        return $ReplayAPIData;
    }

    protected function SendDataToParent($Data)
    {
        // API-Daten verpacken und dann versenden.
        $JSONString = $Data->ToJSONString('{8F47273A-0B69-489E-AF36-F391AE5FBEC0}');
//        IPS_LogMessage('SendDataToSplitter:'.$this->InstanceID,$JSONString);
        // Daten senden
        IPS_SendDataToParent($this->InstanceID, $JSONString);
        return true;
    }

################## DUMMYS / WOARKAROUNDS - protected

    private function WaitForResponse($APIData_Command)
    {
        $ReplyAPIDataID = $this->GetIDForIdent('ReplyAPIData');
        for ($i = 0; $i < 300; $i++)
        {
            if (GetValueString($ReplyAPIDataID) === '')
                IPS_Sleep(4);
            else
            {
                if ($this->lock('ReplyAPIData'))
                {
                    $ret = GetValueString($ReplyAPIDataID);
                    SetValueString($ReplyAPIDataID, '');
                    $this->unlock('ReplyAPIData');
                    $JSON = json_decode($ret);
                    $APIData = new ISCP_API_Data();
                    $APIData->GetDataFromJSONObject($JSON);
                    if ($APIData_Command == $APIData->APICommand)
                        return $APIData;
                }
            }
        }
        if ($this->lock('ReplyAPIData'))
        {
            SetValueString($ReplyAPIDataID, '');
            $this->unlock('ReplyAPIData');
        }

        return false;
    }

    protected function HasActiveParent()
    {
//        IPS_LogMessage(__CLASS__, __FUNCTION__); //          
        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] > 0)
        {
            $parent = IPS_GetInstance($instance['ConnectionID']);

            if ($parent['InstanceStatus'] == 102)
                return true;
        }
        return false;
    }

    protected function GetVariable($Ident, $VarType, $VarName, $Profile, $EnableAction)
    {
        $VarID = @$this->GetIDForIdent($Ident);
        if ($VarID > 0)
        {
            if (IPS_GetVariable($VarID)['VariableType'] <> $VarType)
            {
                IPS_DeleteVariable($VarID);
                $VarID = false;
            }
        }
        if ($VarID === false)
        {
            $this->MaintainVariable($Ident, $VarName, $VarType, $Profile, 0, true);
            if ($EnableAction)
                $this->MaintainAction($Ident, true);
            $VarID = $this->GetIDForIdent($Ident);
        }
        return $VarID;
    }

    protected function RegisterTimer($Name, $Interval, $Script)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id === false)
            $id = 0;


        if ($id > 0)
        {
            if (!IPS_EventExists($id))
                throw new Exception("Ident with name " . $Name . " is used for wrong object type");

            if (IPS_GetEvent($id)['EventType'] <> 1)
            {
                IPS_DeleteEvent($id);
                $id = 0;
            }
        }

        if ($id == 0)
        {
            $id = IPS_CreateEvent(1);
            IPS_SetParent($id, $this->InstanceID);
            IPS_SetIdent($id, $Name);
        } IPS_SetName($id, $Name);
        IPS_SetHidden($id, true);
        IPS_SetEventScript($id, $Script);
        if ($Interval > 0)
        {
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $Interval);

            IPS_SetEventActive($id, true);
        }
        else
        {
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);

            IPS_SetEventActive($id, false);
        }
    }

    protected function UnregisterTimer($Name)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id > 0)
        {
            if (!IPS_EventExists($id))
                throw new Exception('Timer not present'
                );
            IPS_DeleteEvent($id);
        }
    }

    protected function SetTimerInterval($Name, $Interval)
    {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id === false)
            throw new Exception(
            'Timer not present');
        if (!IPS_EventExists($id))
            throw new Exception('Timer not present');

        $Event = IPS_GetEvent($id);

        if ($Interval < 1)
        {
            if ($Event['EventActive'])
                IPS_SetEventActive($id, false);
        }
        else
        {
            if
            ($Event['CyclicTimeValue'] <> $Interval)
                IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $Interval)
                ;
            if (!$Event['EventActive'])
                IPS_SetEventActive($id, true);
        }
    }

    protected function SetStatus($InstanceStatus)
    {
        if ($InstanceStatus <>
                IPS_GetInstance($this->InstanceID)['InstanceStatus'])
            parent::SetStatus($InstanceStatus);
    }

    protected
            function LogMessage($data, $cata)
    {
        
    }

    protected function SetSummary($data)
    {
//        IPS_LogMessage(__CLASS__, __FUNCTION__ . "Data:" . $data); //                   
    }

    //Remove on next Symcon update
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {

        if (!IPS_VariableProfileExists($Name))
        {
            IPS_CreateVariableProfile($Name, 1);
        }
        else
        {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 1)
                throw new Exception("Variable profile type does not match for profile " . $Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (sizeof($Associations) === 0)
        {
            $MinValue = 0;
            $MaxValue = 0;
        }
        else
        {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations) - 1][0];
        }

        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

        foreach ($Associations as $Association)
        {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }

################## SEMAPHOREN Helper  - private  

    private function lock($ident)
    {
        for ($i = 0; $i < 100; $i ++)
        {
            if (IPS_SemaphoreEnter("OAVR_" . (string) $this->InstanceID . (string) $ident, 1))
            {
                return true;
            }
            else
            {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    private function unlock($ident)
    {
        IPS_SemaphoreLeave("OAVR_" . (string) $this->InstanceID . (string) $ident);
    }

}

?>