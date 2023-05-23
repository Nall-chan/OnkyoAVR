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
eval('namespace OnkyoRemote {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('namespace OnkyoRemote {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('namespace OnkyoRemote {?>' . file_get_contents(__DIR__ . '/../libs/helper/WebhookHelper.php') . '}');
eval('namespace OnkyoRemote {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('namespace OnkyoRemote {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * @property int $Type
 * @property string $WebHookSecret
 * @method void SetValueString(string $Ident, string $value)
 * @method void RegisterProfileIntegerEx(string $Name, string $Icon, string $Prefix, string $Suffix, array $Associations, int $MaxValue = -1, float $StepSize = 0)
 * @method void UnregisterProfile(string $Name)
 * @method void RegisterHook(string $WebHook)
 * @method void UnregisterHook(string $WebHook)
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
 */
class OnkyoRemote extends IPSModuleStrict
{
    use \OnkyoRemote\DebugHelper;
    use \OnkyoRemote\WebhookHelper;
    use \OnkyoRemote\BufferHelper;
    use \OnkyoRemote\VariableHelper;
    use \OnkyoRemote\VariableProfileHelper;

    protected static $APICommands = [
        \OnkyoAVR\Remotes::OSD => 'OSD',
        \OnkyoAVR\Remotes::CTV => 'CTV',
        \OnkyoAVR\Remotes::CDV => 'CDV',
        \OnkyoAVR\Remotes::CCD => 'CCD',
        \OnkyoAVR\Remotes::CAP => 'CAP',
    ];
    protected static $Actions = [
        \OnkyoAVR\Remotes::OSD => [
            'MENU',
            'UP',
            'DOWN',
            'RIGHT',
            'LEFT',
            'ENTER',
            'EXIT',
            'AUDIO',
            'VIDEO',
            'HOME',
            'QUICK',
            'RETURN',
        ],
        \OnkyoAVR\Remotes::CTV => [
            'POWER',
            'PWRON',
            'PWROFF',
            'CHUP',
            'CHDN',
            'VLUP',
            'VLDN',
            'MUTE',
            'DISP',
            'INPUT',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '0',
            'CLEAR',
            'SETUP',
            'GUIDE',
            'PREV',
            'UP',
            'DOWN',
            'LEFT',
            'RIGHT',
            'ENTER',
            'RETURN',
            'A',
            'B',
            'C',
            'D',
        ],
        \OnkyoAVR\Remotes::CDV => [
            'POWER',
            'PWRON',
            'PWROFF',
            'PLAY',
            'STOP',
            'SKIP.F',
            'SKIP.R',
            'FF',
            'REW',
            'PAUSE',
            'LASTPLAY',
            'SUBTON/OFF',
            'SUBTITLE',
            'SETUP',
            'TOPMENU',
            'MENU',
            'UP',
            'DOWN',
            'LEFT',
            'RIGHT',
            'ENTER',
            'RETURN',
            'DISC.F',
            'DISC.R',
            'AUDIO',
            'RANDOM',
            'OP/CL',
            'ANGLE',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '10',
            '0',
            'SEARCH',
            'DISP',
            'REPEAT',
            'MEMORY',
            'CLEAR',
            'ABR',
            'STEP.F',
            'STEP.R',
            'SLOW.F',
            'SLOW.R',
            'ZOOMTG',
            'ZOOMUP',
            'ZOOMDN',
            'PROGRE',
            'VDOFF',
            'CONMEM',
            'FUNMEM',
            'DISC1',
            'DISC2',
            'DISC3',
            'DISC4',
            'DISC5',
            'DISC6',
            'FOLDUP',
            'FOLDDN',
            'P.MODE',
            'ASCTG',
            'CDPCD',
            'MSPUP',
            'MSPDN',
            'PCT',
            'RSCTG',
            'INIT',
        ],
        \OnkyoAVR\Remotes::CCD => [
            'POWER',
            'TRACK',
            'PLAY',
            'STOP',
            'PAUSE',
            'SKIP.F',
            'SKIP.R',
            'MEMORY',
            'CLEAR',
            'REPEAT',
            'RANDOM',
            'DISP',
            'D.MODE',
            'FF',
            'REW',
            'OP/CL',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '0',
            '10',
            '+10',
            'D.SKIP',
            'DISC.F',
            'DISC.R',
            'DISC1',
            'DISC2',
            'DISC3',
            'DISC4',
            'DISC5',
            'DISC6',
            'STBY',
            'PON',
        ],
        \OnkyoAVR\Remotes::CAP => [
            'MVLUP',
            'MVLDOWN',
            'SLIUP',
            'SLIDOWN',
            'AMTON',
            'AMTOFF',
            'AMTTG',
            'PWRON',
            'PWROFF',
            'PWRTG',
        ],
    ];

    public function Setup(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * Interne Funktion des SDK.
     */
    public function Create(): void
    {
        parent::Create();
        $this->ConnectParent(\OnkyoAVR\GUID::Splitter);
        $this->RegisterPropertyInteger('Type', 0);
        $this->RegisterPropertyBoolean('showSVGRemote', true);
        $this->RegisterPropertyInteger('RemoteId', 1);
        $this->RegisterPropertyBoolean('showNavigationButtons', true);
        $this->RegisterPropertyBoolean('showControlButtons', true);
        $this->Type = 0;
        $this->WebHookSecret = '';
    }

    /**
     * Interne Funktion des SDK.
     */
    public function Destroy(): void
    {
        if (IPS_GetKernelRunlevel() != KR_READY) {
            parent::Destroy();
            return;
        }
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterHook('/hook/OnkyoRemote' . $this->InstanceID);
            $this->UnregisterProfile('Onkyo.Navigation');
            $this->UnregisterProfile('Onkyo.Control');
        }

        parent::Destroy();
    }

    /**
     * Interne Funktion des SDK.
     */
    public function ApplyChanges(): void
    {
        $this->Type = $this->ReadPropertyInteger('Type');
        if ($this->ReadPropertyBoolean('showSVGRemote')) {
            if (IPS_GetKernelRunlevel() == KR_READY) {
                $this->RegisterHook('/hook/OnkyoRemote' . $this->InstanceID);
            }
            $NewSecret = base64_encode(openssl_random_pseudo_bytes(12));
            $this->WebHookSecret = $Secret = base64_encode(sha1($NewSecret . '0' . (string) $this->InstanceID, true));
            $this->RegisterVariableString('Remote', $this->Translate('Remote'), '~HTMLBox', 1);
            /** @var string $remote  */
            include 'generateRemote' . ($this->ReadPropertyInteger('RemoteId')) . '.php';
            $this->SetValueString('Remote', $remote);
        } else {
            if (IPS_GetKernelRunlevel() == KR_READY) {
                $this->UnregisterHook('/hook/OnkyoRemote' . $this->InstanceID);
            }
            $this->UnregisterVariable('Remote');
        }

        if ($this->ReadPropertyBoolean('showNavigationButtons')) {
            $this->RegisterProfileIntegerEx('Onkyo.Navigation', '', '', '', [
                [1, '<', '', -1],
                [2, '>', '', -1],
                [3, '^', '', -1],
                [4, 'v', '', -1],
                [5, 'Enter', '', -1],
                [6, 'Exit', '', -1],
                [7, $this->Translate('Menu'), '', -1],
            ]);
            $this->RegisterVariableInteger('navremote', $this->Translate('Navigation'), 'Onkyo.Navigation', 2);
            $this->EnableAction('navremote');
        } else {
            $this->UnregisterVariable('navremote');
        }

        if ($this->ReadPropertyBoolean('showControlButtons')) {
            $this->RegisterProfileIntegerEx('Onkyo.Control', '', '', '', [
                [1, '<<', '', -1],
                [2, 'Play', '', -1],
                [3, 'Pause', '', -1],
                [4, 'Stop', '', -1],
                [5, '>>', '', -1],
            ]);
            $this->RegisterVariableInteger('ctrlremote', $this->Translate('Control'), 'Onkyo.Control', 3);
            $this->EnableAction('ctrlremote');
        } else {
            $this->UnregisterVariable('ctrlremote');
        }

        parent::ApplyChanges();
    }

    //################# ActionHandler

    /**
     * Actionhandler der Statusvariablen. Interne SDK-Funktion.
     *
     * @param string                $Ident Der Ident der Statusvariable.
     * @param bool|float|int|string $Value Der angeforderte neue Wert.
     */
    public function RequestAction(string $Ident, mixed $Value): void
    {
        if (parent::RequestAction($Ident, $Value)) {
            return;
        }
        switch ($Ident) {
            case 'navremote':
                switch ($Value) {
                    case 1:
                        $ret = $this->Left();
                        break;
                    case 2:
                        $ret = $this->Right();
                        break;
                    case 3:
                        $ret = $this->Up();
                        break;
                    case 4:
                        $ret = $this->Down();
                        break;
                    case 5:
                        $ret = $this->Enter();
                        break;
                    case 6:
                        $ret = $this->Exit();
                        break;
                    case 6:
                        $ret = $this->Menu();
                        break;
                    default:
                        trigger_error($this->Translate('Invalid Value.'), E_USER_NOTICE);
                        return;
                }
                break;
            case 'ctrlremote':
                switch ($Value) {
                    case 1:
                        $ret = $this->Send('REW');
                        break;
                    case 2:
                        $ret = $this->Send('PLAY');
                        break;
                    case 3:
                        $ret = $this->Send('PAUSE');
                        break;
                    case 4:
                        $ret = $this->Send('STOP');
                        break;
                    case 5:
                        $ret = $this->Send('FF');
                        break;
                    default:
                        trigger_error($this->Translate('Invalid Value.'), E_USER_NOTICE);
                        return;
                }
                break;
            default:
                trigger_error($this->Translate('Invalid Ident.'), E_USER_NOTICE);
                return;
        }
        if (!$ret) {
            trigger_error($this->Translate('Error on execute action.'), E_USER_NOTICE);
        }
    }

    //################# PUBLIC

    /**
     * IPS-Instanz-Funktion 'OAVR_Up'. Tastendruck 'Hoch' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Up(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Down'. Tastendruck 'Runter' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Down(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Left'. Tastendruck 'Links' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Left(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Right'. Tastendruck 'Rechts' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Right(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Menu'. Tastendruck 'Zurück' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Menu(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Enter'. Tastendruck 'ContextMenu' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Enter(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Home'. Tastendruck 'Home' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Home(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Info'. Tastendruck 'Info' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Exit(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_Select'. Tastendruck 'Select' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Quick(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_ShowOSD'. Tastendruck 'ShowOSD' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function Power(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_ShowCodec'. Tastendruck 'ShowCodec' ausführen.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function PowerOn(): bool
    {
        return $this->Send('PWRON');
    }

    /**
     * IPS-Instanz-Funktion 'OAVR_ExecuteAction'. Als Parameter übergebenen Tastendruck ausführen.
     *
     * @param string $Action Auszuführende Aktion.
     *
     * @return bool true bei erfolgreicher Ausführung, sonst false.
     */
    public function PowerOff(): bool
    {
        return $this->Send('PWROFF');
    }

    public function Mute(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    public function Input(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    public function Return(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    public function ChannelDown(): bool
    {
        return $this->Send('CHDN');
    }

    public function ChannelUp(): bool
    {
        return $this->Send('CHUP');
    }

    public function VolumeDown(): bool
    {
        if ($this->Type == \OnkyoAVR\Remotes::CAP) {
            return $this->Send('MVLDOWN');
        }

        return $this->Send('VLDN');
    }

    public function VolumeUp(): bool
    {
        if ($this->Type == \OnkyoAVR\Remotes::CAP) {
            return $this->Send('MVLUP');
        }

        return $this->Send('VLUP');
    }

    public function Play(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    public function Stop(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    public function Pause(): bool
    {
        return $this->Send(strtoupper(__FUNCTION__));
    }

    public function Next(): bool
    {
        return $this->Send('SKIP.F');
    }

    public function Back(): bool
    {
        return $this->Send('SKIP.R');
    }

    public function SendKey(string $Key): bool
    {
        return $this->Send(strtoupper($Key));
    }

    //################# PRIVATE

    /**
     * Verarbeitet Daten aus dem Webhook.
     *
     * @global array $_GET
     */
    protected function ProcessHookdata(): void
    {
        if ((!isset($_GET['button'])) || (!isset($_GET['Secret']))) {
            echo $this->Translate('Bad Request');
            return;
        }
        if ($this->WebHookSecret != rawurldecode($_GET['Secret'])) {
            echo $this->Translate('Access denied');
            return;
        }
        $Command = strtoupper($_GET['button']);
        switch ($this->Type) {
            case \OnkyoAVR\Remotes::CAP:
                switch ($Command) {
                    case 'VLDN':
                        $Command = 'MVLDOWN';
                        break;
                    case 'VLUP':
                        $Command = 'MVLUP';
                        break;
                    case 'POWER':
                        $Command = 'PWRTG';
                        break;
                }
                break;
                /* case \OnkyoAVR\Remotes::OSD:
                  switch ($Command) {
                  case 'RETURN':
                  $Command = 'MVLDOWN';
                  break;
                  }
                  break; */
        }
        if ($this->Send($Command) === true) {
            echo 'OK';
        }
    }

    private function Send(string $Command): mixed
    {
        try {
            if (!in_array($Command, self::$Actions[$this->Type])) {
                throw new Exception($this->Translate('Command not available.'), E_USER_NOTICE);
            }
            $APIData = new \OnkyoAVR\ISCP_API_Data(self::$APICommands[$this->Type], $Command, false);
            $this->SendDebug('ForwardData', $APIData, 0);
            if (!$this->HasActiveParent()) {
                throw new Exception($this->Translate('Instance has no active parent.'), E_USER_NOTICE);
            }
            $ret = $this->SendDataToParent($APIData->ToJSONString(\OnkyoAVR\GUID::SendToSplitter));
            if ($ret === false) {
                $this->SendDebug('Response', 'No answer', 0);
                return false;
            }
            $result = unserialize($ret);
            $this->SendDebug('Response', $result, 0);

            return $result;
        } catch (Exception $exc) {
            $this->SendDebug('Error', $exc->getMessage(), 0);
            trigger_error($exc->getMessage(), E_USER_NOTICE);
            return false;
        }
    }
}
