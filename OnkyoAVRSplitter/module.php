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
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('namespace ISCPSplitter {?>' . file_get_contents(__DIR__ . '/../libs/helper/UTF8Helper.php') . '}');

/**
 * @property mixed[] $ReplyISCPData Enthält die versendeten Befehle und die empfangenen Antworten.
 * @property string $Multi_Buffer EmpfangsBuffer
 * @property int $ParentID Die InstanzeID des IO-Parent
 * @property int $Mode
 * @property array $NetserviceList
 * @property array $ZoneList
 * @property array $SelectorList
 * @property array $PresetList
 * @property array $ControlList
 * @property array $LMDList
 * @property array $ProfileList
 * @property array $TunerList
 * @property bool $PhaseMatchingBass
 * @method void SetValueString(string $Ident, string $value)
 * @method bool lock(string $ident)
 * @method void unlock(string $ident)
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 */
class ISCPSplitter extends IPSModule
{
    use \ISCPSplitter\DebugHelper,
        \ISCPSplitter\BufferHelper,
        \ISCPSplitter\InstanceStatus,
        \ISCPSplitter\VariableHelper,
        \ISCPSplitter\Semaphore,
        \ISCPSplitter\UTF8Coder {
            \ISCPSplitter\InstanceStatus::MessageSink as IOMessageSink;
            \ISCPSplitter\InstanceStatus::RegisterParent as IORegisterParent;
            \ISCPSplitter\InstanceStatus::RequestAction as IORequestAction;
        }
    private $eISCPVersion = "\x01";

    /**
     * Create
     *
     * @return void
     */
    public function Create()
    {
        parent::Create();
        $this->RequireParent(\OnkyoAVR\GUID::ClientSocket);
        $this->RegisterTimer('KeepAlive', 0, 'OAVR_KeepAlive($_IPS[\'TARGET\']);');
        $this->ReplyISCPData = [];
        $this->Multi_Buffer = '';
        $this->ParentID = 0;
        $this->Mode = \OnkyoAVR\ISCP_API_Mode::LAN;
        $this->EmptyProfileBuffers();
        if (IPS_GetKernelRunlevel() != KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
        }
    }

    /**
     * ApplyChanges
     *
     * @return void
     */
    public function ApplyChanges()
    {
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        $this->ReplyISCPData = [];
        $this->Multi_Buffer = '';
        $this->ParentID = 0;
        $this->Mode = \OnkyoAVR\ISCP_API_Mode::LAN;
        $this->EmptyProfileBuffers();
        parent::ApplyChanges();

        //delete very old vars
        $this->UnregisterVariable('BufferIN');
        $this->UnregisterVariable('CommandOut');

        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        $this->RegisterParent();
        if ($this->ParentID > 0) {
            $this->IOChangeState(IS_ACTIVE);
            //IPS_ApplyChanges($this->ParentID);
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
     * GetConfigurationForParent
     *
     * @return string
     */
    public function GetConfigurationForParent()
    {
        $parentGUID = IPS_GetInstance($this->ParentID)['ModuleInfo']['ModuleID'];
        if ($parentGUID == \OnkyoAVR\GUID::ClientSocket) {
            return json_encode(['Port' => 60128]);
        } elseif ($parentGUID == \OnkyoAVR\GUID::SerialPort) {
            $Config['StopBits'] = '1';
            $Config['BaudRate'] = '9600';
            $Config['Parity'] = 'None';
            $Config['DataBits'] = '8';

            return json_encode($Config);
        }

        return [];
    }

    /**
     * RequestAction
     *
     * @param  int $Ident
     * @param  mixed $Value
     * @return void
     */
    public function RequestAction($Ident, $Value)
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return;
        }
    }

    /**
     * KeepAlive
     *
     * @return void
     */
    public function KeepAlive(): void
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::PWR, \OnkyoAVR\ISCP_API_Commands::Request);
        $ret = $this->Send($APIData);
        if (is_null($ret)) {
            $this->LogMessage('Error in KeepAlive', KL_ERROR);
        }
    }

    /**
     * ForwardData
     *
     * @param  string $JSONString
     * @return string
     */
    public function ForwardData($JSONString)
    {
        $APIData = new \OnkyoAVR\ISCP_API_Data($JSONString);
        if ($APIData->APICommand == \OnkyoAVR\ISCP_API_Commands::GetBuffer) {
            return serialize($this->{$APIData->Data});
        }
        $ret = $this->Send($APIData);
        if (!is_null($ret)) {
            $this->SendDebug('Response', $ret, 0);

            return serialize($ret);
        }
        return '';
    }

    /**
     * ReceiveData
     *
     * @param  string $JSONString
     * @return string
     */
    public function ReceiveData($JSONString)
    {
        $stream = $this->Multi_Buffer;
        $stream .= utf8_decode(json_decode($JSONString)->Buffer);
        $this->SendDebug('Receive', $stream, 0);
        if ($this->Mode == \OnkyoAVR\ISCP_API_Mode::LAN) {
            $minTail = 12;
            $start = strpos($stream, 'ISCP');
            if ($start === false) {
                $this->SendDebug('Error', 'eISCP Frame without ISCP', 0);
                $this->Multi_Buffer = '';
                return '';
            } elseif ($start > 0) {
                $this->SendDebug('Warning', 'eISCP Frame start not with ISCP', 0);
                $stream = substr($stream, $start);
            }
            if (strlen($stream) < $minTail) {
                $this->SendDebug('Waiting', 'eISCP Frame incomplete', 0);
                $this->Multi_Buffer = $stream;
                return '';
            }
            $len = unpack('N*', substr($stream, 4, 8));
            $eISCPHeaderLen = $len[1];
            $PayloadLen = $len[2];
            if (strlen($stream) < $eISCPHeaderLen + $PayloadLen) {
                $this->SendDebug('Waiting', 'eISCP Frame must have ' . $eISCPHeaderLen . '+' . $PayloadLen . ' Bytes. ' . strlen($stream) . ' Bytes given.', 0);
                $this->Multi_Buffer = $stream;
                return '';
            }
            $header = substr($stream, 0, $eISCPHeaderLen);
            $frame = substr($stream, $eISCPHeaderLen, $PayloadLen);
            $tail = substr($stream, $eISCPHeaderLen + $PayloadLen);
            if ($this->eISCPVersion != $header[12]) {
                $frame = false;
                $this->SendDebug('Error', 'eISCP Version not supported: ' . ord($header[12]), 0);
                $this->LogMessage('eISCP Version not supported:' . ord($header[12]), KL_ERROR);
            }
        } else {
            $minTail = 7;
            $start = strpos($stream, '!');
            if ($start === false) {
                $this->SendDebug('Error', 'ISCP Frame without "!"', 0);
                $this->Multi_Buffer = '';
                return '';
            } elseif ($start > 0) {
                $this->SendDebug('Warning', 'ISCP Frame do not start with "!"', 0);
                $stream = substr($stream, $start);
            }
            $len = strpos($stream, "\x1A");
            if (($len === false) || (strlen($stream) < $minTail)) { // Kein EOT oder zu klein
                $this->SendDebug('Waiting', 'ISCP Frame incomplete', 0);
                $this->Multi_Buffer = $stream;
                return '';
            }
            $frame = substr($stream, 0, $len);
            $tail = ltrim(substr($stream, $len));
        }
        $this->Multi_Buffer = $tail;
        if ($frame != '') {
            $APIData = $this->DecodeData(rtrim($frame));
            if ($APIData != null) {
                if (!$this->SendQueueUpdate((string) $APIData->APICommand, $APIData->Data)) {
                    $this->SendDataToZone($APIData);
                }
            }
        }
        if (strlen($tail) >= $minTail) {
            $this->ReceiveData(json_encode(['Buffer' => '']));
        }
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
        $this->UnregisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterParent();
        if ($this->ParentID > 0) {
            $this->IOChangeState(IS_ACTIVE);
            //IPS_ApplyChanges($this->ParentID);
        }
    }

    /**
     * RegisterParent
     *
     * @return void
     */
    protected function RegisterParent()
    {
        $IOId = $this->IORegisterParent();
        if ($IOId > 0) {
            $parentGUID = IPS_GetInstance($IOId)['ModuleInfo']['ModuleID'];
            if ($parentGUID == \OnkyoAVR\GUID::ClientSocket) {
                $this->SetSummary(IPS_GetProperty($IOId, 'Host'));
                $this->Mode = \OnkyoAVR\ISCP_API_Mode::LAN;
            } elseif ($parentGUID == \OnkyoAVR\GUID::SerialPort) {
                $this->SetSummary(IPS_GetProperty($IOId, 'Port'));
                $this->Mode = \OnkyoAVR\ISCP_API_Mode::COM;
            } else {
                $this->Mode = \OnkyoAVR\ISCP_API_Mode::COM;
            }
            return;
        }
        $this->SetSummary(('none'));
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
            if ($this->HasActiveParent()) {
                $this->RefreshCapabilities();
                $this->SetStatus(IS_ACTIVE);
                $this->SetTimerInterval('KeepAlive', 3600000);
                return;
            }
        }
        $this->SetTimerInterval('KeepAlive', 0);
        $this->SetStatus(IS_INACTIVE);
    }

    /**
     * Send
     *
     * @param  \OnkyoAVR\ISCP_API_Data $APIData
     * @return mixed
     */
    protected function Send(\OnkyoAVR\ISCP_API_Data $APIData): mixed
    {
        try {
            if (!$this->HasActiveParent()) {
                throw new Exception($this->Translate('Instance has no active parent.'), E_USER_NOTICE);
            }
            $this->SendDebug('Send APIData', $APIData, 0);
            $Frame = $APIData->ToISCPString($this->Mode);
            $Data = json_encode(['DataID' => \OnkyoAVR\GUID::SendToIO, 'Buffer' => utf8_encode($Frame)]);
            if (!$APIData->needResponse) {
                $this->SendDataToParent($Data);
                return true;
            }
            $this->SendQueuePush((string) $APIData->APICommand);
            $this->SendDataToParent($Data);
            $ReplyData = $this->WaitForResponse((string) $APIData->APICommand);
            if ($ReplyData === null) {
                throw new Exception($this->Translate('Timeout') . ' ' . $APIData->APICommand, E_USER_NOTICE);
            }
            return $ReplyData;
        } catch (Exception $ex) {
            $this->SendDebug('Error', $ex->getMessage(), 0);
            trigger_error($ex->getMessage(), $ex->getCode());
        }
        return null;
    }

    /**
     * EmptyProfileBuffers
     *
     * @return void
     */
    private function EmptyProfileBuffers(): void
    {
        $this->SelectorList = [];
        $this->ControlList = [];
        $this->ProfileList = [];
        $this->LMDList = [];
        $this->PhaseMatchingBass = true;
        $this->NetserviceList = [];
        $this->PresetList = [];
        $this->TunerList = [];
        $this->ZoneList = [];
    }

    /**
     * RefreshCapabilities
     *
     * @return void
     */
    private function RefreshCapabilities(): void
    {
        $ret = $this->Send(new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::NRI, \OnkyoAVR\ISCP_API_Commands::Request));
        if (is_null($ret)) {
            $this->EmptyProfileBuffers();
            return;
        }

        try {
            $Xml = new SimpleXMLElement($ret, LIBXML_NOBLANKS + LIBXML_NONET + LIBXML_NOERROR);
        } catch (Exception $ex) {
            $this->EmptyProfileBuffers();
            return;
        }
        foreach ($Xml->xpath('//model') as $model) {
            $this->RegisterVariableString('Model', $this->Translate('Model'), '', 0);
            $this->SetValueString(('Model'), (string) $model);
            $this->LogMessage('Connected to ' . (string) $model, KL_NOTIFY);
        }

        foreach ($Xml->xpath('//firmwareversion') as $firmwareversion) {
            $this->RegisterVariableString('Firmware', $this->Translate('Firmware'), '', 0);
            $this->SetValueString('Firmware', (string) $firmwareversion);
            $this->LogMessage('Firmware: ' . (string) $firmwareversion, KL_NOTIFY);
        }
        $NetserviceList = [];
        foreach ($Xml->xpath('//netservice') as $Netservice) {
            if ((string) $Netservice['value'] == '0') {
                continue;
            }
            $NetserviceList[hexdec((string) $Netservice['id'])] = trim((string) $Netservice['name']);
        }
        $this->LogMessage('Netservices: ' . count($NetserviceList), KL_NOTIFY);
        $this->NetserviceList = $NetserviceList;

        $SelectorList = [];
        foreach ($Xml->xpath('//selector') as $Selector) {
            if ((string) $Selector['value'] == '0') {
                continue;
            }
            $SelectorList[hexdec((string) $Selector['id'])] = [
                'Name' => trim((string) $Selector['name']),
                'Zone' => (int) $Selector['zone'],
            ];
        }
        $this->LogMessage('Input Selector: ' . count($SelectorList), KL_NOTIFY);
        $this->SelectorList = $SelectorList;

        $ZoneList = [];
        foreach ($Xml->xpath('//zone') as $Zone) {
            if ((string) $Zone['value'] == '0') {
                continue;
            }
            $ZoneList[hexdec((string) $Zone['id'])] = [
                'Name'     => trim((string) $Zone['name']),
                'Volmax'   => (int) $Zone['volmax'],
                'Volstep'  => (int) $Zone['volstep'],
            ];
        }
        $this->LogMessage('Zones: ' . count($ZoneList), KL_NOTIFY);
        $this->ZoneList = $ZoneList;
        $PresetList = [];
        foreach ($Xml->xpath('//presetlist') as $Presetlist) {
            $PresetList['MaxPreset'] = (int) $Presetlist['count'];
        }
        foreach ($Xml->xpath('//preset') as $Preset) {
            $Name = trim((string) $Preset['name']);
            if ($Name != '') {
                $PresetList[(int) hexdec((string) $Preset['id'])] = $Name;
            }
        }
        $this->LogMessage('Presets: ' . count($PresetList), KL_NOTIFY);
        $this->PresetList = $PresetList;

        $TunerList = [];
        foreach ($Xml->xpath('//tuners') as $Tuner) {
            $TunerList[(string) $Tuner['band']] = [
                'Min'    => (int) $Tuner['min'],
                'Max'    => (int) $Tuner['max'],
                'Step'   => (int) $Tuner['step'],
                'Suffix' => '',
                'Digits' => 0,
            ];
        }
        $this->LogMessage('Tuners: ' . count($TunerList), KL_NOTIFY);
        $this->TunerList = $TunerList;

        $ControlList = [];
        $ProfileList = [];
        $LMDList = [];
        foreach ($Xml->xpath('//control') as $Control) {
            if ((string) $Control['id'] == 'Phase Matching Bass') {
                $this->PhaseMatchingBass = ((string) $Control['value'] == '1');
                continue;
            }
            if ((string) $Control['value'] == '0') {
                continue;
            }
            if (strpos((string) $Control['id'], 'Control') > 0) {
                $ControlList[] = (string) $Control['id'];
                continue;
            }
            if (array_key_exists('step', ((array) $Control)['@attributes'])) {
                $ProfileList[(string) $Control['id']] = [
                    0      => (int) $Control['min'],
                    1      => (int) $Control['max'],
                    2      => (float) $Control['step'],
                    'Zone' => (int) $Control['zone'],
                ];
            }
            if (strpos((string) $Control['id'], 'LMD') === 0) {
                $LMDList[(int) $Control['position']] = [
                    'Name' => substr((string) $Control['id'], 4),
                    'Code' => (string) $Control['code'],
                ];
                continue;
            }
        }
        $ControlList[] = 'OSD Control';
        $this->LogMessage('Controls: ' . count($ControlList), KL_NOTIFY);
        $this->ControlList = $ControlList;
        $this->ProfileList = $ProfileList;
        $this->LMDList = $LMDList;
        $this->SendDebug('SelectorList', $this->SelectorList, 0);
        $this->SendDebug('ControlList', $this->ControlList, 0);
        $this->SendDebug('ProfileList', $this->ProfileList, 0);
        $this->SendDebug('LMDList', $this->LMDList, 0);
        $this->SendDebug('NetserviceList', $this->NetserviceList, 0);
        $this->SendDebug('PresetList', $this->PresetList, 0);
        $this->SendDebug('TunerList', $this->TunerList, 0);
        $this->SendDebug('ZoneList', $this->ZoneList, 0);
    }

    /**
     * DecodeData
     *
     * @param  string $Frame
     * @return \OnkyoAVR\ISCP_API_Data
     */
    private function DecodeData(string $Frame)
    {
        if ($Frame[0] != '!') {
            $this->SendDebug('Error', 'ISCP Frame without !', 0);
            return null;
        }
        if ($Frame[1] != '1') {
            $this->SendDebug('Error', 'Device Typ ' . $Frame[1] . ' not implemented', 0);
            return null;
        }
        if ($Frame[strlen($Frame) - 1] != "\x1A") {
            $this->SendDebug('Error', 'ISCP Frame have no EOT ' . bin2hex($Frame[strlen($Frame) - 1]), 0);
            return null;
        }
        return new \OnkyoAVR\ISCP_API_Data($Frame);
    }

    /**
     * SendDataToZone
     *
     * @param  \OnkyoAVR\ISCP_API_Data $APIData
     * @return void
     */
    private function SendDataToZone(\OnkyoAVR\ISCP_API_Data $APIData): void
    {
        $this->SendDebug('SendDataToZone', $APIData, 0);
        $Data = $APIData->ToJSONString(\OnkyoAVR\GUID::SendToDevices);
        $this->SendDataToChildren($Data);
    }

    /**
     * WaitForResponse
     *
     * Wartet auf eine Antwort einer Anfrage an den LMS.
     *
     * @param string $APICommand
     * @return mixed
     */
    private function WaitForResponse(string $APICommand): mixed
    {
        for ($i = 0; $i < 2000; $i++) {
            $Buffer = $this->ReplyISCPData;
            if (!array_key_exists($APICommand, $Buffer)) {
                return null;
            }
            if (!is_null($Buffer[$APICommand])) {
                $this->SendQueueRemove($APICommand);
                return $Buffer[$APICommand];
            }
            usleep(5000);
        }
        $this->SendQueueRemove($APICommand);
        return null;
    }

    /**
     * SendQueuePush
     *
     * Fügt eine Anfrage in die SendQueue ein.
     *
     * @param  string $APICommand
     * @return void
     */
    private function SendQueuePush(string $APICommand): void
    {
        if (!$this->lock('ReplyISCPData')) {
            throw new Exception($this->Translate('ReplyBuffer is locked'), E_USER_NOTICE);
        }
        $Buffer = $this->ReplyISCPData;
        $Buffer[$APICommand] = null;
        $this->ReplyISCPData = $Buffer;
        $this->unlock('ReplyISCPData');
    }

    /**
     * SendQueueUpdate
     *
     * Fügt eine Antwort in die SendQueue ein.
     *
     * @param string $APICommand
     * @param mixed  $Data
     *
     * @return bool True wenn Anfrage zur Antwort gefunden wurde, sonst false.
     */
    private function SendQueueUpdate(string $APICommand, mixed $Data): bool
    {
        if (!$this->lock('ReplyISCPData')) {
            throw new Exception($this->Translate('ReplyISCPData is locked'), E_USER_NOTICE);
        }
        $Buffer = $this->ReplyISCPData;
        if (array_key_exists($APICommand, $Buffer)) {
            $Buffer[$APICommand] = $Data;
            $this->ReplyISCPData = $Buffer;
            $this->unlock('ReplyISCPData');
            return true;
        }
        $this->unlock('ReplyISCPData');
        return false;
    }

    /**
     * SendQueueRemove
     *
     * Löscht einen Eintrag aus der SendQueue.
     *
     * @param string $APICommand Der Index des zu löschenden Eintrags.
     * @return void
     */
    private function SendQueueRemove(string $APICommand): void
    {
        if (!$this->lock('ReplyISCPData')) {
            throw new Exception($this->Translate('ReplyISCPData is locked'), E_USER_NOTICE);
        }
        $Buffer = $this->ReplyISCPData;
        unset($Buffer[$APICommand]);
        $this->ReplyISCPData = $Buffer;
        $this->unlock('ReplyISCPData');
    }
}
