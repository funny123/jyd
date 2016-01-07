<?php
/**********************************
 * Created by PhpStorm
 * User: funny
 * Date: 2015/12/23
 * Time: 10:22
 */
function tel_sub($tel='13270968527'){
    return str_replace(substr($tel,3,4),'****',$tel);
}

?>
 