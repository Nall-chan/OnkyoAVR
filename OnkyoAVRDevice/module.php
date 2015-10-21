<?

require_once(__DIR__ . "/../OnkyoAVRClass.php");  // diverse Klassen

class OnkyoAVR extends IPSModule
{

    private $OnkyoZone = null;

    public function Create()
    {
        parent::Create();
        $this->RequireParent("{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}");
//        $this->RegisterPropertyBoolean("EmulateStatus", false);
        $this->RegisterPropertyInteger("Zone", ONKYO_Zone::None);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->RegisterVariableString("ReplyAPIData", "ReplyAPIData", "", -3);
        IPS_SetHidden($this->GetIDForIdent('ReplyAPIData'), true);
        if ($this->GetZone())
            $this->RequestZoneState();

//        if fKernelRunlevel = KR_READY then
//        $this->RegisterTimer('RequestPinState', $this->ReadPropertyInteger('Interval'), 'XBee_RequestState($_IPS[\'TARGET\']);');
//                                IDENT                 INTERVAL                                FUNKTION
//        $this->ReadPinConfig();


        /*
          fFrameID: byte;  // integer
          fFrameIDLock : TCriticalSection;  //Lock
          fReadyToSend : TEvent; // wird Lock
          fDataReadyToReadReply: TEvent; // Wenn ReplyATData <> ""
          fDelayTimerActive: boolean; //später ?
          fReplyATData : TXB_Command_Data; // String JSON
          fReplyATDataLock : TCriticalSection;  // Lock
         */
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

################## ActionHandler

    public function RequestAction($Ident, $Value)
    {
        /*        if (is_bool($Value) === false)
          throw new Exception('Wrong Datatype for ' . $Ident);
          $this->WriteBoolean($Ident, (bool) $Value);
         */
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

    /*    public function WriteBoolean(string $Pin, boolean $Value)
      {
      if ($Pin == '')
      throw new Exception('Pin is not Set!');
      if (!in_array($Pin, $this->DPin_Name))
      throw new Exception('Pin not exists!');
      $VarID = @$this->GetIDForIdent($Pin);
      if ($VarID === false)
      throw new Exception('Pin not exists! Try WriteParameter.');
      if (IPS_GetVariable($VarID)['VariableType'] !== 0)
      throw new Exception('Wrong Datatype for ' . $VarID);
      if ($Value === true)
      $ValueStr = 0x05;
      else
      $ValueStr = 0x04;
      $ATData = new TXB_Command_Data();
      $ATData->ATCommand = $Pin;
      $ATData->Data = chr($ValueStr);
      $this->SendCommand($ATData);
      if ($this->ReadPropertyBoolean('EmulateStatus'))
      SetValue($VarID, $Value);
      return true;
      }

      public function WriteParameter(string $Parameter, string $Value)
      {
      if ($Value == "")
      throw new Exception('Value is empty!');
      if (!in_array($Parameter, $this->AT_WriteCommand))
      throw new Exception('Unknown Parameter: ' . $Parameter);
      $ATData = new TXB_Command_Data();
      $ATData->ATCommand = $Parameter;
      $ATData->Data = $Value;
      $this->SendCommand($ATData);
      return true;
      }

      public function ReadParameter(string $Parameter)
      {
      if (!in_array($Parameter, $this->AT_ReadCommand))
      throw new Exception('Unknown Parameter: ' . $Parameter);
      $ATData = new TXB_Command_Data();
      $ATData->ATCommand = $Parameter;
      $ATData->Data = '';
      $ResponseATData = $this->SendCommand($ATData);
      return $ResponseATData->Data;
      }
     */
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
        IPS_LogMessage('ReceiveAPIData1', print_r($APIData, true));
        if ($this->OnkyoZone->CmdAvaiable($APIData) === false)
            if ($this->OnkyoZone->SubCmdAvaiable($APIData) === false)
                return false;
        $APIData->GetMapping();
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
        IPS_LogMessage('ReceiveAPIData2', print_r($APIData, true));

        if ($APIData->Mapping->IsVariable)
            ;



        // TODO Prüfen ob Variable nachgeführt werden muss.

        /*      switch ($ATData->ATCommand)
          {
          case TXB_AT_Command::XB_AT_D0:
          case TXB_AT_Command::XB_AT_D1:
          case TXB_AT_Command::XB_AT_D2:
          case TXB_AT_Command::XB_AT_D3:
          case TXB_AT_Command::XB_AT_D4:
          case TXB_AT_Command::XB_AT_D5:
          case TXB_AT_Command::XB_AT_D6:
          case TXB_AT_Command::XB_AT_D7:
          case TXB_AT_Command::XB_AT_P0:
          case TXB_AT_Command::XB_AT_P1:
          case TXB_AT_Command::XB_AT_P2:
          // Neuen Wert darstellen und Variable anlegen und Schaltbar machen wenn Value 4 oder 5 sonst nicht schaltbar
          if (strlen($ATData->Data) <> 1)
          return;
          switch (ord($ATData->Data))
          {
          case 0:
          case 1:
          $VarID = @$this->GetIDForIdent($ATData->ATCommand);
          if ($VarID <> 0)
          {
          $this->DisableAction($ATData->ATCommand);
          IPS_SetVariableCustomProfile($VarID, '');
          }
          break;
          case 2:

          $VarID = $this->RegisterVariableInteger('A' . $ATData->ATCommand, 'A' . $ATData->ATCommand);
          if ($VarID <> 0)
          {
          $this->DisableAction($ATData->ATCommand);
          IPS_SetVariableCustomProfile($VarID, '');
          }
          break;
          case 3:
          $VarID = $this->RegisterVariableBoolean($ATData->ATCommand, $ATData->ATCommand);
          $this->DisableAction($ATData->ATCommand);
          IPS_SetVariableCustomProfile($VarID, '');
          break;
          case 4:
          $VarID = $this->RegisterVariableBoolean($ATData->ATCommand, $ATData->ATCommand);
          IPS_SetVariableCustomProfile($VarID, '~Switch');
          $this->EnableAction($ATData->ATCommand);
          SetValueBoolean($VarID, false);
          break;
          case 5:
          $VarID = $this->RegisterVariableBoolean($ATData->ATCommand, $ATData->ATCommand);
          IPS_SetVariableCustomProfile($VarID, '~Switch');
          $this->EnableAction($ATData->ATCommand);
          SetValueBoolean($VarID, true);
          break;
          }
          break;
          case TXB_AT_Command::XB_AT_IS:
          //                if not fDelayTimerActive then
          $IOSample = new TXB_API_IO_Sample();
          $IOSample->Status = TXB_Receive_Status::XB_Receive_Packet_Acknowledged;
          $IOSample->Sample = $ATData->Data;
          $this->DecodeIOSample($IOSample);
          break;
          } */
    }

    /*
      private function DecodeIOSample(TXB_API_IO_Sample $IOSample)
      {
      $ActiveDPins = unpack("n", substr($IOSample->Sample, 1, 2))[1];
      $ActiveAPins = ord($IOSample->Sample[3]);
      if ($ActiveDPins <> 0)
      {
      $PinValue = unpack("n", substr($IOSample->Sample, 4, 2))[1];
      foreach ($this->DPin_Name as $Index => $Pin_Name)
      {
      if ($Pin_Name == '')
      continue;
      $Bit = pow(2, $Index);
      if (($ActiveDPins & $Bit) == $Bit)
      {
      //                        {$IFDEF DEBUG}        SendData('DPIN','I:'+floattostr(Power(2,ord(i))));{$ENDIF}
      $VarID = @$this->GetIDForIdent($Pin_Name);
      if ($VarID === false)
      $VarID = $this->RegisterVariableBoolean($Pin_Name, $Pin_Name);

      if (($PinValue & $Bit) == $Bit)
      {
      //                            {$IFDEF DEBUG}          SendData(DPin_Name[i],'true - Bit:'+inttostr(ord(i)));{$ENDIF}
      SetValueBoolean($VarID, true);
      }
      else
      {
      //                            {$IFDEF DEBUG}          SendData(DPin_Name[i],'false - Bit:'+inttostr(ord(i)));{$ENDIF}
      SetValueBoolean($VarID, false);
      }
      }
      }
      }
      if ($ActiveAPins <> 0)
      {
      $i=0;
      foreach ($this->APin_Name as $Index => $Pin_Name)
      {
      if ($Pin_Name == "")
      continue;;
      $Bit = pow(2, $Index);
      if (($ActiveAPins & $Bit) == $Bit)
      {
      //                    {$IFDEF DEBUG}        SendData('APIN','I:'+floattostr(Power(2,ord(i))));{$ENDIF}
      $PinAValue = 0;
      $PinAValue = unpack("n", substr($IOSample->Sample, 6 + ($i*2), 2))[1];
      $PinAValue = $PinAValue * 1.171875;

      if ($Pin_Name == 'VSS')
      {
      $VarID = @$this->GetIDForIdent($Pin_Name);
      if ($VarID === false)
      $VarID = $this->RegisterVariableFloat('VSS', 'VSS', '~Volt');
      SetValueFloat($VarID, $PinAValue / 1000);
      }
      else
      {
      $VarID = @$this->GetIDForIdent($Pin_Name);
      if ($VarID === false)
      $VarID = $this->RegisterVariableInteger($Pin_Name, $Pin_Name);
      SetValueInteger($VarID, $PinAValue);
      }
      $i++;
      }
      }
      }
      }

      private function ReadPinConfig()
      {
      $ATData = new TXB_Command_Data();
      $ATData->Data = '';
      foreach ($this->DPin_Name as $Pin)
      {
      if ($Pin== '') continue;

      $ATData->ATCommand = $Pin;
      $this->SendCommand($ATData);
      }
      } */

//------------------------------------------------------------------------------
    private function RequestZoneState()
    {
        $APIData = new ISCP_API_Data();

        // Schleife von allen CMDs welche als Variable in dieser Zone sind.
        $APIData->APICommand = ISCP_API_Commands::PWR;
        $APIData->Data = ISCP_API_Commands::Request;
        $result = $this->SendCommand($APIData);
        IPS_LogMessage('RequestZoneStateResult', print_r($result, true));
        // Schleife ende

        /*        $ATData = new TXB_Command_Data();
          $ATData->ATCommand = TXB_AT_Command::XB_AT_IS;
          $this->SendCommand($ATData); */
    }

    private function SendCommand(ISCP_API_Data $APIData)
    {
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
        $ReplayAPIData = $this->WaitForResponse();

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

    private function WaitForResponse()
    {
        $ReplyAPIDataID = $this->GetIDForIdent('ReplyAPIData');
        for ($i = 0; $i < 500; $i++)
        {
            if (GetValueString($ReplyAPIDataID) === '')
                IPS_Sleep(10);
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