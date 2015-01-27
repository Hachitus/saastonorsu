<?php

$ourFileName = "test/testFile.txt";
$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
fclose($ourFileHandle);

echo "jee";

?>
