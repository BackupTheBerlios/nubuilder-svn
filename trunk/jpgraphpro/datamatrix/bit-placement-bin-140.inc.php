<?php
 class BitPlacement_140 { var $iBitPosCRC32 = array(-1218165820,-1642971647,398894782,1874383969,-389282195,1785029150,527100582, 850234052,-659011183,1232248065,983196112,183884585,1491393156,610282677, -821932643,-89670890,-1337417251,669008347, 1977297474,917193336,2107802295); var $iError = 0 ; function BitPlacement_140() { } function Set($aIdx,$aDataBits,&$aOutputMatrice) { if( $aIdx < 0 || $aIdx > 20 ) { $this->iError = -14; return false; } $size = $aIdx*2+7; $sapi = php_sapi_name(); $fname = dirname(__FILE__)."/bindata/bitplacement-$size.dat"; $fp=fopen($fname,'r'); if( $fp === false ) { $this->iError = -26; return false; } $s = fread($fp,8192); $m = array_merge(unpack('n*',$s)); $crc32 = crc32(implode('',$m)); if( $crc32 != $this->iBitPosCRC32[$aIdx] ) { $this->iError = -22; return false; } $aOutputMatrice = array(); for($i=0; $i < $size; ++$i ) { for($j=0; $j < $size; ++$j ) { $aOutputMatrice[$i+1][$j+1] = $aDataBits[$m[$i*$size+$j]]; } } $b=1; for($i=0; $i<$size+2; ++$i) { $aOutputMatrice[$i][0] = 1 ; $aOutputMatrice[$i][$size+1] = $b ; $b ^= 1; } $b = 1; for($i=0; $i<$size+2; ++$i) { $aOutputMatrice[$size+1][$i] = 1; $aOutputMatrice[0][$i] = $b; $b ^= 1; } } } ?>
