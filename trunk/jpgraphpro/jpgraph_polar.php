<?php
 require_once ('jpgraph_plotmark.inc.php'); require_once "jpgraph_log.php"; DEFINE('POLAR_360',1); DEFINE('POLAR_180',2); class PolarPlot { var $numpoints=0; var $iColor='navy',$iFillColor=''; var $iLineWeight=1; var $coord=null; var $legendcsimtarget=''; var $legendcsimalt=''; var $legend=""; var $csimtargets=array(); var $csimareas=""; var $csimalts=null; var $line_style='solid',$mark; function PolarPlot($aData) { $n = count($aData); if( $n & 1 ) { JpGraphError::RaiseL(17001); } $this->numpoints = $n/2; $this->coord = $aData; $this->mark = new PlotMark(); } function SetWeight($aWeight) { $this->iLineWeight = $aWeight; } function SetColor($aColor){ $this->iColor = $aColor; } function SetFillColor($aColor){ $this->iFillColor = $aColor; } function Max() { $m = $this->coord[1]; $i=1; while( $i < $this->numpoints ) { $m = max($m,$this->coord[2*$i+1]); ++$i; } return $m; } function SetCSIMTargets($aTargets,$aAlts=null) { $this->csimtargets=$aTargets; $this->csimalts=$aAlts; } function GetCSIMareas() { return $this->csimareas; } function SetLegend($aLegend,$aCSIM="",$aCSIMAlt="") { $this->legend = $aLegend; $this->legendcsimtarget = $aCSIM; $this->legendcsimalt = $aCSIMAlt; } function Legend(&$aGraph) { $color = $this->iColor ; if( $this->legend != "" ) { if( $this->iFillColor!='' ) { $color = $this->iFillColor; $aGraph->legend->Add($this->legend,$color,$this->mark,0, $this->legendcsimtarget,$this->legendcsimalt); } else { $aGraph->legend->Add($this->legend,$color,$this->mark,$this->line_style, $this->legendcsimtarget,$this->legendcsimalt); } } } function Stroke(&$img,$scale) { $i=0; $p=array(); $this->csimareas=''; while($i < $this->numpoints) { list($x1,$y1) = $scale->PTranslate($this->coord[2*$i],$this->coord[2*$i+1]); $p[2*$i] = $x1; $p[2*$i+1] = $y1; if( isset($this->csimtargets[$i]) ) { $this->mark->SetCSIMTarget($this->csimtargets[$i]); $this->mark->SetCSIMAlt($this->csimalts[$i]); $this->mark->SetCSIMAltVal($this->coord[2*$i], $this->coord[2*$i+1]); $this->mark->Stroke($img,$x1,$y1); $this->csimareas .= $this->mark->GetCSIMAreas(); } else $this->mark->Stroke($img,$x1,$y1); ++$i; } if( $this->iFillColor != '' ) { $img->SetColor($this->iFillColor); $img->FilledPolygon($p); } $img->SetLineWeight($this->iLineWeight); $img->SetColor($this->iColor); $img->Polygon($p,$this->iFillColor!=''); } } class PolarAxis extends Axis { var $angle_step=15,$angle_color='lightgray',$angle_label_color='black'; var $angle_fontfam=FF_FONT1,$angle_fontstyle=FS_NORMAL,$angle_fontsize=10; var $angle_fontcolor = 'navy'; var $gridminor_color='lightgray',$gridmajor_color='lightgray'; var $show_minor_grid = false, $show_major_grid = true ; var $show_angle_mark=true, $show_angle_grid=true, $show_angle_label=true; var $angle_tick_len=3, $angle_tick_len2=3, $angle_tick_color='black'; var $show_angle_tick=true; var $radius_tick_color='black'; function PolarAxis(&$img,&$aScale) { parent::Axis($img,$aScale); } function ShowAngleDegreeMark($aFlg=true) { $this->show_angle_mark = $aFlg; } function SetAngleStep($aStep) { $this->angle_step=$aStep; } function HideTicks($aFlg=true,$aAngleFlg=true) { parent::HideTicks($aFlg,$aFlg); $this->show_angle_tick = !$aAngleFlg; } function ShowAngleLabel($aFlg=true) { $this->show_angle_label = $aFlg; } function ShowGrid($aMajor=true,$aMinor=false,$aAngle=true) { $this->show_minor_grid = $aMinor; $this->show_major_grid = $aMajor; $this->show_angle_grid = $aAngle ; } function SetAngleFont($aFontFam,$aFontStyle=FS_NORMAL,$aFontSize=10) { $this->angle_fontfam = $aFontFam; $this->angle_fontstyle = $aFontStyle; $this->angle_fontsize = $aFontSize; } function SetColor($aColor,$aRadColor='',$aAngleColor='') { if( $aAngleColor == '' ) $aAngleColor=$aColor; parent::SetColor($aColor,$aRadColor); $this->angle_fontcolor = $aAngleColor; } function SetGridColor($aMajorColor,$aMinorColor='',$aAngleColor='') { if( $aMinorColor == '' ) $aMinorColor = $aMajorColor; if( $aAngleColor == '' ) $aAngleColor = $aMajorColor; $this->gridminor_color = $aMinorColor; $this->gridmajor_color = $aMajorColor; $this->angle_color = $aAngleColor; } function SetTickColors($aRadColor,$aAngleColor='') { $this->radius_tick_color = $aRadColor; $this->angle_tick_color = $aAngleColor; } function StrokeGrid($pos) { $x = round($this->img->left_margin + $this->img->plotwidth/2); $this->scale->ticks->Stroke($this->img,$this->scale,$pos); $pmin = array(); $p = $this->scale->ticks->ticks_pos; $n = count($p); $i = 0; $this->img->SetColor($this->gridminor_color); while( $i < $n ) { $r = $p[$i]-$x+1; $pmin[]=$r; if( $this->show_minor_grid ) { $this->img->Circle($x,$pos,$r); } $i++; } $limit = max($this->img->plotwidth,$this->img->plotheight)*1.4 ; while( $r < $limit ) { $off = $r; $i=1; $r = $off + round($p[$i]-$x+1); while( $r < $limit && $i < $n ) { $r = $off+$p[$i]-$x; $pmin[]=$r; if( $this->show_minor_grid ) { $this->img->Circle($x,$pos,$r); } $i++; } } if( $this->show_major_grid ) { $pmaj = $this->scale->ticks->maj_ticks_pos; $p = $this->scale->ticks->ticks_pos; if( $this->scale->name == 'lin' ) { $step=round(($pmaj[1] - $pmaj[0])/($p[1] - $p[0])); } else { $step=9; } $n = round(count($pmin)/$step); $i = 0; $this->img->SetColor($this->gridmajor_color); $limit = max($this->img->plotwidth,$this->img->plotheight)*1.4 ; $off = $r; $i=0; $r = $pmin[$i*$step]; while( $r < $limit && $i < $n ) { $r = $pmin[$i*$step]; $this->img->Circle($x,$pos,$r); $i++; } } if( $this->show_angle_grid ) { $this->img->SetColor($this->angle_color); $d = max($this->img->plotheight,$this->img->plotwidth)*1.4 ; $a = 0; $p = $this->scale->ticks->ticks_pos; $start_radius = $p[1]-$x; while( $a < 360 ) { if( $a == 90 || $a == 270 ) { $this->img->Line($x+$start_radius*cos($a/180*M_PI)+1, $pos-$start_radius*sin($a/180*M_PI), $x+$start_radius*cos($a/180*M_PI)+1, $pos-$d*sin($a/180*M_PI)); } else { $this->img->Line($x+$start_radius*cos($a/180*M_PI)+1, $pos-$start_radius*sin($a/180*M_PI), $x+$d*cos($a/180*M_PI), $pos-$d*sin($a/180*M_PI)); } $a += $this->angle_step; } } } function StrokeAngleLabels($pos,$type) { if( !$this->show_angle_label ) return; $x0 = round($this->img->left_margin+$this->img->plotwidth/2)+1; $d = max($this->img->plotwidth,$this->img->plotheight)*1.42; $a = $this->angle_step; $t = new Text(); $t->SetColor($this->angle_fontcolor); $t->SetFont($this->angle_fontfam,$this->angle_fontstyle,$this->angle_fontsize); $xright = $this->img->width - $this->img->right_margin; $ytop = $this->img->top_margin; $xleft = $this->img->left_margin; $ybottom = $this->img->height - $this->img->bottom_margin; $ha = 'left'; $va = 'center'; $w = $this->img->plotwidth/2; $h = $this->img->plotheight/2; $xt = $x0; $yt = $pos; $margin=5; $tl = $this->angle_tick_len ; $tl2 = $this->angle_tick_len2 ; $this->img->SetColor($this->angle_tick_color); $rot90 = $this->img->a == 90 ; if( $type == POLAR_360 ) { $ca1 = atan($h/$w)/M_PI*180; $ca2 = 180-$ca1; $ca3 = $ca1+180; $ca4 = 360-$ca1; $end = 360; while( $a < $end ) { $ca = cos($a/180*M_PI); $sa = sin($a/180*M_PI); $x = $d*$ca; $y = $d*$sa; $xt=1000;$yt=1000; if( $a <= $ca1 || $a >= $ca4 ) { $yt = $pos - $w * $y/$x; $xt = $xright + $margin; if( $rot90 ) { $ha = 'center'; $va = 'top'; } else { $ha = 'left'; $va = 'center'; } $x1=$xright-$tl2; $x2=$xright+$tl; $y1=$y2=$yt; } elseif( $a > $ca1 && $a < $ca2 ) { $xt = $x0 + $h * $x/$y; $yt = $ytop - $margin; if( $rot90 ) { $ha = 'left'; $va = 'center'; } else { $ha = 'center'; $va = 'bottom'; } $y1=$ytop+$tl2;$y2=$ytop-$tl; $x1=$x2=$xt; } elseif( $a >= $ca2 && $a <= $ca3 ) { $yt = $pos + $w * $y/$x; $xt = $xleft - $margin; if( $rot90 ) { $ha = 'center'; $va = 'bottom'; } else { $ha = 'right'; $va = 'center'; } $x1=$xleft+$tl2;$x2=$xleft-$tl; $y1=$y2=$yt; } else { $xt = $x0 - $h * $x/$y; $yt = $ybottom + $margin; if( $rot90 ) { $ha = 'right'; $va = 'center'; } else { $ha = 'center'; $va = 'top'; } $y1=$ybottom-$tl2;$y2=$ybottom+$tl; $x1=$x2=$xt; } if( $a != 0 && $a != 180 ) { $t->Align($ha,$va); if( $this->show_angle_mark ) $a .= '�'; $t->Set($a); $t->Stroke($this->img,$xt,$yt); if( $this->show_angle_tick ) $this->img->Line($x1,$y1,$x2,$y2); } $a += $this->angle_step; } } else { $ca1 = atan($h/$w*2)/M_PI*180; $ca2 = 180-$ca1; $end = 180; while( $a < $end ) { $ca = cos($a/180*M_PI); $sa = sin($a/180*M_PI); $x = $d*$ca; $y = $d*$sa; if( $a <= $ca1 ) { $yt = $pos - $w * $y/$x; $xt = $xright + $margin; if( $rot90 ) { $ha = 'center'; $va = 'top'; } else { $ha = 'left'; $va = 'center'; } $x1=$xright-$tl2; $x2=$xright+$tl; $y1=$y2=$yt; } elseif( $a > $ca1 && $a < $ca2 ) { $xt = $x0 + 2*$h * $x/$y; $yt = $ytop - $margin; if( $rot90 ) { $ha = 'left'; $va = 'center'; } else { $ha = 'center'; $va = 'bottom'; } $y1=$ytop+$tl2;$y2=$ytop-$tl; $x1=$x2=$xt; } elseif( $a >= $ca2 ) { $yt = $pos + $w * $y/$x; $xt = $xleft - $margin; if( $rot90 ) { $ha = 'center'; $va = 'bottom'; } else { $ha = 'right'; $va = 'center'; } $x1=$xleft+$tl2;$x2=$xleft-$tl; $y1=$y2=$yt; } $t->Align($ha,$va); if( $this->show_angle_mark ) $a .= '�'; $t->Set($a); $t->Stroke($this->img,$xt,$yt); if( $this->show_angle_tick ) $this->img->Line($x1,$y1,$x2,$y2); $a += $this->angle_step; } } } function Stroke($pos) { $this->img->SetLineWeight($this->weight); $this->img->SetColor($this->color); $this->img->SetFont($this->font_family,$this->font_style,$this->font_size); if( !$this->hide_line ) $this->img->FilledRectangle($this->img->left_margin,$pos, $this->img->width-$this->img->right_margin,$pos+$this->weight-1); $y=$pos+$this->img->GetFontHeight()+$this->title_margin+$this->title->margin; if( $this->title_adjust=="high" ) $this->title->Pos($this->img->width-$this->img->right_margin,$y,"right","top"); elseif( $this->title_adjust=="middle" || $this->title_adjust=="center" ) $this->title->Pos(($this->img->width-$this->img->left_margin- $this->img->right_margin)/2+$this->img->left_margin, $y,"center","top"); elseif($this->title_adjust=="low") $this->title->Pos($this->img->left_margin,$y,"left","top"); else { JpGraphError::RaiseL(17002,$this->title_adjust); } if (!$this->hide_labels) { $this->StrokeLabels($pos,false); } $this->img->SetColor($this->radius_tick_color); $this->scale->ticks->Stroke($this->img,$this->scale,$pos); $mid = 2*($this->img->left_margin+$this->img->plotwidth/2); $n = count($this->scale->ticks->ticks_pos); $i=0; while( $i < $n ) { $this->scale->ticks->ticks_pos[$i] = $mid-$this->scale->ticks->ticks_pos[$i] ; ++$i; } $n = count($this->scale->ticks->maj_ticks_pos); $i=0; while( $i < $n ) { $this->scale->ticks->maj_ticks_pos[$i] = $mid-$this->scale->ticks->maj_ticks_pos[$i] ; ++$i; } $n = count($this->scale->ticks->maj_ticklabels_pos); $i=1; while( $i < $n ) { $this->scale->ticks->maj_ticklabels_pos[$i] = $mid-$this->scale->ticks->maj_ticklabels_pos[$i] ; ++$i; } $n = count($this->scale->ticks->ticks_pos); $yu = $pos - $this->scale->ticks->direction*$this->scale->ticks->GetMinTickAbsSize(); if( ! $this->scale->ticks->supress_minor_tickmarks ) { $i=1; while( $i < $n/2 ) { $x = round($this->scale->ticks->ticks_pos[$i]) ; $this->img->Line($x,$pos,$x,$yu); ++$i; } } $n = count($this->scale->ticks->maj_ticks_pos); $yu = $pos - $this->scale->ticks->direction*$this->scale->ticks->GetMajTickAbsSize(); if( ! $this->scale->ticks->supress_tickmarks ) { $i=1; while( $i < $n/2 ) { $x = round($this->scale->ticks->maj_ticks_pos[$i]) ; $this->img->Line($x,$pos,$x,$yu); ++$i; } } if (!$this->hide_labels) { $this->StrokeLabels($pos,false); } $this->title->Stroke($this->img); } } class PolarScale extends LinearScale { var $graph; function PolarScale($aMax=0,&$graph) { parent::LinearScale(0,$aMax,'x'); $this->graph = &$graph; } function _Translate($v) { return parent::Translate($v); } function PTranslate($aAngle,$aRad) { $m = $this->scale[1]; $w = $this->graph->img->plotwidth/2; $aRad = $aRad/$m*$w; $x = cos( $aAngle/180 * M_PI ) * $aRad; $y = sin( $aAngle/180 * M_PI ) * $aRad; $x += $this->_Translate(0); if( $this->graph->iType == POLAR_360 ) { $y = ($this->graph->img->top_margin + $this->graph->img->plotheight/2) - $y; } else { $y = ($this->graph->img->top_margin + $this->graph->img->plotheight) - $y; } return array($x,$y); } } class PolarLogScale extends LogScale { var $graph; function PolarLogScale($aMax=1,&$graph) { parent::LogScale(0,$aMax,'x'); $this->graph = &$graph; $this->ticks->SetLabelLogType(LOGLABELS_MAGNITUDE); } function PTranslate($aAngle,$aRad) { if( $aRad == 0 ) $aRad = 1; $aRad = log10($aRad); $m = $this->scale[1]; $w = $this->graph->img->plotwidth/2; $aRad = $aRad/$m*$w; $x = cos( $aAngle/180 * M_PI ) * $aRad; $y = sin( $aAngle/180 * M_PI ) * $aRad; $x += $w+$this->graph->img->left_margin; if( $this->graph->iType == POLAR_360 ) { $y = ($this->graph->img->top_margin + $this->graph->img->plotheight/2) - $y; } else { $y = ($this->graph->img->top_margin + $this->graph->img->plotheight) - $y; } return array($x,$y); } } class PolarGraph extends Graph { var $scale; var $iType=POLAR_360; var $axis; function PolarGraph($aWidth=300,$aHeight=200,$aCachedName="",$aTimeOut=0,$aInline=true) { parent::Graph($aWidth,$aHeight,$aCachedName,$aTimeOut,$aInline) ; $this->SetDensity(TICKD_DENSE); $this->SetBox(); $this->SetMarginColor('white'); } function SetDensity($aDense) { $this->SetTickDensity(TICKD_NORMAL,$aDense); } function Set90AndMargin($lm=0,$rm=0,$tm=0,$bm=0) { $adj = ($this->img->height - $this->img->width)/2; $this->SetAngle(90); $this->img->SetMargin($lm-$adj,$rm-$adj,$tm+$adj,$bm+$adj); $this->img->SetCenter(floor($this->img->width/2),floor($this->img->height/2)); $this->axis->SetLabelAlign('right','center'); } function SetScale($aScale,$rmax=0) { if( $aScale == 'lin' ) $this->scale = new PolarScale($rmax,$this); elseif( $aScale == 'log' ) { $this->scale = new PolarLogScale($rmax,$this); } else { JpGraphError::RaiseL(17004); } $this->axis = new PolarAxis($this->img,$this->scale); $this->SetMargin(40,40,50,40); } function SetType($aType) { $this->iType = $aType; } function SetPlotSize($w,$h) { $this->SetMargin(($this->img->width-$w)/2,($this->img->width-$w)/2, ($this->img->height-$h)/2,($this->img->height-$h)/2); } function GetPlotsMax() { $n = count($this->plots); $m = $this->plots[0]->Max(); $i=1; while($i < $n) { $m = max($this->plots[$i]->Max(),$m); ++$i; } return $m; } function Stroke($aStrokeFileName="") { $this->AdjustMarginsForTitles(); $_csim = ($aStrokeFileName===_CSIM_SPECIALFILE); $this->iHasStroked = true; if( !$this->scale->IsSpecified() && count($this->plots)>0 ) { $max = $this->GetPlotsMax(); $t1 = $this->img->plotwidth; $this->img->plotwidth /= 2; $t2 = $this->img->left_margin; $this->img->left_margin += $this->img->plotwidth+1; $this->scale->AutoScale($this->img,0,$max, $this->img->plotwidth/$this->xtick_factor/2); $this->img->plotwidth = $t1; $this->img->left_margin = $t2; } else { $max = $this->scale->scale[1]; $t1 = $this->img->plotwidth; $this->img->plotwidth /= 2; $t2 = $this->img->left_margin; $this->img->left_margin += $this->img->plotwidth+1; $this->scale->AutoScale($this->img,0,$max, $this->img->plotwidth/$this->xtick_factor/2); $this->img->plotwidth = $t1; $this->img->left_margin = $t2; } if( $this->iType == POLAR_180 ) $pos = $this->img->height - $this->img->bottom_margin; else $pos = $this->img->plotheight/2 + $this->img->top_margin; if( !$_csim ) { $this->StrokePlotArea(); } $this->iDoClipping = true; if( $this->iDoClipping ) { $oldimage = $this->img->CloneCanvasH(); } if( !$_csim ) { $this->axis->StrokeGrid($pos); } for($i=0; $i < count($this->plots); ++$i) { $this->plots[$i]->Stroke($this->img,$this->scale); } if( $this->iDoClipping ) { if( $this->img->a == 0 ) { $this->img->CopyCanvasH($oldimage,$this->img->img, $this->img->left_margin,$this->img->top_margin, $this->img->left_margin,$this->img->top_margin, $this->img->plotwidth+1,$this->img->plotheight+1); } elseif( $this->img->a == 90 ) { $adj = round(($this->img->height - $this->img->width)/2); $this->img->CopyCanvasH($oldimage,$this->img->img, $this->img->bottom_margin-$adj,$this->img->left_margin+$adj, $this->img->bottom_margin-$adj,$this->img->left_margin+$adj, $this->img->plotheight,$this->img->plotwidth); } $this->img->Destroy(); $this->img->SetCanvasH($oldimage); } if( !$_csim ) { $this->axis->Stroke($pos); $this->axis->StrokeAngleLabels($pos,$this->iType); } if( !$_csim ) { $this->StrokePlotBox(); $this->footer->Stroke($this->img); $aa = $this->img->SetAngle(0); $this->StrokeTitles(); } for($i=0; $i < count($this->plots) ; ++$i ) { $this->plots[$i]->Legend($this); } $this->legend->Stroke($this->img); if( !$_csim ) { $this->StrokeTexts(); $this->img->SetAngle($aa); if(_JPG_DEBUG) $this->DisplayClientSideaImageMapAreas(); if( $aStrokeFileName == _IMG_HANDLER ) { return $this->img->img; } else { $this->cache->PutAndStream($this->img,$this->cache_name,$this->inline, $aStrokeFileName); } } } } ?>