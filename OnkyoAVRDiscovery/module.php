<?php

declare(strict_types=1);
/**
 * @author        Michael Tröger <micha@nall-chan.net>
 * @copyright     2020 Michael Tröger
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 * @version       2.0
 */
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';  // diverse Klassen
eval('namespace OnkyoAVRDiscovery {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');

/**
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 */
class OnkyoAVRDiscovery extends IPSModuleStrict
{
    use \OnkyoAVRDiscovery\DebugHelper;

    /**
     * Interne Funktion des SDK.
     */
    public function Create(): void
    {
        parent::Create();
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
    }

    /**
     * Interne Funktion des SDK.
     */
    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->GetStatus() == IS_CREATING) {
            return json_encode($Form);
        }
        $Devices = $this->DiscoverDevices();
        $IPSDevices = $this->GetIPSInstances();

        $Values = [];

        foreach ($Devices as $IPAddress => $Device) {
            $AddValue = [
                'Host'       => $Device[4],
                'type'       => $Device[0],
                'name'       => 'Onkyo/Pioneer (' . $Device[0] . ')',
                'instanceID' => 0,
            ];
            $InstanceID = array_search($IPAddress, $IPSDevices);
            if ($InstanceID === false) {
                $InstanceID = array_search(strtolower($Device[4]), $IPSDevices);
                if ($InstanceID !== false) {
                    $AddValue['Host'] = $Device[4];
                }
            }
            if ($InstanceID !== false) {
                unset($IPSDevices[$InstanceID]);
                $AddValue['name'] = IPS_GetName($InstanceID);
                $AddValue['instanceID'] = $InstanceID;
            }
            $AddValue['create'] = [
                [
                    'moduleID'      => \OnkyoAVR\GUID::Configurator,
                    'configuration' => new stdClass(),
                ],
                [
                    'moduleID'      => \OnkyoAVR\GUID::Splitter,
                    'configuration' => new stdClass(),
                ],
                [
                    'moduleID'      => \OnkyoAVR\GUID::ClientSocket,
                    'configuration' => [
                        'Host' => $AddValue['Host'],
                        'Port' => (int) $Device[1],
                        'Open' => true,
                    ],
                ],
            ];
            $Values[] = $AddValue;
        }

        foreach ($IPSDevices as $InstanceID => $Host) {
            $Values[] = [
                'Host'       => $Host,
                'type'       => '',
                'name'       => IPS_GetName($InstanceID),
                'instanceID' => $InstanceID,
            ];
        }
        $Form['actions'][0]['values'] = $Values;

        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return json_encode($Form);
    }

    private function GetIPSInstances(): array
    {
        $InstanceIDList = IPS_GetInstanceListByModuleID(\OnkyoAVR\GUID::Configurator);
        $Devices = [];
        foreach ($InstanceIDList as $InstanceID) {
            $Splitter = IPS_GetInstance($InstanceID)['ConnectionID'];
            if ($Splitter > 0) {
                $IO = IPS_GetInstance($Splitter)['ConnectionID'];
                if ($IO > 0) {
                    $parentGUID = IPS_GetInstance($IO)['ModuleInfo']['ModuleID'];
                    if ($parentGUID == \OnkyoAVR\GUID::ClientSocket) {
                        $Devices[$InstanceID] = strtolower(IPS_GetProperty($IO, 'Host'));
                    }
                }
            }
        }

        return $Devices;
    }

    private function DiscoverDevices(): array
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return [];
        }
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 100000]);
        socket_bind($socket, '0.0.0.0', 0);
        $message = hex2bin('49534350000000100000000b01000000217845434e5153544e0d0a');
        if (@socket_sendto($socket, $message, strlen($message), 0, '255.255.255.255', 60128) === false) {
            return [];
        }
        $this->SendDebug('Search', $message, 0);
        usleep(100000);
        $i = 50;
        $buf = '';
        $IPAddress = '';
        $Port = 0;
        $DeviceData = [];
        while ($i) {
            $ret = @socket_recvfrom($socket, $buf, 2048, 0, $IPAddress, $Port);
            if ($ret === false) {
                break;
            }
            if ($ret === 0) {
                $i--;
                continue;
            }
            $start = strpos($buf, '!1ECN');
            if ($start === false) {
                continue;
            }
            $this->SendDebug('Receive', $buf, 0);
            $end = strpos($buf, "\x19", $start);
            $DeviceData[$IPAddress] = explode('/', substr($buf, $start + 5, $end - $start - 5));
            $DeviceData[$IPAddress][] = gethostbyaddr($IPAddress);
        }
        socket_close($socket);
        $this->SendDebug('Discover', $DeviceData, 0);
        return $DeviceData;
    }
}
