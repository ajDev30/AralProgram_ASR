<?php
/* this logout is global function use this in every page to clear/unset session */
/* every time user want to logout */
session_start();
session_unset();
session_destroy();

header("Location: index.php");
exit();

?>
