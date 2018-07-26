<?
require(__DIR__ . "\\pimodule.php");

    // Klassendefinition
    class SymconSchwellwertTimerV2 extends PISymconModule {

        public $Details = false;

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
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
           
            parent::ApplyChanges();

        }

        public function configForm () {


        }

        // public function setExcluded() {

        //     return array($this->AutomatikVar, $this->SperreVar);

        // }

        public function CheckVariables () {

            //$switches = $this->createSwitches(array("Automatik||false", "Sperre||false", "Status||false"));

        }

        public function CheckScripts () {

            // Scripts checken -und erstellen


        }

        public function RegisterProperties () {


        }

        public function onSperreChange () {

            echo "Sperre changed :)";

        }

        public function onAutomatikChange () {

            echo "Automatik changed :)";

        }
}

?>