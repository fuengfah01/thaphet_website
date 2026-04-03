<?php
session_start();
session_destroy();
header("Location: /thaphet_website/admin/index.php");
exit;
