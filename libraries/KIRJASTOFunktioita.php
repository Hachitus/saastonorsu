<?php
function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
        $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
        return $ip;
}
function sivulle( $uri )
{
	echo"
		<script type='text/javascript' language='JavaScript'>
		<!--
		document.location.href='", $uri, "';
		-->
		</script> ";
}
class popup
{
	function createPopupJS () {
?>
		<script type="text/javascript">
		function newPopup(url, title) {
			popupWindow = window.open(
				url,title,'height=700,width=800,left=10,top=10,resizable=yes,scrollbars=yes,toolbar=yes,menubar=no,location=no,directories=no,status=yes')
		}
		</script>
<?php
	}
	function popupURI ($uri, $title) {
		echo "JavaScript:newPopup('".$uri."', '".$title."')";
	}
}
?>
