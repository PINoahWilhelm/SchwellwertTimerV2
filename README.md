# SymconSchwellwertTimerV2
## Daten
* Dem SymconSchwellwertTimerV2 können bis zu 3 verschiedene **Sensoren** übergeben werden
* Es gibt den **UND** und den **ODER** Modus, im **UND** Modus müssen alle **Sensoren** Ihren **Schwellwert** unter/überschreiten und im **ODER** Modus reicht bereits eine Über/unterschreitung aus
* Es kann ein Wert für die **Aktivierung** und ein Wert für die **Deaktivierung** angegeben werden
* Die **Statusvariable** beschreibt ob der/die Schwellwert/e überschritten ist/sind (AN => Überschritten, AUS => Unterschritten)
* **Aktivierung** => Status an
* **Deaktivierung** => Status aus
* Sobald der/die Schwellwert/e überschritten werden beginnt die **Verzögerung**, sobald der/die Sensor/en den/die Schwellwert/e wieder unterschreitet bricht die **Verzögerung** wieder ab. Ist die Verzögerung abgelaufen geht die **Status** Variable auf **AN** und der **Nachlauf** startet.
* Der Nachlauf verlägert sich solange der Wert überschritten ist von alleine. Sobald der Nachlauf abläuft wird die Status Variable auf AUS geschaltet
# IP-Symcon Versionen
| Branch        | IPS 5.0           | IPS 4.0+  |
| ------------- |:-------------:| -----:|
| Master      | :white_check_mark: | :white_check_mark: |
