<?
require(__DIR__ . "\\pimodule.php");

    // Klassendefinition
    class SymconSchwellwertTimerV2 extends PISymconModule {

        public $Details = true;

        // Eigene Variablen 
        public $Status;
        public $Targets;
        public $sensoren;
        public $Events;

        public $detailsIndex = 3;


        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);

            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {

            parent::Create();
            
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {

            parent::ApplyChanges();

            $this->onSensorChange();

            $this->checkSensorVars();

            $this->createRealOnChangeEvents(array($this->searchObjectByName("Verzögerung") . "|onDelayVarChange", $this->searchObjectByName("Nachlauf") . "|onTrailingVarChange"), $this->searchObjectByName("Events"));

            $this->checkBaseScript();

        }

        protected function checkBaseScript () {

            if ($this->ReadPropertyInteger("BaseScript") == null) {

                if (!$this->doesExist($this->ReadPropertyInteger("BaseScript"))) {

                    $this->createBaseScript();  

                }

            }  else { 

                if (!$this->doesExist($this->ReadPropertyInteger("BaseScript"))) {

                    $this->createBaseScript();

                }

            }

        }
 
        protected function createBaseScript () {
            
            $sperre = $this->searchObjectByName("Sperre");

            $baseScript = $this->checkScript("SWT SetValue", "<?\n\necho IPS_GetName(\$_IPS['SELF']) . \" \"" . ";\n\n\$status = PI_GetValueSetTrigger(" . $this->searchObjectByName("Status") . ");\n\n\$sperre = PI_GetValueSetTrigger(" . $sperre . ");\n\nif (\$sperre == true) {\n\n    return;\n\n} \n\nif (\$status == true) {\n\n    echo \"an\";\n\n} else {\n\n    echo \"aus\";\n\n}\n\n?>", false);
            $statusOnChange = $this->easyCreateRealOnChangeFunctionEvent("Status event", $this->searchObjectByName("Status"), $baseScript, $baseScript, false);
            $sperreOnChange = $this->easyCreateRealOnChangeFunctionEvent("Sperre event", $this->searchObjectByName("Sperre"), $baseScript, $baseScript, false);
            IPS_SetProperty($this->InstanceID, "BaseScript", $baseScript);
            $this->setPosition($baseScript, 0);
            $this->hide($baseScript);
            IPS_ApplyChanges($this->InstanceID);

        }

        protected function onDetailsChangeShow () {

            $prnt = IPS_GetParent($this->InstanceID);
            $name = IPS_GetName($this->InstanceID);

            $this->linkFolderMobile($this->Sensoren, $name . " Sensoren", $prnt);
            $this->linkFolderMobile($this->Targets, $name . " Geräte", $prnt);

        }

        protected function onDetailsChangeHide () {

            $prnt = IPS_GetParent($this->InstanceID);
            $name = IPS_GetName($this->InstanceID);

            $this->deleteObject($this->searchObjectByName($name . " Sensoren", $prnt));
            $this->deleteObject($this->searchObjectByName($name . " Geräte", $prnt));

        }

        protected function setGlobalized () {

            return array("Targets", "Sensoren", "Status", "Events");

        }

        protected function setExcludedHide() {

            return array($this->AutomatikVar, $this->SperreVar, $this->detailsVar, $this->Status);

        }

        protected function setExcludedShow () {

            return array("script", "instance", $this->searchObjectByName("Nachlauf aktiv"));

        }

        public function CheckVariables () {

            $switches = $this->createSwitches(array("Automatik|false|0", "Status|false|1", "Sperre|false|2"));

            $verzögerung = $this->checkInteger("Verzögerung", false, "", 3, $this->secondsToTimestamp(300));
            $nachlauf = $this->checkInteger("Nachlauf", false, "", 4, $this->secondsToTimestamp(1800));

            $nachlaufAktiv = $this->checkBoolean("Nachlauf aktiv", false);

            $this->activateVariableLogging($switches[0]);
            $this->activateVariableLogging($switches[1]);
            $this->activateVariableLogging($switches[2]);

            $this->activateVariableLogging($verzögerung);
            $this->activateVariableLogging($nachlauf);

            $this->addProfile($verzögerung, "~UnixTimestampTime");
            $this->addProfile($nachlauf, "~UnixTimestampTime");

            $this->addSetValue($verzögerung);
            $this->addSetValue($nachlauf);

            $this->setIcon($verzögerung, "Clock");
            $this->setIcon($nachlauf, "Clock");

            $targets = $this->checkFolder("Targets");
            $sensoren = $this->checkFolder("Sensoren");

            $this->Sensoren = $sensoren;

            $this->createOnChangeEvents(array($this->AutomatikVar . "|onAutomaticChange"), $this->Events);

            $this->hide($targets);
            $this->hide($sensoren);
            $this->hide($nachlaufAktiv);

        }

        public function CheckScripts () {

            // Scripts checken -und erstellen
            $this->checkScript("DelayEnd", $this->prefix . "_onDelayEnd");
            $this->checkScript("Trailing", $this->prefix . "_trailing");
            $this->checkScript("onTrailingEnd", $this->prefix . "_onTrailingEnd");

        }

        public function RegisterProperties () {

            $this->RegisterPropertyInteger("Sensor1", null);
            $this->RegisterPropertyInteger("Sensor2", null);
            $this->RegisterPropertyInteger("Sensor3", null);

            $this->RegisterPropertyInteger("BaseScript", null);

            $this->RegisterPropertyInteger("Sensor1Profile", 5);
            $this->RegisterPropertyInteger("Sensor2Profile", 5);
            $this->RegisterPropertyInteger("Sensor3Profile", 5);

            $this->RegisterPropertyInteger("SchwellwertMode", 1);

            $this->RegisterPropertyInteger("Mode", 1);

        }

        protected function checkSensorVars() {

            $sensor1 = $this->ReadPropertyInteger("Sensor1");
            $sensor2 = $this->ReadPropertyInteger("Sensor2");
            $sensor3 = $this->ReadPropertyInteger("Sensor3");

            $sensor1profil = $this->ReadPropertyInteger("Sensor1Profile");
            $sensor2profil = $this->ReadPropertyInteger("Sensor2Profile");
            $sensor3profil = $this->ReadPropertyInteger("Sensor3Profile");

            if ($sensor1 != null) {

                ////echo "sensoren: " . $this->sensoren . "\n";
                ////echo "Sensoren: " . $this->Sensoren . "\n";

                if (!$this->doesExist($this->searchObjectByName("Sensor 1", $this->searchObjectByName("Sensoren")))) {
                    //echo "Case 1";
                    $sensor1link = $this->linkVar($sensor1, "Sensor 1", $this->Sensoren, 0, true);

                    $sensor1schwellwert = $this->checkVar("Schwellwert 1", $this->getVarType($sensor1), "", "", 999);

                    $this->giveTresholdProfile($sensor1schwellwert, $sensor1profil, $sensor1);

                    $this->createOnChangeEvents(array($sensor1schwellwert . "|onTresholdChange", $sensor1 . "|onSensorChange"), $this->Events);

                    $sensorName = IPS_GetName($sensor1);
                    IPS_SetName($sensor1link, $sensorName);
 
                } else { 

                    if ($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren)) != $sensor1) {
                        //echo "Case 2";
                        $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren))), $this->Events));
                        $this->deleteObject($this->searchObjectByName("Sensor 1", $this->Sensoren));
                        $this->deleteObject($this->searchObjectByName("Schwellwert 1"));
                        $this->deleteObject($this->searchObjectByName("onChange Schwellwert 1", $this->Events));

                        $sensor1link = $this->linkVar($sensor1, "Sensor 1", $this->Sensoren, 0, true);

                        $sensor1schwellwert = $this->checkVar("Schwellwert 1", $this->getVarType($sensor1), "", "", 999); 

                        $this->addSetValue($sensor1schwellwert);

                        $this->giveTresholdProfile($sensor1schwellwert, $sensor1profil, $sensor1);

                        $this->createOnChangeEvents(array($sensor1schwellwert . "|onTresholdChange", $sensor1 . "|onSensorChange"), $this->Events);

                        $sensorName = IPS_GetName($sensor1);
                        IPS_SetName($sensor1link, $sensorName);
 
                    } else {

                        $this->giveTresholdProfile($this->searchObjectByName("Schwellwert 1"), $sensor1profil, $sensor1);   

                    } 

                }
                

            } else {


                if ($this->doesExist($this->searchObjectByName("Sensor 1", $this->searchObjectByName("Sensoren")))) {
                    
                    $this->deleteObject($this->searchObjectByName("onChange Schwellwert 1", $this->Events));
                    $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren))), $this->Events));
                    $this->deleteObject($this->searchObjectByName("Sensor 1", $this->searchObjectByName("Sensoren")));
                    $this->deleteObject($this->searchObjectByName("Schwellwert 1")); 

                }

            }

            if ($sensor2 != null) {

                if (!$this->doesExist($this->searchObjectByName("Sensor 2", $this->Sensoren))) {

                    $sensor2link = $this->linkVar($sensor2, "Sensor 2", $this->Sensoren, 0, true);

                    $sensor2schwellwert = $this->checkVar("Schwellwert 2", $this->getVarType($sensor2), "", "", 999);

                    $this->addSetValue($sensor2schwellwert);

                    $this->giveTresholdProfile($sensor2schwellwert, $sensor2profil, $sensor2);

                    $this->createOnChangeEvents(array($sensor2schwellwert . "|onTresholdChange", $sensor2 . "|onSensorChange"), $this->Events);

                    $sensorName = IPS_GetName($sensor2);
                    IPS_SetName($sensor2link, $sensorName);

                } else {

                    if ($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren)) != $sensor2) {
                        
                        $this->deleteObject($this->searchObjectByName("Sensor 2", $this->Sensoren));
                        $this->deleteObject($this->searchObjectByName("Schwellwert 2"));
                        $this->deleteObject($this->searchObjectByName("onChange Schwellwert 2", $this->Events));
                        $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren))), $this->Events));

                        $sensor2link = $this->linkVar($sensor2, "Sensor 2", $this->Sensoren, 0, true);

                        $sensor2schwellwert = $this->checkVar("Schwellwert 2", $this->getVarType($sensor2), "", "", 999);

                        $this->giveTresholdProfile($sensor2schwellwert, $sensor2profil, $sensor2);

                        $this->createOnChangeEvents(array($sensor2schwellwert . "|onTresholdChange", $sensor2 . "|onSensorChange"), $this->Events);

                        $sensorName = IPS_GetName($sensor2);
                        IPS_SetName($sensor2link, $sensorName);

                    } else {

                        $this->giveTresholdProfile($this->searchObjectByName("Schwellwert 2"), $sensor2profil, $sensor2);

                    }

                }
                

            } else {

                if ($this->doesExist($this->searchObjectByName("Sensor 2", $this->searchObjectByName("Sensoren")))) {
                    $this->deleteObject($this->searchObjectByName("onChange Schwellwert 2", $this->Events));
                    $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren))), $this->Events));
                    $this->deleteObject($this->searchObjectByName("Sensor 2", $this->searchObjectByName("Sensoren")));
                    $this->deleteObject($this->searchObjectByName("Schwellwert 2"));
                }

            } 

            if ($sensor3 != null) {

                if (!$this->doesExist($this->searchObjectByName("Sensor 3", $this->Sensoren))) {

                    $sensor3link = $this->linkVar($sensor3, "Sensor 3", $this->Sensoren, 0, true);

                    $sensor3schwellwert = $this->checkVar("Schwellwert 3", $this->getVarType($sensor3), "", "", 999);

                    $this->giveTresholdProfile($sensor3schwellwert, $sensor3profil, $sensor3);

                    $this->createOnChangeEvents(array($sensor3schwellwert . "|onTresholdChange", $sensor3 . "|onSensorChange"), $this->Events);

                    $sensorName = IPS_GetName($sensor3);
                    IPS_SetName($sensor3link, $sensorName);

                } else {

                    if ($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren)) != $sensor3) {
                        
                        $this->deleteObject($this->searchObjectByName("Sensor 3", $this->Sensoren));
                        $this->deleteObject($this->searchObjectByName("Schwellwert 3"));
                        $this->deleteObject($this->searchObjectByName("onChange Schwellwert 3", $this->Events));
                        $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren))), $this->Events));

                        $sensor3link = $this->linkVar($sensor3, "Sensor 3", $this->Sensoren, 0, true);

                        $sensor3schwellwert = $this->checkVar("Schwellwert 3", $this->getVarType($sensor3), "", "", 999); 

                        $this->addSetValue($sensor3schwellwert);

                        $this->giveTresholdProfile($sensor3schwellwert, $sensor3profil, $sensor3);

                        $this->createOnChangeEvents(array($sensor3schwellwert . "|onTresholdChange", $sensor3 . "|onSensorChange"), $this->Events);

                        $sensorName = IPS_GetName($sensor3);
                        IPS_SetName($sensor3link, $sensorName);

                    } else {

                        $this->giveTresholdProfile($this->searchObjectByName("Schwellwert 3"), $sensor3profil, $sensor3);

                    }

                }
                

            } else { 

                if ($this->doesExist($this->searchObjectByName("Sensor 3", $this->searchObjectByName("Sensoren")))) {
                    $this->deleteObject($this->searchObjectByName("onChange Schwellwert 3", $this->Events));
                    $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren))), $this->Events));
                    $this->deleteObject($this->searchObjectByName("Sensor 3", $this->searchObjectByName("Sensoren")));
                    $this->deleteObject($this->searchObjectByName("Schwellwert 3"));
                }

            }

        }

        protected function setNeededProfiles () {
            return array("Lux", "Temperature_F", "Temperature_C", "Wattage", "Windspeed", "Wolkendecke");
        }

        protected function giveTresholdProfile ($tresholdVar, $tresholdVal, $source) {


            // Grad_F
            if ($tresholdVal == 2) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Temperature_F_float") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_F_float");
                    $this->setIcon($tresholdVar, "Temperature");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Temperature_F_int") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_F_int");
                    $this->setIcon($tresholdVar, "Temperature");

                }

            }

            // Grad_C
            if ($tresholdVal == 1) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Temperature_C_float") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_C_float");
                    $this->setIcon($tresholdVar, "Temperature"); 

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Temperature_C_int") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_C_int");
                    $this->setIcon($tresholdVar, "Temperature");

                }

            } 

            // Lux
            if ($tresholdVal == 3) {


                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Lux_float") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Lux_float");
                    $this->setIcon($tresholdVar, "Sun");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Lux_int") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Lux_int");
                    $this->setIcon($tresholdVar, "Sun");

                } 

            }

            // Wattage
            if ($tresholdVal == 4) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Wattage_float") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Wattage_float");
                    $this->setIcon($tresholdVar, "Electricity");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Wattage_int") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Wattage_int");
                    $this->setIcon($tresholdVar, "Electricity");

                }

            }

            // Windgeschwindigkeit
            if ($tresholdVal == 5) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Windgeschwindigkeit") {

                    $this->addProfile($tresholdVar, $this->prefix . ".Windgeschwindigkeit");
                    $this->setIcon($tresholdVar, "WindSpeed");

                }

                // if ($this->getVarType($tresholdVar) == $this->varTypeByName("int") && $this->getVarProfile($tresholdVar) != $this->prefix . ".Windspeed") {

                //     $this->addProfile($tresholdVar, $this->prefix . ".Wattage_int");
                //     $this->setIcon($tresholdVar, "Electricity");

                // }

            }

            if ($tresholdVal == 7) {

                $profile = $this->getVarProfile($source);
                $this->addProfile($tresholdVar, $profile);
                $this->addSetValue($tresholdVar);

            }

        }

        ###################################################################################################################################

        public function onAutomaticChange () {

            $automatik = $this->AutomatikVar;
            $automatikVal = GetValue($automatik);

            if (!$automatikVal) {

                $statusVal = GetValue($this->searchObjectByName("Status"));

                $this->deleteObject($this->searchObjectByName("Verzögerung Timer"));
                $this->deleteObject($this->searchObjectByName("Nachlauf Timer"));

                SetValue($this->searchObjectByName("Nachlauf aktiv"), false);

                if ($statusVal != false) {

                    SetValue($this->searchObjectByName("Status"), false);

                }

                IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), 0);
                IPS_SetScriptTimer($this->searchObjectByName("Trailing"), 0);
                IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), 0);



            } else {

                $this->onSensorChange();

            }

        }

        public function onDetailsChange () {

            $senderVar = $_IPS['VARIABLE'];
            $senderVal = GetValue($senderVar);
            $excludeHide = $this->setExcludedHide();
            $excludeShow = $this->setExcludedShow();
    
            $specialShow = $this->setSpecialShow();
            $specialHide = $this->setSpecialHide();
    
            // Wenn ausblenden
            if ($senderVal == false) {
    
                $this->hideAll($excludeHide);
    
                if (count($specialHide)) {
    
                    foreach ($specialHide as $id) {
                        $this->hide($id);
                    }
    
                }
    
                $this->onDetailsChangeHide();
    
            } else {
    
                $this->showAll($excludeShow);
    
                foreach ($specialShow as $id) {
                    $this->show($id);
                }
    
                $this->onDetailsChangeShow();
    
            }
    
        }

        protected function getVariableType ($id) {

            if ($this->doesExist($id)) {

                if ($this->isVariable($id)) {

                    $obj = IPS_GetVariable($id);
                    $type = $obj['VariableType'];
                    return $type;

                }

            }

        }

        protected function castNull ($wert) {

            if ($wert == null) {

                return 0;

            }

        }

        public function onTresholdChange () {

            $this->onSensorChange();

        }

        public function onSensorChange () {

            $automatik = GetValue($this->AutomatikVar);
            $statusVar = $this->Status;
            $statusVal = GetValue($statusVar);

            $sensor1 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren)));
            $sensor2 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren)));
            $sensor3 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren)));

            $sensor1schwellwert = $this->getValueIfPossible($this->searchObjectByName("Schwellwert 1"));
            $sensor2schwellwert = $this->getValueIfPossible($this->searchObjectByName("Schwellwert 2"));
            $sensor3schwellwert = $this->getValueIfPossible($this->searchObjectByName("Schwellwert 3"));

            $trailingActive = $this->getValueIfPossible($this->searchObjectByName("Nachlauf aktiv"));

            $currentStatus = GetValue($this->searchObjectByName("Status"));

            $sensor1type = $this->getVariableType($this->searchObjectByName("Schwellwert 1"));
            $sensor2type = $this->getVariableType($this->searchObjectByName("Schwellwert 2"));
            $sensor3type = $this->getVariableType($this->searchObjectByName("Schwellwert 3"));


            if ($automatik) {

                $newStatus = false;

                if ($this->ReadPropertyInteger("SchwellwertMode") == 1) {

                    $sens1valid = false;
                    $sens2valid = false;
                    $sens3valid = false;

                    if ($sensor1schwellwert != null) {

                        //echo "Sensor1Typ: " . $sensor1type;

                        if (gettype($sensor1schwellwert) == "boolean") {

                            $sensor1schwellwert = (int) $sensor1schwellwert;
                            $sensor1 = (int) $sensor1;

                            if ($sensor1schwellwert == $sensor1) {
                                $sens1valid = true;
                            }

                        } else {

                            if ($sensor1schwellwert <= $sensor1) {
                                $sens1valid = true;
                            }

                        }

                    } 

                    if ($sensor2schwellwert != null) {

                        //echo "Sensor2Typ: " . $sensor2type;

                        if (gettype($sensor2schwellwert) == "boolean") {

                            $sensor2schwellwert = (int) $sensor2schwellwert;
                            $sensor2 = (int) $sensor2;

                            if ($sensor2schwellwert == $sensor2) {
                                $sens2valid = true;
                            }

                        } else {

                            if ($sensor2schwellwert <= $sensor2) {
                                $sens2valid = true;
                            }

                        }

                    } 

                    if ($sensor3schwellwert != null) {

                        if ($sensor3schwellwert <= $sensor3) {
                            $sens3valid = true;
                        }

                    } 

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1schwellwert != null && $sens1valid == true) {
                        $sens1valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1schwellwert != null && $sens1valid == false) {
                        $sens1valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2schwellwert != null && $sens2valid == true) {
                        $sens2valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2schwellwert != null && $sens2valid == false) {
                        $sens2valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3schwellwert != null && $sens3valid == true) {
                        $sens3valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3schwellwert != null && $sens3valid == false) {
                        $sens3valid = true;
                    }

                    if ($sensor1 == null && $sensor1schwellwert == null) {
                        $sens1valid = true;
                    }

                    if ($sensor2 == null && $sensor2schwellwert == null) {
                        $sens2valid = true;
                    }

                    if ($sensor3 == null && $sensor3schwellwert == null) {
                        $sens3valid = true;
                    }

                    if ($sens1valid && $sens2valid && $sens3valid) {

                        $newStatus = true;
    
                    }

                } else if ($this->ReadPropertyInteger("SchwellwertMode") == 2){

                    $sens1valid = false;
                    $sens2valid = false;
                    $sens3valid = false;

                    if ($sensor1schwellwert != null && $sensor1 != null) {

                        if (gettype($sensor1schwellwert) == "boolean" && $sensor1schwellwert == $sensor1) {
                            $sens1valid = true;
                        }

                        if (gettype($sensor1schwellwert) != "boolean" && $sensor1schwellwert <= $sensor1) {
                            $sens1valid = true;
                        }

                    } 

                    if ($sensor2schwellwert != null && $sensor2 != null) {

                        if (gettype($sensor2schwellwert) == "boolean" && $sensor2schwellwert == $sensor2) {
                            $sens2valid = true;
                        }

                        if (gettype($sensor2schwellwert) != "boolean" && $sensor2schwellwert <= $sensor2) {
                            $sens2valid = true;
                        }

                    } 

                    if ($sensor3schwellwert != null && $sensor3 != null) {

                        if (gettype($sensor3schwellwert) == "boolean" && $sensor3schwellwert == $sensor3) {
                            $sens3valid = true;
                        }

                        if (gettype($sensor3schwellwert) != "boolean" && $sensor3schwellwert <= $sensor3) {
                            $sens3valid = true;
                        }

                    } 

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1 != null && $sensor1schwellwert != null && $sens1valid == true) {
                        $sens1valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1 != null && $sensor1schwellwert != null && $sens1valid == false) {
                        $sens1valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2 != null && $sensor2schwellwert != null && $sens2valid == true) {
                        $sens2valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2 != null && $sensor2schwellwert != null && $sens2valid == false) {
                        $sens2valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3 != null && $sensor3schwellwert != null && $sens3valid == true) {
                        $sens3valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3 != null && $sensor3schwellwert != null && $sens3valid == false) {
                        $sens3valid = true;
                    }


                    if ($sens1valid || $sens2valid || $sens3valid) {

                        $newStatus = true;
    
                    }

                }

                    if ($trailingActive && $newStatus) {

                        $nachlauf = GetValue($this->searchObjectByName("Nachlauf"));
                        IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), $this->timestampToSeconds($nachlauf));

                        return;
                    }                    

                    if ($newStatus) {

                        if (!$this->doesExist($this->searchObjectByName("Verzögerung Timer"))) {

                            $verzögerung = GetValue($this->searchObjectByName("Verzögerung"));
    
                            IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), $this->timestampToSeconds($verzögerung));
    
                            $this->setIcon($this->getFirstChildFrom($this->searchObjectByName("DelayEnd")), "Clock");
    
                            $this->linkVar($this->getFirstChildFrom($this->searchObjectByName("DelayEnd")), "Verzögerung Timer", null, "last", true);

                        }
    
                    } else {
    
                        $this->deleteObject($this->searchObjectByName("Verzögerung Timer"));
    
                        IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), 0);
    
                    }

            }

        }

        public function onDelayEnd () {

            $mode = $this->ReadPropertyInteger("Mode");

            $nachlauf = GetValue($this->searchObjectByName("Nachlauf"));

            SetValue($this->searchObjectByName("Nachlauf aktiv"), true);

            if ($this->timestampToSeconds($nachlauf) <= 15) {

                IPS_SetScriptTimer($this->searchObjectByName("Trailing"), $this->timestampToSeconds($nachlauf) - 1);

            } else {

                IPS_SetScriptTimer($this->searchObjectByName("Trailing"), 15);

            }

            $this->deleteObject($this->searchObjectByName("Verzögerung Timer"));

            $statusVal = GetValue($this->searchObjectbyName("Status"));

            // if ($mode == 1) {

            //     if ($statusVal != true) {

            //         SetValue($this->Status, true);
    
            //     }

            // } else if ($mode == 2) {

            //     if ($statusVal != false) {

            //         SetValue($this->Status, false);
    
            //     }

            // }

            if ($statusVal != true) {

                SetValue($this->Status, true);
        
            }


            IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), 0);

            IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), $this->timestampToSeconds($nachlauf));

            $this->linkVar($this->getFirstChildFrom($this->searchObjectByName("onTrailingEnd")), "Nachlauf Timer", null, "last", true);

            $this->setIcon($this->getFirstChildFrom($this->searchObjectByName("onTrailingEnd")), "Clock");

        }

        public function trailing () {

            $automatik = GetValue($this->AutomatikVar);
            $statusVar = $this->Status;
            $statusVal = GetValue($statusVar);

            $sensor1 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren)));
            $sensor2 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren)));
            $sensor3 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren)));

            $sensor1schwellwert = $this->getValueIfPossible($this->searchObjectByName("Schwellwert 1"));
            $sensor2schwellwert = $this->getValueIfPossible($this->searchObjectByName("Schwellwert 2"));
            $sensor3schwellwert = $this->getValueIfPossible($this->searchObjectByName("Schwellwert 3"));


            $trailingActive = $this->getValueIfPossible($this->searchObjectByName("Nachlauf aktiv"));

            $currentStatus = GetValue($this->searchObjectByName("Status"));

            if ($automatik) {

                $newStatus = false;

                if ($this->ReadPropertyInteger("SchwellwertMode") == 1) {

                    $sens1valid = false;
                    $sens2valid = false;
                    $sens3valid = false;

                    if ($sensor1schwellwert != null) {

                        //echo "Sensor1Typ: " . $sensor1type;
                        if ($sensor1schwellwert <= $sensor1) {
                            $sens1valid = true;
                        }

                    } 

                    if ($sensor2schwellwert != null) {

                        //echo "Sensor2Typ: " . $sensor2type;

                        if ($sensor2schwellwert <= $sensor2) {
                            $sens2valid = true;
                        }

                    } 

                    if ($sensor3schwellwert != null) {

                        if ($sensor3schwellwert <= $sensor3) {
                            $sens3valid = true;
                        }

                    } 

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1schwellwert != null && $sens1valid == true) {
                        $sens1valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1schwellwert != null && $sens1valid == false) {
                        $sens1valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2schwellwert != null && $sens2valid == true) {
                        $sens2valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2schwellwert != null && $sens2valid == false) {
                        $sens2valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3schwellwert != null && $sens3valid == true) {
                        $sens3valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3schwellwert != null && $sens3valid == false) {
                        $sens3valid = true;
                    }

                    if ($sensor1 == null && $sensor1schwellwert == null) {
                        $sens1valid = true;
                    }

                    if ($sensor2 == null && $sensor2schwellwert == null) {
                        $sens2valid = true;
                    }

                    if ($sensor3 == null && $sensor3schwellwert == null) {
                        $sens3valid = true;
                    }

                    if ($sens1valid && $sens2valid && $sens3valid) {

                        $newStatus = true;
    
                    }

                } else if ($this->ReadPropertyInteger("SchwellwertMode") == 2){

                    $sens1valid = false;
                    $sens2valid = false;
                    $sens3valid = false;

                    if ($sensor1schwellwert != null && $sensor1 != null) {

                        if (gettype($sensor1schwellwert) == "boolean" && $sensor1schwellwert == $sensor1) {
                            $sens1valid = true;
                        }

                        if (gettype($sensor1schwellwert) != "boolean" && $sensor1schwellwert <= $sensor1) {
                            $sens1valid = true;
                        }

                    } 

                    if ($sensor2schwellwert != null && $sensor2 != null) {

                        if (gettype($sensor2schwellwert) == "boolean" && $sensor2schwellwert == $sensor2) {
                            $sens2valid = true;
                        }

                        if (gettype($sensor2schwellwert) != "boolean" && $sensor2schwellwert <= $sensor2) {
                            $sens2valid = true;
                        }

                    } 

                    if ($sensor3schwellwert != null && $sensor3 != null) {

                        if (gettype($sensor3schwellwert) == "boolean" && $sensor3schwellwert == $sensor3) {
                            $sens3valid = true;
                        }

                        if (gettype($sensor3schwellwert) != "boolean" && $sensor3schwellwert <= $sensor3) {
                            $sens3valid = true;
                        }

                    } 

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1 != null && $sensor1schwellwert != null && $sens1valid == true) {
                        $sens1valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor1 != null && $sensor1schwellwert != null && $sens1valid == false) {
                        $sens1valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2 != null && $sensor2schwellwert != null && $sens2valid == true) {
                        $sens2valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor2 != null && $sensor2schwellwert != null && $sens2valid == false) {
                        $sens2valid = true;
                    }

                    if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3 != null && $sensor3schwellwert != null && $sens3valid == true) {
                        $sens3valid = false;
                    } else if ($this->ReadPropertyInteger("Mode") == 2 && $sensor3 != null && $sensor3schwellwert != null && $sens3valid == false) {
                        $sens3valid = true;
                    }

                    if ($sens1valid || $sens2valid || $sens3valid) {

                        $newStatus = true;
    
                    }

                }

            $nachlaufactive = GetValue($this->searchObjectByName("Nachlauf aktiv"));

            // Bei unterschreitung tauschen
            // if ($this->ReadPropertyInteger("Mode") == 2) {

            //     if (!$newStatus) {
            //         $newStatus = true;
            //     } else {
            //         $newStatus = false;
            //     }

            // }

            if ($newStatus && $nachlaufactive) {

                $nachlauf = GetValue($this->searchObjectByName("Nachlauf"));
                IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), $this->timestampToSeconds($nachlauf));

            }

            }
        }

        public function onTrailingEnd () {

            $statusVal = GetValue($this->searchObjectByName("Status"));
            $mode = $this->ReadPropertyInteger("Mode");


            SetValue($this->searchObjectByName("Nachlauf aktiv"), false);

            // if ($mode == 1) {

            //     if ($statusVal != false) {

            //         SetValue($this->searchObjectByName("Status"), false);
    
            //     }

            // } else if ($mode == 2) {

            //     if ($statusVal != true) {

            //         SetValue($this->searchObjectByName("Status"), true);
    
            //     }

            // }

            if ($statusVal != false) {

                SetValue($this->searchObjectByName("Status"), false);

            }

            $this->deleteObject($this->searchObjectByName("Nachlauf Timer"));

        }

        #####################################################################################################################################

        public function onDelayVarChange () {

            if ($this->doesExist($this->searchObjectByName("Verzögerung Timer"))) {

                $verzögerung = GetValue($this->searchObjectByName("Verzögerung"));

                IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), $this->timestampToSeconds($verzögerung));

            }

        }

        public function onTrailingVarChange () {

            if ($this->doesExist($this->searchObjectByName("Nachlauf Timer"))) {

                $trailing = GetValue($this->searchObjectByName("Nachlauf"));

                IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), $this->timestampToSeconds($trailing));

            }

            if ($this->doesExist($this->getFirstChildFrom($this->searchObjectByName("Trailing")))) {

                $nachlauf = GetValue($this->searchObjectByName("Nachlauf"));

                if ($this->timestampToSeconds($nachlauf) <= 15) {

                    IPS_SetScriptTimer($this->searchObjectByName("Trailing"), $this->timestampToSeconds($nachlauf) - 1);

                } else {

                    IPS_SetScriptTimer($this->searchObjectByName("Trailing"), 15);

                }

            }

        }

        ##############################################################################################
        
        public function GetSpecialFunctions () {

            $obj = new FunctionsObject($this->InstanceID);
            $obj->TargetsFolder = $this->searchObjectByName("Targets");

            $obj->Schwellwert1 = $this->getValIfPossible($this->searchObjectByName("Schwellwert 1"));
            $obj->Schwellwert2 = $this->getValIfPossible($this->searchObjectByName("Schwellwert 2"));
            $obj->Schwellwert3 = $this->getValIfPossible($this->searchObjectByName("Schwellwert 3"));

            $obj->Sensor1 = $this->getValIfPossible($this->ReadPropertyInteger("Sensor1"));
            $obj->Sensor2 = $this->getValIfPossible($this->ReadPropertyInteger("Sensor2"));
            $obj->Sensor3 = $this->getValIfPossible($this->ReadPropertyInteger("Sensor3"));

            return $obj;

        }


}

class FunctionsObject extends IPSModule{

    // Basis Information
    public $InstanceID;
    public $TargetsFolder;
    public $SetTargets;

    // Schwellwerte 
    public $Schwellwert1 = null;
    public $Schwellwert2 = null;
    public $Schwellwert3 = null;

    // Sensoren
    public $Sensor1 = null;
    public $Sensor2 = null;
    public $Sensor3 = null;

    public function __construct ($ii) {
        $this->InstanceID = $ii;
    }

    public function SetTargets ($wert) {

        if (IPS_HasChildren($this->TargetsFolder)) {

            $children = IPS_GetChildrenIDs($this->TargetsFolder);

            foreach ($children as $child) {

                $obj = IPS_GetObject($child);

                // Wenn Link 
                if ($obj['ObjectType'] == 6) {

                    $obj = IPS_GetLink($obj['ObjectID']);
                    $obj = $obj['TargetID'];
                    
                    $this->SetDevice($obj, $wert);

                } else {

                    $this->SetDevice($obj['ObjectID'], $wert);

                }

            }

        }

    }

    protected function setDevice ($deviceID, $wert) {
        if ($deviceID == null || $deviceID == 0) {
            echo "DeviceID darf nicht null oder 0 sein";
            return;
        }
        if (!IPS_ObjectExists($deviceID)) {
            echo "Device/Variable $deviceID existiert nicht!";
            return;
        }
        $actualState = GetValue($deviceID);
        $dimWert = $wert;
        if ($actualState == $wert) {
            return;
        }
        if (gettype($wert) == "boolean") {
            if ($wert == true) {
                $dimWert = 100;
            } else {
                $dimWert = 0;
            }
        }
        $device = IPS_GetObject($deviceID);
        $deviceParent = IPS_GetParent($deviceID);
        if ($device['ObjectType'] == 2) {
            $device = IPS_GetVariable($deviceID);
            $parent = IPS_GetObject($deviceParent);
            if ($parent['ObjectType'] == 6) {
                $parent = IPS_GetInstance($deviceParent);
                if ($parent['ModuleInfo']['ModuleName'] == "EIB Group") {
                    
                    if ($device['VariableType'] == 0) {
                        EIB_Switch($deviceParent, $wert);
                    } else if ($device['VariableType'] == 1) {
                        EIB_DimValue($deviceParent, $dimWert);
                    }
                } else if ($parent['ModuleInfo']['ModuleName'] == "HomeMatic Device") {
                    if ($device['VariableType'] == 0) {
                        HM_WriteValueBoolean($deviceParent, "STATE", $wert);
                    } 
                } else if ($parent['ModuleInfo']['ModuleName'] == "SymconSzenenV2") {
                    SymconSzenenV2_SetScene($deviceParent, $wert);
                } else {
                    if ($device['VariableType'] == 0) {
                        SetValue($deviceID, $wert);
                    } else if ($device['VariableType'] == 1) {
                        SetValue($deviceID, $dimWert);
                    } else {
                        SetValue($deviceID, $dimWert);
                    }
                }
            } else {
                if ($device['VariableType'] == 0) {
                    SetValue($deviceID, $wert);
                } else if ($device['VariableType'] == 1) {
                    SetValue($deviceID, $dimWert);
                } else {
                    SetValue($deviceID, $dimWert);
                }
            }
        } else {
            echo "Bitte nur Variablen verlinken!";
        }
    }

}

?>