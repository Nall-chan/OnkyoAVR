# IPSOnkyoAVR

Implementierung des Integra Serial Communication Protocol für Onkyo AV-Receiver  

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
5. [Einrichten der Instanzen in IPS](#5-einrichten-der--instanzen-in-ips)
6. [PHP-Befehlsreferenz](#6-php-befehlsreferenz) 
7. [Parameter / Modul-Infos](#7-parameter--modul-infos) 
8. [Tips & Tricks](#8-tips--tricks) 
9. [Anhang](#9-anhang)

## 1. Funktionsumfang

 Ermöglicht das steuern und das empfangen von Statusänderungen, von AV-Receivern des Herstellers Onkyo über RS232 oder LAN.
 Direkte (eingeschränkte) Bedienung im WebFront möglich.
 Abbilden des gesamten Funktionsumfangs in PHP-Befehlen.


## 2. Voraussetzungen

 - IPS ab Version 4.x
 - kompatibler AV-Receiver mit LAN oder RS232-Anschluß

 
## 3. Installation

**IPS 4.x:**  
   Über das Modul-Control folgende URL hinzufügen.  
   `git://github.com/Nall-chan/IPSOnkyoAVR.git`  


## 5. Einrichten der  Instanzen in IPS

Unter Instanz hinzufügen .....


## 7. PHP-Befehlsreferenz

 **Onyko Splitter:**  
```php
boolean ISCP_SendCommand(integer $InstanzeID, string $Command, string $Value);
```
 Sendet einen Kommando mit einem bestimmten Wert an das Gerät.

```php
mixed ISCP_GetValue(integer $InstanzeID, string $Command);
```
 Fragt den Wert zu dem Kommando vom Gerät ab.

---

 **Onkyo Device:**  

```php
boolean ISCP_Power(integer $InstanzeID, boolean $Value);
```
Schaltet die Zone ein (true) oder aus (false).

## 8. Parameter / Modul-Infos

GUIDs der Instanzen (z.B. wenn Instanz per PHP angelegt werden soll):  

| Instanz  | GUID                                   |
| :------: | :------------------------------------: |
| Splitter | {EB1697D1-2A88-4A1A-89D9-807D73EEA7C9} |
| Device   | {DEDC12F1-4CF7-4DD1-AE21-B03D7A7FADD7} |

Eigenschaften des Splitter für Get/SetProperty-Befehle:  

| Eigenschaft | Typ     | Standardwert | Funktion             |
| :---------: | :-----: | :----------: | :------------------: |
| Modus       | integer | 1            | 0 = RS232 , 1 = LAN  |

Eigenschaften des Devices für Get/SetProperty-Befehle:  

| Eigenschaft   | Typ     | Standardwert | Funktion                                                 |
| :-----------: | :-----: | :----------: | :------------------------------------------------------: |
| Zone          | integer | 0            | 1 = MAIN , 2 = Zone2, 3 = Zone3, 4 = Zone4               |
| EmulateStatus | boolean | false        | Beim schalten wird die Statusvariable sofort nachgeführt |

## 9. Tips & Tricks

- ...
- ...
- ...

## 10. Anhang

**Changlog:**

 Version 0.4:  
 - Bugfix für IPS 5.0  

 Version 0.3:  
 - Bugfix Datenaustausch aus 0.2  

 Version 0.2:  
 - Bugfix Timer & Datenaustausch. Doku falsch / fehlt noch immer. Umbau auf RC Beta1 folgt.  

 Version 0.1:  
 - Testversion
