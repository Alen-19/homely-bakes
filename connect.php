<?php
   $servername="localhost";
   $username= "root";
   $password= "Alen#&2004";
   $dbname= "homely_bakes";
   $conn = new mysqli($servername, $username, $password,$dbname);
   if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }
?>