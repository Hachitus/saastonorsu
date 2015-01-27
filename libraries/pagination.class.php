<?php
/******************************************************************
Projectname:   php pagination class 
Version:       1.0
Author:        Radovan Janjic <rade@it-radionica.com>
Last modified: 25 09 2012
Copyright (C): 2011 IT-radionica.com, All Rights Reserved

* GNU General Public License (Version 2, June 1991)
*
* This program is free software; you can redistribute
* it and/or modify it under the terms of the GNU
* General Public License as published by the Free
* Software Foundation; either version 2 of the License,
* or (at your option) any later version.
*
* This program is distributed in the hope that it will
* be useful, but WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A
* PARTICULAR PURPOSE. See the GNU General Public License
* for more details.

Description:

php pagination class 

This class can generates pagination and return MySQL limit values.

Easy to set up parametars and appearance.
 
parametars:
    - pagination left from current
    - pagination right from current
    - link href
    - items count
    - items per page
    - current page
    
    
Example:

[prev] [1] ... [9] [10] [11] [12] [13] [14] [15] ... [25] [next]

**************************************************************************************************************
 
$p = new pagination;

// pagination left from current
$p->paginationLeft = 4; 

// pagination right from current
$p->paginationRight = 4; 

// link href
$p->path = '?example=[pageno]'; // or $p->path = 'example/[pageno]/';


// paginaion appearance
$p->appearance = array(
                    'nav_prev' => '<a href="[link]" class="prev"><span>prev</span></a>',
                    'nav_number_link' => '<a href="[link]"><span>[number]</span></a>',
                    'nav_number' => '<a href="javascript:;" class="active"><span>[number]</span></a>',
                    'nav_more' => '<a href="javascript:;" class="more"><span>...</span></a>',
                    'nav_next' => '<a href="[link]" class="next"><span>next</span></a>',
                );


// items count        
$p->setCount(500); 

// current page
if(isset($_GET['example'])){
    $p->setStart($_GET['example']);
}

// true to echo pagination
$p->display(true);


<p>SELECT * FROM some_table LIMIT <?php echo $p->getMySqlLimitStart(); ?>, <?php echo $p->getMySqlLimitEnd(); ?>
 

******************************************************************/

    class pagination
    {    
        var $limitStart = 0;
        var $count = 0;
        var $perPage = 15;
        var $paginationLeft = 3;
        var $paginationRight = 3;
        var $appearance = 
            array(
                'nav_prev' => '<a href="[link]" class="prev"><span>prev</span></a>',
                'nav_number_link' => '<a href="[link]"><span>[number]</span></a>',
                'nav_number' => '<a href="javascript:;" class="active">[number]</a>',
                'nav_more' => '<a href="javascript:;" class="more"><span>...</span></a>',
                'nav_next' => '<a href="[link]" class="next"><span>next</span></a>',
            );
                            
        var $path = 'example-url/[pageno]/';
        
        function pagination( $count = 0, $start = 0 ){
            $this->setCount($count);
            $this->setStart($start);
        }
        
        function setStart( $start = 0 ){
            // limit start
            $this->limitStart = $start > 0 ? (int) ($start - 1) * $this->perPage : 0;
        }
        
        function setCount( $count = 0 ){
            // citems count
            $this->count = $count > 0 ? (int) $count : 0;
        }
        
        function setAppearance($appearance = array()){
            $this->appearance = $appearance;    
        }
        
        function getMySqlLimitStart(){
            if($this->limitStart >= $this->count || $this->limitStart % $this->perPage != 0 ) 
                return 0;
            else
                return $this->limitStart;
        }
        
        function getMySqlLimitEnd(){
            return $this->perPage;
        }
        
        function display( $echo = false ){ 
            
            // [prev] 1 2 3 4 â€¦ 9 [next]
            
            if($this->limitStart >= $this->count || $this->limitStart % $this->perPage != 0 ) return NULL;
            
            $return = "";
            
            if( $this->count > $this->perPage){ // if all elements can not be placed on the page
                   
                if($this->limitStart > 0){ // prev    
                    $return .= str_replace('[link]', str_replace('[pageno]', ($this->limitStart - $this->perPage) / $this->perPage + 1, $this->path),  $this->appearance['nav_prev']); 
                }
                
                // dig.
                $k = $this->limitStart / $this->perPage;
                
                // no more then $this->paginationLeft left
                $min = $k - $this->paginationLeft;
                
                if($min < 0){ 
                    $min = 0; 
                }else{
                    if($min >= 1){ // link to 1. page
                        $number = 1;
                        
                        $link = str_replace('[pageno]', $number, $this->path);
                        $return .= str_replace(array('[link]', '[number]'), array($link, $number), $this->appearance['nav_number_link']); // no. first
                        
                        if ($min != 1) {  // ... not link
                            $return .= $this->appearance['nav_more']; 
                        };
                    }
                }
                
                for($i = $min; $i < $k; $i++){
                    $m = $i * $this->perPage + $this->perPage;
                    if ($m >  $this->count){ 
                        $m =  $this->count;
                    }
                    $number = $i + 1;
                    
                    $link = str_replace('[pageno]', $number, $this->path);
                    $return .= str_replace(array('[link]', '[number]'), array($link, $number), $this->appearance['nav_number_link']); // no. link                    
                }
                
                //# cur. page
                if(strcmp($this->limitStart, "all")){
                    $min = $this->limitStart + $this->perPage;
                    if($min >  $this->count){ 
                        $min =  $this->count;
                    }
                    
                    $return .= str_replace('[number]', $k + 1, $this->appearance['nav_number']); // no. not link
                }else{
                    $min = $this->perPage;
                    if($min >  $this->count){ 
                        $min =  $this->count;
                    }
                    
                    $number = 1;
                    $link = str_replace('[pageno]', $number, $this->path);
                    $return .= str_replace(array('[link]', '[number]'), array($link, $number), $this->appearance['nav_number_link']); // no. first link
                }
                
                // no more then $this->paginationRight on right
                $min = $k + $this->paginationRight + 1;
                if ($min >  $this->count / $this->perPage) { 
                    $min =  $this->count / $this->perPage; 
                };
                
                for ($i = $k + 1; $i < $min; $i++){
                    $m = $i * $this->perPage + $this->perPage;
                    if ($m > $this->count){ 
                        $m = $this->count;
                    }
                    
                    $number = $i + 1;
                    $link = str_replace('[pageno]', $number, $this->path);
                    $return .= str_replace(array('[link]', '[number]'), array($link, $number), $this->appearance['nav_number_link']); // no. link
                }
    
                if($min * $this->perPage <  $this->count){ // last item
                    if($min * $this->perPage <  $this->count - $this->perPage){ 
                        $return .= $this->appearance['nav_more']; // ... not link
                    }
                    if(!( $this->count % $this->perPage == 0)){
                        $number = floor( $this->count / $this->perPage) + 1;
                        
                        $link = str_replace('[pageno]', $number, $this->path);
                        $return .= str_replace(array('[link]', '[number]'), array($link, $number), $this->appearance['nav_number_link']);    // no. link
                    }else{ //  $this->count is dev by $this->perPage
                        $number = floor( $this->count / $this->perPage);
                    
                        $link = str_replace('[pageno]', $number, $this->path);
                        $return .= str_replace(array('[link]', '[number]'), array($link, $number), $this->appearance['nav_number_link']);    // max, last link
                    }
                }
                
                // next
                if($this->limitStart <  $this->count - $this->perPage){
                     $link = str_replace('[pageno]', ($this->limitStart + $this->perPage) / $this->perPage + 1, $this->path);
                    $return .= str_replace('[link]', $link, $this->appearance['nav_next']); // next
                }
            }
            
            if($echo)
                echo $return;
            else
                return $return;
        }
    
        function __toString(){
            return (string) (var_export($this, true));
        }
    } 