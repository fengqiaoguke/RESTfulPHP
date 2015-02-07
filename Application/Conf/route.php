<?php 
return array(
    "/^blogs\/\d+\/order$/i"=>"m=blog&id=$1&do=order",
    "/^user\/([0-9]+)$/i"=>"m=user&id=$1",
    "/^blog\/([0-9]+)$/i"=>"m=blog&id=$1",
    "/^blog\/([0-9]+)\/search$/i"=>"m=user&id=$1&do=search",
)
?>