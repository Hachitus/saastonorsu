<?php
// If the page is being accessed directly and not through index.php, then stop it.
if(!$settings)
    exit();

$debug = new Debugging();

define('GET_ACCESS', "2");
define('GIVE_ACCESS', "1");

if(!empty($_POST['password'])) {
    // SALASANAN VAIHTAMINEN
    $haku = "UPDATE users SET password = PASSWORD('".$dataSource->filterVariable($_POST['password'])."') WHERE ID = '".USER_ID."'";
    if(!$dataSource->query($haku)) {
        $debug->addMsg("Error changing password".$haku);
    } else {
        echo "Password changed";
    }
}
if(!empty($_POST['country'])) {
    // UUDEN MAAN MUOKKAAMINEN KÄYTTÄJÄLLE.
    $haku ="UPDATE users SET country = '".$dataSource->filterVariable($_POST['country'])."' WHERE ID = '".USER_ID."'";
    if(!$dataSource->query($haku)) {
        $debug->addMsg("Error setting country".$haku);
    } else {
        echo "Country settings changed";
    }
}
if(!empty($_POST['accountAccess'])) {
    // giveAccess and getAccess are the second steps:
    $userID = $dataSource->filterVariable($_POST['userID']);
    if($_POST['giveAccess']) {
        $haku ="INSERT INTO accessAccounts SET accessPhase = '".GIVE_ACCESS."', controllerID = '".$userID."', controlleeID = '".USER_ID."'";
        if(!$dataSource->query($haku)) {
            $debug->addMsg("Error getting user 1".$haku);
        } else {
            echo "User access modified. Give ".$haku;
        }

        // We insert the message to the system, so that the other user get the approval notification / question on their end 
        // and thus can approve or decline it
        $haku ="INSERT INTO messages SET text = \"Someone wants to give access to their account. Do you accept?<form method='POST'><type='hidden' name='toUser' value=''><type='submit' class='blueBtn' name='approve'><type='submit' class='redBtn' name='decline'></form>\"', type='2', userID='".USER_ID."'";
        $dataSource->query($haku);        
        
        echo "Access request sent";
    } elseif ($_POST['getAccess']) {
        $haku ="INSERT INTO accessAccounts SET accessPhase = '".GET_ACCESS."', controllerID = '".USER_ID."', controlleeID = '".$userID."'";
        if(!$dataSource->query($haku)) {
            $debug->addMsg("Error getting user 2".$haku);
        } else {
            echo "User access modified. Get ".$haku;
        }

        // We insert the message to the system, so that the other user get the approval notification / question on their end 
        // and thus can approve or decline it
        $haku ="INSERT INTO messages SET text = \"Someone requested to get access to your account. Do you approve?<form method='POST'><type='hidden' name='toUser' value=''><type='submit' name='approve'><type='submit' name='decline'></form>\", type='1', userID='".USER_ID."'";
        $dataSource->query($haku);
        
        echo "Access request sent";
    } else {
        // Fetching the user based on input and suggesting what to do with it. This is the first step
        $mail = $dataSource->filterVariable($_POST['email']);
        $haku ="SELECT ID FROM users WHERE email = '".$mail."'";
        if(!$tulos = $dataSource->query($haku)) {
            $debug->addMsg("Error selecting users".$haku);
        } else {
?>
            User found. Do you want to:<br />
            <form action='#' method='POST' name='accountAccess'>
            <input type='submit' class='blueBtn' name='getAccess' value='Get access to another user'><br />
            <input type='submit' class='blueBtn' name='giveAccess' value='Give access to another user'>
            <input type='hidden' name='userID' value='<?= $tulos[0]; ?>'>
<?php
            exit("stop here for answer");
        }        
    }
    // When the user approves or declines the request for access to their account:
    if(!empty($_POST['accessRequestApproved'])) {

        // this is to set the info that the user has another controllable accounts. This way we don't need to check if a user 
        // has any access rights to another accounts everytime user logs looks for statistics etc.
        if($_POST['accessRequestApproved'] == 1) {
            $userID = $dataSource->filterVariable($_POST['userID']);
            if($_POST['giveAccess'] == GIVE_ACCESS) {
                $dataSource->query("UPDATE users SET accessRight='1' WHERE ID = '".$userID."'");
                $dataSource->query("UPDATE accessAccounts SET accessPhase = '10', controllerID = '".$userID."', controlleeID = '".USER_ID."'");                
            } elseif($_POST['access'] == GET_ACCESS) {
                $dataSource->query("UPDATE users SET accessRight='1' WHERE ID = '".USER_ID."'");
                $dataSource->query("UPDATE accessAccounts SET accessPhase = '10', controllerID = '".USER_ID."', controlleeID = '".$userID."'");
            }
        } elseif($_POST['accessRequestApproved'] == 2) {
            $acID = $_POST['acID'];
            $userID = $dataSource->filterVariable($_POST['userID']);
            $message = "";
            $dataSource->query("DELETE FROM accessAccounts WHERE ID='".$acID."'");
            echo "Access proposal has been denied";
            if($_POST['access'] == GIVE_ACCESS) {
                $message = "You gave access to another user (".$userID.") in request nro #".$acID."'";
            } elseif($_POST['access'] == GET_ACCESS) {
                $message = "You got access to another user (".$userID.") in request nro #".$acID."'";
            }
            $haku = "INSERT INTO messages SET text = '".$message."', userID = '".USER_ID."'";
            if(!$dataSource->query($haku)) {
                $debug->addMsg("Problem: ".$haku);
            }
        }
    }
}

// We fetch the country and default ALV with the same query and assing them their own variables for clarity:
$haku = "SELECT country FROM users WHERE ID = '".USER_ID."'";
if(!$haettuUser = $dataSource->query($haku)) {
    $debug->addMsg("Error fetching countries".$haku);
}
$userInfo = $haettuUser->fetch_row();
$country = $userInfo[0];


// Fetch messages:
$haku = "SELECT time, text, SUM(unread) FROM messages WHERE userID = '".USER_ID."'";
if(!$haettuMessages = $dataSource->query($haku)) {
    echo $haku;
    $debug->addMsg("Error getting messages".$haku);
}
$messages = $haettuMessages->fetch_row(); 

?>

<div class='content' id="settings">
    <div class="ui-tabs ui-widget ui-widget-content ui-corner-all contentInnerBlock">
        <div id="greyBlocks">
            <form method="POST" action="#">
                <div class="columnBase columnColorHeader">
                    <?= _('password') ;?>
                </div>
                <input type="password" name="password">
                <br />
                <input type='submit' class="blueBtn" value="<?= _('submitPassword') ;?>">
            </form>
            <?php /*<p>
                <a href='#' onClick="showOrHide('messages')"><?= $languages->getText('messages') ;?> (<?= $messages[2].$languages->getText('new') ;?>)</a>
                <div id='messages' class='hidden'>
        <?php
                    if(!empty($messages[1]) && !empty($messages[1])) {
                        echo date("D d.m.y, H:I", $messages[0])." - ".$messages[1];
                    } else {
                        echo _("no messages");
                    }
        ?>
                </div>
            </p> */ ?>
        </div>
        <div id="greyBlocks">
            <form method="POST" action="#">
                <div class="columnBase columnColorHeader">
                    Country:
                </div>
                <select name='country'>
        <?php

                    $countries = Array(_("Finland"), _("United Kingdom"));
                    foreach($countries as $var) {
                        $selected = "";
                        if($var == $country) {
                            $selected = "selected";
                        }
                        echo "<option value='".$var."' ".$selected.">".$var."</option>";
                    }
        ?>
                </select>
                <br />
                <input type='submit' class="blueBtn" value='<?= _('change country') ;?>'>
            </form>
        </div>
        <?php /*<div id="greyBlocks">
            <form method="POST" action="<?= $settings->PATH['site'] ;?>">
                <?= _('give access to another user') ;?><br>
                <input type="text" name="email"><br>
                <input type='submit' value='<?= _("add privileges") ;?>' name="accountAccess">
            </form>
        </div> */ ?>
    </div>        
</div>