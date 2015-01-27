<div class='top' id='mainContainer'>
    <div id='header'>
        <a href='<?= $settings->PATH['site'] ;?>'><img src='<?= $settings->PATH['images']."elefant.gif" ;?>' id="elefant" /></a>

<?php

        if(USER_ID) {
?>
        <div id='headerLoggedin'>
            Logged in as: <i><?= $auth->getUserName(); ?></i><br />
            <button class="blueBtn" data-url="<?= $settings->PATH['site'] ;?>login.php?logout=1">logout</button>
        </div>

        <div id='topmenu'>
            <?= _("Money elefant"); ?>
            <table id="topmenuTable" style="margin-right: 20px;">
                <tr>
                    <td>
                        <div class="menuContainer">
                            <a href="<?= $settings->PATH['site'] ;?>?s=mainview" class="strechedLink"><?= _('mainview') ;?></a>
                            <img class="dollarImg hidden" src="images/dollar.png">
                        </div>
                    </td>
                    <td></td>
                    <td>
                        <div class="menuContainer">
                            <a href="<?= $settings->PATH['site'] ;?>?s=charts" class="strechedLink"><?= _('charts') ;?></a>
                            <img class="dollarImg hidden" src="images/dollar.png">
                        </div>
                    </td>
                    <td></td>
                    <td>
                        <div class="menuContainer">
                            <a href="<?= $settings->PATH['site'] ;?>?s=settings" class="strechedLink"><?= _('settings') ;?></a>
                            <img class="dollarImg hidden" src="images/dollar.png">
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
    } else {
?>
    <div id='headerLoggedin'>
    Please login
    </div>
<?php
}
?>
</div>
