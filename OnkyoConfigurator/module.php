<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';  // diverse Klassen
eval('namespace OnkyoConfigurator {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');

/**
 * @property array $Zones
 */
class OnkyoConfigurator extends IPSModule
{
    use \OnkyoConfigurator\DebugHelper;

    /**
     * Interne Funktion des SDK.
     */
    public function Create()
    {
        parent::Create();
        $this->ConnectParent('{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}');
        $this->SetReceiveDataFilter('.*"nothingtoreceive":.*');
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

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

    private function GetInstanceList(string $GUID, int $Parent, string $ConfigParam)
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

    private function GetConfigParam(&$item1, $InstanceID, $ConfigParam)
    {
        $item1 = IPS_GetProperty($InstanceID, $ConfigParam);
    }

    /**
     * Interne Funktion des SDK.
     */
    private function GetZoneConfigFormValues(int $Splitter)
    {
        $ZoneValues = [];
        $APIDataZoneList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ZoneList);
        $this->Zones = $FoundZones = $this->Send($APIDataZoneList);
        $this->SendDebug('Found Zones', $FoundZones, 0);
        if (count($FoundZones) == 0) {
            return $ZoneValues;
        }
        $InstanceIDListZones = $this->GetInstanceList('{DEDC12F1-4CF7-4DD1-AE21-B03D7A7FADD7}', $Splitter, 'Zone');
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
                'moduleID'      => '{DEDC12F1-4CF7-4DD1-AE21-B03D7A7FADD7}',
                'configuration' => ['Zone' => $ZoneID],
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

    private function GetRemoteConfigFormValues(int $Splitter)
    {
        $RemoteValues = [];
        $APIDataRemoteList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::ControlList);
        $FoundRemotes = $this->Send($APIDataRemoteList);
        $this->SendDebug('Found Remotes', $FoundRemotes, 0);
        $InstanceIDListRemotes = $this->GetInstanceList('{C7EA583D-2BAC-41B7-A85A-AD0DF648E514}', $Splitter, 'Type');
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
                'moduleID'      => '{C7EA583D-2BAC-41B7-A85A-AD0DF648E514}',
                'configuration' => ['Type' => $RemoteID],
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

    private function GetTunerConfigFormValues(int $Splitter, bool $HasTuner)
    {
        $InstanceIDListTuner = $this->GetInstanceList('{47D1BFF5-B6A6-4C3A-A11F-CDA656E3D85F}', $Splitter, 'Zone');
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
                    'moduleID'      => '{47D1BFF5-B6A6-4C3A-A11F-CDA656E3D85F}',
                    'configuration' => ['Zone' => $ZoneID],
                ];
            }
            $TunerValues[] = $AddValue;
        }
        if ($HasTuner && (count($TunerValues) == 0)) {
            foreach ($this->Zones as $ZoneID => $Zone) {
                $Create['Tuner ' . $Zone['Name']] = [
                    'moduleID'      => '{47D1BFF5-B6A6-4C3A-A11F-CDA656E3D85F}',
                    'configuration' => ['Zone' => $ZoneID],
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

    private function GetNetworkConfigFormValues(int $Splitter)
    {
        $APIDataNetServiceList = new \OnkyoAVR\ISCP_API_Data(\OnkyoAVR\ISCP_API_Commands::GetBuffer, \OnkyoAVR\ISCP_API_Commands::NetserviceList);
        $FoundNetServiceList = $this->Send($APIDataNetServiceList);
        $HasNetPlayer = false;
        if (count($FoundNetServiceList) > 0) {
            $HasNetPlayer = true;
        }
        $InstanceIDListNetPlayer = $this->GetInstanceList('{3E71DC11-1A93-46B1-9EA0-F0EC0C1B3476}', $Splitter, 'Zone');
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
                    'moduleID'      => '{3E71DC11-1A93-46B1-9EA0-F0EC0C1B3476}',
                    'configuration' => ['Zone' => $ZoneID],
                ];
            }
            $NetPlayerValues[] = $AddValue;
        }
        if ($HasNetPlayer && (count($NetPlayerValues) == 0)) {
            foreach ($this->Zones as $ZoneID => $Zone) {
                $Create['Netplayer ' . $Zone['Name']] = [
                    'moduleID'      => '{3E71DC11-1A93-46B1-9EA0-F0EC0C1B3476}',
                    'configuration' => ['Zone' => $ZoneID],
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
            $this->SendDebug('Response', $result, 0);

            return $result;
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);

            return null;
        }
    }
}
