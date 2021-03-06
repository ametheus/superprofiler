<?php

/*
	Copyright (C) 2012 Thijs van Dijk
	
	This file is part of Superprofiler.

	Superprofiler is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Superprofiler is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Superprofiler.  If not, see <http://www.gnu.org/licenses/>.
*/

?><p>This is a page with simulated load for the profiler.
	The data you see is random.</p><?php


flush();
require_once( "profiler.inc" );

section( is_numeric($_REQUEST['sections']) ? $_REQUEST['sections'] : 6 );

$link = "view/profiler.html#" . Profiler::output();
$href = htmlspecialchars($link);

?>

<p>Finished generating data. Got <?=strlen(Profiler::output())?> bytes of data.</p>

<p><a href="<?=$href?>">Click here to open the development profiler</a></p>

<?=Profiler::write_link()?>

<?php


function section( $countdown )
{
	global $alph;
	$activities = array(
		"db",
		"cache read", "cache write",
		"fs read",    "fs write",
		"net",
		"xmldom",     "xslt"
	);
	
	if ( !isset($alph) ) $alph = 'A';
	
	Profiler::enter_section( $alph );
	
	$it = mt_rand( 3, 35 );
	
	for ( $i = 0; $i < $it; $i++ )
	{
		if ( mt_rand( 0, 31 ) < 3 )
		{
			if (( $countdown > 0 ) && ( $alph != 'Z' ))
			{
				$alph = chr( ord($alph) + 1 );
				section( $countdown - 1 );
			}
		}
		else
		{
			$act = St::exponential( 2/sqrt(count($activities)) );
			if ( $act >= count($activities) ) $act = 0;
			
			Profiler::start( $activities[$act] );
			
			// Simulate a load of some kind
			usleep( ($act+1)*St::normal( 2000, 600 ) );
			
			// Add notes
			//$nt = floor(stats_rand_gen_exponential( 0.3 ));
			$nt = mt_rand(-14,3);
			if ( $nt > 0 )
				for ( $j = 0; $j < $nt; $j++ )
					Profiler::annotate( "This is a note for <emph>{$activities[$act]}</emph>" );
			
			if ( mt_rand(0,($act == 5 ? 3 : 100)) == 0 )
				Profiler::error(mt_rand(0,5000));
			
			Profiler::stop();
		}
		
		if ( mt_rand(0,100) == 0 )
			Profiler::error(mt_rand(0,5000));
		
		if ( mt_rand(0,7) < 1 )
		{
			Profiler::annotate( "I felt compelled to tell you " . str_repeat( $alph, 5 ) . "!!" );
		}
	}
	
	Profiler::leave_section();
}




class St
{
	/**
	 * Generate a random number in the interval (0,1).
	 * 
	 * Using the parameters $closed_left and/or $closed_right, the interval may
	 * be closed to [0,1), (0,1], or [0,1].
	 **/
	public static function uniform( $closed_left = false, $closed_right = false )
	{
		// A large prime for the denominator
		$D = 17638261;
		
		$l = $closed_left  ?  0 :  1;
		$r = $closed_right ? $D : $D-1;
		
		return mt_rand( $l, $r ) / $D;
	}
	
	/** 
	 * Generate a random variable with a Gauss distribution
	 **/
	public static function normal( $mu = 0, $sigma = 1 )
	{
		if ( count(self::$_norm_buffer) == 0 )
			self::_fill_norm_buffer();
		
		return ( $sigma * array_shift(self::$_norm_buffer) ) + $mu;
	}
	private static $_norm_buffer = array();
	/**
	 * Generate two random variables with standard Gauss distribution, using
	 * the Box-Muller method.
	 **/
	private static function _fill_norm_buffer()
	{
		$U = self::uniform();
		$V = self::uniform();
		
		self::$_norm_buffer[] = sqrt(-2*log($U)) * cos(2*M_PI*$V);
		self::$_norm_buffer[] = sqrt(-2*log($U)) * sin(2*M_PI*$V);
	}
	
	
	/**
	 * Generate a random variable with an exponential distribution, 
	 * with mean ( 1/$lambda ).
	 **/
	public static function exponential( $lambda = 1 )
	{
		return ( -1*log(self::uniform()) ) / $lambda;
	}
}
