<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';  // diverse Klassen
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/UTF8Helper.php') . '}');

/**
 * 
 * @property array $ReplyISCPData Enthält die versendeten Befehle und buffert die Antworten.
 * @property string $Buffer Empfangsbuffer
 * @property int $ParentID Die InstanzeID des IO-Parent
 * @property \OnkyoAVR\ISCP_API_Mode $Mode 
 */
class ISCPSplitter extends IPSModule
{

    use \ISCPSplitter\DebugHelper,
        \ISCPSplitter\BufferHelper,
        \ISCPSplitter\InstanceStatus,
        \ISCPSplitter\Semaphore,
        \ISCPSplitter\UTF8Coder {
        \ISCPSplitter\InstanceStatus::MessageSink as IOMessageSink;
        \ISCPSplitter\InstanceStatus::RegisterParent as IORegisterParent;
        \ISCPSplitter\InstanceStatus::RequestAction as IORequestAction;
    }
    private $eISCPVersion = 1;

    public function Create()
    {
        parent::Create();
        $this->RequireParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');
        $this->RegisterTimer('KeepAlive', 0, 'ISCP_KeepAlive($_IPS[\'TARGET\']);');
        $this->ReplyISCPData = [];
        $this->Buffer = '';
        $this->ParentID = 0;
        $this->Mode = \OnkyoAVR\ISCP_API_Mode::NONE;
    }

    public function ApplyChanges()
    {
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        $this->ReplyISCPData = [];
        $this->Buffer = '';
        $this->ParentID = 0;
        $this->Mode = \OnkyoAVR\ISCP_API_Mode::NONE;

        parent::ApplyChanges();

        $this->UnregisterVariable('BufferIN');
        $this->UnregisterVariable('CommandOut');

        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        $this->RegisterParent();
        if ($this->ParentID > 0) {
            IPS_ApplyChanges($this->ParentID);
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

    protected function RegisterParent()
    {
        $IOId = $this->IORegisterParent();
        if ($IOId > 0) {
            $parentGUID = IPS_GetInstance($IOId)['ModuleInfo']['ModuleID'];
            if ($parentGUID == '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}') {
                $this->SetSummary(IPS_GetProperty($IOId, 'Host'));
                $this->Mode = \OnkyoAVR\ISCP_API_Mode::LAN;
            } elseif ($parentGUID == '{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}') {
                $this->SetSummary(IPS_GetProperty($IOId, 'Port'));
                $this->Mode = \OnkyoAVR\ISCP_API_Mode::COM;
            } else {
                $this->LogMessage('IO-Parent not supported.', KL_ERROR);
                $this->Mode = \OnkyoAVR\ISCP_API_Mode::NONE;
            }
            return;
        }
        $this->SetSummary(('none'));
    }

    /**
     * Wird ausgeführt wenn sich der Status vom Parent ändert.
     */
    protected function IOChangeState($State)
    {
        if ($State == IS_ACTIVE) {
            if ($this->HasActiveParent()) {
                $this->SetStatus(IS_ACTIVE);
                return;
            }
        }
        $this->SetStatus(IS_INACTIVE); // Setzen wir uns auf inactive, weil wir vorher eventuell im Fehlerzustand waren.
    }

    public function RequestAction($Ident, $Value)
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return true;
        }
        return false;
    }

    private function RequestAVRState()
    {
//if fKernelRunlevel <> KR_READY then exit;
// TODO
    }

    private function DecodeData($Frame)
    {
        if ($Frame[0] != '!') {
            echo 'ISCP Frame without !';
            return;
        }
        if ($Frame[1] != '1') {
            echo 'Device Typ ' . $Frame[1] . ' not implemented';
            return;
        }
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->APICommand = substr($Frame, 2, 3);
        $APIData->Data = substr($Frame, 5);
        $APIData->GetSubCommand();
        $this->SendDataToZone($APIData);
    }

//################# PUBLIC
    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     */
    public function RequestState()
    {
        $this->RequestAVRState();
    }

//################# DATAPOINT RECEIVE FROM CHILD

    public function ForwardData($JSONString)
    {
        $Data = json_decode($JSONString);
        $this->SendDebug('Forward', $Data, 0);
        /* if ($Data->DataID != '{8F47273A-0B69-489E-AF36-F391AE5FBEC0}') {
          return false;
          } */
        $APIData = new \OnkyoAVR\ISCP_API_Data();
        $APIData->GetDataFromJSONObject($Data);
        //$this->SendDebug('Forward', $APIData, 0);
        try {
            $this->ForwardDataFromDevice($APIData);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return false;
        }
        return true;
        // todo, Antwort abwarten und zurückmelden
    }

//################# DATAPOINTS DEVICE

    private function ForwardDataFromDevice(\OnkyoAVR\ISCP_API_Data $APIData)
    {
        if (is_bool($APIData->Data)) {
            $APIData->Data = \OnkyoAVR\ISCP_API_Commands::$BoolValueMapping($APIData->Data);
        } elseif (is_int($APIData->Data)) {
            $APIData->Data = strlen(dechex($APIData->Data)) == 1 ? '0' . dechex($APIData->Data) : dechex($APIData->Data);
        }
        $Frame = '!1' . $APIData->APICommand . $APIData->Data . chr(0x0D) . chr(0x0A);
        $this->SendDebug('Forward Frame', $Frame, 0);
        try {
            $this->SendDataToParent($Frame);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage(), E_USER_NOTICE);
        }
    }

    private function SendDataToZone(\OnkyoAVR\ISCP_API_Data $APIData)
    {
//        IPS_LogMessage('SendDataToZone',print_r($APIData,true));
        $this->SendDebug('SendDataToZone', $APIData, 0);
        $Data = $APIData->ToJSONString('{43E4B48E-2345-4A9A-B506-3E8E7A964757}');
        $this->SendDebug('SendDataToZone', $Data, 0);
        $this->SendDataToChildren($Data);
    }

//################# DATAPOINTS PARENT

    public function ReceiveData($JSONString)
    {
        if ($this->Mode === \OnkyoAVR\ISCP_API_Mode::NONE) {
            $this->LogMessage($this->Translate('Wrong IO-Parent'), KL_ERROR);
            return false;
        }
        $data = json_decode($JSONString);
// Empfangs Lock setzen
        if (!$this->lock('ReceiveLock')) {
            trigger_error('ReceiveBuffer is locked', E_USER_NOTICE);
            return false;
        }
// Datenstream zusammenfügen
        $head = $this->Buffer;
// Stream in einzelne Pakete schneiden
        $stream = $head . utf8_decode($data->Buffer);
        if ($this->Mode == \OnkyoAVR\ISCP_API_Mode::LAN) {
            $minTail = 24;

            $start = strpos($stream, 'ISCP');
            if ($start === false) {
                $this->SendDebug('Error', 'LANFrame without ISCP', 0);
                $stream = '';
            } elseif ($start > 0) {
                $this->SendDebug('Error', 'LANFrame start not with ISCP', 0);
                $stream = substr($stream, $start);
            }
//Paket suchen
            if (strlen($stream) < $minTail) {
                $this->SendDebug('Error', 'LANFrame to short', 0);
                $this->Buffer = $stream;
                $this->unlock('ReceiveLock');
                return;
            }
            $header_len = ord($stream[6]) * 256 + ord($stream[7]);
            $frame_len = ord($stream[10]) * 256 + ord($stream[11]);
            $this->SendDebug('LANFrame info ', $header_len . '+' . $frame_len . ' Bytes.', 0);
            if (strlen($stream) < $header_len + $frame_len) {
                $this->SendDebug('Error', 'LANFrame must have ' . $header_len . '+' . $frame_len . ' Bytes. ' . strlen($stream) . ' Bytes given.', 0);
                $this->Buffer = $stream;
                $this->unlock('ReceiveLock');
                return;
            }
            $header = substr($stream, 0, $header_len);
            $frame = substr($stream, $header_len, $frame_len);
//EOT wegschneiden von rechts, aber nur wenn es einer der letzten drei zeichen ist
            $end = strrpos($frame, chr(0x1A));
            if ($end >= $frame_len - 3) {
                $frame = substr($frame, 0, $end);
            }
//EOT wegschneiden von rechts, aber nur wenn es einer der letzten drei zeichen ist
            $end = strrpos($frame, chr(0x0D));
            if ($end >= $frame_len - 3) {
                $frame = substr($frame, 0, $end);
            }
//EOT wegschneiden von reschts, aber nur wenn es einer der letzten drei zeichen ist
            $end = strrpos($frame, chr(0x0A));
            if ($end >= $frame_len - 3) {
                $frame = substr($frame, 0, $end);
            }
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
            if ($this->eISCPVersion != ord($header[12])) {
                $frame = false;
                $this->SendDebug('Error', 'Wrong eISCP Version', 0);
                $this->LogMessage('Wrong eISCP Version', KL_ERROR);
            }
        } else {
            $minTail = 6;
            $start = strpos($stream, '!');
            if ($start === false) {
                $this->SendDebug('Error', 'eISCP Frame without !', 0);
                $stream = '';
            } elseif ($start > 0) {
                $this->SendDebug('Error', 'eISCP Frame do not start with !', 0);
                $stream = substr($stream, $start);
            }
//Paket suchen
            $end = strpos($stream, chr(0x1A));
            if (($end === false) or ( strlen($stream) < $minTail)) { // Kein EOT oder zu klein
                $this->SendDebug('Error', 'eISCP Frame to short', 0);
                $this->Buffer = $stream;
                $this->unlock('ReceiveLock');
                return;
            }
            $frame = substr($stream, $start, $end - $start);
// Ende wieder in den Buffer werfen
            $tail = ltrim(substr($stream, $end));
        }
        if ($tail === false) {
            $tail = '';
        }
        $this->Buffer = $tail;
        $this->unlock('ReceiveLock');
        if ($frame !== false) {
            $this->DecodeData($frame);
        }
// Ende war länger als 6 / 23 ? Dann nochmal Packet suchen.
        if (strlen($tail) >= $minTail) {
            $this->ReceiveData(json_encode(['Buffer' => '']));
        }
        return true;
    }

    protected function SendDataToParent($Data)
    {
//        IPS_LogMessage('SendDataToSerialPort:'.$this->InstanceID,$Data);
//Parent ok ?
        if (!$this->HasActiveParent()) {
            throw new Exception('Instance has no active Parent.', E_USER_NOTICE);
        }
        $this->SendDebug('SendDataToParent', $Data, 0);
// Frame bauen
//
// DATA aufüllen
        if ($this->Mode == \OnkyoAVR\ISCP_API_Mode::LAN) {
            $eISCPlen = chr(0x00) . chr(0x00) . chr(floor(strlen($Data) / 256)) . chr(strlen($Data) % 256);
            $Frame = $eISCPlen . chr($this->eISCPVersion) . chr(0x00) . chr(0x00) . chr(0x00);
            $Len = strlen($Frame) + 8;
            $eISCPHeaderlen = chr(0x00) . chr(0x00) . chr(floor($Len / 256)) . chr($Len % 256);
            $Frame = 'ISCP' . $eISCPHeaderlen . $Frame . $Data;
        } elseif ($this->Mode == \OnkyoAVR\ISCP_API_Mode::COM) {
            $Frame = $Data;
        } else {

            //throw new Exception('Wrong IO-Parent.', E_USER_WARNING);
            $this->SendDebug('Error', 'Wrong IO-Parent.', 0);
            $this->LogMessage('Wrong IO-Parent in SendDataToParent.', KL_ERROR);
            return false;
        }
        $this->SendDebug('SendDataToParent', $Frame, 0);
//Semaphore setzen
        if (!$this->lock('ToParent')) {
            throw new Exception('Can not send to Parent', E_USER_NOTICE);
        }
// Daten senden
        try {
            parent::SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => utf8_encode($Frame)]));
        } catch (Exception $exc) {
// Senden fehlgeschlagen
            $this->unlock('ToParent');

            throw new Exception($exc);
        }
        $this->unlock('ToParent');
        return true;
    }

}
