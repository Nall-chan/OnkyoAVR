[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Module Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FOnkyoAVR%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.version&label=Modul%20Version&color=blue)](https://community.symcon.de/t/modul-onkyo-pioneer-avr/53213)
[![Symcon Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FOnkyoAVR%2Frefs%2Fheads%2Fmaster%2Flibrary.json&query=%24.compatibility.version&suffix=%3E&label=Symcon%20Version&color=green)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v81-v90-q1-2026/)  
[![License](https://img.shields.io/badge/License-Custom--NC--SA-green.svg)](#10-lizenz)
[![Check Style](https://github.com/Nall-chan/OnkyoAVR/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/OnkyoAVR/actions) [![Run Tests](https://github.com/Nall-chan/OnkyoAVR/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/OnkyoAVR/actions)  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](#2-spenden) [![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#2-spenden)

# Onkyo & Pioneer Configurator  <!-- omit in toc -->

Vereinfacht das Anlegen von verschiedenen Instanzen für einen AV-Receiver.  

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen](#5-statusvariablen)
- [6. Visualisierung](#6-visualisierung)
- [7. PHP-Befehlsreferenz](#7-php-befehlsreferenz)
- [8. Aktionen](#8-aktionen)
- [9. Anhang](#9-anhang)
  - [1. Changelog](#1-changelog)
  - [2. Spenden](#2-spenden)
- [10. Lizenz](#10-lizenz)

## 1. Funktionsumfang

- Auslesen und darstellen aller vom Gerät bekannten Zonen und Funktionen.  
- Einfaches Anlegen von neuen Instanzen in Symcon.  

## 2. Voraussetzungen

- Symcon ab Version 9.0  
- kompatibler AV-Receiver mit LAN-Anschluss (RS232 Geräte werden nicht unterstützt.)  

## 3. Software-Installation

Dieses Modul ist ein Bestandteil des Symcon-Modul: [Onkyo & Pioneer AVR](../)  

## 4. Einrichten der Instanzen in IP-Symcon

Eine einfache Einrichtung ist über die im Objektbaum unter `Discovery Instanzen` zu findende Instanz [Onkyo bzw. Pioneer AVR Discovery](../OnkyoAVRDiscovery/README.md#4-einrichten-der-instanzen-in-ip-symcon) möglich.  

Bei der manuellen Einrichtung ist das Modul im Dialog `Instanz hinzufügen` unter den Hersteller `Onkyo` zu finden.  
![Instanz hinzufügen](../imgs/instanzen.png)  

Alternativ ist es auch in der Liste aller Konfiguratoren aufgeführt.  
![Instanz hinzufügen](../imgs/instanzen_configurator.png)  

Es wird automatisch eine [ISCP Splitter-Instanz](../OnkyoAVRSplitter/README.md) erzeugt.  
Anschließend muss die IP-Adresse des AVR im sich öffnenden Dialog eingetragen werden.  

Werden in dem sich öffnenden Konfigurationsformular keine Geräte angezeigt, so ist entweder die Verbindung zum Gerät unterbrochen oder dieses Gerät unterstützt das für den Konfigurator benötigte Kommando nicht.  
Über die Schaltfläche `Gateway konfigurieren` und dann `Schnittstelle konfigurieren` kann der Dialog für die IP-Adresse erneut geöffnet werden.  

Ist der Splitter korrekt verbunden, und unterstützt das Gerät die automatische Ermittlung der Fähigkeiten, wird beim Öffnen des Konfigurators folgender Dialog angezeigt.  
Der Inhalt kann je nach Typ unterschiedlich ausfallen.  
![Konfigurator](../imgs/conf_configurator.png)  

Über das selektieren eines Eintrages in der Tabelle und betätigen des dazugehörigen `Erstellen` Button,  
können Instanzen in Symcon angelegt werden.  
Alternativ können auch alle Instanzen über den Button `Alle erstellen` in Symcon angelegt werden.  

## 5. Statusvariablen

Die Konfigurator-Instanz besitzt keine Statusvariablen.  

## 6. Visualisierung

Die Konfigurator-Instanz besitzt keine darstellbaren Elemente.  

## 7. PHP-Befehlsreferenz

Die Konfigurator-Instanz besitzt keine Instanz-Funktionen.  

## 8. Aktionen

Die Konfigurator-Instanz unterstützt keine Aktionen.  

## 9. Anhang

### 1. Changelog

[Changelog der Library](../README.md#2-changelog)

### 2. Spenden

Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

PayPal:  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

Wunschliste:  
[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)  

## 10. Lizenz

IPS-Modul:  
[Custom NC-SA](../LICENSE)  
