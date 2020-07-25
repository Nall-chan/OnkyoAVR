<?php

declare(strict_types=1);
/**
 * @author        Michael Tröger <micha@nall-chan.net>
 * @copyright     2020 Michael Tröger
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 * @version       2.0
 */
eval('namespace OnkyoAVRDiscovery {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');

class OnkyoAVRDiscovery extends ipsmodule
{
    use \OnkyoAVRDiscovery\DebugHelper;

    /**
     * Interne Funktion des SDK.
     */
    public function Create()
    {
        parent::Create();
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    /**
     * Interne Funktion des SDK.
     */
    public function GetConfigurationForm()
    {
        $Devices = $this->DiscoverDevices();
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $IPSDevices = $this->GetIPSInstances();

        $Values = [];

        foreach ($Devices as $IPAddress => $Device) {
            $AddValue = [
                'IPAddress'  => $IPAddress,
                'type'       => $Device[0],
                'name'       => 'Onkyo/Pioneer AVR Splitter (' . $Device[0] . ')',
                'instanceID' => 0,
            ];
            $InstanceID = array_search($IPAddress, $IPSDevices);
            if ($InstanceID === false) {
                $InstanceID = array_search(strtolower($Device[4]), $IPSDevices);
                if ($InstanceID !== false) {
                    $AddValue['IPAddress'] = $Device[4];
                }
            }
            if ($InstanceID !== false) {
                unset($IPSDevices[$InstanceID]);
                $AddValue['name'] = IPS_GetName($InstanceID);
                $AddValue['instanceID'] = $InstanceID;
            }
            $AddValue['create'] = [
                [
                    'moduleID'      => '{251DAC2C-5B1F-4B1F-B843-B22D518F553E}',
                    'configuration' => new stdClass(),
                ],
                [
                    'moduleID'      => '{EB1697D1-2A88-4A1A-89D9-807D73EEA7C9}',
                    'configuration' => new stdClass(),
                ],
                [
                    'moduleID'      => '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}',
                    'configuration' => [
                        'Host' => $AddValue['IPAddress'],
                        'Port' => (int) $Device[1],
                        'Open' => true,
                    ],
                ],
            ];
            $Values[] = $AddValue;
        }

        foreach ($IPSDevices as $InstanceID => $IPAddress) {
            $Values[] = [
                'IPAddress'  => $IPAddress,
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
        $InstanceIDList = IPS_GetInstanceListByModuleID('{251DAC2C-5B1F-4B1F-B843-B22D518F553E}');
        $Devices = [];
        foreach ($InstanceIDList as $InstanceID) {
            $Splitter = IPS_GetInstance($InstanceID)['ConnectionID'];
            if ($Splitter > 0) {
                $IO = IPS_GetInstance($Splitter)['ConnectionID'];
                if ($IO > 0) {
                    $parentGUID = IPS_GetInstance($IO)['ModuleInfo']['ModuleID'];
                    if ($parentGUID == '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}') {
                        $Devices[$InstanceID] = strtolower(IPS_GetProperty($IO, 'Host'));
                    }
                }
            }
        }

        return $Devices;
    }

    private function DiscoverDevices(): array
    {
        $this->LogMessage($this->Translate('Background discovery of Onkyo/Pioneer AV-Receiver'), KL_NOTIFY);
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
