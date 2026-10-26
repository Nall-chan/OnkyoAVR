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
eval('namespace OnkyoConfigurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoConfigurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');

/**
 * @property array $Zones
 * @property int $SplitterID Die InstanzID des Splitter (Parent)
 * @property int $IOID Die InstanzID des IO (Parent des Splitters)
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 */
class OnkyoConfigurator extends IPSModuleStrict
{
    use \OnkyoConfigurator\DebugHelper;
    use \OnkyoConfigurator\BufferHelper;

    /**
     * Create
     *
     * @return void
     */
    public function Create(): void
    {
        parent::Create();
        $this->Zones = [];
        $this->SplitterID = 0;
        $this->IOID = 0;
        $this->SetReceiveDataFilter('.*"nothingtoreceive":.*');
    }

    /**
     * ApplyChanges
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        $this->Zones = [];
        $this->SplitterID = 0;
        $this->IOID = 0;
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        parent::ApplyChanges();
        if (IPS_GetKernelRunlevel() != KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            return;
        }
        $this->RegisterSummarySources();
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
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->UnregisterMessage(0, IPS_KERNELSTARTED);
                $this->RegisterSummarySources();
                break;
            case FM_CONNECT:
            case FM_DISCONNECT:
                $this->RegisterSummarySources();
                break;
            case IM_CONNECT:
            case IM_DISCONNECT:
                // Der Splitter hat einen IO bekommen, verloren oder gewechselt.
                if (($SenderID == $this->SplitterID) && ($SenderID > 0)) {
                    $this->RegisterSummarySources();
                }
                break;
            case IM_CHANGESETTINGS:
                if (($SenderID == $this->IOID) && ($SenderID > 0)) {
                    $this->UpdateSummary();
                }
                break;
        }
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
     * GetConfigurationForm
     *
     * @return string
     */
    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($Form);
        }

        if (!$this->HasActiveParent()) {
            $Form['actions'][] = [
                'type'  => 'PopupAlert',
                'popup' => [
                    'items' => [[
                        'type'    => 'Label',
                        'caption' => 'Instance has no active parent.',
                    ]],
                ],
            ];
            $this->SendDebug('FORM', json_encode($Form), 0);
            $this->SendDebug('FORM', json_last_error_msg(), 0);

            return json_encode($Form);
        }
        $Splitter = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $IO = IPS_GetInstance($Splitter)['ConnectionID'];
        if ($IO == 0) {
            $Form['actions'][] = [
                'type'  => 'PopupAlert',
                'popup' => [
                    'items' => [[
                        'type'    => 'Label',
                        'caption' => 'Splitter has no IO instance.',
                    ]],
                ],
            ];
            $this->SendDebug('FORM', json_encode($Form), 0);
            $this->SendDebug('FORM', json_last_error_msg(), 0);

            return json_encode($Form);
        }
        $ZoneValues = $this->GetZoneConfigFormValues($Splitter);
        if (count($ZoneValues) == 0) {
            $Form['actions'][0]['visible'] = false;
            $Form['actions'][] = [
                'type'  => 'PopupAlert',
                'popup' => [
                    'items' => [[
                        'type'    => 'Label',
                        'caption' => 'This device does not support the NRI command. Please create the required instances manually.',
                    ]],
                ],
            ];
        } else {
            $RemoteValues = $this->GetRemoteConfigFormValues($Splitter);
            $NetworkValues = $this->GetNetworkConfigFormValues($Splitter);
            $Form['actions'][0]['values'] = array_merge($ZoneValues, $RemoteValues, $NetworkValues);
        }

        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return json_encode($Form);
    }

    /**
     * RegisterSummarySources
     *
     * Ermittelt Splitter und IO (Parent-Kette), meldet sich für deren Änderungen an und aktualisiert die Zusammenfassung.
     *
     * @return void
     */
    protected function RegisterSummarySources(): void
    {
        $OldSplitterID = $this->SplitterID;
        $OldIOID = $this->IOID;
        $SplitterID = (int) (@IPS_GetInstance($this->InstanceID)['ConnectionID'] ?? 0);
        $IOID = 0;
        if ($SplitterID > 0) {
            $IOID = (int) (@IPS_GetInstance($SplitterID)['ConnectionID'] ?? 0);
        }
        if (($OldSplitterID > 0) && ($OldSplitterID != $SplitterID)) {
            $this->UnregisterMessage($OldSplitterID, IM_CONNECT);
            $this->UnregisterMessage($OldSplitterID, IM_DISCONNECT);
        }
        if (($OldIOID > 0) && ($OldIOID != $IOID)) {
            $this->UnregisterMessage($OldIOID, IM_CHANGESETTINGS);
        }
        if ($SplitterID > 0) {
            $this->RegisterMessage($SplitterID, IM_CONNECT);
            $this->RegisterMessage($SplitterID, IM_DISCONNECT);
        }
        if ($IOID > 0) {
            $this->RegisterMessage($IOID, IM_CHANGESETTINGS);
        }
        $this->SplitterID = $SplitterID;
        $this->IOID = $IOID;
        $this->UpdateSummary();
    }

    /**
     * UpdateSummary
     *
     * Zeigt Host:Port bzw. den seriellen Port des IO als Zusammenfassung an, ohne IO bleibt sie leer.
     * Die Zusammenfassung ist nur eine Anzeige und keine Datenquelle.
     *
     * @return void
     */
    protected function UpdateSummary(): void
    {
        $Summary = '';
        $IOID = $this->IOID;
        if ($IOID > 0) {
            // Lesender Zugriff auf die Properties des IO (Client Socket / Serial Port).
            $GUID = IPS_GetInstance($IOID)['ModuleInfo']['ModuleID'];
            if ($GUID == \OnkyoAVR\GUID::ClientSocket) {
                $Summary = IPS_GetProperty($IOID, 'Host') . ':' . IPS_GetProperty($IOID, 'Port');
            } elseif ($GUID == \OnkyoAVR\GUID::SerialPort) {
                $Summary = IPS_GetProperty($IOID, 'Port');
            }
        }
        $this->SetSummary($Summary);
    }

    /**
     * GetInstanceList
     *
     * @param  string $GUID
     * @param  int $Parent
     * @param  string $ConfigParam
     * @return array
     */
    private function GetInstanceList(string $GUID, int $Parent, string $ConfigParam): array
    {
        $InstanceIDList = [];
        foreach (IPS_GetInstanceListByModuleID($GUID) as $InstanceID) {
            // Fremde Geräte überspringen
            if (IPS_GetInstance($InstanceID)['ConnectionID'] == $Parent) {
                $InstanceIDList[] = $InstanceID;
            }
        }
        if ($ConfigParam != '') {
            $InstanceIDList = array_flip(array_values($InstanceIDList));
            array_walk($InstanceIDList, [$this, 'GetConfigParam'], $ConfigParam);
        }

        return $InstanceIDList;
    }

    /**
     * GetConfigParam
     *
     * @param  mixed $item1
     * @param  int $InstanceID
     * @param  string $ConfigParam
     * @return void
     */
    private function GetConfigParam(&$item1, int $InstanceID, string $ConfigParam): void
    {
        $item1 = IPS_GetProperty($InstanceID, $ConfigParam);
    }

    /**
     * GetZoneConfigFormValues
     *
     * @param  int $Splitter
     * @return array
     */
    private function GetZoneConfigFormValues(int $Splitter): array
    {
        $ZoneValues = [];
        $APIDataZoneList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ZoneList);
        $this->Zones = $FoundZones = $this->Send($APIDataZoneList);
        $this->SendDebug('Found Zones', $FoundZones, 0);
        if (count($FoundZones) == 0) {
            return $ZoneValues;
        }
        $InstanceIDListZones = $this->GetInstanceList(\OnkyoAVR\GUID::Zone, $Splitter, 'Zone');
        $this->SendDebug('IPS Zones', $InstanceIDListZones, 0);
        foreach ($FoundZones as $ZoneID => $Zone) {
            $InstanceIDZone = array_search($ZoneID, $InstanceIDListZones);
            if ($InstanceIDZone !== false) {
                $AddValue = [
                    'instanceID' => $InstanceIDZone,
                    'name'       => IPS_GetName($InstanceIDZone),
                    'type'       => 'Zone',
                    'zone'       => $Zone['Name'],
                    'location'   => stristr(IPS_GetLocation($InstanceIDZone), IPS_GetName($InstanceIDZone), true),
                ];
                unset($InstanceIDListZones[$InstanceIDZone]);
            } else {
                $AddValue = [
                    'instanceID' => 0,
                    'name'       => $Zone['Name'],
                    'type'       => 'Zone',
                    'zone'       => $Zone['Name'],
                    'location'   => '',
                ];
            }
            $AddValue['create'] = [
                'moduleID'      => \OnkyoAVR\GUID::Zone,
                'configuration' => ['Zone' => $ZoneID],
                'location'      => [IPS_GetName($this->InstanceID)]
            ];

            $ZoneValues[] = $AddValue;
        }

        foreach ($InstanceIDListZones as $InstanceIDZone => $Zone) {
            $ZoneValues[] = [
                'instanceID' => $InstanceIDZone,
                'name'       => IPS_GetName($InstanceIDZone),
                'type'       => 'Zone',
                'zone'       => $Zone,
                'location'   => stristr(IPS_GetLocation($InstanceIDZone), IPS_GetName($InstanceIDZone), true),
            ];
        }

        return $ZoneValues;
    }

    /**
     * GetRemoteConfigFormValues
     *
     * @param  int $Splitter
     * @return array
     */
    private function GetRemoteConfigFormValues(int $Splitter): array
    {
        $RemoteValues = [];
        $APIDataRemoteList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ControlList);
        $FoundRemotes = $this->Send($APIDataRemoteList);
        $this->SendDebug('Found Remotes', $FoundRemotes, 0);
        $InstanceIDListRemotes = $this->GetInstanceList(\OnkyoAVR\GUID::Remote, $Splitter, 'Type');
        $this->SendDebug('IPS Remotes', $InstanceIDListRemotes, 0);

        $HasTuner = false;
        foreach ($FoundRemotes as $RemoteName) {
            $RemoteID = \OnkyoAVR\Remotes::ToRemoteID($RemoteName);
            if ($RemoteID < 0) {
                continue;
            }
            if ($RemoteID == \OnkyoAVR\Remotes::TUN) {
                $HasTuner = true;
                continue;
            }
            $InstanceIDRemote = array_search($RemoteID, $InstanceIDListRemotes);
            if ($InstanceIDRemote !== false) {
                $AddValue = [
                    'instanceID' => $InstanceIDRemote,
                    'name'       => IPS_GetName($InstanceIDRemote),
                    'type'       => 'Remote',
                    'zone'       => $RemoteName,
                    'location'   => stristr(IPS_GetLocation($InstanceIDRemote), IPS_GetName($InstanceIDRemote), true),
                ];
                unset($InstanceIDListRemotes[$InstanceIDRemote]);
            } else {
                $AddValue = [
                    'instanceID' => 0,
                    'name'       => $RemoteName,
                    'type'       => 'Remote',
                    'zone'       => $RemoteName,
                    'location'   => '',
                ];
            }
            $AddValue['create'] = [
                'moduleID'      => \OnkyoAVR\GUID::Remote,
                'configuration' => ['Type' => $RemoteID],
                'location'      => [IPS_GetName($this->InstanceID)]
            ];
            $RemoteValues[] = $AddValue;
        }
        foreach ($InstanceIDListRemotes as $InstanceIDRemote => $RemoteID) {
            $RemoteName = \OnkyoAVR\Remotes::ToRemoteName($RemoteID);
            $RemoteValues[] = [
                'instanceID' => $InstanceIDRemote,
                'name'       => IPS_GetName($InstanceIDRemote),
                'type'       => 'Remote',
                'zone'       => $RemoteName,
                'location'   => stristr(IPS_GetLocation($InstanceIDRemote), IPS_GetName($InstanceIDRemote), true),
            ];
        }
        $TunerValues = $this->GetTunerConfigFormValues($Splitter, $HasTuner);

        return array_merge($RemoteValues, $TunerValues);
    }

    /**
     * GetTunerConfigFormValues
     *
     * @param  int $Splitter
     * @param  bool $HasTuner
     * @return array
     */
    private function GetTunerConfigFormValues(int $Splitter, bool $HasTuner): array
    {
        $InstanceIDListTuner = $this->GetInstanceList(\OnkyoAVR\GUID::Tuner, $Splitter, 'Zone');
        $this->SendDebug('IPS Tuner', $InstanceIDListTuner, 0);
        $TunerValues = [];
        foreach ($InstanceIDListTuner as $InstanceIDTuner => $ZoneID) {
            $AddValue = [
                'instanceID' => $InstanceIDTuner,
                'name'       => IPS_GetName($InstanceIDTuner),
                'type'       => 'Tuner',
                'zone'       => '',
                'location'   => stristr(IPS_GetLocation($InstanceIDTuner), IPS_GetName($InstanceIDTuner), true),
            ];
            if ($HasTuner) {
                $AddValue['create'] = [
                    'moduleID'      => \OnkyoAVR\GUID::Tuner,
                    'configuration' => ['Zone' => $ZoneID],
                    'location'      => [IPS_GetName($this->InstanceID)]
                ];
            }
            $TunerValues[] = $AddValue;
        }
        if ($HasTuner && (count($TunerValues) == 0)) {
            foreach ($this->Zones as $ZoneID => $Zone) {
                $Create['Tuner ' . $Zone['Name']] = [
                    'moduleID'      => \OnkyoAVR\GUID::Tuner,
                    'configuration' => ['Zone' => $ZoneID],
                    'location'      => [IPS_GetName($this->InstanceID)]
                ];
            }
            $TunerValues[] = [
                'instanceID' => 0,
                'name'       => 'Tuner',
                'type'       => 'Tuner',
                'zone'       => '',
                'location'   => '',
                'create'     => $Create,
            ];
        }

        return $TunerValues;
    }

    /**
     * GetNetworkConfigFormValues
     *
     * @param  int $Splitter
     * @return array
     */
    private function GetNetworkConfigFormValues(int $Splitter): array
    {
        $APIDataNetServiceList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::NetServiceList);
        $FoundNetServiceList = $this->Send($APIDataNetServiceList);
        $HasNetPlayer = false;
        if (count($FoundNetServiceList) > 0) {
            $HasNetPlayer = true;
        }
        $InstanceIDListNetPlayer = $this->GetInstanceList(\OnkyoAVR\GUID::NetPlayer, $Splitter, 'Zone');
        $this->SendDebug('IPS NetPlayer', $InstanceIDListNetPlayer, 0);
        $NetPlayerValues = [];
        foreach ($InstanceIDListNetPlayer as $InstanceIDNetPlayer => $ZoneID) {
            $AddValue = [
                'instanceID' => $InstanceIDNetPlayer,
                'name'       => IPS_GetName($InstanceIDNetPlayer),
                'type'       => 'Netplayer',
                'zone'       => '',
                'location'   => stristr(IPS_GetLocation($InstanceIDNetPlayer), IPS_GetName($InstanceIDNetPlayer), true),
            ];
            if ($HasNetPlayer) {
                $AddValue['create'] = [
                    'moduleID'      => \OnkyoAVR\GUID::NetPlayer,
                    'configuration' => ['Zone' => $ZoneID],
                    'location'      => [IPS_GetName($this->InstanceID)]
                ];
            }
            $NetPlayerValues[] = $AddValue;
        }
        if ($HasNetPlayer && (count($NetPlayerValues) == 0)) {
            foreach ($this->Zones as $ZoneID => $Zone) {
                $Create['Netplayer ' . $Zone['Name']] = [
                    'moduleID'      => \OnkyoAVR\GUID::NetPlayer,
                    'configuration' => ['Zone' => $ZoneID],
                    'location'      => [IPS_GetName($this->InstanceID)]
                ];
            }
            $NetPlayerValues[] = [
                'instanceID' => 0,
                'name'       => 'Netplayer',
                'type'       => 'Netplayer',
                'zone'       => '',
                'location'   => '',
                'create'     => $Create,
            ];
        }

        return $NetPlayerValues;
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
            if ($ret == false) {
                $this->SendDebug('Response', 'No answer', 0);
                return [];
            }
            $result = unserialize($ret);
            $this->SendDebug('Response', $result, 0);
            return $result ? $result : [];
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            return [];
        }
    }
}
