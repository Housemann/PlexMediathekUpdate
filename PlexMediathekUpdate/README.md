# Plex Mediathek Update
Das Plex Mediathek Update ließt die Plex Mediatheken aus und speichert diese in einer HMLT Box. 
Mit dem Aktualisierungsbutton kann man dann die entsprechenden Mediatheken einlesen.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)

### 1. Funktionsumfang

* Ermöglicht das Auslesen der eingenen Plex Mediatheken
* Dazu kann man pro Mediathek das einlesen seiner Plex Filme starten
* Darstellung und Bedienung via WebFront und mobilen Apps

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5

### 3. Software-Installation

* Über das Module Control folgende URL hinzufügen:
    `https://github.com/Housemann/PlexMediathekUpdate`

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" kann das 'PlexMediathekUpdate'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)
__Konfigurationsseite__:

Name      | Beschreibung
--------- | ---------------------------------
Variable  | HTML Box

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.