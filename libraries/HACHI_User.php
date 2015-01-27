<?php

class HACHI_User
{
    protected
        $password = "",
        $email = "",
        $ID = 0;

    public function generatePassword(Encryption $encrypt)
    {
        return $encrypt->encrypt($this->password);
    }
 
    public function setPassword($password)
    {
        $this->password = $password;
    } 
 
    public function setID($ID)
    {
        $this->ID = $ID;
    }
  
    public function setEmail($email)
    {
        $this->email = $email;
    }
} 

// =======>

    class HACHI_MultiUser extends HACHI_User
    {
        protected
            $inControlOf = null,
            $controlledBy = null;
    }
    
// ==================
    
class HACHI_UserSettings {
    public
        $showExtraOption = 0,
        $showDefaultDates = 0;
}

?>
