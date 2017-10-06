<?

require_once(__DIR__ . "/../libs/OnkyoAVRClass.php");  // diverse Klassen

class ISCPSplitter extends IPSModule
{

    const LAN = 1;
    const COM = 2;

    private $Mode = self::LAN; // off // 1 = LAN // 2  = COM
    private $eISCPVersion = 1;

    public function Create()
    {
        parent::Create();
        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
        $this->RegisterTimer('KeepAlive', 0, 'ISCP_KeepAlive($_IPS[\'TARGET\']);');        
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $this->RegisterVariableString("BufferIN", "BufferIN", "", -4);
        $this->RegisterVariableString("CommandOut", "CommandOut", "", -3);
        IPS_SetHidden($this->GetIDForIdent('CommandOut'), true);
        IPS_SetHidden($this->GetIDForIdent('BufferIN'), true);

        if ($this->CheckParents())
        {
            $this->RequestAVRState();
            //$this->SetTimerInterval('KeepAlive', 3600*1000);
        }
    }

################## PRIVATE     

    private function CheckParents()
    {
        $result = $this->HasActiveParent();
        if ($result)
        {
            $instance = IPS_GetInstance($this->InstanceID);
            $parentGUID = IPS_GetInstance($instance['ConnectionID'])['ModuleInfo']['ModuleID'];
            if ($parentGUID == '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}')
                $this->Mode = ISCPSplitter::LAN;
            elseif ($parentGUID == '{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}')
                $this->Mode = ISCPSplitter::COM;
            else
            {
                IPS_LogMessage('ISCP Gateway', 'IO-Parent not supported.');
                $this->Mode = false;
                $result = false;
            }
        }
        return $result;
    }

    private function RequestAVRState()
    {
        //if fKernelRunlevel <> KR_READY then exit;
        // TODO
    }

    private function DecodeData($Frame)
    {
        if ($Frame[0] <> '!')
        {
            echo 'ISCP Frame without !';
            return;
        }
        if ($Frame[1] <> '1')
        {
            echo 'Device Typ ' . $Frame[1] . ' not implemented';
            return;
        }
        $APIData = new ISCP_API_Data();
        $APIData->APICommand = substr($Frame, 2, 3);
        $APIData->Data = substr($Frame, 5);
        $APIData->GetSubCommand();
        $this->SendDataToZone($APIData);
    }

################## PUBLIC
    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     */

    public function RequestState()
    {
        if ($this->CheckParents())
            $this->RequestAVRState();
    }

################## DATAPOINT RECEIVE FROM CHILD

    public function ForwardData($JSONString)
    {
        $Data = json_decode($JSONString);
        if ($Data->DataID <> "{8F47273A-0B69-489E-AF36-F391AE5FBEC0}")
            return false;
        $APIData = new ISCP_API_Data();
        $APIData->GetDataFromJSONObject($Data);
        try
        {
            $this->ForwardDataFromDevice($APIData);
            
        } catch (Exception $ex)
        {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
    }

################## DATAPOINTS DEVICE

    private function ForwardDataFromDevice(ISCP_API_Data $APIData)
    {
        if (is_bool($APIData->Data))
        {
            $APIData->Data = ISCP_API_Commands::$BoolValueMapping($APIData->Data);
        }
        elseif (is_int($APIData->Data))
        {
            $APIData->Data = strlen(dechex($APIData->Data)) == 1 ? "0" . dechex($APIData->Data) : dechex($APIData->Data);
        }
        $Frame = "!1" . $APIData->APICommand . $APIData->Data . chr(0x0D) . chr(0x0A);
        try
        {
        $this->SendDataToParent($Frame);
            
        } catch (Exception $ex)
        {
            throw new Exception($ex->getMessage(), E_USER_NOTICE);
        }

    }

    private function SendDataToZone(ISCP_API_Data $APIData)
    {
//        IPS_LogMessage('SendDataToZone',print_r($APIData,true));
        $Data = $APIData->ToJSONString('{43E4B48E-2345-4A9A-B506-3E8E7A964757}');
        $this->SendDataToChildren($Data);
    }

################## DATAPOINTS PARENT

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        //IPS_LogMessage('ReceiveDataFrom???:'.$this->InstanceID,  print_r($data,1));
        $this->CheckParents();
        if ($this->Mode === false){
    trigger_error("Wrong IO-Parent",E_USER_WARNING);
//            echo "Wrong IO-Parent";
            return false;
        }
        $bufferID = $this->GetIDForIdent("BufferIN");
        // Empfangs Lock setzen
        if (!$this->lock("ReceiveLock"))
        {
            trigger_error("ReceiveBuffer is locked",E_USER_NOTICE);
            return false;

//            throw new Exception("ReceiveBuffer is locked",E_USER_NOTICE);
        }
        // Datenstream zusammenfügen
        $head = GetValueString($bufferID);
        SetValueString($bufferID, '');
        // Stream in einzelne Pakete schneiden
        $stream = $head . utf8_decode($data->Buffer);
        if ($this->Mode == ISCPSplitter::LAN)
        {
            $minTail = 24;

            $start = strpos($stream, 'ISCP');
            if ($start === false)
            {
                IPS_LogMessage('ISCP Gateway', 'LANFrame without ISCP');
                $stream = '';
            }
            elseif ($start > 0)
            {
                IPS_LogMessage('ISCP Gateway', 'LANFrame start not with ISCP');
                $stream = substr($stream, $start);
            }
            //Paket suchen
            if (strlen($stream) < $minTail)
            {
                IPS_LogMessage('ISCP Gateway', 'LANFrame to short');
                SetValueString($bufferID, $stream);
                $this->unlock("ReceiveLock");
                return;
            }
            $header_len = ord($stream[6]) * 256 + ord($stream[7]);
            $frame_len = ord($stream[10]) * 256 + ord($stream[11]);
//             IPS_LogMessage('ISCP Gateway', 'LANFrame info ' . $header_len. '+'. $frame_len . ' Bytes.');            
            if (strlen($stream) < $header_len + $frame_len)
            {
                IPS_LogMessage('ISCP Gateway', 'LANFrame must have ' . $header_len . '+' . $frame_len . ' Bytes. ' . strlen($stream) . ' Bytes given.');
                SetValueString($bufferID, $stream);
                $this->unlock("ReceiveLock");
                return;
            }
            $header = substr($stream, 0, $header_len);
            $frame = substr($stream, $header_len, $frame_len);
            //EOT wegschneiden von reschts, aber nur wenn es einer der letzten drei zeichen ist
            $end = strrpos($frame, chr(0x1A));
            if ($end >= $frame_len - 3)
                $frame = substr($frame, 0, $end);
            //EOT wegschneiden von reschts, aber nur wenn es einer der letzten drei zeichen ist
            $end = strrpos($frame, chr(0x0D));
            if ($end >= $frame_len - 3)
                $frame = substr($frame, 0, $end);
            //EOT wegschneiden von reschts, aber nur wenn es einer der letzten drei zeichen ist
            $end = strrpos($frame, chr(0x0A));
            if ($end >= $frame_len - 3)
                $frame = substr($frame, 0, $end);
//                IPS_LogMessage('ISCP Gateway', 'LAN $header:' . $header);
//                IPS_LogMessage('ISCP Gateway', 'LAN $frame:' . $frame);
// 49 53 43 50  // ISCP
// 00 00 00 10  // HEADERLEN
// 00 00 00 0B  // DATALEN
// 01 00 00 00  // Version
// 21 31 4E 4C  // !1NL
// 53 43 2D 50  // SC-P
// 1A 0D 0A     // EOT CR LF
            $tail = substr($stream, $header_len + $frame_len);
            if ($this->eISCPVersion <> ord($header[12]))
            {
                $frame = false;
                trigger_error("Wrong eISCP Version",E_USER_NOTICE);
            }
        }
        else
        {
            $minTail = 6;
            $start = strpos($stream, '!');
            if ($start === false)
            {
                IPS_LogMessage('ISCP Gateway', 'eISCP Frame without !');
                $stream = '';
            }
            elseif ($start > 0)
            {
                IPS_LogMessage('ISCP Gateway', 'eISCP Frame do not start with !');
                $stream = substr($stream, $start);
            }
            //Paket suchen
            $end = strpos($stream, chr(0x1A));
            if (($end === false) or ( strlen($stream) < $minTail)) // Kein EOT oder zu klein
            {
                IPS_LogMessage('ISCP Gateway', 'eISCP Frame to short');
                SetValueString($bufferID, $stream);
                $this->unlock("ReceiveLock");
                return;
            }
            $frame = substr($stream, $start, $end - $start);
            // Ende wieder in den Buffer werfen
            $tail = ltrim(substr($stream, $end));
        }
        if ($tail === false)
            $tail = '';
        SetValueString($bufferID, $tail);
        $this->unlock("ReceiveLock");
        if ($frame !== false)
            $this->DecodeData($frame);
        // Ende war länger als 6 / 23 ? Dann nochmal Packet suchen.
        if (strlen($tail) >= $minTail)
            $this->ReceiveData(json_encode(array('Buffer' => '')));
        return true;
    }

    protected function SendDataToParent($Data)
    {
//        IPS_LogMessage('SendDataToSerialPort:'.$this->InstanceID,$Data);
        //Parent ok ?
        if (!$this->CheckParents())
            throw new Exception("Instance has no active Parent.",E_USER_NOTICE);
        // Frame bauen
        // 
        // DATA aufüllen
        if ($this->Mode == ISCPSplitter::LAN)
        {
            $eISCPlen = chr(0x00) . chr(0x00) . chr(floor(strlen($Data) / 256)) . chr(strlen($Data) % 256);
            $Frame = $eISCPlen . chr($this->eISCPVersion) . chr(0x00).chr(0x00).chr(0x00);
            $Len = strlen($Frame) + 8;
            $eISCPHeaderlen = chr(0x00) . chr(0x00) . chr(floor($Len / 256)) . chr($Len % 256);
            $Frame = "ISCP" . $eISCPHeaderlen . $Frame . $Data;
        }
        elseif ($this->Mode == ISCPSplitter::COM)
        {
            $Frame = $Data;
        }
        else
        {
            throw new Exception("Wrong IO-Parent.",E_USER_WARNING);
        }


        //Semaphore setzen
        if (!$this->lock("ToParent"))
        {
            throw new Exception("Can not send to Parent",E_USER_NOTICE);
        }
        // Daten senden
        try
        {
            parent::SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($Frame))));
        }
        catch (Exception $exc)
        {
            // Senden fehlgeschlagen
            $this->unlock("ToParent");
            throw new Exception($exc);
        }
        $this->unlock("ToParent");
        return true;
    }

################## DUMMYS / WOARKAROUNDS - protected

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

    protected function SetStatus($InstanceStatus)
    {
        if ($InstanceStatus <> IPS_GetInstance($this->InstanceID)['InstanceStatus'])
            parent::SetStatus($InstanceStatus);
    }

    protected function SetSummary($data)
    {
//        IPS_LogMessage(__CLASS__, __FUNCTION__ . "Data:" . $data); //                   
    }

################## SEMAPHOREN Helper  - private  

    private function lock($ident)
    {
        for ($i = 0; $i < 100; $i++)
        {
            if (IPS_SemaphoreEnter("ISCP_" . (string) $this->InstanceID . (string) $ident, 1))
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
        IPS_SemaphoreLeave("ISCP_" . (string) $this->InstanceID . (string) $ident);
    }

}

?>