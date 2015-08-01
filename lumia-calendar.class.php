<?php
require_once( 'lumia-calender.php' );
$LumiaCalender		=	new Lumia_Calender;

class Calendar{
    /*Constructor for the Calendar class */
    function Calendar(){
    }
    /*
        Get the array of strings used to label the days of the week. This array contains seven 
        elements, one for each day of the week. The first entry in this array represents Sunday. 
    */
    function getDayNames(){
        return $this->dayNames;
    }    /*
        Set the array of strings used to label the days of the week. This array must contain seven 
        elements, one for each day of the week. The first entry in this array represents Sunday. 
    */
    function setDayNames( $names ){
        $this->dayNames = $names;
    }
    /*
        Get the array of strings used to label the months of the year. This array contains twelve 
        elements, one for each month of the year. The first entry in this array represents January. 
    */
    function getMonthNames(){
        return $this->monthNames;
    }
    /*
        Set the array of strings used to label the months of the year. This array must contain twelve 
        elements, one for each month of the year. The first entry in this array represents January. 
    */
    function setMonthNames( $names ){
        $this->monthNames = $names;
    }
    
    /* 
        Gets the start day of the week. This is the day that appears in the first column
        of the calendar. Sunday = 0.
    */
    function getStartDay(){
        return $this->startDay;
    }
    /* 
        Sets the start day of the week. This is the day that appears in the first column
        of the calendar. Sunday = 0.
    */
    function setStartDay( $day ){
        $this->startDay = $day;
    }
    /* 
        Gets the start month of the year. This is the month that appears first in the year
        view. January = 1.
    */
    function getStartMonth(){
        return $this->startMonth;
    }
    /* 
        Sets the start month of the year. This is the month that appears first in the year
        view. January = 1.
    */
    function setStartMonth( $month ){
        $this->startMonth = $month;
    }
    /*
        Return the URL to link to in order to display a calendar for a given month/year.
        You must override this method if you want to activate the "forward" and "back" 
        feature of the calendar.
        Note: If you return an empty string from this function, no navigation link will
        be displayed. This is the default behaviour.
        If the calendar is being displayed in "year" view, $month will be set to zero.
    */
    function getCalendarLink( $month, $year ){
        return "";
    }
    /*
        Return the URL to link to  for a given date.
        You must override this method if you want to activate the date linking
        feature of the calendar.
        Note: If you return an empty string from this function, no navigation link will
        be displayed. This is the default behaviour.
    */
    function getDateLink( $day, $month, $year ){
        return "";
    }    /*        Return the HTML for the current month    */
    function getCurrentMonthView(){
        $d 			=	getdate( time() );
        return $this->getMonthView( $d["mon"], $d["year"]);
    }    /*
        Return the HTML for the current year
    */
    function getCurrentYearView(){
        $d 			=	getdate( time() );
        return $this->getYearView( $d["year"] );
    }
    /*
        Return the HTML for a specified month
    */
    function getMonthView( $month, $year ){
        return $this->getMonthHTML( $month, $year );    }
	function getMonthViewMeetings( $month, $year, $gm, $g ){
        return $this->getMonthHTMLMeetings( $month, $year, $gm, $g );    }    /*
        Return the HTML for a specified year
    */
    function getYearView( $year ){
        return $this->getYearHTML( $year );
    }
    
    /********************************************************************************
        The rest are private methods. No user-servicable parts inside.
        You shouldn't need to call any of these functions directly.
    *********************************************************************************/    /*
        Calculate the number of days in a month, taking into account leap years.
    */
    function getDaysInMonth($month, $year){
        if ($month < 1 || $month > 12):
            return 0;
        endif;
        $d 			=	$this->daysInMonth[$month - 1];
        if ( $month == 2 ):// Check for leap year // Forget the 4000 rule, I doubt I'll be around then...
            if ($year%4 == 0):
                if ($year%100 == 0):
                    if ($year%400 == 0):
                        $d = 29;
                    endif;
                else:
                    $d = 29;
                endif;
            endif;
        endif;
        return $d;
    }  
	
	  
	/*
        Generate the HTML for a given month
    */
    function getMonthHTML( $m, $y, $showYear = 1 ){
		
		global $plugin_url, $wpdb, $LumiaCalender;
		
        $s 					=	"";
        $a 					=	$this->adjustDate( $m, $y );
        $month 				=	$a[0];
        $year 				=	$a[1];        
    	$daysInMonth 		=	$this->getDaysInMonth( $month, $year );
    	$date 				=	getdate( mktime( 12, 0, 0, $month, 1, $year ) );
    	$first 				=	$date["wday"];
    	$monthName 			=	$this->monthNames[$month - 1];
    	$prev 				=	$this->adjustDate( $month - 1, $year );
    	$next 				=	$this->adjustDate( $month + 1, $year );
		
    	if ( $showYear == 1 ):
				$prevMonth 	=	"return getMonthHTML( " . $prev[0] . ", " . $prev[1] . " )";
				$nextMonth 	=	"return getMonthHTML( " . $next[0] . ", " . $next[1] . " )";
    	else:
    	    $prevMonth 		=	"";
    	    $nextMonth 		=	"";
    	endif;
    	$header 			=	$monthName . ( ( $showYear > 0 ) ? " " . $year : "" );
		$s .=  "<div class=\"ajax_loader\"></div>";  
    	$s .= "<table class=\"yearname\" width=\"100%\"><tbody><tr>";
    	$s .= "<td colspan=\"3\" align=\"left\" valign=\"middle\">" . (($prevMonth == "") ? "&nbsp;" : "<a href=\"javascript:;\" onclick=\"$prevMonth\" class='month_name prev'><img src=\"$plugin_url/images/left-arrow.png\" /></a>")  . "\n";
    	$s .= "<span class='green2' id=\"currmonthyear\">$header</span>\n"; 
    	$s .= (($nextMonth == "") ? "&nbsp;" : "<a  href=\"javascript:;\" onclick=\"$nextMonth\" class='month_name next'><img src=\"$plugin_url/images/right-arrow.png\" /></a>")  . "</td>\n";
		$s .= "</tr></tbody></table>";
    	$s .= "<table class=\"eventcalender\" cellpadding=\"1\" cellspacing=\"1\" width=\"100%\" >\n";
    	$s .= "<thead><tr class=\"weektitle\">\n";
    	$s .= "<th align=\"center\" valign=\"top\" class=\"daytitle ec-first\">" . $this->dayNames[($this->startDay)%7] . "</th>\n";
    	$s .= "<th align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayNames[($this->startDay+1)%7] . "</th>\n";
    	$s .= "<th align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayNames[($this->startDay+2)%7] . "</th>\n";
    	$s .= "<th align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayNames[($this->startDay+3)%7] . "</th>\n";
    	$s .= "<th align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayNames[($this->startDay+4)%7] . "</th>\n";
    	$s .= "<th align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayNames[($this->startDay+5)%7] . "</th>\n";
    	$s .= "<th align=\"center\" valign=\"top\" class=\"daytitle ec-last\">" . $this->dayNames[($this->startDay+6)%7] . "</th>\n";
    	$s .= "</tr></thead><tbody>\n";
    	// We need to work out what date to start at so that the first appears in the correct column
    	$d 			=	$this->startDay + 1 - $first;
    	while ( $d > 1 ):
    	    $d -= 7;
    	endwhile;

        $today 		= getdate( time() );
		$cnt		=	1;
    	while ( $d <= $daysInMonth ):
			$first	=	( $cnt == 1 ) ? ' cc-first' : '';
			$last	=	( $cnt == 5 ) ? ' cc-last' : '';
    	    $s 		.=	"<tr class=\"ec-week" . $cnt . $first . $last . "\">\n";       
    	    $to_day	=	date( 'j' );
    	    for ( $i = 0; $i < 7; $i++ ):
				$thisMonth	=	date( 'n' );
				$month;
				if( $thisMonth == $month ):
					if( $to_day == $d ):
							$className2 = 'calenderBckGrnd';
					else:
							$className2 = 'blue';
					endif;
				else:
					$className2 = 'blue';
				endif;
				
				$startDate			=	$year . "-" . $month . "-" . "01";
				$endDate			=	$year . "-" . $month . "-" . "31";
				$currDate			=	$year . "-" . $month . "-" . $d;
				$time				=	strtotime( $currDate );
				$dates				=	$LumiaCalender->getDatesFromNo( $startDate, $endDate );

				if( $dates != '' ):
					$dates			=	explode( ",", $dates );
				endif;
				
				if( $year == $today["year"] && $month == $today["mon"] && $d == $today["mday"] && !@in_array( $d, $dates ) ): 
					$class 	=	"today"; 
				elseif ( @in_array( $d, $dates ) && $dates != '' && $d != 0 ):
					$class 	=	"eventday";
					$event_anchor_start		=	'<a class="event" href="#event_content_' . $d . '">';
					$event_anchor_end		=	'</a>';
				else:
					$class 	=	"ecday";
					$event_anchor_start		=	'';
					$event_anchor_end		=	'';
				endif;
				
				$tfirst	=	( $i == 0 ) ? ' cc-first' : '';
				$tlast	=	( $i == 6 ) ? ' cc-last' : '';
				
    	        $s .= "<td width=\"14.29%\" class=\"$class" . "$tfirst" . "$tlast\" align=\"center\" valign=\"middle\">" . $event_anchor_start . "<span class='" . $className2 . "'>";
    	        if ( $d > 0 && $d <= $daysInMonth ):
					$sel_day  = @$_REQUEST['day'];
					if( $sel_day!='' ):
						if( $sel_day == $d ):
								$classLink='calenderDateSeleBckGrnd';
						else:
							$classLink='calenderDate';	
						 endif;
					else:
							$classLink='calenderDate';	
					endif;
    	            $link = $this->getDateLink( $d, $month, $year );
    	            $s .= (( $link == "" ) ? $d : "<a style=text-decoration:none href=\"$link\" class='".$classLink."'>$d</a>" );
						
    	        else:
    	            $s .= "&nbsp;";
					$add_event_link			=	'';
    	        endif;
      	        $s .= "</span>" . $event_anchor_end ."</td>\n";  
				
				if ( @in_array( $d, $dates ) && $dates != '' && $d != 0 ):
					$s .= "<div style='display:none'>
								<div id='event_content_" . $d ."' class='event_content'>
									<div class=\"event_box\">
										<h3>Events on " . date( 'F j, Y', strtotime( $year . '-' . $month . '-' . $d ) ) . "</h3>
										<ul>" . $LumiaCalender->getProjectIds( $d, $month, $year ) . "</ul>
									</div>
								</div>
							</div>";
				endif;  
				
        	    $d++;
    	    endfor;
    	    $s .= "</tr>\n";  
			$cnt++;
		endwhile;
    	$s .= "</tbody></table>\n";
    	return $s;  	
    }    
	
	/*
        Generate the HTML widject for a given month
    */
    function getMonthWidjectHTML( $m, $y, $showYear = 1 ){
		global $plugin_url;
        $s 				=	"";
        $a 				=	$this->adjustDate( $m, $y );
        //$month 			=	( $a[0] != 10 || $a[0] != 11 || $a[0] != 12 ) ? '0' . $a[0] : $a[0];
        $month 			=	$a[0];
        $year 			=	$a[1];        
    	$daysInMonth 	=	$this->getDaysInMonth( $month, $year );
    	$date 			=	getdate( mktime( 12, 0, 0, $month, 1, $year ) );
    	$first 			=	$date["wday"];
    	$monthName 		=	$this->monthNames[$month - 1];
    	$prev 			=	$this->adjustDate( $month - 1, $year );
    	$next 			=	$this->adjustDate( $month + 1, $year );
    	if ( $showYear == 1):
			$prevMonth 	=	"onClick=\"return setPrevNextMonth( " . $prev[0] . ", " . $prev[1] . ", '" . $aparttype . "' );\"";
    	    $nextMonth 	=	"onClick=\"return setPrevNextMonth( " . $next[0] . ", " . $next[1] . ", '" . $aparttype . "' );\"";
    	else:
    	    $prevMonth 	=	"";
    	    $nextMonth 	=	"";
    	endif;
    	$header 		=	$monthName . ( ( $showYear > 0 ) ? " " . $year : "" );
    	$s .= "<table class=\"shedulemaster\" cellpadding=\"1\" cellspacing=\"1\" >\n";
    	$s .= "<tr class=\"calendartop\">\n";
		$s .= "<td valign=\"middle\" align=\"right\" colspan=\"7\"><table class=\"yearname\"  width=\"200\"><tbody><tr>";
    	$s .= "<td align=\"left\" valign=\"middle\">" . (($prevMonth == "") ? "&nbsp;" : "<a href=\"javascript:;\" $prevMonth class='commonTopLinks prevmonth'></a>")  . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"middle\" class=\"calendarDate\" colspan=\"5\"><span class='green2' id=\"currmonthyear\">$header</span></td>\n"; 
    	$s .= "<td align=\"right\" valign=\"middle\">" . (($nextMonth == "") ? "&nbsp;" : "<a href=\"javascript:;\" $nextMonth class='commonTopLinks nextmonth'></a>")  . "</td>\n";
		$s .= "</tr></tbody></table></td>";
    	$s .= "</tr>\n";
    	$s .= "<tr>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayWijtNames[($this->startDay)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayWijtNames[($this->startDay+1)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayWijtNames[($this->startDay+2)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayWijtNames[($this->startDay+3)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayWijtNames[($this->startDay+4)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayWijtNames[($this->startDay+5)%7] . "</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" class=\"daytitle\">" . $this->dayWijtNames[($this->startDay+6)%7] . "</td>\n";
    	$s .= "</tr>\n";
    	// We need to work out what date to start at so that the first appears in the correct column
    	$d 			=	$this->startDay + 1 - $first;
    	while ( $d > 1 ):
    	    $d -= 7;
    	endwhile;
        // Make sure we know when today is, so that we can use a different CSS style
        $today 		= getdate( time() );
    	while ( $d <= $daysInMonth ):
    	    $s 		.=	"<tr>\n";       
    	    $to_day	=	date( 'j' );
    	    for ( $i = 0; $i < 7; $i++ ):
				$thisMonth	=	date( 'n' );
				$month;
				if( $thisMonth == $month ):
					if( $to_day == $d ):
							$className2 = 'calenderBckGrnd';
					else:
							$className2 = 'blue';
					endif;
				else:
					$className2 = 'blue';
				endif;

    	        $s .= "<td class=\"$class\" align=\"center\" valign=\"middle\">" . $status . "<a href=\"javascript:;\" $click><span class='" . $className2 . "'>";
    	        if ( $d > 0 && $d <= $daysInMonth ):
					$sel_day  = $_REQUEST['day'];
					if( $sel_day!='' ):
						if( $sel_day == $d ):
								$classLink='calenderDateSeleBckGrnd';
						else:
							$classLink='calenderDate';	
						 endif;
					else:
							$classLink='calenderDate';	
					endif;
    	            $link = $this->getDateLink( $d, $month, $year );
    	            $s .= (( $link == "" ) ? $d : "<a style=text-decoration:none href=\"$link\" class='".$classLink."'>$d</a>" );
    	        else:
    	            $s .= "";
    	        endif;
      	        $s .= "</span></a></td>\n";       
        	    $d++;
    	    endfor;
    	    $s .= "</tr>\n";    
		endwhile;
    	$s .= "</table>\n";
    	return $s;  	
    }    
	
	/*
        Generate the HTML for a given year
    */
    function getYearHTML($year)
    {
        $s = "";
    	$prev = $this->getCalendarLink(0, $year - 1);
    	$next = $this->getCalendarLink(0, $year + 1);
        $s .= "<table class=\"calendar\" border=\"0\">\n";
        $s .= "<tr>";
    	$s .= "<td align=\"center\" valign=\"top\" align=\"left\">" . (($prev == "") ? "&nbsp;" : "<a href=\"$prev\">&lt;&lt;</a>")  . "</td>\n";
        $s .= "<td class=\"calendarHeader\" valign=\"top\" align=\"center\">" . (($this->startMonth > 1) ? $year . " - " . ($year + 1) : $year) ."</td>\n";
    	$s .= "<td align=\"center\" valign=\"top\" align=\"right\">" . (($next == "") ? "&nbsp;" : "<a href=\"$next\">&gt;&gt;</a>")  . "</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(0 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(1 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(2 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(3 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(4 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(5 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(6 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(7 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(8 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "<tr>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(9 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(10 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(11 + $this->startMonth, $year, 0) ."</td>\n";
        $s .= "</tr>\n";
        $s .= "</table>\n";
        return $s;
    }
    /*
        Adjust dates to allow months > 12 and < 0. Just adjust the years appropriately.
        e.g. Month 14 of the year 2001 is actually month 2 of year 2002.
    */
    function adjustDate( $month, $year ){
        $a 					=	array();  
        $a[0] 				=	$month;
        $a[1] 				=	$year;
		
        while ( $a[0] > 12 ):
            $a[0] -= 12;
            $a[1]++;
        endwhile;
        while ( $a[0] <= 0 ):
            $a[0] += 12;
            $a[1]--;
        endwhile;
		
        return $a;
    }
    /* 
        The start day of the week. This is the day that appears in the first column
        of the calendar. Sunday = 0.
    */
    var $startDay 		=	0;
    /* 
        The start month of the year. This is the month that appears in the first slot
        of the calendar in the year view. January = 1.
    */
    var $startMonth 	=	1;
    /*
        The labels to display for the days of the week. The first entry in this array
        represents Sunday.
    */
	var $dayNames 		=	array( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" );
	var $dayWijtNames 	=	array( "SU", "MO", "TU", "WE", "TH", "FR", "SA" );
    /*
        The labels to display for the months of the year. The first entry in this array
        represents January.
    */
    var $monthNames 	=	array( "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" );
    /*
        The number of days in each month. You're unlikely to want to change this...
        The first entry in this array represents January.
    */
    var $daysInMonth 	=	array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
}