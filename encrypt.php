<?php
    
    class Encripter{
        private $key;
        private $folder;
        private $cipher;
        private $realpath;
        private $onlyfiles;
        private $avoidpath = array();
        
        private $ext = ".crypt";
        private $test = true;
        private $echo = true;
        
        public function __construct($key, $settings = array()){
            $this->key = $key;
            
            if($key == ""){
                throw new Exception("Key can't be an empty string");
            }
            
            $this->folder = isset($settings["folder"]) && is_dir($settings["folder"]) ? $settings["folder"] : ".";
            $this->cipher = isset($settings["cipher"]) && in_array($settings["cipher"], openssl_get_cipher_methods()) ? $settings["cipher"] : "aes-256-cbc";
            $this->onlyfiles = isset($settings["onlyfiles"]) && is_array($settings["onlyfiles"]) ? $settings["onlyfiles"] : array("php", "html", "css", "js");
            if(isset($settings["avoidpath"]) && is_array($settings["avoidpath"])){
                foreach($settings["avoidpath"] as $p){
                    $this->avoidpath[] = realpath($p);
                }
            }
            $this->ext = isset($settings["ext"]) && preg_match('/\.\w{2,}/', $settings["ext"]) ? $settings["ext"] : ".cryp";
            if(!in_array($this->ext, $this->onlyfiles)){
                $this->onlyfiles[] = str_replace(".", "", $this->ext);
            }
            $this->realpath = realpath(basename($_SERVER["PHP_SELF"]));
        }
        
        public function test($test){
            $this->test = $test;
        }
        
        public function echo($echo){
            $this->echo = $echo;
        }
        
        private function encryptFile($method, $filename, $destination){
            if(($method == "encrypt" || $method == "decrypt") && !$this->test){ 
        		$handle = fopen($filename, "r");
        		$contents = fread($handle, filesize($filename));
                
        		$iv_size = openssl_cipher_iv_length($this->cipher);
        		$iv = substr($this->key, 0, $iv_size);
        		$crypttext = $method == "encrypt" ? openssl_encrypt($contents, $this->cipher, $this->key, $options=0, $iv) : openssl_decrypt($contents, $this->cipher, $this->key, $options=0, $iv);
                
        		file_put_contents($destination, $crypttext);
                
        		unlink($filename);
            }
    	}
    	
    	public function encrypt($method = "encrypt", $depth = 0, $folder = ""){
            $folder = $folder != "" ? $folder : $this->folder;
    		$file = scandir($folder);
            
            if($this->echo && $depth == 0){
                echo "<pre>";
            }
            
    		for($i = 0; $i < count($file); $i++){
    			if($file[$i] != "." && $file[$i] != ".."){
                    $isfolder = is_dir($folder."/".$file[$i]);
                    $currentscript = false;
                    $file_name = $file[$i];
                    $lastfile = $i == count($file) - 1;
                    $pathinfo = pathinfo($file_name);
                    
    				if($isfolder && !in_array(realpath($folder."/".$file_name), $this->avoidpath)){
                        $this->printFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
    					$this->encrypt($method, $depth+1, $folder."/".$file_name);
    				}
    				else if(!$isfolder && (in_array($pathinfo["extension"], $this->onlyfiles) || (count($this->onlyfiles) == 1 && $this->onlyfiles[0] == "*"))){
                        if($this->realpath == realpath($folder."/".$file_name)){
                            $currentscript = true;
                            $this->printFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
                        }
                        else{
                            if($method == "encrypt" && substr($file_name, -strlen($this->ext)) != $this->ext){
                                $new_file_name = $file_name.$this->ext;
                                $this->encryptFile($method, $folder."/".$file_name, $folder."/".$new_file_name);
                                $this->printFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
                            }
                            else if($method == "decrypt" && substr($file_name, -strlen($this->ext)) == $this->ext){
                                $new_file_name = str_replace($this->ext, "", $file_name);
                                $this->encryptFile($method, $folder."/".$file_name, $new_file_name);
                                $this->printFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile);
                            }
                        }
    				}
                    else{
                        // $this->printFilesTree($isfolder, "<s>".$file_name."</s>", $depth, $currentscript, $lastfile);
                    }
    			}
    		}
            
            if($this->echo && $depth == 0){
                echo "</pre>";
            }
    	}
        
        public function printFilesTree($isfolder, $file_name, $depth, $currentscript, $lastfile){
            if($this->echo){
                $branch = "";
                
                if($isfolder){
                    $branch = ($depth != 0 ? str_repeat("      ", $depth - 1)." └──── ":"").'<span style="color: red;">'.$file_name.'</span><br>';
                }
                else{
                    $branch = ($depth != 0 ? str_repeat("      ", $depth - 1)." ".($lastfile ? "└────" : "├────")." ":"").'<span style="color: '.($currentscript ? 'green' : 'black').';">'.$file_name.'</span><br>';
                }
                
                echo $branch;
            }
        }
    }
    
    $settings = array(
        "folder" => ".", // "." is the current folder
        "cipher" => "aes-256-cbc",
        "onlyfiles" => array("php"),
        "avoidpath" => array("resource", "function")
    );
    
    $encrypter = new Encripter("top secret key22", $settings);
    
    // $encrypter->echo(false);
    // $encrypter->test(false);
    
    $encrypter->encrypt("decrypt");

?>