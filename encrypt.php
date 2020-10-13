<?php

    /**
     *
     *  PHP Site Encrypter
     *  @see https://github.com/andreazorzi/site-encrypter
     *  @author Andrea Zorzi (andreazorzi) <info@zorziandrea.com>
     *  @version 1.0.0
     *
     */

    class Encrypter{
        private $key;
        private $folder;
        private $cipher;
        private $realpath;
        private $onlyfiles;
        private $excludefolders = array();
        
        private $ext = ".crypt";
        private $test = true;
        private $echo = true;
        
        /**
         *
         * Object constructor
         *
         * @param   String      $key        Encryption secret key
         * @param   Array       $settings   Encryption settings
         * @return  Encrypter               Encrypter Object
         *
         */
        public function __construct($key, $settings = array()){
            $this->key = $key;
            
            if($key == ""){
                throw new Exception("Key can't be an empty string");
            }
            
            $this->folder = isset($settings["folder"]) && is_dir($settings["folder"]) ? $settings["folder"] : "./";
            $this->cipher = isset($settings["cipher"]) && in_array($settings["cipher"], openssl_get_cipher_methods()) ? $settings["cipher"] : "aes-256-cbc";
            $this->onlyfiles = isset($settings["onlyfiles"]) && is_array($settings["onlyfiles"]) ? $settings["onlyfiles"] : array("php", "html", "css", "js");
            if(isset($settings["excludefolders"]) && is_array($settings["excludefolders"])){
                foreach($settings["excludefolders"] as $p){
                    $this->excludefolders[] = realpath($this->folder.$p);
                }
            }
            $this->ext = isset($settings["ext"]) && preg_match('/\.\w{2,}/', $settings["ext"]) ? $settings["ext"] : ".cryp";
            if(!in_array($this->ext, $this->onlyfiles)){
                $this->onlyfiles[] = str_replace(".", "", $this->ext);
            }
            $this->realpath = realpath(basename($_SERVER["PHP_SELF"]));
        }
        
        /**
         *
         * Enable or disable the test mode
         *
         * @param   boolean    $test   Flag for enable or disable the test mode
         *
         */
        public function test($test){
            $this->test = $test;
        }
        
        /**
         *
         * Enable or disable the text output
         *
         * @param   boolean    $echo   Flag for enable or disable the text output
         *
         */
        public function echo($echo){
            $this->echo = $echo;
        }
        
        /**
         *
         * Function that retrieves data from a file, encrypts or decrypts the data, saves it in a new file and deletes the source file
         *
         * @param   String    $method       The encryption method, it can be encrypt or decrypt
         * @param   String    $filename     The path to the source file
         * @param   String    $destination  The path to the destination file
         *
         */
        private function encryptFile($method, $filename, $destination){
            if(($method == "encrypt" || $method == "decrypt") && !$this->test){ 
                $handle = fopen($filename, "r");
                $contents = fread($handle, filesize($filename));

                $iv = $this->formatIV($this->key, $this->cipher);
                $lastsecret = openssl_decrypt($this->getLastSecret(),$this->cipher, $this->key, $options=0, $iv);
                $encryptionControl = $method == "decrypt" && $lastsecret == $this->key || $method == "encrypt";
                $crypttext = "";
                
                if($encryptionControl){
                    if($method == "decrypt"){
                        $crypttext = openssl_decrypt($contents, $this->cipher, $this->key, $options=0, $iv);
                    }
                    else if($method == "encrypt"){
                        $crypttext = openssl_encrypt($contents, $this->cipher, $this->key, $options=0, $iv);
                        $this->setLastSecret();
                    }
                    
                    file_put_contents($destination, $crypttext);
                    unlink($filename);
                }
                else{
                    echo("<br><b>WRONG PASSWORD</b><br>");
                }
                 
            }
        }
        
        /**
         *
         * Function that adjusts the length of the Initialization Vector based on the cipher used
         *
         * @param   String    $str      The string to adjust
         * @param   String    $chiper   The cipher used
         * @return  String              The adjusted string
         * 
         */
        private function formatIV($str, $chiper){
            $iv_size = openssl_cipher_iv_length($chiper);
            
            if(strlen($str) > $iv_size){
                $str = substr($str, 0, $iv_size);
            }
            else if(strlen($str) < $iv_size){
                $str .= str_repeat("0", $iv_size - strlen($str));
            }
            
            return $str;
        }
        
        /**
         *
         * Recursive function that iterates through all folders and files and checks if the file is to be encrypted or decrypted
         *
         * @param   String    $method   The encryption method, it can be encrypt or decrypt
         * @param   String    $depth    The current tree depth (used to print the file tree)
         * @param   String    $folder   The folder to be scanned
         * @param   String    $tree     The string that contains the file tree
         *
         */
        public function encrypt($method = "encrypt", $depth = 0, $folder = "", $tree = ""){
            $folder = $folder != "" ? $folder : $this->folder;
            $file = scandir($folder);
            
            if($this->echo && $depth == 0){
                $tree .= "<pre>";
            }
            
            for($i = 0; $i < count($file); $i++){
                if($file[$i] != "." && $file[$i] != ".."){
                    $isfolder = is_dir($folder."/".$file[$i]);
                    $currentscript = false;
                    $file_name = $file[$i];
                    $lastfile = $i == count($file) - 1;
                    $pathinfo = pathinfo($file_name);
                    $extension = array_key_exists("extension",$pathinfo) ? $pathinfo["extension"] : "";
                    if($isfolder && !in_array(realpath($folder."/".$file_name), $this->excludefolders)){
                        $tree .= $this->getFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
                        $tree = $this->encrypt($method, $depth+1, $folder."/".$file_name, $tree);
                    }
                    else if(!$isfolder && (in_array($extension, $this->onlyfiles) || (count($this->onlyfiles) == 1 && $this->onlyfiles[0] == "*"))){
                        if($this->realpath == realpath($folder."/".$file_name)){
                            $currentscript = true;
                            $tree .= $this->getFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
                        }
                        else{
                            if($method == "encrypt" && substr($file_name, -strlen($this->ext)) != $this->ext){
                                $new_file_name = $file_name.$this->ext;
                                $this->encryptFile($method, $folder."/".$file_name, $folder."/".$new_file_name);
                                $tree .= $this->getFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
                            }
                            else if($method == "decrypt" && substr($file_name, -strlen($this->ext)) == $this->ext){
                                $new_file_name = str_replace($this->ext, "", $file_name);
                                $this->encryptFile($method, $folder."/".$file_name, $folder."/".$new_file_name);
                                $tree .= $this->getFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
                            }
                        }
                    }
                }
            }
            
            if($this->echo && $depth == 0){
                $tree .= "</pre>";
                echo $tree;
            }
            
            return $tree;
        }
        
        /**
         *
         * Function to print the file tree
         *
         * @param   boolean     $isfolder       Indicates whether the current element is a folder or a file
         * @param   String      $file_name      The element name
         * @param   int         $depth          The current file tree depth
         * @param   boolean     $currentscript  Indicates whether the current element is the script itself
         * @param   boolean     $lastfile       Indicates whether the current element is the last file of the folder
         *
         */
        private function getFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile){
            $branch = "";
            
            if($isfolder){
                $branch = ($depth != 0 ? str_repeat("      ", $depth - 1)." └──── ":"").'<span style="color: red;">'.$file_name.'</span><br>';
            }
            else{
                $branch = ($depth != 0 ? str_repeat("      ", $depth - 1)." ".($lastfile ? "└────" : "├────")." ":"").'<span style="color: '.($currentscript ? 'green' : 'black').';">'.$file_name.'</span><br>';
            }
            
            return $branch;
        }
        
        /**
         *
         * Function that retrieves the last key used to encrypt
         *
         * @return  String  Last key used to encrypt
         *
         */
        public function getLastSecret(){
            try {
                $secret = json_decode(explode("?>",file_get_contents(__FILE__))[2],true);
                return $secret["hash"];
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        
        /**
         *
         * Function that store the key used to encrypt
         *
         */
        public function setLastSecret(){
            try {
                $contents = file_get_contents(__FILE__);
                $json = "{\"hash\":\"".$this->getLastSecret()."\"}";
                $contents = str_replace($json, '', $contents);
                file_put_contents(__FILE__, $contents);
            } catch (\Throwable $th) {
                //throw $th;
            }
            $fp = fopen(__FILE__, 'a');
            $iv = $this->formatIV($this->key, $this->cipher);
            $hashed = openssl_encrypt($this->key, $this->cipher, $this->key, $options=0, $iv);
            fwrite($fp, "{\"hash\":\"$hashed\"}");  
            fclose($fp); 
        }
    }
    
    $settings = array(
        "folder" => ".", // "." is the current folder
        "cipher" => "aes-256-cbc",
        "onlyfiles" => array("php")
    );
    
    $encrypter = new Encrypter("top secret", $settings);
  
    // $encrypter->echo(false);
    // $encrypter->test(false);
    
    $encrypter->encrypt("encrypt");

?>
