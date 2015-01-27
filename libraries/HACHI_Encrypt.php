<?php

abstract class HACHI_Encrypt
{
    protected
        $salt = "";
    
    abstract public function encryptThis($string);
    
    public function setSalt($string)
    {
        $this->salt = $salt;
    }
}

// =======>

    abstract class HACHI_EncryptWithHashFunction extends Encrypt{
        public function encryptThis($string, $hashType = "SHA512")
        {
            if($this->salt) {
                return hash($hashType, $string);
            } else {
                hash($hashType, $salt.hash($hashType, $password));
            }
        }
    }
    
// ======>
    
        class EncryptSHA512 extends HACHI_EncryptWithHashFunction
        {    
            public function encryptThis($string)
            {
                parent::encryptThis($string, "SHA512");
            }
        }
        
// ======
        
        class EncryptMD5 extends HACHI_EncryptWithHashFunction
        {    
            public function encryptThis($string)
            {
                parent::encryptThis($string, "MD5");
            }
        }
?>
