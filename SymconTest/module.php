<?
require(__DIR__ . "\\pimodule.php");

    // Klassendefinition
    class SymconSchwellwertTimerV2 extends PISymconModule {

        public $Details = true;

        // Eigene Variablen 
        public $Status;
        public $Targets;
        public $Sensoren;

        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);



            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {

            parent::Create();

 
        }

        protected function setNeededModules () {

            return array("Lux", "Temperature_F", "Temperature_C", "Wattage");

        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
           
            parent::ApplyChanges();

        }

        protected function setGlobalized () {

            // return array("Targets", "Sensoren");

        }

        protected function setExcludedHide() {

            // return array($this->AutomatikVar, $this->SperreVar, $this->detailsVar);

        }

        protected function setExcludedShow () {

            return array("script", "instance");

        }

        public function CheckVariables () {

            // $switches = $this->createSwitches(array("Automatik||false", "Sperre||false", "Status||false"));

            // $targets = $this->checkFolder("Targets");
            // $sensoren = $this->checkFolder("Sensoren");

            // $this->createOnChangeEvents(array($this->searchObjectByName("Automatik") . "|onAutomaticChange"), $this->searchObjectByName("Events"));

            // $this->hide($targets);
            // $this->hide($sensoren);

            // $this->Status = $switches[2];

            // $this->checkSensorVars();

        }

        public function CheckScripts () {

            // Scripts checken -und erstellen


        }

        public function RegisterProperties () {

            $this->RegisterPropertyInteger("Sensor1", null);
            $this->RegisterPropertyInteger("Sensor2", null);
            $this->RegisterPropertyInteger("Sensor3", null);

            $this->RegisterPropertyInteger("Sensor1Profile", 5);
            $this->RegisterPropertyInteger("Sensor2Profile", 5);
            $this->RegisterPropertyInteger("Sensor3Profile", 5);

            $this->RegisterPropertyInteger("Mode", 1);

            $this->RegisterPropertyInteger("SchwellwertMode", 1);

        }

        public function checkSensorVars() {

        }

        ###################################################################################################################################

        protected function onAutomaticChange () {

            $automatik = $this->AutomatikVar;

        }

}

?>